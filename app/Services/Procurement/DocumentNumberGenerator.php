<?php

namespace App\Services\Procurement;

use App\Models\MsuGrn;
use App\Models\MsuPurchaseOrder;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    /**
     * Allocate the next sequential document number: PREFIX-0001.
     * Supports legacy date-bucket numbers (e.g. PR-20260720-0001) when computing the next sequence.
     */
    public function next(string $prefixKey, string $modelClass, string $column): string
    {
        $prefixRoot = strtoupper((string) config("procurement.document_prefixes.{$prefixKey}", strtoupper($prefixKey)));
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_'.$prefixKey;

        return DB::transaction(function () use ($prefixRoot, $padding, $modelClass, $column, $lockName) {
            $this->acquireLock($lockName);

            try {
                return $this->nextAvailableNumber($prefixRoot, $padding, $modelClass, $column);
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    /**
     * Reserve the next document number and run $callback while the lock is held.
     * Use this when persisting the record in the same critical section to avoid duplicates.
     */
    public function allocate(string $prefixKey, string $modelClass, string $column, callable $callback): mixed
    {
        $prefixRoot = strtoupper((string) config("procurement.document_prefixes.{$prefixKey}", strtoupper($prefixKey)));
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_'.$prefixKey;

        return DB::transaction(function () use ($prefixRoot, $padding, $modelClass, $column, $lockName, $callback) {
            $this->acquireLock($lockName);

            try {
                $number = $this->nextAvailableNumber($prefixRoot, $padding, $modelClass, $column);

                return $callback($number);
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextAvailableNumber(string $prefixRoot, int $padding, string $modelClass, string $column): string
    {
        $nextSeq = $this->nextSequence($prefixRoot, $modelClass, $column) + 1;

        do {
            $number = $prefixRoot.'-'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
            $nextSeq++;
        } while ($this->numberExists($modelClass, $column, $number));

        return $number;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextSequence(string $prefixRoot, string $modelClass, string $column): int
    {
        /** @var Builder $query */
        $query = $this->includesTrashedRecords($modelClass)
            ? $modelClass::withTrashed()->where($column, 'like', $prefixRoot.'-%')
            : $modelClass::query()->where($column, 'like', $prefixRoot.'-%');

        $codes = $query
            ->lockForUpdate()
            ->pluck($column);

        $max = 0;
        $pattern = '/^'.preg_quote($prefixRoot, '/').'-(?:\d{6,8}-)?(\d+)$/i';

        foreach ($codes as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function numberExists(string $modelClass, string $column, string $number): bool
    {
        $query = $this->includesTrashedRecords($modelClass)
            ? $modelClass::withTrashed()->where($column, $number)
            : $modelClass::query()->where($column, $number);

        return $query->exists();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function includesTrashedRecords(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }

    private function acquireLock(string $lockName): void
    {
        $result = DB::selectOne('SELECT GET_LOCK(?, 10) AS locked', [$lockName]);
        $locked = (int) ($result->locked ?? 0);

        if ($locked !== 1) {
            throw new Exceptions\ProcurementException(
                "Could not acquire document number lock [{$lockName}]."
            );
        }
    }

    private function releaseLock(string $lockName): void
    {
        DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }

    /**
     * Allocate next compact code: prefix + padded sequence (e.g. com0001).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function nextCompact(string $prefix, string $modelClass, string $column): string
    {
        $prefixRoot = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $prefix) ?? '');
        if ($prefixRoot === '') {
            $prefixRoot = 'ITM';
        }

        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_compact_'.$prefixRoot;

        return DB::transaction(function () use ($prefixRoot, $padding, $modelClass, $column, $lockName) {
            $this->acquireLock($lockName);

            try {
                $max = 0;
                $pattern = '/^'.preg_quote($prefixRoot, '/').'(\d+)$/i';

                $query = $this->includesTrashedRecords($modelClass)
                    ? $modelClass::withTrashed()->where($column, 'like', $prefixRoot.'%')
                    : $modelClass::query()->where($column, 'like', $prefixRoot.'%');

                $codes = $query->lockForUpdate()->pluck($column);

                foreach ($codes as $code) {
                    if (preg_match($pattern, (string) $code, $matches)) {
                        $max = max($max, (int) $matches[1]);
                    }
                }

                $nextSeq = $max + 1;
                do {
                    $number = $prefixRoot.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while ($this->numberExists($modelClass, $column, $number));

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    /**
     * MSU PO format: MSULLP/{BRANCH}/PO/{FY}/{MM}/{SEQ}
     * Example: MSULLP/BLR/PO/2627/08/0001
     * SEQ continues across months for the same company, branch, and FY, and restarts at 0001 on 1 April.
     */
    public function nextMsuPoNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $poDate = null): string
    {
        $prefix = $this->msuPoPrefix($companyCode, $branchCode, $poDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_msu_po_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextMsuPoSequence($prefix, true);
                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (MsuPurchaseOrder::query()->where('po_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    /**
     * Preview next MSU PO number without reserving it.
     */
    public function previewMsuPoNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $poDate = null): string
    {
        $prefix = $this->msuPoPrefix($companyCode, $branchCode, $poDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextMsuPoSequence($prefix, false);

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    public function msuPoPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $poDate = null): string
    {
        $poDate = $poDate
            ? \Carbon\Carbon::parse($poDate)
            : now();

        $company = $companyCode ?: 'MSULLP';
        $branch = $this->normalizeBranchCode($branchCode);
        $module = 'PO';
        $fy = $this->financialYearCode($poDate);
        $month = $poDate->format('m');

        return implode('/', [$company, $branch, $module, $fy, $month]);
    }

    /**
     * MSU GRN format: MSULLP/{BRANCH}/GRN/{FY}/{MM}/{SEQ}
     * Example: MSULLP/BLR/GRN/2627/08/0001
     */
    public function nextMsuGrnNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $grnDate = null): string
    {
        $prefix = $this->msuGrnPrefix($companyCode, $branchCode, $grnDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_msu_grn_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextMsuGrnSequence($prefix, true);
                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (MsuGrn::query()->where('grn_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    public function previewMsuGrnNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $grnDate = null): string
    {
        $prefix = $this->msuGrnPrefix($companyCode, $branchCode, $grnDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextMsuGrnSequence($prefix, false);

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    public function msuGrnPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $grnDate = null): string
    {
        $grnDate = $grnDate
            ? \Carbon\Carbon::parse($grnDate)
            : now();

        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'GRN',
            $this->financialYearCode($grnDate),
            $grnDate->format('m'),
        ]);
    }

    private function nextMsuGrnSequence(string $prefix, bool $forUpdate = false): int
    {
        $query = MsuGrn::query();
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->nextFySequence($query, 'grn_number', $prefix);
    }

    private function nextMsuPoSequence(string $prefix, bool $forUpdate = false): int
    {
        $query = MsuPurchaseOrder::query();
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->nextFySequence($query, 'po_number', $prefix);
    }

    public function normalizeBranchCode(?string $codeOrName): string
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $codeOrName) ?? '');
        if ($raw === '') {
            return 'BRN';
        }

        return substr($raw, 0, 3);
    }

    public function financialYearCode(\DateTimeInterface $date): string
    {
        $carbon = \Carbon\Carbon::instance(\Carbon\Carbon::parse($date));
        $year = (int) $carbon->format('Y');
        $month = (int) $carbon->format('n');

        // Indian FY: Apr–Mar → Aug 2026 => 2627
        if ($month >= 4) {
            return substr((string) $year, -2).substr((string) ($year + 1), -2);
        }

        return substr((string) ($year - 1), -2).substr((string) $year, -2);
    }

    public function nextMrNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $date = null): string
    {
        $prefix = $this->mrPrefix($companyCode, $branchCode, $date);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_mr_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextFySequence(
                    \App\Models\MaterialRequest::withTrashed()->lockForUpdate(),
                    'mr_number',
                    $prefix
                );

                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (\App\Models\MaterialRequest::withTrashed()->where('mr_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    public function previewMrNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $date = null): string
    {
        $prefix = $this->mrPrefix($companyCode, $branchCode, $date);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextFySequence(
            \App\Models\MaterialRequest::withTrashed(),
            'mr_number',
            $prefix
        );

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    public function mrPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $date = null): string
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'MR',
            $this->financialYearCode($date),
            $date->format('m'),
        ]);
    }

    public function nextMiNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $date = null): string
    {
        $prefix = $this->miPrefix($companyCode, $branchCode, $date);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_mi_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextFySequence(
                    \App\Models\MaterialIssue::withTrashed()->lockForUpdate(),
                    'mi_number',
                    $prefix
                );

                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (\App\Models\MaterialIssue::withTrashed()->where('mi_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    public function miPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $date = null): string
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'MI',
            $this->financialYearCode($date),
            $date->format('m'),
        ]);
    }

    public function nextMsuWoNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $woDate = null): string
    {
        $prefix = $this->msuWoPrefix($companyCode, $branchCode, $woDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_msu_wo_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextMsuWoSequence($prefix, true);
                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (\App\Models\MsuWorkOrder::query()->where('wo_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    public function previewMsuWoNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $woDate = null): string
    {
        $prefix = $this->msuWoPrefix($companyCode, $branchCode, $woDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextMsuWoSequence($prefix, false);

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    private function nextMsuWoSequence(string $prefix, bool $forUpdate = false): int
    {
        $query = \App\Models\MsuWorkOrder::query();
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->nextFySequence($query, 'wo_number', $prefix);
    }

    public function msuWoPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $woDate = null): string
    {
        $woDate = $woDate ? \Carbon\Carbon::parse($woDate) : now();
        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'WO',
            $this->financialYearCode($woDate),
            $woDate->format('m'),
        ]);
    }

    /**
     * Support ticket format: {COMPANY}/{BRANCH}/ST/{FY}/{MM}/{SEQ}
     * Example: MSULLP/BLR/ST/2627/10/0001
     * SEQ continues across months for the same company, branch, and FY, and restarts at 0001 on 1 April.
     */
    public function nextSupportTicketNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $ticketDate = null): string
    {
        $prefix = $this->supportTicketPrefix($companyCode, $branchCode, $ticketDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_st_'.md5($this->fyScopePrefix($prefix));

        $generate = function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextSupportTicketSequence($prefix, true);
                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (SupportTicket::query()->where('ticket_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        };

        if (DB::transactionLevel() > 0) {
            return $generate();
        }

        return DB::transaction($generate);
    }

    public function previewSupportTicketNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $ticketDate = null): string
    {
        $prefix = $this->supportTicketPrefix($companyCode, $branchCode, $ticketDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextSupportTicketSequence($prefix, false);

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    public function supportTicketPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $ticketDate = null): string
    {
        $ticketDate = $ticketDate ? \Carbon\Carbon::parse($ticketDate) : now();

        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'ST',
            $this->financialYearCode($ticketDate),
            $ticketDate->format('m'),
        ]);
    }

    private function nextSupportTicketSequence(string $prefix, bool $forUpdate = false): int
    {
        $query = SupportTicket::query();
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->nextFySequence($query, 'ticket_number', $prefix);
    }

    public function nextMsuWiNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $wiDate = null): string
    {
        $prefix = $this->msuWiPrefix($companyCode, $branchCode, $wiDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $lockName = 'procurement_doc_msu_wi_'.md5($this->fyScopePrefix($prefix));

        return DB::transaction(function () use ($prefix, $padding, $lockName) {
            $this->acquireLock($lockName);

            try {
                $nextSeq = $this->nextFySequence(
                    \App\Models\MsuWorkInvoice::query()->lockForUpdate(),
                    'wi_number',
                    $prefix
                );

                do {
                    $number = $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
                    $nextSeq++;
                } while (\App\Models\MsuWorkInvoice::query()->where('wi_number', $number)->exists());

                return $number;
            } finally {
                $this->releaseLock($lockName);
            }
        });
    }

    public function previewMsuWiNumber(string $companyCode, string $branchCode, \DateTimeInterface|string|null $wiDate = null): string
    {
        $prefix = $this->msuWiPrefix($companyCode, $branchCode, $wiDate);
        $padding = (int) config('procurement.document_sequence_padding', 4);
        $nextSeq = $this->nextFySequence(
            \App\Models\MsuWorkInvoice::query(),
            'wi_number',
            $prefix
        );

        return $prefix.'/'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * COMPANY/BRANCH/MODULE/FY/MM → COMPANY/BRANCH/MODULE/FY
     */
    private function fyScopePrefix(string $monthPrefix): string
    {
        $slash = strrpos($monthPrefix, '/');

        return $slash === false ? $monthPrefix : substr($monthPrefix, 0, $slash);
    }

    /**
     * Highest sequence under the financial year, across every month.
     * The printed number still includes the document month.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function nextFySequence(Builder $query, string $column, string $monthPrefix): int
    {
        $fyPrefix = $this->fyScopePrefix($monthPrefix);
        $pattern = '/^'.preg_quote($fyPrefix, '/').'\/\d{2}\/(\d+)$/i';
        $max = 0;

        foreach ($query->where($column, 'like', $fyPrefix.'/%')->pluck($column) as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    public function msuWiPrefix(string $companyCode, string $branchCode, \DateTimeInterface|string|null $wiDate = null): string
    {
        $wiDate = $wiDate ? \Carbon\Carbon::parse($wiDate) : now();
        return implode('/', [
            $companyCode ?: 'MSULLP',
            $this->normalizeBranchCode($branchCode),
            'WI',
            $this->financialYearCode($wiDate),
            $wiDate->format('m'),
        ]);
    }
}

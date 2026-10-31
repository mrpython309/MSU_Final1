<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketReportExportService
{
    private const TICKET_COLUMN_ORDER = [
        'subject',
        'priority',
        'status',
        'company_id',
        'branch_id',
        'department_id',
        'due_date',
        'created_by',
        'asset_id',
    ];

    private const TICKET_COLUMN_LABELS = [
        'subject' => 'Subject',
        'priority' => 'Priority',
        'status' => 'Status',
        'company_id' => 'Company',
        'branch_id' => 'Branch',
        'department_id' => 'Department',
        'due_date' => 'Due Date',
        'created_by' => 'Created By',
        'asset_id' => 'Asset',
    ];

    private const PRIORITY_LABELS = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public function ticketHeaders(array $tableColumns): array
    {
        $headers = ['#'];

        foreach (self::TICKET_COLUMN_ORDER as $column) {
            if (in_array($column, $tableColumns, true)) {
                $headers[] = self::TICKET_COLUMN_LABELS[$column];
            }
        }

        $headers[] = 'Collaborators';

        return $headers;
    }

    public function ticketRow(SupportTicket $ticket, int $index, array $tableColumns): array
    {
        $row = [$index];

        foreach (self::TICKET_COLUMN_ORDER as $column) {
            if (! in_array($column, $tableColumns, true)) {
                continue;
            }

            $row[] = $this->ticketCellValue($ticket, $column);
        }

        $row[] = $ticket->activeAssignments
            ->map(fn ($assignment) => $assignment->user?->name)
            ->filter()
            ->implode(', ');

        return $row;
    }

    public function downloadTicketCsv(Collection $tickets, array $tableColumns, string $filename): StreamedResponse
    {
        $headers = $this->ticketHeaders($tableColumns);

        return response()->streamDownload(function () use ($tickets, $tableColumns, $headers) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($tickets as $index => $ticket) {
                fputcsv($handle, $this->ticketRow($ticket, $index + 1, $tableColumns));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function userWiseHeaders(): array
    {
        return [
            '#',
            'Emp Code',
            'Name',
            'Designation',
            'Department',
            'Company',
            'Branch',
            'Accepted',
            'WIP',
            'On Hold',
            'Completed',
            'Closed',
            'Total',
        ];
    }

    public function userWiseRow(User $user, int $index): array
    {
        return [
            $index,
            (string) ($user->emp_code ?? ''),
            (string) ($user->name ?? ''),
            (string) ($user->designation?->designation_name ?? ''),
            $user->departments->pluck('department_name')->filter()->implode(', ') ?: '-',
            $user->companies->pluck('name')->filter()->implode(', ') ?: '-',
            $user->branches->pluck('name')->filter()->implode(', ') ?: '-',
            (string) ($user->accepted_count ?? 0),
            (string) ($user->work_in_progress_count ?? 0),
            (string) ($user->hold_count ?? 0),
            (string) ($user->completed_count ?? 0),
            (string) ($user->closed_count ?? 0),
            (string) ($user->total_count ?? 0),
        ];
    }

    public function downloadUserWiseCsv(Collection $users, string $filename): StreamedResponse
    {
        $headers = $this->userWiseHeaders();

        return response()->streamDownload(function () use ($users, $headers) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($users as $index => $user) {
                fputcsv($handle, $this->userWiseRow($user, $index + 1));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function ticketCellValue(SupportTicket $ticket, string $column): string
    {
        return match ($column) {
            'subject' => trim((string) $ticket->subject),
            'priority' => self::PRIORITY_LABELS[(string) $ticket->priority] ?? (string) $ticket->priority,
            'status' => SupportTicketProgressStatus::plain((string) $ticket->status),
            'company_id' => (string) ($ticket->company?->name ?? ''),
            'branch_id' => (string) ($ticket->branch?->name ?? ''),
            'department_id' => (string) ($ticket->department?->department_name ?? ''),
            'due_date' => $ticket->due_date?->format('d M Y') ?? '',
            'created_by' => (string) ($ticket->creator?->name ?? ''),
            'asset_id' => trim(implode(' — ', array_filter([
                $ticket->asset?->asset_code,
                $ticket->asset?->asset_name,
            ]))),
            default => '',
        };
    }
}

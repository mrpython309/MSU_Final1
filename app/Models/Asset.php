<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\AssetStatus;
use App\AssetCondition;
use App\AssetOperationalStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\IconTrait;

class Asset extends Model
{
    use HasFactory, IconTrait;

     const FILE_PATH = 'assets';   

     const ASSET_PREFIX='MSU';

    protected $fillable = [
    'asset_name',
    'asset_structure',
    'items_count',
    'category_id',
    'sub_category_id',
    'product_category_id',
    'product_sub_category_id',
    'asset_type_id',
    'serial_no',
    'hsn_code',
    'asset_code',
    'uom',
    'purchase_date',
    'purchase_cost',
    'status',
    'operational_status',
    'condition',
    'description',
    'company_id',
    'branch_id',
    'location_id',
    'supplier',
    'grn_item_id',
    'msu_grn_item_id',
    'source_unit_index',
    'purchase_order_id',
    'indent_id',
    'department_id',
    'brand_id',
    'invoice_details',
    'po_number',
    'po_date',
    'invoice_date',
    'purchase_type',
    'ownership_type',
    'cost_centre',
    'custodian_id',
    'warrenty_information',
    'warrenty_expires',
    'depreciation_method',
    'userful_life',
    'salvage_value',
    'filename',
    'created_by',
    'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($asset) {
            if (! $asset->asset_code && $asset->company_id && $asset->branch_id) {
                $asset->asset_code = static::generateAssetCode((int) $asset->company_id, (int) $asset->branch_id);
            }

            if ($asset->asset_code && preg_match('#^(.*)/(\d+)$#', (string) $asset->asset_code, $matches)) {
                $asset->asset_prefix = $matches[1];
                $asset->asset_nos = (int) $matches[2];
            } elseif ($asset->asset_code && preg_match('/(\d+)$/', (string) $asset->asset_code, $matches)) {
                $asset->asset_prefix = Asset::ASSET_PREFIX;
                $asset->asset_nos = (int) $matches[1];
            }

            $asset->created_by = Auth::id();
        });

        static::updating(function ($asset) {
            $asset->updated_by = Auth::id();
        });

        static::saving(function ($asset) {
            $status = $asset->status;
            $statusValue = $status instanceof AssetStatus ? $status->value : (string) $status;

            if ($statusValue === AssetStatus::DISPOSED->value) {
                $asset->operational_status = AssetOperationalStatus::DISPOSED;
                $asset->condition = AssetCondition::DISPOSED;
            }
        });
    }

    protected $casts = [
        'status' => AssetStatus::class,
        'condition' => AssetCondition::class,
        'operational_status' => AssetOperationalStatus::class,
        'purchase_date' => 'date',
        'po_date' => 'date',
        'invoice_date' => 'date',
        'audited_at' => 'datetime',
    ];

    /**
     * SQL expression (MySQL/SQLite) mapping each asset row to an {@see AssetStatus} value string
     * for dashboards: includes operational under-maintenance and open maintenance tickets, not only `status`.
     *
     * @param  string|null  $table  Table name/alias (defaults to this model's table).
     */
    public static function sqlChartStatusBucketExpression(?string $table = null): string
    {
        $t = $table ?? (new static)->getTable();

        return "(
            CASE
                WHEN LOWER(COALESCE(`{$t}`.`status`, '')) = 'disposed' THEN 'disposed'
                WHEN LOWER(COALESCE(`{$t}`.`operational_status`, '')) = 'under_maintenance'
                    OR LOWER(COALESCE(`{$t}`.`status`, '')) = 'under_maintenance'
                    OR EXISTS (
                        SELECT 1 FROM asset_maintenance am
                        WHERE am.asset_id = `{$t}`.`id`
                          AND am.status IN ('pending', 'in_progress')
                    )
                THEN 'under_maintenance'
                WHEN LOWER(COALESCE(`{$t}`.`status`, '')) IN ('transferred', 'in_transit') THEN 'transferred'
                WHEN LOWER(COALESCE(`{$t}`.`status`, '')) = 'assigned' THEN 'assigned'
                WHEN LOWER(COALESCE(`{$t}`.`status`, '')) = 'available' THEN 'available'
                ELSE 'available'
            END
        )";
    }

    /**
     * MySQL ONLY_FULL_GROUP_BY-safe counts per chart bucket.
     *
     * Inner query: one derived `chart_status` per asset (same CASE + EXISTS as {@see sqlChartStatusBucketExpression}).
     * Outer query: `GROUP BY chart_status` only references the subquery alias — no raw `assets.status` in the grouped SELECT.
     *
     * @param  Builder<static>  $visibleAssetQuery
     * @return array<string, int>
     */
    public static function aggregateChartStatusBuckets(Builder $visibleAssetQuery): array
    {
        $table = (new static)->getTable();
        $expr = static::sqlChartStatusBucketExpression($table);

        $bucketedRows = DB::table($table)
            ->whereIn("{$table}.id", (clone $visibleAssetQuery)->select("{$table}.id"))
            ->selectRaw("{$expr} as chart_status");

        $rows = DB::query()
            ->fromSub($bucketedRows, 'bucketed')
            ->select('chart_status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('chart_status')
            ->pluck('aggregate', 'chart_status');

        $buckets = [];
        foreach (AssetStatus::cases() as $case) {
            $buckets[$case->value] = 0;
        }

        foreach ($rows as $chartStatusKey => $aggregate) {
            $key = strtolower(trim((string) $chartStatusKey));
            $matched = false;
            foreach (AssetStatus::cases() as $case) {
                if ($key === $case->value) {
                    $buckets[$case->value] += (int) $aggregate;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $buckets[AssetStatus::AVAILABLE->value] += (int) $aggregate;
            }
        }

        return $buckets;
    }

    protected $appends = [
        'status_label',
        'condition_label',
        'annual_depreciation',
        'total_depreciation',
        'current_value',
        'depreciation_summary',
        'doc_url',
         'icon',
         'operational_status_label'
    ];


    public function getDocUrlAttribute()
    {
        return !empty($this->filename) ? asset_url_local_s3(Asset::FILE_PATH . '/' . $this->filename) : '';
    }

    /**
     * Lifecycle status shown in headers and lists: reflects an open in-transit assignment
     * even when {@see $status} was not synced to {@see AssetStatus::TRANSFERRED}.
     */
    public function getEffectiveStatusAttribute(): ?AssetStatus
    {
        if (!$this->status instanceof AssetStatus) {
            return null;
        }

        if ($this->status === AssetStatus::DISPOSED) {
            return AssetStatus::DISPOSED;
        }

        $latest = $this->relationLoaded('latestAssignment')
            ? $this->latestAssignment
            : $this->latestAssignment()->first();

        if ($latest && $latest->transfer_status === AssetAssignment::IN_TRANSIT) {
            return AssetStatus::TRANSFERRED;
        }

        if ($this->status === AssetStatus::TRANSFERRED || $this->status === AssetStatus::IN_TRANSIT) {
            return AssetStatus::TRANSFERRED;
        }

        return $this->status;
    }

    public function getStatusLabelAttribute()
    {
        $status = $this->effective_status;

        if (!$status) {
            return '<span class="badge bg-label-secondary me-1">Unknown</span>';
        }

        return match ($status) {
            AssetStatus::AVAILABLE => '<span class="badge bg-label-success me-1">Available</span>',
            AssetStatus::ASSIGNED => '<span class="badge bg-label-info me-1">Assigned</span>',
            AssetStatus::TRANSFERRED => '<span class="badge bg-label-warning me-1">Transferred</span>',
            AssetStatus::UNDER_MAINTENANCE => '<span class="badge bg-label-warning me-1">Under Maintenance</span>',
            AssetStatus::DISPOSED => '<span class="badge bg-label-danger me-1">Disposed</span>',
        };
    }

    /**
     * Next asset code, for example MSULLP/BLR/0001.
     * A named lock serializes allocation. Row locks are not used, so an open
     * asset edit cannot block GRN conversion.
     */
    public static function generateAssetCode(?int $companyId = null, ?int $branchId = null): string
    {
        $fullPrefix = static::assetCodePrefix($companyId, $branchId);
        $prefixKey = rtrim($fullPrefix, '/');
        $lockName = 'asset_code_'.substr(md5($prefixKey), 0, 32);
        $result = DB::selectOne('SELECT GET_LOCK(?, 10) AS locked', [$lockName]);

        if ((int) ($result->locked ?? 0) !== 1) {
            throw new \RuntimeException('Could not acquire asset code lock. Please try again.');
        }

        try {
            $likePrefix = addcslashes($fullPrefix, '%_\\');
            $pattern = '^'.preg_quote($fullPrefix, '/').'[0-9]+$';

            $fromCodes = DB::table('assets')
                ->whereRaw("asset_code LIKE ? ESCAPE '\\\\'", [$likePrefix.'%'])
                ->where('asset_code', 'regexp', $pattern)
                ->selectRaw(
                    'MAX(CAST(SUBSTRING(asset_code, ?) AS UNSIGNED)) as max_seq',
                    [strlen($fullPrefix) + 1]
                )
                ->value('max_seq');

            $fromNumbers = DB::table('assets')
                ->where('asset_prefix', $prefixKey)
                ->selectRaw('MAX(CAST(asset_nos AS UNSIGNED)) as max_seq')
                ->value('max_seq');

            $next = max((int) $fromCodes, (int) $fromNumbers) + 1;

            for ($guard = 0; $guard < 100; $guard++) {
                $code = $fullPrefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);

                $taken = DB::table('assets')
                    ->whereRaw('UPPER(asset_code) = ?', [strtoupper($code)])
                    ->exists();

                if (! $taken) {
                    return $code;
                }

                $next++;
            }

            throw new \RuntimeException('Unable to allocate a unique asset code.');
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }

    /**
     * CompanyCode/BranchCode/ prefix used by new asset codes.
     */
    public static function assetCodePrefix(?int $companyId, ?int $branchId): string
    {
        $branch = $branchId ? Branch::query()->with('company')->find($branchId) : null;
        $company = $companyId ? Company::query()->find($companyId) : $branch?->company;

        if (! $company && $branch?->company) {
            $company = $branch->company;
        }

        $companyCode = static::normalizeCodeSegment($company?->code);
        $branchCode = static::normalizeCodeSegment($branch?->code);

        if ($companyCode === '' || $branchCode === '') {
            throw new \RuntimeException('Company code and branch code are required to generate an asset code.');
        }

        return $companyCode.'/'.$branchCode.'/';
    }

    private static function normalizeCodeSegment(?string $code): string
    {
        $code = strtoupper(trim((string) $code));
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return str_replace('/', '', $code);
    }

       
    public function getOperationalStatusLabelAttribute()
    {
        if (!$this->operational_status) {
            return '<span class="badge bg-label-secondary me-1">Unknown</span>';
        }

        $status = strtolower(trim($this->operational_status->label()));
        $status = str_replace(' ', '_', $status);

        switch ($status) {

            case 'working':
                return '<span class="badge bg-label-success me-1">Working</span>';

            case 'under_maintenance':
                return '<span class="badge bg-label-warning me-1">Under Maintenance</span>';

            case 'damaged':
                return '<span class="badge bg-label-danger me-1">Damaged</span>';

            case 'disposed':
                return '<span class="badge bg-label-secondary me-1">Disposed</span>';

            default:
                return '<span class="badge bg-label-secondary me-1">Unknown</span>';
        }
    }

    

    public function getConditionLabelAttribute()
    {
         if (!$this->condition) {
        return '<span class="badge bg-label-secondary me-1">Unknown</span>';
    }
    

       $conditions = strtolower(trim($this->condition->label()));

        switch ($conditions) {
            case 'new':
                return '<span class="badge bg-label-success me-1">New</span>';

            case 'good':
                return '<span class="badge bg-label-info me-1">Good</span>';

            case 'fair':
                return '<span class="badge bg-label-warning me-1">Fair</span>';

            case 'poor':
                return '<span class="badge bg-label-danger me-1">Poor</span>';

            case 'disposed':
                return '<span class="badge bg-label-secondary me-1">Disposed</span>';

            default:
                return '<span class="badge bg-label-secondary me-1">Unknown</span>';
        }

    }

    // Accessor methods for each depreciation field
    public function getAnnualDepreciationAttribute()
    {
        $depreciation = $this->calculateDepreciation();
        return $depreciation['annual_depreciation'];
    }

    public function getTotalDepreciationAttribute()
    {
        $depreciation = $this->calculateDepreciation();
        return $depreciation['total_depreciation'];
    }

    public function getCurrentValueAttribute()
    {
        $depreciation = $this->calculateDepreciation();
        return $depreciation['current_value'];
    }

    // Optional: Get all depreciation data as a single attribute
    public function getDepreciationSummaryAttribute()
    {
        return $this->calculateDepreciation();
    }


    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'supplier');
    }

    public function grnItem()
    {
        return $this->belongsTo(GrnItem::class, 'grn_item_id');
    }

    public function msuGrnItem()
    {
        return $this->belongsTo(MsuGrnItem::class, 'msu_grn_item_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function indent()
    {
        return $this->belongsTo(Indent::class, 'indent_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function custodian()
    {
        return $this->belongsTo(AssetCustodian::class, 'custodian_id');
    }

    public function purchaseTypeLabel(): string
    {
        return match ($this->purchase_type) {
            'purchased' => 'Purchased',
            'transferred' => 'Transferred',
            default => '--',
        };
    }

    public function ownershipTypeLabel(): string
    {
        return match ($this->ownership_type) {
            'owned' => 'Owned',
            'rent' => 'Rent',
            default => '--',
        };
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function company(){

        return $this->belongsTo(Company::class,'company_id');
    }

    public function documents()
    {
        return $this->hasMany(AssetDocument::class);
    }


    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

     public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(AssetCategory::class,'sub_category_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productSubCategory()
    {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
    }

    public function replacements()
    {
        return $this->hasMany(AssetReplacement::class, 'asset_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditedBy()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    /**
     * Stored result of a completed audit. Null means the asset has never been audited.
     */
    public static function normalizeAuditStatus(mixed $status): ?string
    {
        $value = strtolower(trim((string) $status));
        $value = str_replace(['-', ' '], '_', $value);

        return match ($value) {
            'audited', 'found' => 'audited',
            'not_found', 'notfound' => 'not_found',
            default => null,
        };
    }

    public function hasAuditRecord(): bool
    {
        return self::normalizeAuditStatus($this->audit_status) !== null;
    }

    public function auditByLabel(): string
    {
        if (! $this->hasAuditRecord()) {
            return '-';
        }

        $name = trim((string) ($this->auditedBy?->name ?? ''));

        return $name !== '' ? $name : '-';
    }

    public function auditOnLabel(): string
    {
        if (! $this->hasAuditRecord() || $this->audited_at === null) {
            return '-';
        }

        return $this->audited_at->format('d M Y H:i');
    }

    public function auditStatusLabel(): string
    {
        return match (self::normalizeAuditStatus($this->audit_status)) {
            'audited' => 'Audited',
            'not_found' => 'Not Found',
            default => 'Pending Audit',
        };
    }

    public function auditStatusBadge(): string
    {
        $label = $this->auditStatusLabel();
        $class = match ($label) {
            'Audited' => 'bg-label-success',
            'Not Found' => 'bg-label-danger',
            default => 'bg-label-warning',
        };

        return '<span class="badge '.$class.' me-1">'.e($label).'</span>';
    }

    /**
     * Audit history entries for this asset.
     */
    public function audits()
    {
        return $this->hasMany(AssetAudit::class, 'asset_id')->orderBy('audited_at', 'desc')->orderBy('id', 'desc');
    }

    public function latestAudit()
    {
        return $this->hasOne(AssetAudit::class, 'asset_id')->latestOfMany('audited_at');
    }

    /**
     * Store the latest successful audit without changing other asset columns,
     * and persist an entry in the audit history trail.
     */
    public function recordAudit(int $userId, string $status, array $attributes = []): bool
    {
        $normalized = self::normalizeAuditStatus($status);

        if ($normalized === null || ! $this->exists) {
            return false;
        }

        $auditDate = isset($attributes['audited_at']) ? \Carbon\Carbon::parse($attributes['audited_at']) : now();
        $previousStatus = $this->auditStatusLabel();

        return (bool) \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $normalized, $auditDate, $attributes, $previousStatus) {
            $updated = static::query()->whereKey($this->getKey())->update([
                'audited_by' => $userId,
                'audited_at' => $auditDate,
                'audit_status' => $normalized,
            ]);

            AssetAudit::create([
                'asset_id' => $this->id,
                'auditor_id' => $userId,
                'audit_status' => $normalized === 'audited' ? 'Audited' : 'Not Found',
                'previous_status' => $previousStatus,
                'location_status' => $attributes['location_status'] ?? 'matches',
                'remarks' => $attributes['remarks'] ?? $attributes['notes'] ?? null,
                'audited_at' => $auditDate,
                'source' => $attributes['source'] ?? (request()->is('api/*') ? 'mobile_app' : 'web'),
                'company_id' => $attributes['company_id'] ?? $this->company_id,
                'branch_id' => $attributes['branch_id'] ?? $this->branch_id,
                'location_id' => $attributes['location_id'] ?? $this->location_id,
                'latitude' => $attributes['latitude'] ?? null,
                'longitude' => $attributes['longitude'] ?? null,
            ]);

            $this->refresh();

            return (bool) $updated;
        });
    }


    public function latestAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->OrderBy('id', 'desc');
    }

    

    public function location()
    {
        return $this->belongsTo(BranchLocation::class, 'location_id');
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class)->OrderBy('id', 'desc');
    }

    public function warranties()
    {
        return $this->hasMany(AssetWarranty::class)->OrderBy('id', 'desc');
    }

    public function maintenance()
    {
        return $this->hasMany(AssetMaintenance::class)->OrderBy('id', 'desc');
    }

    public function calculateDepreciation(): array
    {
        $purchaseCost = (float) $this->purchase_cost;

        // NOW salvage_value IS depreciation rate (%)
        $rate = (float) $this->salvage_value;

        if ($purchaseCost <= 0) {
            return [
                'annual_depreciation' => 0,
                'total_depreciation'  => 0,
                'current_value'       => 0,
            ];
        }

        $yearsUsed = Carbon::parse($this->purchase_date)->diffInYears(now());

        /*
        |--------------------------------------------------------------------------
        | NO DEPRECIATION
        |--------------------------------------------------------------------------
        */
        if ($this->depreciation_method === 'no_depreciation') {
            return [
                'annual_depreciation' => 0,
                'total_depreciation'  => 0,
                'current_value'       => round($purchaseCost, 2),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | REDUCING BALANCE (RATE FROM SALVAGE_VALUE)
        |--------------------------------------------------------------------------
        */
        if ($this->depreciation_method === 'reducing_balance') {

            if ($rate <= 0 || $rate >= 100) {
                return [
                    'annual_depreciation' => 0,
                    'total_depreciation'  => 0,
                    'current_value'       => round($purchaseCost, 2),
                ];
            }

            $currentValue = $purchaseCost;
            $totalDepreciation = 0;
            $lastYearDepreciation = 0;

            for ($year = 1; $year <= $yearsUsed; $year++) {

                $depreciation = ($currentValue * $rate) / 100;

                $currentValue -= $depreciation;
                $totalDepreciation += $depreciation;
                $lastYearDepreciation = $depreciation;
            }

            return [
                // Reducing balance → last year depreciation is correct
                'annual_depreciation' => round($lastYearDepreciation, 2),
                'total_depreciation'  => round($totalDepreciation, 2),
                'current_value'       => round($currentValue, 2),
            ];
        }

        return [
            'annual_depreciation' => 0,
            'total_depreciation'  => 0,
            'current_value'       => round($purchaseCost, 2),
        ];
    }

    /**
     * Warehouse / store branches (e.g. HO-store, KTR-store): filter by asset physical location (branch_id).
     *
     * @return list<int>
     */
    public static function warehouseStoreBranchIds(): array
    {
        $ids = collect();

        $warehouseId = (int) setting('warehouse_id', 0);
        if ($warehouseId > 0) {
            $ids->push($warehouseId);
        }

        $namedStoreIds = Branch::query()
            ->whereRaw('LOWER(TRIM(name)) IN (?, ?)', ['ho-store', 'ktr-store'])
            ->pluck('id');

        return $ids->merge($namedStoreIds)->unique()->filter()->map(fn ($id) => (int) $id)->values()->all();
    }

    public static function isWarehouseStoreBranch(int $branchId): bool
    {
        return in_array($branchId, static::warehouseStoreBranchIds(), true);
    }

    /**
     * Branch filter (shared by asset index, dashboard, reports, depreciation, API):
     * - HO-store / KTR-store (and configured warehouse): physical location + status available only.
     * - Other branches: latest assignment to_branch (or available at branch, not in-transit).
     */
    public function scopeFilteredByBranch(Builder $query, int $branchId): Builder
    {
        $table = $query->getModel()->getTable();

        if (static::isWarehouseStoreBranch($branchId)) {
            return $query
                ->where("{$table}.branch_id", $branchId)
                ->where("{$table}.status", AssetStatus::AVAILABLE->value);
        }

        $assetsTable = $query->getModel()->getTable();
        $assignmentsTable = (new AssetAssignment)->getTable();

        return $query->where(function (Builder $w) use ($branchId, $assetsTable, $assignmentsTable): void {
            $w->whereExists(function ($sub) use ($branchId, $assetsTable, $assignmentsTable): void {
                $sub->selectRaw('1')
                    ->from($assignmentsTable)
                    ->whereColumn("{$assignmentsTable}.asset_id", "{$assetsTable}.id")
                    ->where("{$assignmentsTable}.to_branch", $branchId)
                    ->whereRaw("{$assignmentsTable}.id = (
                        SELECT MAX(aa_inner.id)
                        FROM {$assignmentsTable} AS aa_inner
                        WHERE aa_inner.asset_id = {$assetsTable}.id
                    )");
            })
                ->orWhere(function (Builder $atBranch) use ($branchId, $assetsTable): void {
                    $atBranch->where("{$assetsTable}.branch_id", $branchId)
                        ->where("{$assetsTable}.status", AssetStatus::AVAILABLE->value)
                        ->whereDoesntHave('latestAssignment', function (Builder $aq): void {
                            $aq->where('transfer_status', AssetAssignment::IN_TRANSIT);
                        });
                });
        });
    }

    public function scopeNotDisposed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhere('status', '!=', AssetStatus::DISPOSED->value);
        });
    }

    public function scopeVisibleToUser($query, $user)
    {
        // Super Admin / Admin → see all assets
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin'])) {
            return $query;
        }

        $branchIds = $user->branches()->pluck('branches.id');

        if ($branchIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($branchIds) {
            // Asset belongs to user's branch
            $q->whereIn('branch_id', $branchIds)
                // OR assigned FROM or TO user's branch (transfers / in-transit)
                ->orWhereHas('assignments', function ($assignmentQuery) use ($branchIds) {
                    $assignmentQuery->where(function ($sub) use ($branchIds) {
                        $sub->whereIn('from_branch', $branchIds)
                            ->orWhereIn('to_branch', $branchIds);
                    });
                });
        });
    }


     public function hasDependencies()
     {
            return $this->assignments()->exists()
                || $this->warranties()->exists()
                || $this->maintenance()->exists()
                || $this->documents()->exists()
                || $this->replacements()->exists();
     }


     public function isEditable(): bool
     {
         $status = $this->status instanceof AssetStatus
             ? $this->status
             : AssetStatus::tryFrom((string) $this->getRawOriginal('status'));

         return $status === AssetStatus::AVAILABLE;
     }

     public function canModify(User $user = null): bool
    {
        if (! $this->isEditable()) {
            return false;
        }

        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // 1️⃣ Super Admin / Admin
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin', 'admin'])) {
            return true;
        }

        // 2️⃣ Branch user
        if ($user->hasBranch($this->branch_id)) {
            return true;
        }

        $wareHouseCompanyId=setting('warehouse_company_id',1);

        $wareHouseId=setting('warehouse_id',1);

        $hasCompany = $user->companies()
        ->where('companies.id', $wareHouseCompanyId)
        ->exists();

        $hasBranch = $user->branches()
            ->where('branches.id', $wareHouseId)
            ->exists();

        if ($hasCompany && $hasBranch) {
            return true;
        }

        return false;

    }


}

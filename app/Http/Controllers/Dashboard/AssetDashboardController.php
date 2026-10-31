<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetMaintenance;
use App\Models\AssetWarranty;
use App\Models\Company;
use App\Models\Branch;
use App\Models\AssetCategory;

class AssetDashboardController extends Controller
{
    

    public function index(Request $request){

        if (!auth()->user()->can('dashboard.asset:view')) {
                abort(403, 'Unauthorized');
            }

        $user = auth()->user();

        $branches=collect();


        $companies=Company::visibleToUser($user)->active()->get();

        $subCategories = AssetCategory::query()
            ->whereNotNull('parent_id')
            ->orderBy('category_name')
            ->get(['id', 'category_name']);

         // ✅ Base Query (default visible to user)
        $assetQuery = Asset::VisibleToUser($user);

        // ✅ Apply Filters (ONLY when selected)
        if ($request->filled('company_id')) {
            $assetQuery->where('company_id', $request->company_id);

            $branches=Branch::visibleToUser(auth()->user())->where('company_id', $request->company_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        if ($request->filled('branch_id')) {
            $assetQuery->filteredByBranch((int) $request->branch_id);
        }

        if ($request->filled('sub_category_id')) {
            $assetQuery->where('sub_category_id', $request->sub_category_id);
        }

        $dashboard=array();

        // Single source: same CASE/buckets for every summary card (Total = sum of all buckets).
        $statusBuckets = Asset::aggregateChartStatusBuckets(clone $assetQuery);

        $dashboard['total_active_assets'] = (int) ($statusBuckets['available'] ?? 0);

        $dashboard['total_transferred_assets'] = (int) ($statusBuckets['transferred'] ?? 0);

        $dashboard['total_assigned_assets'] = (int) ($statusBuckets['assigned'] ?? 0);

        $dashboard['total_under_maintenance_assets'] = (int) ($statusBuckets['under_maintenance'] ?? 0);

        $dashboard['total_assets'] = (int) array_sum($statusBuckets);

        $dashboard['total_purchase_value']=(clone $assetQuery)->sum('purchase_cost');

        $totalDepreciation=0;

        foreach((clone $assetQuery)->get() as $asset){
            $totalDepreciation+=$asset->total_depreciation;
        }

        $dashboard['total_depreciation']=$totalDepreciation;

        $dashboard['total_current_value']=$dashboard['total_purchase_value']-$totalDepreciation;

        $dashboard['depreciation_percentage'] = $dashboard['total_purchase_value'] > 0 ? round(($dashboard['total_depreciation'] / $dashboard['total_purchase_value']) * 100,2): 0;

        // Asset type on this dashboard is ownership: Owned and Rent.
        // Same filters as totals/cards ($assetQuery), so counts match company/branch/category.
        $ownershipCounts = (clone $assetQuery)
            ->selectRaw('ownership_type, COUNT(*) as cnt')
            ->whereIn('ownership_type', ['owned', 'rent'])
            ->groupBy('ownership_type')
            ->pluck('cnt', 'ownership_type');

        $dashboard['asset_types'] = collect([
            (object) [
                'asset_type_name' => 'Owned',
                'assets_count' => (int) ($ownershipCounts['owned'] ?? 0),
            ],
            (object) [
                'asset_type_name' => 'Rent',
                'assets_count' => (int) ($ownershipCounts['rent'] ?? 0),
            ],
        ]);

        // Audit History breakdown for chart
        $rawAuditCounts = (clone $assetQuery)
            ->selectRaw("
                CASE 
                    WHEN LOWER(REPLACE(REPLACE(COALESCE(audit_status, ''), '-', '_'), ' ', '_')) IN ('audited', 'found') THEN 'audited'
                    WHEN LOWER(REPLACE(REPLACE(COALESCE(audit_status, ''), '-', '_'), ' ', '_')) IN ('not_found', 'notfound') THEN 'not_found'
                    ELSE 'pending'
                END as normalized_status,
                COUNT(*) as cnt
            ")
            ->groupBy('normalized_status')
            ->pluck('cnt', 'normalized_status');

        $auditedCount = (int) ($rawAuditCounts['audited'] ?? 0);
        $notFoundCount = (int) ($rawAuditCounts['not_found'] ?? 0);
        $pendingAuditCount = (int) ($rawAuditCounts['pending'] ?? 0);

        $dashboard['total_audited_assets'] = $auditedCount;
        $dashboard['total_not_found_assets'] = $notFoundCount;
        $dashboard['total_pending_audit_assets'] = $pendingAuditCount;

        $assetAuditChartSeries = [
            [
                'label' => 'Audited',
                'value' => $auditedCount,
                'color' => '#10b981',
            ],
            [
                'label' => 'Not Found',
                'value' => $notFoundCount,
                'color' => '#f43f5e',
            ],
            [
                'label' => 'Pending Audit',
                'value' => $pendingAuditCount,
                'color' => '#f59e0b',
            ],
        ];

        $dashboard['recent_assignments'] = AssetAssignment::with(['asset', 'employee', 'toBranch'])
        ->whereHas('asset', function ($q) use ($assetQuery) {
            $q->whereIn('id', (clone $assetQuery)->pluck('id'));
        })
        ->latest()
        ->limit(10)
        ->get();

        $dashboard['recent_maintenances'] = AssetMaintenance::with('asset')
        ->whereHas('asset', function ($q) use ($assetQuery) {
            $q->whereIn('id', (clone $assetQuery)->pluck('id'));
        })
        ->latest()
        ->limit(10)
        ->get();

        $dashboard['recent_warranties'] = AssetWarranty::with('asset')
        ->whereHas('asset', function ($q) use ($assetQuery) {
            $q->whereIn('id', (clone $assetQuery)->pluck('id'));
        })
        ->whereDate('warranty_expire_date', '>=', now())
        ->whereDate('warranty_expire_date', '<=', now()->addDays(30))
        ->orderBy('warranty_expire_date')
        ->limit(10)
        ->get();

        $colors = [
    'bg-label-primary',
    'bg-label-success',
    'bg-label-info',
    'bg-label-warning',
    'bg-label-danger',
    'bg-label-secondary',
    'bg-label-dark'
];


        // Pie chart: partition of all assets (no "Total" slice — that dominated the chart and hid small counts).
        $assetOverviewChartSeries = [
            [
                'label' => 'Available Assets',
                'value' => (int) $dashboard['total_active_assets'],
                'color' => '#71dd37',
            ],
            [
                'label' => 'Transferred',
                'value' => (int) ($dashboard['total_transferred_assets'] ?? 0),
                'color' => '#03c3ec',
            ],
            [
                'label' => 'Assigned Assets',
                'value' => (int) $dashboard['total_assigned_assets'],
                'color' => '#ffab00',
            ],
            [
                'label' => 'Under Maintenance',
                'value' => (int) $dashboard['total_under_maintenance_assets'],
                'color' => '#ff3e1d',
            ],
            [
                'label' => 'Disposed',
                'value' => (int) ($statusBuckets['disposed'] ?? 0),
                'color' => '#8592a3',
            ],
        ];

        return view('dashboard.assets', compact(
            'companies',
            'subCategories',
            'dashboard',
            'colors',
            'assetOverviewChartSeries',
            'assetAuditChartSeries',
            'branches'
        ));
    }
}

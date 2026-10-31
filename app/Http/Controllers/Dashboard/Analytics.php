<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Procurement\MsuGrnStatus;
use App\Enums\Procurement\MsuPurchaseOrderStatus;
use App\Enums\Procurement\MsuWorkOrderStatus;
use App\Exports\DashboardExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\MsuGrn;
use App\Models\MsuItemMaster;
use App\Models\MsuPurchaseOrder;
use App\Models\MsuStockMovement;
use App\Models\MsuWorkOrder;
use App\Services\ReportPdfExportService;
use App\Support\DashboardLanding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class Analytics extends Controller
{
    public function index(Request $request)
    {
        if ($redirect = DashboardLanding::redirectIfNotHome($request->user())) {
            return $redirect;
        }

        $user = $request->user();
        $allowed = false;

        try {
            $allowed = (bool) ($user && ($user->hasAnyRole(['superadmin', 'Super Admin']) || $user->can('dashboard:view')));
        } catch (\Throwable) {
            $allowed = (bool) ($user && $user->can('dashboard:view'));
        }

        abort_unless($allowed, 403, 'Unauthorized action.');

        return view('content.dashboard.dashboards-analytics', $this->dashboardData($request));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new DashboardExport($this->dashboardData($request)),
            'dashboard-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function exportPdf(Request $request, ReportPdfExportService $pdfExport)
    {
        return $pdfExport->download(
            'dashboard.exports.dashboard-pdf',
            $this->dashboardData($request),
            'dashboard-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    private function dashboardData(Request $request): array
    {
        $user = $request->user();

        $companies = Company::visibleToUser($user)->active()->orderBy('name')->get();

        // Page-local filters only. An empty company is All Companies.
        // Do not read session('global_company_id') — that value belongs to PO, GRN, and other modules.
        $companyId = $request->filled('company_id') ? (int) $request->query('company_id') : null;
        if ($companyId && ! $companies->contains('id', $companyId)) {
            $companyId = null;
        }

        $branches = collect();
        if ($companyId) {
            $branches = Branch::visibleToUser($user)
                ->active()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        }

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        if ($branchId && ! $branches->contains('id', $branchId)) {
            $branchId = null;
        }

        $filterQuery = array_filter([
            'company_id' => $companyId,
            'branch_id' => $branchId,
        ]);

        $poQuery = $this->applyLocationFilters(MsuPurchaseOrder::query(), $companyId, $branchId);
        $grnQuery = $this->applyLocationFilters(MsuGrn::query(), $companyId, $branchId);
        $woQuery = $this->applyLocationFilters(MsuWorkOrder::query(), $companyId, $branchId);

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $poData = (clone $poQuery)
            ->selectRaw("DATE_FORMAT(po_date, '%Y-%m') as period, SUM(grand_total) as total")
            ->where('po_date', '>=', now()->subMonths(5)->startOfMonth())
            ->where('status', '!=', MsuPurchaseOrderStatus::Cancelled->value)
            ->groupBy('period')
            ->pluck('total', 'period');

        $woData = (clone $woQuery)
            ->selectRaw("DATE_FORMAT(wo_date, '%Y-%m') as period, SUM(grand_total) as total")
            ->where('wo_date', '>=', now()->subMonths(5)->startOfMonth())
            ->where('status', '!=', MsuWorkOrderStatus::Cancelled->value)
            ->groupBy('period')
            ->pluck('total', 'period');

        $spendTrend = $months->map(function ($m) use ($poData, $woData) {
            $period = $m->format('Y-m');

            return [
                'period' => $period,
                'label' => $m->format('M Y'),
                'po_total' => (float) ($poData[$period] ?? 0),
                'wo_total' => (float) ($woData[$period] ?? 0),
            ];
        });

        $totalPoSpend = (float) (clone $poQuery)
            ->where('status', '!=', MsuPurchaseOrderStatus::Cancelled->value)
            ->sum('grand_total');

        $totalWoSpend = (float) (clone $woQuery)
            ->where('status', '!=', MsuWorkOrderStatus::Cancelled->value)
            ->sum('grand_total');

        $pendingPoApprovals = (clone $poQuery)
            ->where('status', MsuPurchaseOrderStatus::PoCreated->value)
            ->count();

        $pendingWoApprovals = (clone $woQuery)
            ->where('status', MsuWorkOrderStatus::WoCreated->value)
            ->count();

        $pendingApprovals = $pendingPoApprovals + $pendingWoApprovals;

        $pendingStockGrns = (clone $grnQuery)
            ->where('is_added_to_stock', false)
            ->where('status', '!=', MsuGrnStatus::Cancelled->value)
            ->count();

        $poCount = (clone $poQuery)->count();
        $grnCount = (clone $grnQuery)->count();
        $woCount = (clone $woQuery)->count();
        $itemCount = MsuItemMaster::query()->where('status', 'active')->count();

        $openingStock = 0;
        if (! $companyId && ! $branchId && Schema::hasColumn('msu_item_masters', 'opening_stock')) {
            $openingStock = (float) MsuItemMaster::query()->sum('opening_stock');
        }

        $stockMovementQuery = MsuStockMovement::query();
        if ($companyId || $branchId) {
            $filteredGrnIds = (clone $grnQuery)->select('id');
            $stockMovementQuery->where('reference_type', MsuGrn::class)
                ->whereIn('reference_id', $filteredGrnIds);
        }

        $stockIn = (float) (clone $stockMovementQuery)->where('transaction_type', 'in')->sum('quantity');
        $stockOut = ($companyId || $branchId)
            ? 0
            : (float) MsuStockMovement::query()->where('transaction_type', 'out')->sum('quantity');
        $currentStockQty = $openingStock + $stockIn - $stockOut;

        $latestActivePo = (clone $poQuery)
            ->with('vendor')
            ->where('status', '!=', MsuPurchaseOrderStatus::Cancelled->value)
            ->latest('id')
            ->first();

        $poApprovalProgress = $this->poProgress($latestActivePo);

        $recentPurchaseOrders = (clone $poQuery)
            ->with('vendor')
            ->latest('id')
            ->take(6)
            ->get();

        $recentGrns = (clone $grnQuery)
            ->with(['vendor', 'purchaseOrder'])
            ->latest('id')
            ->take(6)
            ->get();

        $recentWorkOrders = (clone $woQuery)
            ->with('vendor')
            ->latest('id')
            ->take(6)
            ->get();

        $recentStockMovements = MsuStockMovement::query()
            ->with('itemMaster')
            ->when($companyId || $branchId, function ($q) use ($grnQuery) {
                $q->where('reference_type', MsuGrn::class)
                    ->whereIn('reference_id', (clone $grnQuery)->select('id'));
            })
            ->latest('id')
            ->take(6)
            ->get();

        $companyName = $companyId
            ? ($companies->firstWhere('id', $companyId)?->name ?? 'Selected company')
            : 'All Companies';
        $branchName = $branchId
            ? ($branches->firstWhere('id', $branchId)?->name ?? 'Selected branch')
            : 'All Branches';

        return compact(
            'spendTrend',
            'totalPoSpend',
            'totalWoSpend',
            'pendingApprovals',
            'pendingPoApprovals',
            'pendingWoApprovals',
            'pendingStockGrns',
            'poCount',
            'grnCount',
            'woCount',
            'itemCount',
            'currentStockQty',
            'poApprovalProgress',
            'recentPurchaseOrders',
            'recentGrns',
            'recentWorkOrders',
            'recentStockMovements',
            'companies',
            'branches',
            'companyId',
            'branchId',
            'filterQuery',
            'companyName',
            'branchName'
        ) + [
            'company' => $companyId ? $companies->firstWhere('id', $companyId) : null,
        ];
    }

    private function applyLocationFilters(Builder $query, ?int $companyId, ?int $branchId): Builder
    {
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($companyId) {
            $query->whereHas('branch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return $query;
    }

    private function poProgress(?MsuPurchaseOrder $po): array
    {
        if (! $po) {
            return [
                'po_no' => 'No active purchase order',
                'vendor_name' => 'Create a PO to track progress',
                'amount' => 0,
                'status' => 'N/A',
                'status_class' => 'secondary',
                'progress' => 0,
                'url' => route('msu-purchase-orders.index'),
            ];
        }

        $status = $po->statusEnum();
        $progress = match ($status) {
            MsuPurchaseOrderStatus::PoCreated => 25,
            MsuPurchaseOrderStatus::Approved => 50,
            MsuPurchaseOrderStatus::GrnPartiallyReceived => 75,
            MsuPurchaseOrderStatus::GrnReceived => 100,
            default => 0,
        };

        return [
            'po_no' => $po->po_number,
            'vendor_name' => $po->vendor?->name ?? 'Unknown Vendor',
            'amount' => (float) $po->grand_total,
            'status' => $status->label(),
            'status_class' => $status->badgeClass(),
            'progress' => $progress,
            'url' => route('msu-purchase-orders.show', $po),
        ];
    }
}

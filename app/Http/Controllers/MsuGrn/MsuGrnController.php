<?php

namespace App\Http\Controllers\MsuGrn;

use App\Enums\Procurement\MsuGrnAssetStatus;
use App\Enums\Procurement\MsuGrnStatus;
use App\Enums\Procurement\MsuPurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MsuGrn\MsuGrnRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeMaster;
use App\Models\MsuGrn;
use App\Models\MsuGrnItem;
use App\Models\MsuItemMaster;
use App\Models\MsuPurchaseOrder;
use App\Models\MsuPurchaseOrderItem;
use App\Models\Vendor;
use App\Services\Procurement\DocumentNumberGenerator;
use App\Services\Procurement\MsuPurchaseOrderReceiptService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Facades\Excel;

class MsuGrnController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:msu-grn:view', only: ['index', 'show', 'poItems', 'previewGrnNumber']),
            new Middleware('permission:msu-grn:create', only: ['create', 'store']),
            new Middleware('permission:msu-grn:edit|msu-grn:create', only: ['edit', 'update']),
            new Middleware('permission:msu-grn:approve|msu-grn:cancel', only: ['addToStock']),
            new Middleware('permission:msu-grn:approve', only: ['approveAsset']),
            new Middleware('permission:msu-grn:cancel', only: ['cancelAsset']),
            new Middleware('permission:msu-grn:delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $companyId = session('global_company_id');
        $branches = \App\Models\Branch::visibleToUser(auth()->user())
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        $perPage = $request->input('per_page', 10);
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'company_code', 'name']);
        $filterItems = MsuItemMaster::withTrashed()
            ->whereIn('id', MsuGrnItem::query()->select('msu_item_master_id')->whereNotNull('msu_item_master_id'))
            ->orderBy('item_name')
            ->get(['id', 'item_code', 'item_name']);
        $statuses = MsuGrnStatus::options();

        if ($viewMode === 'item') {
            $query = MsuGrnItem::query()
                ->with(['grn.vendor', 'grn.purchaseOrder', 'itemMaster'])
                ->whereHas('grn', fn ($q) => $this->applyFilters($q, $request));
            $this->applyItemLineFilter($query, $request);

            $items = $query
                ->orderByDesc('msu_grn_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString();

            return view('msu-grns.index', compact('items', 'branches', 'vendors', 'filterItems', 'statuses', 'viewMode', 'perPage'));
        }

        $query = MsuGrn::with(['vendor', 'purchaseOrder', 'department', 'employeeMaster', 'items']);
        $this->applyFilters($query, $request);
        $grns = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('msu-grns.index', compact('grns', 'branches', 'vendors', 'filterItems', 'statuses', 'viewMode', 'perPage'));
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('item_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('msu_item_master_id', $request->item_id);
            });
        }

        $search = trim((string) $request->input('search', ''));
        if ($search === '' && $request->filled('grn_number')) {
            $search = trim((string) $request->grn_number);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('grn_number', 'like', $like)
                    ->orWhere('vendor_invoice_number', 'like', $like)
                    ->orWhereHas('purchaseOrder', function ($po) use ($like) {
                        $po->where('po_number', 'like', $like);
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('grn_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('grn_date', '<=', $request->to_date);
        }

        if (session()->has('global_company_id')) {
            $query->whereHas('purchaseOrder.branch', function ($q) {
                $q->where('company_id', session('global_company_id'));
            });
        }
    }

    private function applyItemLineFilter($query, Request $request): void
    {
        if ($request->filled('item_id')) {
            $query->where('msu_item_master_id', $request->item_id);
        }
    }

    private function filteredItems(Request $request): Collection
    {
        $query = MsuGrnItem::query()
            ->with([
                'grn.vendor',
                'grn.purchaseOrder.branch.company',
                'grn.branch.company',
                'purchaseOrderItem',
                'itemMaster.productCategory',
                'itemMaster.productSubCategory',
                'itemMaster.unit',
            ])
            ->whereHas('grn', fn ($q) => $this->applyFilters($q, $request));
        $this->applyItemLineFilter($query, $request);

        return $query->orderByDesc('msu_grn_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function filteredGrns(Request $request): Collection
    {
        $query = MsuGrn::with([
            'vendor',
            'branch.company',
            'purchaseOrder.branch.company',
            'items.purchaseOrderItem',
        ]);
        $this->applyFilters($query, $request);

        return $query->orderByDesc('id')->get();
    }

    public function export(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';

        if ($viewMode === 'item') {
            $rows = $this->filteredItems($request);
            $export = new class($rows) implements FromCollection, WithHeadings, WithStrictNullComparison
            {
                public function __construct(private readonly Collection $rows) {}

                public function collection()
                {
                    return $this->rows->map(function (MsuGrnItem $item) {
                        $grn = $item->grn;
                        $poItem = $item->purchaseOrderItem;
                        $rate = $poItem ? (float) $poItem->price : 0;
                        $taxPct = $poItem ? (float) $poItem->tax : 0;
                        $receivedQty = (float) $item->received_quantity;
                        $total = round($receivedQty * $rate, 2);
                        $taxAmount = round($total * $taxPct / 100, 2);
                        $grandTotal = round($total + $taxAmount, 2);

                        return [
                            $grn?->branch?->company?->name ?? $grn?->purchaseOrder?->branch?->company?->name,
                            $grn?->branch?->name ?? $grn?->purchaseOrder?->branch?->name,
                            $grn?->grn_number,
                            optional($grn?->grn_date)->format('d-m-Y'),
                            $grn?->purchaseOrder?->po_number,
                            optional($grn?->purchaseOrder?->po_date)->format('d-m-Y'),
                            $grn?->vendor?->name,
                            $grn?->vendor?->gst_number,
                            $item->item_code,
                            $item->item_name,
                            $item->itemMaster?->productCategory?->category_name,
                            $item->itemMaster?->productSubCategory?->sub_category_name,
                            $item->hsn,
                            $item->itemMaster?->unit?->unit_name ?? ($item->itemMaster?->uom ?: null),
                            (float) $item->ordered_quantity,
                            (float) $item->previously_received_quantity,
                            $receivedQty,
                            $rate,
                            $total,
                            $taxPct,
                            $taxAmount,
                            $grandTotal,
                            $item->expenseTypeLabel(),
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Company',
                        'Branch',
                        'GRN Number',
                        'GRN Date',
                        'PO Number',
                        'PO Date',
                        'Vendor Name',
                        'Vendor GST',
                        'Item Code',
                        'Item Name',
                        'Product Category',
                        'Sub Category',
                        'HSN',
                        'Unit',
                        'Ordered Qty',
                        'Prev. Received Qty',
                        'Received Qty',
                        'Price / Unit',
                        'Total',
                        'Tax %',
                        'Tax Amount',
                        'Grand Total',
                        'Capex / Opex',
                    ];
                }
            };

            return Excel::download($export, 'msu-grn-items-'.now()->format('Ymd_His').'.xlsx');
        }

        $rows = $this->filteredGrns($request);
        $export = new class($rows) implements FromCollection, WithHeadings
        {
            public function __construct(private readonly Collection $rows) {}

            public function collection()
            {
                return $this->rows->map(function (MsuGrn $grn) {
                    $total = 0.0;
                    $tax = 0.0;

                    foreach ($grn->items as $item) {
                        $poItem = $item->purchaseOrderItem;
                        $rate = $poItem ? (float) $poItem->price : 0;
                        $taxPct = $poItem ? (float) $poItem->tax : 0;
                        $lineTotal = round((float) $item->received_quantity * $rate, 2);
                        $total += $lineTotal;
                        $tax += round($lineTotal * $taxPct / 100, 2);
                    }

                    $miscExp = (float) ($grn->miscellaneous_charges ?? 0);
                    $tax += (float) ($grn->miscellaneous_tax ?? 0);
                    $total = round($total, 2);
                    $tax = round($tax, 2);
                    $grandTotal = round($total + $miscExp + $tax, 2);

                    return [
                        $grn->branch?->company?->name ?? $grn->purchaseOrder?->branch?->company?->name,
                        $grn->branch?->name ?? $grn->purchaseOrder?->branch?->name,
                        $grn->grn_number,
                        $grn->receipt_number,
                        optional($grn->grn_date)->format('d-m-Y'),
                        $grn->purchaseOrder?->po_number,
                        $grn->vendor?->name,
                        $grn->vendor_invoice_number,
                        $grn->items->count(),
                        $grn->statusEnum()->label(),
                        $total,
                        $miscExp,
                        $tax,
                        $grandTotal,
                    ];
                });
            }

            public function headings(): array
            {
                return [
                    'Company',
                    'Branch',
                    'GRN Number',
                    'Receipt Number',
                    'GRN Date',
                    'PO Number',
                    'Vendor Name',
                    'Invoice No',
                    'Items',
                    'Status',
                    'Total',
                    'Misc Exp',
                    'Tax',
                    'Grand Total',
                ];
            }
        };

        return Excel::download($export, 'msu-grns-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request, \App\Services\ReportPdfExportService $pdfExport)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $filters = $request->only(['from_date', 'to_date', 'search', 'grn_number', 'vendor_id', 'item_id', 'status', 'branch_id', 'view']);

        if ($viewMode === 'item') {
            $items = $this->filteredItems($request);

            return $pdfExport->download(
                'msu-grns.export-items',
                compact('items', 'filters'),
                'msu-grn-items-'.now()->format('Ymd_His').'.pdf',
                'landscape'
            );
        }

        $grns = $this->filteredGrns($request);

        return $pdfExport->download(
            'msu-grns.export-list',
            compact('grns', 'filters'),
            'msu-grns-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    public function create(DocumentNumberGenerator $numberGenerator)
    {
        return view('msu-grns.create', $this->formData($numberGenerator));
    }

    public function store(
        MsuGrnRequest $request,
        DocumentNumberGenerator $numberGenerator,
        MsuPurchaseOrderReceiptService $receiptService
    ) {
        $grn = DB::transaction(function () use ($request, $numberGenerator, $receiptService) {
            $validated = $request->validated();

            $po = MsuPurchaseOrder::with(['branch.company', 'items'])->findOrFail($validated['msu_purchase_order_id']);

            if (! $po->statusEnum()->allowsGrn()) {
                abort(422, 'GRN cannot be created for this PO status.');
            }

            if ((int) $po->branch_id !== (int) $validated['branch_id']) {
                abort(422, 'Selected PO does not belong to the selected branch.');
            }

            $branch = Branch::with('company')->findOrFail($validated['branch_id']);
            $branchCode = $branch->code ?: ($branch->name ?: 'BRN');
            $companyCode = $branch->company?->code ?: 'MSULLP';
            $grnNumber = $numberGenerator->nextMsuGrnNumber($companyCode, $branchCode, $validated['grn_date'] ?? now());

            $hasPreviousMiscCharges = MsuGrn::where('msu_purchase_order_id', $po->id)
                ->where('miscellaneous_charges', '>', 0)
                ->exists();

            $grn = MsuGrn::create([
                'grn_number' => $grnNumber,
                'receipt_number' => $validated['receipt_number'] ?? $grnNumber,
                'msu_purchase_order_id' => $po->id,
                'branch_id' => $branch->id,
                'vendor_id' => $po->vendor_id,
                'department_id' => $validated['department_id'] ?? $po->department_id,
                'employee_master_id' => $validated['employee_master_id'] ?? $po->employee_master_id,
                'vendor_invoice_number' => $validated['vendor_invoice_number'] ?? null,
                'vendor_invoice_date' => $validated['vendor_invoice_date'] ?? null,
                'grn_date' => $validated['grn_date'],
                'status' => MsuGrnStatus::PartiallyReceived,
                'remarks' => $validated['remarks'] ?? null,
                'miscellaneous_charges' => $hasPreviousMiscCharges ? 0 : ($po->miscellaneous_charges ?? 0),
                'miscellaneous_tax' => $hasPreviousMiscCharges ? 0 : ($po->miscellaneous_tax ?? 0),
                'is_added_to_asset' => false,
                'asset_status' => MsuGrnAssetStatus::Pending,
                'created_by' => auth()->id(),
            ]);

            $receiptMap = [];
            $newlyReceivedItems = [];
            $hasCapexItem = false;

            foreach ($validated['items'] as $row) {
                $receivedQty = (float) ($row['received_quantity'] ?? 0);
                
                $poItem = MsuPurchaseOrderItem::query()
                    ->where('msu_purchase_order_id', $po->id)
                    ->where('id', $row['msu_purchase_order_item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $previously = (float) $poItem->received_quantity;
                $balance = max(0, (float) $poItem->quantity - $previously);
                if ($receivedQty > $balance) {
                    abort(422, "Received quantity for {$poItem->item_code} exceeds balance ({$balance}).");
                }

                if ($balance > 0 && $receivedQty < $balance) {
                    // Item partially received
                }

                if ($receivedQty <= 0) {
                    continue;
                }

                $isCapex = filter_var($row['is_capex'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $isGroupAsset = $isCapex
                    && $receivedQty > 1
                    && filter_var($row['is_group_asset'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isCapex) {
                    $hasCapexItem = true;
                }

                $grnItem = $grn->items()->where('msu_purchase_order_item_id', $poItem->id)->first();
                if ($grnItem) {
                    $grnItem->received_quantity += $receivedQty;
                    $grnItem->is_capex = $isCapex;
                    $grnItem->is_group_asset = $isGroupAsset;
                    $grnItem->save();
                } else {
                    $sort = $grn->items()->max('sort_order') + 1;
                    $grnItem = $grn->items()->create([
                        'msu_purchase_order_item_id' => $poItem->id,
                        'msu_item_master_id' => $poItem->msu_item_master_id,
                        'item_code' => $poItem->item_code,
                        'item_name' => $poItem->item_name,
                        'hsn' => $row['hsn'] ?? $poItem->hsn,
                        'ordered_quantity' => $poItem->quantity,
                        'previously_received_quantity' => $previously,
                        'received_quantity' => $receivedQty,
                        'is_capex' => $isCapex,
                        'is_group_asset' => $isGroupAsset,
                        'sort_order' => $sort,
                    ]);
                }

                $receiptMap[$poItem->id] = $receivedQty;
                
                $newlyReceivedItems[] = [
                    'grnItem' => $grnItem,
                    'receivedQty' => $receivedQty
                ];
            }

            if ($receiptMap === []) {
                abort(422, 'Enter received quantity for at least one item.');
            }

            $receiptService->applyReceipts($po, $receiptMap, false);
            
            $po->refresh();
            $grn->status = ($po->status === \App\Enums\Procurement\MsuPurchaseOrderStatus::GrnReceived) ? MsuGrnStatus::Received : MsuGrnStatus::PartiallyReceived;

            $expenseType = $hasCapexItem ? 'capex' : 'opex';
            if (\Illuminate\Support\Facades\Schema::hasColumn('msu_grns', 'expense_type')) {
                $grn->expense_type = $expenseType;
                DB::table('msu_grns')->where('id', $grn->id)->update(['expense_type' => $expenseType]);
            }

            $grn->save();

            // Auto-transfer received items into stock on GRN create
            $this->transferGrnItemsToStock($grn);

            return $grn;
        });

        return redirect()
            ->route('msu-grns.show', $grn)
            ->with('success', 'GRN created successfully. Received items added to stock.');
    }

    public function show(MsuGrn $msuGrn)
    {
        $msuGrn->load([
            'vendor',
            'branch.company',
            'purchaseOrder.charges',
            'purchaseOrder.branch.company',
            'department',
            'employeeMaster',
            'items.itemMaster.productCategory',
            'items.itemMaster.productSubCategory',
            'items.itemMaster.unit',
            'items.purchaseOrderItem',
        ]);

        return view('msu-grns.show', ['grn' => $msuGrn]);
    }

    public function edit(MsuGrn $msuGrn)
    {
        if (! $msuGrn->canEditClassification() || $this->grnItemsAlreadyConverted($msuGrn)) {
            return redirect()
                ->route('msu-grns.show', $msuGrn)
                ->with('error', 'This GRN can no longer be edited. CAPEX and Group Asset are locked after it is added to an asset.');
        }

        $msuGrn->load([
            'vendor',
            'branch',
            'purchaseOrder',
            'department',
            'items',
        ]);

        return view('msu-grns.edit', ['grn' => $msuGrn]);
    }

    public function update(Request $request, MsuGrn $msuGrn)
    {
        if (! $msuGrn->canEditClassification() || $this->grnItemsAlreadyConverted($msuGrn)) {
            return redirect()
                ->route('msu-grns.show', $msuGrn)
                ->with('error', 'This GRN can no longer be edited. CAPEX and Group Asset are locked after it is added to an asset.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.is_capex' => ['nullable', 'boolean'],
            'items.*.is_group_asset' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($msuGrn, $validated) {
            $locked = MsuGrn::query()->whereKey($msuGrn->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canEditClassification() || $this->grnItemsAlreadyConverted($locked)) {
                abort(403, 'This GRN can no longer be edited.');
            }

            $items = $locked->items()->lockForUpdate()->get()->keyBy(fn (MsuGrnItem $item) => (int) $item->id);
            $submittedIds = collect($validated['items'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $existingIds = $items->keys()->map(fn ($id) => (int) $id)->sort()->values();

            if ($submittedIds->all() !== $existingIds->all()) {
                abort(422, 'GRN items do not match. Refresh the page and try again.');
            }

            $hasCapexItem = false;

            foreach ($validated['items'] as $row) {
                $item = $items->get((int) $row['id']);
                $isCapex = filter_var($row['is_capex'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $receivedQty = (float) $item->received_quantity;
                $isGroupAsset = $isCapex
                    && $receivedQty > 1
                    && filter_var($row['is_group_asset'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $item->is_capex = $isCapex;
                $item->is_group_asset = $isGroupAsset;
                $item->save();

                if ($isCapex) {
                    $hasCapexItem = true;
                }
            }

            $expenseType = $hasCapexItem ? 'capex' : 'opex';
            if (\Illuminate\Support\Facades\Schema::hasColumn('msu_grns', 'expense_type')) {
                $locked->expense_type = $expenseType;
                DB::table('msu_grns')->where('id', $locked->id)->update(['expense_type' => $expenseType]);
            }

            $locked->save();
        });

        return redirect()
            ->route('msu-grns.show', $msuGrn)
            ->with('success', 'CAPEX and Group Asset updated.');
    }

    private function grnItemsAlreadyConverted(MsuGrn $msuGrn): bool
    {
        $itemIds = $msuGrn->relationLoaded('items')
            ? $msuGrn->items->pluck('id')
            : $msuGrn->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return false;
        }

        return \App\Models\Asset::query()
            ->whereIn('msu_grn_item_id', $itemIds)
            ->exists();
    }

    public function addToStock(
        Request $request,
        MsuGrn $msuGrn,
        \App\Services\Procurement\MsuGrnAssetConversionService $assetConversion
    ) {
        if ($request->input('asset_action') === 'cancel') {
            if (! auth()->user()?->can('msu-grn:cancel')) {
                abort(403);
            }

            return $this->cancelAsset($request, $msuGrn);
        }

        if (! auth()->user()?->can('msu-grn:approve')) {
            abort(403);
        }

        return $this->approveAsset($msuGrn, $assetConversion);
    }

    public function approveAsset(MsuGrn $msuGrn, \App\Services\Procurement\MsuGrnAssetConversionService $assetConversion)
    {
        if ($msuGrn->is_added_to_asset || $msuGrn->assetStatusEnum() === MsuGrnAssetStatus::Approved) {
            return redirect()->back()->with('error', 'Assets have already been created for this GRN.');
        }

        if (! $msuGrn->canApproveAsset()) {
            return redirect()->back()->with('error', 'This GRN cannot be approved for asset conversion.');
        }

        try {
            $result = DB::transaction(function () use ($msuGrn, $assetConversion) {
                $this->transferGrnItemsToStock($msuGrn);

                return $assetConversion->generateFromGrn($msuGrn, auth()->user());
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', $e->getMessage());
        }

        $count = (int) ($result['count'] ?? 0);
        $message = $count > 0
            ? "{$count} asset(s) created successfully from this GRN."
            : 'GRN approved, but no received items were found to convert into assets.';

        return redirect()->back()->with('success', $message);
    }

    public function cancelAsset(Request $request, MsuGrn $msuGrn)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        if ($msuGrn->is_added_to_asset || $msuGrn->assetStatusEnum() === MsuGrnAssetStatus::Approved) {
            return redirect()->back()->with('error', 'Cannot cancel. Assets have already been created for this GRN.');
        }

        if (! $msuGrn->canCancelAsset()) {
            return redirect()->back()->with('error', 'Asset conversion cannot be cancelled for this GRN.');
        }

        $msuGrn->update([
            'asset_status' => MsuGrnAssetStatus::Cancelled,
            'asset_cancel_reason' => $request->cancel_reason,
            'is_added_to_asset' => false,
        ]);

        return redirect()->back()->with('success', 'Asset conversion cancelled. Items were not added to assets.');
    }

    private function transferGrnItemsToStock(MsuGrn $msuGrn): void
    {
        if ($msuGrn->is_added_to_stock) {
            return;
        }

        $msuGrn->loadMissing('items');

        foreach ($msuGrn->items as $item) {
            $qty = (float) $item->received_quantity;

            if ($qty <= 0) {
                continue;
            }

            $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($item->msu_item_master_id);
            if (! $itemMaster) {
                continue;
            }

            $stockBefore = (float) $itemMaster->current_stock;
            $stockAfter = $stockBefore + $qty;

            $itemMaster->current_stock = $stockAfter;
            $itemMaster->save();

            \App\Models\MsuStockMovement::create([
                'msu_item_master_id' => $itemMaster->id,
                'transaction_type' => 'in',
                'quantity' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => get_class($msuGrn),
                'reference_id' => $msuGrn->id,
                'remarks' => 'Stock in via GRN ' . $msuGrn->grn_number,
                'created_by' => auth()->id(),
            ]);
        }

        $msuGrn->update(['is_added_to_stock' => true]);
    }

    public function destroy(MsuGrn $msuGrn)
    {
        if (! $msuGrn->statusEnum()->canDelete()) {
            return redirect()
                ->route('msu-grns.index')
                ->with('error', 'Fully received GRNs cannot be deleted.');
        }

        DB::transaction(function () use ($msuGrn) {
            $msuGrn->load('items');
            $po = MsuPurchaseOrder::query()->lockForUpdate()->find($msuGrn->msu_purchase_order_id);

            foreach ($msuGrn->items as $item) {
                $poItem = MsuPurchaseOrderItem::query()
                    ->where('id', $item->msu_purchase_order_item_id)
                    ->lockForUpdate()
                    ->first();

                if ($poItem) {
                    $poItem->update([
                        'received_quantity' => max(0, (float) $poItem->received_quantity - (float) $item->received_quantity),
                    ]);
                }

                if ($msuGrn->is_added_to_stock) {
                    $qty = (float) $item->received_quantity;
                    if ($qty > 0) {
                        $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($item->msu_item_master_id);
                        if ($itemMaster) {
                            $stockBefore = (float) $itemMaster->current_stock;
                            $stockAfter = max(0, $stockBefore - $qty);

                            $itemMaster->current_stock = $stockAfter;
                            $itemMaster->save();

                            \App\Models\MsuStockMovement::create([
                                'msu_item_master_id' => $itemMaster->id,
                                'transaction_type' => 'out',
                                'quantity' => $qty,
                                'stock_before' => $stockBefore,
                                'stock_after' => $stockAfter,
                                'reference_type' => get_class($msuGrn),
                                'reference_id' => $msuGrn->id,
                                'remarks' => 'Stock reversed for deleted GRN ' . $msuGrn->grn_number,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            $msuGrn->delete();

            if ($po) {
                $po->refresh();
                $po->syncStatusFromReceipts();
            }
        });

        return redirect()
            ->route('msu-grns.index')
            ->with('success', 'GRN deleted and PO quantities reverted.');
    }

    public function getPosByCompany(Request $request)
    {
        $companyId = $request->input('company_id');
        $branchId = $request->input('branch_id');
        $purchaseOrders = MsuPurchaseOrder::with(['vendor', 'branch'])
            ->whereIn('status', [
                MsuPurchaseOrderStatus::Approved->value,
                MsuPurchaseOrderStatus::GrnPartiallyReceived->value,
            ])
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('branch', function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId);
                });
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereHas('branch', function ($q) {
                $q->visibleToUser(auth()->user());
            })
            ->orderByDesc('id')
            ->get();

        return response()->json(['success' => true, 'purchaseOrders' => $purchaseOrders]);
    }

    public function poItems(Request $request, MsuPurchaseOrder $msuPurchaseOrder)
    {
        if (! $msuPurchaseOrder->statusEnum()->allowsGrn()) {
            return response()->json([
                'success' => false,
                'message' => 'This PO is not open for GRN receipt.',
            ], 422);
        }

        if ($request->filled('branch_id')
            && (int) $msuPurchaseOrder->branch_id !== (int) $request->input('branch_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected PO does not belong to the selected branch.',
            ], 422);
        }

        $msuPurchaseOrder->load(['vendor', 'department', 'employeeMaster', 'branch', 'items']);

        $items = $msuPurchaseOrder->items->map(function (MsuPurchaseOrderItem $item) {
            $ordered = (float) $item->quantity;
            $previously = (float) $item->received_quantity;
            $balance = max(0, $ordered - $previously);

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'hsn' => $item->hsn,
                'ordered_quantity' => $ordered,
                'previously_received_quantity' => $previously,
                'balance_quantity' => $balance,
                'received_quantity' => $balance > 0 ? $balance : 0,
                'price' => $item->price,
                'tax' => $item->tax,
            ];
        })->filter(function ($item) {
            return $item['balance_quantity'] > 0;
        })->values();

        $hasPreviousMiscCharges = MsuGrn::where('msu_purchase_order_id', $msuPurchaseOrder->id)
            ->where('miscellaneous_charges', '>', 0)
            ->exists();

        return response()->json([
            'success' => true,
            'po' => [
                'id' => $msuPurchaseOrder->id,
                'po_number' => $msuPurchaseOrder->po_number,
                'vendor_id' => $msuPurchaseOrder->vendor_id,
                'vendor_name' => $msuPurchaseOrder->vendor?->name,
                'department_id' => $msuPurchaseOrder->department_id,
                'employee_master_id' => $msuPurchaseOrder->employee_master_id,
                'branch_id' => $msuPurchaseOrder->branch_id,
                'branch_code' => $msuPurchaseOrder->branch?->code ?: $msuPurchaseOrder->branch?->name,
                'miscellaneous_charges' => $msuPurchaseOrder->miscellaneous_charges,
                'miscellaneous_tax' => $msuPurchaseOrder->miscellaneous_tax,
                'has_previous_misc_charges' => $hasPreviousMiscCharges,
            ],
            'items' => $items,
        ]);
    }

    public function previewGrnNumber(Request $request, DocumentNumberGenerator $numberGenerator)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'msu_purchase_order_id' => 'nullable|exists:msu_purchase_orders,id',
            'grn_date' => 'nullable|date',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $branchCode = 'BRN';
        $companyId = $request->input('company_id')
            ?: \App\Support\GlobalCompanyFilter::resolveFormCompanyId();
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        if ($request->filled('branch_id')) {
            $branch = Branch::with('company')->find($request->branch_id);
            $branchCode = $branch?->code ?: ($branch?->name ?: 'BRN');
            $companyCode = $branch?->company?->code ?: $companyCode;
        } elseif ($request->filled('msu_purchase_order_id')) {
            $po = MsuPurchaseOrder::with('branch.company')->find($request->msu_purchase_order_id);
            $branchCode = $po?->branch?->code ?: ($po?->branch?->name ?: 'BRN');
            $companyCode = $po?->branch?->company?->code ?: $companyCode;
        }

        return response()->json([
            'success' => true,
            'grn_number' => $numberGenerator->previewMsuGrnNumber($companyCode, $branchCode, $request->grn_date),
        ]);
    }

    public function pdf(MsuGrn $msuGrn, \App\Services\ReportPdfExportService $pdfExport)
    {
        $msuGrn->load([
            'vendor',
            'purchaseOrder.branch.company',
            'purchaseOrder.shippingDetail',
            'purchaseOrder.charges',
            'department',
            'employeeMaster',
            'items.itemMaster.unit',
            'items.purchaseOrderItem',
        ]);
        $filename = str_replace(['/', '\\'], '-', $msuGrn->grn_number) . '.pdf';

        $barcodeBase64 = \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($msuGrn->grn_number, 'C128', 1.5, 22);
        $barcodeHtml = '<img src="data:image/png;base64,' . $barcodeBase64 . '" alt="barcode" style="height: 25px; width: 220px; display: inline-block;" /><div style="font-size: 11px; color: #555; margin-top: 4px; text-align: right;">' . $msuGrn->grn_number . '</div>';

        return $pdfExport->download(
            'msu-grns.pdf',
            [
                'grn' => $msuGrn,
                'barcodeHtml' => $barcodeHtml,
            ],
            $filename,
            'portrait'
        );
    }

    public function excel(MsuGrn $msuGrn)
    {
        $msuGrn->load([
            'vendor',
            'purchaseOrder.branch.company',
            'purchaseOrder.shippingDetail',
            'department',
            'employeeMaster',
            'items.itemMaster.unit',
            'items.purchaseOrderItem',
        ]);

        $filename = str_replace(['/', '\\'], '-', $msuGrn->grn_number) . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MsuGrnInvoiceExport($msuGrn),
            $filename
        );
    }

    private function formData(DocumentNumberGenerator $numberGenerator): array
    {
        $companyId = \App\Support\GlobalCompanyFilter::resolveFormCompanyId();
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        $branches = Branch::visibleToUser(auth()->user())
            ->with('company')
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        $selectedBranchId = old('branch_id');
        if ($selectedBranchId && ! $branches->contains('id', (int) $selectedBranchId)) {
            $selectedBranchId = null;
        }
        $selectedBranch = $selectedBranchId
            ? $branches->firstWhere('id', (int) $selectedBranchId)
            : null;
        $branchCode = $selectedBranch
            ? ($selectedBranch->code ?: $selectedBranch->name)
            : 'BRN';
        $previewCompanyCode = $selectedBranch?->company?->code ?: $companyCode;

        $purchaseOrders = MsuPurchaseOrder::with(['vendor', 'branch'])
            ->whereIn('status', [
                MsuPurchaseOrderStatus::Approved->value,
                MsuPurchaseOrderStatus::GrnPartiallyReceived->value,
            ])
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('branch', function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId);
                });
            })
            ->whereHas('branch', function ($q) {
                $q->visibleToUser(auth()->user());
            })
            ->orderByDesc('id')
            ->get();

        return [
            'branches' => $branches,
            'purchaseOrders' => $purchaseOrders,
            'departments' => Department::active()->orderBy('department_name')->get(),
            'employees' => EmployeeMaster::active()->with('department')->orderBy('employee_name')->get(),
            'formCompanyId' => $companyId,
            'companyCode' => $companyCode,
            'previewGrnNumber' => $numberGenerator->previewMsuGrnNumber($previewCompanyCode, $branchCode ?: 'BRN', old('grn_date', now())),
        ];
    }
}

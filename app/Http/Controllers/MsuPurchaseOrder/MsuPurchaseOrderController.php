<?php

namespace App\Http\Controllers\MsuPurchaseOrder;

use App\Enums\Procurement\MsuPurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MsuPurchaseOrder\MsuPurchaseOrderRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeMaster;
use App\Models\MiscellaneousCharge;
use App\Models\MsuItemMaster;
use App\Models\MsuPurchaseOrder;
use App\Models\MsuPurchaseOrderItem;
use App\Models\PaymentTerm;
use App\Models\ShippingDetail;
use App\Models\Vendor;
use App\Models\MsuItemRate;
use App\Services\Procurement\DocumentNumberGenerator;
use App\Services\ReportPdfExportService;
use App\Support\AmountInWords;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Facades\Excel;

class MsuPurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:msu-purchase-order:view', only: ['index', 'show', 'vendorAddress', 'branchDetails', 'previewPoNumber', 'pdf', 'export', 'exportPdf', 'excel']),
            new Middleware('permission:msu-purchase-order:create', only: ['create', 'store']),
            new Middleware('permission:msu-purchase-order:edit', only: ['edit', 'update']),
            new Middleware('permission:msu-purchase-order:approve', only: ['approve']),
            new Middleware('permission:msu-purchase-order:cancel', only: ['cancel']),
            new Middleware('permission:msu-purchase-order:delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'company_code', 'name']);
        $filterItems = MsuItemMaster::withTrashed()
            ->whereIn('id', MsuPurchaseOrderItem::query()->select('msu_item_master_id')->whereNotNull('msu_item_master_id'))
            ->orderBy('item_name')
            ->get(['id', 'item_code', 'item_name']);
        $companyId = session('global_company_id');
        $branches = Branch::visibleToUser(auth()->user())
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();
        $statuses = MsuPurchaseOrderStatus::options();

        $perPage = $request->input('per_page', 10);

        if ($viewMode === 'item') {
            $query = MsuPurchaseOrderItem::query()
                ->with(['purchaseOrder.vendor', 'purchaseOrder.branch', 'purchaseOrder.paymentTerm', 'itemMaster'])
                ->whereHas('purchaseOrder', function ($q) use ($request) {
                    $this->applyPoFilters($q, $request);
                });
            $this->applyItemLineFilter($query, $request);

            $items = $query
                ->orderByDesc('msu_purchase_order_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString();

            return view('msu-purchase-orders.index', compact('items', 'vendors', 'filterItems', 'branches', 'viewMode', 'statuses', 'perPage'));
        }

        $query = MsuPurchaseOrder::with(['vendor', 'paymentTerm', 'branch', 'items']);
        $this->applyPoFilters($query, $request);

        $purchaseOrders = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('msu-purchase-orders.index', compact('purchaseOrders', 'vendors', 'filterItems', 'branches', 'viewMode', 'statuses', 'perPage'));
    }

    private function applyPoFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('item_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('msu_item_master_id', $request->item_id);
            });
        }

        $search = trim((string) $request->input('search', ''));
        if ($search === '' && $request->filled('po_number')) {
            $search = trim((string) $request->po_number);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('po_number', 'like', $like)
                    ->orWhereHas('grns', function ($grn) use ($like) {
                        $grn->where('vendor_invoice_number', 'like', $like);
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('po_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('po_date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (session()->has('global_company_id')) {
            $query->whereHas('branch', function ($q) {
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

    private function filteredPurchaseOrders(Request $request): Collection
    {
        $query = MsuPurchaseOrder::with(['vendor', 'paymentTerm', 'branch.company', 'department', 'items']);
        $this->applyPoFilters($query, $request);

        return $query->orderByDesc('id')->get();
    }

    private function filteredItems(Request $request): Collection
    {
        $query = MsuPurchaseOrderItem::query()
            ->with([
                'purchaseOrder.vendor',
                'purchaseOrder.branch.company',
                'itemMaster.productCategory',
                'itemMaster.productSubCategory',
                'itemMaster.unit',
            ])
            ->whereHas('purchaseOrder', function ($q) use ($request) {
                $this->applyPoFilters($q, $request);
            });
        $this->applyItemLineFilter($query, $request);

        return $query
            ->orderByDesc('msu_purchase_order_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
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
                    return $this->rows->map(function (MsuPurchaseOrderItem $item) {
                        $po = $item->purchaseOrder;

                        return [
                            $po?->branch?->company?->name,
                            $po?->branch?->name,
                            $po?->po_number,
                            optional($po?->po_date)->format('d-m-Y'),
                            $po?->vendor?->name,
                            $po?->vendor?->gst_number,
                            $item->item_code,
                            $item->item_name,
                            $item->hsn,
                            $item->itemMaster?->productCategory?->category_name,
                            $item->itemMaster?->productSubCategory?->sub_category_name,
                            $item->itemMaster?->unit?->unit_name ?: ($item->itemMaster?->uom ?: null),
                            (float) $item->quantity,
                            (float) $item->price,
                            $item->gross_value,
                            (float) $item->tax,
                            $item->tax_value,
                            (float) $item->net_value,
                            $po?->statusEnum()?->label(),
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Company', 'Branch', 'PO Number', 'PO Date', 'Vendor', 'Vendor GST',
                        'Item Code', 'Item Name', 'HSN', 'Product Category', 'Product Sub Category', 'UOM',
                        'Qty', 'Rate', 'Gross Value', 'Tax %', 'Tax Value', 'Net Value', 'Status',
                    ];
                }
            };

            return Excel::download($export, 'msu-po-items-'.now()->format('Ymd_His').'.xlsx');
        }

        $rows = $this->filteredPurchaseOrders($request);
        $export = new class($rows) implements FromCollection, WithHeadings
        {
            public function __construct(private readonly Collection $rows) {}

            public function collection()
            {
                return $this->rows->values()->map(fn (MsuPurchaseOrder $po, int $index) => [
                    $index + 1,
                    $po->branch?->company?->name,
                    $po->branch?->name,
                    $po->po_number,
                    optional($po->po_date)->format('d-m-Y'),
                    $po->vendor?->name,
                    $po->department?->department_name,
                    $po->paymentTerm?->term_name,
                    $po->items->count(),
                    (float) $po->total_value,
                    (float) $po->miscellaneous_charges,
                    (float) $po->grand_total,
                    $po->statusEnum()->label(),
                ]);
            }

            public function headings(): array
            {
                return [
                    'S/No', 'Company', 'Branch', 'PO Number', 'PO Date', 'Vendor', 'Department',
                    'Payment Terms', 'Items', 'Total Value', 'Misc Charges', 'Grand Total', 'Status',
                ];
            }
        };

        return Excel::download($export, 'msu-purchase-orders-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request, ReportPdfExportService $pdfExport)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $filters = $request->only(['vendor_id', 'from_date', 'to_date', 'po_number', 'status', 'view']);

        if ($viewMode === 'item') {
            $items = $this->filteredItems($request);

            return $pdfExport->download(
                'msu-purchase-orders.export-items',
                compact('items', 'filters'),
                'msu-po-items-'.now()->format('Ymd_His').'.pdf',
                'landscape'
            );
        }

        $purchaseOrders = $this->filteredPurchaseOrders($request);

        return $pdfExport->download(
            'msu-purchase-orders.export-list',
            compact('purchaseOrders', 'filters'),
            'msu-purchase-orders-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    public function create()
    {
        return view('msu-purchase-orders.create', $this->formData());
    }

    public function store(MsuPurchaseOrderRequest $request, DocumentNumberGenerator $numberGenerator)
    {
        $po = DB::transaction(function () use ($request, $numberGenerator) {
            $validated = $request->validated();
            $branch = Branch::with('company')->findOrFail($validated['branch_id']);
            $branchCode = $branch->code ?: $branch->name;
            $companyCode = $branch->company?->code ?: 'MSULLP';
            $poNumber = $numberGenerator->nextMsuPoNumber($companyCode, $branchCode, $validated['po_date'] ?? now());

            $po = MsuPurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => $validated['po_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'date_of_commencement' => $validated['date_of_commencement'] ?? ($validated['po_date'] ?? null),
                'vendor_id' => $validated['vendor_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_master_id' => $validated['employee_master_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'shipping_detail_id' => $validated['shipping_detail_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'miscellaneous_charge_id' => $validated['miscellaneous_charge_id'] ?? null,
                'miscellaneous_charges' => 0,
                'miscellaneous_tax' => 0,
                'sgst' => 0,
                'cgst' => 0,
                'status' => MsuPurchaseOrderStatus::PoCreated,
                'created_by' => auth()->id(),
                'total_value' => 0,
                'grand_total' => 0,
            ]);

            $this->syncItems($po, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($po, $validated['charges']);
            }
            $po->recalculateTotals();

            return $po;
        });

        return redirect()
            ->route('msu-purchase-orders.show', $po)
            ->with('success', 'MSU Purchase Order created successfully.');
    }

    public function show(MsuPurchaseOrder $msuPurchaseOrder)
    {
        $msuPurchaseOrder->load(['vendor', 'branch', 'paymentTerm', 'miscellaneousCharge', 'employeeMaster', 'department', 'shippingDetail', 'charges', 'items.itemMaster.hsn', 'items.itemMaster.rates']);

        return view('msu-purchase-orders.show', compact('msuPurchaseOrder'));
    }

    public function edit(MsuPurchaseOrder $msuPurchaseOrder)
    {
        if (! $msuPurchaseOrder->statusEnum()->isEditable()) {
            return redirect()
                ->route('msu-purchase-orders.show', $msuPurchaseOrder)
                ->with('error', 'Only POs with status "PO Created" can be edited.');
        }

        $msuPurchaseOrder->load(['items', 'charges', 'paymentTerm', 'miscellaneousCharge', 'branch.company']);

        return view('msu-purchase-orders.edit', array_merge(
            $this->formData($msuPurchaseOrder),
            ['msuPurchaseOrder' => $msuPurchaseOrder]
        ));
    }

    public function update(MsuPurchaseOrderRequest $request, MsuPurchaseOrder $msuPurchaseOrder)
    {
        if (! $msuPurchaseOrder->statusEnum()->isEditable()) {
            return redirect()
                ->route('msu-purchase-orders.show', $msuPurchaseOrder)
                ->with('error', 'Only POs with status "PO Created" can be edited.');
        }

        DB::transaction(function () use ($request, $msuPurchaseOrder) {
            $validated = $request->validated();

            $msuPurchaseOrder->update([
                'po_date' => $validated['po_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'date_of_commencement' => $validated['date_of_commencement'] ?? ($validated['po_date'] ?? null),
                'vendor_id' => $validated['vendor_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_master_id' => $validated['employee_master_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'shipping_detail_id' => $validated['shipping_detail_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'miscellaneous_charge_id' => $validated['miscellaneous_charge_id'] ?? null,
                'miscellaneous_charges' => 0,
                'miscellaneous_tax' => 0,
            ]);

            $msuPurchaseOrder->items()->delete();
            $msuPurchaseOrder->charges()->delete();
            $this->syncItems($msuPurchaseOrder, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($msuPurchaseOrder, $validated['charges']);
            }
            $msuPurchaseOrder->recalculateTotals();
        });

        return redirect()
            ->route('msu-purchase-orders.show', $msuPurchaseOrder)
            ->with('success', 'MSU Purchase Order updated successfully.');
    }

    public function approve(MsuPurchaseOrder $msuPurchaseOrder)
    {
        if ($msuPurchaseOrder->statusEnum() !== MsuPurchaseOrderStatus::PoCreated) {
            return back()->with('error', 'Only created Purchase Orders can be approved.');
        }

        $msuPurchaseOrder->update([
            'status' => MsuPurchaseOrderStatus::Approved,
        ]);

        return back()->with('success', 'Purchase Order approved successfully.');
    }

    public function cancel(Request $request, MsuPurchaseOrder $msuPurchaseOrder)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        if (! $msuPurchaseOrder->cancel()) {
            return back()->with('error', 'This PO cannot be cancelled in its current status.');
        }

        $msuPurchaseOrder->update([
            'cancel_reason' => $request->cancel_reason,
        ]);

        return back()->with('success', 'Purchase Order cancelled successfully.');
    }

    public function destroy(MsuPurchaseOrder $msuPurchaseOrder)
    {
        if ($msuPurchaseOrder->statusEnum() !== MsuPurchaseOrderStatus::PoCreated) {
            return redirect()->back()->with('error', 'Only created Purchase Orders can be deleted.');
        }

        if ($msuPurchaseOrder->grns()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete a Purchase Order that has been passed to GRN.');
        }

        $msuPurchaseOrder->delete();

        return redirect()
            ->route('msu-purchase-orders.index')
            ->with('success', 'MSU Purchase Order deleted successfully.');
    }

    public function vendorAddress(Vendor $vendor)
    {
        $parts = array_filter([
            $vendor->address,
            $vendor->city,
            $vendor->state,
            $vendor->country,
        ]);

        return response()->json([
            'success' => true,
            'shipping_address' => implode(', ', $parts),
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'company_code' => $vendor->company_code,
            ],
        ]);
    }

    public function branchDetails(Branch $branch)
    {
        $parts = array_filter([
            $branch->address,
            $branch->city,
            $branch->state,
            $branch->country,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'city' => $branch->city,
                'state' => $branch->state,
                'country' => $branch->country,
                'full_address' => implode(', ', $parts),
                'gst_number' => $branch->gst_number,
                'contact_person' => $branch->contact_person,
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
            ],
        ]);
    }

    public function previewPoNumber(Request $request, DocumentNumberGenerator $numberGenerator)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'po_date' => 'nullable|date',
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
        }

        return response()->json([
            'success' => true,
            'po_number' => $numberGenerator->previewMsuPoNumber($companyCode, $branchCode, $request->po_date),
        ]);
    }

    public function pdf(MsuPurchaseOrder $msuPurchaseOrder, ReportPdfExportService $pdfExport)
    {
        $msuPurchaseOrder->load(['vendor', 'branch', 'paymentTerm', 'miscellaneousCharge', 'shippingDetail', 'charges', 'items.itemMaster.unit']);

        $filename = str_replace(['/', '\\'], '-', $msuPurchaseOrder->po_number) . '.pdf';

        $barcodeBase64 = \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($msuPurchaseOrder->po_number, 'C128', 1.5, 22);
        $barcodeHtml = '<img src="data:image/png;base64,' . $barcodeBase64 . '" alt="barcode" style="height: 25px; width: 220px; display: inline-block;" /><div style="font-size: 11px; color: #555; margin-top: 4px; text-align: right;">' . $msuPurchaseOrder->po_number . '</div>';

        return $pdfExport->download(
            'msu-purchase-orders.pdf',
            [
                'msuPurchaseOrder' => $msuPurchaseOrder,
                'amountInWords' => AmountInWords::indianRupees($msuPurchaseOrder->grand_total),
                'barcodeHtml' => $barcodeHtml,
            ],
            $filename,
            'portrait'
        );
    }

    public function excel(MsuPurchaseOrder $msuPurchaseOrder)
    {
        $filename = str_replace(['/', '\\'], '-', $msuPurchaseOrder->po_number) . '.xlsx';
        
        return Excel::download(
            new \App\Exports\MsuPurchaseOrderInvoiceExport($msuPurchaseOrder),
            $filename
        );
    }

    private function formData(?MsuPurchaseOrder $po = null): array
    {
        $poDate = old('po_date', $po?->po_date?->format('Y-m-d') ?? now()->format('Y-m-d'));

        $items = MsuItemMaster::active()
            ->with(['productCategory', 'unit', 'hsn', 'rates'])
            ->orderBy('item_name')
            ->get();

        // Keep existing PO line items available in the autocomplete even if inactive
        if ($po) {
            $existingIds = $items->pluck('id');
            $missingIds = $po->items->pluck('msu_item_master_id')->filter()->unique()->diff($existingIds);
            if ($missingIds->isNotEmpty()) {
                $items = $items->concat(
                    MsuItemMaster::with(['productCategory', 'unit', 'hsn', 'rates'])
                        ->whereIn('id', $missingIds)
                        ->get()
                );
            }
        }

        $items = $items->map(function (MsuItemMaster $item) use ($poDate) {
            $rate = $item->applicableRate($poDate);
            $name = (string) $item->item_name;
            $shortName = mb_strlen($name) > 120
                ? (mb_substr($name, 0, 120).'…')
                : $name;

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'label' => $item->item_code.' - '.$item->item_name,
                // Native datalist option values are truncated by browsers (~512 chars).
                // Keep a short unique value so PO lines still resolve the item id.
                'short_label' => $item->item_code.' - '.$shortName,
                'hsn' => $item->hsn?->hsn_code ?? '',
                'rate' => $rate ? (float) $rate->rate : '',
                'tax' => $rate ? (float) $rate->tax : '',
            ];
        })->values();

        // Create/edit always use a company (navbar hides "All"). Resolve locally
        // without writing session so index "All" survives Back from these pages.
        $poCompanyId = $po?->branch?->company_id;
        $companyId = \App\Support\GlobalCompanyFilter::resolveFormCompanyId($poCompanyId);
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        $branches = Branch::visibleToUser(auth()->user())
            ->with('company')
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        $selectedBranchId = old('branch_id', $po?->branch_id);
        if ($selectedBranchId && ! $branches->contains('id', (int) $selectedBranchId)) {
            $selectedBranchId = null;
        }
        $selectedBranch = $selectedBranchId
            ? $branches->firstWhere('id', $selectedBranchId)
            : null;
        $branchCode = $selectedBranch
            ? ($selectedBranch->code ?: $selectedBranch->name)
            : 'BRN';
        $previewPoNumber = app(DocumentNumberGenerator::class)
            ->previewMsuPoNumber($companyCode, $branchCode, $poDate);

        $displayPoNumber = $po?->po_number ?: $previewPoNumber;

        return [
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'branches' => $branches,
            'formCompanyId' => $companyId,
            'companyCode' => $companyCode,
            'poCompanyId' => $poCompanyId,
            'previewPoNumber' => $previewPoNumber,
            'displayPoNumber' => $displayPoNumber,
            'employees' => EmployeeMaster::active()->with('department')->orderBy('employee_name')->get(),
            'departments' => Department::active()->orderBy('department_name')->get(),
            'shippingDetails' => ShippingDetail::active()->orderBy('shipping_name')->get(),
            'paymentTerms' => PaymentTerm::active()->orderBy('term_name')->get(),
            'miscellaneousCharges' => MiscellaneousCharge::active()->orderBy('charge_name')->get(),
            'items' => $items,
        ];
    }

    private function syncItems(MsuPurchaseOrder $po, array $items): void
    {
        foreach (array_values($items) as $index => $row) {
            $master = MsuItemMaster::with(['hsn', 'rates'])->findOrFail($row['msu_item_master_id']);
            $applicable = $master->applicableRate($po->po_date);
            $qty = (float) $row['quantity'];
            $price = array_key_exists('price', $row) && $row['price'] !== '' && $row['price'] !== null
                ? (float) $row['price']
                : (float) ($applicable?->rate ?? 0);
            $tax = array_key_exists('tax', $row) && $row['tax'] !== '' && $row['tax'] !== null
                ? (float) $row['tax']
                : (float) ($applicable?->tax ?? $master->hsn?->tax_rate ?? 0);
            $hsn = ! empty($row['hsn']) ? $row['hsn'] : ($master->hsn?->hsn_code);
            $net = MsuPurchaseOrderItem::computeNetValue($qty, $price, $tax);

            $po->items()->create([
                'msu_item_master_id' => $master->id,
                'item_code' => $master->item_code,
                'item_name' => $master->item_name,
                'hsn' => $hsn,
                'quantity' => $qty,
                'price' => $price,
                'tax' => $tax,
                'net_value' => $net,
                'sort_order' => $index + 1,
            ]);

            // Update item rate if it differs from the applicable rate
            $currentRate = $applicable ? (float) $applicable->rate : null;
            $currentTax = $applicable ? (float) $applicable->tax : null;
            
            if ($currentRate !== $price || $currentTax !== $tax) {
                MsuItemRate::create([
                    'msu_item_master_id' => $master->id,
                    'rate' => $price,
                    'tax' => $tax,
                    'effective_from' => $po->po_date,
                ]);
            }
        }
    }

    private function syncCharges(MsuPurchaseOrder $po, array $charges): void
    {
        foreach (array_values($charges) as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if ($amount <= 0 && empty($row['charge_name'])) continue;

            $po->charges()->create([
                'charge_name' => $row['charge_name'] ?? 'Misc Charge',
                'amount' => $amount,
                'tax_percentage' => (float) ($row['tax_percentage'] ?? 0),
            ]);
        }
    }
}

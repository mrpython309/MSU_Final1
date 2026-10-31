<?php

namespace App\Http\Controllers;

use App\Enums\Procurement\MsuWorkOrderStatus;
use App\Http\Requests\MsuWorkOrderRequest;
use App\Services\Procurement\MsuWorkOrderRecurrenceService;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeMaster;
use App\Models\MiscellaneousCharge;
use App\Models\MsuWorkOrder;
use App\Models\MsuWorkOrderItem;
use App\Models\PaymentTerm;
use App\Models\ShippingDetail;
use App\Models\Unit;
use App\Models\Vendor;
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

class MsuWorkOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:msu-work-order:view', only: ['index', 'show', 'previewWoNumber', 'pdf', 'excel', 'export', 'exportPdf']),
            new Middleware('permission:msu-work-order:create', only: ['create', 'store']),
            new Middleware('permission:msu-work-order:edit', only: ['edit', 'update']),
            new Middleware('permission:msu-work-order:approve', only: ['approve']),
            new Middleware('permission:msu-work-order:cancel', only: ['cancel']),
            new Middleware('permission:msu-work-order:delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $companyId = session('global_company_id');
        $branches = Branch::visibleToUser(auth()->user())
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();
        $statuses = collect(MsuWorkOrderStatus::cases())
            ->mapWithKeys(fn (MsuWorkOrderStatus $status) => [$status->value => $status->label()])
            ->all();
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'company_code', 'name']);
        $workOrderOptions = MsuWorkOrder::query()
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('branch', function ($branch) use ($companyId) {
                    $branch->where('company_id', $companyId);
                });
            })
            ->orderByDesc('id')
            ->get(['id', 'wo_number']);

        $perPage = $request->input('per_page', 10);

        if ($viewMode === 'item') {
            $query = MsuWorkOrderItem::query()
                ->with(['workOrder.vendor', 'workOrder.branch.company', 'workOrder.paymentTerm'])
                ->whereHas('workOrder', function ($q) use ($request) {
                    $this->applyWoFilters($q, $request);
                });

            $items = $query
                ->orderByDesc('msu_work_order_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString();

            return view('msu-work-orders.index', compact('items', 'branches', 'vendors', 'workOrderOptions', 'viewMode', 'statuses', 'perPage'));
        }

        $query = MsuWorkOrder::with(['vendor', 'paymentTerm', 'branch.company', 'department', 'items']);
        $this->applyWoFilters($query, $request);

        $workOrders = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('msu-work-orders.index', compact('workOrders', 'branches', 'vendors', 'workOrderOptions', 'viewMode', 'statuses', 'perPage'));
    }

    private function applyWoFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('work_order_id')) {
            $query->where('id', $request->work_order_id);
        }

        if ($request->filled('wo_number')) {
            $query->where('wo_number', 'like', '%'.$request->wo_number.'%');
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('employeeMaster', function ($employee) use ($like) {
                    $employee->where('employee_name', 'like', $like);
                })->orWhereHas('department', function ($department) use ($like) {
                    $department->where('department_name', 'like', $like);
                });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('wo_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('wo_date', '<=', $request->to_date);
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

    private function filteredWorkOrders(Request $request): Collection
    {
        $query = MsuWorkOrder::with(['vendor', 'paymentTerm', 'branch.company', 'department', 'items']);
        $this->applyWoFilters($query, $request);

        return $query->orderByDesc('id')->get();
    }

    private function filteredItems(Request $request): Collection
    {
        return MsuWorkOrderItem::query()
            ->with([
                'workOrder.vendor',
                'workOrder.branch.company',
                'unit',
                'invoiceItems.workInvoice',
            ])
            ->whereHas('workOrder', function ($q) use ($request) {
                $this->applyWoFilters($q, $request);
            })
            ->orderByDesc('msu_work_order_id')
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
                    return $this->rows->values()->map(function (MsuWorkOrderItem $item, int $index) {
                        $wo = $item->workOrder;
                        $invoices = $item->invoiceItems
                            ->map(fn ($line) => $line->workInvoice)
                            ->filter()
                            ->unique('id')
                            ->sortBy(fn ($invoice) => sprintf(
                                '%s-%010d',
                                optional($invoice->invoice_date)->format('Y-m-d') ?? '9999-99-99',
                                $invoice->id
                            ))
                            ->values();
                        $from = $wo?->contract_period_from?->format('d-m-Y');
                        $to = $wo?->contract_period_to?->format('d-m-Y');
                        $contractPeriod = match (true) {
                            $from && $to => $from.' to '.$to,
                            (bool) $from => $from,
                            (bool) $to => $to,
                            default => null,
                        };

                        return [
                            $index + 1,
                            $wo?->branch?->company?->name,
                            $wo?->branch?->name,
                            $wo?->wo_number,
                            optional($wo?->wo_date)->format('d-m-Y'),
                            $wo?->vendor?->name,
                            $wo?->vendor?->gst_number,
                            $wo?->subject,
                            $item->scope_of_work,
                            $item->work_description,
                            $item->hsn,
                            $item->unit?->unit_name,
                            (float) $item->quantity,
                            (float) $item->price,
                            $item->gross_value,
                            (float) $item->tax,
                            $item->tax_value,
                            (float) $item->net_value,
                            $wo?->statusEnum()?->label(),
                            $invoices->map(fn ($invoice) => filled($invoice->invoice_number) ? $invoice->invoice_number : $invoice->wi_number)->filter()->implode(', ') ?: null,
                            $invoices->map(fn ($invoice) => $invoice->invoice_date?->format('d-m-Y'))->filter()->unique()->implode(', ') ?: null,
                            null,
                            $contractPeriod,
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'S/No', 'Company', 'Branch', 'WO Number', 'WO Date', 'Vendor', 'Vendor GST',
                        'Subject', 'Scope of Work', 'Work Description', 'HSN', 'UOM', 'Qty', 'Rate',
                        'Gross Value', 'Tax %', 'Tax Value', 'Net Value', 'Status', 'Invoice No',
                        'Invoice Date', 'Capex / Opex', 'Contract Period',
                    ];
                }
            };

            return Excel::download($export, 'msu-wo-items-'.now()->format('Ymd_His').'.xlsx');
        }

        $rows = $this->filteredWorkOrders($request);
        $export = new class($rows) implements FromCollection, WithHeadings, WithStrictNullComparison
        {
            public function __construct(private readonly Collection $rows) {}

            public function collection()
            {
                return $this->rows->values()->map(function (MsuWorkOrder $wo, int $index) {
                    $taxAmount = round((float) $wo->sgst + (float) $wo->cgst + (float) $wo->igst, 2);
                    $taxable = (float) $wo->total_value + (float) $wo->miscellaneous_charges;
                    $taxPercent = $taxable > 0 ? round($taxAmount / $taxable * 100, 2) : 0;

                    return [
                        $index + 1,
                        $wo->branch?->company?->name,
                        $wo->branch?->name,
                        $wo->wo_number,
                        optional($wo->wo_date)->format('d-m-Y'),
                        $wo->vendor?->name,
                        $wo->vendor?->gst_number,
                        $wo->department?->department_name,
                        $wo->paymentTerm?->term_name,
                        $wo->items->count(),
                        (float) $wo->total_value,
                        (float) $wo->miscellaneous_charges,
                        $taxPercent,
                        $taxAmount,
                        (float) $wo->grand_total,
                        $wo->statusEnum()->label(),
                    ];
                });
            }

            public function headings(): array
            {
                return [
                    'S/No', 'Company', 'Branch', 'WO Number', 'WO Date', 'Vendor', 'Vendor GST',
                    'Department', 'Payment Terms', 'Items', 'Total Value', 'Misc Charges',
                    'Tax %', 'Tax Amount', 'Grand Total', 'Status',
                ];
            }
        };

        return Excel::download($export, 'msu-work-orders-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request, ReportPdfExportService $pdfExport)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $filters = $request->only(['from_date', 'to_date', 'wo_number', 'work_order_id', 'vendor_id', 'search', 'status', 'view', 'branch_id']);

        if ($viewMode === 'item') {
            $items = $this->filteredItems($request);

            return $pdfExport->download(
                'msu-work-orders.export-items',
                compact('items', 'filters'),
                'msu-wo-items-'.now()->format('Ymd_His').'.pdf',
                'landscape'
            );
        }

        $workOrders = $this->filteredWorkOrders($request);

        return $pdfExport->download(
            'msu-work-orders.export-list',
            compact('workOrders', 'filters'),
            'msu-work-orders-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    public function excel(MsuWorkOrder $msuWorkOrder)
    {
        $request = request()->merge(['wo_number' => $msuWorkOrder->wo_number, 'view' => 'main']);

        return $this->export($request);
    }

    public function create()
    {
        return view('msu-work-orders.create', $this->formData());
    }

    public function store(MsuWorkOrderRequest $request, DocumentNumberGenerator $numberGenerator)
    {
        $wo = DB::transaction(function () use ($request, $numberGenerator) {
            $validated = $request->validated();
            $branch = Branch::with('company')->findOrFail($validated['branch_id']);
            $branchCode = $branch->code ?: $branch->name;
            $companyCode = $branch->company?->code ?: 'MSULLP';
            $woNumber = $numberGenerator->nextMsuWoNumber($companyCode, $branchCode, $validated['wo_date'] ?? now());

            $wo = MsuWorkOrder::create([
                'wo_number' => $woNumber,
                'wo_date' => $validated['wo_date'],
                'contract_period_from' => $validated['contract_period_from'] ?? null,
                'contract_period_to' => $validated['contract_period_to'] ?? null,
                'frequency' => $validated['frequency'] ?? null,
                'subject' => $validated['subject'] ?? null,
                
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
                'status' => MsuWorkOrderStatus::WoCreated,
                'created_by' => auth()->id(),
                'total_value' => 0,
                'grand_total' => 0,
            ]);

            $this->syncItems($wo, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($wo, $validated['charges']);
            }
            $wo->recalculateTotals();

            return $wo;
        });

        $created = app(MsuWorkOrderRecurrenceService::class)->syncAndGenerate($wo);
        $message = 'Work Order created successfully.';
        if ($created > 0) {
            $message .= ' '.$created.' recurring work '.($created === 1 ? 'order was' : 'orders were').' created for dates already due.';
        }

        return redirect()
            ->route('msu-work-orders.show', $wo)
            ->with('success', $message);
    }

    public function show(MsuWorkOrder $msuWorkOrder)
    {
        $msuWorkOrder->load(['vendor', 'branch', 'paymentTerm', 'miscellaneousCharge', 'employeeMaster', 'department', 'shippingDetail', 'items', 'charges', 'recurrenceSource']);
        return view('msu-work-orders.show', compact('msuWorkOrder'));
    }

    public function edit(MsuWorkOrder $msuWorkOrder)
    {
        if (! $msuWorkOrder->statusEnum()->isEditable()) {
            return redirect()
                ->route('msu-work-orders.show', $msuWorkOrder)
                ->with('error', 'Only Work Orders with status "WO Created" can be edited.');
        }

        $msuWorkOrder->load(['items', 'charges', 'paymentTerm', 'miscellaneousCharge', 'branch.company']);

        return view('msu-work-orders.edit', array_merge(
            $this->formData($msuWorkOrder),
            ['msuWorkOrder' => $msuWorkOrder]
        ));
    }

    public function update(MsuWorkOrderRequest $request, MsuWorkOrder $msuWorkOrder)
    {
        if (! $msuWorkOrder->statusEnum()->isEditable()) {
            return redirect()
                ->route('msu-work-orders.show', $msuWorkOrder)
                ->with('error', 'Only Work Orders with status "WO Created" can be edited.');
        }

        DB::transaction(function () use ($request, $msuWorkOrder) {
            $validated = $request->validated();

            $msuWorkOrder->update([
                'wo_date' => $validated['wo_date'],
                'contract_period_from' => $validated['contract_period_from'] ?? null,
                'contract_period_to' => $validated['contract_period_to'] ?? null,
                'frequency' => $msuWorkOrder->recurrence_source_id ? null : ($validated['frequency'] ?? null),
                'subject' => $validated['subject'] ?? null,
                
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

            $msuWorkOrder->items()->delete();
            $msuWorkOrder->charges()->delete();
            $this->syncItems($msuWorkOrder, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($msuWorkOrder, $validated['charges']);
            }
            $msuWorkOrder->recalculateTotals();
        });

        app(MsuWorkOrderRecurrenceService::class)->syncAndGenerate($msuWorkOrder);

        return redirect()
            ->route('msu-work-orders.show', $msuWorkOrder)
            ->with('success', 'Work Order updated successfully.');
    }

    public function cancel(Request $request, MsuWorkOrder $msuWorkOrder)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        if (! $msuWorkOrder->cancel()) {
            return back()->with('error', 'This WO cannot be cancelled in its current status.');
        }

        $msuWorkOrder->update([
            'cancel_reason' => $request->cancel_reason,
            'next_occurrence_date' => null,
        ]);

        return back()->with('success', 'Work Order cancelled successfully.');
    }

    public function approve(MsuWorkOrder $msuWorkOrder)
    {
        if ($msuWorkOrder->status !== MsuWorkOrderStatus::WoCreated) {
            return back()->with('error', 'Only created Work Orders can be approved.');
        }

        $msuWorkOrder->update([
            'status' => MsuWorkOrderStatus::Approved,
        ]);

        return back()->with('success', 'Work Order approved successfully.');
    }

    public function destroy(MsuWorkOrder $msuWorkOrder)
    {
        if ($msuWorkOrder->statusEnum() !== MsuWorkOrderStatus::WoCreated) {
            return redirect()->back()->with('error', 'Only created Work Orders can be deleted.');
        }

        if ($msuWorkOrder->invoices()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete a Work Order that has invoices.');
        }

        $msuWorkOrder->delete();

        return redirect()
            ->route('msu-work-orders.index')
            ->with('success', 'Work Order deleted successfully.');
    }

    public function previewWoNumber(Request $request, DocumentNumberGenerator $numberGenerator)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'wo_date' => 'nullable|date',
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
            'wo_number' => $numberGenerator->previewMsuWoNumber($companyCode, $branchCode, $request->wo_date),
        ]);
    }

    public function pdf(MsuWorkOrder $msuWorkOrder, ReportPdfExportService $pdfExport)
    {
        $msuWorkOrder->load(['vendor', 'branch', 'paymentTerm', 'miscellaneousCharge', 'shippingDetail', 'items.unit', 'charges']);

        $filename = str_replace(['/', '\\'], '-', $msuWorkOrder->wo_number) . '.pdf';

        $barcodeBase64 = \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($msuWorkOrder->wo_number, 'C128', 1.5, 22);
        $barcodeHtml = '<img src="data:image/png;base64,' . $barcodeBase64 . '" alt="barcode" style="height: 25px; width: 220px; display: inline-block;" /><div style="font-size: 11px; color: #555; margin-top: 4px; text-align: right;">' . $msuWorkOrder->wo_number . '</div>';

        return $pdfExport->download(
            'msu-work-orders.pdf',
            [
                'msuWorkOrder' => $msuWorkOrder,
                'amountInWords' => AmountInWords::indianRupees($msuWorkOrder->grand_total),
                'barcodeHtml' => $barcodeHtml,
            ],
            $filename,
            'portrait'
        );
    }

    private function formData(?MsuWorkOrder $wo = null): array
    {
        // Create/edit always use a company (navbar hides "All"). Resolve locally
        // without writing session so index "All" survives Back from these pages.
        // Same company-filter behavior as PO create/edit.
        $woCompanyId = $wo?->branch?->company_id;
        $companyId = \App\Support\GlobalCompanyFilter::resolveFormCompanyId($woCompanyId);
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        $branches = Branch::visibleToUser(auth()->user())
            ->with('company')
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        $selectedBranchId = old('branch_id', $wo?->branch_id);
        if ($selectedBranchId && ! $branches->contains('id', (int) $selectedBranchId)) {
            $selectedBranchId = null;
        }
        $selectedBranch = $selectedBranchId
            ? $branches->firstWhere('id', $selectedBranchId)
            : null;
        $branchCode = $selectedBranch
            ? ($selectedBranch->code ?: $selectedBranch->name)
            : 'BRN';
        $woDate = old('wo_date', $wo?->wo_date?->format('Y-m-d') ?? now()->format('Y-m-d'));

        $previewWoNumber = app(DocumentNumberGenerator::class)
            ->previewMsuWoNumber($companyCode, $branchCode, $woDate);

        $displayWoNumber = $wo?->wo_number ?: $previewWoNumber;

        return [
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'branches' => $branches,
            'formCompanyId' => $companyId,
            'companyCode' => $companyCode,
            'woCompanyId' => $woCompanyId,
            'previewWoNumber' => $previewWoNumber,
            'displayWoNumber' => $displayWoNumber,
            'employees' => EmployeeMaster::active()->with('department')->orderBy('employee_name')->get(),
            'departments' => Department::active()->orderBy('department_name')->get(),
            'shippingDetails' => ShippingDetail::active()->orderBy('shipping_name')->get(),
            'paymentTerms' => PaymentTerm::active()->orderBy('term_name')->get(),
            'miscellaneousCharges' => MiscellaneousCharge::active()->orderBy('charge_name')->get(),
            'units' => Unit::active()->orderBy('unit_name')->get(),
        ];
    }

    private function syncItems(MsuWorkOrder $wo, array $items): void
    {
        foreach (array_values($items) as $index => $row) {
            $qty = (float) $row['quantity'];
            $price = (float) $row['price'];
            $tax = (float) $row['tax'];
            $hsn = ! empty($row['hsn']) ? $row['hsn'] : null;
            $net = MsuWorkOrderItem::computeNetValue($qty, $price, $tax);

            $wo->items()->create([
                'work_description' => $row['work_description'],
                'scope_of_work' => $row['scope_of_work'],
                'hsn' => $hsn,
                'unit_id' => ! empty($row['unit_id']) ? $row['unit_id'] : null,
                'quantity' => $qty,
                'price' => $price,
                'tax' => $tax,
                'net_value' => $net,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncCharges(MsuWorkOrder $wo, array $charges): void
    {
        foreach (array_values($charges) as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if ($amount <= 0 && empty($row['charge_name'])) continue;

            $wo->charges()->create([
                'charge_name' => $row['charge_name'] ?? 'Misc Charge',
                'amount' => $amount,
                'tax_percentage' => (float) ($row['tax_percentage'] ?? 0),
            ]);
        }
    }
}

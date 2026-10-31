<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Department;
use App\Models\EmployeeMaster;
use App\Models\Branch;
use App\Models\Company;
use App\Models\MsuItemMaster;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Facades\Excel;

class MaterialRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:material_requests:view', only: ['index', 'show', 'export', 'exportPdf', 'pdf', 'excel']),
            new Middleware('permission:material_requests:create', only: ['create', 'store']),
            new Middleware('permission:material_requests:approve', only: ['submit']),
            new Middleware('permission:material_requests:cancel', only: ['cancel']),
            new Middleware('permission:material_requests:edit', only: ['edit', 'update']),
            new Middleware('permission:material_requests:delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $perPage = $request->input('per_page', 10);
        $branches = $this->filterBranches();
        $filterOptions = $this->filterOptions();

        if ($viewMode === 'item') {
            $items = $this->filteredMaterialRequestItemsQuery($request)
                ->paginate($perPage)
                ->withQueryString();

            return view('material_requests.index', array_merge(
                compact('items', 'branches', 'viewMode', 'perPage'),
                $filterOptions
            ));
        }

        $requests = $this->filteredMaterialRequests($request)->paginate($perPage)->withQueryString();

        return view('material_requests.index', array_merge(
            compact('requests', 'branches', 'viewMode', 'perPage'),
            $filterOptions
        ));
    }

    public function export(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';

        if ($viewMode === 'item') {
            $rows = $this->filteredMaterialRequestItems($request);
            $export = new class($rows) implements FromCollection, WithHeadings, WithStrictNullComparison
            {
                public function __construct(private readonly Collection $rows) {}

                public function collection()
                {
                    return $this->rows->map(function (MaterialRequestItem $item) {
                        $mr = $item->materialRequest;

                        return [
                            $mr?->company?->name ?? $mr?->branch?->company?->name,
                            $mr?->branch?->name,
                            $mr?->mr_number,
                            $mr?->request_date ? \Carbon\Carbon::parse($mr->request_date)->format('d-m-Y') : '',
                            $mr?->department?->department_name,
                            $mr?->employee?->employee_name,
                            $item->itemMaster?->item_code,
                            $item->itemMaster?->item_name,
                            $item->itemMaster?->hsn?->hsn_code ?? $item->itemMaster?->hsn?->code,
                            $item->unit?->unit_name ?? $item->itemMaster?->unit?->unit_name,
                            (float) $item->requested_qty,
                            (float) $item->issued_qty,
                            (float) $item->remaining_qty,
                            $mr ? ucfirst(str_replace('_', ' ', $mr->status)) : '',
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Company', 'Branch', 'MR Number', 'Request Date', 'Department', 'Employee',
                        'Item Code', 'Item Name', 'HSN', 'Unit',
                        'Requested Qty', 'Issued Qty', 'Remaining Qty', 'Status',
                    ];
                }
            };

            return Excel::download($export, 'material-request-items-'.now()->format('Ymd_His').'.xlsx');
        }

        $rows = $this->filteredMaterialRequests($request)->get();
        $export = new class($rows) implements FromCollection, WithHeadings
        {
            public function __construct(private readonly Collection $rows) {}

            public function collection()
            {
                return $this->rows->values()->map(fn (MaterialRequest $mr, int $index) => [
                    $index + 1,
                    $mr->company?->name ?? $mr->branch?->company?->name,
                    $mr->branch?->name,
                    $mr->mr_number,
                    $mr->request_date ? \Carbon\Carbon::parse($mr->request_date)->format('d-m-Y') : '',
                    $mr->department?->department_name,
                    $mr->employee?->employee_name,
                    $mr->items->count(),
                    ucfirst(str_replace('_', ' ', $mr->status)),
                ]);
            }

            public function headings(): array
            {
                return [
                    'S/No', 'Company', 'Branch', 'MR Number', 'Request Date', 'Department', 'Employee', 'Items', 'Status',
                ];
            }
        };

        return Excel::download($export, 'material-requests-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request, \App\Services\ReportPdfExportService $pdfExport)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $filters = $request->only([
            'mr_number', 'status', 'request_date', 'branch_id',
            'department_id', 'employee_master_id', 'item_id', 'view',
        ]);

        if ($viewMode === 'item') {
            $items = $this->filteredMaterialRequestItems($request);

            return $pdfExport->download(
                'material_requests.export-items',
                compact('items', 'filters'),
                'material-request-items-'.now()->format('Ymd_His').'.pdf',
                'landscape'
            );
        }

        $materialRequests = $this->filteredMaterialRequests($request)->get();

        return $pdfExport->download(
            'material_requests.export-list',
            compact('materialRequests', 'filters'),
            'material-requests-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    public function pdf(MaterialRequest $materialRequest, \App\Services\ReportPdfExportService $pdfExport)
    {
        $materialRequest->load([
            'department',
            'employee',
            'company',
            'branch.company',
            'createdBy',
            'items.itemMaster.unit',
            'items.itemMaster.hsn',
            'items.unit',
        ]);

        $filename = str_replace(['/', '\\'], '-', $materialRequest->mr_number).'.pdf';

        $barcodeBase64 = \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($materialRequest->mr_number, 'C128', 1.5, 22);
        $barcodeHtml = '<img src="data:image/png;base64,'.$barcodeBase64.'" alt="barcode" style="height: 25px; width: 220px; display: inline-block;" /><div style="font-size: 11px; color: #555; margin-top: 4px; text-align: right;">'.$materialRequest->mr_number.'</div>';

        return $pdfExport->download(
            'material_requests.pdf',
            [
                'materialRequest' => $materialRequest,
                'barcodeHtml' => $barcodeHtml,
            ],
            $filename,
            'portrait'
        );
    }

    public function excel(MaterialRequest $materialRequest)
    {
        $materialRequest->load([
            'department',
            'employee',
            'company',
            'branch.company',
            'createdBy',
            'items.itemMaster.unit',
            'items.itemMaster.hsn',
            'items.unit',
        ]);

        $filename = str_replace(['/', '\\'], '-', $materialRequest->mr_number).'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MaterialRequestExport($materialRequest),
            $filename
        );
    }

    private function applyMaterialRequestFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('mr_number')) {
            $query->where('mr_number', 'like', '%'.$request->mr_number.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_date')) {
            $query->whereDate('request_date', $request->request_date);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employee_master_id')) {
            $query->where('employee_master_id', $request->employee_master_id);
        }

        if ($request->filled('item_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('msu_item_master_id', $request->item_id);
            });
        }

        if (session()->has('global_company_id')) {
            $globalCompanyId = session('global_company_id');
            $query->where(function ($q) use ($globalCompanyId) {
                $q->where('company_id', $globalCompanyId)
                  ->orWhere(function ($q2) use ($globalCompanyId) {
                      $q2->whereNull('company_id')
                         ->whereHas('branch', function ($q3) use ($globalCompanyId) {
                             $q3->where('company_id', $globalCompanyId);
                         });
                  });
            });
        }
    }

    private function filteredMaterialRequests(Request $request)
    {
        $query = MaterialRequest::with(['department', 'employee', 'company', 'branch.company', 'items'])->latest();
        $this->applyMaterialRequestFilters($query, $request);

        return $query;
    }

    private function filteredMaterialRequestItemsQuery(Request $request)
    {
        return MaterialRequestItem::query()
            ->with([
                'materialRequest.department',
                'materialRequest.employee',
                'materialRequest.company',
                'materialRequest.branch.company',
                'itemMaster.hsn',
                'itemMaster.unit',
                'unit',
            ])
            ->whereHas('materialRequest', function ($q) use ($request) {
                $this->applyMaterialRequestFilters($q, $request);
            })
            ->when($request->filled('item_id'), function ($q) use ($request) {
                $q->where('msu_item_master_id', $request->item_id);
            })
            ->orderByDesc('material_request_id')
            ->orderBy('id');
    }

    private function filteredMaterialRequestItems(Request $request): Collection
    {
        return $this->filteredMaterialRequestItemsQuery($request)->get();
    }

    private function filterOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
            'employees' => EmployeeMaster::query()->orderBy('employee_name')->get(['id', 'employee_name']),
            'filterItems' => MsuItemMaster::withTrashed()
                ->whereIn('id', MaterialRequestItem::query()->select('msu_item_master_id')->whereNotNull('msu_item_master_id'))
                ->orderBy('item_name')
                ->get(['id', 'item_code', 'item_name']),
        ];
    }

    private function filterBranches()
    {
        $companyId = session('global_company_id');

        return Branch::visibleToUser(auth()->user())
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();
    }

    public function create()
    {
        $companyId = \App\Support\GlobalCompanyFilter::resolveFormCompanyId();
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        $departments = Department::where('status', 'active')->get();
        $employees = EmployeeMaster::active()->get();
        $branches = Branch::visibleToUser(auth()->user())
            ->where('status', 'active')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->get();
        $items = MsuItemMaster::active()->with('unit')->get();
        $units = Unit::active()->get();
        $previewMrNumber = app(\App\Services\Procurement\DocumentNumberGenerator::class)->previewMrNumber($companyCode, 'BRN', now());
        $formCompanyId = $companyId;

        return view('material_requests.create', compact(
            'departments',
            'employees',
            'branches',
            'items',
            'units',
            'previewMrNumber',
            'formCompanyId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'employee_master_id' => 'nullable|exists:employee_masters,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.msu_item_master_id' => 'required|exists:msu_item_masters,id',
            'items.*.requested_qty' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'nullable|exists:units,id',
        ]);

        foreach ($request->items as $index => $itemData) {
            $master = \App\Models\MsuItemMaster::find($itemData['msu_item_master_id']);
            if ($master && $itemData['requested_qty'] > $master->current_stock) {
                return redirect()->back()->withInput()->withErrors(['items.' . $index . '.requested_qty' => "Requested quantity for '{$master->item_name}' cannot exceed available stock ({$master->current_stock})."]);
            }
        }

        $status = 'created';

        DB::transaction(function () use ($request, $status) {
            $branch = \App\Models\Branch::with('company')->find($request->branch_id);
            $branchCode = $branch ? ($branch->code ?: $branch->name) : 'BRN';
            $companyCode = $branch?->company?->code ?: 'MSULLP';
            $mrNumber = app(\App\Services\Procurement\DocumentNumberGenerator::class)->nextMrNumber($companyCode, $branchCode, $request->request_date);

            $mr = MaterialRequest::create([
                'mr_number' => $mrNumber,
                'request_date' => $request->request_date,
                'department_id' => $request->department_id,
                'employee_master_id' => $request->employee_master_id,
                'branch_id' => $request->branch_id,
                'company_id' => $branch ? $branch->company_id : session('global_company_id'),
                'status' => $status,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $mr->id,
                    'msu_item_master_id' => $item['msu_item_master_id'],
                    'requested_qty' => $item['requested_qty'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('material-requests.index')->with('success', 'Material Request created successfully.');
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['department', 'employee', 'branch.company', 'company', 'createdBy', 'items.itemMaster', 'items.unit']);
        return view('material_requests.show', compact('materialRequest'));
    }

    public function edit(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'created') {
            return redirect()->route('material-requests.index')->with('error', 'Only created requests can be edited.');
        }

        $materialRequest->loadMissing(['branch', 'items.unit', 'items.itemMaster']);

        $companyId = \App\Support\GlobalCompanyFilter::resolveFormCompanyId(
            $materialRequest->company_id ?? $materialRequest->branch?->company_id
        );
        $companyCode = \App\Support\GlobalCompanyFilter::companyCode($companyId);

        $departments = Department::where('status', 'active')->get();
        $employees = EmployeeMaster::active()->get();
        $branches = Branch::visibleToUser(auth()->user())
            ->where('status', 'active')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->get();
        $items = MsuItemMaster::active()->with('unit')->get();
        $existingIds = $items->pluck('id');
        $missingIds = $materialRequest->items->pluck('msu_item_master_id')->filter()->unique()->diff($existingIds);
        if ($missingIds->isNotEmpty()) {
            $items = $items->concat(
                MsuItemMaster::with('unit')->whereIn('id', $missingIds)->get()
            );
        }
        $units = Unit::active()->get();

        $selectedBranchId = old('branch_id', $materialRequest->branch_id);
        $selectedBranch = $branches->firstWhere('id', $selectedBranchId);
        $branchCode = $selectedBranch
            ? ($selectedBranch->code ?: $selectedBranch->name)
            : 'BRN';
        $requestDate = old('request_date', $materialRequest->request_date) ?: now();
        $previewMrNumber = app(\App\Services\Procurement\DocumentNumberGenerator::class)
            ->previewMrNumber($companyCode, $branchCode, $requestDate);
        $formCompanyId = $companyId;

        $mrCompanyId = $materialRequest->company_id ?? $materialRequest->branch?->company_id;
        $displayMrNumber = $materialRequest->mr_number;

        return view('material_requests.edit', compact(
            'materialRequest',
            'departments',
            'employees',
            'branches',
            'items',
            'units',
            'previewMrNumber',
            'displayMrNumber',
            'formCompanyId',
            'mrCompanyId'
        ));
    }

    public function update(Request $request, MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'created') {
            return redirect()->route('material-requests.index')->with('error', 'Only created requests can be edited.');
        }

        $request->validate([
            'request_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'employee_master_id' => 'nullable|exists:employee_masters,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:material_request_items,id',
            'items.*.msu_item_master_id' => 'required|exists:msu_item_masters,id',
            'items.*.requested_qty' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'nullable|exists:units,id',
        ]);

        foreach ($request->items as $index => $itemData) {
            $master = \App\Models\MsuItemMaster::find($itemData['msu_item_master_id']);
            if ($master && $itemData['requested_qty'] > $master->current_stock) {
                return redirect()->back()->withInput()->withErrors(['items.' . $index . '.requested_qty' => "Requested quantity for '{$master->item_name}' cannot exceed available stock ({$master->current_stock})."]);
            }
        }

        $status = 'created';

        DB::transaction(function () use ($request, $status, $materialRequest) {
            $branch = \App\Models\Branch::with('company')->find($request->branch_id);

            $materialRequest->update([
                'request_date' => $request->request_date,
                'department_id' => $request->department_id,
                'employee_master_id' => $request->employee_master_id,
                'branch_id' => $request->branch_id,
                'company_id' => $branch ? $branch->company_id : session('global_company_id'),
                'status' => $status,
            ]);

            // Delete old items and insert new
            $materialRequest->items()->delete();
            
            foreach ($request->items as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'msu_item_master_id' => $item['msu_item_master_id'],
                    'requested_qty' => $item['requested_qty'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('material-requests.index')->with('success', 'Material Request updated successfully.');
    }

    public function submit(Request $request, $id)
    {
        $mr = MaterialRequest::findOrFail($id);
        if ($mr->status !== 'created') {
            return redirect()->route('material-requests.index')->with('error', 'Only created requests can be submitted.');
        }

        $mr->status = 'approved';
        $mr->save();

        return redirect()->route('material-requests.index')->with('success', 'Material Request submitted successfully.');
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        $mr = MaterialRequest::findOrFail($id);
        if ($mr->status !== 'created') {
            return redirect()->route('material-requests.index')->with('error', 'This request cannot be cancelled.');
        }

        $mr->status = 'cancelled';
        $mr->cancel_reason = $request->cancel_reason;
        $mr->save();

        return redirect()->route('material-requests.index')->with('success', 'Material Request cancelled successfully.');
    }

    public function destroy(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'created') {
            return redirect()->back()->with('error', 'Only created requests can be deleted.');
        }

        $materialRequest->delete();
        return redirect()->route('material-requests.index')->with('success', 'Material Request deleted successfully.');
    }
}

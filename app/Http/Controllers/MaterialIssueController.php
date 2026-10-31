<?php

namespace App\Http\Controllers;

use App\Models\MaterialIssue;
use App\Models\MaterialIssueItem;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\MsuItemMaster;
use App\Models\Department;
use App\Models\EmployeeMaster;
use App\Models\Branch;
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

class MaterialIssueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:material_issues:view', only: ['index', 'show', 'export', 'exportPdf', 'pdf', 'excel']),
            new Middleware('permission:material_issues:create', only: ['create', 'store', 'getMaterialRequestItems']),
            new Middleware('permission:material_issues:edit', only: ['edit', 'update']),
            new Middleware('permission:material_issues:delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $perPage = $request->input('per_page', 10);
        $branches = $this->filterBranches();
        $departments = Department::query()->orderBy('department_name')->get(['id', 'department_name']);
        $employees = EmployeeMaster::query()->orderBy('employee_name')->get(['id', 'employee_name']);
        $filterItems = MsuItemMaster::withTrashed()
            ->whereIn('id', MaterialIssueItem::query()->select('msu_item_master_id')->whereNotNull('msu_item_master_id'))
            ->orderBy('item_name')
            ->get(['id', 'item_code', 'item_name']);

        if ($viewMode === 'item') {
            $items = $this->filteredMaterialIssueItemsQuery($request)
                ->paginate($perPage)
                ->withQueryString();

            return view('material_issues.index', compact('items', 'branches', 'departments', 'employees', 'filterItems', 'viewMode', 'perPage'));
        }

        $issues = $this->filteredMaterialIssues($request)->paginate($perPage)->withQueryString();

        return view('material_issues.index', compact('issues', 'branches', 'departments', 'employees', 'filterItems', 'viewMode', 'perPage'));
    }

    public function export(Request $request)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';

        if ($viewMode === 'item') {
            $rows = $this->materialIssueItemExportRows($request);
            $export = new class($rows) implements FromCollection, WithHeadings, WithStrictNullComparison
            {
                public function __construct(private readonly Collection $rows) {}

                public function collection()
                {
                    return $this->rows->map(fn (array $row) => array_values($row));
                }

                public function headings(): array
                {
                    return [
                        'S.No',
                        'Issue No.',
                        'Issue Date',
                        'Company',
                        'Branch Name',
                        'Issued To',
                        'Department',
                        'Material Req Nos.',
                        'Item Code',
                        'Item Name',
                        'Product Cat',
                        'Product Sub Category',
                        'Issue Qty',
                        'Unit',
                        'Price',
                        'Gross Amount',
                        'Tax (%)',
                        'Tax Price',
                        'Net Amount',
                        'Status',
                    ];
                }
            };

            return Excel::download($export, 'material-issue-items-'.now()->format('Ymd_His').'.xlsx');
        }

        $rows = $this->filteredMaterialIssues($request)->get();
        $export = new class($rows) implements FromCollection, WithHeadings
        {
            public function __construct(private readonly Collection $rows) {}

            public function collection()
            {
                return $this->rows->values()->map(fn (MaterialIssue $mi, int $index) => [
                    $index + 1,
                    $mi->materialRequest?->company?->name ?? $mi->materialRequest?->branch?->company?->name,
                    $mi->materialRequest?->branch?->name,
                    $mi->mi_number,
                    $mi->issue_date ? \Carbon\Carbon::parse($mi->issue_date)->format('d-m-Y') : '',
                    $mi->employee?->employee_name,
                    $mi->department?->department_name,
                    $mi->materialRequest?->mr_number,
                    $mi->items->count(),
                    ucfirst(str_replace('_', ' ', $mi->status)),
                ]);
            }

            public function headings(): array
            {
                return [
                    'S/No', 'Company', 'Branch', 'MI Number', 'Issue Date', 'Employee', 'Department', 'MR Number', 'Items', 'Status',
                ];
            }
        };

        return Excel::download($export, 'material-issues-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request, \App\Services\ReportPdfExportService $pdfExport)
    {
        $viewMode = $request->get('view', 'main') === 'item' ? 'item' : 'main';
        $filters = $request->only(['search', 'mi_number', 'status', 'issue_date', 'branch_id', 'department_id', 'employee_master_id', 'item_id', 'view']);

        if ($viewMode === 'item') {
            $rows = $this->materialIssueItemExportRows($request);

            return $pdfExport->download(
                'material_issues.export-items',
                compact('rows', 'filters'),
                'material-issue-items-'.now()->format('Ymd_His').'.pdf',
                'landscape'
            );
        }

        $materialIssues = $this->filteredMaterialIssues($request)->get();

        return $pdfExport->download(
            'material_issues.export-list',
            compact('materialIssues', 'filters'),
            'material-issues-'.now()->format('Ymd_His').'.pdf',
            'landscape'
        );
    }

    public function pdf(MaterialIssue $materialIssue, \App\Services\ReportPdfExportService $pdfExport)
    {
        $materialIssue->load([
            'department',
            'employee',
            'createdBy',
            'materialRequest.company',
            'materialRequest.branch.company',
            'items.itemMaster.unit',
            'items.itemMaster.hsn',
            'items.materialRequestItem.unit',
        ]);

        $filename = str_replace(['/', '\\'], '-', $materialIssue->mi_number).'.pdf';

        $barcodeBase64 = \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($materialIssue->mi_number, 'C128', 1.5, 22);
        $barcodeHtml = '<img src="data:image/png;base64,'.$barcodeBase64.'" alt="barcode" style="height: 25px; width: 220px; display: inline-block;" /><div style="font-size: 11px; color: #555; margin-top: 4px; text-align: right;">'.$materialIssue->mi_number.'</div>';

        return $pdfExport->download(
            'material_issues.pdf',
            [
                'materialIssue' => $materialIssue,
                'barcodeHtml' => $barcodeHtml,
            ],
            $filename,
            'portrait'
        );
    }

    public function excel(MaterialIssue $materialIssue)
    {
        $materialIssue->load([
            'department',
            'employee',
            'createdBy',
            'materialRequest.company',
            'materialRequest.branch.company',
            'items.itemMaster.unit',
            'items.itemMaster.hsn',
            'items.materialRequestItem.unit',
        ]);

        $filename = str_replace(['/', '\\'], '-', $materialIssue->mi_number).'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MaterialIssueExport($materialIssue),
            $filename
        );
    }

    private function applyMaterialIssueFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->whereHas('materialRequest', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        $search = trim((string) $request->input('search', ''));
        if ($search === '' && $request->filled('mi_number')) {
            $search = trim((string) $request->mi_number);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('mi_number', 'like', $like)
                    ->orWhereHas('materialRequest', function ($mr) use ($like) {
                        $mr->where('mr_number', 'like', $like);
                    });
            });
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issue_date')) {
            $query->whereDate('issue_date', $request->issue_date);
        }

        if (session()->has('global_company_id')) {
            $globalCompanyId = session('global_company_id');
            $query->whereHas('materialRequest', function ($q) use ($globalCompanyId) {
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

    private function filteredMaterialIssues(Request $request)
    {
        $query = MaterialIssue::with([
            'department',
            'employee',
            'materialRequest.company',
            'materialRequest.branch.company',
            'items',
        ])->latest();
        $this->applyMaterialIssueFilters($query, $request);

        return $query;
    }

    private function filteredMaterialIssueItemsQuery(Request $request)
    {
        return MaterialIssueItem::query()
            ->with([
                'materialIssue.department',
                'materialIssue.employee',
                'materialIssue.materialRequest.company',
                'materialIssue.materialRequest.branch.company',
                'itemMaster.hsn',
                'itemMaster.unit',
                'itemMaster.productCategory',
                'itemMaster.productSubCategory',
                'itemMaster.rates',
                'materialRequestItem.unit',
            ])
            ->whereHas('materialIssue', function ($q) use ($request) {
                $this->applyMaterialIssueFilters($q, $request);
            })
            ->when($request->filled('item_id'), function ($q) use ($request) {
                $q->where('msu_item_master_id', $request->item_id);
            })
            ->orderByDesc('material_issue_id')
            ->orderBy('id');
    }

    private function filteredMaterialIssueItems(Request $request): Collection
    {
        return $this->filteredMaterialIssueItemsQuery($request)->get();
    }

    private function materialIssueItemExportRows(Request $request): Collection
    {
        return $this->filteredMaterialIssueItems($request)->values()->map(function (MaterialIssueItem $item, int $index) {
            $mi = $item->materialIssue;
            $mr = $mi?->materialRequest;
            $master = $item->itemMaster;
            $rate = $this->applicableItemRate($master, $mi?->issue_date);
            $qty = (float) $item->issue_qty;
            $price = (float) ($rate?->rate ?? 0);
            $taxPercent = (float) ($rate?->tax ?? 0);
            $gross = round($qty * $price, 2);
            $taxPrice = round($gross * $taxPercent / 100, 2);

            return [
                'sno' => $index + 1,
                'issue_no' => $mi?->mi_number ?? '',
                'issue_date' => $mi?->issue_date ? \Carbon\Carbon::parse($mi->issue_date)->format('d-m-Y') : '',
                'company' => $mr?->company?->name ?? $mr?->branch?->company?->name ?? '',
                'branch' => $mr?->branch?->name ?? '',
                'issued_to' => $mi?->employee?->employee_name ?? '',
                'department' => $mi?->department?->department_name ?? '',
                'mr_nos' => $mr?->mr_number ?? '',
                'item_code' => $master?->item_code ?? '',
                'item_name' => $master?->item_name ?? '',
                'product_cat' => $master?->productCategory?->category_name ?? '',
                'product_sub_cat' => $master?->productSubCategory?->sub_category_name ?? '',
                'issue_qty' => $qty,
                'unit' => $item->materialRequestItem?->unit?->unit_name ?? $master?->unit?->unit_name ?? '',
                'price' => $price,
                'gross' => $gross,
                'tax_percent' => $taxPercent,
                'tax_price' => $taxPrice,
                'net' => round($gross + $taxPrice, 2),
                'status' => $mi ? ucfirst(str_replace('_', ' ', $mi->status)) : '',
            ];
        });
    }

    private function applicableItemRate(?MsuItemMaster $item, mixed $asOf): ?\App\Models\MsuItemRate
    {
        if (! $item) {
            return null;
        }

        $date = $asOf ? \Carbon\Carbon::parse($asOf)->toDateString() : now()->toDateString();

        return $item->rates->first(function (\App\Models\MsuItemRate $rate) use ($date) {
            return $rate->effective_from && $rate->effective_from->toDateString() <= $date;
        });
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

        $departments = Department::where('status', 'active')->get();
        $employees = EmployeeMaster::active()->get();
        $branches = Branch::visibleToUser(auth()->user())
            ->with('company')
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();
        $materialRequests = MaterialRequest::whereIn('status', ['approved', 'partially_issued'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->where(function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId)
                      ->orWhere(function ($q3) use ($companyId) {
                          $q3->whereNull('company_id')
                             ->whereHas('branch', function ($q4) use ($companyId) {
                                 $q4->where('company_id', $companyId);
                             });
                      });
                });
            })
            ->whereHas('branch', function ($q) {
                $q->visibleToUser(auth()->user());
            })
            ->orderByDesc('id')
            ->get();

        return view('material_issues.create', compact('departments', 'employees', 'materialRequests', 'branches'));
    }

    public function getMaterialRequestItems(Request $request, $id)
    {
        $mr = MaterialRequest::with(['items.itemMaster.hsn', 'items.unit'])->findOrFail($id);

        if ($request->filled('branch_id') && (int) $mr->branch_id !== (int) $request->input('branch_id')) {
            return response()->json([
                'items' => [],
                'message' => 'Selected Material Request does not belong to the selected branch.',
            ], 422);
        }
        
        $items = $mr->items->map(function ($item) {
            return [
                'id' => $item->id,
                'msu_item_master_id' => $item->msu_item_master_id,
                'item_name' => $item->itemMaster->item_name ?? '',
                'unit_name' => $item->unit->unit_name ?? '',
                'hsn' => $item->itemMaster->hsn->hsn_code ?? $item->itemMaster->hsn->code ?? '',
                'requested_qty' => $item->requested_qty,
                'issued_qty' => $item->issued_qty,
                'available_stock' => $item->itemMaster->current_stock ?? 0,
                'remaining_qty' => $item->remaining_qty,
            ];
        });

        return response()->json(['items' => $items, 'department_id' => $mr->department_id, 'employee_master_id' => $mr->employee_master_id]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'material_request_id' => 'required|exists:material_requests,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_master_id' => 'nullable|exists:employee_masters,id',
            'items' => 'required|array|min:1',
            'items.*.material_request_item_id' => 'required|exists:material_request_items,id',
            'items.*.issue_qty' => 'required|numeric|min:0.001',
        ]);

        DB::transaction(function () use ($request) {
            $mr = MaterialRequest::findOrFail($request->material_request_id);

            if ((int) $mr->branch_id !== (int) $request->branch_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'material_request_id' => 'Selected Material Request does not belong to the selected branch.',
                ]);
            }

            if (in_array($mr->status, ['fully_issued', 'created'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'material_request_id' => 'Invalid Material Request status for issue.'
                ]);
            }

            $mi = MaterialIssue::where('material_request_id', $mr->id)->first();
            
            if ($mi) {
                $mi->issue_date = $request->issue_date;
                $mi->remarks = $request->remarks ?? $mi->remarks;
                $mi->save();
            } else {
                $branch = \App\Models\Branch::with('company')->find($mr->branch_id);
                $branchCode = $branch ? ($branch->code ?: $branch->name) : 'BRN';
                $companyCode = $branch?->company?->code ?: 'MSULLP';
                $miNumber = app(\App\Services\Procurement\DocumentNumberGenerator::class)->nextMiNumber($companyCode, $branchCode, $request->issue_date);

                $mi = MaterialIssue::create([
                    'mi_number' => $miNumber,
                    'issue_date' => $request->issue_date,
                    'department_id' => $request->department_id,
                    'employee_master_id' => $request->employee_master_id,
                    'material_request_id' => $mr->id,
                    'status' => 'approved',
                    'created_by' => Auth::id(),
                    'remarks' => $request->remarks ?? null,
                ]);
            }

            $hasIssues = false;

            foreach ($request->items as $index => $itemData) {
                if ($itemData['issue_qty'] <= 0) continue;
                $hasIssues = true;

                $mrItem = MaterialRequestItem::lockForUpdate()->findOrFail($itemData['material_request_item_id']);
                
                if ($itemData['issue_qty'] > $mrItem->remaining_qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.issue_qty" => "Issue quantity cannot exceed remaining quantity for item."
                    ]);
                }

                $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($mrItem->msu_item_master_id);
                if (!$itemMaster) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.material_request_item_id" => "Item master not found."
                    ]);
                }

                $stockBefore = (float) $itemMaster->current_stock;
                if ($itemData['issue_qty'] > $stockBefore) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.issue_qty" => "Issue quantity cannot exceed current stock ({$stockBefore}) for item {$itemMaster->item_name}."
                    ]);
                }

                $issueItem = \App\Models\MaterialIssueItem::where('material_issue_id', $mi->id)
                    ->where('material_request_item_id', $mrItem->id)
                    ->first();
                
                if ($issueItem) {
                    $issueItem->issue_qty += $itemData['issue_qty'];
                    if (!empty($itemData['remarks'])) {
                        $issueItem->remarks = $itemData['remarks'];
                    }
                    $issueItem->save();
                } else {
                    MaterialIssueItem::create([
                        'material_issue_id' => $mi->id,
                        'material_request_item_id' => $mrItem->id,
                        'msu_item_master_id' => $mrItem->msu_item_master_id,
                        'issue_qty' => $itemData['issue_qty'],
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);
                }

                // Update MR Item
                $mrItem->issued_qty += $itemData['issue_qty'];
                $mrItem->save();

                // Deduct stock
                $stockAfter = $stockBefore - (float) $itemData['issue_qty'];
                $itemMaster->current_stock = $stockAfter;
                $itemMaster->save();

                \App\Models\MsuStockMovement::create([
                    'msu_item_master_id' => $itemMaster->id,
                    'transaction_type' => 'out',
                    'quantity' => $itemData['issue_qty'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => get_class($mi),
                    'reference_id' => $mi->id,
                    'remarks' => 'Stock out via Material Issue ' . $mi->mi_number,
                    'created_by' => Auth::id(),
                ]);
            }

            if (!$hasIssues) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Please provide issue quantity for at least one item."
                ]);
            }

            $this->updateMaterialRequestStatus($mr);
            $mi->status = $mr->status;
            $mi->save();
        });

        return redirect()->route('material-issues.index')->with('success', 'Material Issue saved successfully.');
    }

    public function show(MaterialIssue $materialIssue)
    {
        $materialIssue->load([
            'department',
            'employee',
            'materialRequest.company',
            'materialRequest.branch.company',
            'items.itemMaster',
            'items.materialRequestItem',
            'createdBy',
        ]);
        return view('material_issues.show', compact('materialIssue'));
    }

    public function edit(MaterialIssue $materialIssue)
    {
        $materialIssue->load(['items.materialRequestItem.itemMaster', 'items.materialRequestItem.unit']);
        $departments = Department::where('status', 'active')->get();
        $employees = EmployeeMaster::active()->get();
        $materialRequests = MaterialRequest::where('id', $materialIssue->material_request_id)->get();

        return view('material_issues.edit', compact('materialIssue', 'departments', 'employees', 'materialRequests'));
    }

    public function update(Request $request, MaterialIssue $materialIssue)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'employee_master_id' => 'nullable|exists:employee_masters,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:material_issue_items,id',
            'items.*.issue_qty' => 'required|numeric|min:0.001',
        ]);

        DB::transaction(function () use ($request, $materialIssue) {
            $materialIssue->update([
                'issue_date' => $request->issue_date,
                'department_id' => $request->department_id,
                'employee_master_id' => $request->employee_master_id,
                'remarks' => $request->remarks ?? null,
            ]);

            $mr = MaterialRequest::findOrFail($materialIssue->material_request_id);

            foreach ($request->items as $index => $itemData) {
                $miItem = MaterialIssueItem::findOrFail($itemData['id']);
                $mrItem = MaterialRequestItem::lockForUpdate()->findOrFail($miItem->material_request_item_id);

                // Calculate the difference
                $qtyDiff = $itemData['issue_qty'] - $miItem->issue_qty;

                // Check if the new quantity exceeds the currently available remaining quantity
                if ($qtyDiff > 0 && $qtyDiff > $mrItem->remaining_qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.issue_qty" => "Issue quantity cannot exceed remaining quantity for item."
                    ]);
                }

                $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($mrItem->msu_item_master_id);
                if (!$itemMaster) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.id" => "Item master not found."
                    ]);
                }

                $stockBefore = (float) $itemMaster->current_stock;
                if ($qtyDiff > 0 && $qtyDiff > $stockBefore) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.issue_qty" => "Additional issue quantity cannot exceed current stock ({$stockBefore}) for item {$itemMaster->item_name}."
                    ]);
                }

                // Update the MI Item
                $miItem->update([
                    'issue_qty' => $itemData['issue_qty'],
                    'remarks' => $itemData['remarks'] ?? null,
                ]);

                // Update MR Item
                $mrItem->issued_qty += $qtyDiff;
                $mrItem->save();

                // Update Stock if there's a difference
                if (abs($qtyDiff) > 0) {
                    $stockAfter = $stockBefore - $qtyDiff;
                    $itemMaster->current_stock = $stockAfter;
                    $itemMaster->save();

                    \App\Models\MsuStockMovement::create([
                        'msu_item_master_id' => $itemMaster->id,
                        'transaction_type' => $qtyDiff > 0 ? 'out' : 'in',
                        'quantity' => abs($qtyDiff),
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => get_class($materialIssue),
                        'reference_id' => $materialIssue->id,
                        'remarks' => 'Stock adjustment via Material Issue update ' . $materialIssue->mi_number,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $this->updateMaterialRequestStatus($mr);
        });

        return redirect()->route('material-issues.index')->with('success', 'Material Issue updated successfully.');
    }

    public function destroy(MaterialIssue $materialIssue)
    {
        if ($materialIssue->status === 'fully_issued') {
            return redirect()->route('material-issues.index')->with('error', 'Fully issued materials cannot be deleted.');
        }

        DB::transaction(function () use ($materialIssue) {
            $mr = MaterialRequest::findOrFail($materialIssue->material_request_id);
            
            // Revert issued quantities and return stock
            foreach ($materialIssue->items as $miItem) {
                $mrItem = MaterialRequestItem::lockForUpdate()->findOrFail($miItem->material_request_item_id);
                $mrItem->issued_qty -= $miItem->issue_qty;
                $mrItem->save();

                $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($mrItem->msu_item_master_id);
                if ($itemMaster && $miItem->issue_qty > 0) {
                    $stockBefore = (float) $itemMaster->current_stock;
                    $stockAfter = $stockBefore + (float) $miItem->issue_qty;
                    $itemMaster->current_stock = $stockAfter;
                    $itemMaster->save();

                    \App\Models\MsuStockMovement::create([
                        'msu_item_master_id' => $itemMaster->id,
                        'transaction_type' => 'in',
                        'quantity' => $miItem->issue_qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => get_class($materialIssue),
                        'reference_id' => $materialIssue->id,
                        'remarks' => 'Stock returned via Material Issue deletion ' . $materialIssue->mi_number,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $materialIssue->delete();
            $this->updateMaterialRequestStatus($mr);
        });

        return redirect()->route('material-issues.index')->with('success', 'Material Issue deleted successfully.');
    }

    public function cancel(Request $request, $id)
    {
        $mi = MaterialIssue::findOrFail($id);
        if ($mi->status === 'cancelled' || $mi->status === 'fully_issued') {
            return redirect()->route('material-issues.index')->with('error', 'This issue cannot be cancelled.');
        }

        DB::transaction(function () use ($mi) {
            $mr = MaterialRequest::findOrFail($mi->material_request_id);
            
            // Revert issued quantities and return stock
            foreach ($mi->items as $miItem) {
                $mrItem = MaterialRequestItem::lockForUpdate()->findOrFail($miItem->material_request_item_id);
                $mrItem->issued_qty -= $miItem->issue_qty;
                $mrItem->save();

                $itemMaster = \App\Models\MsuItemMaster::lockForUpdate()->find($mrItem->msu_item_master_id);
                if ($itemMaster) {
                    $stockBefore = $itemMaster->current_stock;
                    $stockAfter = $stockBefore + $miItem->issue_qty;
                    $itemMaster->current_stock = $stockAfter;
                    $itemMaster->save();

                    \App\Models\MsuStockMovement::create([
                        'msu_item_master_id' => $itemMaster->id,
                        'transaction_type' => 'in',
                        'quantity' => $miItem->issue_qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => get_class($mi),
                        'reference_id' => $mi->id,
                        'remarks' => 'Stock return via Material Issue cancellation ' . $mi->mi_number,
                        'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    ]);
                }
            }

            $mi->status = 'cancelled';
            $mi->save();

            $this->updateMaterialRequestStatus($mr);
        });

        return redirect()->route('material-issues.index')->with('success', 'Material Issue cancelled successfully.');
    }



    private function updateMaterialRequestStatus(MaterialRequest $mr)
    {
        $allFullyIssued = true;
        $mrItems = $mr->items()->get();
        foreach ($mrItems as $mrItem) {
            if (round($mrItem->remaining_qty, 3) > 0) {
                $allFullyIssued = false;
                break;
            }
        }

        $mr->status = $allFullyIssued ? 'fully_issued' : 'partially_issued';
        
        // If nothing is issued at all, revert to approved
        $totalIssued = $mrItems->sum('issued_qty');
        if ($totalIssued == 0) {
            $mr->status = 'approved';
        }

        $mr->save();
    }
}

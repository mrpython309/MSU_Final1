<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\AssetAssignment;
use App\AssetStatus;
use App\AssetCondition;
use App\Http\Requests\Assets\AssetRequest;
use App\Models\Vendor;
use App\Helpers\Files;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Department;
use App\Models\AssetCustodian;
use App\Models\Hsn;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Unit;
use App\AssetOperationalStatus;
use App\Models\AssetImport as AssetImportModel;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetExcelExport;
use App\Imports\AssetImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Services\ReportPdfExportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\Assets\AssetLabelPrinter;
use App\Services\Assets\Exceptions\AssetLabelException;
use App\Services\Assets\TsplAssetLabelBuilder;


class AssetController extends Controller
{
    private const ASSET_PER_PAGE = 50;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            app(\App\Services\Procurement\GrnService::class)
                ->syncApprovedGrnAssets(auth()->user());
        } catch (\Throwable) {
            // Never block asset listing if GRN sync fails.
        }

        if (! auth()->user()?->canManageAssets()) {
            foreach (['vendor_id', 'supplier', 'purchase_cost', 'price', 'current_value', 'invoice_amount', 'custodian_id'] as $key) {
                $request->query->remove($key);
            }
        }

        $productCategories = ProductCategory::active()->orderBy('category_name')->get(['id', 'category_name']);
        $productSubCategories = ProductSubCategory::query()
            ->where('status', 'active')
            ->orderBy('sub_category_name')
            ->get(['id', 'sub_category_name', 'product_category_id']);

        $statuses = AssetStatus::casesForFilter();

        $conditions=AssetCondition::cases();      

        $vendors=Vendor::active()->get();

        $companies=Company::visibleToUser(auth()->user())->active()->get();

        $custodians = AssetCustodian::query()
            ->orderBy('employee_name')
            ->get(['id', 'employee_name']);

           $branches = Branch::visibleToUser(auth()->user())
            ->where('status', 'active')
            ->select('id', 'name', 'company_id')
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->get();


        $assets = $this->filteredAssets($request)
            ->paginate($this->assetPerPage($request))
            ->withQueryString();

        return view('assets.index', compact(
            'productCategories',
            'productSubCategories',
            'assets',
            'conditions',
            'statuses',
            'companies',
            'branches',
            'custodians'
        ));
    }

    private function filteredAssets(Request $request): Builder
    {
        return Asset::query()
            ->visibleToUser(auth()->user())
            ->with([
                'latestAssignment.fromBranch',
                'latestAssignment.toBranch',
                'latestAssignment.fromCompany',
                'latestAssignment.toCompany',
                'latestAssignment.fromBranchLocation',
                'latestAssignment.toBranchLocation',
                'latestAssignment.employee',
                'branch',
                'location',
                'productCategory',
                'productSubCategory',
                'custodian',
                'company',
                'vendor',
                'department',
                'auditedBy:id,name',
                'msuGrnItem.grn.purchaseOrder',
            ])
->when($request->company_id, function ($q) use ($request) {
        $q->where(function ($inner) use ($request) {
            $inner->where('company_id', $request->company_id)
                // GRN assets may briefly have null company when warehouse settings are stale
                ->orWhere(function ($grnAssets) {
                    $grnAssets->whereNull('company_id')->whereNotNull('grn_item_id');
                });
        });
    })

    ->when($request->branch_id, function ($q) use ($request) {
        $q->filteredByBranch((int) $request->branch_id);
    })

    ->when($request->product_category_id, function ($q) use ($request) {
        $q->where('product_category_id', $request->product_category_id);
    })

    ->when($request->product_sub_category_id, function ($q) use ($request) {
        $q->where('product_sub_category_id', $request->product_sub_category_id);
    })

    ->when($request->custodian_id, function ($q) use ($request) {
        $q->where('custodian_id', $request->custodian_id);
    })

    ->when($request->status, function ($q) use ($request) {
        $status = (string) $request->status;
        if ($status === 'under_maintenance') {
            $q->where('operational_status', 'under_maintenance');
            return;
        }

        if ($status === AssetStatus::TRANSFERRED->value) {
            $assetsTable = $q->getModel()->getTable();
            $assignmentsTable = (new AssetAssignment)->getTable();

            $q->where(function (Builder $w) use ($assetsTable, $assignmentsTable): void {
                $w->whereIn($assetsTable . '.status', ['transferred', 'in_transit'])
                    ->orWhereExists(function ($sub) use ($assetsTable, $assignmentsTable): void {
                        $sub->selectRaw('1')
                            ->from($assignmentsTable)
                            ->whereColumn($assignmentsTable . '.asset_id', $assetsTable . '.id')
                            ->where($assignmentsTable . '.transfer_status', AssetAssignment::IN_TRANSIT)
                            ->whereRaw($assignmentsTable . '.id = (
                                SELECT MAX(aa_inner.id)
                                FROM ' . $assignmentsTable . ' AS aa_inner
                                WHERE aa_inner.asset_id = ' . $assetsTable . '.id
                            )');
                    });
            });

            return;
        }

        $q->where('status', $status);
    })

    ->when($request->condition, function ($q) use ($request) {
        $q->where('condition', $request->condition);
    })

    ->when($request->purchase_date_from, function ($q) use ($request) {
        $q->whereDate('purchase_date', '>=', $request->purchase_date_from);
    })

    ->when($request->purchase_date_to, function ($q) use ($request) {
        $q->whereDate('purchase_date', '<=', $request->purchase_date_to);
    })

    ->when($request->filled('search'), function ($q) use ($request) {
        $term = trim((string) $request->search);
        if ($term === '') {
            return;
        }

        $q->where(function (Builder $w) use ($term): void {
            $like = "%{$term}%";
            $w->where('asset_code', 'like', $like)
                ->orWhere('asset_name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('invoice_details', 'like', $like)
                ->orWhere('po_number', 'like', $like)
                ->orWhereHas('purchaseOrder', function ($po) use ($like): void {
                    $po->where('po_no', 'like', $like);
                })
                ->orWhereHas('msuGrnItem.grn', function ($grn) use ($like): void {
                    $grn->where('vendor_invoice_number', 'like', $like)
                        ->orWhereHas('purchaseOrder', function ($po) use ($like): void {
                            $po->where('po_number', 'like', $like);
                        });
                });
        });
    })

    ->orderBy('id', 'desc');
    }

    private function assetPerPage(Request $request): int
    {
        $perPage = $request->input('per_page', self::ASSET_PER_PAGE);

        if ($perPage === 'all') {
            return 1000000;
        }

        $perPage = (int) $perPage;

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : self::ASSET_PER_PAGE;
    }

    public function export(Request $request)
    {
        $this->authorizeAssetExport();
        $assets = $this->filteredAssets($request)->get();

        $rows = $assets->values()->map(function (Asset $asset, int $index) {
            return $this->assetListColumns($asset, $index + 1);
        });

        return Excel::download(
            new AssetExcelExport($rows, $this->assetListHeadings()),
            'assets-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function exportPdf(Request $request, ReportPdfExportService $pdfExport)
    {
        $this->authorizeAssetExport();
        $assets = $this->filteredAssets($request)->get();

        return $pdfExport->download(
            'assets.export-list',
            compact('assets'),
            'assets-'.now()->format('Ymd_His').'.pdf',
            'landscape',
            'A3'
        );
    }

    public function exportDetail(Asset $asset)
    {
        $this->authorizeAssetExport();
        $this->authorizeAsset($asset);
        $asset = $this->assetForDetailExport($asset);
        $rows = collect($this->assetAssignmentExportRows($asset));

        $filename = 'asset-'.preg_replace('/[^\w\-]+/', '-', (string) $asset->asset_code).'.xlsx';

        return Excel::download(
            new AssetExcelExport($rows, $this->assetAssignmentExportHeadings()),
            $filename
        );
    }

    public function exportDetailPdf(Asset $asset, ReportPdfExportService $pdfExport)
    {
        $this->authorizeAssetExport();
        $this->authorizeAsset($asset);
        $asset = $this->assetForDetailExport($asset);
        $rows = $this->assetDetailRows($asset);
        $filename = 'asset-'.preg_replace('/[^\w\-]+/', '-', (string) $asset->asset_code).'.pdf';

        return $pdfExport->download(
            'assets.export-detail',
            compact('asset', 'rows'),
            $filename,
            'portrait'
        );
    }

    public function exportHandoverPdf(Asset $asset, ReportPdfExportService $pdfExport)
    {
        $this->authorizeAssetExport();
        $this->authorizeAsset($asset);
        $asset = $this->assetForDetailExport($asset);
        $asset->loadMissing([
            'latestAssignment.employee.department',
            'latestAssignment.department',
            'latestAssignment.assignedBy',
            'custodian.department',
        ]);

        $company = $asset->company
            ?? $asset->latestAssignment?->toCompany
            ?? $asset->latestAssignment?->fromCompany;
        $filename = 'asset-handover-'.preg_replace('/[^\w\-]+/', '-', (string) $asset->asset_code).'.pdf';

        return $pdfExport->download(
            'assets.export-handover',
            [
                'asset' => $asset,
                'handoverCompany' => $company,
                'skipCompanyHeader' => true,
            ],
            $filename,
            'portrait'
        );
    }

    public function exportQrLabelPdf(Asset $asset)
    {
        $this->authorizeAsset($asset);

        $assetCode = (string) ($asset->asset_code ?? '');
        abort_if($assetCode === '', 404);

        $svg = QrCode::format('svg')
            ->size(750)
            ->margin(4)
            ->errorCorrection('M')
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->generate($assetCode);

        $html = view('assets.qr-label', [
            'assetCode' => $assetCode,
            'qrDataUri' => 'data:image/svg+xml;base64,'.base64_encode((string) $svg),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 300);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 4 * 72, 6 * 72], 'portrait');
        $dompdf->render();

        $filename = preg_replace('/[^\w\-]+/', '-', $assetCode).'-qrcode.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    public function printAssetLabel(Request $request, Asset $asset, TsplAssetLabelBuilder $labels, AssetLabelPrinter $printer)
    {
        $this->authorizeAsset($asset);

        try {
            $assetCode = $labels->prepareAssetNumber((string) ($asset->asset_code ?? ''));
        } catch (AssetLabelException $e) {
            return redirect()
                ->route('asset.show', $asset)
                ->with('error', $e->getMessage());
        }

        $maxCopies = (int) config('asset_label.max_copies', 20);
        $copies = (int) $request->input('copies', 1);
        if ($copies < 1) {
            $copies = 1;
        }
        if ($copies > $maxCopies) {
            $copies = $maxCopies;
        }

        try {
            $svg = QrCode::format('svg')
                ->size(360)
                ->margin(2)
                ->errorCorrection('M')
                ->color(0, 0, 0)
                ->backgroundColor(255, 255, 255)
                ->generate($assetCode);
        } catch (\Throwable $e) {
            Log::warning('Asset label QR preview failed', [
                'asset_id' => $asset->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('asset.show', $asset)
                ->with('error', 'The QR code for this asset could not be created.');
        }

        $directPrinter = $printer->directPrinter();
        $requestedDriver = (string) config('asset_label.driver', 'browser');

        return view('assets.print-label', [
            'asset' => $asset,
            'assetCode' => $assetCode,
            'qrDataUri' => 'data:image/svg+xml;base64,'.base64_encode((string) $svg),
            'copies' => $copies,
            'maxCopies' => $maxCopies,
            'directPrinter' => $directPrinter,
            'setupWarning' => $directPrinter === null && in_array($requestedDriver, ['network', 'windows'], true)
                ? 'Direct thermal printing is turned on, but the printer address or name is missing. The browser print dialog will be used instead.'
                : null,
        ]);
    }

    public function sendAssetLabel(Request $request, Asset $asset, TsplAssetLabelBuilder $labels, AssetLabelPrinter $printer)
    {
        $this->authorizeAsset($asset);

        $maxCopies = (int) config('asset_label.max_copies', 20);
        $requestedCopies = (int) $request->input('copies', 1);

        try {
            $assetCode = $labels->prepareAssetNumber((string) ($asset->asset_code ?? ''));
            $copies = $labels->prepareCopies($requestedCopies, $maxCopies);
            $message = $printer->send($assetCode, $copies);
        } catch (AssetLabelException $e) {
            if (trim((string) ($asset->asset_code ?? '')) === '') {
                return redirect()
                    ->route('asset.show', $asset)
                    ->with('error', $e->getMessage());
            }

            return redirect()
                ->route('asset.print-label', ['asset' => $asset, 'copies' => max(1, min($maxCopies, $requestedCopies))])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('asset.print-label', ['asset' => $asset, 'copies' => $copies])
            ->with('success', $message);
    }

    public function downloadAssetLabelCommands(Request $request, Asset $asset, TsplAssetLabelBuilder $labels)
    {
        $this->authorizeAsset($asset);

        $maxCopies = (int) config('asset_label.max_copies', 20);

        try {
            $assetCode = $labels->prepareAssetNumber((string) ($asset->asset_code ?? ''));
            $copies = $labels->prepareCopies((int) $request->input('copies', 1), $maxCopies);
            $commands = $labels->build($assetCode, $copies, $maxCopies);
        } catch (AssetLabelException $e) {
            return redirect()
                ->route('asset.show', $asset)
                ->with('error', $e->getMessage());
        }

        $filename = trim((string) preg_replace('/[^\w\-]+/', '-', $assetCode), '-').'.prn';

        return response($commands, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function assetListHeadings(): array
    {
        return [
            'S.No',
            'Asset Code',
            'Asset Description',
            'SL No / Any ID\'s',
            'Asset Structure',
            'Qty',
            'Product Categories',
            'Product Sub Categories',
            'Status',
            'Operational Status',
            'Condition',
            'Company',
            'Branch',
            'Physical Location',
            'Purchase Date',
            'Invoice Amount',
            'Supplier',
            'Depreciation Method',
            'Rate of Depreciation (%)',
            'Current Value',
            'Asset Custodian',
            'ASSET ASSIGNED DATE',
            'INVOICE COMPANY',
            'Audit By',
            'Audit On',
            'Audit Status',
        ];
    }

    public function assetListColumns(Asset $asset, int $serial): array
    {
        $branchName = $asset->branch->name
            ?? $asset->latestAssignment?->toBranch?->name
            ?? $asset->latestAssignment?->fromBranch?->name
            ?? '--';
        $locationName = $asset->location?->location_name
            ?? $asset->latestAssignment?->toBranchLocation?->location_name
            ?? $asset->latestAssignment?->fromBranchLocation?->location_name
            ?? '--';
        $companyName = $asset->company?->name
            ?? $asset->latestAssignment?->toCompany?->name
            ?? $asset->latestAssignment?->fromCompany?->name
            ?? '--';

        $structure = match ($asset->asset_structure) {
            'single' => 'Single Asset',
            'group' => 'Group Asset',
            default => '--',
        };

        $depreciation = match ($asset->depreciation_method) {
            'no_depreciation' => 'No Depreciation',
            'straight_line' => 'Straight Line',
            'reducing_balance' => 'Reducing Balance',
            default => $asset->depreciation_method
                ? ucfirst(str_replace('_', ' ', (string) $asset->depreciation_method))
                : '--',
        };

        $depreciationRate = '--';
        if ($asset->depreciation_method === 'reducing_balance') {
            $depreciationRate = rtrim(rtrim(number_format((float) $asset->salvage_value, 2, '.', ''), '0'), '.');
            if ($depreciationRate === '') {
                $depreciationRate = '0';
            }
        }

        return [
            $serial,
            $asset->asset_code ?: '--',
            $asset->asset_name ?: '--',
            (string) ($asset->serial_no ?: '--'),
            $structure,
            (string) ($asset->items_count ?: 1),
            $asset->productCategory?->category_name ?: '--',
            $asset->productSubCategory?->sub_category_name ?: '--',
            $asset->effective_status?->label() ?? '--',
            $asset->operational_status?->label() ?? '--',
            $asset->condition?->label() ?? '--',
            $companyName,
            $branchName,
            $locationName,
            optional($asset->purchase_date)->format('d M Y') ?: '--',
            number_format((float) $asset->purchase_cost, 2, '.', ''),
            $asset->vendor?->name ?: '--',
            $depreciation,
            $depreciationRate,
            number_format((float) $asset->current_value, 2, '.', ''),
            $asset->custodian?->employee_name ?: '--',
            $asset->latestAssignment?->assign_date?->format('d-M-y') ?: '--',
            $asset->latestAssignment?->fromCompany?->name ?: '--',
            $asset->auditByLabel(),
            $asset->auditOnLabel(),
            $asset->auditStatusLabel(),
        ];
    }

    public function assetAssignmentExportHeadings(): array
    {
        return $this->assetListHeadings();
    }

    /**
     * One Excel row per assignment. INVOICE COMPANY is the assignment From company.
     *
     * @return array<int, array<int, string>>
     */
    private function assetAssignmentExportRows(Asset $asset): array
    {
        $assignments = $asset->assignments()
            ->visibleToUser(auth()->user())
            ->with([
                'fromCompany',
                'toCompany',
                'fromBranch',
                'toBranch',
                'fromBranchLocation',
                'toBranchLocation',
                'employee',
            ])
            ->get();

        if ($assignments->isEmpty()) {
            return [$this->assetAssignmentExportColumns($asset, null, 1)];
        }

        return $assignments->values()->map(function (AssetAssignment $assignment, int $index) use ($asset) {
            return $this->assetAssignmentExportColumns($asset, $assignment, $index + 1);
        })->all();
    }

    /**
     * @return array<int, string>
     */
    private function assetAssignmentExportColumns(Asset $asset, ?AssetAssignment $assignment, int $serial): array
    {
        $columns = array_combine($this->assetListHeadings(), $this->assetListColumns($asset, $serial));

        if ($assignment) {
            $columns['Company'] = $assignment->toCompany?->name
                ?? $assignment->fromCompany?->name
                ?? '--';
            $columns['Branch'] = $assignment->toBranch?->name
                ?? $assignment->fromBranch?->name
                ?? '--';
            $columns['Physical Location'] = $assignment->toBranchLocation?->location_name
                ?? $assignment->fromBranchLocation?->location_name
                ?? '--';
            $columns['Asset Custodian'] = $assignment->employee?->employee_name ?: '--';
        }

        $columns['ASSET ASSIGNED DATE'] = $assignment?->assign_date?->format('d-M-y') ?: '--';
        $columns['INVOICE COMPANY'] = $assignment?->fromCompany?->name ?: '--';

        return array_values($columns);
    }

    private function authorizeAsset(Asset $asset): void
    {
        abort_unless(
            Asset::query()->whereKey($asset->id)->visibleToUser(auth()->user())->exists(),
            403,
            'You are not authorized to view this asset.'
        );
    }

    private function authorizeAssetExport(): void
    {
        abort_unless(auth()->user()?->canManageAssets(), 403, 'Unauthorized action.');
    }

    private function assetForDetailExport(Asset $asset): Asset
    {
        $asset->loadMissing([
            'latestAssignment.fromCompany',
            'latestAssignment.toCompany',
            'latestAssignment.fromBranch',
            'latestAssignment.toBranch',
            'latestAssignment.fromBranchLocation',
            'latestAssignment.toBranchLocation',
            'company',
            'branch',
            'location',
            'productCategory',
            'productSubCategory',
            'custodian',
            'auditedBy:id,name',
            'vendor',
            'department',
            'msuGrnItem.itemMaster.unit',
            'msuGrnItem.itemMaster.productCategory',
            'msuGrnItem.itemMaster.productSubCategory',
            'msuGrnItem.grn.purchaseOrder',
        ]);

        return $asset;
    }

    private function assetDetailRows(Asset $asset): array
    {
        $plain = fn ($value) => trim(strip_tags((string) $value)) ?: '--';

        return [
            ['Asset Description', $asset->asset_name ?: '--'],
            ['SL No / Any ID\'s', $asset->serial_no ?: '--'],
            ['Asset Code', $asset->asset_code ?: '--'],
            ['Asset Structure', $asset->asset_structure ? ucfirst($asset->asset_structure) : '--'],
            ['Qty', (string) ($asset->items_count ?: 1)],
            ['UOM', $asset->uom ?: ($asset->msuGrnItem?->itemMaster?->unit?->unit_name ?: ($asset->msuGrnItem?->itemMaster?->uom ?: '--'))],
            ['HSN Code', $asset->hsn_code ?: ($asset->msuGrnItem?->hsn ?: '--')],
            ['Status', $asset->effective_status?->label() ?? '--'],
            ['Operational Status', $asset->operational_status?->label() ?? '--'],
            ['Condition', $asset->condition?->label() ?? '--'],
            ['Product Categories', $asset->productCategory?->category_name ?? $asset->msuGrnItem?->itemMaster?->productCategory?->category_name ?? '--'],
            ['Product Sub Categories', $asset->productSubCategory?->sub_category_name ?? $asset->msuGrnItem?->itemMaster?->productSubCategory?->sub_category_name ?? '--'],
            ['Company', $asset->company?->name ?? $asset->latestAssignment?->toCompany?->name ?? $asset->latestAssignment?->fromCompany?->name ?? '--'],
            ['Branch', $asset->branch?->name ?? $asset->latestAssignment?->toBranch?->name ?? $asset->latestAssignment?->fromBranch?->name ?? '--'],
            ['Physical Location', $asset->location?->location_name ?? $asset->latestAssignment?->toBranchLocation?->location_name ?? $asset->latestAssignment?->fromBranchLocation?->location_name ?? '--'],
            ['Purchase Date', optional($asset->purchase_date)->format('d M Y') ?: '--'],
            ['Invoice Amount', number_format((float) $asset->purchase_cost, 2, '.', '')],
            ['Supplier', $asset->vendor?->name ?: '--'],
            ['Invoice No', $asset->invoice_details ?: ($asset->msuGrnItem?->grn?->vendor_invoice_number ?: '--')],
            ['Invoice Date', optional($asset->invoice_date ?: $asset->msuGrnItem?->grn?->vendor_invoice_date)->format('d M Y') ?: '--'],
            ['PO Number', $asset->po_number ?: ($asset->msuGrnItem?->grn?->purchaseOrder?->po_number ?: '--')],
            ['PO Date', optional($asset->po_date ?: $asset->msuGrnItem?->grn?->purchaseOrder?->po_date)->format('d M Y') ?: '--'],
            ['Type of Purchase', $asset->purchaseTypeLabel()],
            ['Asset Type', $asset->ownershipTypeLabel()],
            ['Cost Centre', $asset->cost_centre ?: '--'],
            ['Department', $asset->department?->department_name ?: '--'],
            ['Asset Custodian', $asset->custodian?->employee_name ?: '--'],
            ['Notes', $plain($asset->description)],
            ['Audit By', $asset->auditByLabel()],
            ['Audit On', $asset->auditOnLabel()],
            ['Audit Status', $asset->auditStatusLabel()],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = AssetStatus::casesForFilter();

        $conditions=AssetCondition::cases();

        $operations_status=AssetOperationalStatus::cases();

        $companies=Company::visibleToUser(auth()->user())->active()->get(); 

         $vendors=Vendor::active()->get();

         $productCategories = ProductCategory::active()->orderBy('category_name')->get();

        $duplicateSavedMessage = session()->pull('asset_duplicate_saved');
        $isDuplicateReturn = $duplicateSavedMessage !== null;
        $repopulateForm = $isDuplicateReturn || session()->has('errors');

        if (! $repopulateForm) {
            session()->forget('_old_input');
        }

        $assetCode = $isDuplicateReturn
            ? (string) session()->pull('nextAssetCode', '')
            : '';

        $warehouseCompanyId = (int) setting('warehouse_company_id', 0);
        $warehouseBranchId = (int) setting('warehouse_id', 0);
        $warehouseLocationId = setting('warehouse_location');

        $units = Unit::active()->orderBy('unit_name')->get();
        $departments = Department::query()->orderBy('department_name')->get();
        $custodians = AssetCustodian::active()
            ->orderBy('employee_name')
            ->get(['id', 'employee_name']);
        $hsns = Hsn::query()->active()->orderBy('hsn_code')->get(['id', 'hsn_code', 'description']);
        $costCentres = CostCentre::query()->active()->orderBy('cost_centre_name')->get();

        return view('assets.create', compact(
            'operations_status',
            'productCategories',
            'statuses',
            'conditions',
            'vendors',
            'companies',
            'assetCode',
            'warehouseCompanyId',
            'warehouseBranchId',
            'warehouseLocationId',
            'isDuplicateReturn',
            'duplicateSavedMessage',
            'repopulateForm',
            'units',
            'departments',
            'custodians',
            'hsns',
            'costCentres'
        ));
    }

    public function previewAssetCode(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        try {
            $code = Asset::generateAssetCode((int) $validated['company_id'], (int) $validated['branch_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['asset_code' => $code]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetRequest $request)
    {

        $data = $request->validated();

        $statusValue = $data['status'] instanceof AssetStatus
            ? $data['status']->value
            : (string) $data['status'];

        $assignmentPayload = null;

        if ($statusValue === AssetStatus::ASSIGNED->value) {
            $assignmentPayload = [
                'assign_date' => now()->toDateString(),
                'from_company_id' => (int) $data['company_id'],
                'from_branch' => (int) $data['branch_id'],
                'from_location' => $data['location_id'] ?? null,
                'to_company_id' => (int) $data['to_company_id'],
                'to_branch' => (int) $data['to_branch'],
                'to_location' => $data['to_location'] ?? null,
                'transfer_status' => AssetAssignment::IN_TRANSIT,
                'assigned_to' => ! empty($data['to_custodian_id']) ? 'employee' : 'branch',
                'assiged_emoloyee' => ! empty($data['to_custodian_id']) ? (int) $data['to_custodian_id'] : null,
            ];

            unset($data['to_company_id'], $data['to_branch'], $data['to_location'], $data['to_custodian_id']);
            $data['status'] = AssetStatus::AVAILABLE->value;
        }

        if ($request->hasFile('filename') && $request->file('filename')->isValid()) {

            $data['filename'] = Files::uploadLocalOrS3($request->filename, Asset::FILE_PATH);
        }

        try {
            $data['asset_code'] = Asset::generateAssetCode((int) $data['company_id'], (int) $data['branch_id']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'asset_code' => $e->getMessage(),
            ]);
        }

        $asset = Asset::create($data);

        if ($assignmentPayload !== null) {
            $asset->assignments()->create($assignmentPayload);
        }

        if ($request->has('save_and_duplicate')) {
            $flashInput = $request->except([
                '_token',
                'filename',
                'save_and_duplicate',
                'asset_code',
            ]);
            $flashInput['status'] = (string) $request->input('status', $statusValue);

            return redirect()
                ->route('asset.create')
                ->withInput($flashInput)
                ->with('asset_duplicate_saved', 'Asset saved. Form kept as-is — only asset code was updated.')
                ->with('nextAssetCode', Asset::generateAssetCode((int) $data['company_id'], (int) $data['branch_id']));
        }

        return redirect()
            ->route('asset.index')
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset,Request $request)
    {
        abort_unless(
            Asset::query()->whereKey($asset->id)->visibleToUser(auth()->user())->exists(),
            403,
            'You are not authorized to view this asset.'
        );

        $asset->loadMissing([
            'latestAssignment.fromCompany',
            'latestAssignment.toCompany',
            'latestAssignment.fromBranch',
            'latestAssignment.toBranch',
            'latestAssignment.fromBranchLocation',
            'latestAssignment.toBranchLocation',
            'grnItem.grn',
            'grnItem.poItem',
            'purchaseOrder.purchaseRequisition.indent.department',
            'purchaseOrder.vendor',
            'indent.department',
            'department',
            'vendor',
            'brand',
            'category',
            'subCategory',
            'productCategory',
            'productSubCategory',
            'msuGrnItem.itemMaster.productCategory',
            'msuGrnItem.itemMaster.productSubCategory',
            'msuGrnItem.itemMaster.unit',
            'msuGrnItem.grn.purchaseOrder',
            'custodian',
            'auditedBy:id,name',
            'audits.auditor:id,name',
            'audits.location',
            'audits.branch:id,name',
            'audits.company:id,name',
            'assetType',
            'company',
            'branch',
            'location',
            'maintenance.vendor',
            'documents',
        ]);

        $tab=$request->tab ?? 'assignments';

        $scrollToTabSection = session()->pull('scroll_asset_section');

        $assignments = AssetAssignment::visibleToUser(auth()->user())
            ->with([
                'asset',
                'fromCompany',
                'toCompany',
                'fromBranch',
                'toBranch',
                'fromBranchLocation',
                'toBranchLocation',
                'employee.department',
                'department',
                'assignedBy:id,name',
                'acceptedBy:id,name',
                'rejectedBy:id,name',
                'returnedBy:id,name',
                'returnAcceptedBy:id,name',
            ])
            ->Where('asset_id', $asset->id)
            ->latest()
            ->get();

        $locations = $asset->branch_id
            ? BranchLocation::where('branch_id', $asset->branch_id)->where('status', 'active')->get()
            : collect();

        return view('assets.show',compact('asset','tab','assignments','scrollToTabSection','locations'));
    }

    /**
     * Record a new audit entry from the web application.
     */
    public function recordAuditWeb(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'audit_status' => ['required', 'string', 'in:audited,not_found,audited,found'],
            'audited_at' => ['required', 'date'],
            'location_status' => ['nullable', 'string', 'max:50'],
            'location_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $asset->recordAudit((int) auth()->id(), $validated['audit_status'], [
            'source' => 'web',
            'audited_at' => $validated['audited_at'],
            'location_status' => $validated['location_status'] ?? 'matches',
            'location_id' => $validated['location_id'] ?? $asset->location_id,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect_to_asset_show_section($asset, 'audits')
            ->with('success', 'Asset audit recorded successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Asset $asset)
    {
        if (! $asset->isEditable()) {
            return redirect()
                ->route('asset.show', $asset)
                ->with('error', 'This asset cannot be edited once it has been assigned.');
        }

        $statuses = AssetStatus::casesForFilter();

        $conditions=AssetCondition::cases();

        $companies=Company::visibleToUser(auth()->user())->active()->get(); 

        $branches=Branch::visibleToUser(auth()->user())->Where('company_id',$asset->company_id)->active()->get();

        $locations=BranchLocation::Where('branch_id', $asset->branch_id)->where('status', 'active')->get();

            $operations_status=AssetOperationalStatus::cases();

        $vendors=Vendor::active()->get();

        $productCategories = ProductCategory::active()->orderBy('category_name')->get();

        $asset->loadMissing([
            'msuGrnItem.itemMaster.unit',
            'msuGrnItem.itemMaster.productCategory',
            'msuGrnItem.itemMaster.productSubCategory',
            'msuGrnItem.grn.purchaseOrder',
            'custodian',
            'productCategory',
        ]);

        $units = Unit::active()->orderBy('unit_name')->get();
        $departments = Department::query()->orderBy('department_name')->get();
        $custodians = AssetCustodian::active()
            ->orderBy('employee_name')
            ->get(['id', 'employee_name']);
        $hsns = Hsn::query()->active()->orderBy('hsn_code')->get(['id', 'hsn_code', 'description']);
        $costCentres = CostCentre::query()->active()->orderBy('cost_centre_name')->get();
        if ($asset->cost_centre && ! $costCentres->contains('cost_centre_name', $asset->cost_centre)) {
            $existingCostCentre = CostCentre::query()->where('cost_centre_name', $asset->cost_centre)->first();
            if ($existingCostCentre) {
                $costCentres->push($existingCostCentre);
            }
        }

        $currentHsn = $asset->hsn_code ?: $asset->msuGrnItem?->hsn;
        if ($currentHsn && ! $hsns->contains(fn ($hsn) => (string) $hsn->hsn_code === (string) $currentHsn)) {
            $matchedHsn = Hsn::query()->where('hsn_code', $currentHsn)->first(['id', 'hsn_code', 'description']);
            if ($matchedHsn) {
                $hsns->push($matchedHsn);
            }
        }

        if ($asset->custodian_id && ! $custodians->contains('id', $asset->custodian_id) && $asset->custodian) {
            $custodians->push($asset->custodian);
        }

        if ($asset->product_category_id && ! $productCategories->contains('id', $asset->product_category_id) && $asset->productCategory) {
            $productCategories->push($asset->productCategory);
        }

        $assignment = $asset->assignments()->latest('id')->first();

        return view('assets.edit',compact('operations_status','companies','productCategories','asset','statuses','conditions','vendors','branches','locations','units','departments','custodians','hsns','assignment','costCentres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetRequest $request, Asset $asset)
    {
        if (! $asset->isEditable()) {
            return redirect()
                ->route('asset.show', $asset)
                ->with('error', 'This asset cannot be edited once it has been assigned.');
        }

         $data=$request->validated();
         unset($data['asset_code']);

         if($request->hasFile('filename') && $request->file('filename')->isValid()){    

            $data['filename'] = Files::uploadLocalOrS3($request->filename,  Asset::FILE_PATH);
        }   

        $asset->update($data);

        return redirect()
            ->route('asset.index')
            ->with('success', 'Asset updated successfully.');
         
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset)
    {

        if ($asset->hasDependencies()) {

            return redirect()
            ->route('asset.index')
            ->with('error', 'Asset has dependencies and cannot be deleted.');
        }

        $asset->delete();
        return redirect()
            ->route('asset.index')
            ->with('success', 'Asset deleted successfully.');
    }

    public function depreciation(Request $request)
    {
        $filters = $this->depreciationFilterOptions($request);

        $assets = $this->filteredDepreciationAssets($request)
            ->paginate($this->assetPerPage($request))
            ->withQueryString();

        return view('assets.depreciation', array_merge($filters, compact('assets')));
    }

    public function exportDepreciation(Request $request)
    {
        $assets = $this->filteredDepreciationAssets($request)->get();

        $export = new class($assets) implements FromCollection, WithHeadings
        {
            public function __construct(private readonly Collection $assets) {}

            public function collection()
            {
                return $this->assets->values()->map(function (Asset $asset, int $index) {
                    return app(AssetController::class)->depreciationListColumns($asset, $index + 1);
                });
            }

            public function headings(): array
            {
                return app(AssetController::class)->depreciationListHeadings();
            }
        };

        return Excel::download($export, 'asset-depreciation-'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportDepreciationPdf(Request $request, ReportPdfExportService $pdfExport)
    {
        $assets = $this->filteredDepreciationAssets($request)->get();

        return $pdfExport->download(
            'assets.depreciation-export',
            compact('assets'),
            'asset-depreciation-'.now()->format('Ymd_His').'.pdf',
            'landscape',
            'A3'
        );
    }

    public function depreciationListHeadings(): array
    {
        return [
            'S.No',
            'Asset',
            'Code',
            'Product Category',
            'Product Sub Category',
            'Asset Custodian',
            'Method',
            'Purchase Date',
            'Invoice Amount',
            'Current Value',
        ];
    }

    public function depreciationListColumns(Asset $asset, int $serial): array
    {
        $method = match ($asset->depreciation_method) {
            'no_depreciation' => 'No Depreciation',
            'straight_line' => 'Straight Line',
            'reducing_balance' => 'Reducing Balance',
            default => $asset->depreciation_method
                ? ucfirst(str_replace('_', ' ', (string) $asset->depreciation_method))
                : '--',
        };

        return [
            $serial,
            $asset->asset_name ?: '--',
            $asset->asset_code ?: '--',
            $asset->productCategory?->category_name ?: '--',
            $asset->productSubCategory?->sub_category_name ?: '--',
            $asset->custodian?->employee_name ?: ($asset->latestAssignment?->employee?->name ?: '--'),
            $method,
            optional($asset->purchase_date)->format('d M Y') ?: '--',
            number_format((float) $asset->purchase_cost, 2, '.', ''),
            number_format((float) $asset->current_value, 2, '.', ''),
        ];
    }

    private function depreciationFilterOptions(Request $request): array
    {
        $companies = Company::visibleToUser(auth()->user())->active()->get();

        $branches = Branch::visibleToUser(auth()->user())
            ->active()
            ->select('id', 'name', 'company_id')
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->get();

        $productCategories = ProductCategory::active()->orderBy('category_name')->get(['id', 'category_name']);
        $productSubCategories = ProductSubCategory::query()
            ->where('status', 'active')
            ->orderBy('sub_category_name')
            ->get(['id', 'sub_category_name', 'product_category_id']);

        $custodians = AssetCustodian::query()
            ->orderBy('employee_name')
            ->get(['id', 'employee_name']);

        return compact(
            'companies',
            'branches',
            'productCategories',
            'productSubCategories',
            'custodians'
        );
    }

    private function filteredDepreciationAssets(Request $request): Builder
    {
        return Asset::query()
            ->when($request->company_id, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('company_id', $request->company_id)
                        ->orWhere(function ($grnAssets) {
                            $grnAssets->whereNull('company_id')->whereNotNull('grn_item_id');
                        });
                });
            })
            ->when($request->branch_id, fn ($q) => $q->filteredByBranch((int) $request->branch_id))
            ->when($request->product_category_id, fn ($q) => $q->where('product_category_id', $request->product_category_id))
            ->when($request->product_sub_category_id, fn ($q) => $q->where('product_sub_category_id', $request->product_sub_category_id))
            ->when($request->custodian_id, fn ($q) => $q->where('custodian_id', $request->custodian_id))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = trim((string) $request->search);
                if ($term === '') {
                    return;
                }

                $q->where(function ($w) use ($term): void {
                    $w->where('asset_code', 'like', "%{$term}%")
                        ->orWhere('asset_name', 'like', "%{$term}%");
                });
            })
            ->when($request->depreciation_method, fn ($q) => $q->where('depreciation_method', $request->depreciation_method))
            ->when($request->from, fn ($q) => $q->whereDate('purchase_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('purchase_date', '<=', $request->to))
            ->when(
                ! $request->branch_id || ! Asset::isWarehouseStoreBranch((int) $request->branch_id),
                fn ($q) => $q->where('status', 'assigned')
            )
            ->with([
                'branch',
                'productCategory',
                'productSubCategory',
                'custodian',
                'latestAssignment.employee',
            ])
            ->latest();
    }


    public function import(){

        return view('assets.import');
    }

    public function importAsset(Request $request){

        $request->validate([
        'assetfile' => 'required|mimes:xlsx,csv'
         ]);

         AssetImportModel::truncate();

         Excel::import(new AssetImport, $request->file('assetfile'));

         return redirect()->route('asset.import.preview')->with('success', 'Asset imported successfully.');

         
    }

    public function importPreview(){

        $imports=AssetImportModel::WhereNULL('processed_at')->paginate(50);

        $validAsset=AssetImportModel::WhereNULL('processed_at')->where('is_valid', 1)->count();

        $invalidAsset=AssetImportModel::WhereNULL('processed_at')->where('is_valid', 0)->count();

        return view('assets.importpreview',compact('imports','validAsset','invalidAsset'));
    }


    public function moveValidData(){

         DB::beginTransaction();

        $wareHouseCompanyId=setting('warehouse_company_id',1);

        $wareHouseId=setting('warehouse_id',1);

        $wareHouseLocationId=setting('warehouse_location',1);

        
           try {

             $imports = AssetImportModel::where('is_valid', 1)
                ->whereNull('processed_at')
                ->get();

            if ($imports->isEmpty()) {
                return back()->with('error', 'No valid data to import');
            }

            // Existing serials
            $existingSerials = array_flip(
                Asset::pluck('serial_no')
                    ->map(fn($s) => strtolower(trim($s)))
                    ->toArray()
            );


            $insertData=[];

            // Existing asset codes
            $existingAssetCodes = array_flip(
                Asset::pluck('asset_code')
                    ->map(fn($s) => strtolower(trim($s)))
                    ->toArray()
            );

            foreach ($imports as $import) {

                $data = json_decode($import->data, true);

                if (!$data) {
                    continue;
                }

                $serial = strtolower(trim($data['serial_no'] ?? ''));
                $assetCode = trim($data['asset_code'] ?? '');

                // Skip duplicate serial or missing/duplicate asset code
                if (!$serial || isset($existingSerials[$serial]) || !$assetCode || isset($existingAssetCodes[strtolower($assetCode)])) {
                    continue;
                }

                // Add to tracking to prevent duplicates within this batch
                $existingAssetCodes[strtolower($assetCode)] = true;

                $insertData[] = [
                   'asset_code' => $assetCode,
                   'asset_name' => $data['asset_name'] ?? '',
                   'serial_no' => $data['serial_no'] ?? '',
                   'asset_structure' => $data['asset_structure'] ?? '',
                   'items_count' => $data['items_count'] ?? '',
                   'category_id' => $data['category_id'] ?? '',
                   'sub_category_id' => $data['sub_category_id'] ?? '',
                   'asset_type_id' => $data['asset_type_id'] ?? '',
                   'operational_status' => $data['operational_status'] ?? '',  
                   'condition' => $data['condition'] ?? '',  
                   'status' => 'available',
                   'company_id'=> $wareHouseCompanyId,
                   'branch_id'=> $wareHouseId,
                   'location_id'=> $wareHouseLocationId,                   
                   'purchase_date' => $data['purchase_date'] ?? '', 
                   'purchase_cost' => $data['purchase_cost'] ?? '',       
                   'supplier' => $data['vendor_id'] ?? '',  
                   'brand_id' => $data['brand_id'] ?? NULL,  
                   'invoice_details' => $data['invoice_details_original'] ?? '',  
                   'depreciation_method' => $data['depreciation_method'] ?? '',  
                   'salvage_value' => $data['depreciation_rate'] ?? 0,   
                   'description' => $data['description'] ?? '',  
                   'created_by' => auth()->user()->id
                ];

                $import->processed_at = now();
                $import->save();

            }


             foreach (array_chunk($insertData, 500) as $chunk) {

                 Asset::insert($chunk);
             }

           

            DB::commit();

             return redirect()->route('asset.index')->with('success', 'Asset imported successfully.');

           } catch (\Throwable $e) {
               DB::rollBack();

               return back()->with('error', $e->getMessage());
           }

       
    }


    public function generateAssetCode(){

        Asset::whereNull('asset_code')
            ->orderBy('id')
            ->chunkById(1000, function ($assets) {

                if ($assets->isEmpty()) {
                    return;
                }

                foreach ($assets as $asset) {

                    $assetCode = Asset::generateAssetCode((int) $asset->company_id, (int) $asset->branch_id);

                    while (Asset::where('asset_code', $assetCode)->exists()) {
                        $assetCode = Asset::generateAssetCode((int) $asset->company_id, (int) $asset->branch_id);
                    }

                    $asset->asset_code = $assetCode;
                    if (preg_match('#^(.*)/(\d+)$#', $assetCode, $matches)) {
                        $asset->asset_prefix = $matches[1];
                        $asset->asset_nos = (int) $matches[2];
                    }
                    $asset->save();
                }

            });
        
    }




}

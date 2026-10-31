<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditApiController extends Controller
{
    public function formatAssetForMobile(Asset $asset): array
    {
        $lastAudit = $asset->audits()->latest('id')->first();

        $categoryName = $asset->category?->category_name ?? '--';
        $subCategoryName = $asset->subCategory?->sub_category_name ?? '--';

        $formattedHistory = $asset->audits->sortByDesc('id')->map(function ($h) {
            $auditedDate = $h->audited_at ?? $h->created_at;
            return [
                'id' => $h->id,
                'asset_id' => $h->asset_id,
                'audit_status' => $h->auditStatusLabel(),
                'status' => $h->auditStatusLabel(),
                'auditor' => $h->auditorName(),
                'auditor_name' => $h->auditorName(),
                'audited_by' => $h->auditorName(),
                'location_status' => $h->location_status ?? 'Matches',
                'remarks' => $h->remarks ?? '-',
                'date' => $auditedDate ? Carbon::parse($auditedDate)->format('d M Y') : '-',
                'time' => $auditedDate ? Carbon::parse($auditedDate)->format('h:i A') : '-',
                'date_time' => $auditedDate ? Carbon::parse($auditedDate)->format('d M Y, h:i A') : '-',
                'audited_at' => $auditedDate ? Carbon::parse($auditedDate)->toIso8601String() : null,
            ];
        })->values()->all();

        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code ?? '',
            'asset_name' => $asset->asset_name ?? '',
            'operational_status' => $asset->operational_status ?? 'Working',
            'operational_status_label' => $asset->operational_status_label ?? 'Working',
            'audit_status' => $asset->auditStatusLabel(),
            'company' => $asset->company?->company_name ?? '--',
            'branch' => $asset->branch?->name ?? '--',
            'department' => $asset->department?->department_name ?? '--',
            'physical_location' => $asset->location?->location_name ?? '--',
            'asset_custodian' => $asset->custodian?->name ?? '--',
            'asset_structure' => $asset->asset_structure ?? 'Single',
            'asset_type' => $asset->assetType?->name ?? 'Purchased',
            'condition' => $asset->condition ?? 'Good',
            'category_sub_category' => "{$categoryName} / {$subCategoryName}",
            'serial_number' => (string) ($asset->serial_no ?? '--'),
            'model_no' => (string) ($asset->model_no ?? '--'),
            'brand' => $asset->brand?->brand_name ?? '--',
            'category' => $categoryName,
            'sub_category' => $subCategoryName,
            'purchase_cost' => (string) ($asset->purchase_cost ?? '--'),
            'purchase_date' => $asset->purchase_date ? Carbon::parse($asset->purchase_date)->format('d M Y') : '--',
            'last_audited_at' => $asset->audited_at ? Carbon::parse($asset->audited_at)->format('d M Y, h:i A') : null,
            'last_auditor' => $asset->auditByLabel(),
            'last_remarks' => $lastAudit?->remarks ?? null,
            'last_location_status' => $lastAudit?->location_status ?? null,
            'history' => $formattedHistory,
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $query = Asset::with([
            'company',
            'branch',
            'location',
            'department',
            'custodian',
            'category',
            'subCategory',
            'brand',
            'assetType',
            'audits',
        ]);

        $allCount = Asset::count();
        $pendingCount = Asset::where(function ($q) {
            $q->whereNull('audit_status')
              ->orWhere('audit_status', 'Pending Audit')
              ->orWhere('audit_status', 'pending')
              ->orWhere('audit_status', '');
        })->count();

        $auditedCount = Asset::where(function ($q) {
            $q->where('audit_status', 'audited')
              ->orWhere('audit_status', 'Audited');
        })->count();

        $notFoundCount = Asset::where(function ($q) {
            $q->where('audit_status', 'not_found')
              ->orWhere('audit_status', 'Not Found');
        })->count();

        $status = $request->query('status');
        if (!empty($status) && strtolower($status) !== 'all') {
            $normalizedStatus = strtolower($status);
            if ($normalizedStatus === 'pending audit' || $normalizedStatus === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('audit_status')
                      ->orWhere('audit_status', 'Pending Audit')
                      ->orWhere('audit_status', 'pending')
                      ->orWhere('audit_status', '');
                });
            } elseif ($normalizedStatus === 'audited') {
                $query->where(function ($q) {
                    $q->where('audit_status', 'audited')
                      ->orWhere('audit_status', 'Audited');
                });
            } elseif ($normalizedStatus === 'not found' || $normalizedStatus === 'not_found') {
                $query->where(function ($q) {
                    $q->where('audit_status', 'not_found')
                      ->orWhere('audit_status', 'Not Found');
                });
            }
        }

        $search = $request->query('search');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('asset_name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        }

        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $paginated = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginated->items())->map(function ($asset) {
            return $this->formatAssetForMobile($asset);
        });

        return response()->json([
            'status' => true,
            'message' => 'Audit list retrieved successfully',
            'counts' => [
                'all' => $allCount,
                'pending_audit' => $pendingCount,
                'audited' => $auditedCount,
                'not_found' => $notFoundCount,
            ],
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
            'data' => $data,
        ], 200);
    }

    public function getStats(): JsonResponse
    {
        $pending = Asset::where(function ($q) {
            $q->whereNull('audit_status')
              ->orWhere('audit_status', 'Pending Audit')
              ->orWhere('audit_status', 'pending')
              ->orWhere('audit_status', '');
        })->count();

        $audited = Asset::where('audit_status', 'audited')
            ->orWhere('audit_status', 'Audited')
            ->count();

        $notFound = Asset::where('audit_status', 'not_found')
            ->orWhere('audit_status', 'Not Found')
            ->count();

        $total = Asset::count();

        return response()->json([
            'status' => true,
            'message' => 'Stats retrieved successfully',
            'data' => [
                'pending_audit' => $pending,
                'audited' => $audited,
                'not_found' => $notFound,
                'total' => $total,
            ],
        ], 200);
    }

    public function getAssetDetails(string|int $id): JsonResponse
    {
        $asset = Asset::with([
            'company',
            'branch',
            'location',
            'department',
            'custodian',
            'category',
            'subCategory',
            'brand',
            'assetType',
            'audits',
        ])->where('id', $id)->orWhere('asset_code', $id)->first();

        if (!$asset) {
            return response()->json([
                'status' => false,
                'message' => 'Asset not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Asset details retrieved successfully',
            'data' => $this->formatAssetForMobile($asset),
        ], 200);
    }

    public function lookup(Request $request): JsonResponse
    {
        $rawCode = trim((string) $request->query('code', ''));

        if (empty($rawCode)) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide an asset code parameter.',
            ], 400);
        }

        $code = $this->extractAssetCode($rawCode);

        $asset = Asset::with([
            'company',
            'branch',
            'location',
            'department',
            'custodian',
            'category',
            'subCategory',
            'brand',
            'assetType',
            'audits',
        ])->where('asset_code', $code)
          ->orWhereRaw('UPPER(asset_code) = ?', [strtoupper($code)])
          ->first();

        if (!$asset && is_numeric($code)) {
            $asset = Asset::with([
                'company',
                'branch',
                'location',
                'department',
                'custodian',
                'category',
                'subCategory',
                'brand',
                'assetType',
                'audits',
            ])->find((int) $code);
        }

        if (!$asset) {
            return response()->json([
                'status' => false,
                'message' => "Invalid QR code. No asset found matching \"{$rawCode}\".",
            ], 404);
        }

        $isDuplicate = $asset->hasAuditRecord();
        $duplicateMessage = null;

        if ($isDuplicate) {
            $date = $asset->auditOnLabel();
            $auditor = $asset->auditByLabel();
            $duplicateMessage = "This asset has already been audited on {$date} by {$auditor}. Submitting again will append a new audit record.";
        }

        return response()->json([
            'status' => true,
            'message' => 'Asset retrieved successfully',
            'is_duplicate_scan' => $isDuplicate,
            'duplicate_message' => $duplicateMessage,
            'data' => $this->formatAssetForMobile($asset),
        ], 200);
    }

    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|integer|exists:assets,id',
            'audit_status' => 'required|string',
            'location_status' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            $user = User::first();
        }
        $auditorId = $user ? $user->id : 1;
        $auditorName = $user ? $user->name : 'Super Admin';

        $asset = Asset::findOrFail($request->asset_id);
        $previousStatus = $asset->auditStatusLabel();

        $asset->recordAudit($auditorId, $request->audit_status, [
            'location_status' => $request->location_status,
            'remarks' => $request->remarks,
            'source' => 'mobile_app',
        ]);

        $asset->refresh();
        $newStatus = $asset->auditStatusLabel();
        $nowFormatted = now()->format('d M Y, h:i A');

        return response()->json([
            'status' => true,
            'message' => 'Asset audit recorded successfully and synchronized with web application.',
            'data' => [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'previous_audit_status' => $previousStatus,
                'new_audit_status' => $newStatus,
                'auditor' => $auditorName,
                'date_time' => $nowFormatted,
                'audited_at' => $nowFormatted,
                'remarks' => $request->remarks ?: '-',
                'location_status' => $request->location_status ?: 'Matches',
            ],
        ], 200);
    }

    public function getHistory(string|int $id): JsonResponse
    {
        $asset = Asset::with(['audits.auditor'])
            ->where('id', $id)
            ->orWhere('asset_code', $id)
            ->first();

        if (!$asset) {
            return response()->json([
                'status' => false,
                'message' => 'Asset not found',
            ], 404);
        }

        $formattedHistory = $asset->audits->sortByDesc('id')->map(function ($h) {
            return [
                'id' => $h->id,
                'asset_id' => $h->asset_id,
                'audit_status' => $h->auditStatusLabel(),
                'auditor_name' => $h->auditorName(),
                'location_status' => $h->location_status ?? 'Matches',
                'remarks' => $h->remarks ?? '-',
                'date' => Carbon::parse($h->audited_at ?? $h->created_at)->format('d M Y'),
                'time' => Carbon::parse($h->audited_at ?? $h->created_at)->format('h:i A'),
                'audited_at' => Carbon::parse($h->audited_at ?? $h->created_at)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Audit history retrieved successfully',
            'data' => [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'current_audit_status' => $asset->auditStatusLabel(),
                'total_audits' => $formattedHistory->count(),
                'history' => $formattedHistory,
            ],
        ], 200);
    }

    private function extractAssetCode(string $raw): string
    {
        $trimmed = trim($raw);

        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                if (!empty($decoded['asset_code'])) return trim($decoded['asset_code']);
                if (!empty($decoded['code'])) return trim($decoded['code']);
                if (!empty($decoded['id'])) return (string) $decoded['id'];
            }
        }

        if (preg_match('/\/asset(?:s)?\/([a-zA-Z0-9_\-]+)\/?$/i', $trimmed, $matches)) {
            return $matches[1];
        }

        return $trimmed;
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Traits\ApiResponse;
use App\Http\Requests\Assets\AssetRequest;
use App\AssetStatus;
use App\AssetCondition;
use App\AssetOperationalStatus;
use App\Helpers\Files;
use Illuminate\Database\Eloquent\Builder;

class AssetController extends Controller
{

    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $assets=Asset::with([
        'vendor:id,name',
        'brand:id,brand_name',
        'branch:id,name',
        'company:id,name',
        'assetType:id,asset_type_name',
        'category:id,category_name',
        'subCategory:id,category_name',
        'location:id,location_name',
        'createdBy:id,name'
    ])
    ->visibleToUser(auth()->user())

    ->when($request->company_id, function ($q) use ($request) {
        $q->where('company_id', $request->company_id);
    })

    ->when($request->branch_id, function ($q) use ($request) {
        $q->filteredByBranch((int) $request->branch_id);
    })

    ->when($request->asset_type_id, function ($q) use ($request) {
        $q->where('asset_type_id', $request->asset_type_id);
    })

    ->when($request->status, function ($q) use ($request) {
        $q->where('status', $request->status);
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
            $w->where('asset_code', 'like', "%{$term}%")
                ->orWhere('asset_name', 'like', "%{$term}%")
                ->orWhere('serial_no', 'like', "%{$term}%");
        });
    })

    ->latest()   // same as orderBy('id','DESC')
    ->paginate(50)
    ->withQueryString();

        return $this->successWithPagination($assets);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetRequest $request)
    {
        $data=$request->validated();

         $wareHouseCompanyId=setting('warehouse_company_id',1);

        $wareHouseId=setting('warehouse_id',1);

        $wareHouseLocationId=setting('warehouse_location',1);

        if($data['status'] == 'assigned' && $data['company_id'] == $wareHouseCompanyId && $data['branch_id'] == $wareHouseId){

            return redirect()->back()->with('error', 'Asset cannot be assigned to warehouse');

        }         

        if($request->hasFile('filename') && $request->file('filename')->isValid()){    

            $data['filename'] = Files::uploadLocalOrS3($request->filename,  Asset::FILE_PATH);
        }    

        try {
            $data['asset_code'] = Asset::generateAssetCode((int) $data['company_id'], (int) $data['branch_id']);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        $asset=Asset::create($data);

        if($data['status'] == 'assigned'){

            $asset->assignments()->create([
                'asset_id'=>$asset->id,
                'assign_date'=>date('Y-m-d'),                
                'from_branch' => $wareHouseId,
                'from_location' => $wareHouseLocationId,
                'to_branch' => $data['branch_id'],
                'to_location' => $data['location_id'],
                'transfer_status' => 'in_transit',
            ]);

        }

        return $this->success($asset);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asset= Asset::find($id);

        if(!$asset){ 
         
            return $this->error('Asset not found');
        }

        return $this->success($asset);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetRequest $request, string $id)
    {
        $asset= Asset::find($id);

        if(!$asset){ 
         
            return $this->error('Asset not found');
        }

        $data = $request->validated();
        unset($data['asset_code']);

        $asset->update($data);

        return $this->success($asset);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asset= Asset::find($id);

        if(!$asset){ 
         
            return $this->error('Asset not found');
        }

        $asset->delete();

        return $this->success('Asset deleted successfully');
    }

    public function status(){

        $statuses=AssetStatus::cases();

        return $this->success($statuses);

    }

    public function condition(){

        $conditions=AssetCondition::cases();

        return $this->success($conditions);
    }

    public function getnextassetcode(Request $request){

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        try {
            $assetCode = Asset::generateAssetCode((int) $validated['company_id'], (int) $validated['branch_id']);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success($assetCode);

    }

    public function operatingStatus(){

        $operationalStatus=AssetOperationalStatus::cases();

        return $this->success($operationalStatus);

    }

    /**
     * Record the latest successful audit from the mobile app.
     * Updates only the audit columns on the asset.
     */
    public function audit(Request $request, string $id)
    {
        $asset = Asset::query()->visibleToUser(auth()->user())->find($id);

        if (! $asset) {
            return $this->error('Asset not found');
        }

        $rawStatus = $request->input('audit_status', $request->input('status', $request->input('result')));
        $normalized = Asset::normalizeAuditStatus($rawStatus);

        if ($normalized === null) {
            return $this->error('Audit status must be Audited or Not Found.');
        }

                $extraAttributes = [
            'source' => $request->input('source', 'mobile_app'),
            'remarks' => $request->input('remarks', $request->input('notes')),
            'location_status' => $request->input('location_status', 'matches'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ];

        if (! $asset->recordAudit((int) auth()->id(), $normalized, $extraAttributes)) {
            return $this->error('Unable to save the audit.');
        }

        $asset->load('auditedBy:id,name');

        return $this->success([
            'id' => $asset->id,
            'audit_by' => $asset->auditByLabel(),
            'audit_on' => $asset->auditOnLabel(),
            'audit_status' => $asset->auditStatusLabel(),
        ], 'Asset audited successfully');
    }

    /**
     * Retrieve audit history for an asset.
     */
    public function auditHistory(string $id)
    {
        $asset = Asset::query()->visibleToUser(auth()->user())->find($id);

        if (! $asset) {
            return $this->error('Asset not found');
        }

        $history = $asset->audits()
            ->with(['auditor:id,name', 'location:id,name', 'branch:id,name', 'company:id,name'])
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'audited_by' => $audit->auditorName(),
                    'audit_status' => $audit->auditStatusLabel(),
                    'raw_status' => $audit->audit_status,
                    'previous_status' => $audit->previous_status,
                    'location_status' => $audit->location_status,
                    'remarks' => $audit->remarks,
                    'audited_at' => $audit->formattedDate(),
                    'source' => $audit->source,
                    'location' => $audit->location?->name ?? null,
                    'branch' => $audit->branch?->name ?? null,
                ];
            });

        return $this->success($history, 'Audit history retrieved successfully');
    }
}

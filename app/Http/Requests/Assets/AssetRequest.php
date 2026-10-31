<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use App\AssetStatus;
use App\AssetCondition;
use App\AssetOperationalStatus;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $asset = $this->existingAsset();

        if ($asset && filled($asset->asset_code)) {
            $this->merge([
                'asset_code' => $asset->asset_code,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         
        $assetId = $this->route('asset');

        // If route-model binding is used
        if (is_object($assetId)) {
            $assetId = $assetId->id;
        }

        $isAssigned = $this->input('status') === 'assigned'
            || $this->input('status') === AssetStatus::ASSIGNED->value;

       return [

            /* ================= ASSET DETAILS ================= */
            'asset_name'        => 'required|string|max:255',
            'asset_structure' => 'required|in:single,group',
            'items_count' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $structure = (string) $this->input('asset_structure');
                    $qty = (int) $value;
                    if ($structure === 'single' && $qty !== 1) {
                        $fail('Quantity must be 1 for a single asset.');
                    }
                    if ($structure === 'group' && $qty < 2) {
                        $fail('Quantity must be at least 2 for a group asset.');
                    }
                },
            ],
            'hsn_code' => 'nullable|string|max:50',
            'uom' => 'nullable|string|max:50',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_sub_category_id' => [
                'required',
                Rule::exists('product_sub_categories', 'id')->where(
                    fn ($query) => $query->where('product_category_id', $this->input('product_category_id'))
                ),
            ],
            'serial_no'         => [
            'required',
            'string',
            'max:255',
            Rule::unique('assets', 'serial_no')->ignore($assetId),
        ],
            'asset_code'        => array_values(array_filter([
            $assetId ? 'required' : 'nullable',
            'string',
            'max:255',
            $assetId ? Rule::unique('assets', 'asset_code')->ignore($assetId) : null,
        ])),
            
            'status'            => ['required', new Enum(AssetStatus::class)],
            'condition'         => ['required', new Enum(AssetCondition::class)],
            'operational_status' => ['required', new Enum(AssetOperationalStatus::class)],
            'location_id'       => 'nullable|exists:branch_locations,id',
            'branch_id' => 'required|exists:branches,id',
            'company_id'=>'required|exists:companies,id',

            'to_company_id' => [$isAssigned ? 'required' : 'nullable', 'exists:companies,id'],
            'to_branch' => [
                $isAssigned ? 'required' : 'nullable',
                'exists:branches,id',
            ],
            'to_location' => 'nullable|exists:branch_locations,id',
            'to_custodian_id' => 'nullable|exists:asset_custodians,id',

            /* ================= PURCHASE & WARRANTY ================= */
            'purchase_date'     => 'required|date_format:Y-m-d',
            'purchase_cost'     => 'required|numeric|min:0',
            'supplier'          => 'required|integer|exists:vendors,id',
            'invoice_details' => 'nullable|string',
            'invoice_date' => 'nullable|date_format:Y-m-d',
            'po_number' => 'nullable|string|max:100',
            'po_date' => 'nullable|date_format:Y-m-d',
            'purchase_type' => 'nullable|in:purchased,transferred',
            'ownership_type' => 'nullable|in:owned,rent',
            'cost_centre' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'custodian_id' => 'nullable|exists:asset_custodians,id',

            /* ================= DEPRECIATION ================= */
          'depreciation_method' => 'required|in:no_depreciation,straight_line,reducing_balance',

'userful_life' => 'nullable|required_if:depreciation_method,straight_line|numeric|min:1',

'salvage_value' => [
    'nullable',
    'required_if:depreciation_method,straight_line,reducing_balance',
    'numeric',
    'min:0',
    function ($attr, $value, $fail) {
        if ($this->depreciation_method === 'reducing_balance' && $value > 100) {
            $fail('Rate of depreciation cannot exceed 100%.');
        }
    }
],
            /* ================= DESCRIPTION ================= */
            'description'       => 'nullable|string|max:2000',
        ];
    }

    private function existingAsset(): ?\App\Models\Asset
    {
        $asset = $this->route('asset');

        if ($asset instanceof \App\Models\Asset) {
            return $asset;
        }

        $assetId = is_numeric($asset) ? $asset : $this->route('id');

        if (! is_numeric($assetId)) {
            return null;
        }

        return \App\Models\Asset::query()->find($assetId);
    }

    public function messages(): array
    {
        return [
            'salvage_value.required_if' => 'Rate of Depreciation (%) is required when Depreciation Method is Reducing Balance.',
            'salvage_value.numeric' => 'Rate of Depreciation (%) must be a number.',
            'salvage_value.min' => 'Rate of Depreciation (%) cannot be negative.',
        ];
    }
}

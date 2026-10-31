@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Asset')

@section('page-style')
<style>
    .form-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 2rem;
        border: 1px solid #e3e6f0;
        margin-bottom: 1.5rem;
    }
    .form-section-title {
        color: #495057;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    .input-group > .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .input-group > .select2-container .select2-selection--single {
        height: 100%;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    #salvage_value::-webkit-outer-spin-button,
    #salvage_value::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #salvage_value {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
@endsection

@section('content')

<h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light">Edit Asset</span>
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Edit Asset</h5>
        <a href="{{ route('asset.index') }}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="card-body">
        <div class="form-container">

<form id="assetEditForm" action="{{ route('asset.update', $asset->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="alert alert-danger" id="assetFormErrors">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= ASSET DETAILS ================= --}}
    <div class="form-section-title">Asset Details</div>
    <div class="row g-3">

        <div class="col-md-4">
            <label class="form-label">Asset Description <span class="text-danger">*</span></label>
            <input type="text" name="asset_name" required
                   class="form-control @error('asset_name') is-invalid @enderror"
                   value="{{ old('asset_name', $asset->asset_name) }}">
            @error('asset_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

 <div class="col-md-4">
            <label class="form-label">SL No / Any ID's <span class="text-danger">*</span></label>
            <input type="text" name="serial_no" required
                   class="form-control @error('serial_no') is-invalid @enderror"
                   value="{{ old('serial_no', $asset->serial_no) }}">
            @error('serial_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $selectedHsn = old('hsn_code', $asset->hsn_code ?: $asset->msuGrnItem?->hsn);
        @endphp
        <div class="col-md-4">
            <label class="form-label">HSN Code</label>
            <select name="hsn_code" class="form-select select2 @error('hsn_code') is-invalid @enderror">
                <option value="">Select HSN Code</option>
                @foreach ($hsns as $hsn)
                    <option value="{{ $hsn->hsn_code }}" {{ (string) $selectedHsn === (string) $hsn->hsn_code ? 'selected' : '' }}>
                        {{ $hsn->hsn_code }}
                    </option>
                @endforeach
                @if ($selectedHsn && ! $hsns->contains(fn ($hsn) => (string) $hsn->hsn_code === (string) $selectedHsn))
                    <option value="{{ $selectedHsn }}" selected>{{ $selectedHsn }}</option>
                @endif
            </select>
            @error('hsn_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Asset Code <span class="text-danger">*</span></label>
            <input type="text" name="asset_code" id="asset_code" required readonly
                   class="form-control bg-light @error('asset_code') is-invalid @enderror"
                   value="{{ $asset->asset_code }}">
            @error('asset_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
        <label class="form-label">Asset Structure <span class="text-danger">*</span>  </label>
        <select name="asset_structure" id="asset_structure" required  class="form-select @error('asset_structure') is-invalid @enderror">
            <option value="">Select Structure</option>
            <option value="single" {{ old('asset_structure', $asset->asset_structure) == 'single' ? 'selected' : '' }}> Single Asset  </option>
            <option value="group"  {{ old('asset_structure', $asset->asset_structure) == 'group' ? 'selected' : '' }}> Group Asset </option>
        </select>
        @error('asset_structure')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $editStructure = old('asset_structure', $asset->asset_structure);
            $editIsSingle = $editStructure !== 'group';
            $editQty = $editIsSingle ? 1 : max(2, (int) old('items_count', $asset->items_count ?: 2));
        @endphp
        <div class="col-md-4" id="itemsCountWrapper">
        <label class="form-label">Qty <span class="text-danger">*</span> </label>
        <input type="number" name="items_count" id="items_count" required
               min="{{ $editIsSingle ? 1 : 2 }}"
               @if($editIsSingle) max="1" readonly @endif
               class="form-control @error('items_count') is-invalid @enderror {{ $editIsSingle ? 'bg-light' : '' }}"
               value="{{ $editQty }}">
        @error('items_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $uomValue = old('uom', $asset->uom ?: ($asset->msuGrnItem?->itemMaster?->unit?->unit_name ?: $asset->msuGrnItem?->itemMaster?->uom));
        @endphp
        <div class="col-md-4">
            <label class="form-label">UOM</label>
            <select name="uom" class="form-select select2 @error('uom') is-invalid @enderror">
                <option value="">Select Unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->unit_name }}" {{ (string) $uomValue === (string) $unit->unit_name ? 'selected' : '' }}>{{ $unit->unit_name }}</option>
                @endforeach
                @if ($uomValue && ! $units->contains('unit_name', $uomValue))
                    <option value="{{ $uomValue }}" selected>{{ $uomValue }}</option>
                @endif
            </select>
            @error('uom')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $selectedProductCategoryId = old('product_category_id', $asset->product_category_id ?: $asset->msuGrnItem?->itemMaster?->product_category_id);
            $selectedProductSubCategoryId = old('product_sub_category_id', $asset->product_sub_category_id ?: $asset->msuGrnItem?->itemMaster?->product_sub_category_id);
        @endphp
        <div class="col-md-4">
        <label class="form-label"> Product Categories <span class="text-danger">*</span> </label>
        <select name="product_category_id" id="product_category_id" required class="form-select select2 @error('product_category_id') is-invalid @enderror">
            <option value="">Select Product Category</option>
            @foreach ($productCategories as $category)
                <option value="{{ $category->id }}"
                    {{ (string) $selectedProductCategoryId === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>
        @error('product_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
        <label class="form-label"> Product Sub Categories <span class="text-danger">*</span>  </label>
        <select name="product_sub_category_id" id="product_sub_category_id" required class="form-select select2 @error('product_sub_category_id') is-invalid @enderror">
            <option value="">Select Product Sub Category</option>
        </select>
        @error('product_sub_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Type of Purchase</label>
            <select name="purchase_type" class="form-select @error('purchase_type') is-invalid @enderror">
                <option value="">Select Type of Purchase</option>
                <option value="purchased" {{ old('purchase_type', $asset->purchase_type) == 'purchased' ? 'selected' : '' }}>Purchased</option>
                <option value="transferred" {{ old('purchase_type', $asset->purchase_type) == 'transferred' ? 'selected' : '' }}>Transferred</option>
            </select>
            @error('purchase_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Asset Type</label>
            <select name="ownership_type" class="form-select @error('ownership_type') is-invalid @enderror">
                <option value="">Select Asset Type</option>
                <option value="owned" {{ old('ownership_type', $asset->ownership_type) == 'owned' ? 'selected' : '' }}>Owned</option>
                <option value="rent" {{ old('ownership_type', $asset->ownership_type) == 'rent' ? 'selected' : '' }}>Rent</option>
            </select>
            @error('ownership_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

       

        <div class="col-md-4">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" required
                    class="form-select @error('status') is-invalid @enderror">
                <option value="">Select Status </option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                        {{ old('status', $asset->status?->value) == $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

         <div class="col-md-4">
         <label class="form-label"> Operational Status <span class="text-danger">*</span> </label>
            <select name="operational_status" required
                    class="form-select @error('operational_status') is-invalid @enderror">
                <option value="">Select Operational Status</option>
                @foreach ($operations_status as $operationStatus)
                    <option value="{{$operationStatus->value}}" {{ old('operational_status', $asset->operational_status?->value) == $operationStatus->value ? 'selected' : '' }}>{{$operationStatus->label()}}</option>
                @endforeach
            </select>
            @error('operational_status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
       </div>

        <div class="col-md-4">
            <label class="form-label">Condition <span class="text-danger">*</span></label>
            <select name="condition" required
                    class="form-select @error('condition') is-invalid @enderror">
                <option value="">Select Condition</option>
                @foreach ($conditions as $condition)
                    <option value="{{ $condition->value }}"
                        {{ old('condition', $asset->condition?->value) == $condition->value ? 'selected' : '' }}>
                        {{ $condition->label() }}
                    </option>
                @endforeach
            </select>
            @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">Company <span class="text-danger">*</span></label>
            <select id="company_id" name="company_id" required
                    class="form-select select2 @error('company_id') is-invalid @enderror">
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ old('company_id',$asset->company_id) == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $__supplierIdEdit = old('supplier', $asset->supplier);
            $__supplierNameEdit = old('supplier_display');
            if ($__supplierNameEdit === null && $__supplierIdEdit !== null && $__supplierIdEdit !== '') {
                $__supplierNameEdit = optional(collect($vendors)->first(fn ($v) => (string)$v->id === (string)$__supplierIdEdit))->name;
            }
        @endphp

        <div class="col-md-3">
            <label class="form-label">Branch <span class="text-danger">*</span></label>
            <select name="branch_id" id="branch_id" required class="form-select select2 @error('branch_id') is-invalid @enderror">
                <option value="">Select Branch</option>
            </select>
            @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        @php
            $selectedLocationId = old('location_id', $asset->location_id);
            $selectedLocationName = old('location_display');
            if ($selectedLocationName === null && $selectedLocationId) {
                $selectedLocationName = optional(collect($locations)->first(fn ($location) => (string) $location->id === (string) $selectedLocationId))->location_name;
            }
        @endphp
        <div class="col-md-3">
            <label class="form-label">Physical Location</label>
            <div class="input-group">
                <input type="text"
                       id="location_display"
                       class="form-control @error('location_id') is-invalid @enderror"
                       value="{{ $selectedLocationName ?? '' }}"
                       list="location-options"
                       autocomplete="off"
                       placeholder="Type or pick physical location">
                <button type="button" class="btn btn-outline-primary" id="quickCreateLocationBtn" title="Add physical location">+</button>
            </div>
            <input type="hidden" name="location_id" id="location_id" value="{{ $selectedLocationId }}">
            <datalist id="location-options">
                @foreach($locations as $location)
                    <option value="{{ $location->location_name }}"></option>
                @endforeach
            </datalist>
            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        @php
            $__custodianIdEdit = old('custodian_id', $asset->custodian_id);
            $__custodianNameEdit = old('custodian_display');
            if ($__custodianNameEdit === null && $__custodianIdEdit !== null && $__custodianIdEdit !== '') {
                $__custodianNameEdit = optional(collect($custodians)->first(fn ($c) => (string) $c->id === (string) $__custodianIdEdit))->employee_name;
            }
        @endphp
        <div class="col-md-3">
            <label class="form-label">Asset Custodian</label>
            <div class="input-group">
                <input type="text"
                       id="custodian_display"
                       class="form-control @error('custodian_id') is-invalid @enderror"
                       value="{{ $__custodianNameEdit ?? '' }}"
                       list="custodian-options"
                       autocomplete="off"
                       placeholder="Type or pick asset custodian">
                <button type="button" class="btn btn-outline-primary" id="quickCreateCustodianBtn" title="Add asset custodian">+</button>
            </div>
            <input type="hidden" name="custodian_id" id="custodian_id" value="{{ old('custodian_id', $asset->custodian_id) }}">
            <datalist id="custodian-options">
                @foreach ($custodians as $custodian)
                    <option value="{{ $custodian->employee_name }}"></option>
                @endforeach
            </datalist>
            @error('custodian_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

    </div>

    {{-- ================= PURCHASE & WARRANTY ================= --}}
    <div class="form-section-title mt-4">Purchase</div>
    <div class="row g-3">

        <div class="col-md-4">
            <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
            <input type="date" name="purchase_date" required
                   class="form-control @error('purchase_date') is-invalid @enderror"
                   value="{{ old('purchase_date', optional($asset->purchase_date)->format('Y-m-d')) }}">
            @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Invoice Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.001" name="purchase_cost" required
                   class="form-control @error('purchase_cost') is-invalid @enderror"
                   value="{{ old('purchase_cost', $asset->purchase_cost) }}">
            @error('purchase_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Supplier <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="text"
                       id="supplier_display"
                       class="form-control @error('supplier') is-invalid @enderror"
                       value="{{ $__supplierNameEdit ?? '' }}"
                       list="supplier-options"
                       autocomplete="off"
                       placeholder="Type or pick supplier">
                <button type="button" class="btn btn-outline-primary" id="quickCreateSupplierBtn" title="Add supplier">+</button>
            </div>
            <input type="hidden" name="supplier" id="supplier" value="{{ old('supplier', $asset->supplier) }}">
            <datalist id="supplier-options">
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->name }}"></option>
                @endforeach
            </datalist>
            @error('supplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

         <div class="col-md-4">
            <label class="form-label">Invoice No</label>
            <input type="text"  name="invoice_details"
                   class="form-control @error('invoice_details') is-invalid @enderror"
                   value="{{ old('invoice_details',$asset->invoice_details) }}">
            @error('invoice_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $invoiceDateValue = old('invoice_date', optional($asset->invoice_date ?: $asset->msuGrnItem?->grn?->vendor_invoice_date)->format('Y-m-d'));
            $poDateValue = old('po_date', optional($asset->po_date ?: $asset->msuGrnItem?->grn?->purchaseOrder?->po_date)->format('Y-m-d'));
            $poNumberValue = old('po_number', $asset->po_number ?: $asset->msuGrnItem?->grn?->purchaseOrder?->po_number);
        @endphp

        <div class="col-md-4">
            <label class="form-label">Invoice Date</label>
            <input type="date" name="invoice_date"
                   class="form-control @error('invoice_date') is-invalid @enderror"
                   value="{{ $invoiceDateValue }}">
            @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">PO Number</label>
            <input type="text" name="po_number"
                   class="form-control @error('po_number') is-invalid @enderror"
                   value="{{ $poNumberValue }}">
            @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">PO Date</label>
            <input type="date" name="po_date"
                   class="form-control @error('po_date') is-invalid @enderror"
                   value="{{ $poDateValue }}">
            @error('po_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @php
            $selectedCostCentre = old('cost_centre', $asset->cost_centre);
            $costCentreNames = $costCentres->pluck('cost_centre_name')->values();
            if ($selectedCostCentre && ! $costCentreNames->contains($selectedCostCentre)) {
                $costCentreNames->push($selectedCostCentre);
            }
        @endphp
        <div class="col-md-4">
            <label class="form-label">Cost Centre</label>
            <div class="input-group">
                <input type="text"
                       name="cost_centre"
                       id="cost_centre"
                       class="form-control @error('cost_centre') is-invalid @enderror"
                       value="{{ $selectedCostCentre }}"
                       list="cost-centre-options"
                       autocomplete="off"
                       placeholder="Type or pick cost centre">
                <button type="button" class="btn btn-outline-primary" id="quickCreateCostCentreBtn" title="Add cost centre">+</button>
            </div>
            <datalist id="cost-centre-options">
                @foreach ($costCentreNames as $costCentreName)
                    <option value="{{ $costCentreName }}"></option>
                @endforeach
            </datalist>
            @error('cost_centre')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select select2 @error('department_id') is-invalid @enderror">
                <option value="">Select Department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" {{ old('department_id', $asset->department_id) == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>

    {{-- ================= DEPRECIATION ================= --}}
    <div class="form-section-title mt-4">Depreciation</div>
    <div class="row g-3">

        <div class="col-md-4">
            <label class="form-label">Depreciation Method</label>
            <select name="depreciation_method" class="form-select" id="depreciation_method">
                @foreach(['no_depreciation','reducing_balance'] as $method)
                    <option value="{{ $method }}"
                        {{ old('depreciation_method', $asset->depreciation_method) == $method ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_',' ',$method)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 d-none" id="usefulLifeWrapper">
            <label class="form-label">Useful Life (Years)</label>
            <input type="text" name="userful_life"
                   class="form-control"
                   value="{{ old('userful_life', $asset->userful_life) }}">
        </div>

        <div class="col-md-4 d-none" id="salvageWrapper">
            <label class="form-label" id="salvageLabel">Salvage Value</label>
            <input type="number" step="0.01" min="0" max="100"
                   name="salvage_value"
                   id="salvage_value"
                   class="form-control @error('salvage_value') is-invalid @enderror"
                   value="{{ old('salvage_value', $asset->salvage_value) }}">
            <div class="text-danger small mt-1 @error('salvage_value') @else d-none @enderror" id="salvage_value_error">@error('salvage_value'){{ $message }}@enderror</div>
        </div>

        <div class="col-md-4">
            <label class="form-label">Asset Image </label>
            <input class="form-control @error('filename') is-invalid @enderror" type="file" id="filename" name="filename" accept="image/*">              
            @error('filename')<div class="invalid-feedback">{{ $message }}</div>@enderror

            <div class="mt-2">
                @if ($asset->filename)
                    <img src="{{ $asset->doc_url }}" alt="" width="100">
                @endif
            </div>
          </div>

        <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="description" class="form-control" rows="3">
{{ old('description', $asset->description) }}
            </textarea>
        </div>

    </div>

    {{-- ================= ACTIONS ================= --}}
    <div class="mt-4 text-end">
        <button class="btn btn-primary">Update Asset</button>
        <a href="{{ route('asset.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

        </div>
    </div>
</div>

<div class="modal fade" id="quickCustodianModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="quickCustodianForm">
      <div class="modal-header">
        <h5 class="modal-title">Add Asset Custodian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Custodian name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="quick_custodian_name" required>
          <div class="invalid-feedback d-block" id="quick_custodian_name_error"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Department <span class="text-muted">(optional)</span></label>
          <select class="form-select" id="quick_custodian_department">
            <option value="">Select Department</option>
            @foreach ($departments as $department)
              <option value="{{ $department->id }}">{{ $department->department_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Mobile <span class="text-muted">(optional)</span></label>
          <input type="text" class="form-control" id="quick_custodian_mobile" maxlength="20">
        </div>
        <div class="mb-0">
          <label class="form-label">Email <span class="text-muted">(optional)</span></label>
          <input type="email" class="form-control" id="quick_custodian_email">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="quickSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="quickSupplierForm">
      <div class="modal-header">
        <h5 class="modal-title">Add Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Supplier name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="quick_supplier_name" required>
          <div class="invalid-feedback d-block" id="quick_supplier_name_error"></div>
        </div>
        <div class="mb-0">
          <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
          <input type="text" class="form-control" id="quick_supplier_phone" maxlength="20">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="quickLocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="quickLocationForm">
      <div class="modal-header">
        <h5 class="modal-title">Add Physical Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Physical Location name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="quick_location_name" required>
        <div class="invalid-feedback d-block" id="quick_location_name_error"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="quickCostCentreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="quickCostCentreForm">
      <div class="modal-header">
        <h5 class="modal-title">Add Cost Centre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Cost Centre name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="quick_cost_centre_name" required>
          <div class="invalid-feedback d-block" id="quick_cost_centre_name_error"></div>
        </div>
        <div class="mb-0">
          <label class="form-label">Description <span class="text-muted">(optional)</span></label>
          <textarea class="form-control" id="quick_cost_centre_description" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

@endsection



@section('page-script')

<script>

function syncDisposedFields() {
    if ($('select[name="status"]').val() !== 'disposed') {
        return;
    }

    ['operational_status', 'condition'].forEach(function (name) {
        const $field = $('select[name="' + name + '"]');
        if (!$field.length) {
            return;
        }
        $field.val('disposed');
        refreshSearchable($field);
    });
}

function refreshSearchable($el) {
    if (!$el || !$el.length) {
        return;
    }
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.trigger('change.select2');
    }
}

function initSearchableSelects() {
    $('.select2').each(function () {
        const $select = $(this);
        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }
        $select.select2({
            placeholder: $select.find('option[value=""]').first().text() || 'Select',
            allowClear: $select.find('option[value=""]').length > 0,
            closeOnSelect: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body)
        });
    });
}

let SUPPLIERS_DATA = @json($vendors->map(fn ($v) => ['id' => (int)$v->id, 'name' => (string)$v->name])->values());

function toastSuccessAssetForm(text) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'success', title: 'Success', text: text, timer: 1800, showConfirmButton: false });
    } else {
        alert(text);
    }
}

function rebuildSupplierDatalist() {
    const seen = {};
    const $opts = $('#supplier-options');
    $opts.empty();
    SUPPLIERS_DATA.forEach(function (v) {
        const kk = v.name.trim().toLowerCase();
        if (seen[kk]) {
            return;
        }
        seen[kk] = true;
        $opts.append($('<option></option>').attr('value', v.name));
    });
}

function registerSupplier(row) {
    if (!row || !row.id) {
        return false;
    }
    const id = parseInt(row.id, 10);
    const name = (row.name || '').trim();
    if (!name) {
        return false;
    }
    const ix = SUPPLIERS_DATA.findIndex(function (v) {
        return String(v.id) === String(id);
    });
    if (ix >= 0) {
        SUPPLIERS_DATA[ix].name = name;
    } else {
        SUPPLIERS_DATA.push({ id: id, name: name });
    }
    rebuildSupplierDatalist();
    $('#supplier').val(id);
    $('#supplier_display').val(name);
    return true;
}

function syncSupplierHidden() {
    const raw = ($('#supplier_display').val() || '').trim();
    $('#supplier').val('');
    if (!raw) {
        return false;
    }
    const k = raw.toLowerCase();
    const hits = SUPPLIERS_DATA.filter(function (v) {
        return v.name.trim().toLowerCase() === k;
    });
    if (hits.length !== 1) {
        return false;
    }
    $('#supplier').val(hits[0].id);
    return true;
}

function postVendorQuick(name, phone, done) {
    $.ajax({
        url: "{{ route('vendor.quick-store') }}",
        type: 'POST',
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            name: name,
            phone_number: phone || ''
        },
        success: function (res) {
            if (res && res.data) {
                registerSupplier(res.data);
            }
            toastSuccessAssetForm((res && res.message) ? res.message : 'Supplier saved.');
            if (quickSupplierModal) {
                quickSupplierModal.hide();
            }
            done && done(null, res);
        },
        error: function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                || (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.name && xhr.responseJSON.errors.name[0])
                || 'Unable to save supplier.';
            done && done(msg, xhr);
        }
    });
}

var quickSupplierModal = null;
if (typeof bootstrap !== 'undefined' && document.getElementById('quickSupplierModal')) {
    quickSupplierModal = new bootstrap.Modal(document.getElementById('quickSupplierModal'));
}

function showQuickSupplierModalFn() {
    if (quickSupplierModal) {
        quickSupplierModal.show();
    } else {
        $('#quickSupplierModal').show();
    }
}

$('#supplier_display').on('input', function () {
    $('#supplier').val('');
});

$('#supplier_display').on('change blur', function () {
    syncSupplierHidden();
});

$('#quickCreateSupplierBtn').on('click', function() {
    $('#quick_supplier_name_error').text('');
    const typed = ($('#supplier_display').val() || '').trim();

    if (typed !== '') {
        postVendorQuick(typed, '', function (err) {
            if (err) {
                $('#quick_supplier_name').val(typed);
                $('#quick_supplier_phone').val('');
                $('#quick_supplier_name_error').text(typeof err === 'string' ? err : 'Unable to save supplier.');
                showQuickSupplierModalFn();
            }
        });
        return;
    }

    $('#quick_supplier_name').val('');
    $('#quick_supplier_phone').val('');
    showQuickSupplierModalFn();
});

$('#quickSupplierForm').on('submit', function(e) {
    e.preventDefault();
    const name = ($('#quick_supplier_name').val() || '').trim();
    $('#quick_supplier_name_error').text('');
    if (!name) {
        $('#quick_supplier_name_error').text('Supplier name is required.');
        return;
    }

    const phone = ($('#quick_supplier_phone').val() || '').trim();
    postVendorQuick(name, phone, function (err) {
        if (err) {
            $('#quick_supplier_name_error').text(typeof err === 'string' ? err : 'Unable to save supplier.');
        }
    });
});

let CUSTODIANS_DATA = @json($custodians->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->employee_name])->values());
let quickCustodianTarget = 'from';

function custodianUi(kind) {
    return {
        display: '#custodian_display',
        hidden: '#custodian_id',
        list: '#custodian-options'
    };
}

function rebuildCustodianDatalist() {
    const ui = custodianUi('from');
    const $opts = $(ui.list);
    if (!$opts.length) {
        return;
    }
    const seen = {};
    $opts.empty();
    CUSTODIANS_DATA.forEach(function (v) {
        const kk = (v.name || '').trim().toLowerCase();
        if (!kk || seen[kk]) {
            return;
        }
        seen[kk] = true;
        $opts.append($('<option></option>').attr('value', v.name));
    });
}

function registerCustodian(row) {
    if (!row || !row.id) {
        return false;
    }
    const id = parseInt(row.id, 10);
    const name = (row.name || '').trim();
    if (!name) {
        return false;
    }
    const ix = CUSTODIANS_DATA.findIndex(function (v) {
        return String(v.id) === String(id);
    });
    if (ix >= 0) {
        CUSTODIANS_DATA[ix].name = name;
    } else {
        CUSTODIANS_DATA.push({ id: id, name: name });
    }
    rebuildCustodianDatalist();
    const ui = custodianUi(quickCustodianTarget);
    $(ui.hidden).val(id);
    $(ui.display).val(name);
    return true;
}

function syncCustodianHidden() {
    const ui = custodianUi('from');
    if (!$(ui.display).length) {
        return false;
    }
    const raw = ($(ui.display).val() || '').trim();
    $(ui.hidden).val('');
    if (!raw) {
        return false;
    }
    const k = raw.toLowerCase();
    const hits = CUSTODIANS_DATA.filter(function (v) {
        return (v.name || '').trim().toLowerCase() === k;
    });
    if (hits.length !== 1) {
        return false;
    }
    $(ui.hidden).val(hits[0].id);
    return true;
}

function custodianAjaxError(xhr) {
    const errors = xhr.responseJSON && xhr.responseJSON.errors;
    if (errors) {
        const first = errors.employee_name || errors.mobile_number || errors.email || errors.department_id;
        if (first && first[0]) {
            return first[0];
        }
    }
    return (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save asset custodian.';
}

function postCustodianQuick(payload, done) {
    $.ajax({
        url: "{{ route('asset-custodians.quick-store') }}",
        type: 'POST',
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            employee_name: payload.employee_name,
            department_id: payload.department_id || '',
            mobile_number: payload.mobile_number || '',
            email: payload.email || ''
        },
        success: function (res) {
            if (res && res.data) {
                registerCustodian(res.data);
            }
            toastSuccessAssetForm((res && res.message) ? res.message : 'Asset custodian saved.');
            if (quickCustodianModal) {
                quickCustodianModal.hide();
            }
            done && done(null, res);
        },
        error: function (xhr) {
            done && done(custodianAjaxError(xhr), xhr);
        }
    });
}

var quickCustodianModal = null;
if (typeof bootstrap !== 'undefined' && document.getElementById('quickCustodianModal')) {
    quickCustodianModal = new bootstrap.Modal(document.getElementById('quickCustodianModal'));
}

function showQuickCustodianModalFn() {
    if (quickCustodianModal) {
        quickCustodianModal.show();
    } else {
        $('#quickCustodianModal').show();
    }
}

function resetQuickCustodianForm(name) {
    $('#quick_custodian_name_error').text('');
    $('#quick_custodian_name').val(name || '');
    $('#quick_custodian_department').val('');
    $('#quick_custodian_mobile').val('');
    $('#quick_custodian_email').val('');
}

$('#custodian_display').on('input', function () {
    $('#custodian_id').val('');
});
$('#custodian_display').on('change blur', function () {
    syncCustodianHidden();
});

$('#quickCreateCustodianBtn').on('click', function () {
    $('#quick_custodian_name_error').text('');
    const typed = ($('#custodian_display').val() || '').trim();

    if (typed !== '') {
        postCustodianQuick({ employee_name: typed }, function (err) {
            if (err) {
                resetQuickCustodianForm(typed);
                $('#quick_custodian_name_error').text(typeof err === 'string' ? err : 'Unable to save asset custodian.');
                showQuickCustodianModalFn();
            }
        });
        return;
    }

    resetQuickCustodianForm('');
    showQuickCustodianModalFn();
});

$('#quickCustodianForm').on('submit', function (e) {
    e.preventDefault();
    const name = ($('#quick_custodian_name').val() || '').trim();
    $('#quick_custodian_name_error').text('');
    if (!name) {
        $('#quick_custodian_name_error').text('Custodian name is required.');
        return;
    }

    postCustodianQuick({
        employee_name: name,
        department_id: $('#quick_custodian_department').val() || '',
        mobile_number: ($('#quick_custodian_mobile').val() || '').trim(),
        email: ($('#quick_custodian_email').val() || '').trim()
    }, function (err) {
        if (err) {
            $('#quick_custodian_name_error').text(typeof err === 'string' ? err : 'Unable to save asset custodian.');
        }
    });
});

function ajaxErrorMessage(xhr, fallback) {
    return (xhr.responseJSON && xhr.responseJSON.message)
        || (xhr.responseJSON && xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0] && Object.values(xhr.responseJSON.errors)[0][0])
        || fallback;
}

let FROM_LOCATIONS = @json($locations->map(fn ($location) => ['id' => (int) $location->id, 'name' => (string) $location->location_name])->values());

function rebuildLocationDatalist() {
    const seen = {};
    const $opts = $('#location-options');
    $opts.empty();
    FROM_LOCATIONS.forEach(function (row) {
        const key = (row.name || '').trim().toLowerCase();
        if (!key || seen[key]) {
            return;
        }
        seen[key] = true;
        $opts.append($('<option></option>').attr('value', row.name));
    });
}

function registerLocation(row) {
    if (!row || !row.id) {
        return false;
    }
    const id = parseInt(row.id, 10);
    const name = (row.name || row.location_name || '').trim();
    if (!name) {
        return false;
    }
    const ix = FROM_LOCATIONS.findIndex(function (item) {
        return String(item.id) === String(id);
    });
    if (ix >= 0) {
        FROM_LOCATIONS[ix].name = name;
    } else {
        FROM_LOCATIONS.push({ id: id, name: name });
    }
    rebuildLocationDatalist();
    $('#location_id').val(id);
    $('#location_display').val(name);
    return true;
}

function syncLocationHidden() {
    const raw = ($('#location_display').val() || '').trim();
    $('#location_id').val('');
    if (!raw) {
        return false;
    }
    const key = raw.toLowerCase();
    const hits = FROM_LOCATIONS.filter(function (item) {
        return (item.name || '').trim().toLowerCase() === key;
    });
    if (hits.length !== 1) {
        return false;
    }
    $('#location_id').val(hits[0].id);
    return true;
}

function clearLocationPicker() {
    FROM_LOCATIONS = [];
    rebuildLocationDatalist();
    $('#location_display').val('');
    $('#location_id').val('');
}

function applyLocationRows(rows, selectedId) {
    FROM_LOCATIONS = (rows || []).map(function (item) {
        return { id: item.id, name: item.location_name || item.name || '' };
    }).filter(function (item) {
        return item.id && item.name;
    });
    rebuildLocationDatalist();
    if (selectedId) {
        const hit = FROM_LOCATIONS.find(function (item) {
            return String(item.id) === String(selectedId);
        });
        if (hit) {
            $('#location_id').val(hit.id);
            $('#location_display').val(hit.name);
            return;
        }
    }
    syncLocationHidden();
}

function postLocationQuick(name, branchId, done) {
    $.ajax({
        url: "{{ route('location.quick-store') }}",
        type: 'POST',
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            location_name: name,
            branch_id: branchId
        },
        success: function (res) {
            const row = res && res.data ? res.data : null;
            if (row && row.id) {
                registerLocation(row);
            }
            toastSuccessAssetForm((res && res.message) ? res.message : 'Physical Location saved.');
            if (quickLocationModal) {
                quickLocationModal.hide();
            }
            done && done(null, res);
        },
        error: function (xhr) {
            done && done(ajaxErrorMessage(xhr, 'Unable to save physical location.'), xhr);
        }
    });
}

function beginQuickLocation() {
    const branchId = $('#branch_id').val();
    if (!branchId) {
        alert('Select a branch before adding a physical location.');
        $('#branch_id').trigger('focus');
        return;
    }
    $('#quick_location_name_error').text('');
    const typed = ($('#location_display').val() || '').trim();
    if (typed !== '') {
        postLocationQuick(typed, branchId, function (err) {
            if (err) {
                $('#quick_location_name').val(typed);
                $('#quick_location_name_error').text(typeof err === 'string' ? err : 'Unable to save physical location.');
                showBootstrapModal(quickLocationModal);
            }
        });
        return;
    }
    $('#quick_location_name').val('');
    showBootstrapModal(quickLocationModal);
}

function showBootstrapModal(modal) {
    if (modal) {
        modal.show();
    }
}

var quickLocationModal = null;
var quickCostCentreModal = null;

if (typeof bootstrap !== 'undefined') {
    if (document.getElementById('quickLocationModal')) {
        quickLocationModal = new bootstrap.Modal(document.getElementById('quickLocationModal'));
    }
    if (document.getElementById('quickCostCentreModal')) {
        quickCostCentreModal = new bootstrap.Modal(document.getElementById('quickCostCentreModal'));
    }
}

$('#location_display').on('input', function () {
    $('#location_id').val('');
});
$('#location_display').on('change blur', function () {
    syncLocationHidden();
});

$('#quickCreateLocationBtn').on('click', function () {
    beginQuickLocation();
});

$('#quickLocationForm').on('submit', function (e) {
    e.preventDefault();
    const name = ($('#quick_location_name').val() || '').trim();
    $('#quick_location_name_error').text('');
    if (!name) {
        $('#quick_location_name_error').text('Physical Location name is required.');
        return;
    }
    const branchId = $('#branch_id').val();
    if (!branchId) {
        $('#quick_location_name_error').text('Select a branch first.');
        return;
    }
    postLocationQuick(name, branchId, function (err) {
        if (err) {
            $('#quick_location_name_error').text(typeof err === 'string' ? err : 'Unable to save physical location.');
        }
    });
});

let COST_CENTRES = @json($costCentreNames->filter()->values());

function rebuildCostCentreDatalist() {
    const seen = {};
    const $opts = $('#cost-centre-options');
    $opts.empty();
    COST_CENTRES.forEach(function (name) {
        const label = (name || '').trim();
        const key = label.toLowerCase();
        if (!key || seen[key]) {
            return;
        }
        seen[key] = true;
        $opts.append($('<option></option>').attr('value', label));
    });
}

function matchingCostCentre(raw) {
    const key = (raw || '').trim().toLowerCase();
    if (!key) {
        return '';
    }
    const hit = COST_CENTRES.find(function (name) {
        return (name || '').trim().toLowerCase() === key;
    });
    return hit ? hit.trim() : '';
}

function registerCostCentre(name) {
    const label = (name || '').trim();
    if (!label) {
        return false;
    }
    if (!matchingCostCentre(label)) {
        COST_CENTRES.push(label);
    }
    rebuildCostCentreDatalist();
    $('#cost_centre').val(matchingCostCentre(label) || label);
    return true;
}

function postCostCentreQuick(name, description, done) {
    $.ajax({
        url: "{{ route('cost-centres.quick-store') }}",
        type: 'POST',
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            cost_centre_name: name,
            cost_centre_description: description || ''
        },
        success: function (res) {
            const row = res && res.data ? res.data : null;
            const label = (row && row.cost_centre_name) ? row.cost_centre_name : name;
            registerCostCentre(label);
            toastSuccessAssetForm((res && res.message) ? res.message : 'Cost Centre saved.');
            if (quickCostCentreModal) {
                quickCostCentreModal.hide();
            }
            done && done(null, res);
        },
        error: function (xhr) {
            done && done(ajaxErrorMessage(xhr, 'Unable to save cost centre.'), xhr);
        }
    });
}

$('#cost_centre').on('change blur', function () {
    const match = matchingCostCentre($(this).val());
    if (match) {
        $(this).val(match);
    }
});

$('#quickCreateCostCentreBtn').on('click', function () {
    $('#quick_cost_centre_name_error').text('');
    const typed = ($('#cost_centre').val() || '').trim();
    if (typed !== '') {
        postCostCentreQuick(typed, '', function (err) {
            if (err) {
                $('#quick_cost_centre_name').val(typed);
                $('#quick_cost_centre_description').val('');
                $('#quick_cost_centre_name_error').text(typeof err === 'string' ? err : 'Unable to save cost centre.');
                showBootstrapModal(quickCostCentreModal);
            }
        });
        return;
    }
    $('#quick_cost_centre_name').val('');
    $('#quick_cost_centre_description').val('');
    showBootstrapModal(quickCostCentreModal);
});

$('#quickCostCentreForm').on('submit', function (e) {
    e.preventDefault();
    const name = ($('#quick_cost_centre_name').val() || '').trim();
    $('#quick_cost_centre_name_error').text('');
    if (!name) {
        $('#quick_cost_centre_name_error').text('Cost Centre name is required.');
        return;
    }
    const description = ($('#quick_cost_centre_description').val() || '').trim();
    postCostCentreQuick(name, description, function (err) {
        if (err) {
            $('#quick_cost_centre_name_error').text(typeof err === 'string' ? err : 'Unable to save cost centre.');
        }
    });
});

 const OLD_CATEGORY = @json((string) $selectedProductCategoryId);
    const OLD_SUBCATEGORY = @json((string) $selectedProductSubCategoryId);
    const OLD_BRANCH = "{{ old('branch_id', $asset->branch_id) }}";
    const OLD_LOCATION = "{{ old('location_id', $asset->location_id) }}";

function loadBranches(companyId, selectedId = null, selectedLocationId = null) {
    const branchDropdown = $('#branch_id');
    branchDropdown.html('<option value="">Loading...</option>');

    if (!companyId) {
        branchDropdown.html('<option value="">Select Branch</option>');
        refreshSearchable(branchDropdown);
        return;
    }

    $.get(
        "{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId),
        function (res) {
            branchDropdown.empty().append('<option value="">Select Branch</option>');
            if (res.data && res.data.length > 0) {
                $.each(res.data, function (_, value) {
                    const sel = (selectedId != null && String(selectedId) === String(value.id)) ? 'selected' : '';
                    branchDropdown.append(
                        `<option value="${value.id}" ${sel}>${value.name}</option>`
                    );
                });
            } else {
                branchDropdown.append('<option value="">No branches found</option>');
            }
            refreshSearchable(branchDropdown);
            if (selectedId) {
                loadLocations(String(selectedId), selectedLocationId);
            }
        }
    ).fail(function () {
        branchDropdown.html('<option value="">Failed to load branches</option>');
        refreshSearchable(branchDropdown);
    });
}

$('#company_id').on('change', function () {
    clearLocationPicker();
    loadBranches(this.value, null, null);
});


    function loadProductSubCategories(categoryId, selectedId = null) {
        const subCategoryDropDown = $('#product_sub_category_id');
        subCategoryDropDown.html('<option>Loading...</option>');

        if (!categoryId) {
            subCategoryDropDown.html('<option value="">Select Product Sub Category</option>');
            refreshSearchable(subCategoryDropDown);
            return;
        }

        $.get("{{ route('ajax.getproductsubcategory.bycategory', ':id') }}".replace(':id', categoryId), function (res) {
            subCategoryDropDown.empty().append('<option value="">Select Product Sub Category</option>');

            $.each(res.data || [], function (_, value) {
                let selected = String(selectedId) === String(value.id) ? 'selected' : '';
                subCategoryDropDown.append(
                    `<option value="${value.id}" ${selected}>${value.sub_category_name}</option>`
                );
            });
            refreshSearchable(subCategoryDropDown);
        });
    }

    $('#product_category_id').on('change', function () {
        loadProductSubCategories(this.value);
    });


        function loadLocations(branchId, selectedId = null) {
        if (!branchId) {
            clearLocationPicker();
            return;
        }

        $.get("{{ route('ajax.getlocation.bybranch', ':id') }}".replace(':id', branchId), function (res) {
            applyLocationRows((res && res.data) ? res.data : [], selectedId);
        }).fail(function () {
            clearLocationPicker();
        });
        }

        $('#branch_id').on('change', function () {
            clearLocationPicker();
            loadLocations($(this).val(), null);
        });

    function toggleAssetStructure() {
        const $qty = $('#items_count');
        const isGroup = $('#asset_structure').val() === 'group';
        $qty.prop('required', true);
        if (isGroup) {
            $qty.prop('readonly', false).removeClass('bg-light').attr('min', 2).removeAttr('max');
            const current = parseInt($qty.val(), 10);
            if (!current || current < 2) {
                $qty.val(2);
            }
        } else {
            $qty.val(1).prop('readonly', true).addClass('bg-light').attr('min', 1).attr('max', 1);
        }
    }

    $('#asset_structure').on('change', toggleAssetStructure);
    $('#items_count').on('input', function () {
        if ($('#asset_structure').val() !== 'group') {
            $(this).val(1);
        }
    });

    function clearDepreciationRateError() {
        $('#salvage_value').removeClass('is-invalid');
        $('#salvage_value_error').addClass('d-none').text('');
    }

    function depreciationRateError() {
        if ($('#depreciation_method').val() !== 'reducing_balance') {
            clearDepreciationRateError();
            return '';
        }

        const raw = ($('#salvage_value').val() ?? '').toString().trim();
        if (raw === '') {
            return 'Rate of Depreciation (%) is required.';
        }

        const rate = Number(raw);
        if (!Number.isFinite(rate) || rate < 0 || rate > 100) {
            return 'Rate of Depreciation (%) must be between 0 and 100.';
        }

        clearDepreciationRateError();
        return '';
    }

    function showDepreciationRateError(message) {
        $('#salvage_value').addClass('is-invalid');
        $('#salvage_value_error').removeClass('d-none').text(message);
        const field = document.getElementById('salvage_value');
        if (field) {
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field.focus();
        }
    }

    function toggleDepreciationFields(){

        let method = $('#depreciation_method').val();

        // Reset everything
        $('#usefulLifeWrapper, #salvageWrapper').addClass('d-none');
        $('#useful_life').prop('required', false);

        if(method === 'straight_line'){
            $('#usefulLifeWrapper').removeClass('d-none');
            $('#salvageWrapper').removeClass('d-none');

            $('#salvageLabel').html('Salvage Value <span class="text-danger">*</span>');

            $('#useful_life').prop('required', true);
        }
        else if(method === 'reducing_balance'){
            $('#usefulLifeWrapper').addClass('d-none');
            $('#salvageWrapper').removeClass('d-none');

            $('#salvageLabel').html('Rate of Depreciation (%) <span class="text-danger">*</span>');
        } else {
            clearDepreciationRateError();
        }

    }

    $('#depreciation_method').on('change', toggleDepreciationFields);
    $('#salvage_value').on('input', function () {
        const message = depreciationRateError();
        if (message && !$('#salvage_value_error').hasClass('d-none')) {
            $('#salvage_value').addClass('is-invalid');
            $('#salvage_value_error').text(message);
        }
    });

$(document).ready(function () {

    initSearchableSelects();
    syncDisposedFields();
    rebuildSupplierDatalist();
    rebuildCustodianDatalist();

    $('select[name="status"]').on('change', syncDisposedFields);
    $('select[name="operational_status"], select[name="condition"]').on('change', function () {
        if ($('select[name="status"]').val() === 'disposed') {
            $(this).val('disposed');
            refreshSearchable($(this));
        }
    });

    toggleAssetStructure();
    toggleDepreciationFields();

    if ($('#company_id').val()) {
        loadBranches($('#company_id').val(), OLD_BRANCH, OLD_LOCATION);
    }

    syncSupplierHidden();
    syncCustodianHidden();

    if (OLD_CATEGORY) {
        loadProductSubCategories(OLD_CATEGORY, OLD_SUBCATEGORY);
    }

    $('#assetEditForm').on('submit', function (e) {
        syncSupplierHidden();
        syncCustodianHidden();
        syncLocationHidden();
        toggleAssetStructure();
        const rateError = depreciationRateError();
        if (rateError) {
            e.preventDefault();
            showDepreciationRateError(rateError);
            return false;
        }
        if (!$('#supplier').val()) {
            e.preventDefault();
            alert('Choose a supplier from the suggestions (matching name exactly), or type a new name and click +.');
            $('#supplier_display').trigger('focus');
            return false;
        }
        const typedCustodian = ($('#custodian_display').val() || '').trim();
        if (typedCustodian && !$('#custodian_id').val()) {
            e.preventDefault();
            alert('Choose an asset custodian from the suggestions (matching name exactly), or type a new name and click +.');
            $('#custodian_display').trigger('focus');
            return false;
        }
        const typedLocation = ($('#location_display').val() || '').trim();
        if (typedLocation && !$('#location_id').val()) {
            e.preventDefault();
            alert('Choose a physical location from the suggestions (matching name exactly), or type a new name and click +.');
            $('#location_display').trigger('focus');
            return false;
        }
        const typedCostCentre = ($('#cost_centre').val() || '').trim();
        if (typedCostCentre && !matchingCostCentre(typedCostCentre)) {
            e.preventDefault();
            alert('Choose a cost centre from the suggestions (matching name exactly), or type a new name and click +.');
            $('#cost_centre').trigger('focus');
            return false;
        }
        if (typedCostCentre) {
            $('#cost_centre').val(matchingCostCentre(typedCostCentre));
        }
        if (!$('#branch_id').val()) {
            e.preventDefault();
            alert('Select a branch for the selected company.');
            $('#branch_id').trigger('focus');
            return false;
        }
        return true;
    });

});

</script>

@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Asset Details')

@section('content')
@php
  $canManageAssets = auth()->user()?->canManageAssets() ?? false;
@endphp
<style>
  [id$="-section"] {
    scroll-margin-top: 5.5rem;
  }
</style>
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-sm-between mb-4 text-center text-sm-start gap-2">
  <div class="mb-2 mb-sm-0">
    <h4 class="mb-1">Asset Details</h4>
  </div>
  <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-end">
    @if ($canManageAssets)
    <a href="{{ route('asset.detail-export', $asset) }}" class="btn btn-outline-success btn-sm">
      <i class="bx bx-export me-1"></i> Excel
    </a>
    <a href="{{ route('asset.detail-export-pdf', $asset) }}" class="btn btn-outline-danger btn-sm">
      <i class="bx bxs-file-pdf me-1"></i> PDF
    </a>
    <a href="{{ route('asset.handover-export-pdf', $asset) }}" class="btn btn-outline-danger btn-sm">
      <i class="bx bxs-file-pdf me-1"></i> Asset Handover
    </a>
    @endif
    <a href="{{ route('asset.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
  </div>
</div>

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<div class="row">
  <div class="col-12">

    <div class="card mb-6">

      <div class="card-header d-flex flex-wrap justify-content-between gap-4">
        <div class="card-title mb-0 me-1">
          <div class="d-flex justify-content-start align-items-center flex-wrap gap-3 mb-2">
            <div class="d-flex flex-column">
              <h5 class="mb-1">{{ $asset->asset_name }}
                @if($asset->productCategory?->category_name)
                  <span class="badge bg-label-secondary">{{ $asset->productCategory->category_name }}</span>
                @endif
              </h5>
              {!! $asset->status_label ?? '--' !!}
            </div>
          </div>
        </div>

        <div class="d-flex flex-column align-items-center row-gap-2" id="asset-qr-block">
          <div id="asset-qr-preview">
            {!! QrCode::size(60)->generate($asset->asset_code) !!}
          </div>

          <span class="fw-semibold">{{ $asset->asset_code ?? '--' }}</span>

          @if (filled($asset->asset_code))
          <a href="{{ route('asset.print-label', $asset) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
            <i class="bx bx-printer me-1"></i> Print QR Code
          </a>
          @endif
        </div>
         
      </div>

      <div class="card-body">
        <div class="row">

          {{-- HSN Code --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-barcode"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">HSN Code</small>
                <span class="fw-normal">{{ $asset->hsn_code ?: ($asset->msuGrnItem?->hsn ?: '--') }}</span>
              </div>
            </div>
          </div>

          {{-- SL No --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-hash"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">SL No / Any ID's</small>
                <span class="fw-normal">{{ $asset->serial_no ?? '--' }}</span>
              </div>
            </div>
          </div>

          {{-- Asset Code --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-purchase-tag"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Asset Code</small>
                <span class="fw-normal">{{ $asset->asset_code ?? '--' }}</span>
              </div>
            </div>
          </div>

          {{-- Asset Structure --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-layer"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Asset Structure</small>
                <span class="fw-normal text-capitalize">
                  {{ $asset->asset_structure ?? '--' }}
                </span>
              </div>
            </div>
          </div>

          {{-- Qty --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-box"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Qty</small>
                <span class="fw-normal">{{ $asset->items_count ?: 1 }}</span>
              </div>
            </div>
          </div>

          {{-- UOM --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-cube"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">UOM</small>
                <span class="fw-normal">{{ $asset->uom ?: ($asset->msuGrnItem?->itemMaster?->unit?->unit_name ?: ($asset->msuGrnItem?->itemMaster?->uom ?: '--')) }}</span>
              </div>
            </div>
          </div>


          {{-- Operation Status --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-category"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Operational Status</small>
                <span class="fw-normal">
                  {!! $asset->operational_status_label !!}
                </span>
              </div>
            </div>
          </div>

          {{-- Product Category --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-category"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Product Categories</small>
                <span class="fw-normal">
                  {{
                      $asset->productCategory?->category_name
                          ?? $asset->msuGrnItem?->itemMaster?->productCategory?->category_name
                          ?? '--'
                  }}
                </span>
              </div>
            </div>
          </div>

          {{-- Product Sub Category --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-category-alt"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Product Sub Categories</small>
                <span class="fw-normal">
                  {{
                      $asset->productSubCategory?->sub_category_name
                          ?? $asset->msuGrnItem?->itemMaster?->productSubCategory?->sub_category_name
                          ?? '--'
                  }}
                </span>
              </div>
            </div>
          </div>

          {{-- Condition --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-shield"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Condition</small>
                <span>{!! $asset->condition_label ?? '--' !!}</span>
              </div>
            </div>
          </div>

           {{-- Company --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-store"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Company</small>
                <span class="fw-normal">{{ $asset->company->name ?? $asset->latestAssignment?->toCompany?->name ?? $asset->latestAssignment?->fromCompany?->name ?? '--' }}</span>
              </div>
            </div>
          </div>

          {{-- Branch --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-store"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Branch</small>
                <span class="fw-normal">{{ $asset->branch->name ?? $asset->latestAssignment?->toBranch?->name ?? $asset->latestAssignment?->fromBranch?->name ?? '--' }}</span>
              </div>
            </div>
          </div>

          {{-- Location --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-map"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Physical Location</small>
                <span class="fw-normal">{{ $asset->location->location_name ?? $asset->latestAssignment?->toBranchLocation?->location_name ?? $asset->latestAssignment?->fromBranchLocation?->location_name ?? '--' }}</span>
              </div>
            </div>
          </div>

          {{-- Purchase Date --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-calendar"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Purchase Date</small>
                <span class="fw-normal">
                  {{ optional($asset->purchase_date)->format('d M Y') ?? '--' }}
                </span>
              </div>
            </div>
          </div>

          @if ($canManageAssets)
          {{-- Purchase Cost --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-rupee"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Invoice Amount</small>
                <span class="fw-normal">
                  {{ number_format($asset->purchase_cost ?? 0, 2) }}
                </span>
              </div>
            </div>
          </div>

          {{-- Supplier --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-store"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Supplier</small>
                <span class="fw-normal">{{ $asset->vendor->name ?? '--' }}</span>
              </div>
            </div>
          </div>
          @endif

          @php
            $grn = $asset->grnItem?->grn;
            $po = $asset->purchaseOrder;
            $pr = $po?->purchaseRequisition;
            $indent = $asset->indent ?? $pr?->indent;
            $department = $asset->department ?? $indent?->department;
          @endphp

          @if ($grn || $po || $indent)
          <div class="col-12">
            <hr class="my-2">
            <h6 class="mb-3">Procurement Traceability</h6>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-package"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">GRN</small>
                <span class="fw-normal">
                  @if ($grn)
                    <a href="{{ route('grns.show', $grn) }}">{{ $grn->grn_no }}</a>
                  @else
                    --
                  @endif
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-cart"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Purchase Order</small>
                <span class="fw-normal">
                  @if ($po)
                    <a href="{{ route('purchase-orders.show', $po) }}">{{ $po->po_no }}</a>
                  @else
                    --
                  @endif
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-list-check"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Purchase Requisition</small>
                <span class="fw-normal">
                  @if ($pr)
                    <a href="{{ route('purchase-requisitions.show', $pr) }}">{{ $pr->pr_no }}</a>
                  @else
                    --
                  @endif
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-file"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Indent</small>
                <span class="fw-normal">
                  @if ($indent)
                    <a href="{{ route('indents.show', $indent) }}">{{ $indent->indent_no }}</a>
                  @else
                    --
                  @endif
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-group"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Department</small>
                <span class="fw-normal">{{ $department->department_name ?? '--' }}</span>
              </div>
            </div>
          </div>
          @endif

          {{-- Invoice --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-store"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Invoice No</small>
                <span class="fw-normal">{{ $asset->invoice_details ?? '--' }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calendar"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Invoice Date</small>
                <span class="fw-normal">{{ optional($asset->invoice_date ?: $asset->msuGrnItem?->grn?->vendor_invoice_date)->format('d M Y') ?: '--' }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-cart"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">PO Number</small>
                <span class="fw-normal">{{ $asset->po_number ?: ($asset->msuGrnItem?->grn?->purchaseOrder?->po_number ?: '--') }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calendar"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">PO Date</small>
                <span class="fw-normal">{{ optional($asset->po_date ?: $asset->msuGrnItem?->grn?->purchaseOrder?->po_date)->format('d M Y') ?: '--' }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-purchase-tag"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Type of Purchase</small>
                <span class="fw-normal">{{ $asset->purchaseTypeLabel() }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-category"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Asset Type</small>
                <span class="fw-normal">{{ $asset->ownershipTypeLabel() }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-buildings"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Cost Centre</small>
                <span class="fw-normal">{{ $asset->cost_centre ?: '--' }}</span>
              </div>
            </div>
          </div>

          @if ($canManageAssets)
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Asset Custodian</small>
                <span class="fw-normal">{{ $asset->custodian?->employee_name ?: '--' }}</span>
              </div>
            </div>
          </div>
          @endif

          @if (! ($grn || $po || $indent))
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-group"></i></span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Department</small>
                <span class="fw-normal">{{ $asset->department?->department_name ?? '--' }}</span>
              </div>
            </div>
          </div>
          @endif

          {{-- Depreciation --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-line-chart"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Depreciation Method</small>
                <span class="fw-normal text-capitalize">
                  {{ str_replace('_',' ', $asset->depreciation_method ?? '--') }}
                </span>
              </div>
            </div>
          </div>

          @if($asset->depreciation_method == 'reducing_balance')
          {{-- Salvage --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-recycle"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Salvage Value / Rate</small>
                <span class="fw-normal">{{ $asset->salvage_value ?? '--' }}</span>
              </div>
            </div>
          </div>
          @endif

          @if ($canManageAssets)
          {{-- Current Value: reducing balance on invoice amount, or invoice amount when there is no depreciation --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="bx bx-rupee"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Current Value</small>
                <span class="fw-normal">{{ number_format($asset->current_value ?? 0, 2) }}</span>
              </div>
            </div>
          </div>
          @endif

          {{-- Audit --}}
          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-user-check"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Audit By</small>
                <span class="fw-normal">{{ $asset->auditByLabel() }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-time-five"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Audit On</small>
                <span class="fw-normal">{{ $asset->auditOnLabel() }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-check-shield"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block mb-1">Audit Status</small>
                <span class="fw-normal">{!! $asset->auditStatusBadge() !!}</span>
              </div>
            </div>
          </div>

          {{-- Description + small image (same compact style as assets list name column) --}}
          <div class="col-md-3">
            <div class="d-flex align-items-start mb-4">
              <div class="avatar flex-shrink-0 me-3 mt-1">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-detail"></i>
                </span>
              </div>
              <div class="flex-grow-1 min-w-0">
                <small class="text-muted d-block mb-1">Notes</small>
                <span class="fw-normal">{{ $asset->description ?: ($asset->latestAudit?->remarks ?: ($asset->audits?->first()?->remarks ?: '--')) }}</span>
              </div>
              @if($asset->icon == 'images' && $asset->doc_url)
              <div class="avatar-wrapper flex-shrink-0 ms-3 mt-1">
                <div class="avatar rounded-2 bg-label-secondary" style="width: 40px; height: 40px;">
                  <a href="{{ $asset->doc_url }}" data-lightbox="asset-gallery" class="d-block w-100 h-100">
                    <img src="{{ $asset->doc_url }}" alt="{{ $asset->asset_name }}" class="rounded w-100 h-100" style="object-fit: cover;">
                  </a>
                </div>
              </div>
              @endif
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>



<div class="row">
  

  <!-- Customer Content -->
  <div class="col-md-12 order-0 order-md-1">
    <!-- Customer Pills -->
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 row-gap-2 flex-wrap">
        @can('asset.assignment:view')
        <li class="nav-item">
          <a class="nav-link @if($tab == 'assignments') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'assignments']) }}#assignments-section"><i class="icon-base bx bx-purchase-tag icon-18px me-1_5"></i> Assignments</a>
        </li>
        @endcan
         @can('asset.document:view')
        <li class="nav-item">
          <a class="nav-link @if($tab == 'documents') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'documents']) }}#documents-section"><i class="icon-base bx bx-food-menu icon-18px me-1_5"></i> Documents</a>
        </li>
        @endcan
         @can('asset.warranty:view')
        <li class="nav-item">
          <a class="nav-link @if($tab == 'warranties') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'warranties']) }}#warranties-section"><i class="icon-base bx bx-shield-quarter icon-18px me-1_5"></i> Warranty</a>
        </li>
        @endcan
         @can('asset.maintenance:view')
        <li class="nav-item">
          <a class="nav-link @if($tab == 'maintenance') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'maintenance']) }}#maintenance-section"><i class="icon-base bx bx-timer icon-18px me-1_5"></i> Maintenance & Repair</a>
        </li>
        @endcan
         @can('asset.replacement:view')
        <li class="nav-item">
          <a class="nav-link @if($tab == 'replacements') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'replacements']) }}#replacements-section"><i class="icon-base bx bx-receipt icon-18px me-1_5"></i> Replacement</a>
        </li>
        @endcan
        @canany(['asset.audit:view', 'asset:view'])
        <li class="nav-item">
          <a class="nav-link @if($tab == 'audits') active @endif" href="{{ route('asset.show', ['asset' => $asset->id, 'tab' => 'audits']) }}#audits-section"><i class="icon-base bx bx-check-shield icon-18px me-1_5"></i> Audit History</a>
        </li>
        @endcanany
      </ul>
    </div>
    <!--/ Customer Pills -->

    <div id="{{ ($tab ?? 'assignments') }}-section">
      @yield('asset_section')
    </div>

  </div>
  <!--/ Customer Content -->
</div>

@php
    $assetScrollTab = $scrollToTabSection ?? $scrollToSection ?? null;
@endphp
<script>
(function () {
    const navbarOffset = 88;
    const scrollTab = @json($assetScrollTab);
    const scrollTabSectionId = scrollTab ? (scrollTab + '-section') : null;

    function scrollToElement(el) {
        if (!el) {
            return;
        }
        const top = el.getBoundingClientRect().top + window.scrollY - navbarOffset;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }

    function scheduleScroll(target) {
        const run = function () {
            if (typeof target === 'string') {
                scrollToElement(document.getElementById(target));
            } else {
                scrollToElement(target);
            }
        };
        run();
        [100, 250, 500, 900, 1200].forEach(function (delay) {
            setTimeout(run, delay);
        });
    }

    function runAssetSectionScroll() {
        if (scrollTabSectionId) {
            scheduleScroll(scrollTabSectionId);
            return;
        }
        if (window.location.hash) {
            const hashEl = document.querySelector(window.location.hash);
            if (hashEl) {
                scheduleScroll(hashEl);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAssetSectionScroll);
    } else {
        runAssetSectionScroll();
    }
    window.addEventListener('load', runAssetSectionScroll);
})();
</script>

@endsection

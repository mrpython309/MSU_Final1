@extends('layouts/contentNavbarLayout')

@section('title', 'Assets')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
@endsection

@section('page-style')
@include('partials.content-zoom-style')
    <style>
        .avatar-initial {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        .fs-12px { font-size: 0.75rem !important; }
        .form-container {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }
        #dealerLocationsTable .asset-desc-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.35;
            width: 320px;
            max-width: 320px;
        }
    </style>
@endsection

@section('content')
@include('partials.content-zoom-open')
@php
    $canManageAssets = auth()->user()?->canManageAssets() ?? false;
@endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Assets</h4>
      </div>
    </div>

    {{-- Search & Filter Container --}}
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0">Quick Filters</h5>
        </div>
        <div class="card-body pt-2">
            <form method="GET" action="{{ route('asset.index') }}" class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Asset code, description, invoice no, PO number">
                </div>

                <div class="col-md-3">
            <label class="form-label">Company</label>
            <select id="company_id" name="company_id"
                    class="form-select select2 @error('company_id') is-invalid @enderror">
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                         {{ request('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            
        </div>

<div class="col-md-3">
            <label class="form-label">Branch </label>
            <select id="branch_id" name="branch_id" 
                    class="form-select select2 @error('branch_id') is-invalid @enderror">
                <option value="">Select Branch</option>  
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}"
                         {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach               
            </select>
           
        </div>


                 <div class="col-md-3">
                    <label class="form-label">Product Categories</label>
                    <select class="form-select select2" name="product_category_id" id="product_category_id">
                        <option value="">All Product Categories</option>
                        @foreach($productCategories as $category)
                            <option value="{{ $category->id }}" {{ request('product_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Product Sub Categories</label>
                    <select class="form-select select2" name="product_sub_category_id" id="product_sub_category_id">
                        <option value="">All Product Sub Categories</option>
                    </select>
                </div>

                 <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                         
                    </select>
                </div>

                 <div class="col-md-3">
                    <label class="form-label">Conditions</label>
                    <select class="form-select" name="condition">
                        <option value="">All Conditions</option>
                       @foreach ($conditions as $condition)
        <option value="{{ $condition->value }}" {{ request('condition') == $condition->value ? 'selected' : '' }}>
            {{ $condition->label() }}
        </option>
    @endforeach
                    </select>
                </div>


                 <div class="col-md-3">
                        <label class="form-label form-label-required">Purchase Date From  </label>
                        <input
                            type="date"
                            name="purchase_date_from"
                            class="form-control"
                            value="{{ old('purchase_date_from', request('purchase_date_from')) }}"
                           
                        >
                        
                    </div>

                     <div class="col-md-3">
                        <label class="form-label form-label-required">Purchase Date To </label>
                        <input
                            type="date"
                            name="purchase_date_to"
                            class="form-control"
                            value="{{ old('purchase_date_to', request('purchase_date_to')) }}"                           
                        >
                         
                    </div>

                @if ($canManageAssets)
                <div class="col-md-3">
                    <label class="form-label">Asset Custodian</label>
                    <select class="form-select select2" name="custodian_id">
                        <option value="">All Custodians</option>
                        @foreach ($custodians as $custodian)
                            <option value="{{ $custodian->id }}" {{ (string) request('custodian_id') === (string) $custodian->id ? 'selected' : '' }}>
                                {{ $custodian->employee_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label" for="per_page">Per Page</label>
                    <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 50) === (string) $size)>{{ $size }}</option>
                        @endforeach
                        <option value="all" @selected(request('per_page') === 'all')>All</option>
                    </select>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Assets List</h5>
            </div>
            <div class="d-flex gap-2">
                @if ($canManageAssets)
                <a href="{{ route('asset.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
                    <i class="bx bx-export me-1"></i> Excel
                </a>
                <a href="{{ route('asset.export-pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bx bxs-file-pdf me-1"></i> PDF
                </a>
                @endif
                @can('asset:create')

                <a href="{{ route('asset.import') }}" class="btn btn-primary btn-sm"> <i class="bx bx-import me-1"></i> Import Asset</a>


                <a href="{{ route('asset.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Add Asset
                </a>
                @endcan
            </div>
        </div>

        <div class="table-responsive">
            <table class="table card-table " id="dealerLocationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Actions</th>
                        <th>Asset Code</th>
                        <th>Asset Description</th>
                        <th>Branch</th>
                        <th>Physical Location</th>
                        <th>Product Categories</th>
                        <th>Product Sub Categories</th>
                        <th>Status</th>
                        <th>Operational Status</th>
                        @if ($canManageAssets)
                        <th>Asset Custodian</th>
                        @endif
                        <th>Purchase Date</th>
                        @if ($canManageAssets)
                        <th>Invoice Amount</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr>
                        <td>{{ $assets->firstItem() + $loop->index }}.</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 btn-sm" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'>
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('asset.show', $asset->id) }}">
                                            <i class="bx bx-show me-1"></i> View
                                        </a>
                                    </li>
                                    @if ($asset->isEditable())
                                        @can('asset:edit')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('asset.edit', $asset->id) }}">
                                                    <i class="bx bx-edit me-1"></i> Edit
                                                </a>
                                            </li>
                                        @endcan
                                        @can('asset:delete')
                                            <li>
                                                <form method="POST"
                                                    action="{{ route('asset.destroy', $asset->id) }}"
                                                    class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                        <td>{{ $asset->asset_code }}</td>
                        <td>
                            <div class="d-flex justify-content-start align-items-center customer-name">
                                @if($asset->icon == 'images')
                                <div class="avatar-wrapper">
                                    <div class="avatar me-4 rounded-2 bg-label-secondary">
                                        <a href="{{ $asset->doc_url }}" data-lightbox="gallery">
                                            <img src="{{ $asset->doc_url }}" alt="{{ $asset->asset_name }}" class="rounded">
                                        </a>
                                    </div>
                                </div>
                                @endif
                                <span class="fw-medium text-heading asset-desc-text" title="{{ $asset->asset_name }}">{{ $asset->asset_name }}</span>
                            </div>
                        </td>

                        <td>
                            @php
                                $branchName = $asset->branch->name
                                    ?? $asset->latestAssignment?->toBranch?->name
                                    ?? $asset->latestAssignment?->fromBranch?->name
                                    ?? null;
                            @endphp
                            {{ $branchName ?: '--' }}
                        </td>

                        <td>
                            @php
                                $locationName = $asset->location?->location_name
                                    ?? $asset->latestAssignment?->toBranchLocation?->location_name
                                    ?? $asset->latestAssignment?->fromBranchLocation?->location_name
                                    ?? null;
                            @endphp
                            {{ $locationName ?: '--' }}
                        </td>

                        <td>{{ $asset->productCategory?->category_name ?: '--' }}</td>
                        <td>{{ $asset->productSubCategory?->sub_category_name ?: '--' }}</td>

                        <td>
                            @php $displayAssetStatus = $asset->effective_status; @endphp
                            @if($displayAssetStatus)
                            <span class="badge bg-label-{{ $displayAssetStatus->color() }}">
                                {{ $displayAssetStatus->label() }}
                            </span>
                            @endif
                        </td>

                        <td>{!! $asset->operational_status_label ?? '--' !!}</td>

                        @if ($canManageAssets)
                        <td>{{ $asset->custodian?->employee_name ?: '--' }}</td>
                        @endif

                        <td>
                            {{ optional($asset->purchase_date)->format('d M Y') }}
                        </td>

                        @if ($canManageAssets)
                        <td>
                            ₹ {{ number_format($asset->purchase_cost, 2) }}
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canManageAssets ? 13 : 11 }}" class="text-center py-5 text-muted">
                            No assets found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">Showing {{ $assets->firstItem() ?? 0 }} to {{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} assets</div>
            @if($assets->hasPages())
                <div>{{ $assets->links() }}</div>
            @endif
        </div>
    </div>
@include('partials.content-zoom-close')
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@section('page-script')
<script>

 function refreshSearch($el) {
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.trigger('change.select2');
            }
        }

        function initFilterSearch() {
            $('.select2').each(function () {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                $select.select2({
                    placeholder: $select.find('option[value=""]').first().text() || 'Select',
                    allowClear: true,
                    closeOnSelect: true,
                    width: '100%',
                    minimumResultsForSearch: 0,
                    dropdownParent: $(document.body)
                });
            });
        }

 function loadBranches(companyId, selectedId = null) {
            const branchDropdown = $('#branch_id');
            branchDropdown.html('<option>Loading...</option>');

            if (!companyId) {
                branchDropdown.html('<option value="">Select Branch</option>');
                refreshSearch(branchDropdown);
                return;
            }

            $.get(
                "{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId),
                function (res) {
                    branchDropdown.empty().append('<option value="">Select Branch</option>');

                    if (res.data && res.data.length > 0) {
                        $.each(res.data, function (_, value) {
                            let selected = selectedId == value.id ? 'selected' : '';
                            branchDropdown.append(
                                `<option value="${value.id}" ${selected}>${value.name}</option>`
                            );
                        });
                    } else {
                        branchDropdown.append('<option value="">No branches found</option>');
                    }
                    refreshSearch(branchDropdown);
                }
            ).fail(function () {
                branchDropdown.html('<option value="">Failed to load branches</option>');
                refreshSearch(branchDropdown);
            });
        }

        $('#company_id').on('change', function () {
            loadBranches(this.value);
        });

        const PRODUCT_SUB_CATEGORIES = @json($productSubCategories);
        const SELECTED_PRODUCT_SUB = @json((string) request('product_sub_category_id'));

        function fillProductSubCategories(categoryId, selectedId) {
            const $sub = $('#product_sub_category_id');
            $sub.empty().append('<option value="">All Product Sub Categories</option>');
            PRODUCT_SUB_CATEGORIES.forEach(function (row) {
                if (categoryId && String(row.product_category_id) !== String(categoryId)) {
                    return;
                }
                const selected = String(selectedId) === String(row.id) ? ' selected' : '';
                $sub.append('<option value="' + row.id + '"' + selected + '>' + row.sub_category_name + '</option>');
            });
            refreshSearch($sub);
        }

        fillProductSubCategories($('#product_category_id').val(), SELECTED_PRODUCT_SUB);
        initFilterSearch();

        $('#product_category_id').on('change', function () {
            fillProductSubCategories(this.value, '');
        });

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

$(function() {
    const table = $('#dealerLocationsTable').DataTable({
        paging: false,
        lengthChange: false,
        info: false,
        responsive: true,
        dom: @json($canManageAssets ? 'Bfrtip' : 'frtip'),
        language: { search: "Search assets:" },
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 1 }],
        buttons: @json($canManageAssets ? ['excel', 'pdf'] : [])
    });

    
});
</script>
@include('partials.content-zoom-script', ['zoomStorageKey' => 'asset-content-zoom'])
@endsection

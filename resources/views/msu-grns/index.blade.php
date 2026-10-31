@extends('layouts/contentNavbarLayout')

@section('title', 'Goods Receipt Notes')

@section('page-style')
@include('partials.content-zoom-style')
@endsection

@section('content')
@php
    $viewMode = $viewMode ?? 'main';
    $filterQuery = request()->except('page');
    $msuGrnApproveUrl = function ($grn) {
        return \Illuminate\Support\Facades\Route::has('msu-grns.approve-asset')
            ? route('msu-grns.approve-asset', $grn)
            : route('msu-grns.add-to-stock', $grn);
    };
    $msuGrnCancelUrl = function ($grn) {
        return \Illuminate\Support\Facades\Route::has('msu-grns.cancel-asset')
            ? route('msu-grns.cancel-asset', $grn)
            : route('msu-grns.add-to-stock', $grn);
    };
@endphp
@include('partials.content-zoom-open')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Goods Receipt Notes</h4>
    <div class="d-flex gap-2">
        @can('msu-grn:view')
        <a href="{{ route('msu-grns.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bx bx-export me-1"></i> Excel
        </a>
        <a href="{{ route('msu-grns.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @endcan
        @can('msu-grn:create')
        <a href="{{ route('msu-grns.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Create GRN
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('msu-grns.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select select2">
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (string) request('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->company_code ? $vendor->company_code.' - ' : '' }}{{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select select2">
                    <option value="">Select Item</option>
                    @foreach($filterItems as $filterItem)
                        <option value="{{ $filterItem->id }}" {{ (string) request('item_id') === (string) $filterItem->id ? 'selected' : '' }}>
                            {{ $filterItem->item_code ? $filterItem->item_code.' - ' : '' }}{{ $filterItem->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select select2">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label">Common Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search', request('grn_number')) }}" placeholder="GRN Number, PO Number, Vendor Invoice Number">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2 col-lg-1">
                <label class="form-label">Per Page</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    <option value="10" {{ request('per_page', $perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page', $perPage ?? 10) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page', $perPage ?? 10) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', $perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                    <option value="1000000" {{ request('per_page', $perPage ?? 10) == 1000000 ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <button class="btn btn-primary me-1">Filter</button>
                <a href="{{ route('msu-grns.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">{{ $viewMode === 'item' ? 'Item View' : 'Main View' }}</h5>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('msu-grns.index', array_merge($filterQuery, ['view' => 'main'])) }}"
               class="btn btn-sm {{ $viewMode === 'main' ? 'btn-primary' : 'btn-outline-primary' }}">Main View</a>
            <a href="{{ route('msu-grns.index', array_merge($filterQuery, ['view' => 'item'])) }}"
               class="btn btn-sm {{ $viewMode === 'item' ? 'btn-primary' : 'btn-outline-primary' }}">Item View</a>
        </div>
    </div>

    @if($viewMode === 'item')
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-center">Actions</th>
                        <th>GRN Number</th>
                        <th>GRN Date</th>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th class="text-end">Ordered</th>
                        <th class="text-end">Prev. Received</th>
                        <th class="text-end">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $grn = $item->grn; @endphp
                        <tr>
                            <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($grn)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('msu-grn:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-grns.show', $grn) }}">
                                                <i class="bx bx-show me-1"></i> View GRN
                                            </a>
                                        </li>
                                        @endcan
                                        @canany(['msu-grn:edit', 'msu-grn:create'])
                                        @if($grn->canEditClassification())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-grns.edit', $grn) }}">
                                                <i class="bx bx-edit me-1"></i> Edit
                                            </a>
                                        </li>
                                        @endif
                                        @endcanany
                                        @can('msu-grn:approve')
                                        @if($grn->canApproveAsset())
                                        <li>
                                            <form action="{{ $msuGrnApproveUrl($grn) }}" method="POST" id="approve-asset-form-{{ $grn->id }}">
                                                @csrf
                                                <input type="hidden" name="asset_action" value="approve">
                                                <button type="button" class="dropdown-item text-success" onclick="confirmApproveAsset({{ $grn->id }})">
                                                    <i class="bx bx-check-circle me-1"></i> Approve
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @endcan
                                        @can('msu-grn:cancel')
                                        @if($grn->canCancelAsset())
                                        <li>
                                            <form action="{{ $msuGrnCancelUrl($grn) }}" method="POST" id="cancel-asset-form-{{ $grn->id }}">
                                                @csrf
                                                <input type="hidden" name="asset_action" value="cancel">
                                                <button type="button" class="dropdown-item text-warning" onclick="confirmCancelAsset({{ $grn->id }})">
                                                    <i class="bx bx-x-circle me-1"></i> Cancel
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td><strong>{{ $grn?->grn_number }}</strong></td>
                            <td>{{ $grn?->grn_date?->format('d-m-Y') }}</td>
                            <td>{{ $grn?->purchaseOrder?->po_number ?? '--' }}</td>
                            <td>{{ $grn?->vendor?->name ?? '--' }}</td>
                            <td>{{ $item->item_code }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td class="text-end">{{ number_format((float) $item->ordered_quantity, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->previously_received_quantity, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->received_quantity, 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center py-4 text-muted">No GRN items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="card-footer">{{ $items->links() }}</div>
        @endif
    @else
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-center">Actions</th>
                        <th>GRN Number</th>
                        <th>GRN Date</th>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Department</th>
                        <th>Invoice No</th>
                        <th class="text-end">Items</th>
                        <th>Status</th>
                        <th>Capex / Opex</th>
                        <th>Asset Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                    <tr>
                        <td>{{ ($grns->currentPage() - 1) * $grns->perPage() + $loop->iteration }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'>
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    @can('msu-grn:view')
                                    <li><a class="dropdown-item" href="{{ route('msu-grns.show', $grn) }}"><i class="bx bx-show me-1"></i> View</a></li>
                                    @endcan
                                    @canany(['msu-grn:edit', 'msu-grn:create'])
                                    @if($grn->canEditClassification())
                                    <li><a class="dropdown-item" href="{{ route('msu-grns.edit', $grn) }}"><i class="bx bx-edit me-1"></i> Edit</a></li>
                                    @endif
                                    @endcanany
                                    @can('msu-grn:view')
                                    <li><a class="dropdown-item" href="{{ route('msu-grns.excel', $grn) }}"><i class="bx bx-export me-1"></i> Excel</a></li>
                                    <li><a class="dropdown-item" href="{{ route('msu-grns.pdf', $grn) }}"><i class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                                    @endcan
                                    @can('msu-grn:approve')
                                    @if($grn->canApproveAsset())
                                    <li>
                                        <form action="{{ $msuGrnApproveUrl($grn) }}" method="POST" id="approve-asset-form-{{ $grn->id }}">
                                            @csrf
                                            <input type="hidden" name="asset_action" value="approve">
                                            <button type="button" class="dropdown-item text-success" onclick="confirmApproveAsset({{ $grn->id }})">
                                                <i class="bx bx-check-circle me-1"></i> Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-grn:cancel')
                                    @if($grn->canCancelAsset())
                                    <li>
                                        <form action="{{ $msuGrnCancelUrl($grn) }}" method="POST" id="cancel-asset-form-{{ $grn->id }}">
                                            @csrf
                                            <input type="hidden" name="asset_action" value="cancel">
                                            <button type="button" class="dropdown-item text-warning" onclick="confirmCancelAsset({{ $grn->id }})">
                                                <i class="bx bx-x-circle me-1"></i> Cancel
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-grn:delete')
                                    @if($grn->statusEnum()->canDelete())
                                    <li>
                                        <form action="{{ route('msu-grns.destroy', $grn) }}" method="POST" id="delete-grn-{{ $grn->id }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="dropdown-item text-danger" onclick="confirmDeleteGrn({{ $grn->id }})">
                                                <i class="bx bx-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                        <td><strong>{{ $grn->grn_number }}</strong></td>
                        <td>{{ $grn->grn_date?->format('d-m-Y') }}</td>
                        <td>{{ $grn->purchaseOrder?->po_number ?? '--' }}</td>
                        <td>{{ $grn->vendor?->name ?? '--' }}</td>
                        <td>{{ $grn->department?->department_name ?? '--' }}</td>
                        <td>{{ $grn->vendor_invoice_number ?: '--' }}</td>
                        <td class="text-end">{{ $grn->items->count() }}</td>
                        <td>
                            <span class="badge bg-label-{{ $grn->statusEnum()->badgeClass() }}">{{ $grn->statusEnum()->label() }}</span>
                        </td>
                        <td>
                            @if($grn->expenseTypeLabel() !== '--')
                                <span class="badge bg-label-{{ $grn->isCapex() ? 'info' : 'secondary' }}">{{ $grn->expenseTypeLabel() }}</span>
                            @else
                                --
                            @endif
                        </td>
                        <td>
                            @if($grn->isCapex())
                            <span class="badge bg-label-{{ $grn->assetStatusEnum()->badgeClass() }}">{{ $grn->assetStatusEnum()->label() }}</span>
                            @else
                            --
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center py-4 text-muted">No GRNs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($grns->hasPages())
        <div class="card-footer">{{ $grns->links() }}</div>
        @endif
    @endif
</div>
@include('partials.content-zoom-close')
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    if (typeof $.fn.select2 === 'function') {
        $('select.select2').select2({
            placeholder: 'Select',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    }
});
function confirmDeleteGrn(id) {
    Swal.fire({ title: 'Delete this GRN?', text: 'PO received quantities will be reverted.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete' })
        .then(r => { if (r.isConfirmed) document.getElementById('delete-grn-' + id).submit(); });
}
function confirmApproveAsset(id) {
    Swal.fire({
        title: 'Approve asset conversion?',
        text: 'Approved items will be added to assets.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, approve'
    }).then(r => {
        if (r.isConfirmed) document.getElementById('approve-asset-form-' + id).submit();
    });
}
function confirmCancelAsset(id) {
    Swal.fire({
        title: 'Cancel asset conversion?',
        text: 'Please provide a reason for cancellation (mandatory). Items will not be added to assets.',
        input: 'text',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write something!';
            }
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Yes, cancel'
    }).then(r => {
        if (r.isConfirmed) {
            let form = document.getElementById('cancel-asset-form-' + id);
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cancel_reason';
            input.value = r.value;
            form.appendChild(input);
            form.submit();
        }
    });
}
</script>
@include('partials.content-zoom-script', ['zoomStorageKey' => 'msu-grn-content-zoom'])
@endsection

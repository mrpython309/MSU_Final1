@extends('layouts/contentNavbarLayout')

@section('title', 'New Purchase Orders')

@section('page-style')
@include('partials.content-zoom-style')
@endsection

@section('content')
@php
    $viewMode = $viewMode ?? 'main';
    $filterQuery = request()->except('page');
@endphp
@include('partials.content-zoom-open')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">New Purchase Orders</h4>
    <div class="d-flex gap-2">
        @can('msu-purchase-order:view')
        <a href="{{ route('msu-purchase-orders.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bx bx-export me-1"></i> Excel
        </a>
        <a href="{{ route('msu-purchase-orders.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @endcan
        @can('msu-purchase-order:create')
        <a href="{{ route('msu-purchase-orders.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Create PO
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('msu-purchase-orders.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-md-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->code ? $branch->code.' - ' : '' }}{{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-3">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select select2">
                    <option value="">Select Item</option>
                    @foreach($filterItems as $item)
                        <option value="{{ $item->id }}" {{ (string) request('item_id') === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->item_code ? $item->item_code.' - ' : '' }}{{ $item->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Common Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search', request('po_number')) }}" placeholder="PO Number, Vendor Invoice Number">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select select2">
                    <option value="">All</option>
                    @foreach(($statuses ?? []) as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Per Page</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    <option value="10" {{ request('per_page', $perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page', $perPage ?? 10) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page', $perPage ?? 10) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', $perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                    <option value="1000000" {{ request('per_page', $perPage ?? 10) == 1000000 ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary me-1">Filter</button>
                <a href="{{ route('msu-purchase-orders.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
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
            <a href="{{ route('msu-purchase-orders.index', array_merge($filterQuery, ['view' => 'main'])) }}"
               class="btn btn-sm {{ $viewMode === 'main' ? 'btn-primary' : 'btn-outline-primary' }}">
                Main View
            </a>
            <a href="{{ route('msu-purchase-orders.index', array_merge($filterQuery, ['view' => 'item'])) }}"
               class="btn btn-sm {{ $viewMode === 'item' ? 'btn-primary' : 'btn-outline-primary' }}">
                Item View
            </a>
        </div>
    </div>

    @if($viewMode === 'item')
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th class="text-center" style="width:80px">Actions</th>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Vendor</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>HSN</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Gross Value</th>
                        <th class="text-end">Tax %</th>
                        <th class="text-end">Tax Value</th>
                        <th class="text-end">Net Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $po = $item->purchaseOrder; @endphp
                        <tr>
                            <td class="text-muted">{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($po)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('msu-purchase-order:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-purchase-orders.show', $po) }}">
                                                <i class="bx bx-show me-1"></i> View PO
                                            </a>
                                        </li>
                                        @endcan
                                        @can('msu-purchase-order:edit')
                                        @if($po->statusEnum()->isEditable())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-purchase-orders.edit', $po) }}">
                                                <i class="bx bx-edit-alt me-1"></i> Edit PO
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td><strong>{{ $po?->po_number ?? '--' }}</strong></td>
                            <td>{{ $po?->po_date?->format('d-m-Y') ?? '--' }}</td>
                            <td>{{ $po?->vendor?->name ?? '--' }}</td>
                            <td>{{ $item->item_code ?: '--' }}</td>
                            <td>{{ $item->item_name ?: '--' }}</td>
                            <td>{{ $item->hsn ?: '--' }}</td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->gross_value, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->tax, 2) }}</td>
                            <td class="text-end">{{ number_format($item->tax_value, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->net_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-4 text-muted">No PO items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </div>
            <div>{{ $items->links() }}</div>
        </div>
        @endif
    @else
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th class="text-center" style="width:80px">Actions</th>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Vendor</th>
                        <th>Branch</th>
                        <th>Payment Terms</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Grand Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                    <tr>
                        <td class="text-muted">
                            {{ ($purchaseOrders->currentPage() - 1) * $purchaseOrders->perPage() + $loop->iteration }}
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    @can('msu-purchase-order:view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-purchase-orders.show', $po) }}">
                                            <i class="bx bx-show me-1"></i> View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-purchase-orders.export', ['po_number' => $po->po_number, 'view' => 'main']) }}">
                                            <i class="bx bx-export me-1"></i> Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-purchase-orders.pdf', $po) }}">
                                            <i class="bx bxs-file-pdf me-1"></i> PDF
                                        </a>
                                    </li>
                                    @endcan
                                    @can('msu-purchase-order:edit')
                                    @if($po->statusEnum()->isEditable())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-purchase-orders.edit', $po) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-purchase-order:approve')
                                    @if($po->statusEnum() === \App\Enums\Procurement\MsuPurchaseOrderStatus::PoCreated)
                                    <li>
                                        <form action="{{ route('msu-purchase-orders.approve', $po) }}" method="POST" class="d-inline-block w-100" onsubmit="return confirm('Approve this purchase order?');">
                                            @csrf
                                            <button type="submit" class="dropdown-item w-100 text-start">
                                                <i class="bx bx-check me-1 text-success"></i> Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-purchase-order:cancel')
                                    @if($po->statusEnum()->canCancel())
                                    <li>
                                        <form action="{{ route('msu-purchase-orders.cancel', $po) }}" method="POST" id="cancel-form-{{ $po->id }}">
                                            @csrf
                                            <button type="button" class="dropdown-item text-warning" onclick="confirmCancel({{ $po->id }})">
                                                <i class="bx bx-x-circle me-1"></i> Cancel PO
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-purchase-order:delete')
                                    @if($po->statusEnum()->isEditable() && $po->grns()->doesntExist())
                                    <li>
                                        <form action="{{ route('msu-purchase-orders.destroy', $po) }}" method="POST" id="delete-form-{{ $po->id }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="dropdown-item text-danger" onclick="confirmDelete({{ $po->id }})">
                                                <i class="bx bx-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                        <td><strong>{{ $po->po_number }}</strong></td>
                        <td>{{ $po->po_date?->format('d-m-Y') }}</td>
                        <td>{{ $po->vendor?->name ?? '--' }}</td>
                        <td>{{ $po->branch?->name ?? '--' }}</td>
                        <td>{{ $po->paymentTerm?->term_name ?? '--' }}</td>
                        <td class="text-end">{{ $po->items->count() }}</td>
                        <td class="text-end">{{ number_format((float) $po->grand_total, 2) }}</td>
                        <td>
                            <span class="badge bg-label-{{ $po->statusEnum()->badgeClass() }}">
                                {{ $po->statusEnum()->label() }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No purchase orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchaseOrders->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $purchaseOrders->firstItem() ?? 0 }} to {{ $purchaseOrders->lastItem() ?? 0 }} of {{ $purchaseOrders->total() }}
            </div>
            <div>{{ $purchaseOrders->links() }}</div>
        </div>
        @endif
    @endif
</div>
@include('partials.content-zoom-close')
@endsection

@section('page-script')
<script>
function confirmDelete(id) {
    Swal.fire({ title: 'Delete this PO?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete' })
        .then(r => { if (r.isConfirmed) document.getElementById('delete-form-' + id).submit(); });
}
function confirmCancel(id) {
    Swal.fire({
        title: 'Cancel this PO?',
        text: 'Please provide a reason for cancellation (mandatory):',
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
            let form = document.getElementById('cancel-form-' + id);
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
@include('partials.content-zoom-script', ['zoomStorageKey' => 'msu-po-content-zoom'])
@endsection

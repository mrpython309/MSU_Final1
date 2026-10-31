@extends('layouts/contentNavbarLayout')

@section('title', 'New Work Orders')

@section('content')
@php
    $viewMode = $viewMode ?? 'main';
    $filterQuery = request()->except('page');
@endphp
@include('partials.content-zoom-open')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">New Work Orders</h4>
    <div class="d-flex gap-2">
        @can('msu-work-order:view')
        <a href="{{ route('msu-work-orders.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bx bx-export me-1"></i> Excel
        </a>
        <a href="{{ route('msu-work-orders.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @endcan
        @can('msu-work-order:create')
        <a href="{{ route('msu-work-orders.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Create WO
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('msu-work-orders.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Work Order</label>
                <select name="work_order_id" class="form-select select2">
                    <option value="">Select Work Order</option>
                    @foreach(($workOrderOptions ?? collect()) as $workOrderOption)
                        <option value="{{ $workOrderOption->id }}" {{ (string) request('work_order_id') === (string) $workOrderOption->id ? 'selected' : '' }}>
                            {{ $workOrderOption->wo_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select select2">
                    <option value="">Select Vendor</option>
                    @foreach(($vendors ?? collect()) as $vendor)
                        <option value="{{ $vendor->id }}" {{ (string) request('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->company_code ? $vendor->company_code.' - ' : '' }}{{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Common Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Employee, Department">
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
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
                <a href="{{ route('msu-work-orders.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">{{ $viewMode === 'item' ? 'Work View' : 'Main View' }}</h5>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('msu-work-orders.index', array_merge($filterQuery, ['view' => 'main'])) }}"
               class="btn btn-sm {{ $viewMode === 'main' ? 'btn-primary' : 'btn-outline-primary' }}">
                Main View
            </a>
            <a href="{{ route('msu-work-orders.index', array_merge($filterQuery, ['view' => 'item'])) }}"
               class="btn btn-sm {{ $viewMode === 'item' ? 'btn-primary' : 'btn-outline-primary' }}">
                Work View
            </a>
        </div>
    </div>

    @if($viewMode === 'item')
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle wo-item-table">
                <thead>
                    <tr>
                        <th style="width:60px">S/No</th>
                        <th class="text-center" style="width:80px">Actions</th>
                        <th>Branch</th>
                        <th>WO Number</th>
                        <th>WO Date</th>
                        <th class="work-desc-col">Work Description</th>
                        <th>HSN</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Gross Value</th>
                        <th class="text-end">Tax %</th>
                        <th class="text-end">Tax Value</th>
                        <th class="text-end">Net Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $WO = $item->workOrder; @endphp
                        <tr>
                            <td class="text-muted">{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($WO)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('msu-work-order:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-work-orders.show', $WO) }}">
                                                <i class="bx bx-show me-1"></i> View WO
                                            </a>
                                        </li>
                                        @endcan
                                        @can('msu-work-order:edit')
                                        @if($WO->statusEnum()->isEditable())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('msu-work-orders.edit', $WO) }}">
                                                <i class="bx bx-edit-alt me-1"></i> Edit WO
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td>{{ $WO?->branch?->name ?? '--' }}</td>
                            <td><strong>{{ $WO?->wo_number ?? '--' }}</strong></td>
                            <td>{{ $WO?->wo_date?->format('d-m-Y') ?? '--' }}</td>
                            <td class="work-desc-col">
                                <span class="work-desc-text" title="{{ $item->work_description }}">{{ $item->work_description ?: '--' }}</span>
                            </td>
                            <td>{{ $item->hsn ?: '--' }}</td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->gross_value, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->tax, 2) }}</td>
                            <td class="text-end">{{ number_format($item->tax_value, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->net_value, 2) }}</td>
                            <td>
                                @if($WO)
                                <span class="badge bg-label-{{ $WO->statusEnum()->badgeClass() }}">
                                    {{ $WO->statusEnum()->label() }}
                                </span>
                                @else
                                --
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-4 text-muted">No WO items found.</td>
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
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle wo-list-table">
                <thead>
                    <tr>
                        <th style="width:60px">S/No</th>
                        <th class="text-center" style="width:80px">Actions</th>
                        <th>Branch</th>
                        <th>WO Number</th>
                        <th>WO Date</th>
                        <th>Vendor</th>
                        <th>Department</th>
                        <th class="payment-term-col">Payment Terms</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total Value</th>
                        <th class="text-end">Misc Charges</th>
                        <th class="text-end">Grand Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $po)
                    <tr>
                        <td class="text-muted">
                            {{ ($workOrders->currentPage() - 1) * $workOrders->perPage() + $loop->iteration }}
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    @can('msu-work-order:view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-work-orders.show', $po) }}">
                                            <i class="bx bx-show me-1"></i> View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-work-orders.export', ['wo_number' => $po->wo_number, 'view' => 'main']) }}">
                                            <i class="bx bx-export me-1"></i> Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-work-orders.pdf', $po) }}">
                                            <i class="bx bxs-file-pdf me-1"></i> PDF
                                        </a>
                                    </li>
                                    @endcan
                                    @can('msu-work-invoice:create')
                                    @if($po->statusEnum()->canAddInvoice())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-work-invoices.create', ['msu_work_order_id' => $po->id]) }}">
                                            <i class="bx bx-receipt me-1"></i> Add Invoice
                                        </a>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-work-order:approve')
                                    @if($po->statusEnum() === \App\Enums\Procurement\MsuWorkOrderStatus::WoCreated)
                                    <li>
                                        <form action="{{ route('msu-work-orders.approve', $po) }}" method="POST" id="approve-form-{{ $po->id }}">
                                            @csrf
                                            <button type="button" class="dropdown-item text-success" onclick="confirmApprove({{ $po->id }})">
                                                <i class="bx bx-check-circle me-1"></i> Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-work-order:edit')
                                    @if($po->statusEnum()->isEditable())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('msu-work-orders.edit', $po) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-work-order:cancel')
                                    @if($po->statusEnum()->canCancel())
                                    <li>
                                        <form action="{{ route('msu-work-orders.cancel', $po) }}" method="POST" id="cancel-form-{{ $po->id }}">
                                            @csrf
                                            <button type="button" class="dropdown-item text-warning" onclick="confirmCancel({{ $po->id }})">
                                                <i class="bx bx-x-circle me-1"></i> Cancel WO
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @endcan
                                    @can('msu-work-order:delete')
                                    @if($po->statusEnum()->isEditable() && $po->invoices()->doesntExist())
                                    <li>
                                        <form action="{{ route('msu-work-orders.destroy', $po) }}" method="POST" id="delete-form-{{ $po->id }}">
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
                        <td>{{ $po->branch?->name ?? '--' }}</td>
                        <td><strong>{{ $po->wo_number }}</strong></td>
                        <td>{{ $po->wo_date?->format('d-m-Y') }}</td>
                        <td>{{ $po->vendor?->name ?? '--' }}</td>
                        <td>{{ $po->department?->department_name ?? '--' }}</td>
                        <td class="payment-term-col">
                            @php $paymentTermName = $po->paymentTerm?->term_name; @endphp
                            <span class="payment-term-text" title="{{ $paymentTermName }}">{{ $paymentTermName ?: '--' }}</span>
                        </td>
                        <td class="text-end">{{ $po->items->count() }}</td>
                        <td class="text-end">{{ number_format((float) $po->total_value, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $po->miscellaneous_charges, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $po->grand_total, 2) }}</td>
                        <td>
                            <span class="badge bg-label-{{ $po->statusEnum()->badgeClass() }}">
                                {{ $po->statusEnum()->label() }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">No Work Orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($workOrders->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $workOrders->firstItem() ?? 0 }} to {{ $workOrders->lastItem() ?? 0 }} of {{ $workOrders->total() }}
            </div>
            <div>{{ $workOrders->links() }}</div>
        </div>
        @endif
    @endif
</div>
@include('partials.content-zoom-close')
@endsection

@section('page-style')
@include('partials.content-zoom-style')
<style>
.wo-list-table .payment-term-col {
    width: 280px;
    max-width: 280px;
}
.wo-list-table .payment-term-text {
    display: block;
    width: 280px;
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
table.table.wo-item-table {
    width: max-content;
}
.wo-item-table .work-desc-col,
.wo-item-table .work-desc-text {
    width: 360px;
    max-width: 360px;
    white-space: normal;
}
.wo-item-table .work-desc-text {
    display: block;
    overflow-wrap: anywhere;
}
</style>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    if (typeof $.fn.select2 === 'function') {
        $('select.select2').select2({ placeholder: 'Select', allowClear: true, width: '100%' });
    }
});
function confirmApprove(id) {
    Swal.fire({ title: 'Approve this WO?', icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Yes, approve' })
        .then(r => { if (r.isConfirmed) document.getElementById('approve-form-' + id).submit(); });
}
function confirmDelete(id) {
    Swal.fire({ title: 'Delete this WO?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete' })
        .then(r => { if (r.isConfirmed) document.getElementById('delete-form-' + id).submit(); });
}
function confirmCancel(id) {
    Swal.fire({
        title: 'Cancel this WO?',
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
@include('partials.content-zoom-script', ['zoomStorageKey' => 'msu-wo-content-zoom'])
@endsection



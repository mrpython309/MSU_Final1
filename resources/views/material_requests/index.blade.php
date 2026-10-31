@extends('layouts/contentNavbarLayout')

@section('title', 'Material Requests')

@section('page-style')
@include('partials.content-zoom-style')
@endsection

@section('content')
@php
    $viewMode = $viewMode ?? 'main';
    $filterQuery = request()->except('page');
    if (! isset($branches) || collect($branches)->isEmpty()) {
        $companyId = session('global_company_id');
        $branches = \App\Models\Branch::visibleToUser(auth()->user())
            ->active()
            ->orderBy('name')
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();
    }
@endphp
@include('partials.content-zoom-open')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Material Requests</h4>
    <div class="d-flex gap-2">
        @can('material_requests:view')
        <a href="{{ route('material-requests.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bx bx-export me-1"></i> Excel
        </a>
        <a href="{{ route('material-requests.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @endcan
        @can('material_requests:create')
        <a href="{{ route('material-requests.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Create Material Request
        </a>
        @endcan
    </div>
</div>

<div class="card mb-4">
    <div class="card-body pt-2">
        <form method="GET" action="{{ route('material-requests.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" id="mr_filter_branch_id" class="form-select select2">
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->code ? $branch->code.' - ' : '' }}{{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Item</label>
                <select name="item_id" id="mr_filter_item_id" class="form-select select2">
                    <option value="">Select Item</option>
                    @foreach($filterItems ?? [] as $filterItem)
                        <option value="{{ $filterItem->id }}" {{ (string) request('item_id') === (string) $filterItem->id ? 'selected' : '' }}>
                            {{ $filterItem->item_code ? $filterItem->item_code.' - ' : '' }}{{ $filterItem->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Employee</label>
                <select name="employee_master_id" id="mr_filter_employee_id" class="form-select select2">
                    <option value="">Select Employee</option>
                    @foreach($employees ?? [] as $employee)
                        <option value="{{ $employee->id }}" {{ (string) request('employee_master_id') === (string) $employee->id ? 'selected' : '' }}>
                            {{ $employee->employee_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Department</label>
                <select name="department_id" id="mr_filter_department_id" class="form-select select2">
                    <option value="">Select Department</option>
                    @foreach($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>
                            {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">MR No</label>
                <input type="text" name="mr_number" class="form-control" value="{{ request('mr_number') }}" placeholder="Search MR Number">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" id="mr_filter_status" class="form-select select2">
                    <option value="">All Status</option>
                    <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Created</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="partially_issued" {{ request('status') === 'partially_issued' ? 'selected' : '' }}>Partially Issued</option>
                    <option value="fully_issued" {{ request('status') === 'fully_issued' ? 'selected' : '' }}>Fully Issued</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Request Date</label>
                <input type="date" name="request_date" class="form-control" value="{{ request('request_date') }}">
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
                <button type="submit" class="btn btn-primary me-1">Filter</button>
                <a href="{{ route('material-requests.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
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
            <a href="{{ route('material-requests.index', array_merge($filterQuery, ['view' => 'main'])) }}"
               class="btn btn-sm {{ $viewMode === 'main' ? 'btn-primary' : 'btn-outline-primary' }}">Main View</a>
            <a href="{{ route('material-requests.index', array_merge($filterQuery, ['view' => 'item'])) }}"
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
                        <th>MR No</th>
                        <th>Request Date</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th class="text-end">Requested Qty</th>
                        <th class="text-end">Issued Qty</th>
                        <th class="text-end">Remaining</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $mr = $item->materialRequest; @endphp
                        <tr>
                            <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($mr)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('material_requests:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('material-requests.show', $mr->id) }}">
                                                <i class="bx bx-show me-1 text-info"></i> View
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td><strong>{{ $mr?->mr_number ?? '--' }}</strong></td>
                            <td>{{ $mr?->request_date ? \Illuminate\Support\Carbon::parse($mr->request_date)->format('d-m-Y') : '--' }}</td>
                            <td>{{ $mr?->company?->name ?? $mr?->branch?->company?->name ?? 'N/A' }}</td>
                            <td>{{ $mr?->branch?->name ?? 'N/A' }}</td>
                            <td>{{ $item->itemMaster?->item_code ?? '--' }}</td>
                            <td>{{ $item->itemMaster?->item_name ?? '--' }}</td>
                            <td>{{ $item->unit?->unit_name ?? $item->itemMaster?->unit?->unit_name ?? '--' }}</td>
                            <td class="text-end">{{ number_format((float) $item->requested_qty, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->issued_qty, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->remaining_qty, 3) }}</td>
                            <td>
                                @if($mr)
                                    @include('material_requests._status-badge', ['status' => $mr->status])
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">No material request items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="card-footer">
            {{ $items->links() }}
        </div>
        @endif
    @else
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Actions</th>
                        <th>MR No</th>
                        <th>Requested Date</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Department</th>
                        <th>Employee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $mr)
                    <tr>
                        <td>{{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    @can('material_requests:view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-requests.show', $mr->id) }}">
                                            <i class="bx bx-show me-1 text-info"></i> View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-requests.excel', $mr) }}">
                                            <i class="bx bx-export me-1"></i> Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-requests.pdf', $mr) }}">
                                            <i class="bx bxs-file-pdf me-1"></i> PDF
                                        </a>
                                    </li>
                                    @endcan

                                    @if($mr->status === 'created')
                                        @can('material_requests:edit')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('material-requests.edit', $mr->id) }}">
                                                <i class="bx bx-edit me-1 text-warning"></i> Edit
                                            </a>
                                        </li>
                                        @endcan
                                        @can('material_requests:approve')
                                        <li>
                                            <form action="{{ route('material-requests.submit', $mr->id) }}" method="POST" class="d-inline-block w-100" onsubmit="return confirm('Approve this request?');">
                                                @csrf
                                                <button type="submit" class="dropdown-item w-100 text-start">
                                                    <i class="bx bx-check me-1 text-success"></i> Approve
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        @can('material_requests:cancel')
                                        <li>
                                            <form action="{{ route('material-requests.cancel', $mr->id) }}" method="POST" id="cancel-mr-form-{{ $mr->id }}" class="d-inline-block w-100">
                                                @csrf
                                                <button type="button" class="dropdown-item w-100 text-start" onclick="confirmCancelMr({{ $mr->id }})">
                                                    <i class="bx bx-x me-1 text-danger"></i> Cancel
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        @can('material_requests:delete')
                                        <li>
                                            <form action="{{ route('material-requests.destroy', $mr->id) }}" method="POST" class="d-inline-block w-100" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item w-100 text-start">
                                                    <i class="bx bx-trash me-1 text-danger"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                        <td><strong>{{ $mr->mr_number }}</strong></td>
                        <td>{{ $mr->request_date ? \Illuminate\Support\Carbon::parse($mr->request_date)->format('d-m-Y') : '--' }}</td>
                        <td>{{ $mr->company?->name ?? $mr->branch?->company?->name ?? 'N/A' }}</td>
                        <td>{{ $mr->branch->name ?? 'N/A' }}</td>
                        <td>{{ $mr->department->department_name ?? 'N/A' }}</td>
                        <td>{{ $mr->employee->employee_name ?? 'N/A' }}</td>
                        <td>@include('material_requests._status-badge', ['status' => $mr->status])</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">No material requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
        @endif
    @endif
</div>
@include('partials.content-zoom-close')
@endsection

@section('page-script')
<script>
function confirmCancelMr(id) {
    Swal.fire({
        title: 'Cancel this Request?',
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
            let form = document.getElementById('cancel-mr-form-' + id);
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
@include('partials.content-zoom-script', ['zoomStorageKey' => 'material-request-content-zoom'])
@endsection

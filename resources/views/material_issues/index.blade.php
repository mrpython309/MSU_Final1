@extends('layouts/contentNavbarLayout')

@section('title', 'Material Issues')

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
    <h4 class="mb-0">Material Issues</h4>
    <div class="d-flex gap-2">
        @can('material_issues:view')
        <a href="{{ route('material-issues.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bx bx-export me-1"></i> Excel
        </a>
        <a href="{{ route('material-issues.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @endcan
        @can('material_issues:create')
        <a href="{{ route('material-issues.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Issue Material
        </a>
        @endcan
    </div>
</div>

<div class="card mb-4">
    <div class="card-body pt-2">
        <form method="GET" action="{{ route('material-issues.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">Select Branch</option>
                    @foreach(($branches ?? collect()) as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->code ? $branch->code.' - ' : '' }}{{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select select2">
                    <option value="">Select Department</option>
                    @foreach(($departments ?? collect()) as $department)
                        <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>
                            {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Employee</label>
                <select name="employee_master_id" class="form-select select2">
                    <option value="">Select Employee</option>
                    @foreach(($employees ?? collect()) as $employee)
                        <option value="{{ $employee->id }}" {{ (string) request('employee_master_id') === (string) $employee->id ? 'selected' : '' }}>
                            {{ $employee->employee_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select select2">
                    <option value="">Select Item</option>
                    @foreach(($filterItems ?? collect()) as $filterItem)
                        <option value="{{ $filterItem->id }}" {{ (string) request('item_id') === (string) $filterItem->id ? 'selected' : '' }}>
                            {{ $filterItem->item_code ? $filterItem->item_code.' - ' : '' }}{{ $filterItem->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select select2">
                    <option value="">All Status</option>
                    <option value="partially_issued" {{ request('status') === 'partially_issued' ? 'selected' : '' }}>Partially Issued</option>
                    <option value="fully_issued" {{ request('status') === 'fully_issued' ? 'selected' : '' }}>Fully Issued</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">Issue Date</label>
                <input type="date" name="issue_date" class="form-control" value="{{ request('issue_date') }}">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">MR No / MI No</label>
                <input type="text" name="search" class="form-control" value="{{ request('search', request('mi_number')) }}" placeholder="Search MR No or MI No">
            </div>
            <div class="col-md-3 col-lg-1">
                <label class="form-label">Per Page</label>
                <select name="per_page" class="form-select select2" onchange="this.form.submit()">
                    <option value="10" {{ request('per_page', $perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page', $perPage ?? 10) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page', $perPage ?? 10) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', $perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                    <option value="1000000" {{ request('per_page', $perPage ?? 10) == 1000000 ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <button type="submit" class="btn btn-primary me-1">Filter</button>
                <a href="{{ route('material-issues.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
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
            <a href="{{ route('material-issues.index', array_merge($filterQuery, ['view' => 'main'])) }}"
               class="btn btn-sm {{ $viewMode === 'main' ? 'btn-primary' : 'btn-outline-primary' }}">Main View</a>
            <a href="{{ route('material-issues.index', array_merge($filterQuery, ['view' => 'item'])) }}"
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
                        <th>MI No</th>
                        <th>Issue Date</th>
                        <th>MR No</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th class="text-end">Issue Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $mi = $item->materialIssue;
                            $mr = $mi?->materialRequest;
                        @endphp
                        <tr>
                            <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($mi)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('material_issues:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('material-issues.show', $mi->id) }}">
                                                <i class="bx bx-show me-1 text-info"></i> View
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td><strong>{{ $mi?->mi_number ?? '--' }}</strong></td>
                            <td>{{ $mi?->issue_date ? \Illuminate\Support\Carbon::parse($mi->issue_date)->format('d-m-Y') : '--' }}</td>
                            <td>{{ $mr?->mr_number ?? 'N/A' }}</td>
                            <td>{{ $mr?->company?->name ?? $mr?->branch?->company?->name ?? 'N/A' }}</td>
                            <td>{{ $mr?->branch?->name ?? 'N/A' }}</td>
                            <td>{{ $item->itemMaster?->item_code ?? '--' }}</td>
                            <td>{{ $item->itemMaster?->item_name ?? '--' }}</td>
                            <td>{{ $item->materialRequestItem?->unit?->unit_name ?? $item->itemMaster?->unit?->unit_name ?? '--' }}</td>
                            <td class="text-end">{{ number_format((float) $item->issue_qty, 3) }}</td>
                            <td>
                                @if($mi)
                                    @include('material_issues._status-badge', ['status' => $mi->status])
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No material issue items found.</td>
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
                        <th>MI No</th>
                        <th>Issue Date</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Department</th>
                        <th>Employee</th>
                        <th>MR No</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issues as $mi)
                    <tr>
                        <td>{{ ($issues->currentPage() - 1) * $issues->perPage() + $loop->iteration }}</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    @can('material_issues:view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-issues.show', $mi->id) }}">
                                            <i class="bx bx-show me-1 text-info"></i> View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-issues.excel', $mi) }}">
                                            <i class="bx bx-export me-1"></i> Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('material-issues.pdf', $mi) }}">
                                            <i class="bx bxs-file-pdf me-1"></i> PDF
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                        <td><strong>{{ $mi->mi_number }}</strong></td>
                        <td>{{ $mi->issue_date ? \Illuminate\Support\Carbon::parse($mi->issue_date)->format('d-m-Y') : '--' }}</td>
                        <td>{{ $mi->materialRequest?->company?->name ?? $mi->materialRequest?->branch?->company?->name ?? 'N/A' }}</td>
                        <td>{{ $mi->materialRequest->branch->name ?? 'N/A' }}</td>
                        <td>{{ $mi->department->department_name ?? 'N/A' }}</td>
                        <td>{{ $mi->employee->employee_name ?? 'N/A' }}</td>
                        <td>{{ $mi->materialRequest->mr_number ?? 'N/A' }}</td>
                        <td>@include('material_issues._status-badge', ['status' => $mi->status])</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">No material issues found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($issues->hasPages())
        <div class="card-footer">
            {{ $issues->links() }}
        </div>
        @endif
    @endif
</div>
@include('partials.content-zoom-close')
@endsection

@section('page-script')
@include('partials.content-zoom-script', ['zoomStorageKey' => 'material-issue-content-zoom'])
@endsection

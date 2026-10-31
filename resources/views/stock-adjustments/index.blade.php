@extends('layouts/contentNavbarLayout')

@section('title', 'Stock Adjustments')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Inventory /</span> Stock Adjustments
        </h4>
        <div class="d-flex gap-2">
            @can('stock-adjustments:view')
            <a href="{{ route('stock-adjustments.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="bx bx-export me-1"></i> Excel
            </a>
            <a href="{{ route('stock-adjustments.export-pdf', request()->query()) }}" class="btn btn-outline-danger">
                <i class="bx bxs-file-pdf me-1"></i> PDF
            </a>
            @endcan
            <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Adjustment
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('stock-adjustments.index') }}" method="GET" class="row g-3">

                <div class="col-md-2">
                    <label class="form-label" for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" class="form-control form-control-sm" list="item-name-options" autocomplete="off" value="{{ request('item_name') }}" placeholder="Search Item">
                    <datalist id="item-name-options">
                        @foreach($allItems as $item)
                            @if($item->item_name)
                                <option value="{{ $item->item_name }}"></option>
                            @endif
                        @endforeach
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2" title="Filter"><i class="bx bx-search"></i></button>
                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bx bx-reset"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th class="text-center">Actions</th>
                        <th>Adjustment Number</th>
                        <th>Item</th>
                        <th>Date</th>
                        <th>Adjustment Type</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Adjust Stock (Qty)</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($adjustments as $index => $adjustment)
                        <tr>
                            <td>{{ $adjustments->firstItem() + $index }}</td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('stock-adjustments:view')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('stock-adjustments.show', $adjustment->id) }}">
                                                <i class="bx bx-show me-1 text-info"></i> View
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                            <td><strong>{{ $adjustment->adjustment_number }}</strong></td>
                            <td>{{ $adjustment->itemMaster?->item_name ?? '--' }}</td>
                            <td>{{ $adjustment->adjustment_date->format('d-m-Y') }}</td>
                            <td>
                                @if($adjustment->adjustment_type === 'increment')
                                    <span class="badge bg-label-success"><i class="bx bx-up-arrow-alt"></i> Increment</span>
                                @else
                                    <span class="badge bg-label-danger"><i class="bx bx-down-arrow-alt"></i> Decrement</span>
                                @endif
                            </td>
                            <td>{{ $adjustment->employee?->employee_name ?? '--' }}</td>
                            <td>{{ $adjustment->department?->department_name ?? '--' }}</td>
                            <td class="text-end fw-bold">
                                {{ number_format($adjustment->quantity, 3) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $adjustments->links() }}
        </div>
        @endif
    </div>
@endsection

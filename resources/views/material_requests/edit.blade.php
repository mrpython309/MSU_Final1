@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Material Request')

@section('content')
@php
    $oldItems = old('items');
    if ($oldItems === null) {
        $oldItems = $materialRequest->items->map(fn ($item) => [
            'id' => $item->id,
            'msu_item_master_id' => $item->msu_item_master_id,
            'requested_qty' => $item->requested_qty,
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit->unit_name ?? '',
            'stock' => $item->itemMaster->current_stock ?? 0,
        ])->toArray();
    }
@endphp
<h4 class="py-3 breadcrumb-wrapper mb-4">
  <span class="text-muted fw-light"><a href="{{ route('material-requests.index') }}">Material Requests</a> /</span> Edit {{ $materialRequest->mr_number }}
</h4>

<div class="card mb-4">
    <form action="{{ route('material-requests.update', $materialRequest->id) }}" method="POST" id="material-request-form" novalidate>
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">MR Number</label>
                    <input type="text" class="form-control" id="mr_number_preview"
                           value="{{ $displayMrNumber ?? $previewMrNumber ?? $materialRequest->mr_number }}"
                           disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Request Date <span class="text-danger">*</span></label>
                    <input type="date" name="request_date" class="form-control" value="{{ old('request_date', $materialRequest->request_date) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select select2">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('department_id', $materialRequest->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employee</label>
                    <select name="employee_master_id" class="form-select select2">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ (string) old('employee_master_id', $materialRequest->employee_master_id) === (string) $emp->id ? 'selected' : '' }}>{{ $emp->employee_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select select2" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                data-code="{{ $branch->code ?: 'BRN' }}"
                                {{ (string) old('branch_id', $materialRequest->branch_id) === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->code ? $branch->code.' - ' : '' }}{{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4">

            @include('material_requests._items-table')
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Request</button>
            <a href="{{ route('material-requests.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            minimumResultsForSearch: 0
        });

        var initialMrNumber = @json($materialRequest->mr_number);

        function refreshMrNumberPreview() {
            $('#mr_number_preview').val(initialMrNumber);
        }

        $('select[name="branch_id"], input[name="request_date"]').on('change', function() {
            refreshMrNumberPreview();
        });

        refreshMrNumberPreview();
    });
</script>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Material Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Material Request: {{ $materialRequest->mr_number }}</h4>
    <div class="d-flex gap-2">
        @if($materialRequest->status === 'created')
            @can('material_requests:approve')
            <form action="{{ route('material-requests.submit', $materialRequest->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this request?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bx bx-check me-1"></i> Approve
                </button>
            </form>
            @endcan
            @can('material_requests:cancel')
            <form action="{{ route('material-requests.cancel', $materialRequest->id) }}" method="POST" id="cancel-mr-show-form" class="d-inline">
                @csrf
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="confirmCancelMrShow()">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>
            </form>
            @endcan
        @endif
        <a href="{{ route('material-requests.index') }}" class="btn btn-secondary btn-sm">
            <i class="bx bx-arrow-back me-1"></i> Back
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Details</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong> {{ $materialRequest->request_date ? \Illuminate\Support\Carbon::parse($materialRequest->request_date)->format('d-m-Y') : '--' }}</div>
            <div class="col-md-3"><strong>Department:</strong> {{ $materialRequest->department->department_name ?? 'N/A' }}</div>
            <div class="col-md-3"><strong>Employee:</strong> {{ $materialRequest->employee->employee_name ?? 'N/A' }}</div>
            <div class="col-md-3"><strong>Company:</strong> {{ $materialRequest->company?->name ?? $materialRequest->branch?->company?->name ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="col-md-3"><strong>Branch:</strong> {{ $materialRequest->branch?->name ?? 'N/A' }}</div>
            <div class="col-md-3">
                <strong>Status:</strong> 
                @if($materialRequest->status === 'created')
                    <span class="badge bg-label-secondary">Created</span>
                @elseif($materialRequest->status === 'approved')
                    <span class="badge bg-label-primary">Approved</span>
                @elseif($materialRequest->status === 'partially_issued')
                    <span class="badge bg-label-warning">Partially Issued</span>
                @elseif($materialRequest->status === 'fully_issued')
                    <span class="badge bg-label-success">Fully Issued</span>
                @elseif($materialRequest->status === 'cancelled')
                    <span class="badge bg-label-danger">Cancelled</span>
                @endif
            </div>
            <div class="col-md-3"><strong>Created By:</strong> {{ $materialRequest->createdBy->name ?? 'N/A' }}</div>
            @if($materialRequest->cancel_reason)
            <div class="col-md-12 mt-3">
                <strong class="text-danger">Cancel Reason:</strong> 
                <span class="text-danger">{{ $materialRequest->cancel_reason }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Requested Items</h5>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Unit</th>
                    <th>Requested Qty</th>
                    <th>Available Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materialRequest->items as $item)
                <tr>
                    <td>{{ $item->itemMaster->item_code ?? 'N/A' }}</td>
                    <td>{{ $item->itemMaster->item_name ?? 'N/A' }}</td>
                    <td>{{ $item->unit->unit_name ?? 'N/A' }}</td>
                    <td>{{ $item->requested_qty }}</td>
                    <td>{{ $item->itemMaster->current_stock ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('page-script')
<script>
function confirmCancelMrShow() {
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
            let form = document.getElementById('cancel-mr-show-form');
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
@endsection

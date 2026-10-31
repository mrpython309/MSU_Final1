@extends('layouts/contentNavbarLayout')

@section('title', 'Material Issue Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Material Issue: {{ $materialIssue->mi_number }}</h4>
    <a href="{{ route('material-issues.index') }}" class="btn btn-secondary btn-sm">
        <i class="bx bx-arrow-back me-1"></i> Back
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Details</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong> {{ $materialIssue->issue_date ? \Illuminate\Support\Carbon::parse($materialIssue->issue_date)->format('d-m-Y') : '--' }}</div>
            <div class="col-md-3"><strong>MR Number:</strong> {{ $materialIssue->materialRequest->mr_number ?? 'N/A' }}</div>
            <div class="col-md-3"><strong>Department:</strong> {{ $materialIssue->department->department_name ?? 'N/A' }}</div>
            <div class="col-md-3"><strong>Employee:</strong> {{ $materialIssue->employee->employee_name ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="col-md-3"><strong>Company:</strong> {{ $materialIssue->materialRequest?->company?->name ?? $materialIssue->materialRequest?->branch?->company?->name ?? 'N/A' }}</div>
            <div class="col-md-3"><strong>Branch:</strong> {{ $materialIssue->materialRequest?->branch?->name ?? 'N/A' }}</div>
            <div class="col-md-3">
                <strong>Status:</strong>
                @if($materialIssue->status === 'cancelled')
                    <span class="badge bg-label-danger">Cancelled</span>
                @elseif($materialIssue->status === 'partially_issued')
                    <span class="badge bg-label-warning">Partially Issued</span>
                @elseif($materialIssue->status === 'fully_issued')
                    <span class="badge bg-label-success">Fully Issued</span>
                @else
                    <span class="badge bg-label-secondary">{{ ucfirst($materialIssue->status) }}</span>
                @endif
            </div>
            <div class="col-md-3"><strong>Created By:</strong> {{ $materialIssue->createdBy->name ?? 'N/A' }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Issued Items</h5>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Requested Qty</th>
                    <th>Issued Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materialIssue->items as $item)
                <tr>
                    <td>{{ $item->itemMaster->item_code ?? 'N/A' }}</td>
                    <td>{{ $item->itemMaster->item_name ?? 'N/A' }}</td>
                    <td>{{ $item->materialRequestItem->requested_qty ?? 'N/A' }}</td>
                    <td>{{ $item->issue_qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

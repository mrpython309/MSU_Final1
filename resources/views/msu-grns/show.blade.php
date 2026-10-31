@extends('layouts/contentNavbarLayout')

@section('title', 'GRN ' . $grn->grn_number)

@section('content')
@php
    $msuGrnApproveUrl = \Illuminate\Support\Facades\Route::has('msu-grns.approve-asset')
        ? route('msu-grns.approve-asset', $grn)
        : route('msu-grns.add-to-stock', $grn);
    $msuGrnCancelUrl = \Illuminate\Support\Facades\Route::has('msu-grns.cancel-asset')
        ? route('msu-grns.cancel-asset', $grn)
        : route('msu-grns.add-to-stock', $grn);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">GRN — {{ $grn->grn_number }}</h4>
    <div class="d-flex gap-2">
        @canany(['msu-grn:edit', 'msu-grn:create'])
        @if($grn->canEditClassification())
        <a href="{{ route('msu-grns.edit', $grn) }}" class="btn btn-primary btn-sm">
            <i class="bx bx-edit me-1"></i> Edit
        </a>
        @endif
        @endcanany
        <a href="{{ route('msu-grns.pdf', $grn->id) }}" class="btn btn-danger btn-sm" target="_blank">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('msu-grns.excel', $grn->id) }}" class="btn btn-success btn-sm">
            <i class="bx bx-spreadsheet me-1"></i> Excel
        </a>
        @can('msu-grn:approve')
        @if($grn->canApproveAsset())
        <form action="{{ $msuGrnApproveUrl }}" method="POST" class="d-inline" id="approve-asset-form">
            @csrf
            <input type="hidden" name="asset_action" value="approve">
            <button type="button" class="btn btn-success btn-sm" onclick="confirmApproveAsset()">
                <i class="bx bx-check me-1"></i> Approve
            </button>
        </form>
        @endif
        @endcan
        @can('msu-grn:cancel')
        @if($grn->canCancelAsset())
        <form action="{{ $msuGrnCancelUrl }}" method="POST" class="d-inline" id="cancel-asset-form">
            @csrf
            <input type="hidden" name="asset_action" value="cancel">
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="confirmCancelAsset()">Cancel</button>
        </form>
        @endif
        @endcan
        <a href="{{ route('msu-grns.index') }}" class="btn btn-secondary btn-sm">Back</a>
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
        <div class="row">
            <div class="col-md-3 mb-3"><small class="text-muted d-block">GRN Number</small><strong>{{ $grn->grn_number }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Receipt Number</small><strong>{{ $grn->receipt_number ?: '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">GRN Date</small><strong>{{ $grn->grn_date?->format('d-m-Y') }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Status</small>
                <span class="badge bg-label-{{ $grn->statusEnum()->badgeClass() }}">{{ $grn->statusEnum()->label() }}</span>
            </div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Asset Status</small>
                @if($grn->isCapex())
                <span class="badge bg-label-{{ $grn->assetStatusEnum()->badgeClass() }}">{{ $grn->assetStatusEnum()->label() }}</span>
                @else
                <strong>--</strong>
                @endif
            </div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Company</small><strong>{{ $grn->branch?->company?->name ?? $grn->purchaseOrder?->branch?->company?->name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Branch</small><strong>{{ $grn->branch ? (($grn->branch->code ? $grn->branch->code.' - ' : '').$grn->branch->name) : ($grn->purchaseOrder?->branch?->name ?? '--') }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">PO Number</small><strong>{{ $grn->purchaseOrder?->po_number ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">PO Date</small><strong>{{ $grn->purchaseOrder?->po_date?->format('d-m-Y') ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor</small><strong>{{ $grn->vendor?->name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor GST</small><strong>{{ $grn->vendor?->gst_number ?: '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Department</small><strong>{{ $grn->department?->department_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Employee</small><strong>{{ $grn->employeeMaster?->employee_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor Invoice Number</small><strong>{{ $grn->vendor_invoice_number ?: '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor Invoice Date</small><strong>{{ $grn->vendor_invoice_date?->format('d-m-Y') ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Capex / Opex</small>
                @if($grn->expenseTypeLabel() !== '--')
                    <span class="badge bg-label-{{ $grn->isCapex() ? 'info' : 'secondary' }}">{{ $grn->expenseTypeLabel() }}</span>
                @else
                    <strong>--</strong>
                @endif
            </div>
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Remarks</small><strong>{{ $grn->remarks ?: '--' }}</strong></div>
            @if($grn->asset_cancel_reason)
            <div class="col-md-12 mt-3 mb-0">
                <small class="text-muted d-block text-danger">Asset Cancel Reason</small>
                <strong class="text-danger">{{ $grn->asset_cancel_reason }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">GRN Item Details</h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Product Category</th>
                    <th>Sub Category</th>
                    <th>HSN</th>
                    <th>Unit</th>
                    <th class="text-end">Ordered Qty</th>
                    <th class="text-end">Previously Received</th>
                    <th class="text-end">Received Qty</th>
                    <th class="text-center">CAPEX</th>
                    <th class="text-center">Group Asset</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Gross Value</th>
                    <th class="text-end">Tax %</th>
                    <th class="text-end">Tax Value</th>
                    <th class="text-end">Net Value</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandGross = 0;
                    $grandTax = 0;
                    $grandNet = 0;
                @endphp
                @foreach($grn->items as $item)
                @php
                    $received = (float) $item->received_quantity;
                    $poItem = $item->purchaseOrderItem;
                    $rate = $poItem ? (float) $poItem->price : 0;
                    $taxPct = $poItem ? (float) $poItem->tax : 0;
                    
                    $gross = round($received * $rate, 2);
                    $taxVal = round($gross * $taxPct / 100, 2);
                    $net = round($gross + $taxVal, 2);

                    $grandGross += $gross;
                    $grandTax += $taxVal;
                    $grandNet += $net;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->item_code }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->itemMaster?->productCategory?->category_name ?? '--' }}</td>
                    <td>{{ $item->itemMaster?->productSubCategory?->sub_category_name ?? '--' }}</td>
                    <td>{{ $item->hsn ?: '--' }}</td>
                    <td>{{ $item->itemMaster?->unit?->unit_name ?? ($item->itemMaster?->uom ?: '--') }}</td>
                    <td class="text-end">{{ number_format((float) $item->ordered_quantity, 3) }}</td>
                    <td class="text-end">{{ number_format((float) $item->previously_received_quantity, 3) }}</td>
                    <td class="text-end">{{ number_format($received, 3) }}</td>
                    <td class="text-center">
                        <span class="badge bg-label-{{ $item->is_capex ? 'info' : 'secondary' }}">{{ $item->is_capex ? 'Yes' : 'No' }}</span>
                    </td>
                    <td class="text-center">
                        @if($item->is_group_asset)
                            <span class="badge bg-label-info">Yes</span>
                        @else
                            No
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                    <td class="text-end">{{ number_format($gross, 2) }}</td>
                    <td class="text-end">{{ number_format($taxPct, 2) }}</td>
                    <td class="text-end">{{ number_format($taxVal, 2) }}</td>
                    <td class="text-end">{{ number_format($net, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@php
    $miscCharges = (float) ($grn->miscellaneous_charges ?? 0);
    $miscTax = (float) ($grn->miscellaneous_tax ?? 0);
    $poCharges = ($miscCharges > 0 && $grn->purchaseOrder && $grn->purchaseOrder->charges && $grn->purchaseOrder->charges->count() > 0)
        ? $grn->purchaseOrder->charges
        : collect();
    $totalItemValue = $grandGross;
    $totalItemTax = $grandTax;
    $totalExcludingTax = $totalItemValue + $miscCharges;
    $totalTaxAmount = $totalItemTax + $miscTax;
    $halfTax = round($totalTaxAmount / 2, 2);
    $finalTotal = $totalExcludingTax + $totalTaxAmount;

    $isInterState = false;
    $taxBranch = $grn->branch ?? $grn->purchaseOrder?->branch;
    $taxVendor = $grn->vendor ?? $grn->purchaseOrder?->vendor;
    if ($taxBranch && $taxVendor) {
        $branchState = trim(strtolower((string) $taxBranch->state));
        $vendorState = trim(strtolower((string) $taxVendor->state));
        if ($branchState === '') $branchState = 'karnataka';
        if ($vendorState === '') $vendorState = 'karnataka';
        $isInterState = $branchState !== $vendorState;
    }
@endphp

@if($miscCharges > 0)
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Miscellaneous Charges</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Charge Type</th>
                        <th>Charge Amount</th>
                        <th>Tax %</th>
                        <th>Tax Amount</th>
                        <th>Charge Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($poCharges->count() > 0)
                        @foreach($poCharges as $charge)
                            <tr>
                                <td>{{ $charge->charge_name }}</td>
                                <td>{{ number_format((float) $charge->amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_percentage, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->amount + $charge->tax_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>Miscellaneous Charges</td>
                            <td>{{ number_format($miscCharges, 2) }}</td>
                            <td>--</td>
                            <td>{{ number_format($miscTax, 2) }}</td>
                            <td>{{ number_format($miscCharges + $miscTax, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Overall GRN Values</h5></div>
    <div class="card-body">
        <div class="row justify-content-end">
            <div class="col-md-6 col-lg-5">
                <table class="table table-bordered border-secondary mb-0">
                    <tbody>
                        <tr>
                            <td>Total Item Value</td>
                            <td class="text-end"><strong>{{ number_format($totalItemValue, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Misc Value</td>
                            <td class="text-end"><strong>{{ number_format($miscCharges, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Total(Excluding Tax)</strong></td>
                            <td class="text-end"><strong>{{ number_format($totalExcludingTax, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Items Tax Value</td>
                            <td class="text-end"><strong>{{ number_format($totalItemTax, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Misc Tax Value</td>
                            <td class="text-end"><strong>{{ number_format($miscTax, 2) }}</strong></td>
                        </tr>
                        @if($isInterState)
                            <tr>
                                <td>IGST</td>
                                <td class="text-end"><strong>{{ number_format($totalTaxAmount, 2) }}</strong></td>
                            </tr>
                        @else
                            <tr>
                                <td>SGST</td>
                                <td class="text-end"><strong>{{ number_format($halfTax, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>CGST</td>
                                <td class="text-end"><strong>{{ number_format(round($totalTaxAmount - $halfTax, 2), 2) }}</strong></td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Total(Including Tax)</strong></td>
                            <td class="text-end"><strong>{{ number_format($finalTotal, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function confirmApproveAsset() {
    Swal.fire({
        title: 'Approve asset conversion?',
        text: 'Approved items will be added to assets.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, approve'
    }).then(r => {
        if (r.isConfirmed) document.getElementById('approve-asset-form').submit();
    });
}
function confirmCancelAsset() {
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
            let form = document.getElementById('cancel-asset-form');
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

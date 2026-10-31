@extends('layouts/contentNavbarLayout')

@section('title', 'MSU PO ' . $msuPurchaseOrder->po_number)

@section('content')
@php
    $branch = $msuPurchaseOrder->branch;
    $vendor = $msuPurchaseOrder->vendor;
    $shipping = $msuPurchaseOrder->shippingDetail;
    $branchAddress = collect([
        $branch?->address,
        $branch?->city,
        $branch?->state,
        $branch?->country,
    ])->filter()->implode(', ');
    $vendorAddress = collect([
        $vendor?->address,
        $vendor?->city,
        $vendor?->state,
        $vendor?->country,
    ])->filter()->implode(', ');
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Purchase Order — {{ $msuPurchaseOrder->po_number }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('msu-purchase-orders.excel', $msuPurchaseOrder) }}" class="btn btn-success btn-sm">
            <i class="bx bx-spreadsheet me-1"></i> Excel
        </a>
        <a href="{{ route('msu-purchase-orders.pdf', $msuPurchaseOrder) }}" class="btn btn-primary btn-sm">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @if($msuPurchaseOrder->statusEnum() === \App\Enums\Procurement\MsuPurchaseOrderStatus::PoCreated)
        @can('msu-purchase-order:approve')
        <form action="{{ route('msu-purchase-orders.approve', $msuPurchaseOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this purchase order?');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bx bx-check me-1"></i> Approve
            </button>
        </form>
        @endcan
        @endif
        @can('msu-purchase-order:cancel')
        @if($msuPurchaseOrder->statusEnum()->canCancel())
        <form action="{{ route('msu-purchase-orders.cancel', $msuPurchaseOrder) }}" method="POST" class="d-inline" id="cancel-po-form">
            @csrf
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="confirmCancelPo()">Cancel PO</button>
        </form>
        @endif
        @endcan
        <a href="{{ route('msu-purchase-orders.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3"><small class="text-muted d-block">PO Number</small><strong>{{ $msuPurchaseOrder->po_number }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">PO Date</small><strong>{{ $msuPurchaseOrder->po_date?->format('d-m-Y') }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Date of Commencement</small><strong>{{ $msuPurchaseOrder->date_of_commencement?->format('d-m-Y') ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor</small><strong>{{ $vendor?->name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor GST</small><strong>{{ $vendor?->gst_number ?: '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Branch</small><strong>{{ $branch?->name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Employee</small><strong>{{ $msuPurchaseOrder->employeeMaster?->employee_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Department</small><strong>{{ $msuPurchaseOrder->department?->department_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Payment Terms</small><strong>{{ $msuPurchaseOrder->paymentTerm?->term_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3">
                @php
                    $totalMiscHeader = 0;
                    if($msuPurchaseOrder->charges && $msuPurchaseOrder->charges->count() > 0) {
                        foreach($msuPurchaseOrder->charges as $c) {
                            $totalMiscHeader += (float)$c->amount;
                        }
                    } else {
                        $totalMiscHeader = (float) $msuPurchaseOrder->miscellaneous_charges;
                    }
                    
                    $dynamicGrandTotal = (float) $msuPurchaseOrder->grand_total;
                @endphp
                <small class="text-muted d-block">Total Misc. Charges</small>
                <strong>{{ $totalMiscHeader > 0 ? number_format($totalMiscHeader, 2) : '0' }}</strong>
            </div>
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Status</small>
                <span class="badge bg-label-{{ $msuPurchaseOrder->statusEnum()->badgeClass() }}">
                    {{ $msuPurchaseOrder->statusEnum()->label() }}
                </span>
            </div>
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Grand Total</small>
                <strong>{{ number_format($dynamicGrandTotal, 2) }}</strong>
            </div>
            @if($msuPurchaseOrder->cancel_reason)
            <div class="col-md-12 mb-3">
                <small class="text-muted d-block text-danger">Cancel Reason</small>
                <strong class="text-danger">{{ $msuPurchaseOrder->cancel_reason }}</strong>
            </div>
            @endif
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Remarks</small><strong>{{ $msuPurchaseOrder->remarks ?: '--' }}</strong></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Vendor GST Details</h5></div>
    <div class="card-body">
        @if($vendor)
        <div class="row">
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Name</small><strong>{{ $vendor->name }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Code</small><strong>{{ $vendor->company_code ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">GST Number</small><strong>{{ $vendor->gst_number ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">PAN</small><strong>{{ $vendor->pan_number ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Contact Person</small><strong>{{ $vendor->contact_person ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Phone</small><strong>{{ $vendor->phone_number ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Email</small><strong>{{ $vendor->email ?: '--' }}</strong></div>
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Address</small><strong>{{ $vendorAddress ?: '--' }}</strong></div>
        </div>
        @else
        <p class="mb-0 text-muted">No vendor selected.</p>
        @endif
    </div>
</div>

@if($branch)
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Branch Details</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Name</small><strong>{{ $branch->name }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Code</small><strong>{{ $branch->code ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Phone</small><strong>{{ $branch->contact_number ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Email</small><strong>{{ $branch->email ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">GST</small><strong>{{ $branch->gst_number ?: '--' }}</strong></div>
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Address</small><strong>{{ $branchAddress ?: '--' }}</strong></div>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Shipping Details</h5></div>
    <div class="card-body">
        @if($shipping)
        <div class="row">
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Shipping Name</small><strong>{{ $shipping->shipping_name }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">GST Number</small><strong>{{ $shipping->gst_number ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Contact Person</small><strong>{{ $shipping->contact_person ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Contact No</small><strong>{{ $shipping->contact_no ?: '--' }}</strong></div>
            <div class="col-md-4 mb-3"><small class="text-muted d-block">Email Id</small><strong>{{ $shipping->email ?: '--' }}</strong></div>
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Address</small><strong>{{ $shipping->address ?: '--' }}</strong></div>
        </div>
        @else
        <p class="mb-0 text-muted">No shipping details selected.</p>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0 text-primary">Purchase Order Item Details</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead class="border-bottom">
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Item</th>
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
                    @foreach($msuPurchaseOrder->items as $item)
                        @php
                            $master = $item->itemMaster;
                            $hsn = $item->hsn ?: ($master?->hsn?->hsn_code ?? '--');
                            $qty = (float) $item->quantity;
                            $rate = $item->price !== null ? (float) $item->price : (float) ($master?->applicableRate($msuPurchaseOrder->po_date)?->rate ?? 0);
                            $tax = $item->tax !== null ? (float) $item->tax : (float) ($master?->applicableRate($msuPurchaseOrder->po_date)?->tax ?? $master?->hsn?->tax_rate ?? 0);
                            $gross = \App\Models\MsuPurchaseOrderItem::computeGrossValue($qty, $rate);
                            $taxValue = \App\Models\MsuPurchaseOrderItem::computeTaxValue($qty, $rate, $tax);
                            $net = (float) $item->net_value ?: \App\Models\MsuPurchaseOrderItem::computeNetValue($qty, $rate, $tax);
                        @endphp
                    <tr>
                        <td class="px-3">{{ $loop->iteration }}</td>
                        <td>{{ $item->item_code ?: ($master?->item_code ?? '--') }}</td>
                        <td>{{ $item->item_name ?: ($master?->item_name ?? '--') }}</td>
                        <td>{{ $hsn ?: '--' }}</td>
                        <td class="text-end">{{ number_format($qty, 3) }}</td>
                        <td class="text-end">{{ number_format($rate, 2) }}</td>
                        <td class="text-end">{{ number_format($gross, 2) }}</td>
                        <td class="text-end">{{ number_format($tax, 2) }}</td>
                        <td class="text-end">{{ number_format($taxValue, 2) }}</td>
                        <td class="text-end px-3">{{ number_format($net, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

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
                    @if($msuPurchaseOrder->charges && $msuPurchaseOrder->charges->count() > 0)
                        @foreach($msuPurchaseOrder->charges as $charge)
                            <tr>
                                <td>{{ $charge->charge_name }}</td>
                                <td>{{ number_format((float) $charge->amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_percentage, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->amount + $charge->tax_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        @if((float) $msuPurchaseOrder->miscellaneous_charges > 0)
                            <tr>
                                <td>{{ $msuPurchaseOrder->miscellaneousCharge?->charge_name ?? 'Miscellaneous Charges' }}</td>
                                <td>{{ number_format((float) $msuPurchaseOrder->miscellaneous_charges, 2) }}</td>
                                <td>{{ number_format((float) ($msuPurchaseOrder->miscellaneous_tax ?? 0), 2) }}</td>
                                <td>{{ number_format($msuPurchaseOrder->miscellaneous_tax_amount, 2) }}</td>
                                <td>{{ number_format($msuPurchaseOrder->miscellaneous_total, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="text-center text-muted">No miscellaneous charges</td>
                            </tr>
                        @endif
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Overall PO Values</h5></div>
    <div class="card-body">
        <div class="row justify-content-end">
            <div class="col-md-6 col-lg-5">
                <table class="table table-bordered border-secondary mb-0">
                    <tbody>
                        @php
                            $totalItemTax = $msuPurchaseOrder->items->sum(fn ($item) => (float) $item->tax_value);
                            $totalMiscValue = $msuPurchaseOrder->charges && $msuPurchaseOrder->charges->count() > 0
                                ? $msuPurchaseOrder->charges->sum(fn ($c) => (float) $c->amount)
                                : (float) $msuPurchaseOrder->miscellaneous_charges;
                            $totalMiscTax = $msuPurchaseOrder->charges && $msuPurchaseOrder->charges->count() > 0
                                ? $msuPurchaseOrder->charges->sum(fn ($c) => (float) $c->tax_amount)
                                : (float) $msuPurchaseOrder->miscellaneous_tax;
                            $totalItemMiscValues = (float) $msuPurchaseOrder->total_value + $totalMiscValue;

                            $sgst = (float) $msuPurchaseOrder->sgst;
                            $cgst = (float) $msuPurchaseOrder->cgst;
                            $igst = (float) $msuPurchaseOrder->igst;

                            $displayIgst = $igst;
                            $displaySgst = $sgst;
                            $displayCgst = $cgst;

                            $isInterState = false;
                            if ($msuPurchaseOrder->branch && $msuPurchaseOrder->vendor) {
                                $branchState = trim(strtolower((string)$msuPurchaseOrder->branch->state));
                                $vendorState = trim(strtolower((string)$msuPurchaseOrder->vendor->state));

                                if ($branchState === '') $branchState = 'karnataka';
                                if ($vendorState === '') $vendorState = 'karnataka';

                                if ($branchState !== $vendorState) {
                                    $isInterState = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td>Total Item Value</td>
                            <td class="text-end"><strong>{{ number_format($msuPurchaseOrder->total_value, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Misc Value</td>
                            <td class="text-end"><strong>{{ number_format($totalMiscValue, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Total(Excluding Tax)</strong></td>
                            <td class="text-end"><strong>{{ number_format($totalItemMiscValues, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Items Tax Value</td>
                            <td class="text-end"><strong>{{ number_format($totalItemTax, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Misc Tax Value</td>
                            <td class="text-end"><strong>{{ number_format($totalMiscTax, 2) }}</strong></td>
                        </tr>
                        @if($isInterState)
                            <tr>
                                <td>IGST</td>
                                <td class="text-end"><strong>{{ number_format($displayIgst, 2) }}</strong></td>
                            </tr>
                        @else
                            <tr>
                                <td>SGST</td>
                                <td class="text-end"><strong>{{ number_format($displaySgst, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>CGST</td>
                                <td class="text-end"><strong>{{ number_format($displayCgst, 2) }}</strong></td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Total(Including Tax)</strong></td>
                            <td class="text-end"><strong>{{ number_format($dynamicGrandTotal, 2) }}</strong></td>
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
function confirmCancelPo() {
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
            let form = document.getElementById('cancel-po-form');
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

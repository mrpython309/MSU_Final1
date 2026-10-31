@extends('layouts/contentNavbarLayout')

@section('title', 'MSU WO ' . $msuWorkOrder->wo_number)

@section('content')
@php
    $branch = $msuWorkOrder->branch;
    $shipping = $msuWorkOrder->shippingDetail;
    $branchAddress = collect([
        $branch?->address,
        $branch?->city,
        $branch?->state,
        $branch?->country,
    ])->filter()->implode(', ');
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Work Order — {{ $msuWorkOrder->wo_number }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('msu-work-orders.excel', $msuWorkOrder) }}" class="btn btn-success btn-sm">
            <i class="bx bx-spreadsheet me-1"></i> Excel
        </a>
        <a href="{{ route('msu-work-orders.pdf', $msuWorkOrder) }}" class="btn btn-primary btn-sm">
            <i class="bx bxs-file-pdf me-1"></i> PDF
        </a>
        @can('msu-work-invoice:view')
        @if($msuWorkOrder->invoices->count() === 1)
        <a href="{{ route('msu-work-invoices.show', $msuWorkOrder->invoices->first()) }}" class="btn btn-info btn-sm">
            <i class="bx bx-receipt me-1"></i> Invoice
        </a>
        @elseif($msuWorkOrder->invoices->count() > 1)
        <div class="btn-group">
            <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-receipt me-1"></i> Invoices
            </button>
            <ul class="dropdown-menu">
                @foreach($msuWorkOrder->invoices as $idx => $inv)
                <li><a class="dropdown-item" href="{{ route('msu-work-invoices.show', $inv) }}">Invoice {{ $idx + 1 }}</a></li>
                @endforeach
            </ul>
        </div>
        @endif
        @endcan
        @can('msu-work-invoice:create')
        @if($msuWorkOrder->statusEnum()->canAddInvoice())
        <a href="{{ route('msu-work-invoices.create', ['msu_work_order_id' => $msuWorkOrder->id]) }}" class="btn btn-success btn-sm">
            <i class="bx bx-receipt me-1"></i> Add Invoice
        </a>
        @endif
        @endcan
        @can('msu-work-order:cancel')
        @if($msuWorkOrder->statusEnum()->canCancel())
        <form action="{{ route('msu-work-orders.cancel', $msuWorkOrder) }}" method="POST" class="d-inline" id="cancel-po-form">
            @csrf
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="confirmCancelPo()">Cancel WO</button>
        </form>
        @endif
        @endcan
        @can('msu-work-order:approve')
        @if($msuWorkOrder->statusEnum() === \App\Enums\Procurement\MsuWorkOrderStatus::WoCreated)
        <form action="{{ route('msu-work-orders.approve', $msuWorkOrder) }}" method="POST" class="d-inline" id="approve-wo-form">
            @csrf
            <button type="button" class="btn btn-success btn-sm" onclick="confirmApproveWo()">Approve</button>
        </form>
        @endif
        @endcan
        <a href="{{ route('msu-work-orders.index') }}" class="btn btn-secondary btn-sm">Back</a>
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
            <div class="col-md-3 mb-3"><small class="text-muted d-block">WO Number</small><strong>{{ $msuWorkOrder->wo_number }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">WO Date</small><strong>{{ $msuWorkOrder->wo_date?->format('d-m-Y') }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Vendor</small><strong>{{ $msuWorkOrder->vendor?->name }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Branch</small><strong>{{ $branch?->name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Employee</small><strong>{{ $msuWorkOrder->employeeMaster?->employee_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Department</small><strong>{{ $msuWorkOrder->department?->department_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Contract Period From</small><strong>{{ $msuWorkOrder->contract_period_from?->format('d-m-Y') ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Contract Period To</small><strong>{{ $msuWorkOrder->contract_period_to?->format('d-m-Y') ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Frequency</small><strong>{{ $msuWorkOrder->frequencyEnum()?->label() ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Next Work Order</small>
                <strong>{{ $msuWorkOrder->next_occurrence_date?->format('d-m-Y') ?? '--' }}</strong>
            </div>
            @if($msuWorkOrder->recurrenceSource)
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Generated From</small>
                <strong>
                    <a href="{{ route('msu-work-orders.show', $msuWorkOrder->recurrenceSource) }}">{{ $msuWorkOrder->recurrenceSource->wo_number }}</a>
                </strong>
            </div>
            @endif
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Subject</small><strong>{{ $msuWorkOrder->subject ?: '--' }}</strong></div>
            <div class="col-md-3 mb-3"><small class="text-muted d-block">Payment Terms</small><strong>{{ $msuWorkOrder->paymentTerm?->term_name ?? '--' }}</strong></div>
            <div class="col-md-3 mb-3">
                @php
                    $totalMiscHeader = 0;
                    if($msuWorkOrder->charges && $msuWorkOrder->charges->count() > 0) {
                        foreach($msuWorkOrder->charges as $c) {
                            $totalMiscHeader += (float)$c->amount;
                        }
                    } else {
                        $totalMiscHeader = (float) $msuWorkOrder->miscellaneous_charges;
                    }
                    
                    $dynamicGrandTotal = (float) $msuWorkOrder->grand_total;
                @endphp
                <small class="text-muted d-block">Total Misc. Charges</small>
                <strong>{{ $totalMiscHeader > 0 ? number_format($totalMiscHeader, 2) : '0' }}</strong>
            </div>
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Status</small>
                <span class="badge bg-label-{{ $msuWorkOrder->statusEnum()->badgeClass() }}">
                    {{ $msuWorkOrder->statusEnum()->label() }}
                </span>
            </div>
            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Grand Total</small>
                <strong>{{ number_format($dynamicGrandTotal, 2) }}</strong>
            </div>
            @if($msuWorkOrder->cancel_reason)
            <div class="col-md-12 mb-3">
                <small class="text-muted d-block text-danger">Cancel Reason</small>
                <strong class="text-danger">{{ $msuWorkOrder->cancel_reason }}</strong>
            </div>
            @endif
            <div class="col-md-12 mb-0"><small class="text-muted d-block">Remarks</small><strong>{{ $msuWorkOrder->remarks ?: '--' }}</strong></div>
        </div>
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
    <div class="card-header"><h5 class="mb-0 text-primary">Work Order Item Details</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead class="border-bottom">
                    <tr>
                        <th>#</th>
                        <th>Scope of Work</th>
                        <th>Work Description</th>
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
                    @foreach($msuWorkOrder->items as $item)
                        @php
                            $hsn = $item->hsn ?: '--';
                            $qty = (float) $item->quantity;
                            $rate = $item->price !== null ? (float) $item->price : 0;
                            $tax = $item->tax !== null ? (float) $item->tax : 0;
                            $gross = \App\Models\MsuWorkOrderItem::computeGrossValue($qty, $rate);
                            $taxValue = \App\Models\MsuWorkOrderItem::computeTaxValue($qty, $rate, $tax);
                            $net = (float) $item->net_value ?: \App\Models\MsuWorkOrderItem::computeNetValue($qty, $rate, $tax);
                        @endphp
                    <tr>
                        <td class="px-3">{{ $loop->iteration }}</td>
                        <td>{{ $item->scope_of_work ?: '--' }}</td>
                        <td>{{ $item->work_description ?: '--' }}</td>
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
                    @if($msuWorkOrder->charges && $msuWorkOrder->charges->count() > 0)
                        @foreach($msuWorkOrder->charges as $charge)
                            <tr>
                                <td>{{ $charge->charge_name }}</td>
                                <td>{{ number_format((float) $charge->amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_percentage, 2) }}</td>
                                <td>{{ number_format((float) $charge->tax_amount, 2) }}</td>
                                <td>{{ number_format((float) $charge->amount + $charge->tax_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        @if((float) $msuWorkOrder->miscellaneous_charges > 0)
                            <tr>
                                <td>{{ $msuWorkOrder->miscellaneousCharge?->charge_name ?? 'Miscellaneous Charges' }}</td>
                                <td>{{ number_format((float) $msuWorkOrder->miscellaneous_charges, 2) }}</td>
                                <td>{{ number_format((float) ($msuWorkOrder->miscellaneous_tax ?? 0), 2) }}</td>
                                <td>{{ number_format($msuWorkOrder->miscellaneous_tax_amount, 2) }}</td>
                                <td>{{ number_format($msuWorkOrder->miscellaneous_total, 2) }}</td>
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
    <div class="card-header"><h5 class="mb-0">Overall WO Values</h5></div>
    <div class="card-body">
        <div class="row justify-content-end">
            <div class="col-md-6 col-lg-5">
                <table class="table table-bordered border-secondary mb-0">
                    <tbody>
                        @php
                            $totalItemTax = $msuWorkOrder->items->sum(fn ($item) => (float) $item->tax_value);
                            $totalMiscValue = $totalMiscHeader;
                            $totalMiscTax = $msuWorkOrder->charges && $msuWorkOrder->charges->count() > 0
                                ? $msuWorkOrder->charges->sum(fn ($c) => (float) $c->tax_amount)
                                : (float) $msuWorkOrder->miscellaneous_tax;
                            $totalExcludingTax = (float) $msuWorkOrder->total_value + $totalMiscValue;

                            $sgst = (float) $msuWorkOrder->sgst;
                            $cgst = (float) $msuWorkOrder->cgst;
                            $igst = (float) $msuWorkOrder->igst;

                            $displayIgst = $igst;
                            $displaySgst = $sgst;
                            $displayCgst = $cgst;

                            $isInterState = false;
                            if ($msuWorkOrder->branch && $msuWorkOrder->vendor) {
                                $branchState = trim(strtolower((string) $msuWorkOrder->branch->state));
                                $vendorState = trim(strtolower((string) $msuWorkOrder->vendor->state));

                                if ($branchState === '') $branchState = 'karnataka';
                                if ($vendorState === '') $vendorState = 'karnataka';

                                if ($branchState !== $vendorState) {
                                    $isInterState = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td>Total Item Value</td>
                            <td class="text-end"><strong>{{ number_format($msuWorkOrder->total_value, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Misc Value</td>
                            <td class="text-end"><strong>{{ number_format($totalMiscValue, 2) }}</strong></td>
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
function confirmApproveWo() {
    Swal.fire({ title: 'Approve this WO?', icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Yes, approve' })
        .then(r => { if (r.isConfirmed) document.getElementById('approve-wo-form').submit(); });
}
function confirmCancelPo() {
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



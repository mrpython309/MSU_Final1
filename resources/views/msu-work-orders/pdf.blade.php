<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @if(!isset($isExcel) || !$isExcel)
    <title>WORK ORDER - {{ $msuWorkOrder->wo_number }}</title>
    @endif
    <style>
        @page { margin: 12mm 10mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            line-height: 1.4;
        }
        table { width: 100%; border-collapse: collapse; }
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: -20px 0 10px;
            color: #000;
        }
        .header-meta td { vertical-align: top; }
        .section-label {
            font-weight: bold;
            font-size: 13px;
            color: #888;
            margin-bottom: 4px;
        }
        .company-name {
            font-weight: bold;
            font-size: 12px;
            color: #000;
            margin-bottom: 2px;
        }
        .party-name { 
            font-weight: bold;
            font-size: 11px;
            color: #333;
            margin-bottom: 2px;
        }
        .addr { margin-top: 2px; color: #444; line-height: 1.3;}
        .items {
            margin-top: 15px;
            width: 100%;
            table-layout: fixed;
        }
        .items th,
        .items td {
            border: 1px solid #333;
            word-wrap: break-word;
        }
        .items th {
            padding: 6px 4px;
            font-size: 9px;
            text-align: center;
            font-weight: bold;
            color: #000;
            vertical-align: middle;
            background: #f3f3f3;
        }
        .items td {
            padding: 6px 4px;
            font-size: 9px;
            vertical-align: top;
            color: #333;
        }
        .items .col-sno { width: 4%; text-align: center; }
        .items .col-scope { width: 13%; text-align: left; }
        .items .col-desc { width: 16%; text-align: left; }
        .items .col-hsn { width: 7%; text-align: center; }
        .items .col-unit { width: 6%; text-align: center; }
        .items .col-qty,
        .items .col-rate,
        .items .col-gross,
        .items .col-tax-pct,
        .items .col-tax-val,
        .items .col-net {
            text-align: right;
            white-space: nowrap;
        }
        .items .col-qty { width: 7%; }
        .items .col-rate { width: 8%; }
        .items .col-gross { width: 9%; }
        .items .col-tax-pct { width: 6%; text-align: center; }
        .items .col-tax-val { width: 9%; }
        .items .col-net { width: 10%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .commencement {
            margin-top: 10px;
            font-weight: bold;
            font-size: 11px;
        }
        .bottom-wrap {
            margin-top: 15px;
            width: 100%;
        }
        .bottom-wrap td { vertical-align: top; }
        .remarks {
            border: 1px solid #000;
            padding: 4px 6px;
            min-height: 80px;
        }
        .totals {
            border: 1px solid #000;
            width: 100%;
        }
        .totals th {
            border: 1px solid #000;
            background: #f3f3f3;
            text-align: center;
            padding: 4px;
            font-size: 10px;
        }
        .totals td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        .totals .label { text-align: left; }
        .totals .value { text-align: right; width: 90px; }
        .totals .grand-label { font-weight: bold; }
        .totals th { font-weight: bold; }
        .misc-charges {
            margin-top: 10px;
        }
        .misc-charges th {
            font-weight: bold;
        }
        .misc-charges .label { white-space: nowrap; }
        .misc-charges .value { width: auto; white-space: nowrap; padding-left: 16px; }
        .in-words {
            border: 1px solid #000;
            border-top: none;
            padding: 4px 6px;
            font-size: 9px;
            font-weight: bold;
        }
        .payment-box {
            margin-right: 15px;
        }
        .payment-box .pay-title {
            font-weight: bold;
            color: #777;
            margin-bottom: 6px;
        }
        .payment-box .pay-body {
            font-weight: bold;
            font-size: 10px;
            color: #333;
        }
        .footer-box {
            border: 1px solid #000;
            margin-top: 15px;
        }
        .footer-company {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
            padding: 6px 4px;
            text-transform: uppercase;
        }
        .footer-inner td {
            vertical-align: bottom;
            padding: 6px;
        }
        .disclaimer {
            font-size: 8px;
            width: 60%;
            vertical-align: top !important;
        }
        .signatory {
            text-align: right;
            font-size: 9px;
            width: 40%;
            padding-bottom: 15px !important;
        }
    </style>
</head>
<body>
@php
    $wo = $msuWorkOrder;
    $branch = $wo->branch;
    $vendor = $wo->vendor;
    $companyName = $branch && $branch->company ? $branch->company->name : 'Mysore Saree Udyog LLP';
    $branchTitle = $branch
        ? trim(($branch->name ?: strtoupper($companyName) . ' - BNGLR SHOWROOM'))
        : strtoupper($companyName) . ' - BNGLR SHOWROOM';
    $branchAddress = collect([
        $branch?->gst_number,
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
    $shipping = $wo->shippingDetail;
    $shippingTitle = $shipping?->shipping_name ?: $branchTitle;
    $commencement = $wo->date_of_commencement?->format('d-m-Y')
        ?? $wo->wo_date?->format('d-m-Y')
        ?? '--';
@endphp

@if(!isset($isExcel) || !$isExcel)


<!-- Title & Barcode Section -->
<div style="position: relative;">
    <div class="title" style="text-align: center; margin-bottom: 15px; margin-top: -20px; white-space: nowrap;">WORK ORDER</div>
</div>

<!-- Invoice To & PO Info Section -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px;">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div class="section-label">Invoice To</div>
            <div class="company-name">{{ $companyName }}</div>
            <div class="party-name">{{ strtoupper($branchTitle) }}</div>
            <div class="addr">
                @if($branch?->gst_number){{ $branch->gst_number }}<br>@endif
                {{ $branchAddress ?: '--' }}
            </div>
            <div class="addr">Contact Person : {{ $branch?->contact_person ?: '--' }}</div>
            <div class="addr">Contact No : {{ $branch?->contact_number ?: '--' }}</div>
            @if($branch?->email)
                <div class="addr">EmailID : {{ $branch->email }}</div>
            @endif
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top; line-height: 1.4; font-weight: bold; color: #777;">
            <div>WO No: {{ $wo->wo_number }}</div>
            <div>Date: {{ $wo->wo_date?->format('d-m-Y') ?? '--' }}</div>
        </td>
    </tr>
</table>

<!-- Supplier and Shipping Address Section -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 10px;">
    <tr>
        <!-- Left: Supplier -->
        <td style="width: 50%; vertical-align: top; padding-right: 20px;">
            <div class="section-label">Supplier</div>
            <div class="company-name">
                {{ strtoupper($vendor?->name ?? '--') }}
                @if($vendor?->company_code)
                    ({{ $vendor->company_code }})
                @endif
            </div>
            <div class="addr">
                @if($vendor?->gst_number){{ $vendor->gst_number }}<br>@endif
                {{ $vendorAddress ?: '--' }}
            </div>
            <div class="addr">Contact Person : {{ $vendor?->contact_person ?: '--' }}</div>
            <div class="addr">Contact No : {{ $vendor?->phone_number ?: '--' }}</div>
            @if($vendor?->email)
                <div class="addr">EmailID : {{ $vendor->email }}</div>
            @endif
        </td>
        
        <!-- Right: Shipping Address -->
        <td style="width: 50%; vertical-align: top; padding-left: 20px;">
            <div class="section-label">Shipping Address</div>
            <div class="company-name">{{ strtoupper($shippingTitle) }}</div>
            <div class="addr">
                @if($shipping?->gst_number){{ $shipping->gst_number }}<br>@endif
                {{ $shipping?->address ?: ($branchAddress ?: '--') }}
            </div>
            <div class="addr">Contact Person : {{ $shipping?->contact_person ?: '--' }}</div>
            <div class="addr">Contact No : {{ $shipping?->contact_no ?: '--' }}</div>
            @if($shipping?->email)
                <div class="addr">EmailID : {{ $shipping->email }}</div>
            @endif
        </td>
    </tr>
</table>

<div style="font-weight: bold; font-size: 11px; margin-bottom: 10px; color: #333;">
    Contract Period : <span style="font-weight: normal; margin-left: 15px;">
        {{ $wo->contract_period_from ? $wo->contract_period_from->format('d-m-Y') : '--' }} to {{ $wo->contract_period_to ? $wo->contract_period_to->format('d-m-Y') : '--' }}
    </span>
</div>

@if($wo->subject)
<div style="border: 1px solid #333; padding: 6px 10px; margin-bottom: 15px; font-weight: bold; font-size: 11px;">
    Subject: <span style="font-weight: normal; margin-left: 10px;">{{ $wo->subject }}</span>
</div>
@endif
@endif

<table class="items">
    <thead>
        <tr>
            <th class="col-sno">S.No</th>
            <th class="col-scope">Scope of Work</th>
            <th class="col-desc">Description</th>
            <th class="col-hsn">HSN</th>
            <th class="col-unit">Unit</th>
            <th class="col-qty">Qty</th>
            <th class="col-rate">Rate</th>
            <th class="col-gross">Gross Value</th>
            <th class="col-tax-pct">Tax %</th>
            <th class="col-tax-val">Tax Value</th>
            <th class="col-net">Net Value</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalQty = 0;
            $totalGross = 0;
            $totalTax = 0;
            $totalNet = 0;
        @endphp
        @foreach($wo->items as $item)
            @php
                $qty = (float) $item->quantity;
                $rate = (float) $item->price;
                $taxPct = (float) $item->tax;
                $gross = $item->gross_value;
                $taxValue = $item->tax_value;
                $net = (float) $item->net_value ?: round($gross + $taxValue, 2);
                $qtyDisplay = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
                $totalQty += $qty;
                $totalGross += $gross;
                $totalTax += $taxValue;
                $totalNet += $net;
            @endphp
            <tr>
                <td class="col-sno">{{ $loop->iteration }}</td>
                <td class="col-scope">{{ $item->scope_of_work ?: '--' }}</td>
                <td class="col-desc">{{ $item->work_description ?: '--' }}</td>
                <td class="col-hsn">{{ $item->hsn ?: '--' }}</td>
                <td class="col-unit">{{ $item->unit?->unit_name ?: '--' }}</td>
                <td class="col-qty">{{ $qtyDisplay }}</td>
                <td class="col-rate">{{ number_format($rate, 2) }}</td>
                <td class="col-gross">{{ number_format($gross, 2) }}</td>
                <td class="col-tax-pct">{{ rtrim(rtrim(number_format($taxPct, 2, '.', ''), '0'), '.') }}</td>
                <td class="col-tax-val">{{ number_format($taxValue, 2) }}</td>
                <td class="col-net">{{ number_format($net, 2) }}</td>
            </tr>
        @endforeach
        @if(isset($isExcel) && $isExcel)
        <tr>
            <th colspan="5" style="text-align: right;">Total:</th>
            <th class="col-qty">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</th>
            <th></th>
            <th class="col-gross">{{ number_format($totalGross, 2) }}</th>
            <th></th>
            <th class="col-tax-val">{{ number_format($totalTax, 2) }}</th>
            <th class="col-net">{{ number_format($totalNet, 2) }}</th>
        </tr>
        @endif
    </tbody>
</table>

@if(!isset($isExcel) || !$isExcel)
@php
    $miscChargeLines = collect();
    if ($wo->charges && $wo->charges->count() > 0) {
        $miscChargeLines = $wo->charges->map(fn ($c) => [
            'name' => $c->charge_name,
            'amount' => (float) $c->amount,
        ]);
        $totalMiscValues = $wo->charges->sum(fn ($c) => (float) $c->amount);
        $totalMiscTax = $wo->charges->sum(fn ($c) => (float) $c->tax_amount);
    } else {
        $totalMiscValues = (float) $wo->miscellaneous_charges;
        $totalMiscTax = (float) $wo->miscellaneous_tax;
        if ($totalMiscValues > 0) {
            $miscChargeLines = collect([[
                'name' => $wo->miscellaneousCharge?->charge_name ?? 'Miscellaneous Charges',
                'amount' => $totalMiscValues,
            ]]);
        }
    }
    $totalExcludingTax = (float) $wo->total_value + $totalMiscValues;

    $sgst = (float) $wo->sgst;
    $cgst = (float) $wo->cgst;
    $igst = (float) $wo->igst;

    $displayIgst = $igst;
    $displaySgst = $sgst;
    $displayCgst = $cgst;

    $isInterState = false;
    if ($wo->branch && $wo->vendor) {
        $branchState = trim(strtolower((string) $wo->branch->state));
        $vendorState = trim(strtolower((string) $wo->vendor->state));

        if ($branchState === '') $branchState = 'karnataka';
        if ($vendorState === '') $vendorState = 'karnataka';

        if ($branchState !== $vendorState) {
            $isInterState = true;
        }
    }
@endphp
<table class="bottom-wrap">
    <tr>
        <td style="width:48%; padding-right:10px;">
            <div class="remarks">
                <div class="remarks-title">Remarks</div>
                <div>{{ $wo->remarks ?: '' }}</div>
            </div>
            @if($miscChargeLines->isNotEmpty())
            <table class="totals misc-charges">
                <tr>
                    <th colspan="2"><strong>Total Miscellaneous Charges</strong></th>
                </tr>
                @foreach($miscChargeLines as $chargeLine)
                    <tr>
                        <td class="label">{{ $chargeLine['name'] }}</td>
                        <td class="value">{{ number_format($chargeLine['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="label">Total Misc Value</td>
                    <td class="value">{{ number_format($totalMiscValues, 2) }}</td>
                </tr>
            </table>
            @endif
        </td>
        <td style="width:52%;">
            <table class="totals">
                <tr>
                    <th colspan="2">Total WO Values</th>
                </tr>
                <tr>
                    <td class="label">Total Item Value</td>
                    <td class="value">{{ number_format((float) $wo->total_value, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Misc Value</td>
                    <td class="value">{{ number_format($totalMiscValues, 2) }}</td>
                </tr>
                <tr>
                    <td class="label grand-label"><strong>Total(Excluding Tax)</strong></td>
                    <td class="value"><strong>{{ number_format($totalExcludingTax, 2) }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Total Items Tax Value</td>
                    <td class="value">{{ number_format($totalTax, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Misc Tax Value</td>
                    <td class="value">{{ number_format($totalMiscTax, 2) }}</td>
                </tr>
                @if($isInterState)
                    <tr>
                        <td class="label">IGST</td>
                        <td class="value">{{ number_format($displayIgst, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="label">SGST</td>
                        <td class="value">{{ number_format($displaySgst, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">CGST</td>
                        <td class="value">{{ number_format($displayCgst, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label grand-label"><strong>Total(Including Tax)</strong></td>
                    <td class="value"><strong>{{ number_format((float) $wo->grand_total, 2) }}</strong></td>
                </tr>
            </table>
            <div class="in-words">
                <strong>In Words:</strong> {{ $amountInWords }}
            </div>
        </td>
    </tr>
</table>

<div style="page-break-inside: avoid;">
    <div class="payment-box">
        <div class="pay-title">Payment Terms and Conditions</div>
        <div class="pay-body">{{ $wo->paymentTerm?->term_name ?? '--' }}</div>
    </div>

    <div class="footer-box">
        <div class="footer-company">{{ strtoupper($branchTitle) }}</div>
        <table>
            <tr class="footer-inner">
                <td class="disclaimer">
                    This is computer generated Purchase/Service Order, Hence Signature is not required.
                </td>
                <td class="signatory">
                    Authorized Signatory
                </td>
            </tr>
        </table>
    </div>
</div>
@endif
</body>
</html>

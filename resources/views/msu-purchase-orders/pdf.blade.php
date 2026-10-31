<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @if(!isset($isExcel) || !$isExcel)
    <title>Purchase Order - {{ $msuPurchaseOrder->po_number }}</title>
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
        }
        .items th {
            padding: 8px 4px;
            font-size: 10px;
            text-align: center;
            font-weight: bold;
            color: #444;
        }
        .items td {
            padding: 6px 4px;
            font-size: 10px;
            vertical-align: top;
            text-align: center;
            color: #333;
        }
        .items td:nth-child(2), .items th:nth-child(2) {
            text-align: left;
        }
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
    $po = $msuPurchaseOrder;
    $branch = $po->branch;
    $vendor = $po->vendor;
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
    $shipping = $po->shippingDetail;
    $shippingTitle = $shipping?->shipping_name ?: $branchTitle;
    $commencement = $po->date_of_commencement?->format('d-m-Y')
        ?? $po->po_date?->format('d-m-Y')
        ?? '--';
@endphp

@if(!isset($isExcel) || !$isExcel)


<!-- Title & Barcode Section -->
<div style="position: relative;">
    <div class="title" style="text-align: center; margin-bottom: 15px; margin-top: -20px; white-space: nowrap;">PURCHASE ORDER</div>
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
            <div>PO No: {{ $po->po_number }}</div>
            <div>Date: {{ $po->po_date?->format('d-m-Y') ?? '--' }}</div>
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

<div class="commencement" style="color: #333; margin-bottom: 35px;">Date of Commencement : <span style="font-weight: normal; margin-left: 15px;">{{ $commencement }}</span></div>
@endif

<table class="items">
    <thead>
        <tr>
            <th style="width:28px">SNo</th>
            <th style="text-align: left;">Item Name</th>
            <th style="width:50px">HSN</th>
            <th style="width:70px">PO Qty</th>
            <th style="width:55px">Rate</th>
            <th style="width:65px">Gross Value</th>
            <th style="width:45px">GST(%)</th>
            <th style="width:55px">GST Value</th>
            <th style="width:65px">Net Value</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalQty = 0;
            $totalGross = 0;
            $totalTax = 0;
            $totalNet = 0;
        @endphp
        @foreach($po->items as $item)
            @php
                $qty = (float) $item->quantity;
                $rate = (float) $item->price;
                $taxPct = (float) $item->tax;
                $gross = round($qty * $rate, 2);
                $taxValue = round($gross * $taxPct / 100, 2);
                $net = (float) $item->net_value ?: round($gross + $taxValue, 2);
                $unit = $item->itemMaster?->unit?->unit_name
                    ?? $item->itemMaster?->uom
                    ?? 'NOS';
                $totalQty += $qty;
                $totalGross += $gross;
                $totalTax += $taxValue;
                $totalNet += $net;
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">
                    {{ $item->item_name }}
                    @if(!isset($isExcel) || !$isExcel)
                        <br><span style="font-size: 8px; color: #555;">{{ $item->item_code }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->hsn ?: '' }}</td>
                <td class="text-right">{{ number_format($qty, 1) }} ({{ strtoupper($unit) }})</td>
                <td class="text-right">{{ number_format($rate, 2) }}</td>
                <td class="text-right">{{ number_format($gross, 2) }}</td>
                <td class="text-right">{{ number_format($taxPct, 1) }}</td>
                <td class="text-right">{{ number_format($taxValue, 2) }}</td>
                <td class="text-right">{{ number_format($net, 2) }}</td>
            </tr>
        @endforeach
        @if(isset($isExcel) && $isExcel)
        <tr>
            <th colspan="3" style="text-align: right;">Total:</th>
            <th style="text-align: right;">{{ number_format($totalQty, 1) }}</th>
            <th></th>
            <th style="text-align: right;">{{ number_format($totalGross, 2) }}</th>
            <th></th>
            <th style="text-align: right;">{{ number_format($totalTax, 2) }}</th>
            <th style="text-align: right;">{{ number_format($totalNet, 2) }}</th>
        </tr>
        @endif
    </tbody>
</table>

@if(!isset($isExcel) || !$isExcel)
@php
    $totalItemTax = $totalTax;
    $miscChargeLines = collect();
    if ($po->charges && $po->charges->count() > 0) {
        $miscChargeLines = $po->charges->map(fn ($c) => [
            'name' => $c->charge_name,
            'amount' => (float) $c->amount,
        ]);
        $totalMiscValues = $po->charges->sum(fn ($c) => (float) $c->amount);
        $totalMiscTax = $po->charges->sum(fn ($c) => (float) $c->tax_amount);
    } else {
        $totalMiscValues = (float) $po->miscellaneous_charges;
        $totalMiscTax = (float) $po->miscellaneous_tax_amount;
        if ($totalMiscValues > 0) {
            $miscChargeLines = collect([[
                'name' => $po->miscellaneousCharge?->charge_name ?? 'Miscellaneous Charges',
                'amount' => $totalMiscValues,
            ]]);
        }
    }
    $totalExcludingTax = (float) $po->total_value + $totalMiscValues;

    $sgst = (float) $po->sgst;
    $cgst = (float) $po->cgst;
    $igst = (float) $po->igst;

    $displayIgst = $igst;
    $displaySgst = $sgst;
    $displayCgst = $cgst;

    $isInterState = false;
    if ($po->branch && $po->vendor) {
        $branchState = trim(strtolower((string) $po->branch->state));
        $vendorState = trim(strtolower((string) $po->vendor->state));

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
                <div>{{ $po->remarks ?: '' }}</div>
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
                    <th colspan="2">Total PO Values</th>
                </tr>
                <tr>
                    <td class="label">Total Item Value</td>
                    <td class="value">{{ number_format((float) $po->total_value, 2) }}</td>
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
                    <td class="value">{{ number_format($totalItemTax, 2) }}</td>
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
                    <td class="value"><strong>{{ number_format((float) $po->grand_total, 2) }}</strong></td>
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
        <div class="pay-body">{{ $po->paymentTerm?->term_name ?? '--' }}</div>
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

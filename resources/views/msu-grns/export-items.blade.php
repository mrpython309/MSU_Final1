<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Goods Receipt Note Items Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #222; }
        h2 { margin: 0 0 8px; font-size: 16px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 4px; }
        th { background: #f3f3f3; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Goods Receipt Note Items</h2>
    <div class="meta">Exported: {{ now()->format('d-m-Y H:i') }} | Records: {{ $items->count() }}</div>
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Branch</th>
                <th>GRN Number</th>
                <th>GRN Date</th>
                <th>PO Number</th>
                <th>PO Date</th>
                <th>Vendor Name</th>
                <th>Vendor GST</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Product Category</th>
                <th>Sub Category</th>
                <th>HSN</th>
                <th>Unit</th>
                <th class="text-end">Ordered Qty</th>
                <th class="text-end">Prev. Received Qty</th>
                <th class="text-end">Received Qty</th>
                <th class="text-end">Price / Unit</th>
                <th class="text-end">Total</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amount</th>
                <th class="text-end">Grand Total</th>
                <th>Capex / Opex</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            @php
                $grn = $item->grn;
                $poItem = $item->purchaseOrderItem;
                $rate = $poItem ? (float) $poItem->price : 0;
                $taxPct = $poItem ? (float) $poItem->tax : 0;
                $receivedQty = (float) $item->received_quantity;
                $total = round($receivedQty * $rate, 2);
                $taxAmount = round($total * $taxPct / 100, 2);
                $grandTotal = round($total + $taxAmount, 2);
            @endphp
            <tr>
                <td>{{ $grn?->branch?->company?->name ?? $grn?->purchaseOrder?->branch?->company?->name ?? '--' }}</td>
                <td>{{ $grn?->branch?->name ?? $grn?->purchaseOrder?->branch?->name ?? '--' }}</td>
                <td>{{ $grn?->grn_number ?? '--' }}</td>
                <td>{{ $grn?->grn_date?->format('d-m-Y') ?? '--' }}</td>
                <td>{{ $grn?->purchaseOrder?->po_number ?? '--' }}</td>
                <td>{{ $grn?->purchaseOrder?->po_date?->format('d-m-Y') ?? '--' }}</td>
                <td>{{ $grn?->vendor?->name ?? '--' }}</td>
                <td>{{ $grn?->vendor?->gst_number ?: '--' }}</td>
                <td>{{ $item->item_code ?: '--' }}</td>
                <td>{{ $item->item_name ?: '--' }}</td>
                <td>{{ $item->itemMaster?->productCategory?->category_name ?? '--' }}</td>
                <td>{{ $item->itemMaster?->productSubCategory?->sub_category_name ?? '--' }}</td>
                <td>{{ $item->hsn ?: '--' }}</td>
                <td>{{ $item->itemMaster?->unit?->unit_name ?? ($item->itemMaster?->uom ?: '--') }}</td>
                <td class="text-end">{{ number_format((float) $item->ordered_quantity, 3) }}</td>
                <td class="text-end">{{ number_format((float) $item->previously_received_quantity, 3) }}</td>
                <td class="text-end">{{ number_format($receivedQty, 3) }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($total, 2) }}</td>
                <td class="text-end">{{ number_format($taxPct, 2) }}</td>
                <td class="text-end">{{ number_format($taxAmount, 2) }}</td>
                <td class="text-end">{{ number_format($grandTotal, 2) }}</td>
                <td>{{ $item->expenseTypeLabel() }}</td>
            </tr>
            @empty
            <tr><td colspan="23">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

<?php

namespace App\Http\Requests;

use App\Models\MsuWorkOrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MsuWorkInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'msu_work_order_id' => 'required|exists:msu_work_orders,id',
            'invoice_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:255',
            'vendor_id' => 'required|exists:vendors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
            'document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

            // Invoiced items
            'items' => 'required|array|min:1',
            'items.*.msu_work_order_item_id' => 'required|exists:msu_work_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rows = $this->input('items', []);
            $workOrderId = (int) $this->input('msu_work_order_id');
            $ids = collect($rows)->pluck('msu_work_order_item_id')->filter()->unique()->values();
            $workOrderItems = MsuWorkOrderItem::with('invoiceItems')
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            $seen = [];
            $hasBillable = false;

            foreach ($rows as $index => $row) {
                $workOrderItem = $workOrderItems->get($row['msu_work_order_item_id'] ?? null);
                if (! $workOrderItem || (int) $workOrderItem->msu_work_order_id !== $workOrderId) {
                    $validator->errors()->add("items.$index.msu_work_order_item_id", 'This line does not belong to the selected work order.');
                    continue;
                }

                if (isset($seen[$workOrderItem->id])) {
                    $validator->errors()->add("items.$index.msu_work_order_item_id", 'This work order line is already on the invoice.');
                    continue;
                }
                $seen[$workOrderItem->id] = true;

                $qty = round((float) ($row['quantity'] ?? 0), 3);
                $rate = round((float) ($row['price'] ?? $workOrderItem->price), 2);
                $woRate = round((float) $workOrderItem->price, 2);
                $remainingQty = $workOrderItem->remainingQuantity();
                $remainingAmount = $workOrderItem->remainingAmount();
                $ordered = (float) $workOrderItem->quantity;

                if ($rate - $woRate > 0.009) {
                    $validator->errors()->add(
                        "items.$index.price",
                        'Billed rate cannot exceed the work order rate of '.number_format($woRate, 2).'.'
                    );
                }

                if ($qty - $remainingQty > 0.0001) {
                    $validator->errors()->add(
                        "items.$index.quantity",
                        $remainingQty <= 0.0001
                            ? 'Quantity is already fully billed. It cannot be entered again.'
                            : 'Billed quantity cannot exceed the remaining quantity of '.number_format($remainingQty, 3).'.'
                    );
                }

                $maxRate = $woRate;
                if ($remainingQty <= 0.0001 && $ordered > 0) {
                    $maxRate = min($woRate, round($remainingAmount / $ordered, 2));
                }

                if ($remainingQty <= 0.0001) {
                    if ($rate - $woRate <= 0.009 && $rate - $maxRate > 0.009) {
                        $validator->errors()->add(
                            "items.$index.price",
                            'Billed rate cannot exceed '.number_format($maxRate, 2).'.'
                        );
                    }
                    $gross = round(min($remainingAmount, $ordered * min($rate, $maxRate)), 2);
                    if ($gross > 0.009 && $rate - $maxRate <= 0.009) {
                        $hasBillable = true;
                    }
                    continue;
                }

                if ($qty > 0.0001) {
                    $hasBillable = true;
                    $gross = round($qty * min($rate, $woRate), 2);
                    if ($gross - $remainingAmount > 0.009) {
                        $validator->errors()->add(
                            "items.$index.price",
                            'Billed amount cannot exceed the remaining amount of '.number_format($remainingAmount, 2).'.'
                        );
                    }
                }
            }

            if (! $hasBillable && $validator->errors()->isEmpty()) {
                $validator->errors()->add('items', 'Nothing left to invoice for this work order.');
            }
        });
    }
}

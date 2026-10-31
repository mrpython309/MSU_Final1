<?php

namespace App\Http\Controllers\API;

use App\Enums\Procurement\MsuWorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MsuWorkOrderRequest;
use App\Models\Branch;
use App\Models\MsuWorkOrder;
use App\Models\MsuWorkOrderItem;
use App\Services\Procurement\DocumentNumberGenerator;
use App\Services\Procurement\MsuWorkOrderRecurrenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MsuWorkOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = MsuWorkOrder::with(['vendor', 'paymentTerm', 'branch', 'department', 'items', 'charges']);

        if ($request->filled('work_order_id')) {
            $query->where('id', $request->work_order_id);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('wo_number')) {
            $query->where('wo_number', 'like', '%' . $request->wo_number . '%');
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('employeeMaster', function ($employee) use ($like) {
                    $employee->where('employee_name', 'like', $like);
                })->orWhereHas('department', function ($department) use ($like) {
                    $department->where('department_name', 'like', $like);
                });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('wo_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('wo_date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (session()->has('global_company_id') || $request->filled('company_id')) {
            $companyId = $request->input('company_id', session('global_company_id'));
            $query->whereHas('branch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        $perPage = $request->input('per_page', 20);
        $workOrders = $query->orderByDesc('id')->paginate($perPage);

        return $this->successWithPagination($workOrders);
    }

    public function store(MsuWorkOrderRequest $request, DocumentNumberGenerator $numberGenerator)
    {
        $wo = DB::transaction(function () use ($request, $numberGenerator) {
            $validated = $request->validated();
            $branch = Branch::with('company')->findOrFail($validated['branch_id']);
            $branchCode = $branch->code ?: $branch->name;
            $companyCode = $branch->company?->code ?: 'MSULLP';
            $woNumber = $numberGenerator->nextMsuWoNumber($companyCode, $branchCode, $validated['wo_date'] ?? now());

            $wo = MsuWorkOrder::create([
                'wo_number' => $woNumber,
                'wo_date' => $validated['wo_date'],
                'contract_period_from' => $validated['contract_period_from'] ?? null,
                'contract_period_to' => $validated['contract_period_to'] ?? null,
                'frequency' => $validated['frequency'] ?? null,
                'subject' => $validated['subject'] ?? null,
                
                'vendor_id' => $validated['vendor_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_master_id' => $validated['employee_master_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'shipping_detail_id' => $validated['shipping_detail_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'miscellaneous_charge_id' => $validated['miscellaneous_charge_id'] ?? null,
                'miscellaneous_charges' => 0,
                'miscellaneous_tax' => 0,
                'sgst' => 0,
                'cgst' => 0,
                'status' => MsuWorkOrderStatus::WoCreated,
                'created_by' => auth()->id(),
                'total_value' => 0,
                'grand_total' => 0,
            ]);

            $this->syncItems($wo, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($wo, $validated['charges']);
            }
            $wo->recalculateTotals();

            return $wo->load(['vendor', 'branch', 'items', 'charges']);
        });

        app(MsuWorkOrderRecurrenceService::class)->syncAndGenerate($wo);

        return $this->success($wo->fresh(['vendor', 'branch', 'items', 'charges']));
    }

    public function show(string $id)
    {
        $wo = MsuWorkOrder::with(['vendor', 'branch', 'paymentTerm', 'miscellaneousCharge', 'employeeMaster', 'department', 'shippingDetail', 'items', 'charges'])->find($id);

        if (!$wo) {
            return $this->error('Work Order not found');
        }

        return $this->success($wo);
    }

    public function update(MsuWorkOrderRequest $request, string $id)
    {
        $msuWorkOrder = MsuWorkOrder::find($id);
        if (!$msuWorkOrder) {
            return $this->error('Work Order not found');
        }

        if (! $msuWorkOrder->statusEnum()->isEditable()) {
            return $this->error('Only Work Orders with status "WO Created" can be edited.');
        }

        DB::transaction(function () use ($request, $msuWorkOrder) {
            $validated = $request->validated();

            $msuWorkOrder->update([
                'wo_date' => $validated['wo_date'],
                'contract_period_from' => $validated['contract_period_from'] ?? null,
                'contract_period_to' => $validated['contract_period_to'] ?? null,
                'frequency' => $msuWorkOrder->recurrence_source_id ? null : ($validated['frequency'] ?? null),
                'subject' => $validated['subject'] ?? null,
                
                'vendor_id' => $validated['vendor_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_master_id' => $validated['employee_master_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'shipping_detail_id' => $validated['shipping_detail_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'miscellaneous_charge_id' => $validated['miscellaneous_charge_id'] ?? null,
                'miscellaneous_charges' => 0,
                'miscellaneous_tax' => 0,
            ]);

            $msuWorkOrder->items()->delete();
            $msuWorkOrder->charges()->delete();
            
            $this->syncItems($msuWorkOrder, $validated['items']);
            if (isset($validated['charges'])) {
                $this->syncCharges($msuWorkOrder, $validated['charges']);
            }
            $msuWorkOrder->recalculateTotals();
        });

        app(MsuWorkOrderRecurrenceService::class)->syncAndGenerate($msuWorkOrder);

        return $this->success($msuWorkOrder->fresh(['vendor', 'branch', 'items', 'charges']));
    }

    public function destroy(string $id)
    {
        $msuWorkOrder = MsuWorkOrder::find($id);
        if (!$msuWorkOrder) {
            return $this->error('Work Order not found');
        }

        if ($msuWorkOrder->statusEnum() !== MsuWorkOrderStatus::WoCreated) {
            return $this->error('Only created Work Orders can be deleted.');
        }

        if ($msuWorkOrder->invoices()->exists()) {
            return $this->error('Cannot delete a Work Order that has invoices.');
        }

        $msuWorkOrder->delete();

        return $this->success('Work Order deleted successfully.');
    }

    private function syncItems(MsuWorkOrder $wo, array $items): void
    {
        foreach (array_values($items) as $index => $row) {
            $qty = (float) $row['quantity'];
            $price = (float) $row['price'];
            $tax = (float) $row['tax'];
            $hsn = ! empty($row['hsn']) ? $row['hsn'] : null;
            $net = MsuWorkOrderItem::computeNetValue($qty, $price, $tax);

            $wo->items()->create([
                'work_description' => $row['work_description'],
                'scope_of_work' => $row['scope_of_work'],
                'hsn' => $hsn,
                'unit_id' => ! empty($row['unit_id']) ? $row['unit_id'] : null,
                'quantity' => $qty,
                'price' => $price,
                'tax' => $tax,
                'net_value' => $net,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncCharges(MsuWorkOrder $wo, array $charges): void
    {
        foreach (array_values($charges) as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if ($amount <= 0 && empty($row['charge_name'])) continue;

            $wo->charges()->create([
                'charge_name' => $row['charge_name'] ?? 'Misc Charge',
                'amount' => $amount,
                'tax_percentage' => (float) ($row['tax_percentage'] ?? 0),
            ]);
        }
    }
}

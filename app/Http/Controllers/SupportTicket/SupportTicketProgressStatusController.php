<?php

namespace App\Http\Controllers\SupportTicket;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportTicketProgressStatusController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SupportTicketProgressStatus::selectable(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'status_name' => ['required', 'string', 'max:50', Rule::unique('support_ticket_progress_statuses', 'name')],
        ], [
            'status_name.unique' => 'A status with this name already exists.',
        ]);

        if ($validator->fails()) {
            return $this->backToModal($validator);
        }

        $name = trim($validator->validated()['status_name']);

        SupportTicketProgressStatus::create([
            'name' => $name,
            'slug' => $this->slugFor($name),
            'color' => 'info',
            'sort_order' => ((int) SupportTicketProgressStatus::query()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Status added.');
    }

    public function update(Request $request, SupportTicketProgressStatus $progressStatus)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'status_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('support_ticket_progress_statuses', 'name')->ignore($progressStatus->id),
            ],
        ], [
            'status_name.unique' => 'A status with this name already exists.',
        ]);

        if ($validator->fails()) {
            return $this->backToModal($validator, $progressStatus->id);
        }

        $progressStatus->name = trim($validator->validated()['status_name']);
        $progressStatus->save();

        return back()->with('success', 'Status updated.');
    }

    public function destroy(SupportTicketProgressStatus $progressStatus)
    {
        $this->ensureAdmin();

        if (SupportTicket::query()->where('status', $progressStatus->slug)->exists()) {
            return back()
                ->with('error', 'This status is used on tickets, so it cannot be deleted.')
                ->with('open_status_modal', true);
        }

        $progressStatus->delete();

        return back()->with('success', 'Status deleted.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(SupportTicketProgressStatus::canManage(), 403);
    }

    private function slugFor(string $name): string
    {
        $base = Str::slug($name, '_');
        $base = $base !== '' ? $base : 'status';

        if (in_array($base, SupportTicketProgressStatus::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages([
                'status_name' => 'This name is reserved for the ticket workflow (Open, Accepted, Completed, Closed, Reopen, Rejected).',
            ]);
        }

        $slug = $base;
        $i = 2;

        while (SupportTicketProgressStatus::query()->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i;
            $i++;
        }

        return $slug;
    }

    private function backToModal($validator, ?int $editingId = null)
    {
        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('open_status_modal', true)
            ->with('editing_status_id', $editingId);
    }
}

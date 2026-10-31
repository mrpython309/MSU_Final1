<?php

namespace App\Http\Controllers\SupportTicket;

use App\Exports\SupportTicketDetailExport;
use App\Exports\SupportTicketListExport;
use App\Http\Controllers\Controller;
use App\Services\ReportPdfExportService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use App\Models\User;
use App\Models\Asset;
use App\Models\Division;
use App\Models\Branch;
use App\Models\Company;
use App\Http\Requests\SupportTicket\SaveSupportTicketRequest;
use App\Models\SupportTicketAssignment;
use App\Models\SupportTicketCommand; 
use Illuminate\Support\Facades\Gate;
use App\Helpers\Files;
use App\Services\SupportTicketCompletionFileService;
use App\Services\SupportTicketReplyFileService;
use App\Models\SupportTicketActivity;
use Illuminate\Support\Str;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Jobs\SendPushNotification;
use App\Services\Procurement\DocumentNumberGenerator;
use Illuminate\Validation\Rule;


class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $divisions = Division::visibleToUser($user)->orderBy('division_name')->get();

        $companies = Company::visibleToUser($user)->active()->get();

        $branches = collect();

        if ($request->company_id) {
            $branches = Branch::visibleToUser($user)
                ->where('status', 'active')
                ->select('id', 'name')
                ->where('company_id', $request->company_id)
                ->get();
        }

        $creatorUsers = User::query()
            ->where('employee_status', 'active')
            ->visibleToUser($user)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $collaboratorUsers = User::query()
            ->where('employee_status', 'active')
            ->visibleToUser($user)
            ->orderBy('name')
            ->select('id', 'name')
            ->when($request->division_id, fn ($q) => $q->where('division_id', $request->division_id))
            ->when($request->branch_id, fn ($q) => $q->whereHas('branches', fn ($b) =>
                $b->where('branches.id', $request->branch_id)
            ))
            ->when(! $request->branch_id && $request->company_id, fn ($q) => $q->whereHas('branches', fn ($b) =>
                $b->where('company_id', $request->company_id)
            ))
            ->get();

        $tickets = SupportTicket::query()
            ->visibleToUser($user)
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->division_id, fn ($q) => $q->where('division_id', $request->division_id))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->subject, fn ($q) => $q->where(function ($inner) use ($request) {
                $like = '%'.$request->subject.'%';
                $inner->where('subject', 'like', $like)
                    ->orWhere('ticket_number', 'like', $like);
            }))
            ->when($request->from_date, fn ($q) => $q->whereDate('due_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('due_date', '<=', $request->to_date))
            ->when($request->created_by, fn ($q) => $q->where('created_by', $request->created_by))
            ->when($request->collaborator_id, fn ($q) => $q->whereHas('assignments', fn ($a) =>
                $a->where('user_id', $request->collaborator_id)->where('is_active', true)
            ))
            ->with(['branch', 'division', 'creator', 'activeAssignments.user'])
            ->latest();

        if (in_array($request->input('export_format'), ['excel', 'pdf'], true)) {
            $exportTickets = $tickets->get();
            $filename = 'support-tickets-'.now()->format('Y-m-d');

            if ($request->input('export_format') === 'excel') {
                return Excel::download(new SupportTicketListExport($exportTickets), $filename.'.xlsx');
            }

            return app(ReportPdfExportService::class)->download(
                'support-tickets.exports.list-pdf',
                ['tickets' => $exportTickets, 'skipCompanyHeader' => true],
                $filename.'.pdf',
                'landscape'
            );
        }

        $tickets = $tickets->paginate($this->ticketPerPage($request))->withQueryString();

        return view('support-tickets.index', compact(
            'creatorUsers',
            'collaboratorUsers',
            'divisions',
            'branches',
            'tickets',
            'companies'
        ));
    }

    private function ticketPerPage(Request $request): int
    {
        $perPage = $request->input('per_page', 50);

        if ($perPage === 'all') {
            return 1000000;
        }

        $perPage = (int) $perPage;

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 50;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies=Company::visibleToUser(auth()->user())->active()->get();

        $divisions = Division::active()->orderBy('division_name')->get();

        $assets = Asset::VisibleToUser(auth()->user())
            ->notDisposed()
            ->select('id', 'asset_name', 'serial_no', 'asset_code', 'company_id', 'branch_id', 'department_id')
            ->orderBy('asset_name')
            ->get();

        $previewTicketNumber = $this->previewTicketNumberValue(
            old('company_id'),
            old('branch_id')
        );

         return view('support-tickets.create',compact('companies','divisions','assets','previewTicketNumber'));
    }

    public function previewTicketNumber(Request $request)
    {
        $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        return response()->json([
            'success' => true,
            'ticket_number' => $this->previewTicketNumberValue(
                $request->input('company_id'),
                $request->input('branch_id')
            ),
        ]);
    }

    private function previewTicketNumberValue(mixed $companyId, mixed $branchId): string
    {
        $companyCode = 'MSULLP';
        $branchCode = 'BRN';

        if ($branchId) {
            $branch = Branch::with('company:id,code')->find($branchId);
            if ($branch) {
                $branchCode = $branch->code ?: ($branch->name ?: 'BRN');
                $companyCode = $branch->company?->code ?: $companyCode;
            }
        } elseif ($companyId) {
            $companyCode = Company::query()->whereKey($companyId)->value('code') ?: 'MSULLP';
        }

        return app(DocumentNumberGenerator::class)->previewSupportTicketNumber($companyCode, $branchCode, now());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveSupportTicketRequest $request)
    {
        $data = $request->validated();

    if ($request->hasFile('filename') && $request->file('filename')->isValid()) {
        $data['filename'] = Files::uploadLocalOrS3(
            $request->file('filename'),
            SupportTicket::FILE_PATH
        );
    }

    DB::transaction(function () use ($data) {

        $supportTicket = SupportTicket::create($data);

        SupportTicketActivity::create([
            'support_ticket_id' => $supportTicket->id,
            'user_id'           => auth()->id(),
            'event'             => 'created',
        ]);

        $fcmtokens = $supportTicket->getDepartmentAllUsers();

        if(!empty($fcmtokens)) {

            $title = "New Support Ticket Created";

            $divisionName = $supportTicket->division->division_name ?? '';

            $ticketLabel = $supportTicket->displayTicketNumber();

            $message = $divisionName !== ''
                ? "A new ticket {$ticketLabel} has been submitted under {$divisionName}. Please review and take action."
                : "A new ticket {$ticketLabel} has been submitted. Please review and take action.";

            $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$supportTicket->id
                ];

            SendPushNotification::dispatch($fcmtokens, $title, $message, $data);

        }

    });


       

    return redirect()
        ->route('support-ticket.index')
        ->with('success', 'Support Ticket created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SupportTicket $supportTicket)
    {
        $supportTicket->loadMissing([
            'branch',
            'division',
            'company',
            'asset',
            'creator.designation',
            'activeAssignments.user.designation',
            'activities.user.designation',
            'commands.user.designation',
            'vendor',
        ]);

        if (in_array(request('export_format'), ['excel', 'pdf'], true)) {
            $filename = 'support-ticket-'.$supportTicket->id.'-'.now()->format('Y-m-d');

            if (request('export_format') === 'excel') {
                return Excel::download(new SupportTicketDetailExport($supportTicket), $filename.'.xlsx');
            }

            return app(ReportPdfExportService::class)->download(
                'support-tickets.exports.detail-pdf',
                ['supportTicket' => $supportTicket, 'skipCompanyHeader' => true],
                $filename.'.pdf',
                'portrait'
            );
        }

        $asignedIds=$supportTicket->assignments()->select('user_id')->pluck('user_id')->toArray(); 

        $assignAnotherUser=User::whereNotIn('id', $asignedIds)
            ->where('employee_status', 'active')
            ->whereHas('branches', function ($q) use ($supportTicket) {
                $q->where('branches.id', $supportTicket->branch_id);
            })
            ->when($supportTicket->division_id, fn ($q) => $q->where('division_id', $supportTicket->division_id))
            ->get();

        $activeAssignments = $supportTicket->activeAssignments()->with('user.designation')
    ->get();

        $isAssignedTicket = $supportTicket->isAssignedTo(auth()->user());

        $vendors=Vendor::active()->get();


        return view('support-tickets.show', compact('supportTicket','assignAnotherUser','activeAssignments','isAssignedTicket','vendors'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupportTicket $supportTicket)
    {
         $supportTicket->loadMissing(['branch', 'division', 'company', 'asset']);
         $companies=Company::visibleToUser(auth()->user())->active()->get();

        $divisions = Division::query()
            ->where(function ($query) use ($supportTicket) {
                $query->where('status', 'active');
                if ($supportTicket->division_id) {
                    $query->orWhere('id', $supportTicket->division_id);
                }
            })
            ->orderBy('division_name')
            ->get();

        $branches=Branch::visibleToUser(auth()->user())->Where('id', $supportTicket->branch_id)->active()->get();

        $assets = Asset::VisibleToUser(auth()->user())
            ->where(function ($query) use ($supportTicket) {
                $query->notDisposed();
                if ($supportTicket->asset_id) {
                    $query->orWhere('id', $supportTicket->asset_id);
                }
            })
            ->select('id', 'asset_name', 'serial_no', 'asset_code', 'company_id', 'branch_id', 'department_id')
            ->orderBy('asset_name')
            ->get();

        return view('support-tickets.edit',compact('supportTicket','companies','divisions','branches','assets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveSupportTicketRequest $request, SupportTicket $supportTicket)
    {

       $data = $request->validated();

    if ($request->hasFile('filename') && $request->file('filename')->isValid()) {
        $data['filename'] = Files::uploadLocalOrS3(
            $request->file('filename'),
            SupportTicket::FILE_PATH
        );
    }

    DB::transaction(function () use ($supportTicket, $data) {

        // Fill model but don't save yet
        $supportTicket->fill($data);

        // Get only changed fields
        $dirty = $supportTicket->getDirty();

        if (!empty($dirty)) {

            $original = $supportTicket->getOriginal();

            $supportTicket->save();

            $changes = [];

            foreach ($dirty as $field => $newValue) {

                  $oldValue = $original[$field] ?? null;

                if ($oldValue == $newValue) {
                    continue;
                }

                if ($field == 'asset_id') {

                    $field="Asset";

                    $oldDept = Asset::find($oldValue);
                    $newDept = Asset::find($newValue);

                    $oldValue = $oldDept->asset_name ?? null;
                    $newValue = $newDept->asset_name ?? null;
                }


                if ($field == 'division_id') {

                    $field="Division";

                    $oldDivision = Division::find($oldValue);
                    $newDivision = Division::find($newValue);

                    $oldValue = $oldDivision->division_name ?? null;
                    $newValue = $newDivision->division_name ?? null;
                }

                if ($field == 'company_id') {

                    $field="Company";

                    $oldDept = Company::find($oldValue);
                    $newDept = Company::find($newValue);

                    $oldValue = $oldDept->name ?? null;
                    $newValue = $newDept->name ?? null;
                }

                if ($field == 'branch_id') {

                    $field="Branch";

                    $oldDept = Branch::find($oldValue);
                    $newDept = Branch::find($newValue);

                    $oldValue = $oldDept->name ?? null;
                    $newValue = $newDept->name ?? null;
                }

                if ($field == 'due_date') {

                    $field="Due Date";

            $oldValue = $oldValue ? Carbon::parse($oldValue)->format('Y-m-d') : null;
            $newValue = $newValue ? Carbon::parse($newValue)->format('Y-m-d') : null;
        }





                $changes[$field] = [
                    'old' => $oldValue ?? null,
                    'new' => $newValue,
                ];
            }

            SupportTicketActivity::create([
                'support_ticket_id' => $supportTicket->id,
                'user_id'           => auth()->id(),
                'event'             => 'updated',
                'meta'              => [
                    'changes' => $changes,
                ],
            ]);
        }
    });

    return redirect()
        ->route('support-ticket.index')
        ->with('success', 'Support Ticket updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupportTicket $supportTicket)
    {
        $supportTicket->delete();

        return redirect()
            ->route('support-ticket.index')
            ->with('success', 'Support Ticket deleted successfully.');
        
    }

    public function acceptTicket(SupportTicket $ticket){

       // $this->authorize('close', $ticket);

        Gate::authorize('accept', $ticket);

        if($ticket->status!='open'){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'Support Ticket already accepted.');
        }

        $assignment=SupportTicketAssignment::Where('support_ticket_id', $ticket->id)->Where('user_id', auth()->user()->id)->first();

        if($assignment){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'You are alreadty exited from this ticket.');
        }

        if(!$assignment){

            $assignment= new SupportTicketAssignment();

            $assignment->support_ticket_id=$ticket->id;

            $assignment->user_id=auth()->user()->id;

            $assignment->assigned_by=auth()->user()->id;

            $assignment->is_active=true;

            $assignment->assigned_at=now();
        }

         $assignment->save(); 

         $ticket->status='accepted';

         $ticket->save();


        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

           if(!empty($ticketTokens)) {

                $title = "Ticket Accepted";

                $userName=auth()->user()->name;

                $message = "Your ticket #{$ticket->id} has been accepted by {$userName} and is currently being processed";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

            }




         SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event' => 'accepted',
            ]);

        
        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket accepted successfully.');
    }

    public function completeTicket(Request $request, SupportTicket $ticket)
    {
        $rules = SupportTicketCompletionFileService::validationRules();

        if ($ticket->asset_id) {
            $rules['vendor_id'] = ['required', 'exists:vendors,id'];
            $rules['total_cost'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['vendor_id'] = ['nullable', 'exists:vendors,id'];
            $rules['total_cost'] = ['nullable', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        if (! $ticket->isAssignedTo(auth()->user())) {
            return redirect()
                ->route('support-ticket.show', $ticket->id)
                ->with('error', 'You are not allowed to assign this ticket.');
        }

        $completionFiles = app(SupportTicketCompletionFileService::class);
        $storedFiles = $completionFiles->store($completionFiles->filesFromRequest($request));

        if ($storedFiles === []) {
            return redirect()
                ->route('support-ticket.show', $ticket->id)
                ->with('error', 'Please attach at least one valid file.');
        }

        $ticket->status = 'completed';
        $ticket->vendor_id = $request->filled('vendor_id') ? $request->vendor_id : null;
        $ticket->total_cost = $request->filled('total_cost') ? (float) $request->total_cost : null;
        $completionFiles->applyToTicket($ticket, $storedFiles);
        $ticket->completed_ticket_commands = $request->completed_ticket_commands;

        $ticket->save();

         $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

         if(!empty($ticketTokens)) {

                $title = "Ticket Completed";

                $message = "Your ticket #{$ticket->id} has been marked as completed by the support team. Please review the resolution.";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

         }

        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket completed successfully.');

        
    }




    public function closeTicket(Request $request,SupportTicket $ticket){        

        Gate::authorize('close', $ticket);

         if($ticket->status!='completed'){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'Support Ticket cannot be closed.');
        }

        $ticket->status='closed';       

        $ticket->save();

         $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

         if(!empty($ticketTokens)) {

                $title = "Ticket Closed";

                $message = "Ticket #{$ticket->id} has been successfully closed.";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

         }
        
        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket closed successfully.');
        

    }


    public function reopenTicket(Request $request,SupportTicket $ticket){

         Gate::authorize('reopen', $ticket);

         if($ticket->status!='closed'){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'Support Ticket cannot be Reopened.');
        }

        $ticket->status='reopen';       

        $ticket->save();

        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if(!empty($ticketTokens)) {

                $title = "Ticket Reopened";

                $message = "Ticket #{$ticket->id} has been reopened. The support team will continue working on the issue.";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

        }
        
        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket reopened successfully.');

    }


    public function rejectTicket(Request $request, SupportTicket $ticket)
    {
        Gate::authorize('reject', $ticket);

        if ($ticket->status != 'completed') {
            return redirect()
                ->route('support-ticket.show', $ticket->id)
                ->with('error', 'Support Ticket cannot be Rejected.');
        }

        $request->validate([
            'reject_reason' => 'required|string|max:5000',
        ]);

        $ticket->status = 'rejected';
        $ticket->save();

        // Save rejection reason as command response
        $command = new SupportTicketCommand();
        $command->support_ticket_id = $ticket->id;
        $command->user_id = auth()->id();
        $command->ticket_response = 'Rejected: ' . $request->reject_reason;
        $command->save();

        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if (!empty($ticketTokens)) {
            $title = "Ticket Rejected";
            $message = "Your completed ticket #{$ticket->id} has been rejected by the creator: " . Str::limit($request->reject_reason, 50);
            $data = [
                'type' => 'support_ticket',
                'ref_id' => (string)$ticket->id
            ];

            SendPushNotification::dispatch($ticketTokens, $title, $message, $data);
        }

        return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('success', 'Support Ticket rejected successfully.');
    }


    public function assignTicket(Request $request,SupportTicket $ticket){

        $assignment=SupportTicketAssignment::Where('support_ticket_id', $ticket->id)->Where('user_id', $request->user_id)->first();

        if($assignment){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'Support Ticket already assigned.');
        }

        if(!$ticket->isAssignedTo(auth()->user())){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'You are not allowed to assign this ticket.');
        }

        $assignment= new SupportTicketAssignment();

        $assignment->support_ticket_id=$ticket->id;

        $assignment->user_id=$request->user_id; 

        $assignment->assigned_by=auth()->user()->id;

        $assignment->is_active=true;

        $assignment->assigned_at=now();

        $assignment->save();    

        $assignedUser=[];

        foreach($ticket->assignments as $assignment){

            $userData=User::find($assignment->user_id);

            $assignedUser[]=$userData->name;
        }


        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if(!empty($ticketTokens)) {

                $title = "Collaborator Added to Ticket";

                $message = "A collaborator has been added to ticket #{$ticket->id} They can now view updates and participate in resolving this ticket.";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

        }

        SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event' => 'assigned',
                'meta' => [
                        'assigned_to' => implode(', ',$assignedUser),
                    ],
            ]);

        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket assigned successfully.');
    }

    public function exitTicket(Request $request,SupportTicket $ticket){

          Gate::authorize('exit', $ticket);
       
          $request->validate([
              'exit_reason' => 'required|string|max:5000',
          ]);

          $ticket->releaseBy(auth()->user());

          // Save exit reason as command response
          $command = new SupportTicketCommand();
          $command->support_ticket_id = $ticket->id;
          $command->user_id = auth()->id();
          $command->ticket_response = 'Exited: ' . $request->exit_reason;
          $command->save();

          SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event' => 'exited',
                'meta' => ['reason' => $request->exit_reason],
            ]);

           return back()->with('success', 'You have exited the ticket.');


    }

    public function replyTicket(Request $request, SupportTicket $ticket)
    {
        $request->validate(array_merge([
            'ticket_response' => 'nullable|string',
            'voice_note' => 'nullable|file|mimes:webm,mp3,wav,ogg|max:5120',
            'recording_duration' => 'nullable|integer|min:1|max:300',
        ], SupportTicketReplyFileService::attachmentValidationRules()));

        $replyFiles = app(SupportTicketReplyFileService::class);
        $uploadedFiles = $replyFiles->filesFromRequest($request);

        if (empty($request->ticket_response) && ! $request->hasFile('voice_note') && $uploadedFiles === []) {
            return back()->with('error', 'Please enter a response, attach a file, or upload a voice note.');
        }

        $command = new SupportTicketCommand();
        $command->support_ticket_id = $ticket->id;
        $command->user_id = auth()->user()->id;
        $command->ticket_response = $request->ticket_response;
        $command->recording_duration = $request->recording_duration;

        if ($uploadedFiles !== []) {
            $replyFiles->applyToCommand($command, $replyFiles->store($uploadedFiles));
        }

        if ($request->hasFile('voice_note') && $request->file('voice_note')->isValid()) {
            $command->voice_note = Files::uploadLocalOrS3($request->voice_note, SupportTicketCommand::FILE_PATH);
        }

        $command->save();


        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if(!empty($ticketTokens)) {

                $title = "New Comment on Ticket";

                $commandedBy=auth()->user()->name;

                $message = "A new comment has been added to ticket #{$ticket->id} by {$commandedBy}. Please review the latest update.";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

        }


        SupportTicketActivity::create([
    'support_ticket_id' => $ticket->id,
    'user_id' => auth()->id(),
    'event' => 'commented',
    'meta' => [
        'message' => Str::limit($request->ticket_response, 100),
    ],
]);


        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket replied successfully.');
    }

    public function updateStatus(Request $request,SupportTicket $ticket){

        $request->validate([
            'status' => [
                'required',
                'string',
                Rule::exists('support_ticket_progress_statuses', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        if(!($ticket->isAssignedTo(auth()->user()))){

            return redirect()
            ->route('support-ticket.show', $ticket->id)
            ->with('error', 'You are not allowed to update this ticket.');

        }

        $ticket->status=$request->status;

        $ticket->save();

        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if(!empty($ticketTokens)) {

                $title = "Ticket Status Updated";

                $commandedBy=auth()->user()->name;

                $statusLabel = SupportTicketProgressStatus::plain($request->status);
                $message = "The status of ticket #{$ticket->id} has been updated to {$statusLabel} by {$commandedBy}";

                $data = [
                    'type' => 'support_ticket',
                    'ref_id' => (string)$ticket->id
                ];

                SendPushNotification::dispatch($ticketTokens, $title, $message, $data);

        }


        return redirect()
        ->route('support-ticket.show', $ticket->id)
        ->with('success', 'Support Ticket status updated successfully.');


    }

    
}

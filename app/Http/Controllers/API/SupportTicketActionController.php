<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use App\Models\SupportTicketCommand;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\SupportTicketAssignment;
use App\Models\SupportTicketActivity;
use App\Models\User;
use App\AssetStatus;
use App\Models\Asset;
use App\Jobs\SendPushNotification;
use App\Helpers\Files;
use App\Services\SupportTicketCompletionFileService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SupportTicketActionController extends Controller
{
    use ApiResponse;
    
    public function acceptTicket(Request $request,$id){

        $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!Gate::authorize('accept', $ticket)){
            
            return $this->error('You are not allowed to accept this ticket.');

        }

         $assignment=SupportTicketAssignment::Where('support_ticket_id', $ticket->id)->Where('user_id', auth()->user()->id)->first();

        if($assignment){

            return $this->error('You are alreadty exited from this ticket.');
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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

            }



         SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event' => 'accepted',
            ]);
        
        return $this->success('Ticket accepted successfully.');
        
    }
 

    public function closeTicket(Request $request,$id){       

         $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!Gate::authorize('close', $ticket)){
            
            return $this->error('You are not allowed to close this ticket.');

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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

         }
        
        return $this->success('Support Ticket closed successfully.');
        
    }  
    
    
    public function reopenTicket(Request $request,$id){

        $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!Gate::authorize('reopen', $ticket)){
            
            return $this->error('You are not allowed to reopen this ticket.');
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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

        }
        
        return $this->success('Support Ticket reopened successfully.');

    }

    public function rejectTicket(Request $request, $id)
    {
        $ticket = SupportTicket::find($id);

        if (!$ticket) {
            return $this->error('Ticket not found');
        }

        if ($ticket->status != 'completed') {
            return $this->error('Support Ticket cannot be Rejected.');
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

        SupportTicketActivity::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event' => 'rejected',
            'meta' => ['reason' => $request->reject_reason],
        ]);

        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if (!empty($ticketTokens)) {
            $title = "Ticket Rejected";
            $message = "Your completed ticket #{$ticket->id} has been rejected: " . Str::limit($request->reject_reason, 50);
            $data = [
                'type' => 'support_ticket',
                'ref_id' => (string)$ticket->id
            ];

            SendPushNotification::dispatch($ticketTokens, $title, $message, $data);
        }

        return $this->success('Support Ticket rejected successfully.');
    }


    public function completeTicket(Request $request, $id)
    {
        $ticket = SupportTicket::find($id);

        if (! $ticket) {
            return $this->error('Ticket not found');
        }

        $rules = SupportTicketCompletionFileService::validationRules(api: true);

        // Sanitize / normalize empty string or null string inputs
        if ($request->has('vendor_id') && ($request->vendor_id === '' || $request->vendor_id === 'null')) {
            $request->merge(['vendor_id' => null]);
        }
        if ($request->has('total_cost') && ($request->total_cost === '' || $request->total_cost === 'null')) {
            $request->merge(['total_cost' => null]);
        }

        if ($ticket->asset_id) {
            if (!$request->filled('vendor_id')) {
                $fallbackVendorId = $ticket->vendor_id ?: ($ticket->asset?->vendor_id ?: \App\Models\Vendor::value('id'));
                if ($fallbackVendorId) {
                    $request->merge(['vendor_id' => $fallbackVendorId]);
                }
            }
            if (!$request->filled('total_cost')) {
                $request->merge(['total_cost' => 0]);
            }
            $rules['vendor_id'] = ['nullable', 'exists:vendors,id'];
            $rules['total_cost'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $rules['vendor_id'] = ['nullable', 'exists:vendors,id'];
            $rules['total_cost'] = ['nullable', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        if (! $ticket->isAssignedTo(auth()->user())) {
            return $this->error('You are not allowed to complete this ticket.');
        }

        $completionFiles = app(SupportTicketCompletionFileService::class);
        $storedFiles = $completionFiles->store($completionFiles->filesFromRequest($request));

        if ($storedFiles === []) {
            return $this->error('Please attach at least one valid file.');
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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

         }

        return $this->success('Support Ticket completed successfully.');
    }


    public function exitTicket(Request $request,$id){

         $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!Gate::authorize('exit', $ticket)){
            
            return $this->error('You are not allowed to exit this ticket.');

        }

        $request->validate([
            'exit_reason' => 'nullable|string|max:5000',
        ]);

        $ticket->releaseBy(auth()->user());

        if ($request->exit_reason) {
            $command = new SupportTicketCommand();
            $command->support_ticket_id = $ticket->id;
            $command->user_id = auth()->id();
            $command->ticket_response = 'Exited: ' . $request->exit_reason;
            $command->save();
        }

        SupportTicketActivity::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event' => 'exited',
            'meta' => $request->exit_reason ? ['reason' => $request->exit_reason] : null,
        ]);

       return $this->success('You have exited the ticket.');
        
    }

    public function assignTicket(Request $request,$id){

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

         $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!($ticket->isAssignedTo(auth()->user()))){

            return $this->error('You are not allowed to assign this ticket.');
        }


        $assignedUser=User::find($request->user_id);

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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

        }

        SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event' => 'assigned',
                'meta' => [
                        'assigned_to' => implode(', ', $assignedUser),
                    ],
            ]);
        
        return $this->success('Support Ticket assigned successfully.');


        
    }

    public function updateStatus(Request $request,$id){

        $allowed = SupportTicketProgressStatus::query()
            ->where('is_active', true)
            ->pluck('slug')
            ->push('completed')
            ->push('rejected')
            ->unique()
            ->values()
            ->all();

        $request->validate([
            'status' => ['required', 'string', Rule::in($allowed)],
        ]);

         $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        if(!($ticket->isAssignedTo(auth()->user()))){

            return $this->error('You are not allowed to update this ticket.');
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

                SendPushNotification::dispatch($ticketTokens, $title, $message,$data);

        }
        
        
        return $this->success('Support Ticket status updated successfully.');
        
    }

    public function getAssignedPersons($id){

        $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        $assignedUsers=SupportTicketAssignment::Where('support_ticket_id', $ticket->id)->Where('is_active', true)->with(['user','user.division','user.designation','user.departments'])->get();

        return $this->success($assignedUsers);

    }

    public function getActivityLogs($id){

        $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }

        $activities=SupportTicketActivity::Where('support_ticket_id', $ticket->id)->with('user')->get();

        return $this->success($activities);


    }


    public function getAssetCode($id){

        $asset=Asset::Where('asset_code', 'like', '%'.$id.'%')->first();

        if(!$asset || $asset->status === AssetStatus::DISPOSED){

            return $this->error('Asset not found');
        }


        return $this->success($asset);
    }

    public function getAssignableUsers($id){

        $ticket=SupportTicket::find($id);

        if(!$ticket){

            return $this->error('Ticket not found');
        }


        $assignedUsers=SupportTicketAssignment::Where('support_ticket_id', $ticket->id)->Where('is_active', true)->pluck('user_id')->toArray();


        $users=User::WhereNotIn('id',$assignedUsers)->WhereHas('branches', function ($q) use ($ticket) {
            $q->where('branches.id', $ticket->branch_id);
        })->when($ticket->division_id, function ($q) use ($ticket) {
            $q->where('division_id', $ticket->division_id);
        })->get();



        return $this->success($users);


    }
}

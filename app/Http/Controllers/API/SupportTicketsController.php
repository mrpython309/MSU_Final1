<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\SupportTicket;
use App\Http\Requests\SupportTicket\SaveSupportTicketRequest;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use App\Models\SupportTicketProgressStatus;
use App\Models\SupportTicketActivity;
use App\Helpers\Files;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendPushNotification;

class SupportTicketsController extends Controller
{

     use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $supportTickets=SupportTicket::with([
        'company:id,name',
        'branch:id,name',
        'division:id,division_name',
        'asset:id,asset_name',
        'creator:id,name'
    ])
    ->visibleToUser(auth()->user())

     ->when($request->company_id, function ($q) use ($request) {
        $q->where('company_id', $request->company_id);
    })

    ->when($request->branch_id, function ($q) use ($request) {
        $q->where('branch_id', $request->branch_id);
    })

    ->when($request->division_id, function ($q) use ($request) {
        $q->where('division_id', $request->division_id);
    })

    ->when($request->priority, function ($q) use ($request) {
        $q->where('priority', $request->priority);
    })

    ->when($request->status, function ($q) use ($request) {
        $q->where('status', $request->status);
    })

    ->when($request->subject, function ($q) use ($request) {
        $like = '%' . $request->subject . '%';
        $q->where(function ($inner) use ($like) {
            $inner->where('subject', 'like', $like)
                ->orWhere('ticket_number', 'like', $like);
        });
    })

    ->when($request->from_date, function ($q) use ($request) {
        $q->whereDate('due_date', '>=', $request->from_date);
    })

    ->when($request->to_date, function ($q) use ($request) {
        $q->whereDate('due_date', '<=', $request->to_date);
    })

    ->latest()   // same as orderBy('id','DESC')
    ->paginate(50)
    ->withQueryString();

        return $this->successWithPagination($supportTickets);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveSupportTicketRequest $request)
    {
         $data = $request->validated();

        if($request->hasFile('filename') && $request->file('filename')->isValid()){

            $data['filename'] = Files::uploadLocalOrS3($request->filename, SupportTicket::FILE_PATH);
        }

        $supportTicket = DB::transaction(function () use ($data) {


        $supportTicket=SupportTicket::create($data);

        SupportTicketActivity::create([
            'support_ticket_id' => $supportTicket->id,
            'user_id' => auth()->id(),
            'event' => 'created',
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

            SendPushNotification::dispatch($fcmtokens, $title, $message,$data);

        }

        return $supportTicket;

        });

        return $this->success($supportTicket);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $supportTicket=SupportTicket::with(['company:id,name','branch:id,name','division:id,division_name','asset:id,asset_name','commands','activities','creator:id,name'])->find($id);

        if(!$supportTicket){

            return $this->error('Support Ticket not found');

        }

        return $this->success($supportTicket);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveSupportTicketRequest $request, string $id)
    {
        
        $supportTicket = SupportTicket::find($id);

            if (!$supportTicket) {
                return $this->error('Support Ticket not found');
            }

            $data = $request->validated();

            if ($request->hasFile('filename') && $request->file('filename')->isValid()) {
                $data['filename'] = Files::uploadLocalOrS3(
                    $request->filename,
                    SupportTicket::FILE_PATH
                );
            }

            $supportTicket = DB::transaction(function () use ($supportTicket, $data) {

                $oldData = $supportTicket->only([
                    'subject',
                    'description',
                    'priority',
                    'due_date',
                    'division_id',
                    'asset_id',
                    'branch_id',
                ]);

                $supportTicket->update($data);

                $supportTicket->refresh(); // ensure latest values

                $newData = $supportTicket->only(array_keys($oldData));

                $changedFields = [];

                foreach ($oldData as $field => $oldValue) {
                    if ($oldValue != $newData[$field]) {
                        $changedFields[$field] = [
                            'old' => $oldValue,
                            'new' => $newData[$field],
                        ];
                    }
                }

                if (!empty($changedFields)) {
                    SupportTicketActivity::create([
                        'support_ticket_id' => $supportTicket->id,
                        'user_id' => auth()->id(),
                        'event' => 'updated',
                        'meta' => [
                            'changes' => $changedFields,
                        ],
                    ]);
                }

                return $supportTicket;
            });

            return $this->success($supportTicket);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supportTicket=SupportTicket::find($id);

        if(!$supportTicket){

            return $this->error('Support Ticket not found');
        }

       DB::transaction(function () use ($supportTicket) {

            $supportTicket->delete();
        });

        return $this->success('Support Ticket deleted successfully');
    }

    public function status(){
        try {
            $dynamic = SupportTicketProgressStatus::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if ($dynamic->isNotEmpty()) {
                return $this->success($dynamic->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'slug' => $item->slug,
                    ];
                }));
            }
        } catch (\Throwable $e) {}

        $status=SupportTicketStatus::cases();

        return $this->success($status);
    }

    public function progressStatuses(){
        try {
            $dynamic = SupportTicketProgressStatus::selectable();
            return $this->success($dynamic->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                ];
            }));
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function priority(){

        $priority=SupportTicketPriority::cases();

        return $this->success($priority);
    }
}

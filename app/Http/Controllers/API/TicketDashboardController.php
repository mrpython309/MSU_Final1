<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\Department;
use App\SupportTicketPriority;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class TicketDashboardController extends Controller
{
    use ApiResponse;

    public function ticket(){

         $dashboard=array();

         $dashboard['open_ticket_count']=SupportTicket::visibleToUser(auth()->user())->Where('status','open')->count();

         $dashboard['accepted_ticket_count']=SupportTicket::visibleToUser(auth()->user())->Where('status','accepted')->count();

         $dashboard['on_hold_ticket_count']=SupportTicket::visibleToUser(auth()->user())->Where('status','hold')->count();

         $dashboard['completed_ticket_count']=SupportTicket::visibleToUser(auth()->user())->Where('status','completed')->count();

         $dashboard['closed_ticket_count']=SupportTicket::visibleToUser(auth()->user())->Where('status','closed')->count();

        $departments=Department::visibleToUser(auth()->user())->get();

        // 🔹 Priority + Status Matrix
        $priorities = [
            SupportTicketPriority::HIGH,
            SupportTicketPriority::NORMAL,
            SupportTicketPriority::LOW,
        ];

        $statuses = ['open', 'hold', 'closed','completed'];

        $priorityMatrix = [];

        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {

                $priorityMatrix[$priority->value][$status] =
                    SupportTicket::visibleToUser(auth()->user())
                        ->where('priority', $priority->value)
                        ->where('status', $status)
                        ->count();
            }
        }

        $dashboard['priorityMatrix'] = $priorityMatrix;

        // Get ticket counts grouped by department + status
        $departmentWiseTickets = SupportTicket::visibleToUser(auth()->user())
            ->select('department_id', 'status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', ['open', 'hold', 'closed','completed'])
            ->groupBy('department_id', 'status')
            ->get();           

        $departmentMatrix = [];

        foreach($departments as $department) {
            if (strtolower(trim($department->department_name)) === 'general') { continue; }

            $departmentMatrix[$department->id] = [
                'department_name' => $department->department_name,
                'open' => $departmentWiseTickets->where('department_id', $department->id)->where('status', 'open')->first()->total ?? 0,
                'hold' => $departmentWiseTickets->where('department_id', $department->id)->where('status', 'hold')->first()->total ?? 0,
                'closed' => $departmentWiseTickets->where('department_id', $department->id)->where('status', 'closed')->first()->total ?? 0,
                'completed' => $departmentWiseTickets->where('department_id', $department->id)->where('status', 'completed')->first()->total ?? 0,
            ];
        }

         $dashboard['departmentMatrix'] = $departmentMatrix;

        $tickets = SupportTicket::visibleToUser(auth()->user())
        ->with(['branch', 'department'])
        ->OrderBy('id', 'desc')
        ->take(5) // Show only 5 recent tickets for dashboard
        ->get();

        $dashboard['recentTickets'] = $tickets;

         $statusData = SupportTicket::visibleToUser(auth()->user())
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

        $dashboard['statusData'] = $statusData;

        return $this->success($dashboard);

    }
}

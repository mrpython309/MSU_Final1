 <!-- Ticket Information --> 

        <div class="card card-action mb-6">
      <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0">Ticket {{ $supportTicket->displayTicketNumber() }}</h5>
        <div class="card-action-element ms-2">
          {!! $supportTicket->status_label !!}
        </div>
      </div>
      <div class="card-body">
      
         <table class="table table-sm table-borderless mb-0">

                       

                        <tr>
                            <td class="fw-semibold">Priority</td>
                            <td>
                                {!! $supportTicket->priority_label !!}
                            </td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Due Date</td>
                            <td>
                                {{ $supportTicket->due_date ? date('d M Y', strtotime($supportTicket->due_date)) : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Company</td>
                            <td>{{ $supportTicket->company->name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Branch</td>
                            <td>{{ $supportTicket->branch->name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Division</td>
                            <td>{{ $supportTicket->division->division_name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Asset</td>
                            <td>{{ $supportTicket->asset->asset_name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Created By</td>
                            <td>{{ $supportTicket->creator->name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold">Created At</td>
                            <td>{{ $supportTicket->created_at->format('d M Y h:i A') }}</td>
                        </tr>

                         @if($supportTicket->vendor)
                          <tr>
                            <td class="fw-semibold">Vendor</td>
                            <td>{{ $supportTicket->vendor->name ?? '-' }}</td>
                        </tr>
                        @endif

                        @if($supportTicket->total_cost)
                          <tr>
                            <td class="fw-semibold">Total Cost</td>
                            <td>{{ $supportTicket->total_cost ?? '-' }}</td>
                        </tr>
                        @endif

                         @if($supportTicket->completed_time)

                        <tr>
                            <td class="fw-semibold">Time to take Completion</td>
                            <td>
                                @php
$minutes = $supportTicket->completed_time;
@endphp

{{ floor($minutes/60) }}h {{ $minutes%60 }}m
                                
                               </td>
                        </tr>
                         @endif

                        @if($supportTicket->completed_ticket_commands)
                          <tr>
                            <td class="fw-semibold">Complete Ticket Commands</td>
                            <td>{{ $supportTicket->completed_ticket_commands ?? '-' }}</td>
                        </tr>
                        @endif

                        @if($supportTicket->completed_filenames !== [])
                          <tr>
                            <td class="fw-semibold">Files on Completion</td>
                            <td>
                                @include('support-tickets.partials.completed-files')
                            </td>
                        </tr>
                        @endif


                         @if($supportTicket->completed_at)
                          <tr>
                            <td class="fw-semibold">Completed At</td>
                            <td>{{ $supportTicket->completed_at->format('d M Y h:i A') ?? '-' }}</td>
                        </tr>
                        @endif

                    </table>

                    

      </div>
    </div>
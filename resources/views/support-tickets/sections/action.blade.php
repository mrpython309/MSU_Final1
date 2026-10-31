   <!-- Action Card -->
      @canany(['accept', 'exit', 'close','reopen', 'reject'], $supportTicket)
       <div class="card mb-6">
        <div class="card-body">

          @can('accept', $supportTicket)
          <a href="{{ route('support-ticket.accept',$supportTicket->id) }}" class="btn btn-primary w-100 mb-3">Accept</a>
          @endcan

          @can('exit', $supportTicket)

            <form method="POST" action="{{route('support-ticket.exit', $supportTicket->id)}}" class="mt-4 exit-ticket-from">

                @csrf
                <button type="submit" class="btn btn-danger d-grid w-100">
                    <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="icon-base bx bx-paper-plane icon-sm me-2"></i>Exit Ticket</span>
                </button>


            </form>

            <hr>

              @php
                $ticketProgressStatuses = \App\Models\SupportTicketProgressStatus::selectable();
                $canManageTicketStatuses = \App\Models\SupportTicketProgressStatus::canManage();
              @endphp

              <form method="POST" action="{{route('support-ticket.status.update', $supportTicket->id)}}">
              <div>
              <div class="d-flex align-items-center justify-content-between">
                <label for="status" class="form-label mb-0">Status</label>
                @if($canManageTicketStatuses)
                <button type="button" class="btn btn-sm btn-icon btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#manageTicketStatusModal" title="Add status" aria-label="Add status">
                  <i class="icon-base bx bx-plus"></i>
                </button>
                @endif
              </div>
              <select class="form-select mb-6 mt-2" id="status" name="status" required>
                <option value="">Select Status</option>
                @foreach($ticketProgressStatuses as $progressStatus)
                <option value="{{ $progressStatus->slug }}" {{ $supportTicket->status == $progressStatus->slug ? 'selected' : '' }}>{{ $progressStatus->name }}</option>
                @endforeach
              </select>
              @error('status')
                <div class="text-danger small mb-2">{{ $message }}</div>
              @enderror
              </div>
              @method('PUT')
              @csrf
              <button type="submit" class="btn btn-success d-grid w-100">
                <span class="d-flex align-items-center justify-content-center text-nowrap"> Update Status</span>
              </button>
            </form>

            @if($canManageTicketStatuses)
              @include('support-tickets.model.manage-status', ['ticketProgressStatuses' => $ticketProgressStatuses])
            @endif          

            <hr>


             
            <button type="button" class="btn btn-success d-grid w-100 mb-3" data-bs-toggle="modal" data-bs-target="#makeascompletemodel">
              <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="icon-base bx bx-paper-plane icon-sm me-2"></i>Mark as Complete</span>
            </button>

            @include('support-tickets.model.mark-as-complete')

         
 
          

          @endcan


          

            

            @can('close', $supportTicket)

            <p class="text-muted small mb-3">
                If this ticket is not rejected or closed within {{ \App\Models\SupportTicket::AUTO_CLOSE_AFTER_DAYS }} days of completion, it closes automatically @if($supportTicket->marked_completed_at) on {{ $supportTicket->marked_completed_at->copy()->addDays(\App\Models\SupportTicket::AUTO_CLOSE_AFTER_DAYS)->format('d M Y, h:i A') }}@endif.
            </p>

            <ul class="list-unstyled mb-6">
               @if($supportTicket->vendor)
              <li class="mb-2">
                <span class="h6">Vendor :</span>
                <span>{{$supportTicket->vendor->name ?? '-'}}</span>
              </li>
              @endif
               @if($supportTicket->total_cost)
              <li class="mb-2">
                <span class="h6">Total Cost :</span>
                <span>{{ $supportTicket->total_cost ?? '-' }}</span>
              </li>
              @endif
              @if($supportTicket->completed_ticket_commands)
              <li class="mb-2">
                <span class="h6">Commands :</span>
                <span>{{ $supportTicket->completed_ticket_commands ?? '-' }}</span>
              </li>
              @endif
                @if($supportTicket->completed_filenames !== [])
              <li class="mb-2">
                <span class="h6 mb-3 d-block">Files on Completion :</span>
                <span>@include('support-tickets.partials.completed-files')</span>
              </li>
              @endif
              
            </ul>



              <form method="POST" action="{{route('support-ticket.rejectticket', $supportTicket->id)}}" class="mt-4 reject-ticket-form">
                @csrf

                <button type="submit" class="btn btn-warning d-grid w-100 mb-3">
                    <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="icon-base bx bx-x icon-sm me-2"></i>Reject Ticket</span>
                </button>

              </form>

              <form method="POST" action="{{route('support-ticket.closeticket', $supportTicket->id)}}" class="mt-4 close-ticket-form">
                @csrf

                <button type="submit" class="btn btn-danger d-grid w-100">
                    <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="icon-base bx bx-paper-plane icon-sm me-2"></i>Close Ticket</span>
                </button>

              </form>

            
             @endcan    
             
             @can('reopen', $supportTicket)

              <form method="POST" action="{{route('support-ticket.reopenticket', $supportTicket->id)}}" class="mt-4 reopen-ticket-form">
                @csrf

                <button type="submit" class="btn btn-warning d-grid w-100">
                    <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="icon-base bx bx-history icon-sm me-2"></i>Reopen Ticket</span>
                </button>

              </form>

            
             @endcan   
             
             
             

        </div>
       </div>
       @endcanany
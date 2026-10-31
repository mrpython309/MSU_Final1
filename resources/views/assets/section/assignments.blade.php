<div class="card card-action mb-6">
      <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0 me-3">Assignments</h5>
        <div class="card-action-element d-md-flex justify-content-md-end">
          @if($asset->status?->value == 'available')
          @can('asset.assignment:create')
          <a class="btn btn-sm btn-primary text-white float-end" href="{{ asset_section_action_url('asset.assignment.create', ['asset_id' => $asset->id], 'assignments') }}">Assign Asset</a>
         @endcan
          @endif
        </div>
      </div>
      <div class="card-body">

        <div class="table-responsive">
        <table class="table border-top table-border-bottom-0">
          <thead>
            <tr>
              <th class="text-truncate">Actions</th>
              <th>Logs</th>
              <th class="text-truncate">Assigned Date</th>
              <th class="text-truncate">From </th>
              <th class="text-truncate">To</th>
              @if (auth()->user()?->canManageAssets())
              <th class="text-truncate">Asset Custodian</th>
              @endif
              <th class="text-truncate">Status</th>
              <th class="text-truncate">Transfer Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($assignments as $assignment)
             <tr>
              <td class="text-truncate">

                <div class="d-flex align-items-center gap-4">
                 
                    @if($assignment->canModify())

                    @can('asset.assignment:edit')

                    <a class="btn btn-sm btn-icon edit-record" href="{{ asset_section_action_url('asset.assignment.edit', $assignment, 'assignments') }}"><i class="icon-base bx bx-edit icon-22px"></i></a>
                    
                    @endcan 

                    {{-- @can('asset.assignment:delete')
                    <form method="POST"
                                              action="{{ route('asset.assignment.destroy', $assignment) }}"
                                              class="delete-form">
                                              @csrf
                                              @method('DELETE')
                      <button class="btn btn-sm btn-icon delete-record" data-id="416"><i class="icon-base bx bx-trash icon-22px"></i></button>
                    </form>
                    @endcan --}}
                  @endif

     @if($assignment->can_accept_assignment || $assignment->can_reject_assignment || $assignment->can_return_assignment || $assignment->can_return_accept_assignment)

        <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base bx bx-dots-vertical-rounded icon-22px"></i>
        </button>

        <div class="dropdown-menu m-0">

            @can('updateStatus', [$assignment, 'accept'])
            <a href="{{ asset_section_action_url('asset.assignment.accept', $assignment, 'assignments') }}" class="dropdown-item text-success">
                    <i class="bx bx-check-circle me-1"></i> Accept
            </a>
            @endcan

            @can('updateStatus', [$assignment, 'reject'])
            <a href="{{ asset_section_action_url('asset.assignment.reject', $assignment, 'assignments') }}" class="dropdown-item text-warning">
                    <i class="bx bx-dislike me-1"></i> Reject
            </a>
            @endcan

            @can('updateStatus', [$assignment, 'return'])
            <a href="{{ asset_section_action_url('asset.assignment.return', $assignment, 'assignments') }}" class="dropdown-item text-primary">
                    <i class="bx bx-undo me-1"></i> Return
            </a>
            @endcan

            @can('updateStatus', [$assignment, 'accept_return'])
            <a href="{{ asset_section_action_url('asset.assignment.return.accept', $assignment, 'assignments') }}" class="dropdown-item text-info">
                    <i class="bx bx-log-in-circle me-1"></i> Accept Return
            </a>
            @endcan

          </div>
         @endif

                </div>

              </td>
              <td>
                <button type="button" class="btn btn-icon btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentLogs{{$assignment->id}}">
                  <i class="icon-base bx bx-pie-chart-alt icon-md text-white"></i>
                </button>
                @include('assets.assignments.logs',['assignment'=>$assignment])
              </td>
              <td class="text-truncate">
                <div class="d-flex align-items-center gap-2">
                  <span>{{ $assignment->assign_date?->format('d M Y') }}</span>
                  @if($assignment->canEditAssignDate())
                    @can('asset.assignment:edit')
                    <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#editAssignDate{{ $assignment->id }}" title="Edit assigned date">
                      <i class="icon-base bx bx-edit icon-22px"></i>
                    </button>
                    @endcan
                  @endif
                </div>
              </td>
              <td class="text-truncate">
                
                <div class="d-flex align-items-center">
    <div class="report-list-icon shadow-xs me-4">
        <i class="bx bx-buildings text-primary"></i>
    </div>

    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <div class="d-flex flex-column">

            {{-- Company --}}
            <h6 class="mb-0">
                {{ $assignment->fromCompany->name ?? ($assignment->from_company_id ? ('Company #'.$assignment->from_company_id) : '--') }}
            </h6>

            {{-- Branch --}}
            <span class="fw-medium text-dark">
                {{ $assignment->fromBranch->name ?? ($assignment->from_branch ? ('Branch #'.$assignment->from_branch) : '--') }}
            </span>

            {{-- Location --}}
            @if($assignment->fromBranchLocation || $assignment->from_location)
                <small class="text-muted">
                    {{ $assignment->fromBranchLocation->location_name ?? ('Location #'.$assignment->from_location) }}
                </small>
            @endif

        </div>
    </div>
</div>

              </td>
              <td class="text-truncate">

                 @if($assignment->toBranch || $assignment->toCompany || $assignment->to_branch || $assignment->to_company_id)
                <div class="d-flex align-items-center">
                  <div class="report-list-icon shadow-xs me-4">
                    <i class="bx bx-store text-info"></i>
                  </div>
                  <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                    <div class="d-flex flex-column">


                          {{-- Company --}}
            <h6 class="mb-0">
                {{ $assignment->toCompany->name ?? ($assignment->to_company_id ? ('Company #'.$assignment->to_company_id) : '--') }}
            </h6>

            {{-- Branch --}}
            <span class="fw-medium text-dark">
                {{ $assignment->toBranch->name ?? ($assignment->to_branch ? ('Branch #'.$assignment->to_branch) : '--') }}
            </span>

                           @if($assignment->toBranchLocation || $assignment->to_location)
                    <small class="text-muted">{{ $assignment->toBranchLocation->location_name ?? ('Location #'.$assignment->to_location) }}</small>
                    @endif
                     
                    </div>
                   
                  </div>
                </div>
                @else
                --
                @endif

               

               

              </td>
              @if (auth()->user()?->canManageAssets())
              <td>

                 @php
                    $linkedPerson = $assignment->employee;
                    $linkedName = $linkedPerson?->employee_name ?? $linkedPerson?->name;
                 @endphp
                 @if($linkedPerson && $linkedName)
                <a class="d-flex align-items-center mt-3">
              <div class="flex-shrink-0 avatar">
               <div class="avatar avatar-sm me-1">
              <span class="avatar-initial rounded-circle bg-label-danger">{{ strtoupper(substr($linkedName, 0, 1)) }}</span>
            </div>

              </div>
              <div class="chat-contact-info flex-grow-1 ms-4">
                <div class="d-flex justify-content-between align-items-center">
                  <h6 class="chat-contact-name text-truncate m-0 fw-normal">{{ $linkedName }}</h6> 
                </div>
                <small class="chat-contact-status text-truncate">{{ $assignment->department?->department_name ?? $linkedPerson->department?->department_name ?? '--' }}</small>
              </div>
            </a>
            @else

            --
            @endif

              </td>
              @endif
            
              <td class="text-truncate">{!! $assignment->status_label !!}</td>
              <td class="text-truncate" align="center">{!! $assignment->transfer_status_label !!}

                        


              </td>
            </tr>
            @empty
             <tr>
                 <td colspan="{{ auth()->user()?->canManageAssets() ? 8 : 7 }}" class="text-center">No Assignments Found</td> 
             </tr>
            @endforelse
           
          </tbody>
        </table>
      </div>

      @foreach ($assignments as $assignment)
        @if($assignment->canEditAssignDate())
          @can('asset.assignment:edit')
            @include('assets.assignments.edit-date', ['assignment' => $assignment])
          @endcan
        @endif
      @endforeach
       

      </div>
    </div> 
 


@extends('layouts/supportTicketLayout')

@section('title', 'Support Tickets')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
@endsection

@section('page-style')
@include('partials.content-zoom-style')
<style>
    .fs-12px { font-size: 0.75rem !important; }
    #ticketTable {
        margin-bottom: 0;
    }
    #ticketTable th,
    #ticketTable td {
        vertical-align: middle;
    }
    #ticketTable .col-fit {
        width: 1%;
        white-space: nowrap !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    #ticketTable .col-id {
        width: 1%;
        white-space: nowrap !important;
        padding-left: 0.35rem;
        padding-right: 0.75rem;
    }
    #ticketTable td.ticket-meta {
        vertical-align: top;
        white-space: nowrap !important;
        padding-top: 0.85rem;
    }
    #ticketTable .status-time {
        display: block;
        white-space: nowrap;
        margin-top: 0.15rem;
    }
    #ticketTable th.actions-col,
    #ticketTable td.actions-col {
        text-align: center;
        width: 1%;
        white-space: nowrap !important;
    }
    #ticketTable td.actions-col .dropdown {
        display: flex;
        justify-content: center;
    }
    #ticketTable th.created-col,
    #ticketTable td.created-cell {
        text-align: center;
        width: 1%;
        white-space: normal !important;
    }
    #ticketTable .created-date,
    #ticketTable .created-time {
        display: block;
        white-space: nowrap;
        line-height: 1.3;
    }
</style>
@endsection

@section('content')
@include('partials.content-zoom-open')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="layout-menu-toggle navbar-nav align-items-center d-xl-none">
            <a class="nav-item nav-link px-0" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
            </a>
        </div>
        <h4 class="mb-0">Support Tickets</h4>
    </div>
    @include('support-tickets.partials.header-actions')
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form id="supportTicketFilterForm" method="GET" action="{{ route('support-ticket.index') }}" class="row g-3">

             <div class="col-md-3">
            <label class="form-label">Company</label>
            <select id="company_id" name="company_id"
                    class="form-select @error('company_id') is-invalid @enderror">
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                         {{ request('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            
        </div>

<div class="col-md-3">
            <label class="form-label">Branch </label>
            <select id="branch_id" name="branch_id" 
                    class="form-select select2 @error('branch_id') is-invalid @enderror">
                <option value="">Select Branch</option>  
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}"
                         {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach               
            </select>
           
        </div>

            <div class="col-md-3">
                <label class="form-label">Division</label>
                <select class="form-select" name="division_id" id="division_id">
                    <option value="">All Divisions</option>
                    @foreach($divisions as $item)
                        <option value="{{ $item->id }}" {{ request('division_id')==$item->id?'selected':'' }}>
                            {{ $item->division_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Created By</label>
                <select class="form-select select2" name="created_by" id="created_by">
                    <option value="">All Users</option>
                    @foreach($creatorUsers as $creatorUser)
                        <option value="{{ $creatorUser->id }}" {{ request('created_by') == $creatorUser->id ? 'selected' : '' }}>
                            {{ $creatorUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Collaborators</label>
                <select class="form-select select2" name="collaborator_id" id="collaborator_id">
                    <option value="">All Collaborators</option>
                    @foreach($collaboratorUsers as $collaboratorUser)
                        <option value="{{ $collaboratorUser->id }}" {{ request('collaborator_id') == $collaboratorUser->id ? 'selected' : '' }}>
                            {{ $collaboratorUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority">
                    <option value="">All</option>
                    <option value="low" @if(request('priority')=='low') selected @endif>Low</option>
                    <option value="normal" @if(request('priority')=='normal') selected @endif>Normal</option>
                    <option value="high" @if(request('priority')=='high') selected @endif>High</option>
                    <option value="urgent" @if(request('priority')=='urgent') selected @endif>Urgent</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    @foreach(\App\Models\SupportTicketProgressStatus::selectable() as $progressStatus)
                    <option value="{{ $progressStatus->slug }}" {{ request('status') == $progressStatus->slug ? 'selected' : '' }}>{{ $progressStatus->name }}</option>
                    @endforeach
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="reopen" {{ request('status') == 'reopen' ? 'selected' : '' }}>Reopen</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Common Search</label>
                <div class="input-group">
                    <input type="text" name="subject" class="form-control" placeholder="ID, Subject..." value="{{ request('subject') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search"></i>
                    </button>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="per_page">Per Page</label>
                <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((string) request('per_page', 50) === (string) $size)>{{ $size }}</option>
                    @endforeach
                    <option value="all" @selected(request('per_page') === 'all')>All</option>
                </select>
            </div>

        </form>
    </div>
</div>

{{-- Tickets Table --}}
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Tickets List</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-sm btn-success" form="supportTicketFilterForm" name="export_format" value="excel">Excel</button>
            <button type="submit" class="btn btn-sm btn-danger" form="supportTicketFilterForm" name="export_format" value="pdf">PDF</button>
            <a href="{{ route('support-ticket.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Ticket
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table" id="ticketTable">
            <thead>
                <tr>
                    <th class="actions-col">Actions</th>
                    <th class="col-fit">#</th>
                    <th class="col-id">ID</th>
                    <th>Subject</th>
                    <th class="col-fit">Priority</th>
                    <th class="col-fit">Status</th>
                    <th>Branch</th>
                    <th>Division</th>
                    <th class="col-fit">Due Date</th>
                    <th class="created-col">Created</th>
                    <th>Created By</th>
                    <th>Collaborators</th>
                </tr>
            </thead>

            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td class="actions-col">
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'>
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('support-ticket.show',$ticket->id) }}">
                                        <i class="bx bx-show me-1"></i> View
                                    </a>
                                </li>
                                @can('update', $ticket)
                                
                                <li>
                                    <a class="dropdown-item" href="{{ route('support-ticket.edit',$ticket->id) }}">
                                        <i class="bx bx-edit me-1"></i> Edit
                                    </a>
                                </li>
                             
                                @endcan

                                @can('delete', $ticket)
                                <li>
                                    <form method="POST" action="{{ route('support-ticket.destroy',$ticket->id) }}" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </td>
                    <td class="col-fit">{{ $tickets->firstItem() + $loop->index }}.</td>
                    <td class="col-id">{{ $ticket->displayTicketNumber() }}</td>

                    <td>
                        <strong>{{ $ticket->subject }}</strong>
                        <div class="text-muted fs-12px">
                            {{ Str::limit($ticket->description, 60) }}
                        </div>
                    </td>

                    <td class="ticket-meta">{!! $ticket->priority_label !!}</td>

                    <td class="ticket-meta">
                        {!! $ticket->status_label !!}
                        @if($ticket->status === 'completed' && $ticket->marked_completed_at)
                            <span class="status-time text-muted fs-12px">{{ $ticket->marked_completed_at->format('d M Y, h:i A') }}</span>
                        @elseif($ticket->status === 'closed' && $ticket->completed_at)
                            <span class="status-time text-muted fs-12px">{{ $ticket->completed_at->format('d M Y, h:i A') }}</span>
                        @endif
                    </td>

                    <td>{{ $ticket->branch?->name ?? '-' }}</td>

                    <td>{{ $ticket->division?->division_name ?? '-' }}</td>

                    <td class="col-fit">{{ optional($ticket->due_date)->format('d M Y') ?? '-' }}</td>

                    <td class="created-cell">
                        @if($ticket->created_at)
                            <span class="created-date">{{ $ticket->created_at->format('d M Y') }}</span>
                            <span class="created-time">{{ $ticket->created_at->format('h:i A') }}</span>
                        @else
                            -
                        @endif
                    </td>

                    <td>{{ $ticket->creator?->name ?? '-' }}</td>

                    <td>
                        @forelse($ticket->activeAssignments as $collaborator)
                            <span class="badge bg-label-info">{{ $collaborator->user->name ?? '-' }}</span>
                        @empty
                            --
                        @endforelse
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center py-5 text-muted">
                        No tickets found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
        @if($tickets->hasPages())
            <div class="text-muted">
                Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} of {{ $tickets->total() }} tickets
            </div>
            <div>{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@include('partials.content-zoom-close')
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@section('page-script')
<script>


 $('.select2').select2({
    placeholder: "Select",
    allowClear: true,
    closeOnSelect: true, 
    width: '100%'
});

 function loadBranches(companyId, selectedId = null) {
            const branchDropdown = $('#branch_id');
            branchDropdown.html('<option>Loading...</option>');

            if (!companyId) {
                branchDropdown.html('<option value="">Select Branch</option>');
                return;
            }

            $.get(
                "{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId),
                function (res) {
                    branchDropdown.empty().append('<option value="">Select Branch</option>');

                    if (res.data && res.data.length > 0) {
                        $.each(res.data, function (_, value) {
                            let selected = selectedId == value.id ? 'selected' : '';
                            branchDropdown.append(
                                `<option value="${value.id}" ${selected}>${value.name}</option>`
                            );
                        });
                    } else {
                        branchDropdown.append('<option value="">No branches found</option>');
                    }
                }
            ).fail(function () {
                branchDropdown.html('<option value="">Failed to load branches</option>');
            });
        }

        function loadCollaborators(branchId, selectedId = null) {
            const collaboratorDropdown = $('#collaborator_id');

            if (!branchId) {
                collaboratorDropdown.html('<option value="">All Collaborators</option>');
                return;
            }

            collaboratorDropdown.html('<option value="">Loading...</option>');

            $.get(
                "{{ route('ajax.getusers.bybranch', ':id') }}".replace(':id', branchId),
                { for_filter: 1, division_id: $('#division_id').val() || '' },
                function (res) {
                    collaboratorDropdown.empty().append('<option value="">All Collaborators</option>');

                    if (res.data && res.data.length > 0) {
                        $.each(res.data, function (_, value) {
                            let selected = selectedId == value.id ? 'selected' : '';
                            collaboratorDropdown.append(
                                `<option value="${value.id}" ${selected}>${value.name}</option>`
                            );
                        });
                    }
                }
            ).fail(function () {
                collaboratorDropdown.html('<option value="">Failed to load collaborators</option>');
            });
        }

        $('#company_id').on('change', function () {
            loadBranches(this.value);
            $('#collaborator_id').html('<option value="">All Collaborators</option>');
        });

        $('#branch_id').on('change', function () {
            loadCollaborators(this.value);
        });

        $('#division_id').on('change', function () {
            if ($('#branch_id').val()) {
                loadCollaborators($('#branch_id').val(), "{{ request('collaborator_id') }}");
            }
        });

        @if(request('branch_id'))
            loadCollaborators("{{ request('branch_id') }}", "{{ request('collaborator_id') }}");
        @endif

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "This ticket will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

$(function() {
    $('#ticketTable').DataTable({
        paging: false,
        info: false,
        lengthChange: false,
        searching: false,
        responsive: false,
        autoWidth: false,
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, targets: 0, width: '1%' },
            { targets: [1, 2, 4, 5, 8, 9], width: '1%' }
        ]
    });
});
</script>
@include('partials.content-zoom-script', ['zoomStorageKey' => 'support-ticket-content-zoom'])
@endsection

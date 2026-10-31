@php
    $optionalDataCols = ['subject', 'priority', 'status', 'company_id', 'branch_id', 'department_id', 'asset_id', 'due_date', 'created_by'];
    $reportColCount = 2 + count(array_intersect($optionalDataCols, $tableColumns ?? []));
@endphp
<div class="table-responsive">
    <table class="table card-table" id="ticketTable">
        <thead>
            <tr>
                <th class="text-nowrap">#</th>
                <th>ID</th>
                @if(in_array('subject', $tableColumns)) <th>Subject</th> @endif
                @if(in_array('priority', $tableColumns)) <th>Priority</th> @endif
                @if(in_array('status', $tableColumns)) <th>Status</th> @endif
                @if(in_array('company_id', $tableColumns)) <th>Company</th> @endif
                @if(in_array('branch_id', $tableColumns)) <th>Branch</th> @endif
                @if(in_array('department_id', $tableColumns)) <th>Department</th> @endif
                @if(in_array('asset_id', $tableColumns)) <th>Asset</th> @endif
                @if(in_array('due_date', $tableColumns)) <th>Due Date</th> @endif
                @if(in_array('created_by', $tableColumns)) <th>Created By</th> @endif
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td class="text-nowrap text-muted">
                    @if (method_exists($tickets, 'currentPage'))
                        {{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}
                    @else
                        {{ $loop->iteration }}
                    @endif
                </td>
                <td class="text-nowrap">{{ $ticket->displayTicketNumber() }}</td>
                @if(in_array('subject', $tableColumns))
                <td>
                    <strong>{{ $ticket->subject }}</strong>
                    <div class="text-muted" style="font-size: 0.75rem !important;">
                        {{ Str::limit($ticket->description, 60) }}
                    </div>
                </td>
                @endif
                @if(in_array('priority', $tableColumns)) <td>{!! $ticket->priority_label !!}</td> @endif
                @if(in_array('status', $tableColumns)) <td>{!! $ticket->status_label !!}</td> @endif
                @if(in_array('company_id', $tableColumns)) <td>{{ $ticket->company?->name ?? '-' }}</td> @endif
                @if(in_array('branch_id', $tableColumns)) <td>{{ $ticket->branch?->name ?? '-' }}</td> @endif
                @if(in_array('department_id', $tableColumns)) <td>{{ $ticket->department?->department_name ?? '-' }}</td> @endif
                @if(in_array('asset_id', $tableColumns)) <td>{{ $ticket->asset?->asset_name ?? '-' }}</td> @endif
                @if(in_array('due_date', $tableColumns)) <td>{{ optional($ticket->due_date)->format('d M Y') ?? '-' }}</td> @endif
                @if(in_array('created_by', $tableColumns)) <td>{{ $ticket->creator?->name ?? '-' }}</td> @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $reportColCount }}" class="text-center py-5 text-muted">
                    No tickets found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($tickets instanceof \Illuminate\Pagination\LengthAwarePaginator && $tickets->hasPages())
<div class="card-footer d-flex justify-content-between align-items-center">
    <div class="text-muted">Showing {{ $tickets->firstItem() ?? 0 }} to {{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} tickets</div>
    <div>{{ $tickets->links() }}</div>
</div>
@endif

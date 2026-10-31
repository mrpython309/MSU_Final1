<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Support Tickets</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #444050; margin: 0; padding: 12px; }
        h1 { margin: 0 0 4px; font-size: 16px; color: #2f3349; }
        p { margin: 0 0 10px; color: #8a8d93; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e4e6ef; padding: 4px 5px; text-align: left; vertical-align: top; }
        thead th { background: #f8f9fa; font-weight: 600; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>Support Tickets</h1>
    <p>Generated on {{ now()->format('d M Y, h:i A') }} · {{ $tickets->count() }} tickets</p>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>ID</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Status Date</th>
                <th>Branch</th>
                <th>Division</th>
                <th>Due Date</th>
                <th>Created</th>
                <th>Created By</th>
                <th>Collaborators</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
                @php
                    $collaborators = $ticket->activeAssignments
                        ->map(fn ($assignment) => $assignment->user->name ?? null)
                        ->filter()
                        ->implode(', ');
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $ticket->displayTicketNumber() }}</td>
                    <td>{{ $ticket->subject ?: '-' }}</td>
                    <td>{{ trim((string) $ticket->description) !== '' ? $ticket->description : '-' }}</td>
                    <td>{{ \App\Exports\SupportTicketListExport::priority($ticket->priority) }}</td>
                    <td>{{ \App\Models\SupportTicketProgressStatus::plain($ticket->status) }}</td>
                    <td>{{ \App\Exports\SupportTicketListExport::statusDate($ticket) }}</td>
                    <td>{{ $ticket->branch?->name ?? '-' }}</td>
                    <td>{{ $ticket->division?->division_name ?? '-' }}</td>
                    <td>{{ $ticket->due_date?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}</td>
                    <td>{{ $ticket->creator?->name ?? '-' }}</td>
                    <td>{{ $collaborators !== '' ? $collaborators : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No tickets found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Support Ticket {{ $supportTicket->displayTicketNumber() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #444050; margin: 0; padding: 14px; }
        h1 { margin: 0 0 4px; font-size: 18px; color: #2f3349; }
        h2 { margin: 0; font-size: 13px; color: #2f3349; }
        p { margin: 0 0 8px; color: #8a8d93; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #e4e6ef; padding: 5px 7px; text-align: left; vertical-align: top; }
        thead th { background: #f8f9fa; font-weight: 600; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .section { margin-top: 8px; page-break-inside: avoid; }
        .label { width: 28%; font-weight: 600; background: #fafafa; }
    </style>
</head>
<body>
    @php
        $minutes = (int) ($supportTicket->completed_time ?? 0);
        $completionTime = $supportTicket->completed_time ? floor($minutes / 60).'h '.($minutes % 60).'m' : '-';
        $collaborators = $supportTicket->activeAssignments
            ->map(fn ($assignment) => $assignment->user->name ?? null)
            ->filter()
            ->implode(', ');
        $comments = $supportTicket->commands->sortBy('created_at')->values();
        $logs = $supportTicket->activities->sortBy('created_at')->values();
    @endphp

    <h1>Support Ticket {{ $supportTicket->displayTicketNumber() }}</h1>
    <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>

    <div class="section">
        <h2>Details</h2>
        <table>
            <tr><td class="label">Subject</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->subject) }}</td></tr>
            <tr><td class="label">Description</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->description) }}</td></tr>
            <tr><td class="label">Priority</td><td>{{ \App\Exports\SupportTicketDetailExport::priority($supportTicket->priority) }}</td></tr>
            <tr><td class="label">Status</td><td>{{ \App\Models\SupportTicketProgressStatus::plain($supportTicket->status) }}</td></tr>
            <tr><td class="label">Company</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->company->name ?? null) }}</td></tr>
            <tr><td class="label">Branch</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->branch->name ?? null) }}</td></tr>
            <tr><td class="label">Division</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->division->division_name ?? null) }}</td></tr>
            <tr><td class="label">Asset</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->asset->asset_name ?? null) }}</td></tr>
            <tr><td class="label">Due Date</td><td>{{ $supportTicket->due_date?->format('d M Y') ?? '-' }}</td></tr>
            <tr><td class="label">Created By</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->creator->name ?? null) }}</td></tr>
            <tr><td class="label">Created At</td><td>{{ $supportTicket->created_at?->format('d M Y h:i A') ?? '-' }}</td></tr>
            <tr><td class="label">Attachment</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->filename) }}</td></tr>
            <tr><td class="label">Collaborators</td><td>{{ $collaborators !== '' ? $collaborators : '-' }}</td></tr>
            <tr><td class="label">Vendor</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->vendor->name ?? null) }}</td></tr>
            <tr><td class="label">Total Cost</td><td>{{ $supportTicket->total_cost === null || $supportTicket->total_cost === '' ? '-' : $supportTicket->total_cost }}</td></tr>
            <tr><td class="label">Time to Completion</td><td>{{ $completionTime }}</td></tr>
            <tr><td class="label">Completion Notes</td><td>{{ \App\Exports\SupportTicketDetailExport::text($supportTicket->completed_ticket_commands) }}</td></tr>
            <tr><td class="label">Files on Completion</td><td>{{ $supportTicket->completed_filenames === [] ? '-' : implode(', ', $supportTicket->completed_filenames) }}</td></tr>
            <tr><td class="label">Completed At</td><td>{{ $supportTicket->completed_at?->format('d M Y h:i A') ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Comments</h2>
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Date</th>
                    <th>User</th>
                    <th>Comment</th>
                    <th>Attachments</th>
                    <th>Voice Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $command)
                    @php
                        $seconds = (int) ($command->recording_duration ?? 0);
                        $voice = $command->voice_note
                            ? $command->voice_note.($seconds > 0 ? ' ('.floor($seconds / 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT).')' : '')
                            : '-';
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $command->created_at?->format('d M Y h:i A') ?? '-' }}</td>
                        <td>{{ \App\Exports\SupportTicketDetailExport::text($command->user->name ?? null) }}</td>
                        <td>{{ \App\Exports\SupportTicketDetailExport::text($command->ticket_response) }}</td>
                        <td>{{ $command->attachment_filenames === [] ? '-' : implode(', ', $command->attachment_filenames) }}</td>
                        <td>{{ $voice }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No comments</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Logs</h2>
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Date</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $activity)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $activity->created_at?->format('d M Y h:i A') ?? '-' }}</td>
                        <td>{{ \App\Exports\SupportTicketDetailExport::text($activity->user->name ?? 'System') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $activity->event)) }}</td>
                        <td>{{ \App\Exports\SupportTicketLogsSheet::detail($activity->event, $activity->meta ?? []) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No logs</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

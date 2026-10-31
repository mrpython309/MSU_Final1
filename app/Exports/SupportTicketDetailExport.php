<?php

namespace App\Exports;

use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupportTicketDetailExport implements WithMultipleSheets
{
    public function __construct(private readonly SupportTicket $ticket) {}

    public function sheets(): array
    {
        return [
            new SupportTicketDetailSheet($this->ticket),
            new SupportTicketCommentsSheet($this->ticket),
            new SupportTicketLogsSheet($this->ticket),
            new SupportTicketCollaboratorsSheet($this->ticket),
        ];
    }

    public static function text(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? '-' : $text;
    }

    public static function priority(?string $priority): string
    {
        return SupportTicketListExport::priority($priority);
    }
}

class SupportTicketDetailSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly SupportTicket $ticket) {}

    public function title(): string
    {
        return 'Details';
    }

    public function headings(): array
    {
        return ['Field', 'Value'];
    }

    public function array(): array
    {
        $ticket = $this->ticket;
        $minutes = (int) ($ticket->completed_time ?? 0);
        $completionTime = $ticket->completed_time
            ? floor($minutes / 60).'h '.($minutes % 60).'m'
            : '-';
        $files = $ticket->completed_filenames;
        $collaborators = $ticket->activeAssignments
            ->map(fn ($assignment) => $assignment->user->name ?? null)
            ->filter()
            ->implode(', ');

        return [
            ['Ticket ID', $ticket->displayTicketNumber()],
            ['Subject', SupportTicketDetailExport::text($ticket->subject)],
            ['Description', SupportTicketDetailExport::text($ticket->description)],
            ['Priority', SupportTicketDetailExport::priority($ticket->priority)],
            ['Status', SupportTicketProgressStatus::plain($ticket->status)],
            ['Company', SupportTicketDetailExport::text($ticket->company->name ?? null)],
            ['Branch', SupportTicketDetailExport::text($ticket->branch->name ?? null)],
            ['Division', SupportTicketDetailExport::text($ticket->division->division_name ?? null)],
            ['Asset', SupportTicketDetailExport::text($ticket->asset->asset_name ?? null)],
            ['Due Date', $ticket->due_date?->format('d M Y') ?? '-'],
            ['Created By', SupportTicketDetailExport::text($ticket->creator->name ?? null)],
            ['Created At', $ticket->created_at?->format('d M Y h:i A') ?? '-'],
            ['Attachment', SupportTicketDetailExport::text($ticket->filename)],
            ['Collaborators', $collaborators !== '' ? $collaborators : '-'],
            ['Vendor', SupportTicketDetailExport::text($ticket->vendor->name ?? null)],
            ['Total Cost', $ticket->total_cost === null || $ticket->total_cost === '' ? '-' : (string) $ticket->total_cost],
            ['Time to Completion', $completionTime],
            ['Completion Notes', SupportTicketDetailExport::text($ticket->completed_ticket_commands)],
            ['Files on Completion', $files === [] ? '-' : implode(', ', $files)],
            ['Completed At', $ticket->completed_at?->format('d M Y h:i A') ?? '-'],
        ];
    }
}

class SupportTicketCommentsSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly SupportTicket $ticket) {}

    public function title(): string
    {
        return 'Comments';
    }

    public function headings(): array
    {
        return ['S.No', 'Date', 'User', 'Comment', 'Attachments', 'Voice Note', 'Voice Duration'];
    }

    public function array(): array
    {
        $rows = $this->ticket->commands->sortBy('created_at')->values()->map(function ($command, $index) {
            $seconds = (int) ($command->recording_duration ?? 0);
            $attachments = $command->attachment_filenames;

            return [
                $index + 1,
                $command->created_at?->format('d M Y h:i A') ?? '-',
                SupportTicketDetailExport::text($command->user->name ?? null),
                SupportTicketDetailExport::text($command->ticket_response),
                $attachments === [] ? '-' : implode(', ', $attachments),
                SupportTicketDetailExport::text($command->voice_note),
                $seconds > 0
                    ? floor($seconds / 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT)
                    : '-',
            ];
        })->all();

        return $rows === [] ? [[ '-', '-', '-', 'No comments', '-', '-', '-' ]] : $rows;
    }
}

class SupportTicketLogsSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly SupportTicket $ticket) {}

    public function title(): string
    {
        return 'Logs';
    }

    public function headings(): array
    {
        return ['S.No', 'Date', 'User', 'Event', 'Detail'];
    }

    public function array(): array
    {
        $rows = $this->ticket->activities->sortBy('created_at')->values()->map(function ($activity, $index) {
            return [
                $index + 1,
                $activity->created_at?->format('d M Y h:i A') ?? '-',
                SupportTicketDetailExport::text($activity->user->name ?? 'System'),
                ucfirst(str_replace('_', ' ', (string) $activity->event)),
                self::detail($activity->event, $activity->meta ?? []),
            ];
        })->all();

        return $rows === [] ? [[ '-', '-', '-', '-', 'No logs' ]] : $rows;
    }

    /** @param array<string, mixed> $meta */
    public static function detail(string $event, array $meta): string
    {
        if ($event === 'status_changed') {
            $old = SupportTicketProgressStatus::plain((string) ($meta['old'] ?? ''));
            $new = SupportTicketProgressStatus::plain((string) ($meta['new'] ?? ''));
            $line = $old.' -> '.$new;
            if (! empty($meta['note'])) {
                $line .= '. '.$meta['note'];
            }

            return $line;
        }

        if ($event === 'commented') {
            return SupportTicketDetailExport::text($meta['message'] ?? null);
        }

        if ($event === 'assigned') {
            return 'Collaborator: '.SupportTicketDetailExport::text($meta['assigned_to'] ?? null);
        }

        if ($event === 'exited') {
            return 'Reason: '.SupportTicketDetailExport::text($meta['reason'] ?? null);
        }

        if ($event === 'updated') {
            $changes = [];
            foreach ($meta['changes'] ?? [] as $field => $change) {
                $changes[] = ucfirst(str_replace('_', ' ', (string) $field))
                    .': '.SupportTicketDetailExport::text($change['old'] ?? null)
                    .' -> '.SupportTicketDetailExport::text($change['new'] ?? null);
            }

            return $changes === [] ? '-' : implode('; ', $changes);
        }

        return '-';
    }
}

class SupportTicketCollaboratorsSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly SupportTicket $ticket) {}

    public function title(): string
    {
        return 'Collaborators';
    }

    public function headings(): array
    {
        return ['S.No', 'Name', 'Designation', 'Assigned At'];
    }

    public function array(): array
    {
        $rows = $this->ticket->activeAssignments->values()->map(function ($assignment, $index) {
            return [
                $index + 1,
                SupportTicketDetailExport::text($assignment->user->name ?? null),
                SupportTicketDetailExport::text($assignment->user->designation->designation_name ?? null),
                $assignment->created_at?->format('d M Y h:i A') ?? '-',
            ];
        })->all();

        return $rows === [] ? [[ '-', 'No collaborators', '-', '-' ]] : $rows;
    }
}

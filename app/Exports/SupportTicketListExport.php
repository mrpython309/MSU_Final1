<?php

namespace App\Exports;

use App\Models\SupportTicketProgressStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupportTicketListExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $tickets) {}

    public function title(): string
    {
        return 'Support Tickets';
    }

    public function headings(): array
    {
        return ['S.No', 'ID', 'Subject', 'Description', 'Priority', 'Status', 'Status Date', 'Branch', 'Division', 'Due Date', 'Created', 'Created By', 'Collaborators'];
    }

    public function array(): array
    {
        return $this->tickets->values()->map(function ($ticket, $index) {
            $collaborators = $ticket->activeAssignments
                ->map(fn ($assignment) => $assignment->user->name ?? null)
                ->filter()
                ->implode(', ');

            return [
                $index + 1,
                $ticket->displayTicketNumber(),
                $ticket->subject ?: '-',
                trim((string) $ticket->description) !== '' ? $ticket->description : '-',
                self::priority($ticket->priority),
                SupportTicketProgressStatus::plain($ticket->status),
                self::statusDate($ticket),
                $ticket->branch?->name ?? '-',
                $ticket->division?->division_name ?? '-',
                $ticket->due_date?->format('d M Y') ?? '-',
                $ticket->created_at?->format('d M Y, h:i A') ?? '-',
                $ticket->creator?->name ?? '-',
                $collaborators !== '' ? $collaborators : '-',
            ];
        })->all();
    }

    public static function statusDate($ticket): string
    {
        if ($ticket->status === 'completed' && $ticket->marked_completed_at) {
            return $ticket->marked_completed_at->format('d M Y, h:i A');
        }

        if ($ticket->status === 'closed' && $ticket->completed_at) {
            return $ticket->completed_at->format('d M Y, h:i A');
        }

        return '-';
    }

    public static function priority(?string $priority): string
    {
        return match ($priority) {
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => '-',
        };
    }
}

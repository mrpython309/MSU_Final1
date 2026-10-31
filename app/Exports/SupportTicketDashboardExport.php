<?php

namespace App\Exports;

use App\Models\SupportTicketProgressStatus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupportTicketDashboardExport implements WithMultipleSheets
{
    public function __construct(private readonly array $payload) {}

    public function sheets(): array
    {
        return [
            new SupportTicketDashboardSummarySheet($this->payload),
            new SupportTicketDashboardPrioritySheet($this->payload),
            new SupportTicketDashboardDivisionSheet($this->payload),
            new SupportTicketDashboardTicketSheet($this->payload),
        ];
    }

    public static function count(mixed $value): string
    {
        return (string) (int) $value;
    }
}

class SupportTicketDashboardSummarySheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $payload) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return ['Metric', 'Count'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->payload['summaryCards'] as $card) {
            $rows[] = [$card['label'], SupportTicketDashboardExport::count($card['count'] ?? 0)];
        }

        if ($this->payload['filters'] !== []) {
            $rows[] = [];
            $rows[] = ['Filter', 'Value'];
            foreach ($this->payload['filters'] as $label => $value) {
                $rows[] = [$label, $value];
            }
        }

        return $rows;
    }
}

class SupportTicketDashboardPrioritySheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $payload) {}

    public function title(): string
    {
        return 'Tickets by Priority';
    }

    public function headings(): array
    {
        return array_merge(
            ['Priority'],
            collect($this->payload['matrixStatuses'])->pluck('short')->all()
        );
    }

    public function array(): array
    {
        $rows = [];

        foreach (['high' => 'High', 'normal' => 'Medium', 'low' => 'Low'] as $key => $label) {
            $row = [$label];
            foreach ($this->payload['matrixStatuses'] as $status) {
                $row[] = SupportTicketDashboardExport::count($this->payload['priorityMatrix'][$key][$status['slug']] ?? 0);
            }
            $rows[] = $row;
        }

        return $rows;
    }
}

class SupportTicketDashboardDivisionSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $payload) {}

    public function title(): string
    {
        return 'Tickets by Division';
    }

    public function headings(): array
    {
        return array_merge(
            ['Division'],
            collect($this->payload['matrixStatuses'])->pluck('short')->all()
        );
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->payload['divisions'] as $division) {
            $counts = $this->payload['divisionMatrix'][$division->id] ?? [];
            if (collect($counts)->sum() <= 0) {
                continue;
            }

            $row = [$division->division_name];
            foreach ($this->payload['matrixStatuses'] as $status) {
                $row[] = SupportTicketDashboardExport::count($counts[$status['slug']] ?? 0);
            }
            $rows[] = $row;
        }

        return $rows === [] ? [['No tickets in any division']] : $rows;
    }
}

class SupportTicketDashboardTicketSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $payload) {}

    public function title(): string
    {
        return 'Tickets';
    }

    public function headings(): array
    {
        return ['Ticket', 'Subject', 'Priority', 'Status', 'Branch', 'Division', 'Created By', 'Created', 'Due'];
    }

    public function array(): array
    {
        return $this->payload['tickets']->map(function ($ticket) {
            return [
                $ticket->displayTicketNumber(),
                $ticket->subject,
                ucfirst((string) ($ticket->priority ?? '-')),
                SupportTicketProgressStatus::plain($ticket->status),
                $ticket->branch?->name ?? '-',
                $ticket->division?->division_name ?? '-',
                $ticket->creator?->name ?? '-',
                $ticket->created_at?->format('d M Y') ?? '-',
                $ticket->due_date?->format('d M Y') ?? '-',
            ];
        })->all();
    }
}

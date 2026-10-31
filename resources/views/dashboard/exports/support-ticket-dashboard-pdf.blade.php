<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Support Ticket Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #566a7f; margin: 0; padding: 14px; background: #f5f5f9; }
        h1, h2, h3 { margin: 0; color: #566a7f; }
        p { margin: 0 0 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e4e6ef; padding: 7px 8px; text-align: left; vertical-align: middle; }
        thead th { background: #f8f9fa; color: #566a7f; font-weight: 600; }
        .text-center { text-align: center; }
        .text-muted { color: #a1acb8; }
        .keep-together { page-break-inside: avoid; }

        .report-header { background: #fff; border: 1px solid #e4e6ef; padding: 14px 16px; margin-bottom: 12px; }
        .report-header h1 { font-size: 20px; margin-bottom: 4px; }
        .filters { margin-top: 8px; }
        .filters span { display: inline-block; margin-right: 14px; margin-bottom: 4px; background: #f1f2f6; padding: 4px 8px; border-radius: 4px; }

        .card { background: #fff; border: 1px solid #e4e6ef; margin-bottom: 12px; }
        .card-header { padding: 10px 14px; border-bottom: 1px solid #e4e6ef; background: #fff; }
        .card-header h2 { font-size: 14px; font-weight: 600; }
        .card-body { padding: 12px 14px; }

        .summary-row td { width: 25%; border: none; padding: 6px 6px 6px 0; vertical-align: top; }
        .summary-row td:last-child { padding-right: 0; }
        .stat-card { border: 1px solid #e4e6ef; border-radius: 6px; padding: 10px; min-height: 72px; }
        .stat-label { font-size: 10px; font-weight: 600; margin-bottom: 4px; }
        .stat-value { font-size: 22px; font-weight: 700; line-height: 1; }
        .stat-dark { background: #eeeff1; color: #233446; }
        .stat-warning { background: #fff8e1; color: #ffab00; }
        .stat-secondary { background: #eeeff1; color: #8592a3; }
        .stat-info { background: #e7f7ff; color: #03c3ec; }
        .stat-primary { background: #e7e7ff; color: #696cff; }
        .stat-success { background: #e8fadf; color: #71dd37; }
        .stat-teal { background: #e0f2f1; color: #009688; }
        .stat-danger { background: #ffe5e5; color: #ff3e1d; }

        .layout-row td { width: 50%; vertical-align: top; border: none; padding: 0 6px 0 0; }
        .layout-row td:last-child { padding: 0 0 0 6px; }

        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .badge-open { background: #fff8e1; color: #ffab00; }
        .badge-hold { background: #e7f7ff; color: #03c3ec; }
        .badge-completed { background: #e7e7ff; color: #696cff; }
        .badge-closed { background: #e8fadf; color: #71dd37; }

        .chart-panel { width: 100%; }
        .chart-panel td { border: none; vertical-align: middle; }
        .chart-svg { width: 220px; }
        .legend-item { margin-bottom: 8px; font-size: 11px; }
        .legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .legend-bar-wrap { width: 100%; height: 8px; background: #eceef1; border-radius: 4px; margin-top: 4px; overflow: hidden; }
        .legend-bar { height: 8px; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="report-header">
        <h1>Support Ticket Dashboard</h1>
        <p class="text-muted">Generated on {{ now()->format('d M Y, h:i A') }}</p>
        @if(!empty($filterSummary))
        <div class="filters">
            <strong>Applied Filters:</strong>
            @foreach($filterSummary as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h2>Support Ticket Summary</h2></div>
        <div class="card-body">
            <table class="summary-row">
                @foreach($summaryCardRows as $row)
                <tr>
                    @foreach($row as $card)
                    <td>
                        <div class="stat-card {{ $card['stat_class'] }}">
                            <div class="stat-label">{{ $card['label'] }}</div>
                            <div class="stat-value">{{ $card['count'] }}</div>
                        </div>
                    </td>
                    @endforeach
                    @for($pad = $row->count(); $pad < 4; $pad++)
                    <td></td>
                    @endfor
                </tr>
                @endforeach
            </table>
        </div>
    </div>

    <table class="layout-row" style="width:100%; margin-bottom:12px;">
        <tr>
            <td>
                <div class="card">
                    <div class="card-header"><h2>Tickets by Priority</h2></div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    @foreach($matrixStatuses as $status)
                                    <th class="text-center"><span class="badge badge-open">{{ $status['short'] }}</span></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (['high' => 'High', 'normal' => 'Medium', 'low' => 'Low'] as $key => $label)
                                <tr>
                                    <td><strong>{{ $label }}</strong></td>
                                    @foreach($matrixStatuses as $status)
                                    <td class="text-center">{{ $dashboard['priorityMatrix'][$key][$status['slug']] ?? 0 }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-header"><h2>Support Tickets by Status</h2></div>
                    <div class="card-body">
                        <table class="chart-panel">
                            <tr>
                                <td class="chart-svg">
                                    @if(!empty($statusChartImage))
                                        <img src="{{ $statusChartImage }}" width="220" height="220" alt="Support Tickets by Status">
                                    @elseif(!empty($statusChartSlices))
                                        <svg width="220" height="220" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
                                            @foreach($statusChartSlices as $slice)
                                                <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" stroke="#ffffff" stroke-width="1"></path>
                                            @endforeach
                                        </svg>
                                    @else
                                        <p class="text-muted">No chart data available.</p>
                                    @endif
                                </td>
                                <td>
                                    @foreach($statusChartSlices ?? [] as $slice)
                                        <div class="legend-item">
                                            <span class="legend-dot" style="background: {{ $slice['color'] }};"></span>
                                            <strong>{{ $slice['label'] }}</strong> — {{ $slice['value'] }} ({{ $slice['percent'] }}%)
                                            <div class="legend-bar-wrap">
                                                <div class="legend-bar" style="width: {{ $slice['percent'] }}%; background: {{ $slice['color'] }};"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="card {{ $divisionsWithTickets->count() <= 8 ? 'keep-together' : '' }}">
        <div class="card-header"><h2>Tickets by Divisions</h2></div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Division</th>
                        @foreach($matrixStatuses as $status)
                        <th class="text-center"><span class="badge badge-open">{{ $status['short'] }}</span></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($divisionsWithTickets as $division)
                    <tr>
                        <td><strong>{{ $division->division_name }}</strong></td>
                        @foreach($matrixStatuses as $status)
                        <td class="text-center">{{ $divisionMatrix[$division->id][$status['slug']] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $matrixStatuses->count() + 1 }}" class="text-center">No Divisions Found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card {{ $tickets->count() <= 12 ? 'keep-together' : '' }}">
        <div class="card-header"><h2>Recent Support Tickets</h2></div>
        <div class="card-body">
            <p class="text-muted" style="margin-bottom:10px;">{{ $totalTickets }} total tickets</p>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Branch</th>
                        <th>Division</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->displayTicketNumber() }}</td>
                        <td>{{ Str::limit($ticket->subject, 50) }}</td>
                        <td>{{ ucfirst($ticket->priority ?? '-') }}</td>
                        <td>{{ \App\Models\SupportTicketProgressStatus::plain($ticket->status) }}</td>
                        <td>{{ $ticket->branch?->name ?? '-' }}</td>
                        <td>{{ $ticket->division?->division_name ?? '-' }}</td>
                        <td>{{ optional($ticket->due_date)->format('d M Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No tickets found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>

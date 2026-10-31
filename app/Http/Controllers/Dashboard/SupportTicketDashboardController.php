<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\SupportTicketDashboardExport;
use App\Http\Controllers\Controller;
use App\Services\ReportPdfExportService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketProgressStatus;
use App\Models\Division;
use App\SupportTicketPriority;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SupportTicketDashboardController extends Controller
{
    public function index(Request $request) {
         $user = auth()->user();

    // =========================
    // FILTER DATA
    // =========================
    $companies = \App\Models\Company::visibleToUser($user)->active()->get();

    $branches=collect();

    if($request->company_id){

 $branches = \App\Models\Branch::visibleToUser($user)
        ->when($request->company_id, fn($q) =>
            $q->where('company_id', $request->company_id)
        )
        ->orderBy('name')
        ->get();
    }

   

    $divisions = Division::visibleToUser($user)
        ->orderBy('division_name')
        ->get();

    $collaboratorUsers = User::query()
        ->where('employee_status', 'active')
        ->visibleToUser($user)
        ->orderBy('name')
        ->select('id', 'name')
        ->when($request->division_id, fn ($q) => $q->where('division_id', $request->division_id))
        ->when($request->branch_id, fn ($q) => $q->whereHas('branches', fn ($b) =>
            $b->where('branches.id', $request->branch_id)
        ))
        ->when(! $request->branch_id && $request->company_id, fn ($q) => $q->whereHas('branches', fn ($b) =>
            $b->where('company_id', $request->company_id)
        ))
        ->get();

    // =========================
    // BASE QUERY
    // =========================
    $ticketQuery = \App\Models\SupportTicket::visibleToUser($user)
        ->when($request->company_id, fn($q) =>
            $q->where('company_id', $request->company_id)
        )
        ->when($request->branch_id, fn($q) =>
            $q->where('branch_id', $request->branch_id)
        )
        ->when($request->division_id, fn($q) =>
            $q->where('division_id', $request->division_id)
        )
        ->when($request->from_date, fn($q) =>
            $q->whereDate('due_date', '>=', $request->from_date)
        )
        ->when($request->to_date, fn($q) =>
            $q->whereDate('due_date', '<=', $request->to_date)
        )
        ->when($request->collaborator_id, fn ($q) =>
            $q->whereHas('assignments', fn ($a) =>
                $a->where('user_id', $request->collaborator_id)->where('is_active', true)
            )
        );

    $baseQuery = clone $ticketQuery;

    // =========================
    // STATUS COUNTS (1 QUERY)
    // =========================
    $statusCounts = (clone $baseQuery)
        ->select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')
        ->pluck('total', 'status');

    $dashboard = [
        'open_count'      => $statusCounts['open'] ?? 0,
        'accepted_count'  => $statusCounts['accepted'] ?? 0,
        'on_hold_count'   => $statusCounts['hold'] ?? 0,
        'completed_count' => $statusCounts['completed'] ?? 0,
        'closed_count'    => $statusCounts['closed'] ?? 0,
        'rejected_count'  => $statusCounts['rejected'] ?? 0,
        'wip_count'       => $statusCounts['work_in_progress'] ?? 0,
        'total_count'     => $baseQuery->count(),
    ];

    // =========================
    // PRIORITY MATRIX (1 QUERY)
    // =========================
    $priorityData = (clone $baseQuery)
        ->select('priority', 'status', DB::raw('COUNT(*) as total'))
        ->groupBy('priority', 'status')
        ->get();

    $priorityMatrix = [];

    foreach ($priorityData as $row) {
        $priorityMatrix[$row->priority][$row->status] = $row->total;
    }

    $statusData = collect(SupportTicketProgressStatus::dashboardDefinitions())
        ->map(function (array $item) use ($statusCounts) {
            $item['count'] = (int) ($statusCounts[$item['slug']] ?? 0);

            return $item;
        })
        ->filter(fn (array $item) => ($item['show_when_empty'] ?? true) || $item['count'] > 0)
        ->values();

    $knownSlugs = $statusData->pluck('slug')->all();

    foreach ($statusCounts as $slug => $total) {
        if (in_array($slug, $knownSlugs, true)) {
            continue;
        }

        $label = SupportTicketProgressStatus::plain((string) $slug);
        $statusData->push([
            'slug' => (string) $slug,
            'label' => $label,
            'short' => $label,
            'color' => SupportTicketProgressStatus::chartColor((string) $slug),
            'card_class' => 'card-custom',
            'kind' => 'progress',
            'in_matrix' => true,
            'show_when_empty' => true,
            'count' => (int) $total,
        ]);
    }

    $matrixStatuses = $statusData->filter(fn (array $item) => $item['in_matrix'])->values();
    $matrixSlugs = $matrixStatuses->pluck('slug')->all();

    // Normalize matrix (avoid ?? in blade)
    $priorities = ['high', 'normal', 'low'];

    foreach ($priorities as $priority) {
        foreach ($matrixSlugs as $status) {
            $priorityMatrix[$priority][$status] =
                $priorityMatrix[$priority][$status] ?? 0;
        }
    }

    $dashboard['priorityMatrix'] = $priorityMatrix;

    $divisionData = (clone $baseQuery)
        ->select('division_id', 'status', DB::raw('COUNT(*) as total'))
        ->groupBy('division_id', 'status')
        ->get();

    $divisionMatrix = [];

    foreach ($divisionData as $row) {
        $divisionMatrix[$row->division_id][$row->status] = $row->total;
    }

    foreach ($divisions as $division) {
        foreach ($matrixSlugs as $status) {
            $divisionMatrix[$division->id][$status] =
                $divisionMatrix[$division->id][$status] ?? 0;
        }
    }

    // =========================
    // TICKETS LIST (5 on screen, all filtered records for PDF)
    // =========================
    $tickets = (clone $baseQuery)
        ->with(['branch', 'division', 'creator'])
        ->latest()
        ->limit(8)
        ->get();

    $summaryCards = collect([
        ['label' => 'Total Tickets', 'count' => (int) $dashboard['total_count'], 'stat_class' => 'stat-dark'],
        ['label' => 'Open Tickets', 'count' => (int) ($statusCounts['open'] ?? 0), 'stat_class' => 'stat-warning'],
        ['label' => 'Accepted Tickets', 'count' => (int) ($statusCounts['accepted'] ?? 0), 'stat_class' => 'stat-secondary'],
    ]);

    foreach ($statusData->where('kind', 'progress') as $item) {
        $summaryCards->push([
            'label' => $item['label'],
            'count' => $item['count'],
            'stat_class' => match ($item['slug']) {
                'work_in_progress' => 'stat-primary',
                'hold' => 'stat-info',
                'in_purchase' => 'stat-secondary',
                default => 'stat-primary',
            },
        ]);
    }

    $summaryCards->push(
        ['label' => 'Completed Tickets', 'count' => (int) ($statusCounts['completed'] ?? 0), 'stat_class' => 'stat-success'],
        ['label' => 'Closed Tickets', 'count' => (int) ($statusCounts['closed'] ?? 0), 'stat_class' => 'stat-teal'],
        ['label' => 'Rejected Tickets', 'count' => (int) ($statusCounts['rejected'] ?? 0), 'stat_class' => 'stat-danger'],
    );

    $summaryCardRows = $summaryCards->chunk(4);

    $viewData = compact(
        'companies',
        'branches',
        'divisions',
        'collaboratorUsers',
        'dashboard',
        'divisionMatrix',
        'tickets',
        'statusData',
        'matrixStatuses',
        'summaryCardRows'
    );

    if (in_array($request->input('export_format'), ['pdf', 'excel'], true)) {
        $exportTickets = (clone $baseQuery)
            ->with(['branch', 'division', 'creator'])
            ->latest()
            ->get();

        $divisionsWithTickets = $divisions->filter(function ($division) use ($divisionMatrix) {
            return collect($divisionMatrix[$division->id] ?? [])->sum() > 0;
        })->values();

        if ($request->input('export_format') === 'excel') {
            return Excel::download(
                new SupportTicketDashboardExport([
                    'summaryCards' => $summaryCards,
                    'matrixStatuses' => $matrixStatuses,
                    'priorityMatrix' => $dashboard['priorityMatrix'],
                    'divisions' => $divisions,
                    'divisionMatrix' => $divisionMatrix,
                    'tickets' => $exportTickets,
                    'filters' => $this->buildFilterSummary($request),
                ]),
                'support-ticket-dashboard-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $statusChartSlices = $this->buildStatusChartSlices($statusData);

        return app(ReportPdfExportService::class)->download(
            'dashboard.exports.support-ticket-dashboard-pdf',
            array_merge($viewData, [
                'tickets' => $exportTickets,
                'divisionsWithTickets' => $divisionsWithTickets,
                'filterSummary' => $this->buildFilterSummary($request),
                'statusChartSlices' => $statusChartSlices,
                'statusChartImage' => $this->buildStatusChartImageBase64($statusChartSlices),
                'totalTickets' => $exportTickets->count(),
            ]),
            'support-ticket-dashboard-'.now()->format('Y-m-d').'.pdf',
            'landscape'
        );
    }

    return view('dashboard.support-ticket', $viewData);
    }

    protected function buildFilterSummary(Request $request): array
    {
        $summary = [];

        if ($request->filled('company_id')) {
            $summary['Company'] = Company::find($request->company_id)?->name ?? '-';
        }

        if ($request->filled('branch_id')) {
            $summary['Branch'] = Branch::find($request->branch_id)?->name ?? '-';
        }

        if ($request->filled('division_id')) {
            $summary['Division'] = Division::find($request->division_id)?->division_name ?? '-';
        }

        if ($request->filled('collaborator_id')) {
            $summary['Collaborator'] = User::find($request->collaborator_id)?->name ?? '-';
        }

        if ($request->filled('from_date')) {
            $summary['From Date'] = $request->from_date;
        }

        if ($request->filled('to_date')) {
            $summary['To Date'] = $request->to_date;
        }

        return $summary;
    }

    /** @return array<int, array{label: string, value: int, color: string, path: string, percent: float}> */
    protected function buildStatusChartSlices(\Illuminate\Support\Collection $statusData): array
    {
        $total = (int) $statusData->sum('count');

        if ($total <= 0) {
            return [];
        }

        $cx = 110;
        $cy = 110;
        $radius = 90;
        $startAngle = 0.0;
        $slices = [];

        foreach ($statusData as $item) {
            $value = (int) ($item['count'] ?? 0);
            $sliceFraction = $value / $total;

            if ($value <= 0 || $sliceFraction <= 0) {
                continue;
            }

            $sliceAngle = 360 * $sliceFraction;
            $endAngle = $startAngle + $sliceAngle;

            $slices[] = [
                'label' => (string) ($item['label'] ?? ''),
                'value' => $value,
                'color' => (string) ($item['color'] ?? '#999999'),
                'path' => $this->pieSlicePath($cx, $cy, $radius, $startAngle, $endAngle),
                'percent' => round($sliceFraction * 100, 1),
            ];

            $startAngle = $endAngle;
        }

        return $slices;
    }

    /** @param array<int, array{label: string, value: int, color: string, path: string, percent: float}> $slices */
    protected function buildStatusChartImageBase64(array $slices): ?string
    {
        if (! function_exists('imagecreatetruecolor') || $slices === []) {
            return null;
        }

        $size = 220;
        $image = imagecreatetruecolor($size, $size);

        if ($image === false) {
            return null;
        }

        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $transparent);

        $cx = (int) ($size / 2);
        $cy = (int) ($size / 2);
        $diameter = 180;
        $startAngle = 270.0;

        foreach ($slices as $slice) {
            $hex = ltrim((string) ($slice['color'] ?? '#999999'), '#');
            if (strlen($hex) !== 6) {
                continue;
            }

            $color = imagecolorallocate(
                $image,
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2))
            );

            $sliceAngle = ((float) ($slice['percent'] ?? 0) / 100) * 360;
            $endAngle = $startAngle + $sliceAngle;

            imagefilledarc(
                $image,
                $cx,
                $cy,
                $diameter,
                $diameter,
                (int) round($startAngle),
                (int) round($endAngle),
                $color,
                IMG_ARC_PIE
            );

            $startAngle = $endAngle;
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean() ?: '';
        imagedestroy($image);

        if ($pngData === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($pngData);
    }

    protected function pieSlicePath(float $cx, float $cy, float $radius, float $startAngle, float $endAngle): string
    {
        if ($endAngle - $startAngle >= 360) {
            return sprintf(
                'M %.2f %.2f m -%.2f 0 a %.2f %.2f 0 1 0 %.2f 0 a %.2f %.2f 0 1 0 -%.2f 0',
                $cx,
                $cy,
                $radius,
                $radius,
                $radius,
                $radius * 2,
                $radius,
                $radius,
                $radius * 2
            );
        }

        $start = $this->polarToCartesian($cx, $cy, $radius, $endAngle);
        $end = $this->polarToCartesian($cx, $cy, $radius, $startAngle);
        $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

        return sprintf(
            'M %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 0 %.2f %.2f Z',
            $cx,
            $cy,
            $start['x'],
            $start['y'],
            $radius,
            $radius,
            $largeArc,
            $end['x'],
            $end['y']
        );
    }

    /** @return array{x: float, y: float} */
    protected function polarToCartesian(float $cx, float $cy, float $radius, float $angle): array
    {
        $radians = deg2rad($angle - 90);

        return [
            'x' => $cx + ($radius * cos($radians)),
            'y' => $cy + ($radius * sin($radians)),
        ];
    }
}

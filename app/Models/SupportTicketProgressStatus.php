<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SupportTicketProgressStatus extends Model
{
    public const CACHE_KEY = 'support_ticket_progress_statuses.v1';

    /** Workflow statuses that stay on the ticket buttons, not this list. */
    public const RESERVED_SLUGS = [
        'open',
        'accepted',
        'completed',
        'closed',
        'reopen',
        'rejected',
    ];

    public const COLORS = [
        'primary',
        'info',
        'warning',
        'secondary',
        'dark',
        'success',
        'danger',
    ];

    protected $fillable = [
        'name',
        'slug',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        $flush = static function () {
            Cache::forget(self::CACHE_KEY);
        };

        static::saved($flush);
        static::deleted($flush);
    }

    public static function canManage(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['Admin', 'admin', 'superadmin', 'Super Admin']);
    }

    public static function selectable()
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()
                ->orderBy('sort_order')
                ->get(['slug', 'name', 'color'])
                ->mapWithKeys(fn ($row) => [
                    $row->slug => [
                        'name' => $row->name,
                        'color' => $row->color,
                    ],
                ])
                ->all();
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function presentation(?string $slug): array
    {
        $slug = strtolower(trim((string) $slug));
        $row = self::map()[$slug] ?? null;

        if ($row) {
            $color = in_array($row['color'], self::COLORS, true) ? $row['color'] : 'secondary';

            return [$row['name'], 'bg-label-'.$color];
        }

        $workflow = [
            'open' => ['Open', 'primary'],
            'accepted' => ['Accepted', 'info'],
            'work_in_progress' => ['Work In Progress', 'warning'],
            'hold' => ['Hold', 'secondary'],
            'in_purchase' => ['In Purchase', 'dark'],
            'completed' => ['Completed', 'success'],
            'closed' => ['Closed', 'danger'],
            'reopen' => ['Reopen', 'danger'],
            'rejected' => ['Rejected', 'danger'],
        ];

        if (isset($workflow[$slug])) {
            return [$workflow[$slug][0], 'bg-label-'.$workflow[$slug][1]];
        }

        if ($slug === '') {
            return ['Unknown', 'bg-label-light'];
        }

        return [ucwords(str_replace('_', ' ', $slug)), 'bg-label-secondary'];
    }

    public static function badge(?string $slug): string
    {
        [$name, $class] = self::presentation($slug);

        return '<span class="badge '.$class.'">'.e($name).'</span>';
    }

    public static function plain(?string $slug): string
    {
        return self::presentation($slug)[0];
    }

    public static function chartColor(string $slug): string
    {
        $known = [
            'open' => '#DDA15E',
            'accepted' => '#A3B19B',
            'work_in_progress' => '#6B8BA4',
            'hold' => '#A8DADC',
            'in_purchase' => '#7C6A9A',
            'completed' => '#819864',
            'closed' => '#E0A899',
            'reopen' => '#5C7A8A',
            'rejected' => '#C15C3D',
        ];

        if (isset($known[$slug])) {
            return $known[$slug];
        }

        $palette = ['#B5838D', '#6D6875', '#E09F3E', '#335C67', '#9A8C98', '#4A5759', '#BC6C25', '#3D5A80', '#98C1D9', '#EE6C4D'];

        return $palette[abs(crc32($slug)) % count($palette)];
    }

    public static function cardClass(string $slug): string
    {
        return match ($slug) {
            'work_in_progress' => 'card-wip',
            'hold' => 'card-hold',
            'in_purchase' => 'card-purchase',
            default => 'card-custom',
        };
    }

    public static function matrixBadgeClass(string $slug): string
    {
        return match ($slug) {
            'open' => 'bg-label-warning',
            'hold' => 'bg-label-info',
            'completed' => 'bg-label-primary',
            'closed' => 'bg-label-success',
            default => 'bg-label-secondary',
        };
    }

    /**
     * Ordered statuses for the ticket dashboard chart, cards, and matrices.
     *
     * @return array<int, array{slug: string, label: string, short: string, color: string, card_class: string, kind: string, in_matrix: bool, show_when_empty: bool}>
     */
    public static function dashboardDefinitions(): array
    {
        $items = [];

        foreach ([
            ['slug' => 'open', 'label' => 'Open Tickets', 'short' => 'Open', 'card_class' => 'card-open', 'in_matrix' => true],
            ['slug' => 'accepted', 'label' => 'Accepted Tickets', 'short' => 'Accepted', 'card_class' => 'card-accepted', 'in_matrix' => false],
        ] as $row) {
            $items[] = $row + [
                'color' => self::chartColor($row['slug']),
                'kind' => 'workflow',
                'show_when_empty' => true,
            ];
        }

        foreach (self::selectable() as $status) {
            $items[] = [
                'slug' => $status->slug,
                'label' => $status->name,
                'short' => $status->name,
                'color' => self::chartColor($status->slug),
                'card_class' => self::cardClass($status->slug),
                'kind' => 'progress',
                'in_matrix' => true,
                'show_when_empty' => true,
            ];
        }

        foreach ([
            ['slug' => 'completed', 'label' => 'Completed Tickets', 'short' => 'Completed', 'card_class' => 'card-completed', 'in_matrix' => true, 'show_when_empty' => true],
            ['slug' => 'closed', 'label' => 'Closed Tickets', 'short' => 'Closed', 'card_class' => 'card-closed', 'in_matrix' => true, 'show_when_empty' => true],
            ['slug' => 'reopen', 'label' => 'Reopen', 'short' => 'Reopen', 'card_class' => 'card-custom', 'in_matrix' => false, 'show_when_empty' => false],
            ['slug' => 'rejected', 'label' => 'Rejected Tickets', 'short' => 'Rejected', 'card_class' => 'card-rejected', 'in_matrix' => false, 'show_when_empty' => true],
        ] as $row) {
            $items[] = $row + [
                'color' => self::chartColor($row['slug']),
                'kind' => 'workflow',
            ];
        }

        return $items;
    }
}

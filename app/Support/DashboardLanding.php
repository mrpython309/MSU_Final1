<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardLanding
{
    /**
     * Dashboards other than the main home page, in the order they should be tried.
     *
     * @return array<string, string> permission => route name
     */
    public static function alternatives(): array
    {
        return [
            'dashboard.asset:view' => 'dashboard.asset',
            'dashboard.supportticket:view' => 'dashboard.supportticket',
            'item-dashboard:view' => 'item-dashboard.index',
            'procurement.dashboard:view' => 'procurement.dashboard',
            'purchase-management.dashboard' => 'purchase-management.dashboard',
        ];
    }

    /**
     * Route name of the first dashboard this user is allowed to open.
     */
    public static function routeName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        try {
            if ($user->hasAnyRole(['superadmin', 'Super Admin']) || $user->can('dashboard:view')) {
                return 'dashboard';
            }
        } catch (\Throwable) {
            if ($user->can('dashboard:view')) {
                return 'dashboard';
            }
        }

        foreach (self::alternatives() as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Send the user away from the main dashboard when they cannot open it.
     */
    public static function redirectIfNotHome(?User $user): ?RedirectResponse
    {
        $route = self::routeName($user);

        if ($route === null || $route === 'dashboard') {
            return null;
        }

        return redirect()->route($route);
    }
}

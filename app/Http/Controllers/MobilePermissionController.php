<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MobilePermissionController extends Controller
{
    /**
     * Dedicated mobile application module permissions.
     * Completely isolated from web application permissions so managing mobile access
     * has ZERO side-effects on the web application.
     */
    public const MOBILE_PERMISSIONS = [
        'mobile.dashboard:view' => [
            'key' => 'mobile.dashboard:view',
            'client_key' => 'dashboard:view',
            'name' => 'Dashboard',
            'description' => 'Access mobile home metrics, status overviews and dashboards',
            'icon' => 'bx bx-home-circle',
            'color' => 'primary',
        ],
        'mobile.asset:view' => [
            'key' => 'mobile.asset:view',
            'client_key' => 'asset:view',
            'name' => 'Assets Module',
            'description' => 'Browse and view organization assets, specifications and status',
            'icon' => 'bx bx-box',
            'color' => 'success',
        ],
        'mobile.audit:view' => [
            'key' => 'mobile.audit:view',
            'client_key' => 'audit:view',
            'name' => 'Asset Audit',
            'description' => 'Conduct physical QR scans, asset audits and view audit logs',
            'icon' => 'bx bx-qr-scan',
            'color' => 'warning',
        ],
        'mobile.support-ticket:view' => [
            'key' => 'mobile.support-ticket:view',
            'client_key' => 'support-ticket:view',
            'name' => 'Support Tickets',
            'description' => 'Access helpdesk ticketing system, active tickets and status tracking',
            'icon' => 'bx bx-support',
            'color' => 'info',
        ],
        'mobile.support-ticket:create' => [
            'key' => 'mobile.support-ticket:create',
            'client_key' => 'support-ticket:create',
            'name' => 'Create Tickets',
            'description' => 'Allow user to submit new incident/request tickets from mobile',
            'icon' => 'bx bx-plus-circle',
            'color' => 'danger',
        ],
    ];

    public function index(Request $request)
    {
        $this->ensureMobilePermissionsExist();

        $roles = Role::with('permissions')->orderBy('name')->get();

        $usersQuery = User::with(['roles', 'permissions'])->where('employee_status', 'active');
        if ($request->filled('search')) {
            $search = trim($request->search);
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $users = $usersQuery->orderBy('name')->paginate(15)->withQueryString();

        $mobilePermissions = self::MOBILE_PERMISSIONS;

        return view('master.mobile_permissions.index', compact('roles', 'users', 'mobilePermissions'));
    }

    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);
        $selectedKeys = $request->input('permissions', []);

        $mobileKeys = array_keys(self::MOBILE_PERMISSIONS);

        // Keep all existing non-mobile permissions (web permissions untouched!)
        $currentPerms = $role->permissions->pluck('name')->toArray();
        $retainedPerms = array_diff($currentPerms, $mobileKeys);

        // Merge retained web permissions + newly selected mobile.* permissions
        $finalPerms = array_values(array_unique(array_merge($retainedPerms, $selectedKeys)));

        $role->syncPermissions($finalPerms);

        // Clean stale direct mobile permissions on users of this role so role changes take effect immediately
        $roleUsers = User::role($role->name)->get();
        foreach ($roleUsers as $user) {
            $userDirectPerms = $user->permissions->pluck('name')->toArray();
            $overlap = array_intersect($userDirectPerms, $mobileKeys);
            if (!empty($overlap)) {
                $user->revokePermissionTo($overlap);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('mobile-permissions.index')->with('success', "Mobile module permissions for role '{$role->name}' updated successfully without affecting web permissions.");
    }

    public function updateUserPermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array',
        ]);

        $user = User::findOrFail($request->user_id);
        $selectedKeys = $request->input('permissions', []);

        $mobileKeys = array_keys(self::MOBILE_PERMISSIONS);

        // Keep existing non-mobile direct permissions
        $currentDirectPerms = $user->permissions->pluck('name')->toArray();
        $retainedDirectPerms = array_diff($currentDirectPerms, $mobileKeys);

        // Merge retained direct web + newly selected mobile.*
        $finalDirectPerms = array_values(array_unique(array_merge($retainedDirectPerms, $selectedKeys)));

        $user->syncPermissions($finalDirectPerms);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('mobile-permissions.index')->with('success', "Mobile module permissions for user '{$user->email}' updated successfully.");
    }

    public function resetUserPermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $mobileKeys = array_keys(self::MOBILE_PERMISSIONS);

        $userDirectPerms = $user->permissions->pluck('name')->toArray();
        $overlap = array_intersect($userDirectPerms, $mobileKeys);
        if (!empty($overlap)) {
            $user->revokePermissionTo($overlap);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('mobile-permissions.index')->with('success', "Direct mobile permissions for user '{$user->email}' cleared. User now follows role defaults.");
    }

    private function ensureMobilePermissionsExist(): void
    {
        foreach (self::MOBILE_PERMISSIONS as $key => $info) {
            foreach (['web', 'api'] as $guard) {
                Permission::firstOrCreate([
                    'name' => $key,
                    'guard_name' => $guard,
                ]);
            }
        }
    }
}

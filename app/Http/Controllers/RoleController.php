<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** @var Collection<string, Collection<int, Permission>>|null */
    private static ?Collection $roleFormGroups = null;
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('id', 'desc')->paginate(15);
        $permissionCounts = [];

        foreach ($roles as $role) {
            $permissionCounts[$role->id] = self::countVisibleAssigned(
                $role->permissions->pluck('name')->all()
            );
        }

        return view('master.roles.index', compact('roles', 'permissionCounts'));
    }

    public function create()
    {
        $permissions = $this->permissionsGroupedForRoleForm();

        return view('master.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $permissions = $this->expandPermissionAliases($data['permissions'] ?? []);

        $roleData = ['name' => $data['name']];
        if (! empty($data['description'])) {
            $roleData['description'] = $data['description'];
        }

        $role = Role::create($roleData);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Role created successfully with '.self::countVisibleAssigned($permissions).' permissions assigned.');
    }

    public function edit(Role $role)
    {
        $permissions = $this->permissionsGroupedForRoleForm();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('master.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $permissions = $this->expandPermissionAliases($data['permissions'] ?? []);
        $permissions = array_values(array_unique(array_merge(
            $permissions,
            $this->retainedHiddenPermissions($role)
        )));

        $roleData = ['name' => $data['name']];
        if (! empty($data['description'])) {
            $roleData['description'] = $data['description'];
        } else {
            $roleData['description'] = null;
        }

        $role->update($roleData);
        $role->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Role updated successfully with '.self::countVisibleAssigned($permissions).' permissions assigned.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    /**
     * When the UI saves a canonical permission (e.g. purchase-requisitions:view),
     * also grant matching aliases (e.g. purchase-requisition.view) so menus/routes work.
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function expandPermissionAliases(array $permissions): array
    {
        $selected = array_values(array_unique(array_filter(array_map('strval', $permissions))));
        if ($selected === []) {
            return [];
        }

        $expanded = $selected;

        foreach (self::permissionsGroupedByModule()->flatten(1) as $permission) {
            $canonical = (string) $permission->name;
            $aliases = array_values(array_filter(array_map('strval', $permission->alias_names ?? [])));

            if ($aliases === []) {
                continue;
            }

            $family = array_values(array_unique(array_merge([$canonical], $aliases)));
            if (array_intersect($family, $selected) === []) {
                continue;
            }

            $expanded = array_merge($expanded, $family);
        }

        return array_values(array_unique($expanded));
    }

    /**
     * Group permissions by submodule for Roles UI.
     * Supports both "module:action" and "module.action" naming.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function permissionsGroupedByModule(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->filter(function (Permission $p) {
                if (preg_match('/\.(l1|l2|l3|l4|it_head|finance|manager|department_head)$/i', $p->name)) {
                    return false;
                }
                if (str_starts_with($p->name, 'permissions:') || str_starts_with($p->name, 'permission.')) {
                    return false;
                }
                return true;
            })
            ->groupBy(fn (Permission $p) => self::permissionModuleLabel($p->name))
            ->map(fn (Collection $group) => self::dedupePermissionsByAction($group));
    }

    /**
     * Count assigned permissions the way the role form shows them:
     * one per visible action, without unused modules or alias duplicates.
     *
     * @param  list<string>  $assignedNames
     */
    public static function countVisibleAssigned(array $assignedNames): int
    {
        $assigned = array_values(array_unique(array_map('strval', $assignedNames)));
        if ($assigned === []) {
            return 0;
        }

        $count = 0;

        foreach (self::permissionsGroupedForRoleForm() as $group) {
            foreach ($group as $permission) {
                $family = array_merge([(string) $permission->name], $permission->alias_names ?? []);
                if (array_intersect($family, $assigned) !== []) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Role create/edit only lists modules that are still in the app menu.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function permissionsGroupedForRoleForm(): Collection
    {
        if (self::$roleFormGroups instanceof Collection) {
            return self::$roleFormGroups;
        }

        self::$roleFormGroups = self::permissionsGroupedByModule()
            ->filter(function (Collection $group) {
                $permission = $group->first();
                if (! $permission instanceof Permission) {
                    return false;
                }

                return self::isRoleModuleVisible(self::permissionModuleKey($permission->name));
            });

        return self::$roleFormGroups;
    }

    /**
     * Keep permissions for unused modules already on the role so a save
     * does not drop access that the form no longer displays.
     *
     * @return list<string>
     */
    private function retainedHiddenPermissions(Role $role): array
    {
        return $role->permissions
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->filter(function (string $name) {
                if (preg_match('/\.(l1|l2|l3|l4|it_head|finance|manager|department_head)$/i', $name)) {
                    return false;
                }
                if (str_starts_with($name, 'permissions:') || str_starts_with($name, 'permission.')) {
                    return false;
                }

                return ! self::isRoleModuleVisible(self::permissionModuleKey($name));
            })
            ->values()
            ->all();
    }

    public static function permissionModuleKey(string $name): string
    {
        if (str_contains($name, ':')) {
            $module = explode(':', $name, 2)[0];
        } elseif (str_contains($name, '.')) {
            $parts = explode('.', $name);
            array_pop($parts);
            $module = implode('.', $parts) ?: 'general';
        } else {
            $module = 'general';
        }

        return strtolower($module);
    }

    public static function isRoleModuleVisible(string $module): bool
    {
        $module = strtolower($module);

        $exact = [
            'dashboard',
            'asset',
            'asset-types',
            'asset-category',
            'companies',
            'branch',
            'location',
            'brands',
            'cost-centres',
            'designation',
            'department',
            'division',
            'vendor',
            'asset-master',
            'item-master',
            'product-category',
            'product-sub-category',
            'unit',
            'payment-term',
            'miscellaneous-charge',
            'hsn',
            'hsns',
            'employee-master',
            'asset-custodian',
            'shipping-detail',
            'employees',
            'users',
            'roles',
            'item-dashboard',
            'item-stocks',
            'item-stock',
            'stock-adjustments',
            'stock-adjustment',
            'msu-purchase-order',
            'msu-purchase-orders',
            'msu-grn',
            'msu-grns',
            'msu-work-order',
            'msu-work-orders',
            'msu-work-invoice',
            'msu-work-invoices',
            'material_requests',
            'material_request',
            'material-requests',
            'material-request',
            'material_issues',
            'material_issue',
            'material-issues',
            'material-issue',
            'support-ticket',
            'support-tickets',
            'audit',
            'audits',
            'asset-audit',
            'asset-audits',
        ];

        if (in_array($module, $exact, true)) {
            return true;
        }

        foreach (['asset.', 'dashboard.', 'reports.', 'support-ticket.'] as $prefix) {
            if (str_starts_with($module, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function dedupePermissionsByAction(Collection $permissions): Collection
    {
        $byAction = [];

        foreach ($permissions as $permission) {
            $action = strtolower(self::permissionActionLabel($permission->name));
            if ($action === 'payment') {
                $action = 'pay';
            }
            if ($action === '') {
                continue;
            }

            $byAction[$action][] = $permission;
        }

        return collect($byAction)
            ->map(function (array $items) {
                usort($items, fn (Permission $a, Permission $b) => self::permissionNameRank($b->name) <=> self::permissionNameRank($a->name));

                /** @var Permission $canonical */
                $canonical = $items[0];
                $aliases = collect($items)
                    ->pluck('name')
                    ->filter(fn (string $name) => $name !== $canonical->name)
                    ->values()
                    ->all();
                $canonical->setAttribute('alias_names', $aliases);

                return $canonical;
            })
            ->sortBy('name')
            ->values();
    }

    public static function permissionNameRank(string $name): int
    {
        if (str_contains($name, ':')) {
            return 2;
        }

        if (str_contains($name, '.')) {
            return 1;
        }

        return 0;
    }

    public static function permissionModuleLabel(string $name): string
    {
        $module = self::permissionModuleKey($name);

        return match (true) {
            str_starts_with($module, 'indent') => 'Procurement — Indents',
            str_starts_with($module, 'purchase-requisition') => 'Procurement — Purchase Requisitions',
            str_starts_with($module, 'rfq') => 'Procurement — RFQs',
            str_starts_with($module, 'quotation') => 'Procurement — Quotations',
            str_starts_with($module, 'purchase-order') => 'Procurement — Purchase Orders',
            str_starts_with($module, 'msu-purchase-order') => 'MSU — Purchase Orders',
            str_starts_with($module, 'msu-work-order') => 'MSU — Work Orders',
            str_starts_with($module, 'msu-grn') => 'MSU — GRNs',
            str_starts_with($module, 'material_request') => 'MSU — Material Requests',
            str_starts_with($module, 'material_issue') => 'MSU — Material Issues',
            str_starts_with($module, 'grn') => 'Procurement — GRNs',
            str_starts_with($module, 'payment') => 'Procurement — Payments',
            str_starts_with($module, 'quality-inspection') => 'Procurement — Quality Inspections',
            str_starts_with($module, 'procurement') => 'Procurement — Dashboard & Reports',
            str_starts_with($module, 'purchase-management') => 'Procurement — Dashboard & Reports',
            str_starts_with($module, 'purchase-report') => 'Procurement — Dashboard & Reports',
            str_starts_with($module, 'approval') => 'Procurement — Approval Templates',
            str_starts_with($module, 'asset-audit') || str_starts_with($module, 'audit') => 'Asset Audit',
            $module === 'cost-centres' => 'Cost Centres',
            $module === 'support-ticket' || $module === 'support-tickets' => 'Support Tickets',
            $module === 'dashboard.supportticket' => 'Support Ticket Dashboard',
            default => ucfirst($module),
        };
    }

    /** Human label for the action part of a permission (supports module:action and module.action). */
    public static function permissionActionLabel(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if (str_contains($name, ':')) {
            $action = explode(':', $name, 2)[1] ?? $name;
        } elseif (str_contains($name, '.')) {
            $action = (string) last(explode('.', $name));
        } else {
            $action = $name;
        }

        $action = trim(str_replace(['-', '_'], ' ', $action));

        return $action !== '' ? ucfirst($action) : $name;
    }
}

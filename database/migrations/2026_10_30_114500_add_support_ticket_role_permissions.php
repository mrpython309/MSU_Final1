<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! class_exists(Permission::class)) {
            return;
        }

        $guard = 'web';
        $permissions = [
            'support-ticket:view',
            'support-ticket:create',
            'support-ticket:edit',
            'support-ticket:delete',
            'dashboard.supportticket:view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        foreach (['Super Admin', 'superadmin', 'Admin', 'admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! class_exists(Permission::class)) {
            return;
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'support-ticket:view',
                'support-ticket:create',
                'support-ticket:edit',
                'support-ticket:delete',
                'dashboard.supportticket:view',
            ])
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

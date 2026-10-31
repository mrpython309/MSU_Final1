<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('division_name');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->unique('division_name');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('division_id')
                    ->nullable()
                    ->after('designation_id')
                    ->constrained('divisions')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('support_tickets') && ! Schema::hasColumn('support_tickets', 'division_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->foreignId('division_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('divisions')
                    ->nullOnDelete();
            });
        }

        $guard = 'web';
        $permissions = [
            'division:view',
            'division:create',
            'division:edit',
            'division:delete',
            'division:status',
        ];

        if (Schema::hasTable('permissions') && class_exists(Permission::class)) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
            }

            $roleNames = ['Super Admin', 'superadmin', 'Admin', 'admin'];
            $roles = Role::query()->where('guard_name', $guard)->whereIn('name', $roleNames)->get();

            $departmentView = Permission::query()
                ->where('name', 'department:view')
                ->where('guard_name', $guard)
                ->first();

            if ($departmentView) {
                $roles = $roles->merge(
                    Role::permission('department:view')->where('guard_name', $guard)->get()
                )->unique('id');
            }

            foreach ($roles as $role) {
                $role->load('permissions');
                $role->givePermissionTo($permissions);
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'division_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('division_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('division_id');
            });
        }

        Schema::dropIfExists('divisions');
    }
};

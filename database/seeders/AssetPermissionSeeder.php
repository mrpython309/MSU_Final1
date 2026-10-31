<?php

namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AssetPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $guard = 'web';

        /**
         * 🔹 Step 1: Define permissions (based on your routes)
         */
        $permissions = [
            'dashboard:view',
            'companies:view', 'companies:create', 'companies:edit', 'companies:delete',
            'branch:view', 'branch:create', 'branch:edit', 'branch:delete',
            'location:view', 'location:create', 'location:edit', 'location:delete',
            'brands:view', 'brands:create', 'brands:edit', 'brands:delete',
            'cost-centres:view', 'cost-centres:create', 'cost-centres:edit', 'cost-centres:delete',
            'department:view', 'department:create', 'department:edit', 'department:delete',
            'division:view', 'division:create', 'division:edit', 'division:delete', 'division:status',
            'designation:view', 'designation:create', 'designation:edit', 'designation:delete',
            'vendor:view', 'vendor:create', 'vendor:edit', 'vendor:delete',
            'asset-master.view', 'asset-master.create', 'asset-master.edit', 'asset-master.delete',
            'item-master:view', 'item-master:create', 'item-master:edit', 'item-master:delete',
            'product-category:view', 'product-category:create', 'product-category:edit', 'product-category:delete', 'product-category:status',
            'product-sub-category:view', 'product-sub-category:create', 'product-sub-category:edit', 'product-sub-category:delete', 'product-sub-category:status',
            'unit:view', 'unit:create', 'unit:edit', 'unit:delete', 'unit:status',
            'payment-term:view', 'payment-term:create', 'payment-term:edit', 'payment-term:delete', 'payment-term:status',
            'miscellaneous-charge:view', 'miscellaneous-charge:create', 'miscellaneous-charge:edit', 'miscellaneous-charge:delete', 'miscellaneous-charge:status',
            'hsn:view', 'hsn:create', 'hsn:edit', 'hsn:delete', 'hsn:status',
            'employee-master:view', 'employee-master:create', 'employee-master:edit', 'employee-master:delete', 'employee-master:status',
            'asset-custodian:view', 'asset-custodian:create', 'asset-custodian:edit', 'asset-custodian:delete', 'asset-custodian:status',
            'shipping-detail:view', 'shipping-detail:create', 'shipping-detail:edit', 'shipping-detail:delete', 'shipping-detail:status',
            'msu-purchase-order:view', 'msu-purchase-order:create', 'msu-purchase-order:edit', 'msu-purchase-order:delete', 'msu-purchase-order:approve', 'msu-purchase-order:cancel',
            'msu-work-order:view', 'msu-work-order:create', 'msu-work-order:edit', 'msu-work-order:delete', 'msu-work-order:approve', 'msu-work-order:cancel',
            'msu-grn:view', 'msu-grn:create', 'msu-grn:delete', 'msu-grn:approve', 'msu-grn:cancel',
            'asset-types:view', 'asset-types:create', 'asset-types:edit', 'asset-types:delete',
            'asset-category:view', 'asset-category:create', 'asset-category:edit', 'asset-category:delete',
            'asset:view', 'asset:create', 'asset:edit', 'asset:delete',
            'asset.assign.bulk:view',
            'asset.maintenance:view', 'asset.maintenance:create', 'asset.maintenance:edit', 'asset.maintenance:delete',
            'asset.document:view', 'asset.document:create', 'asset.document:edit', 'asset.document:delete',
            'asset.assignment:view', 'asset.assignment:create', 'asset.assignment:edit', 'asset.assignment:delete',
            'asset.warranty:view', 'asset.warranty:create', 'asset.warranty:edit', 'asset.warranty:delete',
            'asset.replacement:view', 'asset.replacement:create', 'asset.replacement:edit', 'asset.replacement:delete',
            'asset.depreciation:view', 
            'dashboard.asset:view',
            'audit:view', 'audit:create', 'audit:edit', 'audit:delete',
            'indents:view', 'indents:create', 'indents:edit', 'indents:delete', 'indents:approve', 'indents:reject',
            'indent.approve.manager', 'indent.approve.it_head', 'indent.approve.finance',
            'approval-workflows:manage',
            'purchase-requisitions:view',
            'purchase-requisition.view',
            'purchase-requisition.process',
            'rfqs:view', 'rfqs:create', 'rfqs:edit',
            'purchase-orders:view', 'purchase-orders:create', 'purchase-orders:edit',
            'grns:view', 'grns:create', 'grns:edit', 'grns:approve',
            'grn.view', 'grn.create', 'grn.edit', 'grn.approve',
            'procurement.dashboard:view', 'procurement.reports:view',
            'settings:view', 
            'reports.companywiseassets:view', 
            'reports.branchwiseassets:view', 
            'reports.brandwiseassets:view', 
            'reports.vendorwiseassets:view', 
            'reports.locationwiseassets:view', 
            'reports.categorywiseassets:view', 
            'reports.typewiseassets:view', 
            'employees:view', 'employees:create', 'employees:edit', 'employees:delete',
            'support-ticket:view', 'support-ticket:create', 'support-ticket:edit', 'support-ticket:delete',
            'dashboard.supportticket:view',
        ];

        Permission::query()
            ->where('guard_name', $guard)
            ->where(function ($query) {
                $query->where('name', 'like', 'recurring-tasks.%')
                    ->orWhere('name', 'like', 'reports.%wisesupportticket:view')
                    ->orWhere('name', 'like', 'inventory%');
            })
            ->delete();

        /**
         * 🔹 Step 2: Create permissions
         */
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard
            ]);
        }


        $employee = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => $guard
        ]);

         $superAdmin = Role::firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => $guard
        ]);

        $superAdmin->syncPermissions(Permission::all());


         $employee->syncPermissions(Permission::all());
         $employee->revokePermissionTo('approval-workflows:manage');

        // The production role is named "Super Admin" (with a space) and is not
        // covered by the blade menu's 'superadmin' bypass, so grant it everything too.
        $namedSuperAdmin = Role::where('name', 'Super Admin')->where('guard_name', $guard)->first();
        if ($namedSuperAdmin) {
            $namedSuperAdmin->syncPermissions(Permission::all());
        }
        foreach (['Admin', 'admin'] as $adminRoleName) {
            if ($admin = Role::where('name', $adminRoleName)->where('guard_name', $guard)->first()) {
                $admin->syncPermissions(Permission::all());
            }
        }
        if ($manager = Role::where('name', 'Manager')->where('guard_name', $guard)->first()) {
            $manager->givePermissionTo(Permission::query()->whereIn('name', [
                'indents:view', 'indents:create', 'indents:edit', 'indents:approve',
                'rfqs:view',
                'purchase-orders:view',
                'grns:view',
                'procurement.dashboard:view', 'procurement.reports:view',
            ])->get());
        }
    }
}
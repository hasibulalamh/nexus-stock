<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Optional starter permission sets for the three operational roles.
 *
 * This seeder is NOT run automatically. A human can apply it after
 * PermissionSeeder with:
 *
 *     php artisan db:seed --class=RolePermissionDefaultsSeeder
 *
 * It only creates roles that do not exist yet and syncs the listed
 * permissions; adjust the sets in the Roles UI afterwards if needed.
 */
class RolePermissionDefaultsSeeder extends Seeder
{
    public const DEFAULTS = [
        'warehouse_manager' => [
            'dashboard.view', 'products.view', 'suppliers.view',
            'stock.view', 'stock.movements.view', 'stock.opening', 'stock.opening.approve',
            'stock.thresholds', 'stock.alerts.view', 'stock.alerts.receive',
            'stock.discrepancies.resolve', 'stock.kpi.view',
            'transfers.view', 'transfers.create', 'transfers.send', 'transfers.receive', 'transfers.cancel',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.approve',
            'receiving.view', 'receiving.create',
            'adjustments.view', 'adjustments.create', 'adjustments.approve',
            'counts.view', 'counts.create', 'counts.approve',
            'reports.view',
        ],
        'branch_manager' => [
            'dashboard.view', 'products.view',
            'stock.view', 'stock.movements.view', 'stock.opening',
            'stock.alerts.view', 'stock.alerts.receive',
            'transfers.view', 'transfers.receive', 'transfers.cancel',
            'adjustments.view', 'adjustments.create',
            'counts.view', 'counts.create',
            'reports.view',
        ],
        'branch_staff' => [
            'dashboard.view', 'stock.view', 'stock.alerts.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::DEFAULTS as $roleName => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['status' => 'active', 'icon_class' => 'fa-user']
            );

            // Only assign permissions that exist in this installation.
            $available = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->pluck('name')
                ->all();

            $role->syncPermissions($available);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

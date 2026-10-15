<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingService
{
    private const DESTINATIONS = [
        ['permission' => 'dashboard.view', 'route' => 'admin.dashboard'],
        ['permission' => 'users.view', 'route' => 'admin.users.list'],
        ['permission' => 'roles.view', 'route' => 'admin.roles.list'],
        ['permission' => 'branches.view', 'route' => 'admin.branch.list'],
        ['permission' => 'warehouses.view', 'route' => 'admin.warehouse.list'],
        ['permission' => 'categories.view', 'route' => 'admin.categories.list'],
        ['permission' => 'brands.view', 'route' => 'admin.brands.list'],
        ['permission' => 'products.view', 'route' => 'admin.products.list'],
    ];

    public function redirect(User $user): RedirectResponse|View
    {
        foreach (self::DESTINATIONS as $destination) {
            if ($user->can($destination['permission'])) {
                return redirect()->route($destination['route']);
            }
        }

        return view('auth.no-access');
    }
}

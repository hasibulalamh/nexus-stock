<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $branchIds = $user->accessibleBranchIds();
        $warehouseIds = $user->accessibleWarehouseIds();

        $branchesCount = Branch::accessibleTo($user)->count();
        $warehousesCount = Warehouse::accessibleTo($user)->count();
        $users = User::query();
        if ($branchIds === [] && $warehouseIds === []) {
            $users->whereRaw('1 = 0');
        }
        if ($branchIds !== null || $warehouseIds !== null) {
            $users->where(function ($query) use ($branchIds, $warehouseIds) {
                if ($branchIds !== null && $branchIds !== []) {
                    $query->whereHas('branches', fn ($branches) => $branches->whereIn('branches.id', $branchIds));
                }
                if ($warehouseIds !== null && $warehouseIds !== []) {
                    $query->orWhereHas('warehouses', fn ($warehouses) => $warehouses->whereIn('warehouses.id', $warehouseIds));
                }
            });
        }

        return view('admin.dashboard', [
            'role' => $user->roles->first(),
            'usersCount' => $users->count(),
            'rolesCount' => \Spatie\Permission\Models\Role::count(),
            'branchesCount' => $branchesCount,
            'warehousesCount' => $warehousesCount,
            'recentUsers' => $users->latest()->take(6)->get(),
        ]);
    }
}

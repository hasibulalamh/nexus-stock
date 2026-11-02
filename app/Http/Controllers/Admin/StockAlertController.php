<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAlert;
use App\Support\StockAccess;
use Illuminate\Http\Request;

class StockAlertController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canViewAll = $user->hasAccessAll() || $user->can('stock.alerts.view_all');
        $branchIds = $user->accessibleBranchIds() ?? [];
        $warehouseIds = $user->accessibleWarehouseIds() ?? [];

        $alerts = StockAlert::with(['product', 'branch.warehouse'])
            ->where(function ($query) use ($canViewAll, $branchIds, $warehouseIds): void {
                if ($canViewAll) {
                    return;
                }
                $query->whereIn('branch_id', $branchIds)
                    ->orWhereIn('warehouse_id', $warehouseIds);
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->alert_id, fn ($q, $id) => $q->whereKey($id))
            ->latest('opened_at')->paginate(25)->withQueryString();

        return view('admin.stock.alerts', [
            'alerts' => $alerts,
            'branches' => $canViewAll ? Branch::all() : Branch::whereIn('id', array_unique(array_merge($branchIds, StockAlert::whereIn('warehouse_id', $warehouseIds)->pluck('branch_id')->all())))->get(),
            'products' => Product::whereIn('id', StockAlert::query()->select('product_id'))->get(),
        ]);
    }
}

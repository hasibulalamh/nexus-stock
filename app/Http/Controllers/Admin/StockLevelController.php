<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockLevel;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;

class StockLevelController
{
    public function index()
    {
        $user = auth()->user();
        $levels = StockLevel::with('product')
            ->where(function ($query) use ($user) {
                foreach (['branch', 'warehouse'] as $type) {
                    $ids = StockAccess::readableLocationIds($user, $type);
                    $query->orWhere(function ($location) use ($type, $ids) {
                        $location->where('location_type', $type)
                            ->when($ids !== null, fn ($q) => $q->whereIn('location_id', $ids));
                    });
                }
            })
            ->when(request('branch_id'), fn ($query, $branchId) => $query
                ->where('location_type', 'branch')
                ->where('location_id', $branchId))
            ->paginate(25);

        return view('admin.stock.levels', compact('levels'));
    }

    public function setMinimum(Request $request, StockLevel $stockLevel)
    {
        abort_unless($stockLevel->location_type === 'branch', 404);
        abort_unless(StockAccess::canWrite(auth()->user(), 'branch', $stockLevel->location_id), 403);

        $validated = Validator::make($request->all(), [
            'min_quantity' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
        ])->validate();

        DB::transaction(function () use ($stockLevel, $validated): void {
            $stockLevel->update(['min_quantity' => $validated['min_quantity'] ?? null]);
            app(StockService::class)->syncAlertForLevel($stockLevel);
        });

        return back()->with('success', 'Branch stock minimum updated.');
    }
}

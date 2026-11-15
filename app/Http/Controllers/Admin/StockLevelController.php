<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockLevel;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockUnit;
use Carbon\Carbon;
use App\Services\OpeningStockRequestService;

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
            ->when(request('location_type'), fn ($query, $type) => $query->where('location_type', $type))
            ->when(request('location_id'), fn ($query, $id) => $query->where('location_id', $id))
            ->when(request('product'), fn ($query, $term) => $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))
            ->when(request('below_minimum'), fn ($query) => $query->whereRaw("CAST(quantity AS REAL) <= COALESCE(CAST(min_quantity AS REAL), (SELECT reorder_level FROM products WHERE products.id = stock_levels.product_id))"))
            ->paginate(25)->withQueryString();

        return view('admin.stock.levels', [
            'levels' => $levels,
            'branches' => Branch::query()->when(($ids = StockAccess::readableLocationIds($user, 'branch')) !== null, fn ($q) => $q->whereIn('id', $ids))->get(),
            'warehouses' => Warehouse::query()->when(($ids = StockAccess::readableLocationIds($user, 'warehouse')) !== null, fn ($q) => $q->whereIn('id', $ids))->get(),
        ]);
    }

    public function movements()
    {
        $user = auth()->user();
        $movements = StockMovement::with(['product', 'performer'])
            ->where(function ($query) use ($user) {
                foreach (['branch', 'warehouse'] as $type) {
                    $ids = StockAccess::readableLocationIds($user, $type);
                    $query->orWhere(fn ($q) => $q->where('location_type', $type)->when($ids !== null, fn ($q) => $q->whereIn('location_id', $ids)));
                }
            })
            ->when(request('product'), fn ($q, $term) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))
            ->when(request('location_type'), fn ($q, $type) => $q->where('location_type', $type))
            ->when(request('movement_type'), fn ($q, $type) => $q->where('movement_type', $type))
            ->when(request('from'), fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))
            ->when(request('to'), fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date))
            ->latest('occurred_at')->paginate(25)->withQueryString();

        return view('admin.stock.movements', compact('movements'));
    }

    public function opening()
    {
        $user = auth()->user();

        return view('admin.stock.opening', [
            'products' => Product::where('track_stock', true)->where('status', 'active')->get(),
            'branches' => Branch::when(($ids = $user->accessibleBranchIds()) !== null, fn ($q) => $q->whereIn('id', $ids))->get(),
            'warehouses' => Warehouse::when(($ids = $user->accessibleWarehouseIds()) !== null, fn ($q) => $q->whereIn('id', $ids))->get(),
        ]);
    }

    public function storeOpening(Request $request)
    {
        $validated = $request->validate([
            'location_type' => ['required', 'in:branch,warehouse'],
            'location_id' => ['required', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'lines.*.serial_numbers_text' => ['nullable', 'string'],
            'lines.*.use_system_codes' => ['nullable', 'boolean'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_unless(StockAccess::canWrite($request->user(), $validated['location_type'], (int) $validated['location_id']), 403);
        try {
            $lines = array_map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'] ?? 0,
                'serial_numbers' => preg_split('/\R/', (string) ($line['serial_numbers_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY),
                'use_system_codes' => (bool) ($line['use_system_codes'] ?? false),
            ], $validated['lines']);
            $newRequest = app(OpeningStockRequestService::class)->submit($request->user(), $validated + ['lines' => $lines]);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.stock.requests.show', $newRequest)->with('success', 'Opening stock request submitted for approval.');
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

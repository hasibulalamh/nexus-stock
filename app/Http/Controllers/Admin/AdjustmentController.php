<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockUnit;
use App\Models\Warehouse;
use App\Services\AdjustmentService;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdjustmentController
{
    public function __construct(private readonly AdjustmentService $service)
    {
    }

    public function index(Request $request)
    {
        $adjustments = $this->service->visibleTo($request->user())
            ->with(['requester', 'reviewer'])
            ->withCount('lines')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.adjustments.index', ['adjustments' => $adjustments]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $type = $request->string('location_type')->toString() === 'branch' ? 'branch' : 'warehouse';
        $locationId = $request->integer('location_id');
        $availableUnits = $locationId > 0
            ? StockUnit::query()->with('product')->inStockAt($type, $locationId)
                ->whereHas('product', fn ($q) => $q->where('tracking_type', 'serial')->where('status', 'active'))
                ->orderBy('product_id')->orderBy('id')->limit(500)->get()
            : collect();

        return view('admin.adjustments.create', [
            'locationType' => $type,
            'locationId' => $locationId,
            'warehouses' => Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get(),
            'branches' => Branch::query()->where('status', 'active')
                ->when(($ids = $user->accessibleBranchIds()) !== null, fn ($q) => $q->whereIn('id', $ids))
                ->orderBy('name')->get(),
            'products' => Product::query()->where('track_stock', true)->where('status', 'active')->orderBy('name')->get(),
            'availableUnits' => $availableUnits,
            'reasons' => StockAdjustment::REASONS,
            'oldLines' => old('lines', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_type' => ['required', Rule::in(['branch', 'warehouse'])],
            'location_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(StockAdjustment::REASONS)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.delta' => ['required', 'numeric', 'decimal:0,3'],
            'lines.*.stock_unit_ids' => ['nullable', 'array'],
            'lines.*.stock_unit_ids.*' => ['integer'],
            'lines.*.serial_numbers_text' => ['nullable', 'string'],
        ]);
        $lines = [];
        foreach ($validated['lines'] as $line) {
            $lines[] = [
                'product_id' => $line['product_id'],
                'delta' => $line['delta'],
                'stock_unit_ids' => $line['stock_unit_ids'] ?? [],
                'serial_numbers' => preg_split('/\R/', (string) ($line['serial_numbers_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            ];
        }
        try {
            $adjustment = $this->service->submit($request->user(), $validated + ['lines' => $lines]);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.adjustments.show', $adjustment)->with('success', 'Adjustment submitted for approval.');
    }

    public function show(Request $request, StockAdjustment $stockAdjustment)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockAdjustment->id)->exists(), 403);

        return view('admin.adjustments.show', [
            'adjustment' => $stockAdjustment->load(['lines.product', 'requester', 'reviewer', 'count']),
        ]);
    }

    public function approve(Request $request, StockAdjustment $stockAdjustment)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockAdjustment->id)->exists(), 403);
        try {
            $this->service->approve($request->user(), $stockAdjustment);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Adjustment approved and posted.');
    }

    public function reject(Request $request, StockAdjustment $stockAdjustment)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockAdjustment->id)->exists(), 403);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        try {
            $this->service->reject($request->user(), $stockAdjustment, $validated['rejection_reason']);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Adjustment rejected.');
    }

    public function cancel(Request $request, StockAdjustment $stockAdjustment)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockAdjustment->id)->exists(), 403);
        try {
            $this->service->cancel($request->user(), $stockAdjustment);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Adjustment cancelled.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class PurchaseOrderController
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function index(Request $request)
    {
        $orders = $this->service->visibleTo($request->user())
            ->with(['supplier', 'warehouse', 'creator'])
            ->withCount('lines')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.purchases.index', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(),
            'warehouses' => Warehouse::query()->accessibleTo($request->user())->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('admin.purchases.create', [
            'order' => new PurchaseOrder(['expected_date' => now()->addWeek()]),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('track_stock', true)->where('status', 'active')->orderBy('name')->get(),
            'oldLines' => old('lines', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        try {
            $order = $this->service->create($request->user(), $validated);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.purchases.show', $order)->with('success', 'Purchase order created.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);

        return view('admin.purchases.show', [
            'order' => $purchaseOrder->load(['lines.product', 'supplier', 'warehouse', 'creator', 'approver', 'receipts']),
        ]);
    }

    public function edit(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);
        abort_unless($purchaseOrder->status === 'draft', 403);
        $user = $request->user();

        return view('admin.purchases.create', [
            'order' => $purchaseOrder->load('lines.product'),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('track_stock', true)->where('status', 'active')->orderBy('name')->get(),
            'oldLines' => old('lines', []),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);
        $validated = $request->validate($this->rules(false));
        try {
            $this->service->update($request->user(), $purchaseOrder, $validated);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.purchases.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);
        try {
            $this->service->approve($request->user(), $purchaseOrder);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return back()->with('success', 'Purchase order approved and ordered.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->service->cancel($request->user(), $purchaseOrder, $validated['reason'] ?? null);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return back()->with('success', 'Purchase order cancelled.');
    }

    public function print(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($purchaseOrder->id)->exists(), 403);

        return view('admin.purchases.print', [
            'order' => $purchaseOrder->load(['lines.product', 'supplier', 'warehouse', 'creator', 'approver']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $withWarehouse = true): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'warehouse_id' => [$withWarehouse ? 'required' : 'nullable', 'integer', 'exists:warehouses,id'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.ordered_qty' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }
}

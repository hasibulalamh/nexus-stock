<?php

namespace App\Http\Controllers\Admin;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class GoodsReceiptController
{
    public function __construct(private readonly GoodsReceiptService $service)
    {
    }

    public function index(Request $request)
    {
        $receipts = $this->service->visibleTo($request->user())
            ->with(['supplier', 'warehouse', 'purchaseOrder'])
            ->withCount('lines')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('received_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('received_at', '<=', $request->date('to')))
            ->latest('received_at')->paginate(25)->withQueryString();

        return view('admin.receiving.index', [
            'receipts' => $receipts,
            'warehouses' => Warehouse::query()->accessibleTo($request->user())->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $orderId = $request->integer('purchase_order_id');
        $orders = PurchaseOrder::query()->visibleTo($user)
            ->whereIn('status', ['ordered', 'partial'])
            ->with(['supplier', 'warehouse', 'lines.product'])
            ->latest()->get();
        $order = $orderId > 0 ? $orders->firstWhere('id', $orderId) : null;
        if ($orderId > 0 && ! $order) {
            abort(403);
        }

        return view('admin.receiving.create', [
            'order' => $order,
            'orders' => $orders,
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('track_stock', true)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.accepted_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'lines.*.damaged_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'lines.*.short_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:1000'],
            'lines.*.serial_numbers_text' => ['nullable', 'string'],
        ]);
        $lines = [];
        foreach ($validated['lines'] as $line) {
            $serials = preg_split('/\R/', (string) ($line['serial_numbers_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $lines[] = [
                'product_id' => $line['product_id'],
                'accepted_qty' => $line['accepted_qty'] ?? 0,
                'damaged_qty' => $line['damaged_qty'] ?? 0,
                'short_qty' => $line['short_qty'] ?? 0,
                'unit_cost' => $line['unit_cost'] ?? 0,
                'reason' => $line['reason'] ?? null,
                'serial_numbers' => array_map('trim', $serials),
            ];
        }
        try {
            $receipt = $this->service->create($request->user(), $validated + ['lines' => $lines]);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['receipt' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.receiving.show', $receipt)->with('success', 'Goods receipt saved as a draft. Post it to update stock.');
    }

    public function show(Request $request, GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($goodsReceipt->id)->exists(), 403);

        return view('admin.receiving.show', [
            'receipt' => $goodsReceipt->load(['lines.product', 'supplier', 'warehouse', 'purchaseOrder', 'receiver']),
        ]);
    }

    public function post(Request $request, GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($goodsReceipt->id)->exists(), 403);
        try {
            $this->service->post($request->user(), $goodsReceipt);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['receipt' => $exception->getMessage()]);
        }

        return redirect()->route('admin.receiving.show', $goodsReceipt)->with('success', 'Goods receipt posted to stock.');
    }

    public function supplierReturn(Request $request, GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($goodsReceipt->id)->exists(), 403);
        $validated = $request->validate([
            'line_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        try {
            $this->service->supplierReturn(
                $request->user(), $goodsReceipt, (int) $validated['line_id'], $validated['quantity'], $validated['reason'],
            );
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['receipt' => $exception->getMessage()]);
        }

        return back()->with('success', 'Supplier return recorded.');
    }

    public function print(Request $request, GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($goodsReceipt->id)->exists(), 403);

        return view('admin.receiving.print', [
            'receipt' => $goodsReceipt->load(['lines.product', 'supplier', 'warehouse', 'purchaseOrder', 'receiver']),
        ]);
    }
}

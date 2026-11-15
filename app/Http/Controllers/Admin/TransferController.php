<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockUnit;
use App\Models\Transfer;
use App\Models\TransferDiscrepancy;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Services\TransferService;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use RuntimeException;

class TransferController
{
    public function __construct(private readonly TransferService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $transfers = $this->service->visibleTo($user)
            ->with(['items.product', 'requester', 'sender', 'receiver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('to_type', 'branch')->where('to_id', $request->integer('branch_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('q'), fn ($q) => $q->where('transfer_no', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(25)->withQueryString();

        return view('admin.transfers.index', [
            'transfers' => $transfers,
            'branches' => Branch::query()->accessibleTo($user)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $type = $request->string('type')->toString() === Transfer::RETURN ? Transfer::RETURN : Transfer::TRANSFER;
        $fromId = (int) old('from_id', $request->input('from_id', 0));

        $warehouses = Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get();
        $branchIds = $type === Transfer::RETURN
            ? $user->accessibleBranchIds()
            : StockAccess::readableLocationIds($user, 'branch');
        $branches = Branch::query()->where('status', 'active')
            ->when($branchIds !== null, fn ($q) => $q->whereIn('id', $branchIds))
            ->with('warehouse')->orderBy('name')->get();

        $fromType = $type === Transfer::RETURN ? 'branch' : 'warehouse';
        $availableUnits = $fromId > 0
            ? StockUnit::query()->with('product')->inStockAt($fromType, $fromId)
                ->whereHas('product', fn ($q) => $q->where('tracking_type', 'serial')->where('status', 'active'))
                ->orderBy('product_id')->orderBy('id')->limit(500)->get()
            : collect();

        return view('admin.transfers.create', [
            'type' => $type,
            'warehouses' => $warehouses,
            'branches' => $branches,
            'products' => Product::query()->where('track_stock', true)->where('status', 'active')->orderBy('name')->get(),
            'availableUnits' => $availableUnits,
            'oldLines' => old('lines', []),
            'prefill' => [
                'from_id' => $fromId,
                'to_id' => (int) old('to_id', $request->input('to_id', 0)),
                'product_id' => (int) $request->input('product_id', 0),
                'stock_alert_id' => $request->input('stock_alert_id'),
            ],
            'returnReasons' => Transfer::RETURN_REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([Transfer::TRANSFER, Transfer::RETURN])],
            'from_id' => ['required', 'integer'],
            'to_id' => ['nullable', 'integer'],
            'reason' => ['nullable', Rule::in(Transfer::RETURN_REASONS)],
            'stock_alert_id' => ['nullable', 'integer', 'exists:stock_alerts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.requested_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'lines.*.unit_ids' => ['nullable', 'array'],
            'lines.*.unit_ids.*' => ['integer'],
        ]);

        try {
            $transfer = $this->service->create($request->user(), $validated);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.transfers.show', $transfer)->with('success', 'Transfer saved as a draft.');
    }

    public function show(Request $request, Transfer $transfer)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($transfer->id)->exists(), 403);

        return view('admin.transfers.show', [
            'transfer' => $transfer->load([
                'items.product', 'items.stockUnits', 'items.discrepancies.resolver',
                'requester', 'sender', 'receiver', 'stockAlert',
            ]),
        ]);
    }

    public function send(Request $request, Transfer $transfer)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($transfer->id)->exists(), 403);
        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.sent_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.unit_ids' => ['nullable', 'array'],
            'items.*.unit_ids.*' => ['integer'],
        ]);
        $sent = [];
        foreach ($validated['items'] ?? [] as $itemId => $input) {
            $sent[(int) $itemId] = $input;
        }
        try {
            $this->service->send($request->user(), $transfer, $sent);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.transfers.show', $transfer)->with('success', 'Transfer sent.');
    }

    public function receive(Request $request, Transfer $transfer)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($transfer->id)->exists(), 403);
        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.received_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.damaged_qty' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.reason' => ['nullable', 'string', 'max:1000'],
            'items.*.unit_ids' => ['nullable', 'array'],
            'items.*.unit_ids.*' => ['integer'],
            'items.*.damaged_unit_ids' => ['nullable', 'array'],
            'items.*.damaged_unit_ids.*' => ['integer'],
        ]);
        $received = [];
        foreach ($validated['items'] ?? [] as $itemId => $input) {
            $received[(int) $itemId] = $input;
        }
        try {
            $this->service->receiveWithQuantities($request->user(), $transfer, $received);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.transfers.show', $transfer)->with('success', 'Transfer received.');
    }

    public function cancel(Request $request, Transfer $transfer)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($transfer->id)->exists(), 403);
        try {
            $this->service->cancel($request->user(), $transfer);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.transfers.show', $transfer)->with('success', 'Transfer cancelled.');
    }

    public function resolveDiscrepancy(Request $request, TransferDiscrepancy $transferDiscrepancy)
    {
        $transferId = (int) $transferDiscrepancy->item()->value('transfer_id');
        abort_unless($this->service->visibleTo($request->user())->whereKey($transferId)->exists(), 403);
        $validated = $request->validate([
            'resolution' => ['required', Rule::in(TransferDiscrepancy::RESOLUTIONS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $this->service->resolveDiscrepancy(
                $request->user(), $transferDiscrepancy, $validated['resolution'], $validated['note'] ?? null,
            );
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['discrepancy' => $exception->getMessage()]);
        }

        return back()->with('success', 'Discrepancy resolved.');
    }

    public function deliveryNote(Request $request, Transfer $transfer)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($transfer->id)->exists(), 403);
        $generator = new BarcodeGeneratorSVG();

        return view('admin.transfers.delivery-note', [
            'transfer' => $transfer->load(['items.product', 'items.stockUnits', 'requester', 'sender', 'receiver']),
            'svg' => $generator->getBarcode($transfer->transfer_no, BarcodeGenerator::TYPE_CODE_128),
        ]);
    }

    public function backorders(Request $request)
    {
        $user = $request->user();
        $items = TransferItem::query()->with(['transfer', 'product'])
            ->whereColumn('sent_qty', '<', 'requested_qty')
            ->whereHas('transfer', fn ($q) => $q->whereIn('status', ['draft', 'sent'])->visibleTo($user))
            ->when($request->filled('product'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->string('product').'%')->orWhere('sku', 'like', '%'.$request->string('product').'%')))
            ->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.transfers.backorders', ['items' => $items]);
    }
}

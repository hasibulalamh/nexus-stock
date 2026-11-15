<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\StockCount;
use App\Models\Warehouse;
use App\Services\StockCountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use RuntimeException;

class StockCountController
{
    public function __construct(private readonly StockCountService $service)
    {
    }

    public function index(Request $request)
    {
        $counts = $this->service->visibleTo($request->user())
            ->with(['requester', 'reviewer'])
            ->withCount('lines')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.counts.index', ['counts' => $counts]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('admin.counts.create', [
            'warehouses' => Warehouse::query()->accessibleTo($user)->where('status', 'active')->orderBy('name')->get(),
            'branches' => Branch::query()->where('status', 'active')
                ->when(($ids = $user->accessibleBranchIds()) !== null, fn ($q) => $q->whereIn('id', $ids))
                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_type' => ['required', Rule::in(['branch', 'warehouse'])],
            'location_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        try {
            $count = $this->service->start($request->user(), $validated);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['count' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.counts.show', $count)->with('success', 'Stock count started. Enter the counted quantities.');
    }

    public function show(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);

        return view('admin.counts.show', [
            'count' => $stockCount->load(['lines.product', 'adjustment', 'requester', 'reviewer']),
        ]);
    }

    public function submit(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);
        $validated = $request->validate([
            'counts' => ['required', 'array'],
            'counts.*' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
        ]);
        try {
            $this->service->submit($request->user(), $stockCount, $validated['counts']);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['count' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.counts.show', $stockCount)->with('success', 'Stock count submitted for approval.');
    }

    public function approve(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);
        try {
            $this->service->approve($request->user(), $stockCount);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['count' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stock count approved and variances posted.');
    }

    public function acknowledgeDrift(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);
        try {
            $this->service->acknowledgeDrift($request->user(), $stockCount);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['count' => $exception->getMessage()]);
        }

        return back()->with('success', 'Drift acknowledged. Approval will use the snapshot variances.');
    }

    public function cancel(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);
        try {
            $this->service->cancel($request->user(), $stockCount);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['count' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stock count cancelled.');
    }

    public function print(Request $request, StockCount $stockCount)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($stockCount->id)->exists(), 403);
        $generator = new BarcodeGeneratorSVG();

        return view('admin.counts.print', [
            'count' => $stockCount->load(['lines.product', 'requester']),
            'barcodes' => $stockCount->lines->mapWithKeys(fn ($line) => [
                $line->id => $generator->getBarcode(
                    (string) ($line->product?->barcode ?: $line->product?->sku ?: 'P'.$line->product_id),
                    BarcodeGenerator::TYPE_CODE_128,
                ),
            ]),
        ]);
    }
}

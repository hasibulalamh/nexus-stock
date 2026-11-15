<?php

namespace App\Http\Controllers\Admin;

use App\Models\OpeningStockRequest;
use App\Services\OpeningStockRequestService;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use RuntimeException;

class OpeningStockRequestController
{
    public function __construct(private readonly OpeningStockRequestService $service)
    {
    }

    public function index(Request $request)
    {
        $query = $this->service->visibleTo($request->user());
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('occurred_at', $request->date('date')));

        return view('admin.stock.requests.index', ['requests' => $query->latest()->paginate(25)->withQueryString()]);
    }

    public function show(Request $request, OpeningStockRequest $openingStockRequest)
    {
        abort_unless($this->service->visibleTo($request->user())->whereKey($openingStockRequest->id)->exists(), 403);
        return view('admin.stock.requests.show', ['requestModel' => $openingStockRequest->load(['lines.product', 'requester', 'reviewer', 'location'])]);
    }

    public function approve(Request $request, OpeningStockRequest $openingStockRequest)
    {
        try {
            $this->service->approve($request->user(), $openingStockRequest);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }
        return redirect()->route('admin.stock.requests.show', $openingStockRequest)->with('success', 'Opening stock request approved.');
    }

    public function reject(Request $request, OpeningStockRequest $openingStockRequest)
    {
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        try {
            $this->service->reject($request->user(), $openingStockRequest, $validated['rejection_reason']);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }
        return redirect()->route('admin.stock.requests.show', $openingStockRequest)->with('success', 'Opening stock request rejected.');
    }

    public function cancel(Request $request, OpeningStockRequest $openingStockRequest)
    {
        try {
            $this->service->cancel($request->user(), $openingStockRequest);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }
        return back()->with('success', 'Opening stock request cancelled.');
    }

    public function resubmit(Request $request, OpeningStockRequest $openingStockRequest)
    {
        try {
            $newRequest = $this->service->resubmit($request->user(), $openingStockRequest);
        } catch (RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }
        return redirect()->route('admin.stock.requests.show', $newRequest)->with('success', 'Opening stock request resubmitted.');
    }
}

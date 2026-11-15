<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\OpeningStockRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockAlert;
use App\Models\StockCount;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\Transfer;
use App\Models\TransferDiscrepancy;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Support\Money;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController
{
    private const REPORTS = [
        ['key' => 'valuation', 'title' => 'Stock valuation by location', 'description' => 'Quantity on hand at product cost per location.'],
        ['key' => 'low-stock', 'title' => 'Low stock / below minimum', 'description' => 'Branch products at or below their minimum.'],
        ['key' => 'movements', 'title' => 'Stock movement summary', 'description' => 'In/out totals per movement type.'],
        ['key' => 'transfers', 'title' => 'Transfer report', 'description' => 'Requested to sent to received lead times.'],
        ['key' => 'discrepancies', 'title' => 'Discrepancy report', 'description' => 'Short, damaged and extra receipts.'],
        ['key' => 'purchases', 'title' => 'Supplier purchases and receipts', 'description' => 'Purchase order values and receipt counts.'],
        ['key' => 'adjustments', 'title' => 'Adjustments and damage', 'description' => 'Approved adjustments by reason.'],
        ['key' => 'backorders', 'title' => 'Backorder aging', 'description' => 'Unsent transfer quantities and their age.'],
        ['key' => 'stock-on-hand', 'title' => 'Stock on hand by product', 'description' => 'Total quantity and in-stock serial counts.'],
    ];

    public function index()
    {
        return view('admin.reports.index', ['reports' => self::REPORTS]);
    }

    public function valuation(Request $request)
    {
        $query = $this->scopedLevels($request)
            ->with('product')
            ->when($request->filled('product'), fn ($q) => $q->whereHas('product', fn ($p) => $this->productFilter($p, $request)))
            ->orderBy('location_type')->orderBy('location_id')->orderBy('product_id');

        $summary = ['Total stock value' => Money::format((clone $query)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->sum(DB::raw('stock_levels.quantity * products.cost_price')))];

        return $this->render($request, 'Stock valuation by location', $query, function (StockLevel $level) {
            $name = $this->locationName($level->location_type, (int) $level->location_id);

            return [
                $name,
                $level->product?->name ?? 'Deleted product',
                $level->product?->sku ?? '—',
                (string) $level->quantity,
                Money::format($level->product?->cost_price ?? 0),
                Money::format((float) $level->quantity * (float) ($level->product?->cost_price ?? 0)),
            ];
        }, [
            'location' => 'Location', 'product' => 'Product', 'sku' => 'SKU',
            'quantity' => 'Quantity', 'cost' => 'Unit cost', 'value' => 'Value',
        ], $summary);
    }

    public function lowStock(Request $request)
    {
        $query = $this->scopedLevels($request)
            ->with('product')
            ->where('location_type', 'branch')
            ->whereRaw('COALESCE(min_quantity, (SELECT reorder_level FROM products WHERE products.id = stock_levels.product_id)) > 0')
            ->whereRaw('CAST(quantity AS DECIMAL(14,3)) <= COALESCE(CAST(min_quantity AS DECIMAL(14,3)), (SELECT reorder_level FROM products WHERE products.id = stock_levels.product_id))')
            ->when($request->filled('product'), fn ($q) => $q->whereHas('product', fn ($p) => $this->productFilter($p, $request)))
            ->orderBy('product_id');

        return $this->render($request, 'Low stock / below minimum', $query, function (StockLevel $level) {
            $minimum = (float) ($level->min_quantity ?? $level->product?->reorder_level ?? 0);

            return [
                $this->locationName('branch', (int) $level->location_id),
                $level->product?->name ?? 'Deleted product',
                $level->product?->sku ?? '—',
                (string) $level->quantity,
                number_format($minimum, 3, '.', ''),
                number_format(max(0, $minimum - (float) $level->quantity), 3, '.', ''),
            ];
        }, [
            'branch' => 'Branch', 'product' => 'Product', 'sku' => 'SKU',
            'quantity' => 'On hand', 'minimum' => 'Minimum', 'shortfall' => 'Shortfall',
        ]);
    }

    public function movements(Request $request)
    {
        $branchIds = StockAccess::readableLocationIds($request->user(), 'branch');
        $warehouseIds = StockAccess::readableLocationIds($request->user(), 'warehouse');
        $query = DB::table('stock_movements')
            ->selectRaw('movement_type, COUNT(*) as movements, SUM(CASE WHEN quantity_delta > 0 THEN quantity_delta ELSE 0 END) as qty_in, SUM(CASE WHEN quantity_delta < 0 THEN -quantity_delta ELSE 0 END) as qty_out, SUM(quantity_delta) as net')
            ->groupBy('movement_type')->orderBy('movement_type')
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->date('to')))
            ->when($branchIds !== null || $warehouseIds !== null, function ($q) use ($branchIds, $warehouseIds): void {
                $q->where(function ($scope) use ($branchIds, $warehouseIds): void {
                    $scope->whereRaw('1 = 0');
                    if ($branchIds === null) {
                        $scope->orWhere('location_type', 'branch');
                    } elseif ($branchIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'branch')->whereIn('location_id', $branchIds));
                    }
                    if ($warehouseIds === null) {
                        $scope->orWhere('location_type', 'warehouse');
                    } elseif ($warehouseIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
                    }
                });
            });

        return $this->render($request, 'Stock movement summary', $query, fn ($row) => [
            ucfirst(str_replace('_', ' ', (string) $row->movement_type)),
            (string) $row->movements,
            (string) $row->qty_in,
            (string) $row->qty_out,
            (string) $row->net,
        ], [
            'type' => 'Movement type', 'movements' => 'Rows', 'in' => 'Quantity in',
            'out' => 'Quantity out', 'net' => 'Net',
        ]);
    }

    public function transfers(Request $request)
    {
        $query = Transfer::query()->visibleTo($request->user())
            ->with(['fromBranch', 'fromWarehouse', 'toBranch', 'toWarehouse', 'requester'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id');

        return $this->render($request, 'Transfer report', $query, function (Transfer $transfer) {
            $leadTime = $transfer->sent_at && $transfer->received_at
                ? number_format($transfer->sent_at->diffInMinutes($transfer->received_at) / 60, 1, '.', '')
                : '—';

            return [
                $transfer->transfer_no,
                ucfirst($transfer->type),
                $transfer->fromLocationName(),
                $transfer->toLocationName(),
                ucfirst($transfer->status),
                $transfer->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
                $transfer->sent_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
                $transfer->received_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
                $leadTime,
            ];
        }, [
            'transfer' => 'Transfer', 'type' => 'Type', 'from' => 'From', 'to' => 'To',
            'status' => 'Status', 'created' => 'Created', 'sent' => 'Sent',
            'received' => 'Received', 'lead' => 'Lead time (hours)',
        ]);
    }

    public function discrepancies(Request $request)
    {
        $query = TransferDiscrepancy::query()
            ->with(['item.product', 'item.transfer', 'resolver'])
            ->whereHas('item.transfer', fn ($q) => $q->visibleTo($request->user()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id');

        return $this->render($request, 'Discrepancy report', $query, fn (TransferDiscrepancy $discrepancy) => [
            $discrepancy->item?->transfer?->transfer_no ?? '—',
            $discrepancy->item?->product?->name ?? '—',
            ucfirst($discrepancy->type),
            (string) $discrepancy->qty,
            ucfirst($discrepancy->status),
            $discrepancy->resolution ? ucfirst(str_replace('_', ' ', $discrepancy->resolution)) : '—',
            $discrepancy->resolver?->full_name ?? '—',
            $discrepancy->resolved_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
        ], [
            'transfer' => 'Transfer', 'product' => 'Product', 'type' => 'Type',
            'qty' => 'Quantity', 'status' => 'Status', 'resolution' => 'Resolution',
            'resolver' => 'Resolved by', 'resolved' => 'Resolved at',
        ]);
    }

    public function purchases(Request $request)
    {
        $query = PurchaseOrder::query()->visibleTo($request->user())
            ->with(['supplier', 'warehouse'])
            ->withCount('receipts')
            ->select('purchase_orders.*')
            ->selectRaw('(SELECT COALESCE(SUM(ordered_qty * unit_cost), 0) FROM purchase_order_lines WHERE purchase_order_id = purchase_orders.id) as ordered_value')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id');

        $summary = [
            'Orders' => (string) (clone $query)->count(),
            'Ordered value' => Money::format((clone $query)->get()->sum('ordered_value')),
            'Receipts' => (string) GoodsReceipt::query()->visibleTo($request->user())->count(),
        ];

        return $this->render($request, 'Supplier purchases and receipts', $query, fn (PurchaseOrder $order) => [
            $order->po_no,
            $order->supplier?->name ?? '—',
            $order->warehouse?->name ?? '—',
            ucfirst($order->status),
            (string) $order->receipts_count,
            Money::format($order->ordered_value ?? 0),
            $order->expected_date?->format('Y-m-d') ?? '—',
            $order->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
        ], [
            'po' => 'PO', 'supplier' => 'Supplier', 'warehouse' => 'Warehouse',
            'status' => 'Status', 'receipts' => 'Receipts', 'value' => 'Ordered value',
            'expected' => 'Expected', 'created' => 'Created',
        ], $summary);
    }

    public function adjustments(Request $request)
    {
        $query = StockAdjustment::query()->visibleTo($request->user())
            ->where('status', 'approved')
            ->with(['lines', 'reviewer'])
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('reviewed_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('reviewed_at', '<=', $request->date('to')))
            ->latest('reviewed_at');

        return $this->render($request, 'Adjustments and damage', $query, fn (StockAdjustment $adjustment) => [
            $adjustment->adj_no,
            $adjustment->locationName(),
            ucfirst(str_replace('_', ' ', $adjustment->reason)),
            (string) $adjustment->lines->count(),
            number_format($adjustment->lines->sum(fn ($line) => (float) $line->delta), 3, '.', ''),
            $adjustment->reviewer?->full_name ?? '—',
            $adjustment->reviewed_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') ?? '—',
        ], [
            'adjustment' => 'Adjustment', 'location' => 'Location', 'reason' => 'Reason',
            'lines' => 'Lines', 'delta' => 'Total delta', 'reviewer' => 'Reviewed by', 'reviewed' => 'Reviewed at',
        ]);
    }

    public function backorders(Request $request)
    {
        $query = TransferItem::query()
            ->with(['transfer.fromBranch', 'transfer.fromWarehouse', 'transfer.toBranch', 'transfer.toWarehouse', 'product'])
            ->whereColumn('sent_qty', '<', 'requested_qty')
            ->whereHas('transfer', fn ($q) => $q->whereIn('status', ['draft', 'sent'])->visibleTo($request->user()))
            ->when($request->filled('product'), fn ($q) => $q->whereHas('product', fn ($p) => $this->productFilter($p, $request)))
            ->latest('id');

        return $this->render($request, 'Backorder aging', $query, function (TransferItem $item) {
            $age = $item->transfer?->created_at
                ? (int) $item->transfer->created_at->diffInDays(now())
                : 0;

            return [
                $item->transfer?->transfer_no ?? '—',
                $item->product?->name ?? '—',
                $item->transfer?->toLocationName() ?? '—',
                (string) $item->requested_qty,
                (string) $item->sent_qty,
                $item->backorderQty(),
                (string) $age,
            ];
        }, [
            'transfer' => 'Transfer', 'product' => 'Product', 'destination' => 'Destination',
            'requested' => 'Requested', 'sent' => 'Sent', 'backorder' => 'Backorder',
            'age' => 'Age (days)',
        ]);
    }

    public function stockOnHand(Request $request)
    {
        $branchIds = StockAccess::readableLocationIds($request->user(), 'branch');
        $warehouseIds = StockAccess::readableLocationIds($request->user(), 'warehouse');
        $query = StockLevel::query()
            ->selectRaw('product_id, SUM(quantity) as total_qty, COUNT(*) as location_count')
            ->groupBy('product_id')
            ->with('product')
            ->when($request->filled('product'), fn ($q) => $q->whereHas('product', fn ($p) => $this->productFilter($p, $request)))
            ->when($branchIds !== null || $warehouseIds !== null, function ($q) use ($branchIds, $warehouseIds): void {
                $q->where(function ($scope) use ($branchIds, $warehouseIds): void {
                    $scope->whereRaw('1 = 0');
                    if ($branchIds === null) {
                        $scope->orWhere('location_type', 'branch');
                    } elseif ($branchIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'branch')->whereIn('location_id', $branchIds));
                    }
                    if ($warehouseIds === null) {
                        $scope->orWhere('location_type', 'warehouse');
                    } elseif ($warehouseIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
                    }
                });
            })
            ->orderByDesc('total_qty');

        $serialCounts = StockUnit::query()->where('status', 'in_stock')
            ->when($branchIds !== null || $warehouseIds !== null, function ($q) use ($branchIds, $warehouseIds): void {
                $q->where(function ($scope) use ($branchIds, $warehouseIds): void {
                    $scope->whereRaw('1 = 0');
                    if ($branchIds === null) {
                        $scope->orWhere('location_type', 'branch');
                    } elseif ($branchIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'branch')->whereIn('location_id', $branchIds));
                    }
                    if ($warehouseIds === null) {
                        $scope->orWhere('location_type', 'warehouse');
                    } elseif ($warehouseIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
                    }
                });
            })
            ->selectRaw('product_id, COUNT(*) as unit_count')->groupBy('product_id')->pluck('unit_count', 'product_id');

        return $this->render($request, 'Stock on hand by product', $query, fn (StockLevel $row) => [
            $row->product?->name ?? 'Deleted product',
            $row->product?->sku ?? '—',
            (string) $row->total_qty,
            (string) $row->location_count,
            (string) ($serialCounts[$row->product_id] ?? 0),
            Money::format((float) $row->total_qty * (float) ($row->product?->cost_price ?? 0)),
        ], [
            'product' => 'Product', 'sku' => 'SKU', 'total' => 'Total quantity',
            'locations' => 'Locations', 'serials' => 'Serial units in stock', 'value' => 'Value',
        ]);
    }

    public function kpi(Request $request)
    {
        $user = $request->user();
        $days = in_array((int) $request->input('days', 90), [30, 90, 365], true) ? (int) $request->input('days', 90) : 90;
        $start = now()->subDays($days);
        $branchIds = StockAccess::readableLocationIds($user, 'branch');
        $warehouseIds = StockAccess::readableLocationIds($user, 'warehouse');
        $canViewAllAlerts = $user->hasAccessAll() || $user->can('stock.alerts.view_all');

        $openAlerts = StockAlert::query()->where('status', 'open')
            ->when(! $canViewAllAlerts, fn ($q) => $q->where(function ($scope) use ($user): void {
                $scope->whereIn('branch_id', $user->accessibleBranchIds() ?: [])
                    ->orWhereIn('warehouse_id', $user->accessibleWarehouseIds() ?: []);
            }))->count();

        $pendingOpening = OpeningStockRequest::query()->where('status', 'pending')
            ->when($branchIds !== null || $warehouseIds !== null, function ($q) use ($branchIds, $warehouseIds): void {
                $q->where(function ($scope) use ($branchIds, $warehouseIds): void {
                    $scope->whereRaw('1 = 0');
                    if ($branchIds === null) {
                        $scope->orWhere('location_type', 'branch');
                    } elseif ($branchIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'branch')->whereIn('location_id', $branchIds));
                    }
                    if ($warehouseIds === null) {
                        $scope->orWhere('location_type', 'warehouse');
                    } elseif ($warehouseIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
                    }
                });
            })->count();

        $pendingAdjustments = StockAdjustment::query()->visibleTo($user)->where('status', 'pending')->count();
        $pendingCounts = StockCount::query()->visibleTo($user)->where('status', 'submitted')->count();

        $transferTotals = TransferItem::query()
            ->whereHas('transfer', fn ($q) => $q->visibleTo($user)->where('created_at', '>=', $start))
            ->selectRaw('COALESCE(SUM(requested_qty), 0) as requested, COALESCE(SUM(sent_qty), 0) as sent')
            ->first();
        $requestedQty = (float) ($transferTotals->requested ?? 0);
        $sentQty = (float) ($transferTotals->sent ?? 0);
        $fillRate = $requestedQty > 0 ? ($sentQty / $requestedQty) * 100 : null;

        $service = DB::table('stock_levels')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('stock_levels.location_type', 'branch')
            ->when($branchIds !== null, fn ($q) => $q->whereIn('stock_levels.location_id', $branchIds))
            ->whereRaw('COALESCE(stock_levels.min_quantity, products.reorder_level) > 0')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN stock_levels.quantity >= COALESCE(stock_levels.min_quantity, products.reorder_level) THEN 1 ELSE 0 END) as ok')
            ->first();
        $serviceLevel = ($service->total ?? 0) > 0 ? ((float) $service->ok / (float) $service->total) * 100 : null;

        $alerts = StockAlert::query()->whereNotNull('resolved_at')->where('opened_at', '>=', $start)
            ->when(! $canViewAllAlerts, fn ($q) => $q->where(function ($scope) use ($user): void {
                $scope->whereIn('branch_id', $user->accessibleBranchIds() ?: [])
                    ->orWhereIn('warehouse_id', $user->accessibleWarehouseIds() ?: []);
            }))->get(['opened_at', 'resolved_at']);
        $averageAlertHours = $alerts->isNotEmpty()
            ? $alerts->avg(fn (StockAlert $alert) => $alert->opened_at->diffInMinutes($alert->resolved_at) / 60)
            : null;

        $discrepancyCount = TransferDiscrepancy::query()->where('created_at', '>=', $start)
            ->whereHas('item.transfer', fn ($q) => $q->visibleTo($user))->count();
        $receivedLines = TransferItem::query()
            ->whereHas('transfer', fn ($q) => $q->visibleTo($user)->where('status', 'received')->where('received_at', '>=', $start))
            ->count();
        $discrepancyRate = $receivedLines > 0 ? ($discrepancyCount / $receivedLines) * 100 : null;

        $damage = DB::table('goods_receipt_lines')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->where('goods_receipts.status', 'posted')->where('goods_receipts.posted_at', '>=', $start)
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('goods_receipts.warehouse_id', $warehouseIds))
            ->selectRaw('COALESCE(SUM(damaged_qty), 0) as damaged, COALESCE(SUM(received_qty), 0) as received')
            ->first();
        $damagedRate = (float) ($damage->received ?? 0) > 0
            ? ((float) $damage->damaged / (float) $damage->received) * 100
            : null;

        return view('admin.reports.kpi', [
            'days' => $days,
            'cards' => [
                ['label' => 'Open stock alerts', 'value' => (string) $openAlerts, 'note' => 'Open alerts you can see.'],
                ['label' => 'Pending approvals', 'value' => (string) ($pendingOpening + $pendingAdjustments + $pendingCounts), 'note' => $pendingOpening.' opening · '.$pendingAdjustments.' adjustment · '.$pendingCounts.' count'],
                ['label' => 'Fill rate', 'value' => $fillRate === null ? 'No transfers' : number_format($fillRate, 1).'%', 'note' => 'Sent quantity / requested quantity over '.$days.' days.'],
                ['label' => 'Service level', 'value' => $serviceLevel === null ? 'No minimums' : number_format($serviceLevel, 1).'%', 'note' => 'Branch products at or above their minimum / branch products with a minimum.'],
                ['label' => 'Average alert-to-receive time', 'value' => $averageAlertHours === null ? 'No resolved alerts' : number_format($averageAlertHours, 1).' h', 'note' => 'Average time from alert opened to resolved.'],
                ['label' => 'Discrepancy rate', 'value' => $discrepancyRate === null ? 'No receipts' : number_format($discrepancyRate, 1).'%', 'note' => 'Discrepancies / received transfer lines.'],
                ['label' => 'Damaged %', 'value' => $damagedRate === null ? 'No receipts' : number_format($damagedRate, 1).'%', 'note' => 'Damaged quantity / received quantity on posted GRNs.'],
            ],
        ]);
    }

    private function render(Request $request, string $title, $query, callable $mapper, array $columns, array $summary = [])
    {
        $user = $request->user();
        $filters = [
            'branches' => Branch::query()->accessibleTo($user)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->accessibleTo($user)->orderBy('name')->get(),
        ];

        if ($request->string('export')->toString() === 'csv') {
            $filename = str()->slug($title).'-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($query, $mapper, $columns): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, array_values($columns));
                foreach ($query->get() as $model) {
                    $row = array_map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : '', $mapper($model));
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $paginator = $query->paginate(50)->withQueryString();
        $rows = collect($paginator->items())->map(fn ($model) => $mapper($model))->all();

        return view('admin.reports.table', compact('title', 'rows', 'columns', 'summary', 'filters', 'paginator'));
    }

    private function scopedLevels(Request $request): Builder
    {
        $user = $request->user();
        $branchIds = StockAccess::readableLocationIds($user, 'branch');
        $warehouseIds = StockAccess::readableLocationIds($user, 'warehouse');

        return StockLevel::query()
            ->when($branchIds !== null || $warehouseIds !== null, function ($q) use ($branchIds, $warehouseIds): void {
                $q->where(function ($scope) use ($branchIds, $warehouseIds): void {
                    $scope->whereRaw('1 = 0');
                    if ($branchIds === null) {
                        $scope->orWhere('location_type', 'branch');
                    } elseif ($branchIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'branch')->whereIn('location_id', $branchIds));
                    }
                    if ($warehouseIds === null) {
                        $scope->orWhere('location_type', 'warehouse');
                    } elseif ($warehouseIds !== []) {
                        $scope->orWhere(fn ($x) => $x->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
                    }
                });
            })
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')));
    }

    private function productFilter($query, Request $request): void
    {
        $term = '%'.$request->string('product').'%';
        $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
    }

    private function locationName(string $type, int $id): string
    {
        $model = $type === 'branch' ? Branch::find($id) : Warehouse::find($id);

        return $model?->name ?? ucfirst($type).' #'.$id;
    }
}

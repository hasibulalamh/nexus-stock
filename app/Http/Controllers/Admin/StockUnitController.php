<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\StockUnit;
use App\Support\StockAccess;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGenerator;

class StockUnitController
{
    public function labels(Request $request)
    {
        $validated = $request->validate([
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
            'product_id' => ['nullable', 'exists:products,id'],
            'location_type' => ['nullable', 'in:branch,warehouse'],
            'location_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:in_stock,in_transit,sold,returned,damaged,written_off'],
            'size' => ['nullable', 'in:50x30,40x25,a4'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        $user = $request->user();
        $units = StockUnit::with('product')->when($validated['unit_ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', $ids))
            ->when($validated['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
            ->when($validated['location_type'] ?? null, fn ($q, $type) => $q->where('location_type', $type))
            ->when($validated['location_id'] ?? null, fn ($q, $id) => $q->where('location_id', $id))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->get()
            ->filter(fn (StockUnit $unit) => $unit->location_type === null || StockAccess::canRead($user, $unit->location_type, (int) $unit->location_id))
            ->take(200);
        $generator = new BarcodeGeneratorSVG();

        return view('admin.stock.unit-labels', [
            'units' => $units->map(fn (StockUnit $unit) => [
                'unit' => $unit,
                'svg' => $generator->getBarcode($unit->unit_code, BarcodeGenerator::TYPE_CODE_128),
            ]),
            'products' => Product::where('tracking_type', 'serial')->where('status', 'active')->get(),
            'sizes' => ['50x30' => '50 x 30 mm', '40x25' => '40 x 25 mm', 'a4' => 'A4 sheet (3 x 8 grid)'],
            'selectedSize' => $validated['size'] ?? '50x30',
        ]);
    }

    public function lookup(Request $request)
    {
        $value = trim((string) $request->input('q'));
        $unit = null;
        $history = collect();
        $message = null;
        if ($value !== '') {
            $unit = StockUnit::with('product')->where('unit_code', $value)->orWhere('serial_no', $value)->first();
            if ($unit && ($unit->location_type === null || StockAccess::canRead($request->user(), $unit->location_type, (int) $unit->location_id))) {
                $history = $unit->movements()->with('performer')->orderBy('occurred_at')->get();
            } elseif ($unit) {
                $unit = null;
                $message = 'Unit not found.';
            } else {
                $message = 'Unit not found.';
            }
        }

        return view('admin.stock.unit-lookup', compact('unit', 'history', 'message'));
    }
}

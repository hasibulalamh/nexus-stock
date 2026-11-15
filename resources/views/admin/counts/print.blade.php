<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Count sheet {{ $count->count_no }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 20px; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .muted { color: #6b7280; font-size: 12px; }
    .head { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #d1d5db; padding: 5px 7px; font-size: 12px; text-align: left; vertical-align: middle; }
    th { background: #f3f4f6; }
    .barcode svg { width: 150px; height: auto; }
    .counted { height: 26px; }
    @media print { .no-print { display: none; } body { margin: 0; } }
  </style>
</head>
<body>
  <div class="head">
    <div>
      <h1>Stock count sheet</h1>
      <div class="muted">{{ $count->count_no }} &middot; {{ $count->locationName() }}</div>
      <div class="muted">Snapshot: {{ $count->snapshot_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }} &middot; {{ $count->status }}</div>
    </div>
    <div class="muted" style="text-align:right">
      <div>Nexus Stock</div>
      <div>Counted by: ____________________</div>
    </div>
  </div>

  <table>
    <thead><tr><th style="width:60px">Barcode</th><th>Product</th><th>SKU</th><th>System qty</th><th>Counted qty</th><th>Notes</th></tr></thead>
    <tbody>
    @foreach($count->lines as $line)
      <tr>
        <td class="barcode">{!! $barcodes[$line->id] ?? '' !!}</td>
        <td>{{ $line->product?->name }}</td>
        <td>{{ $line->product?->sku }}</td>
        <td>{{ $line->system_qty }}</td>
        <td class="counted"></td>
        <td></td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <p class="no-print" style="margin-top:20px"><button onclick="window.print()">Print</button></p>
</body>
</html>

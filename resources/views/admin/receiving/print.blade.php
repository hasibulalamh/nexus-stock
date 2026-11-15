<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Goods receipt {{ $receipt->grn_no }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 24px; }
    h1 { font-size: 22px; margin: 0 0 4px; }
    .muted { color: #6b7280; }
    .head { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 13px; text-align: left; }
    th { background: #f3f4f6; }
    .grid { display: flex; gap: 32px; margin-bottom: 8px; font-size: 13px; }
    .signatures { display: flex; justify-content: space-between; margin-top: 64px; font-size: 13px; }
    .line { border-top: 1px solid #111827; padding-top: 6px; width: 30%; }
    @media print { .no-print { display: none; } body { margin: 0; } }
  </style>
</head>
<body>
  <div class="head">
    <div>
      <h1>Goods receipt note</h1>
      <div class="muted">{{ $receipt->grn_no }} &middot; {{ ucfirst($receipt->status) }}</div>
    </div>
    <div class="muted" style="text-align:right">
      <div>Nexus Stock</div>
      <div>{{ $receipt->warehouse?->name }}</div>
    </div>
  </div>

  <div class="grid">
    <div><strong>Supplier:</strong> {{ $receipt->supplier?->name }}</div>
    <div><strong>Purchase order:</strong> {{ $receipt->purchaseOrder?->po_no ?? '—' }}</div>
    <div><strong>Received:</strong> {{ $receipt->received_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</div>
  </div>
  @if($receipt->notes)<div class="grid"><div><strong>Notes:</strong> {{ $receipt->notes }}</div></div>@endif

  <table>
    <thead><tr><th>#</th><th>Product</th><th>SKU</th><th>Received</th><th>Accepted</th><th>Damaged</th><th>Short</th><th>Serials</th></tr></thead>
    <tbody>
    @foreach($receipt->lines as $index => $line)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $line->product?->name }}</td>
        <td>{{ $line->product?->sku }}</td>
        <td>{{ $line->received_qty }}</td>
        <td>{{ $line->accepted_qty }}</td>
        <td>{{ $line->damaged_qty }}</td>
        <td>{{ $line->short_qty }}</td>
        <td>{{ implode(', ', $line->serial_numbers ?? []) ?: '—' }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <div class="signatures">
    <div class="line">Delivered by</div>
    <div class="line">Received by</div>
    <div class="line">Checked by</div>
  </div>

  <p class="no-print" style="margin-top:28px"><button onclick="window.print()">Print</button></p>
</body>
</html>

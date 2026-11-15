<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Delivery note {{ $transfer->transfer_no }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 24px; }
    h1 { font-size: 22px; margin: 0 0 4px; }
    .muted { color: #6b7280; }
    .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 18px; }
    .barcode svg { width: 240px; height: auto; }
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
      <h1>Delivery note</h1>
      <div class="muted">{{ ucfirst($transfer->type) }} &middot; {{ $transfer->transfer_no }}</div>
    </div>
    <div class="barcode">
      {!! $svg !!}
      <div class="muted" style="font-size:12px">{{ $transfer->transfer_no }}</div>
    </div>
  </div>

  <div class="grid">
    <div><strong>From:</strong> {{ $transfer->fromLocationName() }}</div>
    <div><strong>To:</strong> {{ $transfer->toLocationName() }}</div>
    <div><strong>Date:</strong> {{ $transfer->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</div>
  </div>
  <div class="grid">
    <div><strong>Status:</strong> {{ ucfirst($transfer->status) }}</div>
    <div><strong>Prepared by:</strong> {{ $transfer->requester?->full_name ?? '—' }}</div>
    @if($transfer->sender)<div><strong>Sent by:</strong> {{ $transfer->sender->full_name }}</div>@endif
  </div>
  @if($transfer->notes)
    <div class="grid"><div><strong>Notes:</strong> {{ $transfer->notes }}</div></div>
  @endif

  <table>
    <thead><tr><th>#</th><th>Product</th><th>SKU</th><th>Requested</th><th>Sent</th><th>Received</th><th>Serial units</th></tr></thead>
    <tbody>
    @foreach($transfer->items as $index => $item)
      @php($serials = $item->stockUnits->map(fn ($unit) => $unit->serial_no ?: $unit->unit_code)->implode(', '))
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $item->product?->name }}</td>
        <td>{{ $item->product?->sku }}</td>
        <td>{{ $item->requested_qty }}</td>
        <td>{{ $item->sent_qty }}</td>
        <td>{{ $item->received_qty }}</td>
        <td>{{ $serials !== '' ? $serials : '—' }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <div class="signatures">
    <div class="line">Prepared by</div>
    <div class="line">Sent by</div>
    <div class="line">Received by</div>
  </div>

  <p class="no-print" style="margin-top:28px"><button onclick="window.print()">Print</button></p>
</body>
</html>

@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Goods receipt</small>{{ $receipt->grn_no }}</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.receiving.print', $receipt) }}">Print</a>
      <a class="btn btn-outline-secondary" href="{{ route('admin.receiving.index') }}">Back</a>
    </div>
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card mb-3"><div class="card-body">
    <div class="row">
      <div class="col-sm-6"><p class="mb-1"><strong>Purchase order:</strong>
        @if($receipt->purchaseOrder)
          <a href="{{ route('admin.purchases.show', $receipt->purchaseOrder) }}">{{ $receipt->purchaseOrder->po_no }}</a>
        @else
          —
        @endif
      </p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Supplier:</strong> {{ $receipt->supplier?->name }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Warehouse:</strong> {{ $receipt->warehouse?->name }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Received:</strong> {{ $receipt->received_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Status:</strong>
        <span class="badge {{ $receipt->status === 'posted' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($receipt->status) }}</span>
      </p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Recorded by:</strong> {{ $receipt->receiver?->full_name }}</p></div>
      <div class="col-12"><p class="mb-0"><strong>Notes:</strong> {{ $receipt->notes ?: 'None' }}</p></div>
    </div>

    @if($receipt->status === 'draft' && auth()->user()->can('receiving.create'))
      <form method="POST" action="{{ route('admin.receiving.post', $receipt) }}" class="mt-3">
        @csrf
        <button class="btn btn-success">Post receipt to stock</button>
      </form>
    @endif
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="h5 mb-3">Lines</h2>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Product</th><th>Received</th><th>Accepted</th><th>Damaged</th><th>Short</th><th>Returned</th><th>Unit cost</th><th>Serials / reason</th><th></th></tr></thead>
        <tbody>
        @foreach($receipt->lines as $line)
          <tr>
            <td>{{ $line->product?->name }}<div class="small text-muted">{{ $line->product?->sku }}</div></td>
            <td>{{ $line->received_qty }}</td>
            <td>{{ $line->accepted_qty }}</td>
            <td>{{ $line->damaged_qty }}</td>
            <td>{{ $line->short_qty }}</td>
            <td>{{ $line->returned_qty }}</td>
            <td>{{ \App\Support\Money::format($line->unit_cost) }}</td>
            <td class="small">
              {{ implode(', ', $line->serial_numbers ?? []) ?: '—' }}
              @if($line->reason)<div class="text-muted">{{ $line->reason }}</div>@endif
            </td>
            <td>
              @if($receipt->status === 'posted' && auth()->user()->can('receiving.create') && (float) $line->returnableQty() > 0 && $line->product?->tracking_type !== 'serial')
                <form method="POST" action="{{ route('admin.receiving.supplier-return', $receipt) }}" class="d-flex flex-wrap gap-1">
                  @csrf
                  <input type="hidden" name="line_id" value="{{ $line->id }}">
                  <input type="number" step="0.001" min="0.001" max="{{ $line->returnableQty() }}" name="quantity" value="{{ $line->returnableQty() }}" class="form-control form-control-sm" style="width:100px">
                  <input name="reason" class="form-control form-control-sm" style="width:140px" placeholder="Reason" required>
                  <button class="btn btn-sm btn-outline-danger">Return</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div></div>
</div>
@endsection

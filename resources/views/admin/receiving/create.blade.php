@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Receiving</small>{{ $order ? 'Receive '.$order->po_no : 'New goods receipt' }}</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.receiving.index') }}">Back</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  @if(! $order)
    <div class="card"><div class="card-body">
      <h2 class="h5 mb-3">Select an open purchase order</h2>
      <form method="GET" action="{{ route('admin.receiving.create') }}" class="row g-2">
        <div class="col-md-6">
          <select name="purchase_order_id" class="form-select" required>
            <option value="">Select purchase order</option>
            @foreach($orders as $openOrder)
              <option value="{{ $openOrder->id }}">{{ $openOrder->po_no }} — {{ $openOrder->supplier?->name }} ({{ $openOrder->warehouse?->name }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary">Continue</button></div>
      </form>
      @if($orders->isEmpty())<p class="text-muted mt-3 mb-0">There are no ordered or partially received purchase orders.</p>@endif
    </div></div>
  @else
    <form method="POST" action="{{ route('admin.receiving.store') }}">
      @csrf
      <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
      <input type="hidden" name="supplier_id" value="{{ $order->supplier_id }}">
      <input type="hidden" name="warehouse_id" value="{{ $order->warehouse_id }}">

      <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
          <div class="col-md-4"><p class="mb-0"><strong>Supplier:</strong> {{ $order->supplier?->name }}</p></div>
          <div class="col-md-4"><p class="mb-0"><strong>Warehouse:</strong> {{ $order->warehouse?->name }}</p></div>
          <div class="col-md-2">
            <label class="form-label">Received at</label>
            <input type="datetime-local" name="received_at" class="form-control" value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}">
            @error('received_at')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h2 class="h5 mb-3">Received lines</h2>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Product</th><th>Outstanding</th><th>Accepted</th><th>Damaged</th><th>Short</th><th>Unit cost</th><th>Reason / serials</th></tr></thead>
            <tbody>
            @foreach($order->lines as $i => $line)
              <tr>
                <td>
                  {{ $line->product?->name }}
                  <div class="small text-muted">{{ $line->product?->sku }}</div>
                  <input type="hidden" name="lines[{{ $i }}][product_id]" value="{{ $line->product_id }}">
                </td>
                <td>{{ $line->outstandingQty() }}</td>
                <td><input type="number" step="0.001" min="0" class="form-control" name="lines[{{ $i }}][accepted_qty]" value="{{ old("lines.$i.accepted_qty", $line->outstandingQty()) }}"></td>
                <td><input type="number" step="0.001" min="0" class="form-control" name="lines[{{ $i }}][damaged_qty]" value="{{ old("lines.$i.damaged_qty", '0') }}"></td>
                <td><input type="number" step="0.001" min="0" class="form-control" name="lines[{{ $i }}][short_qty]" value="{{ old("lines.$i.short_qty", '0') }}"></td>
                <td><input type="number" step="0.01" min="0" class="form-control" name="lines[{{ $i }}][unit_cost]" value="{{ old("lines.$i.unit_cost", $line->unit_cost) }}"></td>
                <td>
                  <input name="lines[{{ $i }}][reason]" class="form-control form-control-sm" placeholder="Reason for damage/shortage" value="{{ old("lines.$i.reason") }}">
                  @if($line->product?->tracking_type === 'serial')
                    <textarea name="lines[{{ $i }}][serial_numbers_text]" class="form-control form-control-sm mt-1" rows="2" placeholder="One serial per line (accepted units)">{{ old("lines.$i.serial_numbers_text") }}</textarea>
                  @endif
                  @error("lines.$i.accepted_qty")<div class="text-danger small">{{ $message }}</div>@enderror
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <div class="form-text">Only the accepted quantity enters stock. Damaged and short quantities are recorded on the receipt; damaged quantity can be returned to the supplier afterwards.</div>
      </div></div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary">Save draft receipt</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.receiving.index') }}">Cancel</a>
      </div>
    </form>
  @endif
</div>
@endsection

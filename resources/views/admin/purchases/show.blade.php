@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Purchase order</small>{{ $order->po_no }}</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.purchases.print', $order) }}">Print</a>
      <a class="btn btn-outline-secondary" href="{{ route('admin.purchases.index') }}">Back</a>
    </div>
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-body">
        <div class="row">
          <div class="col-sm-6"><p class="mb-1"><strong>Supplier:</strong> {{ $order->supplier?->name }}</p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>Warehouse:</strong> {{ $order->warehouse?->name }}</p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>Status:</strong>
            <span class="badge {{ in_array($order->status, ['received', 'closed']) ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-secondary' : ($order->status === 'partial' ? 'bg-info' : 'bg-warning')) }}">{{ ucfirst($order->status) }}</span>
          </p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>Expected:</strong> {{ $order->expected_date?->format('Y-m-d') ?? '—' }}</p></div>
          <div class="col-12"><p class="mb-1"><strong>Notes:</strong> {{ $order->notes ?: 'None' }}</p></div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
          @if($order->status === 'draft')
            @can('purchases.edit')
              <a class="btn btn-outline-primary" href="{{ route('admin.purchases.edit', $order) }}">Edit</a>
            @endcan
            @can('purchases.approve')
              <form method="POST" action="{{ route('admin.purchases.approve', $order) }}">@csrf<button class="btn btn-primary">Approve &amp; order</button></form>
            @endcan
          @endif
          @if($order->isOpen() && auth()->user()->can('receiving.create'))
            <a class="btn btn-success" href="{{ route('admin.receiving.create', ['purchase_order_id' => $order->id]) }}">Receive stock</a>
          @endif
          @if(in_array($order->status, ['draft', 'ordered', 'partial'], true) && auth()->user()->can('purchases.cancel'))
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel PO</button>
          @endif
        </div>
      </div></div>

      <div class="card"><div class="card-body">
        <h2 class="h5 mb-3">Lines</h2>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Outstanding</th><th>Unit cost</th><th>Line value</th></tr></thead>
            <tbody>
            @forelse($order->lines as $line)
              <tr>
                <td>{{ $line->product?->name }}<div class="small text-muted">{{ $line->product?->sku }}</div></td>
                <td>{{ $line->ordered_qty }}</td>
                <td>{{ $line->received_qty }}</td>
                <td>{{ $line->outstandingQty() }}</td>
                <td>{{ \App\Support\Money::format($line->unit_cost) }}</td>
                <td>{{ \App\Support\Money::format((float) $line->ordered_qty * (float) $line->unit_cost) }}</td>
              </tr>
            @empty
              <tr><td colspan="6">No lines.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div></div>

      <div class="card mt-3"><div class="card-body">
        <h2 class="h5 mb-3">Goods receipts</h2>
        <table class="table table-sm">
          <thead><tr><th>GRN</th><th>Received</th><th>Status</th><th></th></tr></thead>
          <tbody>
          @forelse($order->receipts as $receipt)
            <tr>
              <td>{{ $receipt->grn_no }}</td>
              <td>{{ $receipt->received_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
              <td>{{ ucfirst($receipt->status) }}</td>
              <td><a href="{{ route('admin.receiving.show', $receipt) }}">View</a></td>
            </tr>
          @empty
            <tr><td colspan="4">No receipts yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-body">
        <h2 class="h5 mb-3">Timeline</h2>
        <p class="mb-1"><strong>Created by</strong> {{ $order->creator?->full_name }}</p>
        <p class="small text-muted">{{ $order->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</p>
        @if($order->approved_at)
          <p class="mb-1"><strong>Approved by</strong> {{ $order->approver?->full_name }}</p>
          <p class="small text-muted">{{ $order->approved_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</p>
        @endif
        @if($order->cancelled_at)
          <p class="mb-1"><strong>Cancelled</strong></p>
          <p class="small text-muted">{{ $order->cancelled_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</p>
        @endif
      </div></div>
    </div>
  </div>
</div>

@if(in_array($order->status, ['draft', 'ordered', 'partial'], true) && auth()->user()->can('purchases.cancel'))
  <div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.purchases.cancel', $order) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Cancel purchase order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" rows="2" maxlength="1000"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-danger">Cancel PO</button>
        </div>
      </form>
    </div></div>
  </div>
@endif
@endsection

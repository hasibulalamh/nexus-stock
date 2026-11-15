@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Transfers</small>Backorders</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.transfers.index') }}">All transfers</a>
  </div>

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-4"><input name="product" class="form-control" placeholder="Product or SKU" value="{{ request('product') }}"></div>
      <div class="col-md-2"><button class="btn btn-primary">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Transfer</th><th>Product</th><th>Destination</th><th>Requested</th><th>Sent</th><th>Backorder</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($items as $item)
          <tr>
            <td>{{ $item->transfer?->transfer_no }}</td>
            <td>{{ $item->product?->name }}<div class="small text-muted">{{ $item->product?->sku }}</div></td>
            <td>{{ $item->transfer?->toLocationName() }}</td>
            <td>{{ $item->requested_qty }}</td>
            <td>{{ $item->sent_qty }}</td>
            <td><span class="badge bg-warning text-dark">{{ $item->backorderQty() }}</span></td>
            <td>{{ ucfirst($item->transfer?->status ?? '') }}</td>
            <td>@if($item->transfer)<a href="{{ route('admin.transfers.show', $item->transfer) }}">View</a>@endif</td>
          </tr>
        @empty
          <tr><td colspan="8">No backorders found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $items->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

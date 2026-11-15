@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Procurement</small>Purchase orders</h1></div>
    @can('purchases.create')
      <a class="btn btn-primary" href="{{ route('admin.purchases.create') }}">New purchase order</a>
    @endcan
  </div>

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          @foreach(['draft', 'ordered', 'partial', 'received', 'closed', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="supplier_id" class="form-select">
          <option value="">All suppliers</option>
          @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="warehouse_id" class="form-select">
          <option value="">All warehouses</option>
          @foreach($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
      <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>PO</th><th>Supplier</th><th>Warehouse</th><th>Lines</th><th>Expected</th><th>Created</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($orders as $order)
          <tr>
            <td>{{ $order->po_no }}</td>
            <td>{{ $order->supplier?->name }}</td>
            <td>{{ $order->warehouse?->name }}</td>
            <td>{{ $order->lines_count }}</td>
            <td>{{ $order->expected_date?->format('Y-m-d') ?? '—' }}</td>
            <td>{{ $order->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
            <td><span class="badge {{ in_array($order->status, ['received', 'closed']) ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-secondary' : ($order->status === 'partial' ? 'bg-info' : 'bg-warning')) }}">{{ ucfirst($order->status) }}</span></td>
            <td><a href="{{ route('admin.purchases.show', $order) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="8">No purchase orders found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $orders->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

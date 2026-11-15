@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Procurement</small>Goods receipts</h1></div>
    @can('receiving.create')
      <a class="btn btn-primary" href="{{ route('admin.receiving.create') }}">New receipt</a>
    @endcan
  </div>

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <option value="draft" @selected(request('status') === 'draft')>Draft</option>
          <option value="posted" @selected(request('status') === 'posted')>Posted</option>
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
      <div class="col-md-1"><button class="btn btn-primary">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>GRN</th><th>Purchase order</th><th>Supplier</th><th>Warehouse</th><th>Lines</th><th>Received</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($receipts as $receipt)
          <tr>
            <td>{{ $receipt->grn_no }}</td>
            <td>{{ $receipt->purchaseOrder?->po_no ?? '—' }}</td>
            <td>{{ $receipt->supplier?->name }}</td>
            <td>{{ $receipt->warehouse?->name }}</td>
            <td>{{ $receipt->lines_count }}</td>
            <td>{{ $receipt->received_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
            <td><span class="badge {{ $receipt->status === 'posted' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($receipt->status) }}</span></td>
            <td><a href="{{ route('admin.receiving.show', $receipt) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="8">No goods receipts found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $receipts->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

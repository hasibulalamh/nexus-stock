@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock</small>Transfers</h1></div>
    <div class="d-flex gap-2">
      @can('transfers.view')
        <a class="btn btn-outline-secondary" href="{{ route('admin.transfers.backorders') }}">Backorders</a>
      @endcan
      @can('transfers.create')
        <a class="btn btn-outline-primary" href="{{ route('admin.transfers.create', ['type' => 'return']) }}">New return</a>
        <a class="btn btn-primary" href="{{ route('admin.transfers.create') }}">New transfer</a>
      @endcan
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          @foreach(['draft', 'sent', 'received', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select">
          <option value="">All types</option>
          <option value="transfer" @selected(request('type') === 'transfer')>Transfer</option>
          <option value="return" @selected(request('type') === 'return')>Return</option>
        </select>
      </div>
      <div class="col-md-3">
        <select name="branch_id" class="form-select">
          <option value="">All branches</option>
          @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
      <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
      <div class="col-md-2"><input name="q" class="form-control" placeholder="Transfer no." value="{{ request('q') }}"></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Transfer</th><th>Type</th><th>From</th><th>To</th><th>Lines</th><th>Created</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($transfers as $transfer)
          <tr>
            <td>{{ $transfer->transfer_no }}</td>
            <td>{{ ucfirst($transfer->type) }}</td>
            <td>{{ $transfer->fromLocationName() }}</td>
            <td>{{ $transfer->toLocationName() }}</td>
            <td>{{ $transfer->items->count() }}</td>
            <td>{{ $transfer->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
            <td>
              <span class="badge {{ $transfer->status === 'received' ? 'bg-success' : ($transfer->status === 'cancelled' ? 'bg-secondary' : ($transfer->status === 'sent' ? 'bg-info' : 'bg-warning')) }}">{{ ucfirst($transfer->status) }}</span>
              @if($transfer->status === 'sent')
                <span class="badge bg-light text-dark">On the way</span>
              @endif
            </td>
            <td><a href="{{ route('admin.transfers.show', $transfer) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="8">No transfers found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $transfers->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

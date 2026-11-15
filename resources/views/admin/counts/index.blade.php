@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock</small>Stock counts</h1></div>
    @can('counts.create')
      <a class="btn btn-primary" href="{{ route('admin.counts.create') }}">New count</a>
    @endcan
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          @foreach(\App\Models\StockCount::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select name="location_type" class="form-select">
          <option value="">All locations</option>
          <option value="branch" @selected(request('location_type') === 'branch')>Branch</option>
          <option value="warehouse" @selected(request('location_type') === 'warehouse')>Warehouse</option>
        </select>
      </div>
      <div class="col-md-1"><button class="btn btn-primary">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Count</th><th>Location</th><th>Lines</th><th>Snapshot</th><th>Requested by</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($counts as $count)
          <tr>
            <td>{{ $count->count_no }}</td>
            <td>{{ $count->locationName() }}</td>
            <td>{{ $count->lines_count }}</td>
            <td>{{ $count->snapshot_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
            <td>{{ $count->requester?->full_name }}</td>
            <td><span class="badge {{ $count->status === 'approved' ? 'bg-success' : (in_array($count->status, ['open', 'counting'], true) ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($count->status) }}</span></td>
            <td><a href="{{ route('admin.counts.show', $count) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="7">No stock counts found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $counts->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

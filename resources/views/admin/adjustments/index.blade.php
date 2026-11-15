@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock</small>Adjustments</h1></div>
    @can('adjustments.create')
      <a class="btn btn-primary" href="{{ route('admin.adjustments.create') }}">New adjustment</a>
    @endcan
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          @foreach(['pending', 'approved', 'rejected', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="reason" class="form-select">
          <option value="">All reasons</option>
          @foreach(\App\Models\StockAdjustment::REASONS as $reason)
            <option value="{{ $reason }}" @selected(request('reason') === $reason)>{{ ucfirst(str_replace('_', ' ', $reason)) }}</option>
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
        <thead><tr><th>Adjustment</th><th>Location</th><th>Reason</th><th>Lines</th><th>Requested by</th><th>Created</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($adjustments as $adjustment)
          <tr>
            <td>{{ $adjustment->adj_no }}</td>
            <td>{{ $adjustment->locationName() }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $adjustment->reason)) }}</td>
            <td>{{ $adjustment->lines_count }}</td>
            <td>{{ $adjustment->requester?->full_name }}</td>
            <td>{{ $adjustment->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</td>
            <td><span class="badge {{ $adjustment->status === 'approved' ? 'bg-success' : ($adjustment->status === 'pending' ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($adjustment->status) }}</span></td>
            <td><a href="{{ route('admin.adjustments.show', $adjustment) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="8">No adjustments found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $adjustments->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

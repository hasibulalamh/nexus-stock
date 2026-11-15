@extends('layout.master')
@section('content')
<div class="container-fluid p-0"><div class="nx-page-head"><h1 class="nx-title"><small>Stock</small>Stock requests</h1></div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending','approved','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-md-2"><select name="location_type" class="form-select"><option value="">All locations</option><option value="branch" @selected(request('location_type') === 'branch')>Branch</option><option value="warehouse" @selected(request('location_type') === 'warehouse')>Warehouse</option></select></div><div class="col-md-2"><input type="date" name="date" class="form-control" value="{{ request('date') }}"></div><div class="col-md-1"><button class="btn btn-primary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Request</th><th>Location</th><th>Date</th><th>Lines</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($requests as $item)<tr><td>{{ $item->request_no }}</td><td>{{ $item->location?->name ?? ucfirst($item->location_type).' #'.$item->location_id }}</td><td>{{ $item->occurred_at->format('Y-m-d') }}</td><td>{{ $item->lines->count() }}</td><td>{{ ucfirst($item->status) }}</td><td><a href="{{ route('admin.stock.requests.show', $item) }}">View</a></td></tr>@empty<tr><td colspan="6">No stock requests found.</td></tr>@endforelse
</tbody></table></div>{{ $requests->links('pagination::bootstrap-5') }}</div></div></div>
@endsection

@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock</small>Levels</h1></div>
  </div>
  <div class="card">
    <div class="card-body">
      <form class="row g-2 mb-3">
        <div class="col-md-2"><select name="location_type" class="form-select"><option value="">All locations</option><option value="branch" @selected(request('location_type') === 'branch')>Branches</option><option value="warehouse" @selected(request('location_type') === 'warehouse')>Warehouses</option></select></div>
        <div class="col-md-2"><select name="location_id" class="form-select"><option value="">All locations</option>@foreach($branches as $location)<option value="{{ $location->id }}" @selected(request('location_id') == $location->id && request('location_type') === 'branch')>Branch: {{ $location->name }}</option>@endforeach @foreach($warehouses as $location)<option value="{{ $location->id }}" @selected(request('location_id') == $location->id && request('location_type') === 'warehouse')>Warehouse: {{ $location->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="product" class="form-control" placeholder="Product or SKU" value="{{ request('product') }}"></div>
        <div class="col-md-3 form-check align-self-center ms-2"><input class="form-check-input" type="checkbox" name="below_minimum" value="1" id="belowMinimum" @checked(request('below_minimum'))><label class="form-check-label" for="belowMinimum">Below minimum</label></div>
        <div class="col-md-1"><button class="btn btn-primary">Filter</button></div>
      </form>
      <table class="table">
        <thead><tr><th>Product</th><th>Location</th><th>Quantity</th><th>Effective minimum</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($levels as $level)
          <tr>
            <td>{{ $level->product->name }}</td>
            <td>{{ ucfirst($level->location_type) }}: {{ $level->location?->name ?? $level->location_id }}</td>
            <td>{{ $level->quantity }}</td>
            <td>{{ $level->effectiveMinimum() ?? 'No minimum' }}</td>
            <td>@if($level->effectiveMinimum() === null)<span class="badge bg-secondary">No minimum</span>@elseif((float)$level->quantity <= (float)$level->effectiveMinimum())<span class="badge bg-danger">Low</span>@else<span class="badge bg-success">OK</span>@endif</td>
            <td>
              @if($level->location_type === 'branch')
                @can('stock.thresholds')
                  <form method="POST" action="{{ route('admin.stock.levels.minimum', $level) }}" class="d-flex gap-2">
                    @csrf
                    <input type="number" name="min_quantity" class="form-control form-control-sm" min="0" step="0.001" value="{{ $level->min_quantity }}">
                    <button class="btn btn-sm btn-outline-primary">Set minimum</button>
                  </form>
                @endcan
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6">No stock levels found.</td></tr>
        @endforelse
        </tbody>
      </table>
      {{ $levels->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>
@endsection

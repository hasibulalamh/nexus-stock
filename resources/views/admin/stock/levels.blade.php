@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock</small>Levels</h1></div>
  </div>
  <div class="card">
    <div class="card-body">
      <table class="table">
        <thead><tr><th>Product</th><th>Location</th><th>Quantity</th><th>Minimum</th><th></th></tr></thead>
        <tbody>
        @forelse($levels as $level)
          <tr>
            <td>{{ $level->product->name }}</td>
            <td>{{ ucfirst($level->location_type) }} #{{ $level->location_id }}</td>
            <td>{{ $level->quantity }}</td>
            <td>{{ $level->effectiveMinimum() ?? 'No minimum' }}</td>
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
          <tr><td colspan="5">No stock levels found.</td></tr>
        @endforelse
        </tbody>
      </table>
      {{ $levels->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>
@endsection

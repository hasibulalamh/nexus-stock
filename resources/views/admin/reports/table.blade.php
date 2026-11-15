@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Reports</small>{{ $title }}</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}">All reports</a>
      <a class="btn btn-primary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Export CSV</a>
    </div>
  </div>

  @if(! empty($summary))
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-4">
      @foreach($summary as $label => $value)
        <div><div class="text-muted small">{{ $label }}</div><div class="h5 mb-0">{{ $value }}</div></div>
      @endforeach
    </div></div>
  @endif

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="location_type" class="form-select">
          <option value="">All location types</option>
          <option value="branch" @selected(request('location_type') === 'branch')>Branch</option>
          <option value="warehouse" @selected(request('location_type') === 'warehouse')>Warehouse</option>
        </select>
      </div>
      <div class="col-md-3">
        <select name="location_id" class="form-select">
          <option value="">All locations</option>
          @foreach($filters['branches'] as $branch)<option value="{{ $branch->id }}" @selected(request('location_id') == $branch->id)>Branch: {{ $branch->name }}</option>@endforeach
          @foreach($filters['warehouses'] as $warehouse)<option value="{{ $warehouse->id }}" @selected(request('location_id') == $warehouse->id)>Warehouse: {{ $warehouse->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2"><input name="product" class="form-control" placeholder="Product or SKU" value="{{ request('product') }}"></div>
      <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
      <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($rows as $row)
          <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
        @empty
          <tr><td colspan="{{ count($columns) }}">No rows for these filters.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $paginator->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

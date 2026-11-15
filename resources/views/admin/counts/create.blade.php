@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock counts</small>Start a count</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.counts.index') }}">Back</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.counts.store') }}">
    @csrf
    <div class="card"><div class="card-body">
      <p class="text-muted">Starting a count snapshots the system quantity of every quantity-tracked product with a stock level at the location. Serial-tracked products are not part of a count sheet.</p>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Location type</label>
          <select name="location_type" class="form-select">
            <option value="warehouse" @selected(old('location_type') === 'warehouse')>Warehouse</option>
            <option value="branch" @selected(old('location_type') === 'branch')>Branch</option>
          </select>
          @error('location_type')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
          <label class="form-label">Location</label>
          <select name="location_id" class="form-select">
            <option value="">Select location</option>
            @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('location_id') == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach
            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('location_id') == $branch->id)>{{ $branch->name }}</option>@endforeach
          </select>
          @error('location_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
      </div>
      <div class="mt-3"><button class="btn btn-primary">Start count</button></div>
    </div></div>
  </form>
</div>
@endsection

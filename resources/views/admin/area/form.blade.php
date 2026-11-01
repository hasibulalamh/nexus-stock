@extends('layout.master')
@section('content')
<div class="container-fluid p-0"><div class="nx-page-head"><h1 class="nx-title"><small>Administration</small>{{ $area->exists ? 'Edit' : 'Create' }} Area</h1></div>
<div class="card"><div class="card-body"><form method="POST" action="{{ $area->exists ? route('admin.areas.update', $area) : route('admin.areas.store') }}">@csrf @if($area->exists) @method('PUT') @endif
<div class="row g-3">
  <div class="col-md-6"><label class="form-label required">City</label><select name="city_id" class="form-select" required><option value="">-- Select --</option>@foreach($cities as $city)<option value="{{ $city->id }}" @selected(old('city_id', $area->city_id) == $city->id)>{{ $city->name }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $area->name) }}" required maxlength="150"></div>
  <div class="col-md-6"><label class="form-label">Bangla name</label><input name="name_bn" class="form-control" value="{{ old('name_bn', $area->name_bn) }}" maxlength="150"></div>
  <div class="col-md-6"><label class="form-label">Type</label><select name="type" class="form-select"><option value="">-- Select --</option>@foreach(['area','section','locality'] as $type)<option @selected(old('type', $area->type) === $type)>{{ $type }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label">OSM ID</label><input name="osm_id" class="form-control" value="{{ old('osm_id', $area->osm_id) }}"></div>
  <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $area->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $area->status) === 'inactive')>Inactive</option></select></div>
  <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ old('notes', $area->notes) }}</textarea></div>
</div><div class="nx-form-actions"><button class="btn btn-primary">Save Area</button></div></form></div></div></div>
@endsection

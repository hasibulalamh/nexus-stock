@extends('layout.master')
@section('content')
<div class="container-fluid p-0"><div class="nx-page-head"><h1 class="nx-title"><small>Administration</small>Import Areas</h1></div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('admin.areas.import.preview') }}" enctype="multipart/form-data">@csrf
<label class="form-label required">CSV file</label><input type="file" name="csv_file" class="form-control" accept=".csv" required>
<p class="text-muted mt-2">Expected header: <code>osm_id, area, area_bn, type, osm_place, lat, lng, city, thana_hint, source, verified, notes</code></p>
<button class="btn btn-primary">Preview</button></form>
@isset($preview)<hr><h5>Preview</h5><p>Total: {{ $preview['total'] }}; create: {{ $preview['to_create'] }}; update: {{ $preview['to_update'] }}</p>
@if($preview['errors'])<div class="alert alert-danger">@foreach($preview['errors'] as $error)<div>Row {{ $error['row'] }}: {{ $error['message'] }}</div>@endforeach</div>
@else<form method="POST" action="{{ route('admin.areas.import.apply') }}">@csrf<button class="btn btn-success">Confirm and apply</button></form>@endif
@endisset
</div></div></div>
@endsection

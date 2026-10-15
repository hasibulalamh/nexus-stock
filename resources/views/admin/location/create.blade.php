@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Import Locations</h1>
      <p class="nx-sub">Upload a CSV file to add country, state, city and upazila rows</p>
    </div>
    <a href="{{ route('admin.locations.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.locations.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label class="form-label required" for="csv_file">CSV File</label>
          <input type="file" id="csv_file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv" required>
          @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <small class="text-muted">
            Expected CSV columns: <code>division_bn, division_en, district_bn, district_en, upazila_bn, upazila_en, postcode, postcode_status</code>.
            Upazila will be stored directly; union layer is not used.
          </small>
        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-upload"></i> Import Locations
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

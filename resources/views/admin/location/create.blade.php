@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Import Locations</h1>
      <p class="nx-sub">Upload a CSV file to add country, state, city, thana and union rows</p>
    </div>
    <a href="{{ route('admin.locations.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <strong><i class="fas fa-triangle-exclamation"></i> Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.locations.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-file-csv"></i> CSV File</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="csv_file">Select CSV File</label>
            <input type="file" class="form-control @error('csv_file') is-invalid @enderror" id="csv_file"
                   name="csv_file" accept=".csv" required>
            <small class="text-muted">Accepted file types: .csv, .txt</small>
            @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-upload"></i> Upload CSV
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

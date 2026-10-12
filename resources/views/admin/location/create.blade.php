@extends('layout.master')

@section('content')

<style>
  .dept-form-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .dept-form-card .card-header {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .form-label {
    font-weight: 500;
    color: #495057;
  }
  .form-control, .form-select {
    border-radius: .6rem;
    padding: .6rem .9rem;
  }
  .btn-gradient {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    border: 0;
    color: #fff;
    border-radius: .6rem;
    transition: all .3s ease;
  }
  .btn-gradient:hover {
    opacity: .9;
    transform: translateY(-2px);
  }
</style>

<div class="container my-4">
  <div class="card dept-form-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-file-upload me-2"></i>Upload Location CSV</h5>
      <a href="{{ route('admin.locations.list') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-list me-1"></i>Location List</a>
    </div>

    <div class="card-body">
      <form action="{{route('admin.locations.store')}}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">

          <!-- CSV File Input -->
          <div class="col-md-6">
            <label class="form-label">Select CSV File <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
          </div>

          <!-- Action Buttons -->
          <div class="col-12 d-flex justify-content-end gap-2 mt-3">
            <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
            <button type="submit" class="btn btn-gradient"><i class="fa-solid fa-upload me-1"></i>Upload CSV</button>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@extends('layout.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Create Warehouse</h5>
      <a href="{{ route('admin.warehouse.list') }}" class="btn btn-light btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Back
      </a>
    </div>

    <div class="card-body">
      <form action="{{route('admin.warehouse.store')}}" method="POST" id="warehouseForm">
        @csrf
        <div class="row g-4">

          <!-- Warehouse Info -->
          <h6 class="fw-bold text-primary mb-2">🏢 Warehouse Information</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Warehouse Name</label>
            <input type="text" id="warehouse_name" name="name" class="form-control" placeholder="Enter warehouse name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Warehouse Code</label>
            <input type="text" id="warehouse_code" name="code" class="form-control" readonly required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Slug</label>
            <input type="text" id="warehouse_slug" name="slug" class="form-control" readonly>
          </div>

          <!-- Location Section -->
          <h6 class="fw-bold text-primary mt-4 mb-2">📍 Location Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Country</label>
            <select id="country" name="country_id" class="form-select" required>
              <option value="">-- Select Country --</option>
              @foreach($countries as $country)
                <option value="{{ $country->id }}">{{ $country->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">State</label>
            <select id="state" name="state_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">City</label>
            <select id="city" name="city_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Thana</label>
            <select id="thana" name="thana_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Union</label>
            <select id="union" name="union_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Zipcode</label>
            <input type="text" id="zipcode" name="zipcode" class="form-control" readonly>
          </div>

          <!-- Capacity & Dates -->
          <h6 class="fw-bold text-primary mt-4 mb-2">⚙️ Operational Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Capacity</label>
            <input type="number" name="capacity" class="form-control" placeholder="Enter capacity" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Opening Date</label>
            <input type="date" name="starting_date" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Status</label>
            <select name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mt-4">
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-undo"></i> Reset
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save Warehouse
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script src="{{ asset('assets/js/location.js') }}"></script>
<!-- location-->
<script>
document.addEventListener('DOMContentLoaded', function () {

  // Initialize dynamic location loader
  initLocationSelector({
    countryId: 'country',
    stateId: 'state',
    cityId: 'city',
    thanaId: 'thana',
    unionId: 'union',
    zipcodeId: 'zipcode',
    baseUrl: '{{ url("admin/warehouse") }}'
  });

  // Auto generate Code & Slug from Name
  const nameInput = document.getElementById('warehouse_name');
  nameInput.addEventListener('input', function () {
    const name = nameInput.value.trim();
    document.getElementById('warehouse_code').value = name.toUpperCase().replace(/\s+/g, '_').substring(0, 10);
    document.getElementById('warehouse_slug').value = name.toLowerCase().replace(/\s+/g, '-');
  });

});
</script>

<style>
  .required::after {
    content: " *";
    color: red;
    font-weight: bold;
  }
  .bg-gradient-primary {
    background: linear-gradient(135deg, #20c997, #6f42c1);
  }
</style>
@endsection

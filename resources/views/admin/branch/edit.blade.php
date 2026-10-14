@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Modules</small>Edit Branch</h1>
      <p class="nx-sub">Update the branch details and address</p>
    </div>
    <a href="{{ route('admin.branch.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.branch.update', $branch->id) }}" method="POST" id="branchForm">
        @csrf
        @method('PUT')

        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-shop"></i> Branch Information</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="branch_name">Branch Name</label>
            <input type="text" id="branch_name" name="name" value="{{ old('name', $branch->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="branch_code">Branch Code</label>
            <input type="text" id="branch_code" name="code" value="{{ old('code', $branch->code) }}"
                   class="form-control @error('code') is-invalid @enderror" readonly required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="branch_slug">Branch Slug</label>
            <input type="text" id="branch_slug" name="slug" value="{{ old('slug', $branch->slug) }}"
                   class="form-control @error('slug') is-invalid @enderror" readonly>
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-location-dot"></i> Location Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="country">Country</label>
            <select id="country" name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
              <option value="">-- Select Country --</option>
              @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $branch->location->country_id) == $country->id)>
                  {{ $country->name }}
                </option>
              @endforeach
            </select>
            @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="state">State</label>
            <select id="state" name="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
              @if($branch->location->state)
                <option value="{{ $branch->location->state_id }}">{{ $branch->location->state->name }}</option>
              @else
                <option value="">-- Select State --</option>
              @endif
            </select>
            @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="city">City</label>
            <select id="city" name="city_id" class="form-select @error('city_id') is-invalid @enderror" required>
              @if($branch->location->city)
                <option value="{{ $branch->location->city_id }}">{{ $branch->location->city->name }}</option>
              @else
                <option value="">-- Select City --</option>
              @endif
            </select>
            @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="thana">Thana</label>
            <select id="thana" name="thana_id" class="form-select @error('thana_id') is-invalid @enderror" required>
              @if($branch->location->thana)
                <option value="{{ $branch->location->thana_id }}">{{ $branch->location->thana->name }}</option>
              @else
                <option value="">-- Select Thana --</option>
              @endif
            </select>
            @error('thana_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="union">Union</label>
            <select id="union" name="union_id" class="form-select @error('union_id') is-invalid @enderror" required>
              @if($branch->location->union)
                <option value="{{ $branch->location->union_id }}">{{ $branch->location->union->name }}</option>
              @else
                <option value="">-- Select Union --</option>
              @endif
            </select>
            @error('union_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="zipcode">Zipcode</label>
            <input type="text" id="zipcode" name="zipcode" class="form-control"
                   value="{{ $branch->location->union->zipcode ?? '' }}" readonly>
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-sliders"></i> Operational Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="capacity">Capacity</label>
            <input type="number" id="capacity" name="capacity" value="{{ old('capacity', $branch->capacity) }}"
                   class="form-control @error('capacity') is-invalid @enderror" required>
            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="starting_date">Opening Date</label>
            <input type="date" id="starting_date" name="starting_date"
                   value="{{ old('starting_date', $branch->starting_date) }}"
                   class="form-control @error('starting_date') is-invalid @enderror" required>
            @error('starting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', $branch->status) == 'active')>Active</option>
              <option value="inactive" @selected(old('status', $branch->status) == 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Branch
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/location.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initLocationSelector({
    countryId: 'country',
    stateId: 'state',
    cityId: 'city',
    thanaId: 'thana',
    unionId: 'union',
    zipcodeId: 'zipcode',
    baseUrl: '{{ url("admin/branch") }}'
  });

  // Auto-update code & slug when name changes
  const nameInput = document.getElementById('branch_name');
  nameInput.addEventListener('input', function () {
    const name = this.value.trim();
    document.getElementById('branch_code').value = name.toUpperCase().replace(/\s+/g, '_').substring(0, 10);
    document.getElementById('branch_slug').value = name.toLowerCase().replace(/\s+/g, '-');
  });
});
</script>
@endsection

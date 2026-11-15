@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Suppliers</small>{{ $supplier->exists ? 'Edit supplier' : 'New supplier' }}</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.suppliers.index') }}">Back</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ $supplier->exists ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}">
    @csrf
    @if($supplier->exists) @method('PUT') @endif
    <div class="card"><div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" value="{{ old('name', $supplier->name) }}" required>
          @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Code</label>
          <input name="code" class="form-control" value="{{ old('code', $supplier->code) }}" required>
          @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
          </select>
          @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">Contact person</label>
          <input name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person) }}">
          @error('contact_person')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Phone</label>
          <input name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
          @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" value="{{ old('email', $supplier->email) }}">
          @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
          @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary">{{ $supplier->exists ? 'Save changes' : 'Create supplier' }}</button>
      </div>
    </div></div>
  </form>
</div>
@endsection

@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <h1 class="h3 nx-title mb-0"><small>Inventory</small>Edit Brand</h1>
    <a href="{{ route('admin.brands.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <strong><i class="fas fa-triangle-exclamation me-1"></i> Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body p-4">
      <form action="{{ route('admin.brands.update', $brand->id) }}" method="POST" enctype="multipart/form-data" id="brandForm">
        @csrf
        @method('PUT')
        <div class="row g-4">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-box"></i> Basic Information</div>
            <hr>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="name">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $brand->name) }}"
                   class="form-control @error('name') is-invalid @enderror" placeholder="Enter brand name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="slug">Slug</label>
            <div class="input-group">
              <input type="text" name="slug" id="slug" value="{{ old('slug', $brand->slug) }}"
                     class="form-control @error('slug') is-invalid @enderror" required>
              <button type="button" id="generateSlug" class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate from name">
                <i class="fas fa-magic"></i> Generate
              </button>
            </div>
            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="logo">Logo</label>
            <div class="d-flex align-items-center gap-3 mb-2">
              <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="nx-avatar">
              <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
            </div>
            <small class="text-muted">Leave empty to keep the current logo. Max 2MB.</small>
            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="website">Website</label>
            <input type="url" name="website" id="website" value="{{ old('website', $brand->website) }}"
                   class="form-control @error('website') is-invalid @enderror" placeholder="https://example.com">
            @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $brand->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', $brand->status) === 'active')>Active</option>
              <option value="inactive" @selected(old('status', $brand->status) === 'inactive')>Inactive</option>
            </select>
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.brands.list') }}" class="btn btn-outline-secondary">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Brand
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const nameInput = document.getElementById('name');
  const slugInput = document.getElementById('slug');
  const generateBtn = document.getElementById('generateSlug');

  if (generateBtn && nameInput && slugInput) {
    generateBtn.addEventListener('click', function () {
      const name = nameInput.value.trim();
      if (!name) {
        slugInput.value = '';
        return;
      }

      // Simple slug generation (frontend only for UX)
      let slug = name.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();

      // Call backend to ensure uniqueness
      fetch('{{ route('admin.brands.generate-slug') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ name: name })
      })
      .then(response => response.json())
      .then(data => {
        if (data.slug) {
          slugInput.value = data.slug;
        }
      })
      .catch(() => {
        // Fallback to frontend slug if fetch fails
        slugInput.value = slug;
      });
    });
  }
});
</script>
@endsection

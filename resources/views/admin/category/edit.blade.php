@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <h1 class="h3 nx-title mb-0"><small>Inventory</small>Edit Category</h1>
    <a href="{{ route('admin.categories.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" id="categoryForm">
        @csrf
        @method('PUT')
        <div class="row g-4">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-tag"></i> Basic Information</div>
            <hr>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="name">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                   class="form-control @error('name') is-invalid @enderror" placeholder="Enter category name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="slug">Slug</label>
            <div class="input-group">
              <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}"
                     class="form-control @error('slug') is-invalid @enderror" required>
              <button type="button" id="generateSlug" class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate from name">
                <i class="fas fa-magic"></i> Generate
              </button>
            </div>
            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="parent_id">Parent Category</label>
            <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
              <option value="">-- None (Top Level) --</option>
              @foreach($parents as $parent)
                <option value="{{ $parent->id }}"
                        @selected(old('parent_id', $category->parent_id) == $parent->id)>
                  {{ $parent->name }}
                </option>
              @endforeach
            </select>
            @error('parent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <small class="text-muted">Maximum 2 levels deep. Cannot select a child of a child.</small>
          </div>

          <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', $category->status) === 'active')>Active</option>
              <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="sort_order">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                   class="form-control @error('sort_order') is-invalid @enderror" min="0">
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.categories.list') }}" class="btn btn-outline-secondary">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Category
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
      fetch('{{ route('admin.categories.generate-slug') }}', {
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

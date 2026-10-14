@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <h1 class="h3 nx-title mb-0"><small>Inventory</small>Create Product</h1>
    <a href="{{ route('admin.products.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        <div class="row g-4">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-tag"></i> Basic Information</div>
            <hr>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="sku">SKU</label>
            <input type="text" name="sku" id="sku" value="{{ old('sku') }}"
                   class="form-control @error('sku') is-invalid @enderror" placeholder="Enter SKU" required>
            <small class="text-muted">Will be converted to uppercase automatically.</small>
            @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="name">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror" placeholder="Enter product name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="barcode">Barcode</label>
            <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                   class="form-control @error('barcode') is-invalid @enderror" placeholder="Optional barcode">
          </div>

          <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="form-control @error('description') is-invalid @enderror"
                      placeholder="Optional description">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-sitemap"></i> Classification</div>
            <hr>
          </div>

          <div class="col-md-6">
            <label class="form-label required" for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
              <option value="">-- Select Category --</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
              @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="brand_id">Brand</label>
            <select name="brand_id" id="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
              <option value="">-- No Brand --</option>
              @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
              @endforeach
            </select>
            @error('brand_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-money-bill-wave"></i> Pricing and Stock Settings</div>
            <hr>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="cost_price">Cost Price</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', 0) }}"
                     class="form-control @error('cost_price') is-invalid @enderror" step="0.01" min="0" required>
            </div>
            @error('cost_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="selling_price">Selling Price</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price', 0) }}"
                     class="form-control @error('selling_price') is-invalid @enderror" step="0.01" min="0" required>
            </div>
            @error('selling_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="unit">Unit</label>
            <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
              <option value="">-- Select Unit --</option>
              @foreach($units as $key => $label)
                <option value="{{ $key }}" @selected(old('unit') == $key)>{{ $label }}</option>
              @endforeach
            </select>
            @error('unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="reorder_level">Reorder Level</label>
            <input type="number" name="reorder_level" id="reorder_level" value="{{ old('reorder_level', 0) }}"
                   class="form-control @error('reorder_level') is-invalid @enderror" min="0">
            <small class="text-muted">Stock level at which to trigger reorder.</small>
          </div>

          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input nx-check-input" type="checkbox" name="track_stock" id="track_stock"
                     value="1" @checked(old('track_stock', true))>
              <label class="form-check-label" for="track_stock">Track stock levels</label>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
              <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-image"></i> Image</div>
            <hr>
          </div>

          <div class="col-12">
            <label class="form-label" for="image">Product Image</label>
            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
            <small class="text-muted">Max 2MB. Allowed: JPG, PNG, GIF, SVG.</small>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.products.list') }}" class="btn btn-outline-secondary">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Save Product
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

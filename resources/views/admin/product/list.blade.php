@extends('layout.master')

@section('content')
@php
    $activeFilters = collect(['q', 'category_id', 'brand_id', 'status'])
        ->filter(fn ($k) => request()->filled($k))->count();
@endphp
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Inventory</small>Products</h1>
      <p class="nx-sub">Manage product catalog</p>
    </div>
    @can('products.create')
      <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add product
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>

    <form method="GET" action="{{ route('admin.products.list') }}" class="nx-toolbar" data-filter>
      <div class="nx-search">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Search name, SKU or barcode" aria-label="Search products">
      </div>

      <select name="category_id" class="form-select" aria-label="Filter by category" onchange="nxSubmit(this.form)">
        <option value="">All categories</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
        @endforeach
      </select>

      <select name="brand_id" class="form-select" aria-label="Filter by brand" onchange="nxSubmit(this.form)">
        <option value="">All brands</option>
        @foreach($brands as $brand)
          <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
        @endforeach
      </select>

      <select name="status" class="form-select" aria-label="Filter by status" onchange="nxSubmit(this.form)">
        <option value="">Any status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
      </select>

      <input type="hidden" name="per_page" value="{{ request('per_page') }}">

      <button type="submit" class="btn btn-light">
        <i class="fas fa-filter"></i> Apply
        @if($activeFilters)<span class="nx-count" aria-label="{{ $activeFilters }} filters applied">{{ $activeFilters }}</span>@endif
      </button>
      @if($activeFilters)
        <a href="{{ route('admin.products.list') }}" class="btn btn-link">Reset</a>
      @endif
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th class="nx-col-num">#</th>
            <th>Product</th>
            <th class="d-none d-md-table-cell">SKU</th>
            <th class="d-none d-lg-table-cell">Category</th>
            <th class="d-none d-lg-table-cell">Brand</th>
            <th>Price</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $product)
          <tr>
            <td class="text-muted">{{ $products->firstItem() + $loop->index }}</td>

            <td>
              <div class="d-flex align-items-center gap-3">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="nx-avatar">
                <div>
                  <div class="fw-semibold">{{ $product->name }}</div>
                  <div class="text-muted small">{{ $product->sku }}</div>
                </div>
              </div>
            </td>

            <td class="d-none d-md-table-cell">
              <span class="nx-code">{{ $product->sku }}</span>
            </td>

            <td class="d-none d-lg-table-cell">
              @if($product->category)
                <span class="nx-code">{{ $product->category->name }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>

            <td class="d-none d-lg-table-cell">
              @if($product->brand)
                <span class="nx-code">{{ $product->brand->name }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>

            <td>
              <div class="text-end">
                <div class="fw-semibold">${{ number_format($product->selling_price, 2) }}</div>
                <div class="text-muted small">Cost: ${{ number_format($product->cost_price, 2) }}</div>
              </div>
            </td>

            <td>
              @if($product->status == 'active')
                <span class="badge bg-success">Active</span>
              @else
                <span class="badge bg-danger">Inactive</span>
              @endif
            </td>

            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @can('products.edit')
                  <a href="{{ route('admin.products.edit', $product->id) }}" class="nx-action"
                     data-tip="Edit" aria-label="Edit {{ $product->name }}">
                    <i class="fas fa-pen"></i>
                  </a>
                @endcan

                @can('products.delete')
                  <form action="{{ route('admin.products.delete', $product->id) }}" method="POST"
                        data-confirm="This will permanently delete {{ $product->name }}. This cannot be undone."
                        data-confirm-title="Delete product?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nx-action danger" data-tip="Delete" aria-label="Delete {{ $product->name }}">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center py-5">
              <i class="fas fa-box-open fs-3 d-block mb-2 text-muted"></i>
              <div class="fw-semibold">No products found</div>
              <div class="text-muted small">
                {{ $activeFilters ? 'Try changing or clearing the filters.' : 'Add your first product to get started.' }}
              </div>
              @if($activeFilters)
                <a href="{{ route('admin.products.list') }}" class="btn btn-light btn-sm mt-3">Clear filters</a>
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="nx-footer-bar">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <small class="text-muted">
          @if($products->total())
            Showing <strong>{{ $products->firstItem() }}-{{ $products->lastItem() }}</strong> of <strong>{{ $products->total() }}</strong> products
          @else
            0 results
          @endif
        </small>
        <form method="GET" action="{{ route('admin.products.list') }}" class="d-flex align-items-center gap-2" data-filter>
          @foreach(request()->except(['per_page', 'page']) as $k => $v)
            @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
          @endforeach
          <label class="small text-muted mb-0" for="per_page">Rows</label>
          <select id="per_page" name="per_page" class="form-select form-select-sm w-auto" onchange="nxSubmit(this.form)">
            @foreach([10, 25, 50] as $n)
              <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
            @endforeach
          </select>
        </form>
      </div>
      @if($products->hasPages()) {{ $products->links() }} @endif
    </div>
  </div>
</div>
@endsection

@extends('layout.master')

@section('content')
@php
    $activeFilters = collect(['q', 'parent_id', 'status'])
        ->filter(fn ($k) => request()->filled($k))->count();
@endphp
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Inventory</small>Categories</h1>
      <p class="nx-sub">Manage product categories</p>
    </div>
    @can('categories.create')
      <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add category
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>

    <form method="GET" action="{{ route('admin.categories.list') }}" class="nx-toolbar" data-filter>
      <div class="nx-search">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Search name or slug" aria-label="Search categories">
      </div>

      <select name="parent_id" class="form-select" aria-label="Filter by parent" onchange="nxSubmit(this.form)">
        <option value="">All categories</option>
        @foreach($parents as $parent)
          <option value="{{ $parent->id }}" @selected(request('parent_id') == $parent->id)>{{ $parent->name }}</option>
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
        <a href="{{ route('admin.categories.list') }}" class="btn btn-link">Reset</a>
      @endif
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th class="nx-col-num">#</th>
            <th>Category</th>
            <th class="d-none d-md-table-cell">Parent</th>
            <th class="d-none d-lg-table-cell">Products</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $category)
          <tr>
            <td class="text-muted">{{ $categories->firstItem() + $loop->index }}</td>

            <td>
              <div class="fw-semibold">{{ $category->name }}</div>
              <div class="text-muted small">{{ $category->slug }}</div>
            </td>

            <td class="d-none d-md-table-cell">
              @if($category->parent)
                <span class="nx-code">{{ $category->parent->name }}</span>
              @else
                <span class="text-muted">Root</span>
              @endif
            </td>

            <td class="d-none d-lg-table-cell">
              <span class="nx-stat-num">{{ $category->product_count }}</span>
            </td>

            <td>
              @if($category->status == 'active')
                <span class="badge bg-success">Active</span>
              @else
                <span class="badge bg-danger">Inactive</span>
              @endif
            </td>

            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @can('categories.edit')
                  <a href="{{ route('admin.categories.edit', $category->id) }}" class="nx-action"
                     data-tip="Edit" aria-label="Edit {{ $category->name }}">
                    <i class="fas fa-pen"></i>
                  </a>
                @endcan

                @can('categories.delete')
                  <form action="{{ route('admin.categories.delete', $category->id) }}" method="POST"
                        data-confirm="This will permanently delete {{ $category->name }}. This cannot be undone."
                        data-confirm-title="Delete category?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nx-action danger" data-tip="Delete" aria-label="Delete {{ $category->name }}">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-5">
              <i class="fas fa-folder-open fs-3 d-block mb-2 text-muted"></i>
              <div class="fw-semibold">No categories found</div>
              <div class="text-muted small">
                {{ $activeFilters ? 'Try changing or clearing the filters.' : 'Add your first category to get started.' }}
              </div>
              @if($activeFilters)
                <a href="{{ route('admin.categories.list') }}" class="btn btn-light btn-sm mt-3">Clear filters</a>
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
          @if($categories->total())
            Showing <strong>{{ $categories->firstItem() }}-{{ $categories->lastItem() }}</strong> of <strong>{{ $categories->total() }}</strong> categories
          @else
            0 results
          @endif
        </small>
        <form method="GET" action="{{ route('admin.categories.list') }}" class="d-flex align-items-center gap-2" data-filter>
          @foreach(request()->except(['per_page', 'page']) as $k => $v)
            @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
          @endforeach
          <label class="small text-muted mb-0" for="per_page">Rows</label>
          <select id="per_page" name="per_page" class="form-select form-select-sm w-auto" onchange="nxSubmit(this.form)">
            @foreach([10, 25, 50] as $n)
              <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
            @endforeach
          </@foreach([10, 25, 50] as $n)
              <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
            @endforeach
          </select>
        </form>
      </div>
      @if($categories->hasPages()) {{ $categories->links() }} @endif
    </div>
  </div>
</div>
@endsection

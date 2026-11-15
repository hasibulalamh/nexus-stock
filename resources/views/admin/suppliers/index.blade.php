@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Procurement</small>Suppliers</h1></div>
    @can('suppliers.create')
      <a class="btn btn-primary" href="{{ route('admin.suppliers.create') }}">New supplier</a>
    @endcan
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col-md-4"><input name="q" class="form-control" placeholder="Name, code or phone" value="{{ request('q') }}"></div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <option value="active" @selected(request('status') === 'active')>Active</option>
          <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
      </div>
      <div class="col-md-1"><button class="btn btn-primary">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Name</th><th>Code</th><th>Contact</th><th>Phone</th><th>Email</th><th>Purchase orders</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($suppliers as $supplier)
          <tr>
            <td>{{ $supplier->name }}</td>
            <td>{{ $supplier->code }}</td>
            <td>{{ $supplier->contact_person ?: '—' }}</td>
            <td>{{ $supplier->phone ?: '—' }}</td>
            <td>{{ $supplier->email ?: '—' }}</td>
            <td>{{ $supplier->purchase_orders_count }}</td>
            <td><span class="badge {{ $supplier->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($supplier->status) }}</span></td>
            <td class="d-flex gap-2">
              @can('suppliers.edit')
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.suppliers.edit', $supplier) }}">Edit</a>
              @endcan
              @can('suppliers.delete')
                <form method="POST" action="{{ route('admin.suppliers.delete', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              @endcan
            </td>
          </tr>
        @empty
          <tr><td colspan="8">No suppliers found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $suppliers->links('pagination::bootstrap-5') }}
  </div></div>
</div>
@endsection

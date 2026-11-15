@extends('layout.master')

@section('content')
@php($editing = $order->exists)
@php($lines = $oldLines ?: ($editing ? $order->lines->map(fn ($line) => ['product_id' => $line->product_id, 'ordered_qty' => $line->ordered_qty, 'unit_cost' => $line->unit_cost])->all() : [[]]))
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Purchase orders</small>{{ $editing ? 'Edit '.$order->po_no : 'New purchase order' }}</h1></div>
    <a class="btn btn-outline-secondary" href="{{ $editing ? route('admin.purchases.show', $order) : route('admin.purchases.index') }}">Back</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ $editing ? route('admin.purchases.update', $order) : route('admin.purchases.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <select name="supplier_id" class="form-select">
            <option value="">Select supplier</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
          </select>
          @error('supplier_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Warehouse</label>
          <select name="warehouse_id" class="form-select" @disabled($editing)>
            <option value="">Select warehouse</option>
            @foreach($warehouses as $warehouse)
              <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
          </select>
          @if($editing)<input type="hidden" name="warehouse_id" value="{{ $order->warehouse_id }}">@endif
          @error('warehouse_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Expected date</label>
          <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date', $order->expected_date?->format('Y-m-d')) }}">
          @error('expected_date')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes', $order->notes) }}</textarea>
          @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Lines</h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-po-line">Add line</button>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th style="width:45%">Product</th><th>Ordered qty</th><th>Unit cost</th><th></th></tr></thead>
          <tbody id="po-lines">
            @foreach($lines as $i => $line)
              @include('admin.purchases._line', ['i' => $i, 'line' => $line])
            @endforeach
          </tbody>
        </table>
      </div>
      <select id="po-product-source" class="d-none">
        @foreach($products as $product)
          <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
      </select>
    </div></div>

    <div class="d-flex gap-2 mb-4">
      <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create purchase order' }}</button>
      <a class="btn btn-outline-secondary" href="{{ route('admin.purchases.index') }}">Cancel</a>
    </div>
  </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var body = document.getElementById('po-lines');
  var source = document.getElementById('po-product-source');
  var nextIndex = {{ count($lines) }};

  function bindRow(row) {
    var remove = row.querySelector('.remove-line');
    if (remove) {
      remove.addEventListener('click', function () {
        if (body.querySelectorAll('tr.po-line').length > 1) { row.remove(); }
      });
    }
  }

  function buildRow(index) {
    var row = document.createElement('tr');
    row.className = 'po-line';
    row.innerHTML = '<td><select name="lines[' + index + '][product_id]" class="form-select line-product"><option value="">Select product</option></select><div class="text-danger small"></div></td>'
      + '<td><input type="number" step="0.001" min="0.001" class="form-control" name="lines[' + index + '][ordered_qty]"></td>'
      + '<td><input type="number" step="0.01" min="0" class="form-control" name="lines[' + index + '][unit_cost]" value="0.00"></td>'
      + '<td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>';
    var select = row.querySelector('select');
    Array.prototype.forEach.call(source.options, function (opt) { select.appendChild(opt.cloneNode(true)); });
    bindRow(row);
    return row;
  }

  Array.prototype.forEach.call(body.querySelectorAll('tr.po-line'), bindRow);
  var add = document.getElementById('add-po-line');
  if (add) { add.addEventListener('click', function () { body.appendChild(buildRow(nextIndex++)); }); }
});
</script>
@endsection

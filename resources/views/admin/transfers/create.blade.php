@extends('layout.master')

@section('content')
@php($lines = $oldLines ?: ($prefill['product_id'] ? [['product_id' => $prefill['product_id']]] : [[]]))
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Transfers</small>{{ $type === 'return' ? 'New return' : 'New transfer' }}</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.transfers.index') }}">Back to list</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.transfers.store') }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    @if($prefill['stock_alert_id'])
      <input type="hidden" name="stock_alert_id" value="{{ $prefill['stock_alert_id'] }}">
    @endif

    <div class="card mb-3"><div class="card-body">
      <h2 class="h5 mb-3">Route</h2>
      <div class="row g-3">
        @if($type === 'return')
          <div class="col-md-4">
            <label class="form-label">Returning branch</label>
            <select name="from_id" class="form-select">
              <option value="">Select branch</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('from_id', $prefill['from_id'] ?: '') === (string) $branch->id)>{{ $branch->name }} &rarr; {{ $branch->warehouse?->name ?? 'No warehouse' }}</option>
              @endforeach
            </select>
            @error('from_id')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Reason</label>
            <select name="reason" class="form-select">
              <option value="">Select reason</option>
              @foreach($returnReasons as $reason)
                <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst(str_replace('_', ' ', $reason)) }}</option>
              @endforeach
            </select>
            @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-5"><div class="alert alert-info mb-0">The stock returns to the warehouse that supplies the selected branch.</div></div>
        @else
          <div class="col-md-4">
            <label class="form-label">From warehouse</label>
            <select name="from_id" class="form-select" id="from-warehouse">
              <option value="">Select warehouse</option>
              @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('from_id', $prefill['from_id'] ?: '') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
              @endforeach
            </select>
            @error('from_id')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">To branch</label>
            <select name="to_id" class="form-select" id="to-branch">
              <option value="">Select branch</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" data-warehouse="{{ $branch->warehouse_id }}" @selected((string) old('to_id', $prefill['to_id'] ?: '') === (string) $branch->id)>{{ $branch->name }}</option>
              @endforeach
            </select>
            @error('to_id')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4"><div class="form-text mt-4">Only branches supplied by the selected warehouse can be chosen; the server validates the pair.</div></div>
        @endif
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
          @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="form-text mt-2">Serial units for the selected source location are listed per line. Reload the page after changing the source location.</div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Product lines</h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-transfer-line">Add line</button>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th style="width:30%">Product</th><th style="width:15%">Quantity</th><th>Serial units</th><th></th></tr></thead>
          <tbody id="transfer-lines">
            @foreach($lines as $i => $line)
              @include('admin.transfers._line', ['i' => $i, 'line' => $line])
            @endforeach
          </tbody>
        </table>
      </div>
      <select id="product-options-source" class="d-none">
        @foreach($products as $product)
          <option value="{{ $product->id }}" data-tracking="{{ $product->tracking_type }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
      </select>
      <select id="unit-options-source" class="d-none" multiple>
        @foreach($availableUnits as $unit)
          <option value="{{ $unit->id }}" data-product-id="{{ $unit->product_id }}">{{ $unit->product?->name }} &mdash; {{ $unit->serial_no ?: $unit->unit_code }} [{{ $unit->unit_code }}]</option>
        @endforeach
      </select>
    </div></div>

    <div class="d-flex gap-2 mb-4">
      <button class="btn btn-primary">Save draft</button>
      <a class="btn btn-outline-secondary" href="{{ route('admin.transfers.index') }}">Cancel</a>
    </div>
  </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var body = document.getElementById('transfer-lines');
  var productSource = document.getElementById('product-options-source');
  var unitSource = document.getElementById('unit-options-source');
  var nextIndex = {{ count($lines) }};

  function bindRow(row) {
    var product = row.querySelector('.line-product');
    var serial = row.querySelector('.line-units');
    var qty = row.querySelector('.line-qty');

    function syncUnits() {
      if (!product || !serial) { return; }
      var option = product.options[product.selectedIndex];
      var tracking = option ? option.getAttribute('data-tracking') : '';
      var isSerial = tracking === 'serial';
      serial.closest('td').style.display = isSerial ? '' : 'none';
      if (qty) { qty.closest('td').style.display = isSerial ? 'none' : ''; }
      var id = product.value;
      Array.prototype.forEach.call(serial.options, function (opt) {
        var match = opt.getAttribute('data-product-id') === id;
        opt.hidden = !match;
        opt.disabled = !match;
        if (!match) { opt.selected = false; }
      });
    }

    if (product) { product.addEventListener('change', syncUnits); }
    syncUnits();
    var remove = row.querySelector('.remove-line');
    if (remove) {
      remove.addEventListener('click', function () {
        if (body.querySelectorAll('tr.transfer-line').length > 1) { row.remove(); }
      });
    }
  }

  function buildRow(index) {
    var row = document.createElement('tr');
    row.className = 'transfer-line';
    row.innerHTML = '<td><select name="lines[' + index + '][product_id]" class="form-select line-product"><option value="">Select product</option></select><div class="text-danger small"></div></td>'
      + '<td><input type="number" step="0.001" min="0" class="form-control line-qty" name="lines[' + index + '][requested_qty]"></td>'
      + '<td><select multiple size="4" class="form-select line-units" name="lines[' + index + '][unit_ids][]"></select><div class="form-text">Serial products only.</div></td>'
      + '<td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>';
    var product = row.querySelector('.line-product');
    var unitSelect = row.querySelector('.line-units');
    Array.prototype.forEach.call(productSource.options, function (opt) {
      product.appendChild(opt.cloneNode(true));
    });
    Array.prototype.forEach.call(unitSource.options, function (opt) {
      unitSelect.appendChild(opt.cloneNode(true));
    });
    bindRow(row);
    return row;
  }

  Array.prototype.forEach.call(body.querySelectorAll('tr.transfer-line'), bindRow);
  var add = document.getElementById('add-transfer-line');
  if (add) {
    add.addEventListener('click', function () {
      body.appendChild(buildRow(nextIndex++));
    });
  }

  var warehouse = document.getElementById('from-warehouse');
  var branch = document.getElementById('to-branch');
  if (warehouse && branch) {
    warehouse.addEventListener('change', function () {
      var id = warehouse.value;
      Array.prototype.forEach.call(branch.options, function (opt) {
        if (!opt.value) { return; }
        var match = !id || opt.getAttribute('data-warehouse') === id;
        opt.hidden = !match;
        opt.disabled = !match;
        if (!match) { opt.selected = false; }
      });
    });
  }
});
</script>
@endsection

@extends('layout.master')

@section('content')
@php($lines = $oldLines ?: [[]])
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Adjustments</small>New adjustment</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.adjustments.index') }}">Back</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first() }}</div>
  @endif

  <div class="card mb-3"><div class="card-body">
    <form method="GET" action="{{ route('admin.adjustments.create') }}" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label">Location type</label>
        <select name="location_type" class="form-select">
          <option value="warehouse" @selected($locationType === 'warehouse')>Warehouse</option>
          <option value="branch" @selected($locationType === 'branch')>Branch</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Location</label>
        <select name="location_id" class="form-select">
          <option value="">Select location</option>
          @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($locationType === 'warehouse' && $locationId === $warehouse->id)>Warehouse: {{ $warehouse->name }}</option>@endforeach
          @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($locationType === 'branch' && $locationId === $branch->id)>Branch: {{ $branch->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-outline-primary">Load serial units</button></div>
      <div class="col-md-4"><div class="form-text">Loading lists the in-stock serial units for the selected location.</div></div>
    </form>
  </div></div>

  <form method="POST" action="{{ route('admin.adjustments.store') }}">
    @csrf
    <input type="hidden" name="location_type" value="{{ $locationType }}">
    <input type="hidden" name="location_id" value="{{ $locationId }}">

    <div class="card mb-3"><div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Reason</label>
          <select name="reason" class="form-select">
            <option value="">Select reason</option>
            @foreach($reasons as $reason)
              <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst(str_replace('_', ' ', $reason)) }}</option>
            @endforeach
          </select>
          @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
          <label class="form-label">Notes</label>
          <input name="notes" class="form-control" value="{{ old('notes') }}">
          @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Lines</h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-adjustment-line">Add line</button>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th style="width:28%">Product</th><th>Delta (+/-)</th><th>Serial units out</th><th>Serial numbers in</th><th></th></tr></thead>
          <tbody id="adjustment-lines">
            @foreach($lines as $i => $line)
              <tr class="adjustment-line">
                <td>
                  <select name="lines[{{ $i }}][product_id]" class="form-select line-product">
                    <option value="">Select product</option>
                    @foreach($products as $product)
                      <option value="{{ $product->id }}" data-tracking="{{ $product->tracking_type }}" @selected((string) old("lines.$i.product_id", $line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                  </select>
                  @error("lines.$i.product_id")<div class="text-danger small">{{ $message }}</div>@enderror
                </td>
                <td>
                  <input type="number" step="0.001" class="form-control" name="lines[{{ $i }}][delta]" value="{{ old("lines.$i.delta", $line['delta'] ?? '') }}" placeholder="e.g. -2.000">
                  @error("lines.$i.delta")<div class="text-danger small">{{ $message }}</div>@enderror
                </td>
                <td>
                  <select multiple size="4" class="form-select line-units" name="lines[{{ $i }}][stock_unit_ids][]">
                    @foreach($availableUnits as $unit)
                      <option value="{{ $unit->id }}" data-product-id="{{ $unit->product_id }}" @selected(in_array($unit->id, (array) old("lines.$i.stock_unit_ids", $line['stock_unit_ids'] ?? [])))>{{ $unit->serial_no ?: $unit->unit_code }}</option>
                    @endforeach
                  </select>
                  <div class="form-text">Negative serial deltas.</div>
                </td>
                <td>
                  <textarea name="lines[{{ $i }}][serial_numbers_text]" class="form-control" rows="3" placeholder="One serial per line">{{ old("lines.$i.serial_numbers_text") }}</textarea>
                  <div class="form-text">Positive serial deltas (found).</div>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <select id="adjustment-product-source" class="d-none">
        @foreach($products as $product)<option value="{{ $product->id }}" data-tracking="{{ $product->tracking_type }}">{{ $product->name }} ({{ $product->sku }})</option>@endforeach
      </select>
      <select id="adjustment-unit-source" class="d-none" multiple>
        @foreach($availableUnits as $unit)<option value="{{ $unit->id }}" data-product-id="{{ $unit->product_id }}">{{ $unit->serial_no ?: $unit->unit_code }}</option>@endforeach
      </select>
    </div></div>

    <div class="d-flex gap-2 mb-4">
      <button class="btn btn-primary" @disabled($locationId < 1)>Submit for approval</button>
      <a class="btn btn-outline-secondary" href="{{ route('admin.adjustments.index') }}">Cancel</a>
    </div>
  </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var body = document.getElementById('adjustment-lines');
  var productSource = document.getElementById('adjustment-product-source');
  var unitSource = document.getElementById('adjustment-unit-source');
  var nextIndex = {{ count($lines) }};

  function bindRow(row) {
    var product = row.querySelector('.line-product');
    var units = row.querySelector('.line-units');
    function sync() {
      if (!product || !units) { return; }
      var id = product.value;
      Array.prototype.forEach.call(units.options, function (opt) {
        var match = opt.getAttribute('data-product-id') === id;
        opt.hidden = !match;
        opt.disabled = !match;
        if (!match) { opt.selected = false; }
      });
    }
    if (product) { product.addEventListener('change', sync); }
    sync();
    var remove = row.querySelector('.remove-line');
    if (remove) { remove.addEventListener('click', function () { if (body.querySelectorAll('tr.adjustment-line').length > 1) { row.remove(); } }); }
  }

  function buildRow(index) {
    var row = document.createElement('tr');
    row.className = 'adjustment-line';
    row.innerHTML = '<td><select name="lines[' + index + '][product_id]" class="form-select line-product"><option value="">Select product</option></select></td>'
      + '<td><input type="number" step="0.001" class="form-control" name="lines[' + index + '][delta]"></td>'
      + '<td><select multiple size="4" class="form-select line-units" name="lines[' + index + '][stock_unit_ids][]"></select></td>'
      + '<td><textarea name="lines[' + index + '][serial_numbers_text]" class="form-control" rows="3"></textarea></td>'
      + '<td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>';
    var product = row.querySelector('.line-product');
    var units = row.querySelector('.line-units');
    Array.prototype.forEach.call(productSource.options, function (opt) { product.appendChild(opt.cloneNode(true)); });
    Array.prototype.forEach.call(unitSource.options, function (opt) { units.appendChild(opt.cloneNode(true)); });
    bindRow(row);
    return row;
  }

  Array.prototype.forEach.call(body.querySelectorAll('tr.adjustment-line'), bindRow);
  var add = document.getElementById('add-adjustment-line');
  if (add) { add.addEventListener('click', function () { body.appendChild(buildRow(nextIndex++)); }); }
});
</script>
@endsection

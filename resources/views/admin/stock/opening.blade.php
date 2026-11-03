@extends('layout.master')
@section('content')
<div class="container-fluid p-0"><div class="nx-page-head"><h1 class="nx-title"><small>Stock</small>Opening stock</h1></div><div class="card"><div class="card-body">
<form method="POST" action="{{ route('admin.stock.opening.store') }}" class="row g-3">@csrf
<div class="col-md-3"><label class="form-label">Location type</label><select name="location_type" class="form-select" required><option value="branch">Branch</option><option value="warehouse">Warehouse</option></select></div>
<div class="col-md-5"><label class="form-label">Location</label><select name="location_id" class="form-select" required><optgroup label="Branches">@foreach($branches as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup><optgroup label="Warehouses">@foreach($warehouses as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup></select></div>
<div class="col-md-4"><label class="form-label">Product</label><select name="product_id" id="openingProduct" class="form-select" required>@foreach($products as $product)<option value="{{ $product->id }}" data-tracking="{{ $product->tracking_type }}">{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select></div>
<div class="col-md-4" id="quantityField"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="0.001" step="0.001"></div>
<div class="col-md-8 d-none" id="serialField"><label class="form-label">Serial numbers</label><textarea name="serial_numbers" class="form-control" rows="4" placeholder="One manufacturer serial per line"></textarea><small class="text-muted">Scan or type one serial per line.</small><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="allow_system_codes" value="1" id="allowSystemCodes"><label class="form-check-label" for="allowSystemCodes">No manufacturer serial (system codes only)</label></div><div class="mt-2"><label class="form-label">System-code quantity</label><input type="number" name="quantity" class="form-control" min="1" step="1"></div></div>
<div class="col-md-4"><label class="form-label">Date</label><input type="date" name="occurred_at" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div><div class="col-12"><button class="btn btn-primary">Record opening stock</button></div></form>
</div></div></div>
<script>
document.getElementById('openingProduct')?.addEventListener('change', function () {
  var serial = this.options[this.selectedIndex].dataset.tracking === 'serial';
  document.getElementById('serialField').classList.toggle('d-none', !serial);
  document.getElementById('quantityField').classList.toggle('d-none', serial);
});
document.getElementById('openingProduct')?.dispatchEvent(new Event('change'));
</script>
@endsection

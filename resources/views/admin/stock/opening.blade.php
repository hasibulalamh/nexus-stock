@extends('layout.master')
@section('content')
<div class="container-fluid p-0"><div class="nx-page-head"><h1 class="nx-title"><small>Stock</small>Opening stock</h1></div><div class="card"><div class="card-body">
<form method="POST" action="{{ route('admin.stock.opening.store') }}" class="row g-3">@csrf
<div class="col-md-3"><label class="form-label">Location type</label><select name="location_type" class="form-select" required><option value="branch">Branch</option><option value="warehouse">Warehouse</option></select></div>
<div class="col-md-5"><label class="form-label">Location</label><select name="location_id" class="form-select" required><optgroup label="Branches">@foreach($branches as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup><optgroup label="Warehouses">@foreach($warehouses as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup></select></div>
<div class="col-md-4"><label class="form-label">Product</label><select name="product_id" class="form-select" required>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="0.001" step="0.001" required></div><div class="col-md-4"><label class="form-label">Date</label><input type="date" name="occurred_at" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div><div class="col-12"><button class="btn btn-primary">Record opening stock</button></div></form>
</div></div></div>
@endsection

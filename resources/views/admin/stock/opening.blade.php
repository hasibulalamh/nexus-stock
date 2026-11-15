@extends('layout.master')
@section('content')
<div class="container-fluid p-0">
    <div class="nx-page-head"><h1 class="nx-title"><small>Stock</small>Submit opening stock</h1></div>
    <div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.stock.opening.store') }}">@csrf
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Location type</label><select name="location_type" class="form-select" required><option value="branch">Branch</option><option value="warehouse">Warehouse</option></select></div>
                <div class="col-md-5"><label class="form-label">Location</label><select name="location_id" class="form-select" required><optgroup label="Branches">@foreach($branches as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup><optgroup label="Warehouses">@foreach($warehouses as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</optgroup></select></div>
                <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="occurred_at" class="form-control" value="{{ old('occurred_at', now()->toDateString()) }}" required></div>
                <div class="col-12"><label class="form-label">Product lines</label><div id="openingLines"></div><button type="button" class="btn btn-outline-secondary" id="addLine">Add product line</button></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary">Submit for approval</button></div>
            </div>
        </form>
    </div></div>
</div>
<template id="lineTemplate"><div class="row g-2 align-items-end mb-2 opening-line">
    <div class="col-md-4"><label class="form-label">Product</label><select class="form-select product-select" required></select></div>
    <div class="col-md-3 quantity-wrap"><label class="form-label">Quantity</label><input class="form-control quantity-input" type="number" min="0.001" step="0.001"></div>
    <div class="col-md-4 serial-wrap d-none"><label class="form-label">Serial numbers</label><textarea class="form-control serial-input" rows="2" placeholder="One manufacturer serial per line"></textarea><div class="form-check"><input class="form-check-input system-codes" type="checkbox"><label class="form-check-label">System codes only</label></div></div>
    <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-line">Remove</button></div>
</div></template>
@php($fieldErrors = $errors->toArray())
<script>
const products = @json($productOptions);
const oldLines = @json($oldLines);
const fieldErrors = @json($fieldErrors);
const lines = document.getElementById('openingLines');
function addLine(line = {}) {
    const index = lines.children.length, fragment = document.getElementById('lineTemplate').content.cloneNode(true), row = fragment.querySelector('.opening-line');
    const select = row.querySelector('.product-select');
    select.name = `lines[${index}][product_id]`;
    products.forEach(product => { const option = new Option(`${product.name} (${product.sku})`, product.id); option.dataset.tracking = product.tracking; select.add(option); });
    const quantity = row.querySelector('.quantity-input'); quantity.name = `lines[${index}][quantity]`;
    const serials = row.querySelector('.serial-input'); serials.name = `lines[${index}][serial_numbers_text]`;
    const system = row.querySelector('.system-codes'); system.name = `lines[${index}][use_system_codes]`;
    select.value = line.product_id ?? '';
    quantity.value = line.quantity ?? '';
    serials.value = line.serial_numbers_text ?? '';
    system.checked = line.use_system_codes === '1' || line.use_system_codes === true;
    function showErrors(field, element) { (fieldErrors[`lines.${index}.${field}`] || []).forEach(message => { const error = document.createElement('div'); error.className = 'text-danger small'; error.textContent = message; element.parentElement.appendChild(error); }); }
    showErrors('product_id', select); showErrors('quantity', quantity); showErrors('serial_numbers_text', serials);
    function toggle() { const serial = select.selectedOptions[0]?.dataset.tracking === 'serial'; row.querySelector('.serial-wrap').classList.toggle('d-none', !serial); row.querySelector('.quantity-wrap').classList.toggle('d-none', serial); quantity.required = !serial; serials.required = serial; }
    select.addEventListener('change', toggle); row.querySelector('.remove-line').addEventListener('click', () => row.remove()); lines.appendChild(row); toggle();
}
document.getElementById('addLine').addEventListener('click', () => addLine()); (oldLines.length ? oldLines : [{}]).forEach(addLine);
</script>
@endsection

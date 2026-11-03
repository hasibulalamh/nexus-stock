@extends('layout.master')

@section('content')
<style>
@media print {
  body * { visibility: hidden; }
  #barcodeLabels, #barcodeLabels * { visibility: visible; }
  #barcodeLabels { position: absolute; left: 0; top: 0; width: 100%; }
  .barcode-controls { display: none !important; }
}
.barcode-label { width: 50mm; min-height: 30mm; padding: 3mm; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; border: 1px dashed #adb5bd; margin: 2mm; page-break-inside: avoid; }
.barcode-label.size-40x25 { width: 40mm; min-height: 25mm; }
.barcode-label.size-a4 { width: calc(33.333% - 4mm); min-height: 32mm; margin: 2mm; }
.barcode-label svg { max-width: 100%; height: auto; }
@media print { .barcode-label.size-a4 { margin: 0; border: 0; } }
</style>
<div class="container-fluid p-0">
  <div class="nx-page-head barcode-controls">
    <div><h1 class="nx-title"><small>Products</small>Print barcodes</h1></div>
  </div>
  <form method="GET" class="card card-body barcode-controls mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label for="quantity" class="form-label">Number of labels</label><input id="quantity" name="quantity" type="number" min="1" max="200" value="{{ request('quantity', 1) }}" class="form-control"></div>
      <div class="col-md-3"><label for="size" class="form-label">Label size</label><select id="size" name="size" class="form-select">@foreach($sizes as $key => $label)<option value="{{ $key }}" @selected(($selectedSize ?? '50x30') === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Update preview</button><button type="button" class="btn btn-primary ms-2" onclick="window.print()">Print</button></div>
    </div>
  </form>
  <div id="barcodeLabels">
    @foreach($labels as $label)
      <div class="barcode-label size-{{ $selectedSize ?? '50x30' }}">
        {!! $label['svg'] !!}
        <strong>{{ $label['product']->name }}</strong>
        <span>{{ $label['product']->sku }}</span>
        <span>{{ $label['product']->barcode }}</span>
      </div>
    @endforeach
  </div>
</div>
@endsection

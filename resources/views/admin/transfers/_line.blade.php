@php($i = $i ?? 0)
@php($line = $line ?? [])
<tr class="transfer-line">
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
    <input type="number" step="0.001" min="0" class="form-control line-qty" name="lines[{{ $i }}][requested_qty]" value="{{ old("lines.$i.requested_qty", $line['requested_qty'] ?? '') }}">
    @error("lines.$i.requested_qty")<div class="text-danger small">{{ $message }}</div>@enderror
  </td>
  <td>
    <select multiple size="4" class="form-select line-units" name="lines[{{ $i }}][unit_ids][]">
      @foreach($availableUnits as $unit)
        <option value="{{ $unit->id }}" data-product-id="{{ $unit->product_id }}" @selected(in_array($unit->id, (array) old("lines.$i.unit_ids", $line['unit_ids'] ?? [])))>{{ $unit->product?->name }} &mdash; {{ $unit->serial_no ?: $unit->unit_code }} [{{ $unit->unit_code }}]</option>
      @endforeach
    </select>
    @error("lines.$i.unit_ids")<div class="text-danger small">{{ $message }}</div>@enderror
    <div class="form-text">Serial products: pick the exact units. Quantity is taken from the selection.</div>
  </td>
  <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>
</tr>

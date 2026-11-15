@php($i = $i ?? 0)
@php($line = $line ?? [])
<tr class="po-line">
  <td>
    <select name="lines[{{ $i }}][product_id]" class="form-select line-product">
      <option value="">Select product</option>
      @foreach($products as $product)
        <option value="{{ $product->id }}" @selected((string) old("lines.$i.product_id", $line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
      @endforeach
    </select>
    @error("lines.$i.product_id")<div class="text-danger small">{{ $message }}</div>@enderror
  </td>
  <td>
    <input type="number" step="0.001" min="0.001" class="form-control" name="lines[{{ $i }}][ordered_qty]" value="{{ old("lines.$i.ordered_qty", $line['ordered_qty'] ?? '') }}">
    @error("lines.$i.ordered_qty")<div class="text-danger small">{{ $message }}</div>@enderror
  </td>
  <td>
    <input type="number" step="0.01" min="0" class="form-control" name="lines[{{ $i }}][unit_cost]" value="{{ old("lines.$i.unit_cost", $line['unit_cost'] ?? '0.00') }}">
    @error("lines.$i.unit_cost")<div class="text-danger small">{{ $message }}</div>@enderror
  </td>
  <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button></td>
</tr>

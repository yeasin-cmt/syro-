<tr>
    <td>
        <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
        <input name="variants[{{ $i }}][size]" class="form-control form-control-sm" value="{{ $v['size'] ?? '' }}" placeholder="M" style="width: 80px">
    </td>
    <td><input name="variants[{{ $i }}][color]" class="form-control form-control-sm" value="{{ $v['color'] ?? '' }}" placeholder="Black" style="width: 110px"></td>
    <td>
        <input type="color" class="form-control form-control-color form-control-sm" value="{{ $v['color_hex'] ?? '#111111' }}" style="width: 44px">
        <input type="hidden" name="variants[{{ $i }}][color_hex]" value="{{ $v['color_hex'] ?? '#111111' }}">
    </td>
    <td><input name="variants[{{ $i }}][sku]" class="form-control form-control-sm" value="{{ $v['sku'] ?? '' }}" style="width: 130px"></td>
    <td><input type="number" min="0" name="variants[{{ $i }}][stock]" class="form-control form-control-sm" value="{{ $v['stock'] ?? 0 }}" style="width: 80px" required></td>
    <td><input type="number" min="0" step="0.01" name="variants[{{ $i }}][price]" class="form-control form-control-sm" value="{{ $v['price'] ?? '' }}" style="width: 110px"></td>
    <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row aria-label="Remove variant"><i class="bi bi-x-lg"></i></button></td>
</tr>

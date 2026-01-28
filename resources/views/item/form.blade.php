<!-- resources/views/item/_form.blade.php -->

@csrf

<div class="mb-3">
    <label for="name" class="form-label">Material</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name ?? '') }}" required>

    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="item_group_id" class="form-label">Materialgruppe</label>
    <select class="form-control" id="item_group_id" name="item_group_id" required>
        <option value="">Bitte wählen</option>
        @foreach($itemGroups as $group)
            <option value="{{ $group->id }}" {{ old('item_group_id', $item->item_group_id ?? '') == $group->id ? 'selected' : '' }}>
                {{ $group->name }}
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label for="stock" class="form-label">Bestand</label>
    <input type="number" class="form-control" id="stock" name="stock" value="{{ old('stock', $item->stock ?? 0) }}" required>

    @error('stock')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="repair" class="form-label">Reparatur</label>
    <input type="number" class="form-control" id="repair" name="repair"  value="{{ old('repair', $item->repair ?? 0) }}" required>

    @error('repair')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<br />

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>

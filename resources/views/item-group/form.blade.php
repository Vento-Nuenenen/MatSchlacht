<!-- resources/views/item-group/form.blade.php -->

@csrf

<div class="mb-3">
    <label for="name" class="form-label">Bezeichnung</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $itemgroup->name ?? '') }}" required>

    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<input type="hidden" name="position" value="{{ $itemgroup->position ?? 0 }}" />

<br />

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>

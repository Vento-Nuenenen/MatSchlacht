<!-- resources/views/users/_form.blade.php -->

@csrf

<div class="mb-3">
    <label for="name" class="form-label">Einheit</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $unit->name ?? '') }}" required>

    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">Beschreibung</label>
    <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $unit->description ?? '') }}">

    @error('description')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<br />

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>

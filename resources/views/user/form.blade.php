<!-- resources/views/users/_form.blade.php -->

@csrf

<div class="mb-3">
    <label for="scoutname" class="form-label">Pfadiname</label>
    <input type="text" class="form-control" id="scoutname" name="scoutname" value="{{ old('scoutname', $user->scoutname ?? '') }}" required>

    @error('scoutname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="firstname" class="form-label">Vorname</label>
    <input type="text" class="form-control" id="firstname" name="firstname" value="{{ old('firstname', $user->firstname ?? '') }}" required>

    @error('firstname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="lastname" class="form-label">Nachname</label>
    <input type="text" class="form-control" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname ?? '') }}" required>

    @error('lastname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">E-Mail</label>
    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>

    @error('email')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-check">
    <input type="checkbox" class="form-check-input" id="aktiv" name="aktiv" {{ old('aktiv', $user->aktiv ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="aktiv">Aktiv</label>

    @error('aktiv')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<br />

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>

<!-- resources/views/users/_form.blade.php -->

@csrf

<div class="mb-3">
    <label for="pfadiname" class="form-label">Pfadiname</label>
    <input type="text" class="form-control" id="pfadiname" name="pfadiname" value="{{ old('pfadiname', $user->pfadiname ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="vorname" class="form-label">Vorname</label>
    <input type="text" class="form-control" id="vorname" name="vorname" value="{{ old('vorname', $user->vorname ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="nachname" class="form-label">Nachname</label>
    <input type="text" class="form-control" id="nachname" name="nachname" value="{{ old('nachname', $user->nachname ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="email" class="form-label">E-Mail</label>
    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
</div>
<div class="form-check">
    <input type="checkbox" class="form-check-input" id="aktiv" name="aktiv" value="1" {{ old('aktiv', $user->aktiv ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="aktiv">Aktiv</label>
</div>
<br />
<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>

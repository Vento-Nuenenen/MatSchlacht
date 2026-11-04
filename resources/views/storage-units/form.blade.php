@csrf

<input type="hidden" name="level" value="{{ $level->value }}">

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">
        <i class="fa {{ $level->icon() }} me-1"></i> {{ $level->label() }}
    </div>
    <div class="card-body">
        @if($level->parentLevel())
            <div class="mb-3">
                <label for="parent_id" class="form-label fw-semibold">{{ $level->parentLevel()->label() }}</label>
                <select class="form-select" id="parent_id" name="parent_id" required>
                    <option value="">Bitte wählen</option>
                    @foreach($parentOptions as $id => $path)
                        <option value="{{ $id }}" @selected(old('parent_id', $storageUnit->parent_id) == $id)>{{ $path }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($level->holdsStock())
            <p class="text-muted small mb-3">
                @if($storageUnit->exists)
                    Bezeichnung: <code>{{ $storageUnit->name }}</code>.
                @endif
                Die Bezeichnung entsteht automatisch aus den Codes der übergeordneten Ebenen und einer laufenden Nummer.
            </p>
        @else
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-8">
                    <label for="name" class="form-label fw-semibold">Bezeichnung</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name', $storageUnit->name) }}" required autofocus maxlength="255">
                </div>
                <div class="col-12 col-md-4">
                    <label for="code" class="form-label fw-semibold">Code <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" class="form-control text-uppercase" id="code" name="code"
                           value="{{ old('code', $storageUnit->code) }}" maxlength="{{ \App\Services\StorageCodeGenerator::MAX_LENGTH }}"
                           aria-describedby="code-help">
                    <div id="code-help" class="form-text">Leer lassen = automatisch aus der Bezeichnung.</div>
                </div>
            </div>
        @endif

        <div class="mb-0">
            <label for="description" class="form-label fw-semibold">Beschreibung <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $storageUnit->description) }}</textarea>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ $storageUnit->exists ? route('storage-units.show', $storageUnit) : ($storageUnit->parent_id ? route('storage-units.show', $storageUnit->parent_id) : route('storage-units.index')) }}"
       class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>

@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">Für wen gilt die Regel?</div>
    <div class="card-body">
        <p class="text-muted small">
            Alle ausgefüllten Felder müssen zutreffen. Gruppe, Rolle und Berechtigung müssen zur selben Hitobito-Rolle einer Person gehören.
            Für mehrere Möglichkeiten legst du mehrere Regeln an.
        </p>
        <div class="row g-3">
            @foreach([
                'hitobito_person_id' => ['Personen-ID', 'Die Zahl in der Adresse des Hitobito-Profils.'],
                'hitobito_group_id' => ['Gruppen-ID', 'Die Zahl in der Adresse der Hitobito-Gruppe.'],
                'hitobito_role_class' => ['Hitobito-Rolle', 'Der technische Rollentyp (role_class) aus dem Hitobito-Profil.'],
                'hitobito_permission' => ['Hitobito-Berechtigung', 'Zum Beispiel layer_and_below_full oder group_read.'],
            ] as $field => [$label, $help])
                <div class="col-12 col-md-6">
                    <label for="{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
                    <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}"
                           value="{{ old($field, $hitobitoAccessRule->$field ?? '') }}" aria-describedby="{{ $field }}-help">
                    <div id="{{ $field }}-help" class="form-text">{{ $help }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">Was bewirkt die Regel?</div>
    <div class="card-body">
        <div class="form-check form-switch mb-3">
            <input type="hidden" name="grants_access" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="grants_access" name="grants_access" value="1"
                   aria-describedby="grants_access-help" @checked(old('grants_access', $hitobitoAccessRule->grants_access ?? true))>
            <label class="form-check-label fw-semibold" for="grants_access">Zugang erlauben</label>
            <div id="grants_access-help" class="form-text">
                Ausgeschaltet vergibt die Regel nur die App-Rolle. Anmelden kann sich die Person dann nur, wenn eine andere Regel den Zugang erlaubt.
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="role_name" class="form-label fw-semibold">App-Rolle</label>
                <select class="form-select" id="role_name" name="role_name">
                    <option value="">Keine Rolle vergeben</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role_name', $hitobitoAccessRule->role_name ?? '') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label for="description" class="form-label fw-semibold">Beschreibung <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" class="form-control" id="description" name="description"
                       value="{{ old('description', $hitobitoAccessRule->description ?? '') }}" maxlength="255">
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('hitobito-access-rules.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>

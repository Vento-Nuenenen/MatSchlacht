@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <h2 class="mb-3">Einstellungen: App-Komponenten</h2>

                <form method="POST" action="{{ route('settings.components.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        @foreach($components as $key => $component)
                            @php($isEnabled = (bool) old("components.$key.enabled", $component['enabled']))
                            <div class="col-12 {{ $component['toggleable'] ? 'col-lg-6' : '' }}">
                                <div class="card shadow-sm h-100" data-component="{{ $key }}">
                                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                                        <label class="h6 mb-0" for="component-{{ $key }}">{{ $component['label'] }}</label>
                                        @if($component['toggleable'])
                                            <div class="form-check form-switch mb-0">
                                                <input type="hidden" name="components[{{ $key }}][enabled]" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="component-{{ $key }}" name="components[{{ $key }}][enabled]" value="1"
                                                       @checked($isEnabled)>
                                            </div>
                                        @elseif($key === 'file_storage')
                                            <button type="submit" form="file-storage-test-form" class="btn btn-outline-secondary btn-sm"
                                                    title="Testet die gespeicherten Einstellungen">
                                                <i class="fa fa-plug me-1"></i> Verbindung testen
                                            </button>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-0">{{ $component['description'] }}</p>

                                        <div class="row g-3 mt-0">
                                            @foreach(collect($component['settings'])->reject(fn ($setting) => $setting['hidden'] ?? false) as $settingKey => $setting)
                                                @php($fieldName = "components[$key][settings][$settingKey]")
                                                @php($oldKey = "components.$key.settings.$settingKey")
                                                @php($fieldId = "setting-$key-$settingKey")
                                                <div class="{{ isset($setting['group']) && ! ($setting['multiline'] ?? false) && $setting['type'] !== 'bool' ? 'col-12 col-md-6' : 'col-12' }}"
                                                     @isset($setting['group']) data-setting-group="{{ $setting['group'] }}" @endisset>
                                                    @if($setting['type'] === 'bool')
                                                        <div class="form-check">
                                                            <input type="hidden" name="{{ $fieldName }}" value="0">
                                                            <input class="form-check-input" type="checkbox" id="{{ $fieldId }}"
                                                                   name="{{ $fieldName }}" value="1"
                                                                   @checked((bool) old($oldKey, $setting['value']))>
                                                            <label class="form-check-label" for="{{ $fieldId }}">{{ $setting['label'] }}</label>
                                                        </div>
                                                    @elseif($setting['type'] === 'select')
                                                        <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>
                                                        <select class="form-select" id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                                data-group-select="{{ $key }}">
                                                            @foreach($setting['options'] as $optionValue => $optionLabel)
                                                                <option value="{{ $optionValue }}" @selected(old($oldKey, $setting['value']) === $optionValue)>{{ $optionLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($setting['type'] === 'color')
                                                        <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="color" class="form-control form-control-color"
                                                                   id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ old($oldKey, $setting['value']) }}"
                                                                   oninput="this.nextElementSibling.textContent = this.value">
                                                            <code>{{ old($oldKey, $setting['value']) }}</code>
                                                        </div>
                                                    @elseif($setting['type'] === 'secret')
                                                        <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>
                                                        @if($setting['multiline'] ?? false)
                                                            <textarea class="form-control font-monospace small" id="{{ $fieldId }}"
                                                                      name="{{ $fieldName }}" rows="4" autocomplete="off"
                                                                      placeholder="{{ $setting['isSet'] ? 'Gespeichert – leer lassen, um ihn zu behalten' : '-----BEGIN OPENSSH PRIVATE KEY-----' }}"></textarea>
                                                        @else
                                                            <input type="password" class="form-control" id="{{ $fieldId }}"
                                                                   name="{{ $fieldName }}" autocomplete="new-password"
                                                                   placeholder="{{ $setting['isSet'] ? '•••••••• gespeichert – leer lassen, um es zu behalten' : 'nicht gesetzt' }}">
                                                        @endif
                                                    @else
                                                        <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>
                                                        <input type="{{ $setting['type'] === 'integer' ? 'number' : 'text' }}"
                                                               class="form-control"
                                                               id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                               value="{{ old($oldKey, $setting['value']) }}">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($key === 'hitobito_login')
                                            <p class="small text-muted mt-3 mb-1">
                                                In Hitobito unter Einstellungen › OAuth eine Applikation mit dem Scope <code>with_roles</code> anlegen. Als Redirect-URI eintragen:
                                            </p>
                                            <code class="small user-select-all">{{ route('hitobito.callback') }}</code>
                                        @endif

                                        @if($key === 'file_storage')
                                            <p class="small text-muted mt-3 mb-0" data-setting-group="sharepoint">
                                                SharePoint / OneDrive nutzt die Azure-App aus der .env (AZURE_TENANT_ID, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET).
                                                Sie braucht die Anwendungsberechtigung <code>Sites.ReadWrite.All</code> oder <code>Files.ReadWrite.All</code>.
                                                Die Drive-ID findet man z. B. im Graph Explorer unter <code>GET /sites/{host}:/sites/{site}:/drives</code>.
                                            </p>
                                            @foreach($oauthProviders as $oauth)
                                                <div class="mt-3 p-3 bg-light border rounded-3" data-setting-group="{{ $oauth['provider']->value }}">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                        <span>
                                                            @if($oauth['isConnected'])
                                                                <span class="badge bg-success"><i class="fa fa-check me-1"></i>Verbunden</span>
                                                            @else
                                                                <span class="badge bg-secondary">Nicht verbunden</span>
                                                            @endif
                                                        </span>
                                                        <a href="{{ route('settings.file-storage.connect', $oauth['provider']) }}" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-link me-1"></i>
                                                            {{ $oauth['isConnected'] ? 'Neu verbinden' : 'Mit '.($oauth['provider'] === \App\Enums\FileStorageProvider::GoogleDrive ? 'Google' : 'Dropbox').' verbinden' }}
                                                        </a>
                                                    </div>
                                                    <p class="small text-muted mt-2 mb-1">
                                                        Zuerst App-Zugangsdaten eintragen und speichern, dann verbinden.
                                                        @if($oauth['provider'] === \App\Enums\FileStorageProvider::GoogleDrive)
                                                            In der Google Cloud Console einen OAuth-Client vom Typ «Webanwendung» anlegen, die Drive API aktivieren und den OAuth-Zustimmungsbildschirm auf «In Produktion» stellen (sonst läuft die Verbindung nach 7 Tagen ab).
                                                        @else
                                                            In der Dropbox App Console eine App mit Zugriff «App folder» und den Berechtigungen files.content.read/write und files.metadata.read anlegen.
                                                        @endif
                                                        Als Redirect-URI eintragen:
                                                    </p>
                                                    <code class="small user-select-all">{{ $oauth['redirectUri'] }}</code>
                                                </div>
                                            @endforeach
                                            <p class="small text-muted mt-3 mb-0" data-setting-group="nextcloud">
                                                Für Nextcloud ein App-Passwort unter Persönliche Einstellungen › Sicherheit erstellen; das normale Passwort funktioniert bei aktivierter Zwei-Faktor-Anmeldung nicht.
                                            </p>
                                            <p class="small text-muted mt-3 mb-0" data-setting-group="local">
                                                Die Dateien liegen auf dem Server unter <code>storage/app/private/attachments</code>. Diesen Ordner ins Backup aufnehmen.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>

                <form id="file-storage-test-form" method="POST" action="{{ route('settings.file-storage.test') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
@endsection

@section('assets-js')
    <script>
        // Only show the settings of the selected provider, e.g. the SFTP fields while SFTP is chosen.
        document.querySelectorAll('[data-group-select]').forEach(select => {
            const card = select.closest('[data-component]');
            const update = () => card.querySelectorAll('[data-setting-group]').forEach(field => {
                field.classList.toggle('d-none', field.dataset.settingGroup !== select.value);
            });
            select.addEventListener('change', update);
            update();
        });
    </script>
@endsection

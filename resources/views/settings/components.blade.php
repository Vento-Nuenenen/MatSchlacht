@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                @include('components.status-alerts')

                <h2 class="mb-3">Einstellungen: App-Komponenten</h2>

                <form method="POST" action="{{ route('settings.components.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        @foreach($components as $key => $component)
                            @php($isEnabled = (bool) old("components.$key.enabled", $component['enabled']))
                            <div class="col-12 col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                                        <label class="h6 mb-0" for="component-{{ $key }}">{{ $component['label'] }}</label>
                                        <div class="form-check form-switch mb-0">
                                            <input type="hidden" name="components[{{ $key }}][enabled]" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="component-{{ $key }}" name="components[{{ $key }}][enabled]" value="1"
                                                   @checked($isEnabled)>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-0">{{ $component['description'] }}</p>

                                        @foreach($component['settings'] as $settingKey => $setting)
                                            @php($fieldName = "components[$key][settings][$settingKey]")
                                            @php($oldKey = "components.$key.settings.$settingKey")
                                            @php($fieldId = "setting-$key-$settingKey")
                                            <div class="mt-3">
                                                @if($setting['type'] === 'bool')
                                                    <div class="form-check">
                                                        <input type="hidden" name="{{ $fieldName }}" value="0">
                                                        <input class="form-check-input" type="checkbox" id="{{ $fieldId }}"
                                                               name="{{ $fieldName }}" value="1"
                                                               @checked((bool) old($oldKey, $setting['value']))>
                                                        <label class="form-check-label" for="{{ $fieldId }}">{{ $setting['label'] }}</label>
                                                    </div>
                                                @else
                                                    <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $setting['label'] }}</label>
                                                    <input type="text"
                                                           class="form-control @error($oldKey) is-invalid @enderror"
                                                           id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                           value="{{ old($oldKey, $setting['value']) }}">
                                                    @error($oldKey)
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

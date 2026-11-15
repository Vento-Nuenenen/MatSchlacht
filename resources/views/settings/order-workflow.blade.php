@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <h2 class="mb-2">{{ __('Einstellungen: Bestellablauf') }}</h2>
                <p class="text-muted">
                    {{ __('Legt fest, welche Statuswechsel einer Bestellung es gibt, wer sie ausführen darf und bei welchen eine Mail an Besteller:in und Materialverantwortliche geht.') }}
                    {{ __('Die Einheit «:role» darf jeden aktiven Statuswechsel für alle Bestellungen ausführen.', ['role' => $privilegedRole]) }}
                </p>

                <form method="POST" action="{{ route('settings.order-workflow.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        @foreach($transitions as $key => $transition)
                            @php($fieldName = "transitions[$key]")
                            @php($oldKey = "transitions.$key")
                            @php($fieldId = 'transition-'.$transition['from'].'-'.$transition['to'])
                            <div class="col-12 col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                                        <label class="h6 mb-0" for="{{ $fieldId }}-enabled">
                                            {{ $transition['fromLabel'] }} <i class="fa fa-arrow-right mx-1 small"></i> {{ $transition['toLabel'] }}
                                        </label>
                                        <div class="form-check form-switch mb-0">
                                            <input type="hidden" name="{{ $fieldName }}[enabled]" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="{{ $fieldId }}-enabled" name="{{ $fieldName }}[enabled]" value="1"
                                                   title="{{ __('Aktiv') }}"
                                                   @checked((bool) old("$oldKey.enabled", $transition['isEnabled']))>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">{{ __('Schaltfläche:') }} «{{ $transition['label'] }}»</p>

                                        <div class="form-check mb-3">
                                            <input type="hidden" name="{{ $fieldName }}[mail]" value="0">
                                            <input class="form-check-input" type="checkbox" id="{{ $fieldId }}-mail"
                                                   name="{{ $fieldName }}[mail]" value="1"
                                                   @checked((bool) old("$oldKey.mail", $transition['sendsMail']))>
                                            <label class="form-check-label" for="{{ $fieldId }}-mail">{{ __('Mail senden') }}</label>
                                        </div>

                                        <h6 class="text-muted text-uppercase small fw-bold">{{ __('Wer darf verschieben') }}</h6>
                                        <div class="row g-2">
                                            @forelse($roles as $role)
                                                @php($selectedScope = old("$oldKey.roles.$role->id", ($transition['scopes'][$role->id] ?? null)?->value))
                                                <div class="col-12 col-sm-6">
                                                    <label for="{{ $fieldId }}-role-{{ $role->id }}" class="form-label small mb-1">{{ $role->name }}</label>
                                                    <select class="form-select form-select-sm @error("$oldKey.roles.$role->id") is-invalid @enderror"
                                                            id="{{ $fieldId }}-role-{{ $role->id }}" name="{{ $fieldName }}[roles][{{ $role->id }}]">
                                                        <option value="">{{ __('Keine') }}</option>
                                                        @foreach($scopes as $scope)
                                                            <option value="{{ $scope->value }}" @selected($selectedScope === $scope->value)>{{ $scope->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @empty
                                                <div class="col-12 text-muted small">{{ __('Keine Einheiten vorhanden.') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('Speichern') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

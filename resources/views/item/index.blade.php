@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ __('Material') }}</span>
                        <div class="d-flex flex-wrap gap-1">
                            @component_enabled('storage')
                                <button type="submit" form="item-label-form" class="btn btn-outline-dark btn-sm">
                                    <i class="fa fa-print me-1"></i> {{ __('Etiketten für Auswahl') }}
                                </button>
                            @endcomponent_enabled
                            @can('create items')
                                <a href="{{ route('items.create') }}" class="btn btn-info btn-sm">
                                    <i class="fa fa-plus me-1"></i> {{ __('Neues Material') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body p-2 p-sm-3">
                        <form action="{{ route('items.index') }}" method="GET" class="mb-3">
                            <div class="row g-2">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fa fa-filter me-1 text-muted"></i> {{ __('Gruppe:') }}</span>
                                        <select name="item_group_id" class="form-select" onchange="this.form.submit()" aria-label="{{ __('Nach Materialgruppe filtern') }}">
                                            <option value="">{{ __('Alle Materialgruppen') }}</option>
                                            @foreach($itemGroups as $group)
                                                <option value="{{ $group->id }}" {{ isset($currentGroupId) && $currentGroupId == $group->id ? 'selected' : '' }}>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>

                        @component_enabled('storage')
                            <form id="item-label-form" method="GET" action="{{ route('labels.items') }}" target="_blank"></form>
                        @endcomponent_enabled

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @component_enabled('storage')
                                            <th style="width: 40px;">
                                                <input class="form-check-input" type="checkbox" aria-label="{{ __('Alle auswählen') }}"
                                                       onclick="document.querySelectorAll('.item-label-checkbox').forEach(cb => cb.checked = this.checked)">
                                            </th>
                                        @endcomponent_enabled
                                        <th class="sortable-header" style="min-width: 140px;">{{ __('Bezeichnung') }}</th>
                                        <th class="sortable-header">{{ __('Materialgruppe') }}</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">{{ __('Bestand') }}</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">{{ __('Packungsgrösse') }}</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">{{ __('Reparatur') }}</th>
                                        <th class="text-nowrap" style="min-width: 120px;">{{ __('Optionen') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        @component_enabled('storage')
                                            <td>
                                                <input class="form-check-input item-label-checkbox" type="checkbox" name="items[]" value="{{ $item->id }}"
                                                       form="item-label-form" aria-label="{{ __(':name auswählen', ['name' => $item->name]) }}">
                                            </td>
                                        @endcomponent_enabled
                                        <td>
                                            <a href="{{ route('items.show', $item) }}" class="fw-semibold text-decoration-none">{{ $item->name }}</a>
                                            @if(app(\App\Services\ComponentManager::class)->isEnabled('attachments') && $item->attachments->count() > 0)
                                                <i class="fa fa-paperclip text-muted ms-1" title="{{ __('Hat Anhänge (:count)', ['count' => $item->attachments->count()]) }}"></i>
                                            @endif
                                        </td>
                                        <td>{{ $item->itemGroup->name }}</td>
                                        <td class="text-center fw-bold">{{ $item->stock }}</td>
                                        <td class="text-center">{{ $item->lot }}</td>
                                        <td class="text-center">{{ $item->repair }}</td>
                                        <td class="text-nowrap">
                                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                                @can('edit items')
                                                    <a href="{{ route('items.edit', $item->id) }}" class="btn btn-warning btn-sm">{{ __('Bearbeiten') }}</a>
                                                @endcan
                                                @can('delete items')
                                                    @include('components.delete', [
                                                        'route' => 'items.destroy',
                                                        'id' => $item->id
                                                    ])
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">{{ __('Keine Materialien vorhanden.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

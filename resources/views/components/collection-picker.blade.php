{{-- Selected collections and the selection of further ones; expects $collections and optionally $order, handled by resources/js/ui/collections.js --}}
@php($selectedCollections = isset($order) ? $order->itemCollections : collect())
<div data-error-for="collections"></div>

<div id="addedCollections" class="mb-3 {{ $selectedCollections->isEmpty() ? 'd-none' : '' }}">
    <div class="mb-2"><strong>{{ __('Verknüpfte Kollektionen:') }}</strong></div>
    <div class="d-flex flex-wrap gap-2 mb-3" id="collections-wrapper" data-remove-label="{{ __(':name entfernen') }}">
        @foreach($selectedCollections as $collection)
            <span class="badge bg-info text-dark p-2" data-collection-badge data-items="{{ json_encode($collection->formItems()) }}">
                {{ $collection->name }}
                <input type="hidden" name="collections[]" value="{{ $collection->id }}">
                <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem" data-collection-remove aria-label="{{ __(':name entfernen', ['name' => $collection->name]) }}"></button>
            </span>
        @endforeach
    </div>
</div>

@if($collections->isNotEmpty())
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold bg-light">{{ __('Kollektionen') }}</div>
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-12 col-md-8 col-lg-9">
                    <label for="collectionSelect" class="form-label fw-semibold">{{ __('Kollektion auswählen') }}</label>
                    <select id="collectionSelect" class="form-select">
                        <option value="">{{ __('-- Kollektion wählen --') }}</option>
                        @foreach($collections as $collection)
                            <option value="{{ $collection->id }}" data-name="{{ $collection->name }}" data-items="{{ json_encode($collection->formItems()) }}">
                                {{ __(':name (:count Artikel)', ['name' => $collection->name, 'count' => $collection->items->count()]) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <button type="button" class="btn btn-outline-primary w-100" data-collection-add data-empty-message="{{ __('Bitte wählen Sie eine Kollektion aus.') }}">
                        <i class="fa fa-plus me-1"></i> {{ __('Hinzufügen') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

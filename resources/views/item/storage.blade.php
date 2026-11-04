{{-- Storage places of an item on the item edit page; expects $item and $binOptions --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 text-primary"><i class="fa fa-warehouse me-2"></i>{{ __('Lagerplätze') }}</h5>
        <a href="{{ route('labels.items', ['items' => [$item->id]]) }}" target="_blank" class="btn btn-outline-dark btn-sm">
            <i class="fa fa-print me-1"></i> {{ __('Etikett drucken') }}
        </a>
    </div>
    <div class="card-body">
        @if($item->stocks->isNotEmpty())
            <p class="small text-muted">{{ __('Der Bestand dieses Materials ergibt sich aus der Summe der Lagerplätze.') }}</p>
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Lagerplatz') }}</th>
                            <th class="text-center" style="width: 200px;">{{ __('Menge') }}</th>
                            @can('edit storage')
                                <th class="text-end" style="width: 90px;">{{ __('Aktion') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->stocks as $stock)
                            <tr>
                                <td><a href="{{ route('storage-units.show', $stock->storageUnit) }}" class="text-decoration-none">{{ $stock->storageUnit->path() }}</a></td>
                                <td class="text-center">
                                    @can('edit storage')
                                        <form method="POST" action="{{ route('item-stocks.update', $stock) }}" class="d-flex gap-1 justify-content-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="quantity" value="{{ $stock->quantity }}" min="0" max="999999"
                                                   class="form-control form-control-sm" style="max-width: 100px;" aria-label="{{ __('Menge') }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('Speichern') }}"><i class="fa fa-check"></i></button>
                                        </form>
                                    @else
                                        <span class="fw-bold">{{ $stock->quantity }}</span>
                                    @endcan
                                </td>
                                @can('edit storage')
                                    <td class="text-end">@include('components.delete', ['route' => 'item-stocks.destroy', 'id' => $stock->id])</td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-3">{{ __('Dieses Material ist noch keinem Lagerplatz zugeordnet.') }}</p>
        @endif

        @can('edit storage')
            @if($binOptions !== [])
                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('Auf Lagerplatz einlagern') }}</h6>
                    <form method="POST" action="{{ route('item-stocks.store') }}">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="row g-2">
                            <div class="col-12 col-md-7">
                                <select name="storage_unit_id" class="form-select" required aria-label="{{ __('Lagerplatz') }}">
                                    <option value="">{{ __('Lagerplatz wählen') }}</option>
                                    @foreach($binOptions as $id => $path)
                                        <option value="{{ $id }}">{{ $path }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="number" name="quantity" class="form-control" placeholder="{{ __('Menge') }}" min="0" max="999999" required aria-label="{{ __('Menge') }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-success w-100"><i class="fa fa-plus me-1"></i> {{ __('Einlagern') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @elseif($item->stocks->isEmpty())
                <p class="small text-muted mb-0">{{ __('Es sind noch keine Lagerplätze erfasst.') }} <a href="{{ route('storage-units.index') }}">{{ __('Zum Lager') }}</a></p>
            @endif
        @endcan
    </div>
</div>

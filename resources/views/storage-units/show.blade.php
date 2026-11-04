@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <nav aria-label="{{ __('Pfad') }}">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('storage-units.index') }}">{{ __('Lager') }}</a></li>
                        @foreach($storageUnit->ancestors() as $ancestor)
                            <li class="breadcrumb-item"><a href="{{ route('storage-units.show', $ancestor) }}">{{ $ancestor->name }}</a></li>
                        @endforeach
                        <li class="breadcrumb-item active" aria-current="page">{{ $storageUnit->name }}</li>
                    </ol>
                </nav>

                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">
                            <i class="fa {{ $storageUnit->level->icon() }} text-muted me-1"></i>
                            {{ $storageUnit->name }}
                            @unless($storageUnit->level->holdsStock())
                                <code class="fs-6 ms-1">{{ $storageUnit->code }}</code>
                            @endunless
                            <span class="badge bg-secondary ms-1 fs-6 fw-normal">{{ $storageUnit->level->label() }}</span>
                        </span>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#labelModal">
                                <i class="fa fa-print me-1"></i> {{ __('Etiketten') }}
                            </button>
                            @can('update', $storageUnit)
                                <a href="{{ route('storage-units.edit', $storageUnit) }}" class="btn btn-warning btn-sm">{{ __('Bearbeiten') }}</a>
                            @endcan
                            @can('delete', $storageUnit)
                                @include('components.delete', ['route' => 'storage-units.destroy', 'id' => $storageUnit->id])
                            @endcan
                        </div>
                    </div>
                    @if($storageUnit->description)
                        <div class="card-body">
                            <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $storageUnit->description }}</p>
                        </div>
                    @endif
                </div>

                @if($childLevel = $storageUnit->level->childLevel())
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="h6 mb-0">{{ $childLevel->pluralLabel() }}</span>
                            @can('create', \App\Models\StorageUnit::class)
                                <a href="{{ route('storage-units.create', ['parent' => $storageUnit->id]) }}" class="btn btn-info btn-sm">
                                    <i class="fa fa-plus me-1"></i> {{ __(':label hinzufügen', ['label' => $childLevel->label()]) }}
                                </a>
                            @endcan
                        </div>
                        <div class="card-body p-0 p-sm-3">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable-header">{{ __('Bezeichnung') }}</th>
                                            <th class="sortable-header text-center" style="width: 160px;">
                                                {{ $childLevel->childLevel()?->pluralLabel() ?? __('Material') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($storageUnit->children as $child)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('storage-units.show', $child) }}" class="fw-semibold text-decoration-none">
                                                        <i class="fa {{ $child->level->icon() }} text-muted me-1"></i>{{ $child->name }}
                                                    </a>
                                                    @unless($child->level->holdsStock())
                                                        <code class="small ms-1">{{ $child->code }}</code>
                                                    @endunless
                                                </td>
                                                <td class="text-center">{{ $child->level->holdsStock() ? $child->stocks_count : $child->children_count }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-muted">{{ __('Keine :plurallabel vorhanden.', ['plurallabel' => $childLevel->pluralLabel()]) }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card shadow-sm">
                        <div class="card-header h6 mb-0 py-3">{{ __('Eingelagertes Material') }}</div>
                        <div class="card-body p-0 p-sm-3">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable-header">{{ __('Material') }}</th>
                                            <th class="sortable-header">{{ __('Materialgruppe') }}</th>
                                            <th class="text-center" style="width: 220px;">{{ __('Menge') }}</th>
                                            @can('update', $storageUnit)
                                                <th class="text-nowrap" style="width: 100px;">{{ __('Optionen') }}</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($storageUnit->stocks->sortBy('item.name') as $stock)
                                            <tr>
                                                <td><a href="{{ route('items.show', $stock->item) }}" class="fw-semibold text-decoration-none">{{ $stock->item->name }}</a></td>
                                                <td>{{ $stock->item->itemGroup?->name }}</td>
                                                <td class="text-center">
                                                    @can('update', $storageUnit)
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
                                                @can('update', $storageUnit)
                                                    <td>@include('components.delete', ['route' => 'item-stocks.destroy', 'id' => $stock->id])</td>
                                                @endcan
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">{{ __('Auf diesem Lagerplatz liegt kein Material.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @can('update', $storageUnit)
                                <div class="mt-3 p-3 bg-light rounded-3 border">
                                    <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('Material einlagern') }}</h6>
                                    <form method="POST" action="{{ route('item-stocks.store') }}">
                                        @csrf
                                        <input type="hidden" name="storage_unit_id" value="{{ $storageUnit->id }}">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-7">
                                                <select name="item_id" class="form-select" required aria-label="{{ __('Material') }}">
                                                    <option value="">{{ __('Material wählen') }}</option>
                                                    @foreach($items as $item)
                                                        <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <input type="number" name="quantity" class="form-control" placeholder="{{ __('Menge') }}" min="0" max="999999" value="{{ old('quantity') }}" required aria-label="{{ __('Menge') }}">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <button type="submit" class="btn btn-success w-100"><i class="fa fa-plus me-1"></i> {{ __('Einlagern') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('storage-units.labels-modal')
@endsection

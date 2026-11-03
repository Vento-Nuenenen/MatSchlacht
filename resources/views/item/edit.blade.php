@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Material bearbeiten</h2>
        <form method="POST" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data" id="item-update-form">
            @method('PUT')
            @include('item.form', [
                'buttonText' => 'Speichern',
                'item' => $item
            ])
        </form>

        @component_enabled('storage')
            <div class="mt-4 pt-3 border-top">
                @include('item.storage')
            </div>
        @endcomponent_enabled

        @component_enabled('maintenance')
            <div class="mt-4 pt-3 border-top">
                @include('item.maintenance')
            </div>
        @endcomponent_enabled

        @component_enabled('procurement')
            @can('manage procurements')
                <div class="mt-4 pt-3 border-top">
                    @include('item.procurements')
                </div>
            @endcan
        @endcomponent_enabled

        @if(isset($item) && $item->exists && app(\App\Services\ComponentManager::class)->isEnabled('item_contents'))
            <div class="mt-4 pt-3 border-top">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="fa fa-list-ul me-2"></i>Inhaltsliste
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($item->contents->count() > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 90px;">Menge</th>
                                            <th>Bezeichnung</th>
                                            <th class="text-end" style="width: 80px;">Aktion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($item->contents as $content)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary px-2 py-1">{{ $content->quantity ?: '1x' }}</span>
                                                </td>
                                                <td class="fw-bold">{{ $content->name }}</td>
                                                <td class="text-end">
                                                    @can('edit items')
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                                onclick="if(confirm('Inhalt wirklich löschen?')) { document.getElementById('delete-content-{{ $content->id }}').submit(); }"
                                                                title="Löschen">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                        <form id="delete-content-{{ $content->id }}" action="{{ route('items.contents.destroy', $content) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fa fa-box-open fa-3x mb-3 opacity-25"></i>
                                <p class="mb-0">Kein Inhalt erfasst</p>
                            </div>
                        @endif

                        @can('edit items')
                            <div class="mt-3 p-3 p-md-4 bg-light rounded-3 border">
                                <h6 class="text-uppercase text-muted small fw-bold mb-3">Neuen Inhalt hinzufügen</h6>
                                <form action="{{ route('items.contents.store', $item) }}" method="POST">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-4 col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa fa-hashtag"></i></span>
                                                <input type="text" name="quantity" class="form-control" placeholder="Menge (z.B. 2x)">
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-5 col-md-7">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa fa-tag"></i></span>
                                                <input type="text" name="name" class="form-control" placeholder="Bezeichnung" required list="content-names">
                                            </div>
                                            <datalist id="content-names">
                                                @foreach($allContentNames as $name)
                                                    <option value="{{ $name }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div class="col-12 col-sm-3 col-md-2">
                                            <button type="submit" class="btn btn-success w-100 shadow-sm">
                                                <i class="fa fa-plus me-1"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

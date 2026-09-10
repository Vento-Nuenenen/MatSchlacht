@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Material</span>
                        @can('create items')
                            <a href="{{ route('items.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> Neues Material
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-2 p-sm-3">
                        <form action="{{ route('items.index') }}" method="GET" class="mb-3">
                            <div class="row g-2">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fa fa-filter me-1 text-muted"></i> Gruppe:</span>
                                        <select name="item_group_id" class="form-select" onchange="this.form.submit()">
                                            <option value="">Alle Materialgruppen</option>
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

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header" style="min-width: 140px;">Bezeichnung</th>
                                        <th class="sortable-header">Materialgruppe</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">Bestand</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">Packungsgrösse</th>
                                        <th class="sortable-header text-center" style="min-width: 80px;">Reparatur</th>
                                        <th class="text-nowrap" style="min-width: 120px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $item->name }}</span>
                                            @if($item->attachments->count() > 0)
                                                <i class="fa fa-paperclip text-muted ms-1" title="Hat Anhänge ({{ $item->attachments->count() }})"></i>
                                            @endif
                                        </td>
                                        <td>{{ $item->itemGroup->name }}</td>
                                        <td class="text-center fw-bold">{{ $item->stock }}</td>
                                        <td class="text-center">{{ $item->lot }}</td>
                                        <td class="text-center">{{ $item->repair }}</td>
                                        <td class="text-nowrap">
                                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                                @can('edit items')
                                                    <a href="{{ route('items.edit', $item->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
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
                                        <td colspan="6" class="text-center py-4 text-muted">Keine Materialien vorhanden.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

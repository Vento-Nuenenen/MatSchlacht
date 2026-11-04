@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Materialgruppen</span>
                        @can('create itemgroups')
                            <a href="{{ route('item-groups.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> Neue Materialgruppe
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0" id="item-groups-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;"></th>
                                        <th class="sortable-header text-center" style="width: 70px;">Pos.</th>
                                        <th class="sortable-header" style="min-width: 140px;">Bezeichnung</th>
                                        <th class="text-nowrap" style="min-width: 120px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody id="item-groups-body">
                                @forelse($itemGroups as $itemGroup)
                                    <tr data-id="{{ $itemGroup->id }}">
                                        @can('edit itemgroups')
                                            <td class="drag-handle text-center text-muted p-2" style="cursor: grab;">
                                                <i class="fa fa-bars fa-lg"></i>
                                            </td>
                                        @else
                                            <td></td>
                                        @endcan
                                        <td class="position-index text-center fw-bold">{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $itemGroup->name }}</td>
                                        <td class="text-nowrap">
                                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                                @can('edit itemgroups')
                                                    <a href="{{ route('item-groups.edit', $itemGroup->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                @endcan
                                                @can('delete itemgroups')
                                                    @include('components.delete', [
                                                        'route' => 'item-groups.destroy',
                                                        'id' => $itemGroup->id,
                                                    ])
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Keine Materialgruppen vorhanden.</td>
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

@section('assets-js')
    @can('edit itemgroups')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('item-groups-body');
            if (el) {
                Sortable.create(el, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function () {
                        const positions = [];
                        const rows = el.querySelectorAll('tr');
                        rows.forEach((row, index) => {
                            positions.push(row.dataset.id);
                        });

                        fetch('{{ route('item-groups.reorder') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ positions: positions })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Reordered successfully');
                                // Update visible position numbers
                                rows.forEach((row, index) => {
                                    row.querySelector('.position-index').textContent = index + 1;
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    }
                });
            }
        });
    </script>
    @endcan
@endsection

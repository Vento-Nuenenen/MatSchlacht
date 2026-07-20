@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if(session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">Materialgruppen</div>
                    <div class="card-body">
                        @can('create itemgroups')
                            <a href="{{ route('item-groups.create') }}" class="btn btn-info btn-sm col-12">Neue Materialgruppe erstellen</a>
                            <br />
                            <br />
                        @endcan

                        <table class="table table-hover table-bordered table-striped" id="item-groups-table">
                            <thead>
                                <th width="50"></th>
                                <th>Bezeichnung</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody id="item-groups-body">
                            @foreach($itemGroups as $itemGroup)
                                <tr data-id="{{ $itemGroup->id }}">
                                    @can('edit itemgroups')
                                        <td class="drag-handle" style="cursor: move;">
                                            <i class="fa fa-bars"></i>
                                        </td>
                                    @else
                                        <td></td>
                                    @endcan
                                    <td>{{ $itemGroup->name }}</td>
                                    <td>
                                        @can('edit itemgroups')
                                            <a href="{{ route('item-groups.edit', $itemGroup->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                            &nbsp;
                                        @endcan
                                        @can('delete itemgroups')
                                            @include('components.delete', [
                                                'route' => 'item-groups.destroy',
                                                'id' => $itemGroup->id,
                                            ])
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
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

@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">Material</div>
                    <div class="card-body table-responsive">
                        @can('create items')
                            <a href="{{ route('items.create') }}" class="btn btn-info col-12">Neues Material</a>
                            <br />
                            <br />
                        @endcan

                        <form action="{{ route('items.index') }}" method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <span class="input-group-text">Filter:</span>
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

                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Bezeichnung</th>
                                <th>Materialgruppe</th>
                                <th>Bestand</th>
                                <th>Packungsgrösse</th>
                                <th>Reparatur</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->itemGroup->name }}</td>
                                        <td>{{ $item->stock }}</td>
                                        <td>{{ $item->lot }}</td>
                                        <td>{{ $item->repair }}</td>
                                        <td>
                                            @can('edit items')
                                                <a href="{{ route('items.edit', $item->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                &nbsp;
                                            @endcan
                                            @can('delete items')
                                                @include('components.delete', [
                                                    'route' => 'items.destroy',
                                                    'id' => $item->id
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

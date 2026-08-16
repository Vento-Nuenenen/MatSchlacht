@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @include('components.status-alerts')

                <div class="card">
                    <div class="card-header">Azure Group Role Mappings</div>
                    <div class="card-body">
                        @can('manage azure mappings')
                            <a href="{{ route('azure-group-role-mappings.create') }}" class="btn btn-info btn-sm col-12">Office 365 Mapping hinzufügen</a>
                            <br />
                            <br />
                        @endcan

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th class="sortable-header">Azure Group Object ID</th>
                                        <th class="sortable-header">Rolle</th>
                                        <th>Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mappings as $mapping)
                                        <tr>
                                            <td>{{ $mapping->azure_group_id }}</td>
                                            <td>{{ $mapping->role_name }}</td>
                                            <td>
                                                @can('manage azure mappings')
                                                    <a href="{{ route('azure-group-role-mappings.edit', $mapping->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    &nbsp;
                                                    @include('components.delete', [
                                                        'route' => 'azure-group-role-mappings.destroy',
                                                        'id' => $mapping->id
                                                    ])
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($mappings->isEmpty())
                                        <tr>
                                            <td colspan="3" class="text-center">Keine Mappings vorhanden</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

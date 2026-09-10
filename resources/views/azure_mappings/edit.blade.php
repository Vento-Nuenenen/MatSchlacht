@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Azure Mapping anpassen</h2>
        <form method="POST" action="{{ route('azure-group-role-mappings.update', $azureGroupRoleMapping->id) }}">
            @method('PUT')
            @include('azure_mappings.form', [
                'buttonText' => 'Speichern',
                'azureGroupRoleMapping' => $azureGroupRoleMapping,
            ])
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Azure Mapping erstellen</h2>
        <form method="POST" action="{{ route('azure-group-role-mappings.store') }}">
            @include('azure_mappings.form', [
                'buttonText' => 'Erstellen',
                'azureGroupRoleMapping' => null,
            ])
        </form>
    </div>
@endsection


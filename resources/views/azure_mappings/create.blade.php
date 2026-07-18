@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Azure Mapping erstellen</h2>
        <form method="POST" action="{{ route('azure-group-role-mappings.store') }}">
            @include('azure_mappings.form', [
                'buttonText' => 'Erstellen',
                'azureGroupRoleMapping' => null,
            ])
        </form>
    </div>
@endsection


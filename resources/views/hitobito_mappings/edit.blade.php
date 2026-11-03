@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Hitobito Mapping bearbeiten</h2>
        <form method="POST" action="{{ route('hitobito-group-role-mappings.update', $hitobitoGroupRoleMapping) }}">
            @method('PUT')
            @include('hitobito_mappings.form', ['buttonText' => 'Speichern'])
        </form>
    </div>
@endsection

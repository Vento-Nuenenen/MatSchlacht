@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Hitobito Mapping erstellen</h2>
        <form method="POST" action="{{ route('hitobito-group-role-mappings.store') }}">
            @include('hitobito_mappings.form', [
                'buttonText' => 'Erstellen',
                'hitobitoGroupRoleMapping' => null,
            ])
        </form>
    </div>
@endsection

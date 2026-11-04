@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Hitobito-Regel bearbeiten</h2>
        <form method="POST" action="{{ route('hitobito-access-rules.update', $hitobitoAccessRule) }}">
            @method('PUT')
            @include('hitobito_access_rules.form', ['buttonText' => 'Speichern'])
        </form>
    </div>
@endsection

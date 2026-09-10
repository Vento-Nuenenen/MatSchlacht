@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Benutzer erstellen</h2>
        <form method="POST" action="{{ route('users.store') }}">
            @include('user.form', [
                'buttonText' => 'Erstellen',
                'user' => null
            ])
        </form>
    </div>
@endsection

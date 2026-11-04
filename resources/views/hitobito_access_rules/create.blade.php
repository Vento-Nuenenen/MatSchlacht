@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Hitobito-Regel erstellen') }}</h2>
        <form method="POST" action="{{ route('hitobito-access-rules.store') }}">
            @include('hitobito_access_rules.form', [
                'buttonText' => __('Erstellen'),
                'hitobitoAccessRule' => null,
            ])
        </form>
    </div>
@endsection

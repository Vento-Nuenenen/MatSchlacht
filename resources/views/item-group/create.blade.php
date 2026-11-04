@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Materialgruppe erstellen') }}</h2>
        <form method="POST" action="{{ route('item-groups.store') }}">
            @include('item-group.form', [
                'buttonText' => __('Erstellen'),
                'itemGroup' => null,
            ])
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Material erstellen') }}</h2>
        <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
            @include('item.form', [
                'buttonText' => __('Erstellen'),
                'item' => null
            ])
        </form>
    </div>
@endsection

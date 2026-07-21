@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Material bearbeiten</h2>
        <form method="POST" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('item.form', [
                'buttonText' => 'Speichern',
                'item' => $item
            ])
        </form>
    </div>
@endsection

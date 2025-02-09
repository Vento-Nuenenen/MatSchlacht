@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Bestellen</h2>
        <order-form
            create-url="{{ route('orders.store') }}"
            edit-url="{{ route('orders.update', $order->id ?? '') }}"
            :order="{{ isset($order) ? $order->toJson() : '{}' }}"
            :users="{{ isset($users) ? $users->toJson() : '{}' }}"
            :units="{{ isset($units) ? $units->toJson() : '{}' }}"
            :items="{{ isset($items) ? $items->toJson() : '{}' }}"
            :step="3"
        ></order-form>
    </div>
@endsection

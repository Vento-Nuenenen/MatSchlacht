@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Bestellung anpassen</h2>
        <order-form
            create-url="{{ route('orders.store') }}"
            edit-url="{{ route('orders.update', $order->id ?? '') }}"
            order="{{ isset($order) ? $order->toJson() : '{}' }}"
            :step="1"
        ></order-form>
    </div>
@endsection

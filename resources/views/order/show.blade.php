@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <button class="btn btn-warning">Auftragsliste PDF</button>
                <button class="btn btn-success ms-3">Bestellung bestätigen</button>
                <button class="btn btn-danger ms-3">Bestellung ablehnen</button>
                <div class="mb-3"></div>

                <div class="card">
                    <div class="card-header">Bestellung {{ $order->event }}</div>
                    <div class="card-body">
                        <p>
                            <b>Anlass:</b> {{ $order->event }} <br />
                            <b>Einheit:</b> {{ $order->role }} <br />
                            <b>Vom:</b> {{ $order->event_start }} <br />
                            <b>Bis:</b> {{ $order->event_end }} <br />
                            <b>Status:</b> {{ $order->status }} <br />
                            <b>Material:</b> <br />
                            @foreach ($order->orderedItems as $item)
                                {{ $item->item->name }} - {{ $item->quantity }} Stk.
                            @endforeach
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

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

                <div class="card">
                    <div class="card-header">Bestellungen</div>
                    <div class="card-body">
                        <a href="{{ route('orders.create') }}" class="btn btn-info btn-sm col-12">Neue Bestellung</a>
                        <br />
                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                            <th>Anlass</th>
                            <th>Einheit</th>
                            <th>Vom</th>
                            <th>Bis</th>
                            <th>Status</th>
                            <th>Optionen</th>
                            </thead>
                            <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>{{ $order->event }}</td>
                                    <td>{{ $order->unit->name }}</td>
                                    <td>{{ $order->event_start }}</td>
                                    <td>{{ $order->event_end }}</td>
                                    <td>{{ $order->status }}</td>
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm">Anzeigen</a>
                                        &nbsp;
                                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                        &nbsp;
                                        @include('components.delete', [
                                            'route' => 'orders.destroy',
                                            'id' => $order->id
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

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
                        @can('create orders')
                            <a href="{{ route('orders.create') }}" class="btn btn-info btn-sm col-12">Neue Bestellung</a>
                            <br />
                            <br />
                        @endcan

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th class="sortable-header">Anlass</th>
                                <th class="sortable-header">Einheit</th>
                                <th class="sortable-header">Verantwortlich</th>
                                <th class="sortable-header">Von</th>
                                <th class="sortable-header">Bis</th>
                                <th class="sortable-header">Status</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>{{ $order->event }}</td>
                                    <td>{{ $order->role->name }}</td>
                                    <td>{{ $order->user->preferredName() }}</td>
                                    <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_start)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }}</td>
                                    <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_end)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->state->color() }}">
                                            {{ $order->state->display() }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm">Anzeigen</a>
                                        &nbsp;
                                        @if($order->state->equals(\App\States\Order\Stored::class))
                                            @if(auth()->user()->can('edit orders') || (auth()->user()->can('edit own orders') && $order->user_id === auth()->id()))
                                                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                &nbsp;
                                            @endif
                                            @if(auth()->user()->can('delete orders') || (auth()->user()->can('delete own orders') && $order->user_id === auth()->id()))
                                                @include('components.delete', [
                                                    'route' => 'orders.destroy',
                                                    'id' => $order->id,
                                                ])
                                            @endif

                                        @endif
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

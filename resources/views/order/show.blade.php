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

                @if($order->status->equals(\App\States\Order\Stored::class))
                    @if(auth()->user()->can('edit orders') || (auth()->user()->can('edit own orders') && $order->user_id === auth()->id()))
                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning">Bearbeiten</a>
                    @endif

                    @if(auth()->user()->can('delete orders') || (auth()->user()->can('delete own orders') && $order->user_id === auth()->id()))
                        <div class="d-inline ms-3">
                            @include('components.delete', [
                                'route' => 'orders.destroy',
                                'id' => $order->id
                            ])
                        </div>
                    @endif

                    <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="to" value="committed">
                        <button type="submit" class="btn btn-success ms-3">Bestellung absenden (Commit)</button>
                    </form>
                @endif

                @can('edit orders')
                    @if($order->status->equals(\App\States\Order\Committed::class))
                        <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="to" value="accepted">
                            <button type="submit" class="btn btn-primary ms-3">Bestellung akzeptieren</button>
                        </form>
                    @endif

                    @if($order->status->equals(\App\States\Order\Accepted::class))
                        <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="to" value="delivered">
                            <button type="submit" class="btn btn-info ms-3">Material ausgegeben (Deliver)</button>
                        </form>
                    @endif

                    @if($order->status->equals(\App\States\Order\Delivered::class))
                        <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="to" value="returned">
                            <button type="submit" class="btn btn-secondary ms-3">Material zurückerhalten (Return)</button>
                        </form>
                    @endif

                    @if($order->status->canTransitionTo(\App\States\Order\Archived::class))
                        <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="to" value="archived">
                            <button type="submit" class="btn btn-danger ms-3">Bestellung archivieren/stornieren</button>
                        </form>
                    @endif
                @endcan

                <div class="mb-3"></div>

                <div class="card">
                    <div class="card-header">Bestellung {{ $order->event }}</div>
                    <div class="card-body">
                        <p>
                            <b>Anlass:</b> {{ $order->event }} <br />
                            <b>Einheit:</b> {{ $order->role->name }} <br />
                            <b>Vom:</b> {{ $order->event_start }} <br />
                            <b>Bis:</b> {{ $order->event_end }} <br />
                            <b>Status:</b>
                            <span class="badge bg-{{ $order->status->color() }}">
                                {{ $order->status->display() }}
                            </span>
                            <br />
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

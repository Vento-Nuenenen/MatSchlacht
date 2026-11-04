<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h3>{{ __('Neue Materialbestellung') }}</h3>
    <p>{{ __('Es wurde eine neue Bestellung erfasst.') }}</p>

    <p>
        {{ __('Einheit:') }} <strong>{{ $role?->name }}</strong><br>
        {{ __('Anlass:') }} <strong>{{ $order->event }}</strong><br>
        {{ __('Zeitraum:') }} <strong>{{ $order->event_start }}</strong> {{ __('bis') }} <strong>{{ $order->event_end }}</strong><br>
        {{ __('Bestellt von:') }} <strong>{{ $user?->firstname }} {{ $user?->lastname }}</strong>
    </p>

    @if($items->count())
        <h4>{{ __('Bestellte Artikel') }}</h4>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Artikel') }}</th>
                    <th>{{ __('Menge') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $ordered)
                    <tr>
                        <td>{{ $ordered->item?->name }}</td>
                        <td>{{ $ordered->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>{{ __('Status:') }} <strong>{{ $order->state->display() }}</strong></p>
</body>
</html>

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Neue Materialbestellung</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; }
    </style>
    </head>
<body>
<h3>Neue Materialbestellung</h3>
<p>Es wurde eine neue Bestellung erfasst.</p>

<p>
    Einheit: <strong>{{ $unit?->name }}</strong><br>
    Anlass: <strong>{{ $order->event }}</strong><br>
    Zeitraum: <strong>{{ $order->event_start }}</strong> bis <strong>{{ $order->event_end }}</strong><br>
    Bestellt von: <strong>{{ $user?->firstname }} {{ $user?->lastname }}</strong>
    </p>

@if($items->count())
    <h4>Bestellte Artikel</h4>
    <table>
        <thead>
        <tr>
            <th>Artikel</th>
            <th>Menge</th>
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

<p>Status: <strong>{{ $order->status }}</strong></p>

</body>
</html>

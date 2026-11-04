<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />

    <title>Status der Materialbestellung geändert</title>

    <style>
        body { font-family: Arial, sans-serif; color: #1C1C1C; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #B1B1B1B0 }
        .header { margin-bottom: 20px; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; background: #555; color: #fff; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #6a6a6a; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Status der Materialbestellung geändert</h3>
        <p>Der Status deiner Materialbestellung für <strong>{{ $order->event }}</strong> hat sich geändert.</p>
        <p>Status: <span class="status-badge">{{ $order->state->display() }}</span></p>
    </div>

    <div class="details">
        <p>
            <strong>Einheit:</strong> {{ $role?->name }}<br>
            <strong>Zeitraum:</strong> {{ $order->event_start }} bis {{ $order->event_end }}<br>
            <strong>Verantwortlich:</strong> {{ $user?->firstname }} {{ $user?->lastname }}
        </p>
    </div>

    @if($items->count())
        <h4>Artikel</h4>
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

    <p class="footer">
        Dies ist eine automatisch generierte Nachricht von <a href="https://github.com/Vento-Nuenenen/MatSchlacht">MatSchlacht</a>.
    </p>
</body>
</html>

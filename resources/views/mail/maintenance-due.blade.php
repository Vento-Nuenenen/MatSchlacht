<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />

    <title>Fällige Wartungen</title>

    <style>
        body { font-family: Arial, sans-serif; color: #1C1C1C; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #B1B1B1B0 }
        .header { margin-bottom: 20px; }
        .overdue { color: #c00; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #6a6a6a; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Fällige Wartungen</h3>
        <p>Für folgendes Material ist eine Wartung fällig oder überfällig:</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th>Letzte Wartung</th>
                <th>Fällig am</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php($nextDue = $item->nextMaintenanceDue())
                <tr>
                    <td><a href="{{ route('items.show', $item) }}">{{ $item->name }}</a></td>
                    <td>{{ $item->latestMaintenance?->performed_on->format('d.m.Y') ?? 'noch nie' }}</td>
                    <td class="{{ $nextDue?->isPast() && ! $nextDue->isToday() ? 'overdue' : '' }}">{{ $nextDue?->format('d.m.Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><a href="{{ route('maintenance.index', ['due' => 1]) }}">Zur Wartungsübersicht</a></p>

    <p class="footer">
        Dies ist eine automatisch generierte Nachricht von <a href="https://github.com/Vento-Nuenenen/MatSchlacht">MatSchlacht</a>.
    </p>
</body>
</html>

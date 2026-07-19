<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Auftragsliste - {{ $order->event }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .signatures { margin-top: 50px; width: 100%; }
        .signature-box { border-top: 1px solid #000; width: 45%; float: left; text-align: center; padding-top: 5px; }
        .signature-box-right { float: right; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Auftragsliste</h1>
        <h2>{{ $order->event }}</h2>
    </div>

    <div class="details">
        <p><strong>Einheit:</strong> {{ $order->role->name }}</p>
        <p><strong>Bestellt von:</strong> {{ $order->user->fullName() }}</p>
        <p><strong>Zeitraum:</strong> {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</p>
    </div>

    <h3>Bestellte Materialien</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th width="50%">Bezeichnung</th>
                <th width="25%">Bestellt</th>
                <th width="25%">Zurückgegeben</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderedItems as $orderedItem)
                <tr>
                    <td>{{ $orderedItem->item->name }}</td>
                    <td>{{ $orderedItem->quantity }} Stk.</td>
                    <td>{{ $orderedItem->returned }} Stk.</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            Unterschrift Materialwart
        </div>
        <div class="signature-box signature-box-right">
            Unterschrift Besteller ({{ $order->user->fullName() }})
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>

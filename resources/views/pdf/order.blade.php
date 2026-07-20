<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Auftragsliste - {{ $order->event }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-weight: 400 !important;
            line-height: 1.5;
            color: #212529;
            margin: 0;
        }
        .details {
            border-collapse: collapse;
            border-bottom: 1px solid #dddddd;
        }
        .materials {
            border-collapse: collapse;
            border: 1px solid #dddddd;
        }
        .table-header {
            background-color: #a3a3a3;
        }
        .table-odd {
            background-color: rgb(221 221 221 / 0.69);
        }
        .col-1 {
            width: 8.332%;
        }
        .col-2 {
            width: 16.664%;
        }
        .col-3 {
            width: 25%;
        }
        .col-7 {
            width: 58.332%;
        }
        .col-9 {
            width: 75%;
        }
        .col-10 {
            width: 83.332%;
        }
        .col-11 {
            width: 91.666%;
        }
        .col-12 {
            width: 100%;
        }
        h1, h2, h3, h4 {
            font-weight: bold;
            margin: 0;
            padding: 0;
        }
        .textalign-right {
            text-align: right;
        }
        .textalign-left {
            text-align: left;
        }
        .textalign-center {
            text-align: center;
        }
        .textalign-bottom {
            vertical-align: bottom;
        }
    </style>
</head>
<body>
    <table class="col-12 header">
        <thead>
            <tr>
                <th class="col-10">
                    <h2>Auftragsliste</h2>
                    <h1>{{ $order->event }}</h1>
                </th>
                <th class="col-2 textalign-right textalign-bottom">
                    <b>Datum:</b> {{ date('d.m.Y') }}
                </th>
            </tr>
        </thead>
    </table>

    <br />
    <br />
    <br />

    <h3 class="details">Bestellinformationen</h3>
    <table class="col-12 details">
        <tr>
            <td class="col-3 textalign-left details">
                <b>Einheit:</b>
            </td>
            <td class="col-9 details">
                {{ $order->role->name }}
            </td>
        </tr>
        <tr>
            <th class="col-3 textalign-left details">
                <b>Bestellt von:</b>
            </th>
            <td class="col-9 details">
                {{ $order->user->fullName() }}
            </td>
        </tr>
        <tr>
            <th class="col-3 textalign-left">
                <b>Zeitraum:</b>
            </th>
            <td class="col-9">
                {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} bis {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}
            </td>
        </tr>
    </table>

    <br />
    <br />
    <br />

    <h3>Materialkollektionen</h3>
    @if($order->itemCollections->isNotEmpty())
        <table class="col-12 collections">
            <tr>
                <td class="col-9 details">
                    {{
                        $order->itemCollections->groupBy('id')->map(function ($group) {
                            $count = $group->count();
                            return $count > 1 ? $group->first()->name . ' (' . $count . 'x)' : $group->first()->name;
                        })->implode(', ')
                    }}
                </td>
            </tr>
        </table>
    @else
        Es wurden keine Materialkollektionen angefordert.
    @endif

    <br />

    <h3>Ausgegebene Materialien</h3>
    <table class="col-12 materials">
        <thead>
            <tr class="table-header">
                <th class="col-1 materials textalign-center">Pos.</th>
                <th class="col-7 materials textalign-left">Bezeichnung</th>
                <th class="col-2 materials textalign-center">Bestellt</th>
                <th class="col-2 materials textalign-center">Ausgegeben</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderedItems as $index => $orderedItem)
                <tr class="table-row {{ $index % 2 !== 0 ? 'table-odd' : '' }}">
                    <td class="materials textalign-center">{{ $index + 1 }}</td>
                    <td class="materials textalign-left">{{ $orderedItem->item->name }}</td>
                    <td class="materials textalign-center">{{ $orderedItem->quantity }}</td>
                    <td class="materials textalign-center"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br />
    <br />
    <br />
    <br />
    <br />
    <br />

    <!-- SIGNATURES -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="45%" style="border-top: 1px solid #000000; padding-top: 5px; text-align: center;">
                <br />Unterschrift Materialwart
            </td>
            <td width="10%"></td>
            <td width="45%" style="border-top: 1px solid #000000; padding-top: 5px; text-align: center;">
                <br />Unterschrift Besteller ({{ $order->user->fullName() }})
            </td>
        </tr>
    </table>
</body>
</html>

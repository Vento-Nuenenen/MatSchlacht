{{-- Badge with the maintenance status of an item; expects $item and $status --}}
@if($status === null)
    <span class="badge bg-light text-muted border">{{ __('Kein Wartungsintervall') }}</span>
@else
    @php($nextDue = $item->nextMaintenanceDue())
    <span class="badge {{ ['overdue' => 'bg-danger', 'due' => 'bg-warning text-dark', 'ok' => 'bg-success'][$status] }}">
        {{ ['overdue' => __('Überfällig'), 'due' => __('Fällig'), 'ok' => __('Nächste Wartung')][$status] }}:
        {{ $nextDue->format('d.m.Y') }}
    </span>
@endif

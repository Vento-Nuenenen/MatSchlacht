@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Wartung</span>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Filter">
                            <a href="{{ route('maintenance.index') }}" class="btn {{ $isDueOnly ? 'btn-outline-secondary' : 'btn-secondary' }}">Alle mit Intervall</a>
                            <a href="{{ route('maintenance.index', ['due' => 1]) }}" class="btn {{ $isDueOnly ? 'btn-secondary' : 'btn-outline-secondary' }}">Nur fällige</a>
                        </div>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <p class="small text-muted px-3 px-sm-0 pt-3 pt-sm-0">
                            Fällig sind Wartungen, die in den nächsten {{ $maintenance->daysAhead() }} Tagen anstehen. Material mit Intervall, das noch nie gewartet wurde, ist sofort fällig.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header" style="min-width: 140px;">Material</th>
                                        <th class="sortable-header">Materialgruppe</th>
                                        <th class="sortable-header text-center">Intervall</th>
                                        <th class="sortable-header text-nowrap">Letzte Wartung</th>
                                        <th class="sortable-header">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($items as $item)
                                        <tr>
                                            <td>
                                                <a href="{{ auth()->user()->can('manage maintenance') ? route('items.edit', $item) : route('items.show', $item) }}" class="fw-semibold text-decoration-none">{{ $item->name }}</a>
                                            </td>
                                            <td>{{ $item->itemGroup?->name }}</td>
                                            <td class="text-center" data-sort-value="{{ $item->maintenance_interval_months }}">{{ $item->maintenance_interval_months }} Mt.</td>
                                            <td class="text-nowrap" data-sort-value="{{ $item->latestMaintenance?->performed_on->format('Ymd') ?? 0 }}">
                                                {{ $item->latestMaintenance?->performed_on->format('d.m.Y') ?? 'noch nie' }}
                                            </td>
                                            <td data-sort-value="{{ $item->nextMaintenanceDue()?->format('Ymd') }}">
                                                @include('maintenance.status', ['item' => $item, 'status' => $maintenance->status($item)])
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                {{ $isDueOnly ? 'Zurzeit ist keine Wartung fällig.' : 'Noch kein Material mit Wartungsintervall.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

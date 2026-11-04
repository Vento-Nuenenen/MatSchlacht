@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ __('Lager') }}</span>
                        @can('create storage')
                            <a href="{{ route('storage-units.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> {{ __('Neuer Lagerort') }}
                            </a>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($locations->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="fa fa-warehouse fa-3x mb-3 opacity-25"></i>
                                <p class="mb-0">{{ __('Noch keine Lagerorte erfasst.') }}</p>
                            </div>
                        @else
                            @include('storage-units.tree', ['units' => $locations, 'depth' => 0])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Benutzer</span>
                        @can('create users')
                            <a href="{{ route('users.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> Neuer Benutzer
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="sortable-header" style="min-width: 120px;">Pfadiname</th>
                                        <th scope="col" class="sortable-header" style="min-width: 140px;">Name</th>
                                        <th scope="col" class="sortable-header">Mail</th>
                                        <th scope="col" class="sortable-header">Einheiten</th>
                                        <th scope="col" class="sortable-header text-center" style="min-width: 90px;">Login</th>
                                        <th scope="col" class="sortable-header text-center" style="min-width: 70px;">Aktiv</th>
                                        <th scope="col" class="text-nowrap" style="min-width: 140px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="fw-semibold">{{ $user->scoutname }}</td>
                                            <td>{{ $user->fullName(false) }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($user->roles as $role)
                                                        <span class="badge bg-pink">{{ $role->name }}</span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($user->isAzureUser())
                                                    <span class="badge bg-primary">Azure AD</span>
                                                @elseif($user->isHitobitoUser())
                                                    <span class="badge bg-info text-dark">Hitobito</span>
                                                @else
                                                    <span class="badge bg-secondary">Lokal</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($user->aktiv)
                                                    <span class="badge bg-success">Ja</span>
                                                @else
                                                    <span class="badge bg-danger">Nein</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                                    @can('edit users')
                                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    @endcan

                                                    @if(!$user->isExternalUser())
                                                        <form action="{{ route('users.password-reset', $user->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary btn-sm">Passwort-Reset</button>
                                                        </form>
                                                    @endif

                                                    @can('delete users')
                                                        @include('components.delete', [
                                                            'route' => 'users.destroy',
                                                            'id' => $user->id
                                                        ])
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Keine Benutzer vorhanden.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

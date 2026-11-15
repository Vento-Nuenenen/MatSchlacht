<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        $users = auth()->user()->scopedUsersQuery()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->orderBy('id')
            ->paginate($this->perPage());

        return view('user.index', ['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
    {
        return view('user.create', ['roles' => auth()->user()->scopedRoles()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        /** @var array<int, string> $roles */
        $roles = $request->validated('roles', []);
        $user->syncRoles($roles);

        if (! $user->isExternalUser()) {
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return redirect()->route('users.index')->with('success', __('Neuer Benutzer wurde erfolgreich erstellt.'));
    }

    /**
     * Send a password reset link to the user.
     */
    public function sendResetLink(User $user): RedirectResponse
    {
        $this->authorize('sendResetLink', $user);

        if ($user->isExternalUser()) {
            return redirect()->back()->with('error', __('Passwort-Reset für Benutzer mit externem Login nicht möglich.'));
        }

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->back()->with('success', __('Passwort-Reset-Link wurde erfolgreich gesendet.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Factory|View
    {
        return view('user.edit', ['user' => $user, 'roles' => auth()->user()->scopedRoles()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        /** @var array<int, string> $roles */
        $roles = $request->validated('roles', []);
        $user->syncRoles(auth()->user()->rolesIncludingUnmanaged($user, $roles));

        return redirect()->route('users.index')->with('success', __('Benutzer wurde erfolgreich aktualisiert.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->orders()->exists()) {
            return back()->with('error', __(':fullname hat Bestellungen und kann nicht gelöscht werden.', ['fullname' => $user->fullName()]));
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', __('Benutzer wurde erfolgreich gelöscht.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view users')->only(['index', 'show']);
        $this->middleware('permission:create users')->only(['create', 'store']);
        $this->middleware('permission:edit users')->only(['edit', 'update']);
        $this->middleware('permission:delete users')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        return view('user.index', ['users' => User::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
    {
        $roles = Role::all();

        return view('user.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        // Assign selected roles (if any)
        $user->syncRoles($request->input('roles', []));

        if (! $user->isAzureUser()) {
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return redirect()->route('users.index')->with('success', 'Neuer Benutzer wurde erfolgreich erstellt.');
    }

    /**
     * Send a password reset link to the user.
     */
    public function sendResetLink(User $user): RedirectResponse
    {
        if ($user->isAzureUser()) {
            return redirect()->back()->with('error', 'Passwort-Reset für Office 365 Benutzer nicht möglich.');
        }

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->back()->with('success', 'Passwort-Reset-Link wurde erfolgreich gesendet.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Factory|View
    {
        $roles = Role::all();

        return view('user.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        // Sync roles assignment (empty clears all)
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Benutzer wurde erfolgreich gelöscht.');
    }
}

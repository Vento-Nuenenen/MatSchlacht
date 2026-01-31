<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        return view('profile.index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        if ($request->user()->isAzureUser()) {
            return back()->with('error', 'Azure-Benutzer können ihr Passwort hier nicht ändern.');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Passwort erfolgreich aktualisiert.');
    }
}

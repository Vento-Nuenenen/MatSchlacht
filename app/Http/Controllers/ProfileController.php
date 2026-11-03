<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit(): Factory|View
    {
        return view('profile.index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's password.
     *
     * @return RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        if ($request->user()->isExternalUser()) {
            return back()->with('error', 'Benutzer mit externem Login können ihr Passwort hier nicht ändern.');
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

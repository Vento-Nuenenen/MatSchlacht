<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
     * Change the language of the user.
     */
    public function updateLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('app.available_locales')))],
        ]);

        $request->user()->update($validated);

        return back();
    }

    /**
     * Update the user's password.
     *
     * @return RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        if ($request->user()->isExternalUser()) {
            return back()->with('error', __('Benutzer mit externem Login können ihr Passwort hier nicht ändern.'));
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', __('Passwort erfolgreich aktualisiert.'));
    }
}

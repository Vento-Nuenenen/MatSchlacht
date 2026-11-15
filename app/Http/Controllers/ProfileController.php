<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
    public function updateLocale(UpdateLocaleRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back();
    }

    /**
     * Update the user's password. The model hashes it.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', __('Passwort erfolgreich aktualisiert.'));
    }
}

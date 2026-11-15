<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Lets a privileged account sign in as another user to test what they see and may do,
 * and return to the own account afterwards.
 */
class ImpersonationController extends Controller
{
    /**
     * Session key holding the id of the account that started the impersonation.
     */
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        $impersonator = $request->user();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $impersonator->id);

        Log::info('Impersonation started', ['impersonator_id' => $impersonator->id, 'user_id' => $user->id]);

        return redirect()->route('overwatch')
            ->with('success', __('Du bist jetzt als :name angemeldet.', ['name' => $user->fullName()]));
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get(self::SESSION_KEY);

        abort_unless(is_int($impersonatorId), 403);

        $impersonator = User::find($impersonatorId);
        $user = $request->user();

        if ($impersonator === null || ! $impersonator->aktiv || ! $impersonator->isPrivileged()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Auth::login($impersonator);
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        Log::info('Impersonation ended', ['impersonator_id' => $impersonator->id, 'user_id' => $user?->id]);

        return redirect()->route('users.index')
            ->with('success', __('Du bist wieder mit deinem eigenen Konto angemeldet.'));
    }

    /**
     * Whether the current session is an impersonation.
     */
    public static function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }
}

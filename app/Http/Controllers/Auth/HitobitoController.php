<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HitobitoTestController;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\Hitobito\HitobitoAccess;
use App\Services\Hitobito\HitobitoConnector;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\User as SocialiteUser;

class HitobitoController extends Controller
{
    public function __construct(
        protected HitobitoConnector $connector,
        protected HitobitoAccess $access,
    ) {}

    public function redirectToHitobito(): RedirectResponse
    {
        if (! $this->connector->isConfigured()) {
            return redirect()->route('login')->with('error', __('Der Hitobito-Login ist noch nicht eingerichtet.'));
        }

        return $this->connector->provider()->redirect();
    }

    /**
     * Log in a person from Hitobito. Access is checked on every login, so that
     * people who lost their group or role cannot get in anymore.
     */
    public function handleHitobitoCallback(): RedirectResponse
    {
        $isTest = session()->pull(HitobitoTestController::SESSION_KEY, false) && auth()->user()?->can('manage hitobito access');

        try {
            /** @var SocialiteUser $hitobitoUser */
            $hitobitoUser = $this->connector->provider()->user();
        } catch (Exception $exception) {
            return $isTest
                ? redirect()->route('hitobito-test.index')->with('error', __('Die Testanmeldung ist fehlgeschlagen: :getmessage', ['getmessage' => $exception->getMessage()]))
                : redirect()->route('login')->with('error', __('Die Anmeldung mit Hitobito ist fehlgeschlagen.'));
        }

        /** @var array<string, mixed> $profile */
        $profile = $hitobitoUser->getRaw();

        if ($isTest) {
            return redirect()->route('hitobito-test.index')->with(HitobitoTestController::RESULT_KEY, $profile);
        }

        if ($this->access->admittingRules($profile)->isEmpty()) {
            Log::info('Hitobito login refused, no access rule applies.', [
                'hitobito_id' => $hitobitoUser->getId(),
                'role_classes' => array_column(is_array($profile['roles'] ?? null) ? $profile['roles'] : [], 'role_class'),
            ]);

            return redirect()->route('login')->with('error', __('Dein Hitobito-Konto ist für diese App nicht freigeschaltet.'));
        }

        if (blank($hitobitoUser->getEmail())) {
            return redirect()->route('login')->with('error', __('Dein Hitobito-Konto hat keine E-Mail-Adresse.'));
        }

        $user = $this->syncUser($hitobitoUser, $profile);

        if (app(ComponentManager::class)->setting('hitobito_login', 'sync_group_roles')) {
            $this->syncRoles($user, $profile);
        }

        Auth::login($user);

        return redirect()->intended(route('overwatch'));
    }

    /**
     * Find the account of the person. An account is only linked by its e-mail address
     * as long as it does not belong to another Hitobito person.
     *
     * @param  array<string, mixed>  $profile
     */
    protected function syncUser(SocialiteUser $hitobitoUser, array $profile): User
    {
        $hitobitoId = (string) $hitobitoUser->getId();
        $email = (string) $hitobitoUser->getEmail();

        $user = User::where('hitobito_id', $hitobitoId)->first()
            ?? User::where('email', $email)->whereNull('hitobito_id')->first()
            ?? new User(['password' => null]);

        $isEmailTaken = User::where('email', $email)->whereKeyNot($user->getKey())->exists();

        $user->fill([
            'hitobito_id' => $hitobitoId,
            'firstname' => $profile['first_name'] ?? $user->firstname ?? '',
            'lastname' => $profile['last_name'] ?? $user->lastname ?? '',
            'scoutname' => $profile['nickname'] ?? $user->scoutname,
            'email' => $isEmailTaken ? $user->email : $email,
        ])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function syncRoles(User $user, array $profile): void
    {
        $roles = $this->access->appRoles($profile);

        if ($roles->isNotEmpty()) {
            $user->syncRoles($roles);
        }
    }
}

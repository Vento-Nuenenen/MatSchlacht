<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ComponentManager;
use App\Services\Hitobito\HitobitoAccess;
use App\Services\Hitobito\HitobitoConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tries the Hitobito login without logging in or creating an account: it
 * only shows the data of the instance and what a real login would do with it.
 */
class HitobitoTestController extends Controller
{
    /**
     * Marks the next callback as a test.
     */
    public const SESSION_KEY = 'hitobito_test';

    /**
     * The profile of the last test, only kept for the next request.
     */
    public const RESULT_KEY = 'hitobito_test_profile';

    public function __construct(
        protected HitobitoConnector $connector,
        protected HitobitoAccess $access,
        protected ComponentManager $components,
    ) {}

    public function index(): View
    {
        Gate::authorize('manage hitobito access');

        /** @var array<string, mixed>|null $profile */
        $profile = session(self::RESULT_KEY);

        return view('hitobito_test.index', [
            'isConfigured' => $this->connector->isConfigured(),
            'baseUrl' => $this->connector->baseUrl(),
            'callbackUrl' => route('hitobito.callback'),
            'profile' => $profile,
            'result' => $profile !== null ? $this->result($profile) : null,
        ]);
    }

    /**
     * Start the login at Hitobito, the callback then returns to the test page.
     */
    public function start(): RedirectResponse
    {
        Gate::authorize('manage hitobito access');

        if (! $this->connector->isConfigured()) {
            return redirect()->route('hitobito-test.index')->with('error', __('Trage zuerst Client-ID und Client-Secret in den Einstellungen ein.'));
        }

        session()->put(self::SESSION_KEY, true);

        return $this->connector->provider()->redirect();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{rules: mixed, appRoles: mixed, isRoleSyncEnabled: bool, account: User|null}
     */
    protected function result(array $profile): array
    {
        return [
            'rules' => $this->access->admittingRules($profile),
            'appRoles' => $this->access->appRoles($profile),
            'isRoleSyncEnabled' => (bool) $this->components->setting('hitobito_login', 'sync_group_roles'),
            'account' => User::where('hitobito_id', (string) ($profile['id'] ?? ''))->first()
                ?? User::where('email', (string) ($profile['email'] ?? ''))->whereNull('hitobito_id')->first(),
        ];
    }
}

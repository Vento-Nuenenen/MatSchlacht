<?php

namespace App\Http\Controllers;

use App\Enums\FileStorageProvider;
use App\Services\FileStorage\OAuthConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class FileStorageConnectionController extends Controller
{
    protected const STATE_SESSION_KEY = 'file_storage_oauth_state';

    public function __construct(protected OAuthConnector $connector) {}

    /**
     * Send the administrator to the login page of Dropbox or Google.
     */
    public function connect(Request $request, FileStorageProvider $provider): RedirectResponse
    {
        Gate::authorize('manage settings');
        abort_unless($provider->usesOAuth(), 404);

        if (! $this->connector->hasAppCredentials($provider)) {
            return redirect()->route('settings.components.index')
                ->with('error', "Bitte zuerst die App-Zugangsdaten für {$provider->label()} eintragen und speichern.");
        }

        $state = Str::random(40);
        $request->session()->put(self::STATE_SESSION_KEY, $state);

        return redirect()->away($this->connector->authorizationUrl($provider, $this->redirectUri($provider), $state));
    }

    /**
     * Receive the authorization code and store the refresh token.
     */
    public function callback(Request $request, FileStorageProvider $provider): RedirectResponse
    {
        Gate::authorize('manage settings');
        abort_unless($provider->usesOAuth(), 404);

        $expectedState = $request->session()->pull(self::STATE_SESSION_KEY);
        $settings = redirect()->route('settings.components.index');

        if (! is_string($expectedState) || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return $settings->with('error', "Die Verbindung zu {$provider->label()} wurde abgebrochen (ungültiger Status). Bitte erneut versuchen.");
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return $settings->with('error', "Die Verbindung zu {$provider->label()} wurde nicht erlaubt: ".($request->query('error_description') ?? $request->query('error') ?? 'kein Code erhalten'));
        }

        try {
            $this->connector->connect($provider, (string) $request->query('code'), $this->redirectUri($provider));
        } catch (Throwable $exception) {
            return $settings->with('error', "Verbindung zu {$provider->label()} fehlgeschlagen: {$exception->getMessage()}");
        }

        return $settings->with('success', "{$provider->label()} ist verbunden.");
    }

    protected function redirectUri(FileStorageProvider $provider): string
    {
        return route('settings.file-storage.callback', $provider);
    }
}

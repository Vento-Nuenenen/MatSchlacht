<?php

namespace App\Services\Hitobito;

use App\Services\ComponentManager;
use Laravel\Socialite\Facades\Socialite;

/**
 * Builds the OAuth client from the credentials saved under "Einstellungen".
 */
class HitobitoConnector
{
    public function __construct(protected ComponentManager $components) {}

    public function isConfigured(): bool
    {
        return filled($this->components->setting('hitobito_login', 'client_id'))
            && filled($this->components->setting('hitobito_login', 'client_secret'));
    }

    public function provider(): HitobitoProvider
    {
        /** @var HitobitoProvider $provider */
        $provider = Socialite::buildProvider(HitobitoProvider::class, [
            'client_id' => $this->components->setting('hitobito_login', 'client_id'),
            'client_secret' => $this->components->setting('hitobito_login', 'client_secret'),
            'redirect' => route('hitobito.callback'),
        ]);

        return $provider->setBaseUrl($this->baseUrl());
    }

    /**
     * The address of the instance. Only its host counts, so that a pasted
     * link to the sign-in page of the instance works as well.
     */
    public function baseUrl(): string
    {
        $url = parse_url((string) $this->components->setting('hitobito_login', 'base_url'));
        $port = isset($url['port']) ? ":{$url['port']}" : '';

        return ($url['scheme'] ?? 'https').'://'.($url['host'] ?? '').$port;
    }
}

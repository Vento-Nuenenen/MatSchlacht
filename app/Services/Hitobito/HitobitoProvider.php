<?php

namespace App\Services\Hitobito;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * OAuth 2 login against a Hitobito instance, e.g. the MiData of the Pfadibewegung Schweiz.
 */
class HitobitoProvider extends AbstractProvider
{
    /**
     * The profile scope that also returns the roles of a person in their groups.
     */
    protected const string PROFILE_SCOPE = 'with_roles';

    /**
     * @var array<int, string>
     */
    protected $scopes = [self::PROFILE_SCOPE];

    protected $scopeSeparator = ' ';

    protected string $baseUrl = '';

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    /**
     * @param  string  $state
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase("{$this->baseUrl}/oauth/authorize", $state);
    }

    protected function getTokenUrl(): string
    {
        return "{$this->baseUrl}/oauth/token";
    }

    /**
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get("{$this->baseUrl}/oauth/profile", [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
                'X-Scope' => self::PROFILE_SCOPE,
            ],
        ]);

        return (array) json_decode((string) $response->getBody(), true);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => (string) ($user['id'] ?? ''),
            'nickname' => $user['nickname'] ?? null,
            'name' => trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')),
            'email' => $user['email'] ?? null,
        ]);
    }
}

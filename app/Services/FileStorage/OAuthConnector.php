<?php

namespace App\Services\FileStorage;

use App\Enums\FileStorageProvider;
use App\Services\ComponentManager;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Connects Dropbox and Google Drive through the OAuth authorization code flow and stores the
 * resulting refresh token (encrypted) in the file storage settings.
 */
class OAuthConnector
{
    public function __construct(
        protected ComponentManager $components,
        protected FileStorageManager $fileStorage,
    ) {}

    /**
     * Whether the app credentials (client ID and secret) needed to start the login are saved.
     */
    public function hasAppCredentials(FileStorageProvider $provider): bool
    {
        [$clientId, $clientSecret] = $this->appCredentials($provider);

        return filled($clientId) && filled($clientSecret);
    }

    public function isConnected(FileStorageProvider $provider): bool
    {
        return filled($this->components->setting('file_storage', $this->refreshTokenSetting($provider)));
    }

    /**
     * The URL of the provider's login and consent page.
     */
    public function authorizationUrl(FileStorageProvider $provider, string $redirectUri, string $state): string
    {
        [$clientId] = $this->appCredentials($provider);

        return match ($provider) {
            FileStorageProvider::Dropbox => 'https://www.dropbox.com/oauth2/authorize?'.http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'token_access_type' => 'offline',
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]),
            FileStorageProvider::GoogleDrive => 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'scope' => GoogleDriveAdapter::SCOPE,
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]),
            default => throw new InvalidArgumentException(__(':label wird nicht über OAuth verbunden.', ['label' => $provider->label()])),
        };
    }

    /**
     * Exchange the authorization code for a refresh token and save it.
     */
    public function connect(FileStorageProvider $provider, string $code, string $redirectUri): void
    {
        [$clientId, $clientSecret] = $this->appCredentials($provider);

        $response = match ($provider) {
            FileStorageProvider::Dropbox => Http::asForm()->withBasicAuth((string) $clientId, (string) $clientSecret)->post(DropboxAdapter::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]),
            FileStorageProvider::GoogleDrive => Http::asForm()->post(GoogleDriveAdapter::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
            ]),
            default => throw new InvalidArgumentException(__(':label wird nicht über OAuth verbunden.', ['label' => $provider->label()])),
        };

        $refreshToken = $response->json('refresh_token');

        if (! $response->successful() || ! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException((string) ($response->json('error_description') ?? $response->json('error') ?? __('Status :status', ['status' => $response->status()])));
        }

        $this->components->updateSetting('file_storage', $this->refreshTokenSetting($provider), $refreshToken);
        $this->fileStorage->forgetDisks();
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    protected function appCredentials(FileStorageProvider $provider): array
    {
        return match ($provider) {
            FileStorageProvider::Dropbox => [
                $this->components->setting('file_storage', 'dropbox_app_key'),
                $this->components->setting('file_storage', 'dropbox_app_secret'),
            ],
            FileStorageProvider::GoogleDrive => [
                $this->components->setting('file_storage', 'google_client_id'),
                $this->components->setting('file_storage', 'google_client_secret'),
            ],
            default => [null, null],
        };
    }

    protected function refreshTokenSetting(FileStorageProvider $provider): string
    {
        return match ($provider) {
            FileStorageProvider::Dropbox => 'dropbox_refresh_token',
            FileStorageProvider::GoogleDrive => 'google_refresh_token',
            default => throw new InvalidArgumentException(__(':label wird nicht über OAuth verbunden.', ['label' => $provider->label()])),
        };
    }
}

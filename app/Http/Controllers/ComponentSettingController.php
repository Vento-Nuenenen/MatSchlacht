<?php

namespace App\Http\Controllers;

use App\Enums\FileStorageProvider;
use App\Http\Requests\UpdateComponentSettingsRequest;
use App\Services\ComponentManager;
use App\Services\FileStorage\FileStorageManager;
use App\Services\FileStorage\OAuthConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ComponentSettingController extends Controller
{
    public function __construct(
        protected ComponentManager $components,
        protected FileStorageManager $fileStorage,
        protected OAuthConnector $oauth,
    ) {}

    /**
     * Show all app components with their state and settings.
     */
    public function index(): View
    {
        Gate::authorize('manage settings');

        $components = collect($this->components->definitions())->map(fn (array $definition, string $component) => [
            'label' => $definition['label'],
            'description' => $definition['description'],
            'enabled' => $this->components->isEnabled($component),
            'toggleable' => $this->components->isToggleable($component),
            'settings' => collect($definition['settings'])->map(fn (array $setting, string $key) => [
                ...$setting,
                // Secrets are never sent to the browser, the form only shows whether one is set.
                'value' => $setting['type'] === 'secret' ? null : $this->components->setting($component, $key),
                'isSet' => $setting['type'] === 'secret' && filled($this->components->setting($component, $key)),
            ])->all(),
        ])->all();

        $oauthProviders = collect(FileStorageProvider::cases())
            ->filter(fn (FileStorageProvider $provider) => $provider->usesOAuth())
            ->map(fn (FileStorageProvider $provider) => [
                'provider' => $provider,
                'isConnected' => $this->oauth->isConnected($provider),
                'redirectUri' => route('settings.file-storage.callback', $provider),
            ])
            ->values();

        return view('settings.components', compact('components', 'oauthProviders'));
    }

    /**
     * Save the component states and settings.
     */
    public function update(UpdateComponentSettingsRequest $request): RedirectResponse
    {
        /** @var array<string, array{enabled?: mixed, settings?: array<string, mixed>}> $values */
        $values = $request->validated('components');

        $this->components->update($values);
        $this->fileStorage->forgetDisks();

        return redirect()->route('settings.components.index')
            ->with('success', 'Einstellungen wurden gespeichert.');
    }

    /**
     * Write, read and delete a test file on the saved file storage provider.
     */
    public function testFileStorage(): RedirectResponse
    {
        Gate::authorize('manage settings');

        $provider = $this->fileStorage->activeProvider();
        $error = $this->fileStorage->testConnection();

        return redirect()->route('settings.components.index')->with(
            $error === null ? 'success' : 'error',
            $error === null
                ? "Verbindung zu «{$provider->label()}» funktioniert: Testdatei geschrieben, gelesen und gelöscht."
                : "Verbindung zu «{$provider->label()}» fehlgeschlagen: {$error}"
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateComponentSettingsRequest;
use App\Services\ComponentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ComponentSettingController extends Controller
{
    public function __construct(protected ComponentManager $components) {}

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
            'settings' => collect($definition['settings'])->map(fn (array $setting, string $key) => [
                ...$setting,
                'value' => $this->components->setting($component, $key),
            ])->all(),
        ])->all();

        return view('settings.components', compact('components'));
    }

    /**
     * Save the component states and settings.
     */
    public function update(UpdateComponentSettingsRequest $request): RedirectResponse
    {
        /** @var array<string, array{enabled?: mixed, settings?: array<string, mixed>}> $values */
        $values = $request->validated('components');

        $this->components->update($values);

        return redirect()->route('settings.components.index')
            ->with('success', 'Einstellungen wurden gespeichert.');
    }
}

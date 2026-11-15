<?php

namespace App\Http\Controllers;

use App\Enums\TransitionScope;
use App\Http\Requests\UpdateOrderWorkflowRequest;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderWorkflowController extends Controller
{
    public function __construct(protected OrderWorkflow $workflow) {}

    /**
     * Show the transitions of the order state machine with their configuration.
     */
    public function edit(): View
    {
        Gate::authorize('manage settings');

        return view('settings.order-workflow', [
            'transitions' => $this->workflow->transitions(),
            'roles' => $this->workflow->configurableRoles(),
            'scopes' => TransitionScope::cases(),
            'privilegedRole' => OrderWorkflow::PRIVILEGED_ROLE,
        ]);
    }

    /**
     * Save which transitions are enabled, send a mail and may be performed by which unit.
     */
    public function update(UpdateOrderWorkflowRequest $request): RedirectResponse
    {
        /** @var array<string, array{enabled?: mixed, mail?: mixed, roles?: array<int|string, mixed>}> $values */
        $values = $request->validated('transitions');

        $this->workflow->update($values);

        return redirect()->route('settings.order-workflow.edit')
            ->with('success', __('Bestellablauf wurde gespeichert.'));
    }
}

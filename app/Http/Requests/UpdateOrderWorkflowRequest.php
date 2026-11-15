<?php

namespace App\Http\Requests;

use App\Enums\TransitionScope;
use App\Services\OrderWorkflow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage settings');
    }

    /**
     * Get the validation rules that apply to the request. Only known transitions and
     * configurable units have a rule, everything else is dropped.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workflow = app(OrderWorkflow::class);
        $roleIds = $workflow->configurableRoles()->modelKeys();
        $rules = ['transitions' => ['required', 'array']];

        foreach (array_keys($workflow->transitions()) as $key) {
            $rules["transitions.{$key}.enabled"] = ['required', 'boolean'];
            $rules["transitions.{$key}.mail"] = ['required', 'boolean'];

            foreach ($roleIds as $roleId) {
                $rules["transitions.{$key}.roles.{$roleId}"] = ['nullable', Rule::enum(TransitionScope::class)];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $workflow = app(OrderWorkflow::class);
        $roles = $workflow->configurableRoles();
        $attributes = [];

        foreach ($workflow->transitions() as $key => $transition) {
            $name = "{$transition['fromLabel']} → {$transition['toLabel']}";
            $attributes["transitions.{$key}.enabled"] = __(':transition: Aktiv', ['transition' => $name]);
            $attributes["transitions.{$key}.mail"] = __(':transition: Mail senden', ['transition' => $name]);

            foreach ($roles as $role) {
                $attributes["transitions.{$key}.roles.{$role->id}"] = "{$name}: {$role->name}";
            }
        }

        return $attributes;
    }
}

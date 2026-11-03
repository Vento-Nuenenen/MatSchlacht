<?php

namespace App\Http\Requests;

use App\Services\ComponentManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateComponentSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage settings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = ['components' => ['required', 'array']];

        foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
            $rules["components.{$component}.enabled"] = ['required', 'boolean'];

            foreach ($definition['settings'] as $key => $setting) {
                $rules["components.{$component}.settings.{$key}"] = match ($setting['type']) {
                    'bool' => ['required', 'boolean'],
                    'integer' => ['required', 'integer', 'min:0', 'max:3650'],
                    'email_list' => ['nullable', 'string', 'max:1000', $this->emailListRule()],
                    default => ['nullable', 'string', 'max:255'],
                };
            }
        }

        return $rules;
    }

    /**
     * Ensure that at least one login method stays enabled, so nobody gets locked out.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isPasswordLoginEnabled = $this->boolean('components.password_login.enabled');
                $isAzureLoginEnabled = $this->boolean('components.azure_login.enabled');

                if (! $isPasswordLoginEnabled && ! $isAzureLoginEnabled) {
                    $validator->errors()->add('components.password_login.enabled', 'Mindestens eine Login-Methode (Passwort oder Azure) muss aktiv bleiben.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
            $attributes["components.{$component}.enabled"] = $definition['label'];

            foreach ($definition['settings'] as $key => $setting) {
                $attributes["components.{$component}.settings.{$key}"] = $setting['label'];
            }
        }

        return $attributes;
    }

    /**
     * Validate a comma separated list of e-mail addresses.
     *
     * @return Closure(string, mixed, Closure(string): void): void
     */
    protected function emailListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $fail("«{$email}» ist keine gültige E-Mail-Adresse.");
                }
            }
        };
    }
}

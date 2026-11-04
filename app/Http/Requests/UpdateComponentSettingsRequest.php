<?php

namespace App\Http\Requests;

use App\Enums\FileStorageProvider;
use App\Services\ComponentManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            if ($definition['toggleable'] ?? true) {
                $rules["components.{$component}.enabled"] = ['required', 'boolean'];
            }

            foreach ($definition['settings'] as $key => $setting) {
                $rules["components.{$component}.settings.{$key}"] = $setting['rules'] ?? match ($setting['type']) {
                    'bool' => ['required', 'boolean'],
                    'integer' => ['required', 'integer', 'min:0', 'max:3650'],
                    'select' => ['required', Rule::in(array_keys($setting['options'] ?? []))],
                    'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'secret' => ['nullable', 'string', 'max:10000'],
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
                    $validator->errors()->add('components.password_login.enabled', __('Mindestens eine Login-Methode (Passwort oder Azure) muss aktiv bleiben.'));
                }
            },
            function (Validator $validator): void {
                $provider = FileStorageProvider::tryFrom((string) $this->input('components.file_storage.settings.provider'));
                $requiredSettings = match ($provider) {
                    FileStorageProvider::S3 => ['s3_region', 's3_bucket'],
                    FileStorageProvider::Sftp => ['sftp_host', 'sftp_username'],
                    FileStorageProvider::Ftp => ['ftp_host', 'ftp_username'],
                    FileStorageProvider::SharePoint => ['sharepoint_drive_id'],
                    FileStorageProvider::Nextcloud => ['nextcloud_url', 'nextcloud_username'],
                    FileStorageProvider::Dropbox => ['dropbox_app_key'],
                    FileStorageProvider::GoogleDrive => ['google_client_id'],
                    default => [],
                };

                foreach ($requiredSettings as $key) {
                    if (blank($this->input("components.file_storage.settings.{$key}"))) {
                        $label = __((string) config("components.file_storage.settings.{$key}.label"));
                        $validator->errors()->add("components.file_storage.settings.{$key}", __('«:label» ist für :label2 erforderlich.', ['label' => $label, 'label2' => $provider->label()]));
                    }
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
            $attributes["components.{$component}.enabled"] = __($definition['label']);

            foreach ($definition['settings'] as $key => $setting) {
                $attributes["components.{$component}.settings.{$key}"] = __($setting['label']);
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
                    $fail(__('«:email» ist keine gültige E-Mail-Adresse.', ['email' => $email]));
                }
            }
        };
    }
}

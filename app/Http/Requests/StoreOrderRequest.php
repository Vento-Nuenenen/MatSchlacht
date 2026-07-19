<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'event' => 'required|string|max:255',
            'event_start' => 'required|date',
            'event_end' => 'required|date|after_or_equal:event_start',

            'role_id' => 'required|exists:roles,id',
            'user_id' => 'required|exists:users,id',

            'items' => 'nullable|array',
            'items.*' => 'nullable|integer|min:0',

            'collections' => 'nullable|array',
            'collections.*' => 'exists:item_collections,id',

            'action' => 'required|in:save,order',
        ];
    }
}

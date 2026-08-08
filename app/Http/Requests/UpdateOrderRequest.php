<?php

namespace App\Http\Requests;

use App\Rules\CheckItemStock;
use App\Rules\CheckReturnedQuantity;
use App\Rules\RoleInScope;
use App\Rules\UserInScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event' => 'required|string|max:255',
            'event_start' => 'required|date',
            'event_end' => 'required|date|after_or_equal:event_start',

            'role_id' => ['required', 'exists:roles,id', new RoleInScope],
            'user_id' => ['required', 'exists:users,id', new UserInScope],

            'items' => ['nullable', 'array', new CheckItemStock],
            'items.*' => ['integer', 'min:0', 'max:100000', new CheckItemStock],

            'collections' => ['nullable', 'array', new CheckItemStock],
            'collections.*' => 'exists:item_collections,id',

            'returned' => 'nullable|array',
            'returned.*' => ['nullable', 'integer', 'min:0', new CheckReturnedQuantity],

            'action' => 'nullable|in:save,order',
        ];
    }
}

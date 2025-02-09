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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event' => 'required',
            'event_start' => 'required',
            'event_end' => 'required',
            'handover_date' => 'required',
            'return_date' => 'required',
            'status' => 'required',

            'unit_id' => 'required',
            'user_id' => 'required',
        ];
    }
}

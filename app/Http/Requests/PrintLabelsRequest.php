<?php

namespace App\Http\Requests;

use App\Services\Pdf\LabelSheetPdf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrintLabelsRequest extends FormRequest
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
            'skip' => ['nullable', 'integer', 'min:0', 'max:'.(LabelSheetPdf::LABELS_PER_SHEET - 1)],
            'with_children' => ['nullable', 'boolean'],
            'with_items' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array', 'max:500'],
            'items.*' => ['integer', 'exists:items,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'skip' => __('Bereits verwendete Etiketten'),
            'items' => __('Material'),
        ];
    }
}

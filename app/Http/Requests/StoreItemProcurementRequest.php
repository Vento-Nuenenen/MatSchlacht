<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Rules\MaxServerFileSize;
use App\Services\ComponentManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreItemProcurementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage procurements');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'procured_on' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'warranty_until' => ['nullable', 'date', 'after_or_equal:procured_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'add_to_stock' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,svg', new MaxServerFileSize],
        ];
    }

    /**
     * The stock of items stored in bins is the sum of the bins, so a procurement cannot change it.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $newStockContribution = $this->boolean('add_to_stock') ? $this->integer('quantity') : 0;
                $isStockFromBins = app(ComponentManager::class)->isEnabled('storage') && $this->item()->stocks()->exists();

                if ($isStockFromBins && $newStockContribution !== $this->previousStockContribution()) {
                    $validator->errors()->add('add_to_stock', 'Dieses Material liegt auf Lagerplätzen. Bitte die Menge dort einlagern.');
                }
            },
        ];
    }

    /**
     * The validated procurement fields, with the price converted to cents.
     *
     * @return array<string, mixed>
     */
    public function procurementData(): array
    {
        $unitPrice = $this->validated('unit_price');

        return [
            ...collect($this->validated())->except(['unit_price', 'add_to_stock', 'files', 'remove_attachments'])->all(),
            'unit_price_cents' => $unitPrice === null ? null : (int) round((float) $unitPrice * 100),
            'added_to_stock' => $this->boolean('add_to_stock'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'procured_on' => 'Beschaffungsdatum',
            'quantity' => 'Menge',
            'supplier' => 'Lieferant',
            'unit_price' => 'Stückpreis',
            'invoice_number' => 'Beleg-Nr.',
            'warranty_until' => 'Garantie bis',
            'notes' => 'Notiz',
            'add_to_stock' => 'Zum Bestand addieren',
            'files' => 'Dateien',
            'files.*' => 'Datei',
        ];
    }

    protected function item(): Item
    {
        /** @var Item $item */
        $item = $this->route('item');

        return $item;
    }

    /**
     * How much the procurement already added to the stock before this request.
     */
    protected function previousStockContribution(): int
    {
        return 0;
    }
}

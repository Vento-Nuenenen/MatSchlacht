<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ItemProcurement;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateItemProcurementRequest extends StoreItemProcurementRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
        ];
    }

    public function procurement(): ItemProcurement
    {
        /** @var ItemProcurement $procurement */
        $procurement = $this->route('itemProcurement');

        return $procurement;
    }

    protected function item(): Item
    {
        return $this->procurement()->item;
    }

    protected function previousStockContribution(): int
    {
        return $this->procurement()->added_to_stock ? $this->procurement()->quantity : 0;
    }
}

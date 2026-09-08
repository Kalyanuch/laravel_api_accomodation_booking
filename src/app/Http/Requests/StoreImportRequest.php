<?php

namespace App\Http\Requests;

use App\Data\Imports\CreateImportData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportRequest extends FormRequest
{
    public function toDto(): CreateImportData
    {
        return CreateImportData::fromArray($this->validated());
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier' => ['required', 'string', 'max:100', Rule::exists('suppliers', 'code')],
            'external_import_id' => ['required', 'string', 'max:255'],
            'sent_at' => ['required', 'date'],
            'offers' => ['required', 'array', 'min:1'],
            'offers.*.external_id' => ['required', 'string', 'max:255', 'distinct:strict'],
            'offers.*.property' => ['required', 'array:code,name,city'],
            'offers.*.property.code' => ['required', 'string', 'max:100'],
            'offers.*.property.name' => ['required', 'string', 'max:255'],
            'offers.*.property.city' => ['required', 'string', 'max:100'],
            'offers.*.check_in' => ['required', 'date_format:Y-m-d'],
            'offers.*.check_out' => ['required', 'date_format:Y-m-d', 'after:offers.*.check_in'],
            'offers.*.max_guests' => ['required', 'integer', 'min:1', 'max:65535'],
            'offers.*.price' => ['required', 'integer', 'min:0'],
            'offers.*.currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'offers.*.available_units' => ['required', 'integer', 'min:0'],
            'offers.*.expires_at' => ['required', 'date'],
        ];
    }
}

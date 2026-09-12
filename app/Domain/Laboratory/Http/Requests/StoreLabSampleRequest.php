<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_order_id' => ['required', 'uuid', 'exists:lab_orders,id'],
            'sample_type' => ['required', 'string', 'max:50'],
            'container_type' => ['required', 'string', 'max:100'],
            'collection_site' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

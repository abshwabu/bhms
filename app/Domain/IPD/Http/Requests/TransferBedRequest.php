<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_bed_id' => ['required', 'uuid', 'exists:beds,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

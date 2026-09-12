<?php

namespace App\Domain\Radiology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitChunkedUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:2000'],
            'total_size_bytes' => ['required', 'integer', 'min:1'],
            'report_id' => ['nullable', 'uuid', 'exists:imaging_reports,id'],
        ];
    }
}

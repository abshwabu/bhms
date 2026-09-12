<?php

namespace App\Domain\Radiology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendImagingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amendment_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'findings' => ['nullable', 'string', 'max:4000'],
            'impression' => ['required', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:1000'],
            'technique' => ['nullable', 'string', 'max:1000'],
            'comparison' => ['nullable', 'string', 'max:1000'],
            'critical_alert' => ['nullable', 'boolean'],
            'critical_alert_communicated_to' => ['nullable', 'string', 'max:255'],
            'finalize' => ['nullable', 'boolean'],
        ];
    }
}

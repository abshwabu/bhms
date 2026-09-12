<?php

namespace App\Domain\Radiology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImagingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imaging_order_id' => ['required', 'uuid', 'exists:imaging_orders,id'],
            'radiologist_id' => ['nullable', 'uuid', 'exists:users,id'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'technique' => ['nullable', 'string', 'max:1000'],
            'comparison' => ['nullable', 'string', 'max:1000'],
            'findings' => ['nullable', 'string', 'max:4000'],
            'impression' => ['required', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:1000'],
            'critical_alert' => ['nullable', 'boolean'],
            'critical_alert_communicated_to' => ['nullable', 'string', 'max:255'],
            'finalize' => ['nullable', 'boolean'], // If true, report is immediately finalized & digitally signed
        ];
    }
}

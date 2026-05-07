<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAlertRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'location_id'  => 'required|exists:locations,id',
            'risk_type'    => 'required|string|exists:risk_types,code',
            'channel'      => 'required|in:sms,whatsapp,both',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}

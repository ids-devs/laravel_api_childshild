<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'              => 'required|string|max:255',
            'message'            => 'required|string|max:1000',
            'channel'            => 'required|in:sms,whatsapp,both',
            'target_provinces'   => 'nullable|array',
            'target_provinces.*' => 'string',
            'target_districts'   => 'nullable|array',
            'target_districts.*' => 'string',
            'target_risk_level'  => 'in:medium,high,critical,all',
            'status'             => 'in:draft,scheduled',
            'scheduled_at'       => 'nullable|date|after:now',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterClinicUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:clinic_users,email|max:255',
            'password'          => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'organization_name' => 'nullable|string|max:255',
            'organization_type' => 'required|in:clinic,ong,government,unicef,admin',
            'location_id'       => 'nullable|exists:locations,id',
        ];
    }
}

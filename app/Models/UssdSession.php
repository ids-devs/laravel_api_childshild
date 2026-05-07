<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UssdSession extends Model
{
    protected $fillable = [
        'session_id', 'phone_hash', 'service_code',
        'current_step', 'session_data', 'is_active',
    ];

    protected $casts = [
        'session_data' => 'array',
        'is_active'    => 'boolean',
    ];

    public function getData(string $key, mixed $default = null): mixed
    {
        return data_get($this->session_data, $key, $default);
    }

    public function setData(string $key, mixed $value): void
    {
        $data = $this->session_data ?? [];
        data_set($data, $key, $value);
        $this->update(['session_data' => $data]);
    }
}

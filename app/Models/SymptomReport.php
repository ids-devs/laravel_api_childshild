<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Community symptom report for epidemiological surveillance.
 */
class SymptomReport extends Model
{
    protected $fillable = [
        'user_id', 'location_id', 'symptoms', 'notes', 'channel',
    ];

    protected $casts = [
        'symptoms' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

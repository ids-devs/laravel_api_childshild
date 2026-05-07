<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskType extends Model
{
    protected $fillable = [
        'name', 'code', 'description',
    ];

    public function riskScores(): HasMany
    {
        return $this->hasMany(RiskScore::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

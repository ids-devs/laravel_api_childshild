<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'region',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}

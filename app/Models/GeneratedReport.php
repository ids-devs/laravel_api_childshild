<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    protected $fillable = [
        'name',
        'period_start',
        'period_end',
        'province',
        'district',
        'report_type',
        'format',
        'status',
        'download_url',
        'alerts_count',
        'families_covered',
        'error_message',
        'location_id',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClinicUser::class, 'created_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'title', 'message', 'channel',
        'target_provinces', 'target_districts', 'target_risk_level',
        'status', 'recipients_total', 'recipients_sent',
        'scheduled_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'target_provinces' => 'array',
        'target_districts' => 'array',
        'scheduled_at'     => 'datetime',
        'sent_at'          => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClinicUser::class, 'created_by');
    }

    public function scopeDraft($query) { return $query->where('status', 'draft'); }
    public function scopeScheduled($query) { return $query->where('status', 'scheduled'); }
}

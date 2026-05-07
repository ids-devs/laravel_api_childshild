<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Alert dispatched to families in a geographic zone.
 */
class Alert extends Model
{
    protected $fillable = [
        'location_id', 'risk_score_id', 'risk_type_id', 'risk_level',
        'message_pt', 'message_changane', 'message_sena', 'message_macua', 'message_ndau',
        'channel', 'status',
        'recipients_total', 'recipients_sent', 'recipients_failed',
        'delivery_report', 'sent_at', 'scheduled_at', 'created_by',
    ];

    protected $casts = [
        'delivery_report' => 'array',
        'sent_at'         => 'datetime',
        'scheduled_at'    => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function riskScore(): BelongsTo
    {
        return $this->belongsTo(RiskScore::class);
    }

    public function riskType(): BelongsTo
    {
        return $this->belongsTo(RiskType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClinicUser::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AlertDelivery::class);
    }

    /** Get message in user's preferred language */
    public function getMessageForLanguage(string $lang): string
    {
        $field = "message_{$lang}";
        return $this->$field ?? $this->message_pt;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}

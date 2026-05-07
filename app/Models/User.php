<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Family user registered via USSD/SMS/WhatsApp.
 *
 * @property int $id
 * @property string $phone_number_encrypted
 * @property string $phone_hash
 * @property string $channel
 * @property string $language
 * @property int|null $location_id
 * @property bool $consent_status
 * @property bool $subscription_active
 */
class User extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'phone_number_encrypted', 'phone_hash',
        'channel', 'language', 'location_id',
        'consent_status', 'subscription_active',
        'consent_given_at', 'last_interaction_at',
    ];

    protected $hidden = ['phone_number_encrypted'];

    protected $casts = [
        'consent_status'      => 'boolean',
        'subscription_active' => 'boolean',
        'consent_given_at'    => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    // ─── Phone encryption helpers ─────────────────────────────────────────

    public static function encryptPhone(string $phone): string
    {
        $key = config('app.encryption_key') ?: config('app.key');
        return DB::selectOne("SELECT encode(pgp_sym_encrypt(?, ?), 'base64') AS enc", [$phone, $key])->enc;
    }

    public static function decryptPhone(string $encrypted): string
    {
        $key = config('app.encryption_key') ?: config('app.key');
        return DB::selectOne("SELECT pgp_sym_decrypt(decode(?, 'base64'), ?) AS dec", [$encrypted, $key])->dec;
    }

    public static function hashPhone(string $phone): string
    {
        return hash('sha256', config('app.key') . $phone);
    }

    public function getPhoneNumberAttribute(): string
    {
        return static::decryptPhone($this->phone_number_encrypted);
    }

    // ─── Relationships ────────────────────────────────────────────────────

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function household(): HasOne
    {
        return $this->hasOne(Household::class);
    }

    public function alertDeliveries(): HasMany
    {
        return $this->hasMany(AlertDelivery::class);
    }

    public function symptomReports(): HasMany
    {
        return $this->hasMany(SymptomReport::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActiveSubscribers($query)
    {
        return $query->where('subscription_active', true)->where('consent_status', true);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}

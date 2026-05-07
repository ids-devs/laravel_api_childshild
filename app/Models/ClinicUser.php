<?php

namespace App\Models;

use App\Models\OrganizationType;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Dashboard user (clinic, ONG, government, UNICEF, admin).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $organization_type  clinic|ong|government|unicef|admin
 */
class ClinicUser extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'name', 'email', 'password',
        'organization_name', 'organization_type_id',
        'location_id', 'is_active',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'          => 'boolean',
        'last_login_at'      => 'datetime',
        'email_verified_at'  => 'datetime',
    ];

    // ─── JWT interface ────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email'             => $this->email,
            'organization_type' => $this->organization_type,
            'roles'             => $this->getRoleNames(),
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByOrgType($query, string $type)
    {
        return $query->whereHas('organizationType', fn ($q) => $q->where('code', $type));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->organization_type === 'admin' || $this->hasRole('super-admin');
    }

    public function canAccessAllZones(): bool
    {
        return in_array($this->organization_type, ['government', 'unicef', 'admin']);
    }

    /**
     * Backward-compatible virtual attribute mapped to organization_types.code.
     */
    public function getOrganizationTypeAttribute(): ?string
    {
        if ($this->relationLoaded('organizationType')) {
            return $this->organizationType?->code;
        }

        if (! $this->organization_type_id) {
            return null;
        }

        return $this->organizationType()->value('code');
    }

    /**
     * Allow assigning organization_type string while persisting organization_type_id.
     */
    public function setOrganizationTypeAttribute(?string $value): void
    {
        if (! $value) {
            $this->attributes['organization_type_id'] = null;

            return;
        }

        $id = OrganizationType::query()->where('code', $value)->value('id');

        if ($id) {
            $this->attributes['organization_type_id'] = $id;
        }
    }
}

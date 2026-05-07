<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Household data associated with a family user.
 */
class Household extends Model
{
    protected $fillable = [
        'user_id', 'number_of_children',
        'children_age_groups', 'pregnant_woman',
        'weeks_pregnant', 'vulnerability_score',
    ];

    protected $casts = [
        'children_age_groups' => 'array',
        'pregnant_woman'      => 'boolean',
        'number_of_children'  => 'integer',
        'vulnerability_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate household vulnerability score (0-100).
     * Higher score = more vulnerable to climate risks.
     */
    public function calculateVulnerability(): float
    {
        $score = 0;

        // Children under 5 increase vulnerability significantly
        $ageGroups = $this->children_age_groups ?? [];
        if (in_array('0-1', $ageGroups)) $score += 30;
        if (in_array('1-5', $ageGroups)) $score += 20;
        if (in_array('6-12', $ageGroups)) $score += 10;

        // Pregnant women are high risk
        if ($this->pregnant_woman) {
            $score += 25;
            if ($this->weeks_pregnant === '1-12') $score += 5; // early trimester risk
        }

        // Number of children
        $score += min($this->number_of_children * 5, 20);

        return min(100, $score);
    }
}

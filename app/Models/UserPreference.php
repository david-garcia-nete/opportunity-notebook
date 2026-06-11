<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    public const DEFAULT_WEIGHT = 5;

    public const WEIGHT_FIELDS = [
        'income_weight' => 'Income',
        'probability_weight' => 'Probability',
        'time_to_revenue_weight' => 'Time To Revenue',
        'strategic_alignment_weight' => 'Strategic Alignment',
        'personal_interest_weight' => 'Personal Interest',
        'skill_growth_weight' => 'Skill Growth',
        'family_fit_weight' => 'Family Fit',
        'risk_weight' => 'Risk',
    ];

    protected $fillable = [
        'user_id',
        'income_weight',
        'probability_weight',
        'time_to_revenue_weight',
        'strategic_alignment_weight',
        'personal_interest_weight',
        'skill_growth_weight',
        'family_fit_weight',
        'risk_weight',
    ];

    public static function defaults(): array
    {
        return collect(array_keys(self::WEIGHT_FIELDS))
            ->mapWithKeys(fn (string $field) => [$field => self::DEFAULT_WEIGHT])
            ->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return collect(array_keys(self::WEIGHT_FIELDS))
            ->mapWithKeys(fn (string $field) => [$field => 'integer'])
            ->all();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OpportunityDecision extends Model
{
    public const DECISION_TYPES = [
        'focus',
        'continue',
        'intensify',
        'park',
        'abandon',
        'reopen',
    ];

    public const REASON_CATEGORIES = [
        'capacity',
        'financial_return',
        'strategic_alignment',
        'skill_gap',
        'relationship_gap',
        'market_timing',
        'risk_too_high',
        'better_alternative',
        'personal_interest',
        'other',
    ];

    protected $fillable = [
        'opportunity_id',
        'review_id',
        'decision_type',
        'reason_category',
        'notes',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function decisionTypeLabel(): string
    {
        return Str::headline($this->decision_type);
    }

    public function reasonCategoryLabel(): string
    {
        return Str::headline($this->reason_category);
    }

    public static function decisionTypeOptions(): array
    {
        return collect(self::DECISION_TYPES)
            ->mapWithKeys(fn (string $type) => [$type => Str::headline($type)])
            ->all();
    }

    public static function reasonCategoryOptions(): array
    {
        return collect(self::REASON_CATEGORIES)
            ->mapWithKeys(fn (string $category) => [$category => Str::headline($category)])
            ->all();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Review extends Model
{
    public const REVIEW_TYPES = [
        'daily',
        'weekly',
        'focus',
        'portfolio',
    ];

    protected $fillable = [
        'review_type',
        'started_at',
        'completed_at',
        'summary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function opportunityDecisions(): HasMany
    {
        return $this->hasMany(OpportunityDecision::class);
    }

    public function reviewTypeLabel(): string
    {
        return Str::headline($this->review_type);
    }

    public static function reviewTypeOptions(): array
    {
        return collect(self::REVIEW_TYPES)
            ->mapWithKeys(fn (string $type) => [$type => Str::headline($type)])
            ->all();
    }
}

<?php

namespace App\Models;

use App\Support\Statuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = Statuses::normalizeApplication($value) ?? $value;
    }

    protected $fillable = [
        'opportunity_id',
        'applied_at',
        'status',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}

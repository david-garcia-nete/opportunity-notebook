<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Action extends Model
{
    protected $fillable = [
        'opportunity_id',
        'title',
        'description',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function status(): string
    {
        if ($this->completed_at !== null) {
            return 'Completed';
        }

        if ($this->due_date !== null && $this->due_date->isBefore(Carbon::today())) {
            return 'Overdue';
        }

        return 'Open';
    }
}

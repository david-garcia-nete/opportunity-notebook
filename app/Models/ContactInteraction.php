<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInteraction extends Model
{
    public const TYPES = [
        'Email',
        'Phone Call',
        'Meeting',
        'Coffee Chat',
        'Interview',
        'Referral',
        'Introduction',
        'Networking Event',
        'Other',
    ];

    protected $fillable = [
        'contact_id',
        'opportunity_id',
        'interaction_date',
        'interaction_type',
        'summary',
        'outcome',
        'next_follow_up_date',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    protected function casts(): array
    {
        return [
            'interaction_date' => 'date',
            'next_follow_up_date' => 'date',
        ];
    }
}

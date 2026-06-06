<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = [
        'title',
        'company',
        'type',
        'status',
        'score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }
}

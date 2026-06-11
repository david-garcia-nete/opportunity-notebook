<?php

namespace App\Services;

use App\Models\OpportunityGap;

class GapActionTemplateService
{
    public function title(OpportunityGap $gap): string
    {
        return 'Close Gap: '.$gap->title;
    }

    public function description(OpportunityGap $gap): string
    {
        $gap->loadMissing('opportunity');

        return collect([
            'Gap title: '.$gap->title,
            'Gap description: '.($gap->description ?: 'No description provided.'),
            'Gap category: '.$gap->category,
            'Gap priority: '.$gap->priority,
            'Related opportunity: '.($gap->opportunity?->title ?: 'No opportunity linked'),
            'Opportunity name: '.($gap->opportunity?->title ?: 'No opportunity linked'),
        ])->implode("\n");
    }

    public function defaults(OpportunityGap $gap): array
    {
        $gap->loadMissing('opportunity');

        return [
            'title' => $this->title($gap),
            'description' => $this->description($gap),
            'opportunity_id' => $gap->opportunity_id,
            'opportunity_gap_id' => $gap->id,
        ];
    }
}

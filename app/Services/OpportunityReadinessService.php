<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Support\Statuses;
use Illuminate\Support\Collection;

class OpportunityReadinessService
{
    private const BASE_SCORE = 100;

    private const OPEN_GAP_PENALTIES = [
        'Critical' => -25,
        'High' => -15,
        'Medium' => -5,
        'Low' => -2,
    ];

    private const COMPLETED_GAP_BONUS = 5;
    private const LINKED_PROJECT_BONUS = 10;

    public function score(Opportunity $opportunity): int
    {
        return max(0, min(100, $this->rawScore($opportunity)));
    }

    public function breakdown(Opportunity $opportunity): Collection
    {
        $gaps = $this->gaps($opportunity);
        $projects = $this->projects($opportunity);
        $openGaps = $gaps->where('status', Statuses::GAP_OPEN);
        $completedGapCount = $gaps->where('status', Statuses::GAP_COMPLETE)->count();
        $projectCount = $projects->count();

        $items = collect([
            [
                'label' => 'Starting Readiness',
                'count' => null,
                'points' => self::BASE_SCORE,
            ],
            [
                'label' => 'Projects',
                'count' => $projectCount,
                'points' => $projectCount * self::LINKED_PROJECT_BONUS,
            ],
            [
                'label' => 'Completed Gaps',
                'count' => $completedGapCount,
                'points' => $completedGapCount * self::COMPLETED_GAP_BONUS,
            ],
        ]);

        foreach (self::OPEN_GAP_PENALTIES as $priority => $penalty) {
            $count = $openGaps->where('priority', $priority)->count();

            $items->push([
                'label' => $priority.' Gaps',
                'count' => $count,
                'points' => $count * $penalty,
            ]);
        }

        $items->push([
            'label' => 'Total',
            'count' => null,
            'points' => $this->score($opportunity),
        ]);

        return $items;
    }

    public function indicators(Opportunity $opportunity): array
    {
        $gaps = $this->gaps($opportunity);
        $score = $this->score($opportunity);

        return [
            'score' => $score,
            'status' => $this->statusForScore($score),
            'open_gaps_count' => $gaps->where('status', Statuses::GAP_OPEN)->count(),
            'completed_gaps_count' => $gaps->where('status', Statuses::GAP_COMPLETE)->count(),
            'open_critical_gaps_count' => $gaps->where('status', Statuses::GAP_OPEN)->where('priority', 'Critical')->count(),
            'open_high_gaps_count' => $gaps->where('status', Statuses::GAP_OPEN)->where('priority', 'High')->count(),
            'projects_count' => $this->projects($opportunity)->count(),
            'applications_count' => $this->applications($opportunity)->count(),
            'strategic_objectives_count' => $this->strategicObjectives($opportunity)->count(),
            'is_low_readiness' => $score < 50,
        ];
    }

    public function statusForScore(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Ready',
            $score >= 70 => 'Mostly Ready',
            $score >= 50 => 'Needs Preparation',
            default => 'Significant Gaps',
        };
    }

    public function dashboardSummaries(Collection $opportunities): Collection
    {
        return $opportunities
            ->map(fn (Opportunity $opportunity) => [
                'opportunity' => $opportunity,
                'indicators' => $this->indicators($opportunity),
            ])
            ->values();
    }

    private function rawScore(Opportunity $opportunity): int
    {
        $gaps = $this->gaps($opportunity);
        $projects = $this->projects($opportunity);
        $openGapPenalty = $gaps
            ->where('status', Statuses::GAP_OPEN)
            ->sum(fn ($gap) => self::OPEN_GAP_PENALTIES[$gap->priority] ?? 0);

        return self::BASE_SCORE
            + $openGapPenalty
            + ($gaps->where('status', Statuses::GAP_COMPLETE)->count() * self::COMPLETED_GAP_BONUS)
            + ($projects->count() * self::LINKED_PROJECT_BONUS);
    }

    private function gaps(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('opportunityGaps')) {
            return $opportunity->opportunityGaps;
        }

        return $opportunity->opportunityGaps()->get();
    }

    private function projects(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('projects')) {
            return $opportunity->projects;
        }

        return $opportunity->projects()->get();
    }

    private function applications(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('applications')) {
            return $opportunity->applications;
        }

        return $opportunity->applications()->get();
    }

    private function strategicObjectives(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('strategicObjectives')) {
            return $opportunity->strategicObjectives;
        }

        return $opportunity->strategicObjectives()->get();
    }
}

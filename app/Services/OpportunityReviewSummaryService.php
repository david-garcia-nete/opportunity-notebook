<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Support\Statuses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OpportunityReviewSummaryService
{
    private const HIGH_SCORE = 75;
    private const HIGH_READINESS = 70;
    private const LOW_READINESS = 50;

    public function __construct(private OpportunityStrategicContextService $context)
    {
    }

    public function summarize(Opportunity|array $opportunityOrContext): array
    {
        $context = $opportunityOrContext instanceof Opportunity
            ? $this->context->build($opportunityOrContext)
            : $opportunityOrContext;

        return [
            'headline' => $this->headline($context),
            'strengths' => $this->strengths($context),
            'risks' => $this->risks($context),
            'blockers' => $this->blockers($context),
            'recent_progress' => $this->recentProgress($context),
            'decision_prompt' => $this->decisionPrompt($context),
            'suggested_next_action' => $this->suggestedNextAction($context),
        ];
    }

    private function headline(array $context): string
    {
        $title = $context['identity']['title'];
        $score = $this->score($context);
        $readiness = $context['scores']['readiness_score'];

        if ($score >= self::HIGH_SCORE && $readiness >= self::HIGH_READINESS) {
            return "Strong opportunity: {$title} is promising and ready to pursue.";
        }

        if ($score >= self::HIGH_SCORE && $readiness < self::LOW_READINESS) {
            return "Promising but blocked opportunity: {$title} needs readiness work before heavier pursuit.";
        }

        if ($score >= self::HIGH_SCORE) {
            return "Promising opportunity: {$title} is attractive but still needs preparation.";
        }

        if ($readiness < self::LOW_READINESS) {
            return "Blocked opportunity: {$title} needs readiness work before it can move confidently.";
        }

        return "Review needed: {$title} has useful signals but needs a clear next decision.";
    }

    private function strengths(array $context): array
    {
        $strengths = [];
        $score = $this->score($context);

        if ($score >= self::HIGH_SCORE) {
            $strengths[] = "High opportunity score ({$score}).";
        }

        if ($context['scores']['readiness_score'] >= self::HIGH_READINESS) {
            $strengths[] = "Readiness is {$context['scores']['readiness_status']} ({$context['scores']['readiness_score']}).";
        }

        if ($context['focus']['is_focus']) {
            $strengths[] = 'This is a focus opportunity.';
        }

        if (count($context['strategic_objectives']) > 0) {
            $strengths[] = 'Linked to '.Str::plural('strategic objective', count($context['strategic_objectives'])).'.';
        }

        if (count($context['themes']) > 0) {
            $strengths[] = 'Connected to '.Str::plural('theme', count($context['themes'])).': '.$this->joinNames($context['themes']).'.';
        }

        if (count($context['actions']['completed_recent']) > 0) {
            $strengths[] = 'Recent action completed: '.$context['actions']['completed_recent'][0]['title'].'.';
        }

        if ($context['outcome_learning']['lesson_learned']) {
            $strengths[] = 'Outcome lesson available for future decisions.';
        }

        return $strengths ?: ['No major strengths identified yet.'];
    }

    private function risks(array $context): array
    {
        $risks = [];
        $openActions = $context['actions']['open'];
        $overdueActions = $this->overdueActions($context);

        if ($this->isOpenForNextAction($context) && count($openActions) === 0) {
            $risks[] = 'Missing next action.';
        }

        if (count($overdueActions) > 0) {
            $risks[] = Str::plural('Overdue action', count($overdueActions)).' may be stale.';
        }

        if ($context['scores']['weighted_score'] === null) {
            $risks[] = 'Missing weighted evaluation.';
        }

        if (count($context['contacts']) === 0) {
            $risks[] = 'No relationship contacts linked.';
        }

        return $risks ?: ['No immediate risks identified.'];
    }

    private function blockers(array $context): array
    {
        $blockers = [];

        if ($context['scores']['readiness_score'] < self::LOW_READINESS) {
            $blockers[] = 'Low readiness.';
        }

        foreach ($context['gaps']['critical'] as $gap) {
            $blockers[] = 'Critical gap: '.$gap['title'].'.';
        }

        return $blockers ?: ['No blockers identified.'];
    }

    private function recentProgress(array $context): array
    {
        $progress = [];

        foreach ($context['actions']['completed_recent'] as $action) {
            $progress[] = 'Completed action: '.$action['title'].'.';
        }

        foreach ($context['decisions']['recent'] as $decision) {
            if (in_array($decision['decision_type'], ['park', 'abandon', 'reopen'], true)) {
                $progress[] = 'Recent strategic change: '.$decision['decision_type_label'].'.';
            } else {
                $progress[] = 'Recent decision: '.$decision['decision_type_label'].'.';
            }
        }

        if ($context['outcome_learning']['lesson_learned']) {
            $progress[] = 'Outcome lesson: '.$context['outcome_learning']['lesson_learned'];
        }

        return $progress ?: ['No recent progress recorded.'];
    }

    private function decisionPrompt(array $context): string
    {
        $latestDecision = $context['decisions']['recent'][0] ?? null;

        if ($latestDecision && in_array($latestDecision['decision_type'], ['park', 'abandon'], true)) {
            $state = $latestDecision['decision_type'] === 'park' ? 'parked' : 'abandoned';

            return 'Confirm whether this opportunity should stay '.$state.' or be reopened.';
        }

        if ($latestDecision && $latestDecision['decision_type'] === 'reopen') {
            return 'Decide whether the reopened opportunity deserves focus time now.';
        }

        if ($context['scores']['readiness_score'] < self::LOW_READINESS) {
            return 'Decide whether to invest in readiness or park this until the blockers are resolved.';
        }

        if ($this->score($context) >= self::HIGH_SCORE && $context['scores']['readiness_score'] >= self::HIGH_READINESS) {
            return 'Decide whether to intensify pursuit while readiness and score are strong.';
        }

        return 'Decide whether to continue, intensify, park, or abandon based on the next evidence gathered.';
    }

    private function suggestedNextAction(array $context): string
    {
        $overdueActions = $this->overdueActions($context);
        $dueFollowUps = $this->dueFollowUps($context);
        $openActions = $context['actions']['open'];
        $criticalGapWithoutAction = collect($context['gaps']['critical'])
            ->first(fn (array $gap) => $gap['open_actions_count'] === 0);

        if (count($overdueActions) > 0) {
            return 'Clear or revise overdue action: '.$overdueActions[0]['title'].'.';
        }

        if (count($dueFollowUps) > 0) {
            return 'Follow up with '.$dueFollowUps[0]['contact_name'].' about this opportunity.';
        }

        if ($criticalGapWithoutAction) {
            return 'Create an action plan for critical gap: '.$criticalGapWithoutAction['title'].'.';
        }

        if (count($openActions) === 0) {
            return 'Define the next concrete action for this opportunity.';
        }

        return 'Complete next action: '.$openActions[0]['title'].'.';
    }

    private function score(array $context): int
    {
        return $context['scores']['weighted_score']
            ?? $context['scores']['forecast_score']
            ?? $context['scores']['computed_score']
            ?? $context['scores']['manual_score']
            ?? 0;
    }

    private function overdueActions(array $context): array
    {
        return collect($context['actions']['open'])
            ->filter(fn (array $action) => $action['status'] === 'Overdue')
            ->values()
            ->all();
    }

    private function dueFollowUps(array $context): array
    {
        return collect($context['recent_contact_interactions'])
            ->filter(fn (array $interaction) => $interaction['next_follow_up_date'] !== null)
            ->filter(fn (array $interaction) => Carbon::parse($interaction['next_follow_up_date'])->lte(today()))
            ->values()
            ->all();
    }

    private function joinNames(array $items): string
    {
        return collect($items)
            ->pluck('name')
            ->filter()
            ->take(3)
            ->join(', ');
    }

    private function isOpenForNextAction(array $context): bool
    {
        return ! in_array($context['identity']['status'], Statuses::unavailableForNextActionOpportunities(), true);
    }
}

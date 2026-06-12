<?php

namespace App\Services;

use App\Models\Opportunity;
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
        $score = $this->opportunityScore($context);
        $readiness = $context['scores']['readiness_score'];

        if ($score >= self::HIGH_SCORE && $readiness >= self::HIGH_READINESS) {
            return "Strong opportunity: {$title} is promising and ready to pursue.";
        }

        if ($score >= self::HIGH_SCORE && $readiness < self::LOW_READINESS) {
            return "Promising but blocked opportunity: {$title} has strong upside, but readiness is low.";
        }

        if ($readiness < self::LOW_READINESS) {
            return "Blocked opportunity: {$title} needs readiness work before it can move cleanly.";
        }

        if ($context['focus']['is_focus']) {
            return "Focus opportunity: {$title} needs a clear next move.";
        }

        return "Review opportunity: {$title} has enough context for a practical next decision.";
    }

    private function strengths(array $context): array
    {
        $strengths = [];
        $score = $this->opportunityScore($context);
        $readiness = $context['scores']['readiness_score'];

        if ($score >= self::HIGH_SCORE) {
            $strengths[] = 'High opportunity score indicates meaningful upside.';
        }

        if ($readiness >= self::HIGH_READINESS) {
            $strengths[] = "Readiness is {$context['scores']['readiness_status']}.";
        }

        if (! empty($context['strategic_objectives'])) {
            $strengths[] = 'Linked to strategic objective: '.$context['strategic_objectives'][0]['name'].'.';
        }

        if (! empty($context['themes'])) {
            $strengths[] = 'Aligned with theme: '.$context['themes'][0]['name'].'.';
        }

        if (! empty($context['contacts'])) {
            $strengths[] = Str::plural('Relationship', count($context['contacts'])).' attached to this opportunity.';
        }

        if (! empty($context['actions']['completed_recent'])) {
            $strengths[] = 'Recent execution exists: '.$context['actions']['completed_recent'][0]['title'].'.';
        }

        return $strengths ?: ['No clear strengths have been surfaced yet.'];
    }

    private function risks(array $context): array
    {
        $risks = [];
        $openActions = $context['actions']['open'];
        $overdueActions = $this->overdueActions($context);

        if ($context['focus']['is_focus'] && empty($openActions)) {
            $risks[] = 'Missing next action.';
        }

        if (! empty($overdueActions)) {
            $risks[] = Str::plural('Overdue action', count($overdueActions)).' may be slowing progress.';
        }

        if ($this->opportunityScore($context) < 40) {
            $risks[] = 'Low opportunity score may limit the return on effort.';
        }

        if (empty($context['contacts'])) {
            $risks[] = 'No relationship support is linked yet.';
        }

        return $risks ?: ['No immediate risks detected by the current rules.'];
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

        return $blockers ?: ['No blockers detected by the current rules.'];
    }

    private function recentProgress(array $context): array
    {
        $progress = [];

        foreach (array_slice($context['actions']['completed_recent'], 0, 2) as $action) {
            $progress[] = 'Completed action: '.$action['title'].'.';
        }

        foreach ($context['decisions']['recent'] as $decision) {
            if (in_array($decision['decision_type'], ['park', 'abandon', 'reopen'], true)) {
                $progress[] = 'Recent strategic change: '.$decision['decision_type_label'].'.';
                break;
            }
        }

        if ($context['outcome_learning']['lesson_learned']) {
            $progress[] = 'Outcome lesson: '.$context['outcome_learning']['lesson_learned'];
        } elseif ($context['outcome_learning']['outcome_notes']) {
            $progress[] = 'Outcome note: '.$context['outcome_learning']['outcome_notes'];
        }

        if (! empty($context['recent_contact_interactions'])) {
            $interaction = $context['recent_contact_interactions'][0];
            $progress[] = 'Recent relationship activity: '.$interaction['interaction_type'].' with '.($interaction['contact_name'] ?? 'a contact').'.';
        }

        return $progress ?: ['No recent progress recorded yet.'];
    }

    private function decisionPrompt(array $context): string
    {
        $strategicChange = collect($context['decisions']['recent'])
            ->first(fn (array $decision) => in_array($decision['decision_type'], ['park', 'abandon', 'reopen'], true));

        if ($strategicChange) {
            return 'Recent decision was '.$strategicChange['decision_type_label'].'. Should this opportunity stay on that path or be revisited?';
        }

        if (! empty($context['gaps']['critical']) || $context['scores']['readiness_score'] < self::LOW_READINESS) {
            return 'What must be true to unblock this opportunity, and is it worth the effort now?';
        }

        if ($this->opportunityScore($context) >= self::HIGH_SCORE && $context['scores']['readiness_score'] >= self::HIGH_READINESS) {
            return 'Is this strong enough to intensify or make it the next priority?';
        }

        return 'Should you continue, park, or revise the next action for this opportunity?';
    }

    private function suggestedNextAction(array $context): string
    {
        $overdueActions = $this->overdueActions($context);

        if (! empty($overdueActions)) {
            return 'Clear or revise overdue action: '.$overdueActions[0]['title'].'.';
        }

        $dueFollowUps = $this->dueFollowUps($context);

        if (! empty($dueFollowUps)) {
            $followUp = $dueFollowUps[0];

            return 'Follow up with '.($followUp['contact_name'] ?? 'a relationship contact').' about this opportunity.';
        }

        if (! empty($context['gaps']['critical'])) {
            return 'Create or update an action plan for critical gap: '.$context['gaps']['critical'][0]['title'].'.';
        }

        if ($context['focus']['is_focus'] && empty($context['actions']['open'])) {
            return 'Define the next concrete action for this focus opportunity.';
        }

        if (! empty($context['actions']['open'])) {
            return 'Work the next open action: '.$context['actions']['open'][0]['title'].'.';
        }

        return 'Review whether this opportunity should continue, be parked, or be closed.';
    }

    private function opportunityScore(array $context): int
    {
        return $context['scores']['weighted_score']
            ?? $context['scores']['forecast_score']
            ?? $context['scores']['manual_score']
            ?? $context['scores']['computed_score']
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
            ->filter(function (array $interaction) {
                if (! $interaction['next_follow_up_date']) {
                    return false;
                }

                return Carbon::parse($interaction['next_follow_up_date'])->lessThanOrEqualTo(today());
            })
            ->values()
            ->all();
    }
}

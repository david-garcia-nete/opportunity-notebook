<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\OpportunityGap;
use App\Models\User;
use App\Services\OpportunityReviewSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityReviewSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_score_and_high_readiness_produces_positive_headline(): void
    {
        $opportunity = $this->highScoreOpportunity();

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertSame(
            'Strong opportunity: Strategic Retainer is promising and ready to pursue.',
            $summary['headline']
        );
    }

    public function test_high_score_and_low_readiness_produces_blocked_headline(): void
    {
        $opportunity = $this->highScoreOpportunity();
        $this->addCriticalGap($opportunity, 'Missing compliance credential');
        $this->addCriticalGap($opportunity, 'No enterprise case study');
        $this->addCriticalGap($opportunity, 'Weak implementation proof');

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertSame(
            'Promising but blocked opportunity: Strategic Retainer has strong upside, but readiness is low.',
            $summary['headline']
        );
    }

    public function test_missing_next_action_appears_as_risk(): void
    {
        $opportunity = $this->highScoreOpportunity(['is_focus' => true, 'focused_at' => now()]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Missing next action.', $summary['risks']);
    }

    public function test_critical_gaps_appear_as_blockers(): void
    {
        $opportunity = $this->highScoreOpportunity();
        $this->addCriticalGap($opportunity, 'Missing senior sponsor');

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Critical gap: Missing senior sponsor.', $summary['blockers']);
    }

    public function test_overdue_actions_influence_suggested_next_action(): void
    {
        $opportunity = $this->highScoreOpportunity();
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send revised proposal',
            'due_date' => today()->subDay(),
        ]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertSame('Clear or revise overdue action: Send revised proposal.', $summary['suggested_next_action']);
    }

    public function test_recent_decision_appears_in_recent_progress_or_decision_prompt(): void
    {
        $opportunity = $this->highScoreOpportunity();
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'decision_type' => 'park',
            'reason_category' => 'capacity',
            'notes' => 'Pause until capacity improves.',
            'decided_at' => now(),
        ]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Recent strategic change: Park.', $summary['recent_progress']);
        $this->assertSame('Recent decision was Park. Should this opportunity stay on that path or be revisited?', $summary['decision_prompt']);
    }

    public function test_contacts_due_follow_ups_influence_suggested_next_action(): void
    {
        $opportunity = $this->highScoreOpportunity();
        $contact = Contact::create(['name' => 'Maya Chen']);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subWeek(),
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Discussed team goals.',
            'next_follow_up_date' => today(),
        ]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertSame('Follow up with Maya Chen about this opportunity.', $summary['suggested_next_action']);
    }

    public function test_opportunity_show_page_links_to_review_summary(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->highScoreOpportunity(['title' => 'Summary Link']);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Review Summary')
            ->assertSee(route('opportunities.review-summary', $opportunity));
    }

    public function test_review_summary_page_displays_structured_summary(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->highScoreOpportunity(['title' => 'Readable Summary']);

        $response = $this->actingAs($user)->get(route('opportunities.review-summary', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Opportunity Review Summary')
            ->assertSeeText('Readable Summary')
            ->assertSeeText('Headline')
            ->assertSeeText('Strengths')
            ->assertSeeText('Risks')
            ->assertSeeText('Blockers')
            ->assertSeeText('Recent Progress')
            ->assertSeeText('Decision Prompt')
            ->assertSeeText('Suggested Next Action');
    }

    private function highScoreOpportunity(array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Strategic Retainer',
            'status' => 'Active',
            'income_potential' => 10,
            'probability_of_success' => 10,
            'time_to_revenue' => 1,
            'strategic_alignment' => 10,
            'personal_interest' => 10,
            'skill_growth' => 10,
            'family_fit' => 10,
            'risk_level' => 1,
        ], $attributes));
    }

    private function addCriticalGap(Opportunity $opportunity, string $title): OpportunityGap
    {
        return OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => $title,
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
    }
}

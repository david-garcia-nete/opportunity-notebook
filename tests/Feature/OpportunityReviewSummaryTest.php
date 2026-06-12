<?php

namespace Tests\Feature;

use App\Models\Action;
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
        $opportunity = $this->highScoreOpportunity(['title' => 'Ready Advisory Retainer']);
        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Send scope', 'due_date' => today()->addDay()]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertStringContainsString('Strong opportunity', $summary['headline']);
        $this->assertStringContainsString('ready to pursue', $summary['headline']);
    }

    public function test_high_score_and_low_readiness_produces_blocked_headline(): void
    {
        $opportunity = $this->highScoreOpportunity(['title' => 'Blocked Advisory Retainer']);
        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Send scope', 'due_date' => today()->addDay()]);
        $this->criticalGap($opportunity, 'Missing case study');
        $this->criticalGap($opportunity, 'No executive sponsor');
        $this->criticalGap($opportunity, 'Unclear delivery plan');

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertStringContainsString('Promising but blocked opportunity', $summary['headline']);
    }

    public function test_missing_next_action_appears_as_risk(): void
    {
        $opportunity = $this->highScoreOpportunity();

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Missing next action.', $summary['risks']);
    }

    public function test_critical_gaps_appear_as_blockers(): void
    {
        $opportunity = $this->highScoreOpportunity();
        $this->criticalGap($opportunity, 'Missing proof of enterprise delivery');

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Critical gap: Missing proof of enterprise delivery.', $summary['blockers']);
    }

    public function test_overdue_actions_influence_suggested_next_action(): void
    {
        $opportunity = $this->highScoreOpportunity();
        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Reply to buyer', 'due_date' => today()->subDay()]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertSame('Clear or revise overdue action: Reply to buyer.', $summary['suggested_next_action']);
    }

    public function test_recent_decision_appears_in_recent_progress_or_decision_prompt(): void
    {
        $opportunity = $this->highScoreOpportunity();
        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Send scope', 'due_date' => today()->addDay()]);
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'decision_type' => 'park',
            'reason_category' => 'capacity',
            'notes' => 'Need to wait for capacity.',
            'decided_at' => now(),
        ]);

        $summary = app(OpportunityReviewSummaryService::class)->summarize($opportunity);

        $this->assertContains('Recent strategic change: Park.', $summary['recent_progress']);
        $this->assertStringContainsString('stay parked', $summary['decision_prompt']);
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
        $opportunity = $this->highScoreOpportunity(['title' => 'Structured Summary']);

        $response = $this->actingAs($user)->get(route('opportunities.review-summary', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Opportunity Review Summary')
            ->assertSeeText('Structured Summary')
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
            'title' => 'Strategic Opportunity',
            'status' => 'Active',
            'income_potential' => 9,
            'probability_of_success' => 9,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 9,
            'skill_growth' => 9,
            'family_fit' => 9,
            'risk_level' => 2,
        ], $attributes));
    }

    private function criticalGap(Opportunity $opportunity, string $title): OpportunityGap
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

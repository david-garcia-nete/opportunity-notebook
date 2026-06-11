<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\Project;
use App\Models\User;
use App\Services\OpportunityReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_score_calculation_uses_gaps_and_projects(): void
    {
        $opportunity = $this->createOpportunity('AI Consulting');
        $project = Project::create(['name' => 'AI Case Study', 'status' => 'Completed']);
        $opportunity->projects()->attach($project);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Proof gap', 'status' => 'Open', 'priority' => 'High']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Messaging gap', 'status' => 'Complete', 'priority' => 'Medium']);

        $score = app(OpportunityReadinessService::class)->score($opportunity->fresh(['opportunityGaps', 'projects']));

        $this->assertSame(100, $score);
    }

    public function test_critical_gaps_reduce_readiness(): void
    {
        $opportunity = $this->createOpportunity('Critical Gap Opportunity');
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);

        $score = app(OpportunityReadinessService::class)->score($opportunity->fresh('opportunityGaps'));

        $this->assertSame(75, $score);
    }

    public function test_completed_gaps_improve_readiness(): void
    {
        $opportunity = $this->createOpportunity('Completed Gap Opportunity');
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Closed proof gap', 'status' => 'Complete', 'priority' => 'High']);

        $score = app(OpportunityReadinessService::class)->score($opportunity->fresh('opportunityGaps'));

        $this->assertSame(80, $score);
    }

    public function test_projects_improve_readiness(): void
    {
        $opportunity = $this->createOpportunity('Project Opportunity');
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);
        $opportunity->projects()->attach(Project::create(['name' => 'Portfolio Proof', 'status' => 'Completed']));

        $score = app(OpportunityReadinessService::class)->score($opportunity->fresh(['opportunityGaps', 'projects']));

        $this->assertSame(85, $score);
    }

    public function test_readiness_score_is_capped_between_zero_and_one_hundred(): void
    {
        $opportunity = $this->createOpportunity('Capped Opportunity');

        foreach (range(1, 5) as $number) {
            OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical gap '.$number, 'status' => 'Open', 'priority' => 'Critical']);
        }

        $lowScore = app(OpportunityReadinessService::class)->score($opportunity->fresh('opportunityGaps'));
        $highOpportunity = $this->createOpportunity('High Capped Opportunity');
        foreach (range(1, 3) as $number) {
            $highOpportunity->projects()->attach(Project::create(['name' => 'Project '.$number, 'status' => 'Completed']));
        }

        $highScore = app(OpportunityReadinessService::class)->score($highOpportunity->fresh('projects'));

        $this->assertSame(0, $lowScore);
        $this->assertSame(100, $highScore);
    }

    public function test_dashboard_readiness_section_appears(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createOpportunity('Low Readiness Focus', ['is_focus' => true, 'focused_at' => now()]);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'High proof gap', 'status' => 'Open', 'priority' => 'High']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Second high proof gap', 'status' => 'Open', 'priority' => 'High']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Focus Opportunity Readiness')
            ->assertSeeText('Low Readiness Focus')
            ->assertSeeText('Significant Gaps')
            ->assertSeeText('Low readiness: prepare evidence or close priority gaps before heavier pursuit.');
    }

    public function test_portfolio_readiness_page_loads(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createOpportunity('Portfolio Readiness Lead', [
            'income_potential' => 8,
            'probability_of_success' => 8,
            'time_to_revenue' => 2,
            'strategic_alignment' => 8,
            'personal_interest' => 8,
            'skill_growth' => 8,
            'family_fit' => 8,
            'risk_level' => 2,
        ]);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'High proof gap', 'status' => 'Open', 'priority' => 'High']);
        $opportunity->projects()->attach(Project::create(['name' => 'Case Study', 'status' => 'Completed']));
        Application::create(['opportunity_id' => $opportunity->id, 'applied_at' => now(), 'status' => 'Applied']);

        $response = $this->actingAs($user)->get(route('portfolio-readiness'));

        $response
            ->assertOk()
            ->assertSeeText('Portfolio Readiness')
            ->assertSeeText('Portfolio Readiness Lead')
            ->assertSeeText('Weighted Score')
            ->assertSeeText('Readiness Score')
            ->assertSeeText('Mostly Ready')
            ->assertSeeText('Not Focus');
    }

    public function test_low_readiness_focus_opportunities_appear_in_queue(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createOpportunity('AI Consulting', ['is_focus' => true, 'focused_at' => now()]);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'High proof gap', 'status' => 'Open', 'priority' => 'High']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Second high proof gap', 'status' => 'Open', 'priority' => 'High']);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('AI Consulting opportunity is not yet ready for pursuit.')
            ->assertSeeText('Priority 5 · Low readiness focus opportunity')
            ->assertSeeText('Review portfolio readiness and close the most important evidence gaps.');
    }

    public function test_readiness_breakdown_renders_on_opportunity_detail(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createOpportunity('Breakdown Opportunity');
        $opportunity->projects()->attach(Project::create(['name' => 'Proof Project', 'status' => 'Completed']));
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Critical proof gap', 'status' => 'Open', 'priority' => 'Critical']);
        OpportunityGap::create(['opportunity_id' => $opportunity->id, 'title' => 'Closed proof gap', 'status' => 'Complete', 'priority' => 'High']);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Portfolio Readiness')
            ->assertSeeText('Readiness Breakdown')
            ->assertSeeText('Projects (1)')
            ->assertSeeText('+10')
            ->assertSeeText('Completed Gaps (1)')
            ->assertSeeText('+5')
            ->assertSeeText('Critical Gaps (1)')
            ->assertSeeText('-25')
            ->assertSeeText('Total')
            ->assertSeeText('90')
            ->assertSeeText('Ready');
    }

    private function createOpportunity(string $title, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => $title,
            'status' => 'Active',
        ], $attributes));
    }
}

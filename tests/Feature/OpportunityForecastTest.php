<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\User;
use App\Services\OpportunityForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_score_calculation_combines_weighted_readiness_and_execution_health(): void
    {
        $opportunity = $this->scoredOpportunity('Balanced Strong Forecast', 8, 3);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send tailored outreach',
            'due_date' => today()->addDay(),
        ]);

        $forecast = app(OpportunityForecastService::class);

        $this->assertSame(92, $forecast->score($opportunity->fresh(['actions', 'opportunityGaps.actions', 'projects'])));
    }

    public function test_execution_health_applies_transparent_penalties(): void
    {
        $opportunity = $this->scoredOpportunity('Penalty Forecast', 8, 3);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Overdue one',
            'due_date' => today()->subDays(2),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Overdue two',
            'due_date' => today()->subDay(),
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Critical proof gap',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'High networking gap',
            'category' => 'Networking',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $forecast = app(OpportunityForecastService::class);

        $this->assertSame(55, $forecast->executionHealth($opportunity->fresh(['actions', 'opportunityGaps.actions'])));
    }

    public function test_forecast_status_mapping(): void
    {
        $forecast = app(OpportunityForecastService::class);

        $this->assertSame('Excellent', $forecast->statusForScore(90));
        $this->assertSame('Strong', $forecast->statusForScore(75));
        $this->assertSame('Moderate', $forecast->statusForScore(60));
        $this->assertSame('At Risk', $forecast->statusForScore(40));
        $this->assertSame('Unlikely', $forecast->statusForScore(39));
    }

    public function test_forecast_page_loads_and_sorts_by_forecast_score(): void
    {
        $user = User::factory()->create();
        $strong = $this->scoredOpportunity('Strong Forecast Page Opportunity', 10, 1);
        Action::create([
            'opportunity_id' => $strong->id,
            'title' => 'Confirm sponsor call',
            'due_date' => today()->addDay(),
        ]);
        $weak = $this->scoredOpportunity('Weak Forecast Page Opportunity', 2, 10);
        $this->addCriticalGaps($weak, 3);

        $response = $this->actingAs($user)->get(route('forecasts', ['sort' => 'forecast_score']));

        $response
            ->assertOk()
            ->assertSeeText('Opportunity Forecasts')
            ->assertSeeText('Weighted Score')
            ->assertSeeText('Readiness Score')
            ->assertSeeText('Execution Health')
            ->assertSeeText('Forecast Status')
            ->assertSeeInOrder(['Strong Forecast Page Opportunity', 'Weak Forecast Page Opportunity']);
    }

    public function test_forecast_dashboard_sections_appear(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Forecasted Best Opportunities')
            ->assertSeeText('Focus Opportunities At Risk');
    }

    public function test_focus_opportunities_at_risk_appear_with_reasons(): void
    {
        $user = User::factory()->create();
        $risk = $this->scoredOpportunity('At Risk Focus Advisory', 8, 3, [
            'is_focus' => true,
            'focused_at' => now(),
        ]);
        $this->addCriticalGaps($risk, 3);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $section = $this->dashboardSection($response->getContent(), 'focus-opportunities-at-risk');

        $this->assertStringContainsString('At Risk Focus Advisory', $section);
        $this->assertStringContainsString('Missing next action', $section);
        $this->assertStringContainsString('Low readiness', $section);
        $this->assertStringContainsString('Critical gaps without action plan', $section);
    }

    public function test_strong_opportunities_appear_in_forecast_rankings(): void
    {
        $user = User::factory()->create();
        $strong = $this->scoredOpportunity('Best Forecasted Advisory', 10, 1);
        Action::create([
            'opportunity_id' => $strong->id,
            'title' => 'Book intro call',
            'due_date' => today()->addDay(),
        ]);
        $weak = $this->scoredOpportunity('Lower Forecasted Contract', 3, 10);
        $this->addCriticalGaps($weak, 2);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $section = $this->dashboardSection($response->getContent(), 'forecasted-best-opportunities');

        $this->assertStringContainsString('Best Forecasted Advisory', $section);
        $this->assertStringContainsString('Excellent', $section);
        $this->assertStringContainsString('Lower Forecasted Contract', $section);
        $this->assertLessThan(
            strpos($section, 'Lower Forecasted Contract'),
            strpos($section, 'Best Forecasted Advisory')
        );
    }

    public function test_forecast_breakdown_renders_on_opportunity_detail(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->scoredOpportunity('Detailed Forecast Advisory', 10, 1);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Follow up with hiring manager',
            'due_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Forecast')
            ->assertSeeText('Forecast Score')
            ->assertSeeText('Forecast Status')
            ->assertSeeText('Forecast Breakdown')
            ->assertSeeText('Weighted Score:')
            ->assertSeeText('+40')
            ->assertSeeText('Readiness:')
            ->assertSeeText('Execution:')
            ->assertSeeText('+20')
            ->assertSeeText('Excellent');
    }

    public function test_forecast_entries_appear_in_timeline_without_stored_history(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->scoredOpportunity('Timeline Forecast Risk', 8, 3);
        $this->addCriticalGaps($opportunity, 3);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Forecast Alert')
            ->assertSeeText('Forecast dropped below 60');
    }

    private function scoredOpportunity(string $title, int $positiveValue, int $dragValue, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => $title,
            'status' => 'Active',
            'income_potential' => $positiveValue,
            'probability_of_success' => $positiveValue,
            'time_to_revenue' => $dragValue,
            'strategic_alignment' => $positiveValue,
            'personal_interest' => $positiveValue,
            'skill_growth' => $positiveValue,
            'family_fit' => $positiveValue,
            'risk_level' => $dragValue,
        ], $attributes));
    }

    private function addCriticalGaps(Opportunity $opportunity, int $count): void
    {
        foreach (range(1, $count) as $index) {
            OpportunityGap::create([
                'opportunity_id' => $opportunity->id,
                'title' => 'Critical gap '.$index,
                'category' => 'Portfolio',
                'status' => 'Open',
                'priority' => 'Critical',
            ]);
        }
    }

    private function dashboardSection(string $content, string $testId): string
    {
        $matched = preg_match('/<section\b(?=[^>]*data-testid="'.preg_quote($testId, '/').'"[^>]*)[^>]*>(.*?)<\/section>/s', $content, $matches);

        $this->assertSame(1, $matched, 'Dashboard section [data-testid="'.$testId.'"] was not found.');

        return html_entity_decode(strip_tags($matches[0]));
    }
}

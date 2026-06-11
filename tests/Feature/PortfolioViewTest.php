<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\StrategicObjective;
use App\Models\User;
use App\Services\PortfolioAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('portfolio'));

        $response
            ->assertOk()
            ->assertSeeText('Portfolio View')
            ->assertSeeText('Portfolio Summary')
            ->assertSeeText('Strategic Objective Coverage')
            ->assertSeeText('Opportunity Distribution');
    }

    public function test_portfolio_metrics_calculate_correctly(): void
    {
        $strong = $this->strongOpportunity('Strong Advisory', ['is_focus' => true, 'focused_at' => now()]);
        Action::create([
            'opportunity_id' => $strong->id,
            'title' => 'Prepare proposal',
            'due_date' => today()->addDay(),
        ]);
        $risk = $this->weakOpportunity('Risky Contract', ['is_focus' => true, 'focused_at' => now()]);
        $this->addCriticalGaps($risk, 3);
        Opportunity::create([
            'title' => 'Closed Past Option',
            'status' => 'Closed',
        ]);

        $analysis = app(PortfolioAnalysisService::class)->analysis();

        $this->assertSame(3, $analysis['metrics']['total_opportunities']);
        $this->assertSame(2, $analysis['metrics']['active_opportunities']);
        $this->assertSame(2, $analysis['metrics']['focused_opportunity_count']);
        $this->assertSame(1, $analysis['metrics']['forecasted_strong_opportunities']);
        $this->assertSame(1, $analysis['metrics']['forecasted_at_risk_opportunities']);
        $this->assertSame(55.0, $analysis['metrics']['average_opportunity_score']);
        $this->assertSame(62.5, $analysis['metrics']['average_readiness_score']);
        $this->assertSame(61.0, $analysis['metrics']['average_forecast_score']);
    }

    public function test_objective_coverage_calculations_work_and_weak_objectives_appear(): void
    {
        $growth = StrategicObjective::create([
            'name' => 'Grow consulting income',
            'priority' => 5,
            'active' => true,
        ]);
        $neglected = StrategicObjective::create([
            'name' => 'Build product revenue',
            'priority' => 4,
            'active' => true,
        ]);

        $first = $this->strongOpportunity('Consulting One', ['is_focus' => true, 'focused_at' => now()]);
        Action::create(['opportunity_id' => $first->id, 'title' => 'Follow up', 'due_date' => today()->addDay()]);
        $second = $this->strongOpportunity('Consulting Two');
        $first->strategicObjectives()->attach($growth);
        $second->strategicObjectives()->attach($growth);

        $response = $this->actingAs(User::factory()->create())->get(route('portfolio'));
        $section = $this->section($response->getContent(), 'strategic-objective-coverage');

        $this->assertStringContainsString('Grow consulting income', $section);
        $this->assertStringContainsString('Moderate Coverage', $section);
        $this->assertStringContainsString('Build product revenue', $section);
        $this->assertStringContainsString('Weak Coverage', $section);
        $this->assertStringContainsString('100.0', $section);
        $this->assertStringContainsString((string) $neglected->priority, $section);
    }

    public function test_portfolio_risks_appear(): void
    {
        $risk = $this->weakOpportunity('At Risk Focus Opportunity', ['is_focus' => true, 'focused_at' => now()]);
        $this->addCriticalGaps($risk, 3);
        Action::create([
            'opportunity_id' => $risk->id,
            'title' => 'Late outreach',
            'due_date' => today()->subDay(),
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('portfolio'));
        $section = $this->section($response->getContent(), 'portfolio-risks');

        $this->assertStringContainsString('At Risk Focus Opportunity', $section);
        $this->assertStringContainsString('Forecast score below 60', $section);
        $this->assertStringContainsString('Readiness score below 50', $section);
        $this->assertStringContainsString('overdue action', $section);
        $this->assertStringContainsString('critical gaps', $section);
    }

    public function test_portfolio_strengths_appear(): void
    {
        $strong = $this->strongOpportunity('Strong Focus Opportunity', ['is_focus' => true, 'focused_at' => now()]);
        Action::create([
            'opportunity_id' => $strong->id,
            'title' => 'Schedule sponsor call',
            'due_date' => today()->addDay(),
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('portfolio'));
        $section = $this->section($response->getContent(), 'portfolio-strengths');

        $this->assertStringContainsString('Strong Focus Opportunity', $section);
        $this->assertStringContainsString('Active execution', $section);
        $this->assertStringContainsString('No overdue actions', $section);
    }

    public function test_dashboard_portfolio_health_appears(): void
    {
        $this->strongOpportunity('Dashboard Portfolio Signal', ['is_focus' => true, 'focused_at' => now()]);

        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Portfolio Health')
            ->assertSeeText('Average Forecast')
            ->assertSeeText('Average Readiness')
            ->assertSeeText('Focused Opportunities')
            ->assertSeeText('Portfolio Risks')
            ->assertSee(route('portfolio'), false);
    }

    public function test_focus_opportunity_portfolio_table_renders(): void
    {
        $objective = StrategicObjective::create([
            'name' => 'Executive advisory objective',
            'priority' => 5,
            'active' => true,
        ]);
        $focus = $this->strongOpportunity('Focused Portfolio Row', ['is_focus' => true, 'focused_at' => now()]);
        $focus->strategicObjectives()->attach($objective);
        Action::create(['opportunity_id' => $focus->id, 'title' => 'Send next step', 'due_date' => today()->addDay()]);

        $response = $this->actingAs(User::factory()->create())->get(route('portfolio'));
        $section = $this->section($response->getContent(), 'focus-portfolio');

        $this->assertStringContainsString('Focused Portfolio Row', $section);
        $this->assertStringContainsString('Weighted Score', $section);
        $this->assertStringContainsString('Readiness Score', $section);
        $this->assertStringContainsString('Forecast Score', $section);
        $this->assertStringContainsString('Open Actions', $section);
        $this->assertStringContainsString('Critical Gaps', $section);
        $this->assertStringContainsString('Executive advisory objective', $section);
    }

    private function strongOpportunity(string $title, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => $title,
            'company' => 'Acme',
            'type' => 'Consulting',
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

    private function weakOpportunity(string $title, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => $title,
            'company' => 'Globex',
            'type' => 'Job',
            'status' => 'Active',
            'income_potential' => 1,
            'probability_of_success' => 1,
            'time_to_revenue' => 10,
            'strategic_alignment' => 1,
            'personal_interest' => 1,
            'skill_growth' => 1,
            'family_fit' => 1,
            'risk_level' => 10,
        ], $attributes));
    }

    private function addCriticalGaps(Opportunity $opportunity, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            OpportunityGap::create([
                'opportunity_id' => $opportunity->id,
                'title' => 'Critical gap '.$i,
                'category' => 'Portfolio',
                'status' => 'Open',
                'priority' => 'Critical',
            ]);
        }
    }

    private function section(string $html, string $testId): string
    {
        $start = strpos($html, 'data-testid="'.$testId.'"');

        if ($start === false) {
            return '';
        }

        $next = strpos($html, 'data-testid="', $start + 1);

        return $next === false ? substr($html, $start) : substr($html, $start, $next - $start);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\StrategicObjective;
use App\Models\User;
use App\Services\OutcomeAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutcomeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_outcome_fields_can_be_saved(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Fractional CTO Lead',
            'company' => 'Acme Inc.',
            'type' => 'Consulting',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->patch(route('opportunities.update', $opportunity), [
            'title' => 'Fractional CTO Lead',
            'company' => 'Acme Inc.',
            'type' => 'Consulting',
            'status' => 'Active',
            'outcome' => 'Won',
            'outcome_date' => '2026-06-10',
            'outcome_notes' => 'Converted after a warm referral and focused portfolio review.',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'outcome' => 'Won',
            'outcome_date' => '2026-06-10',
            'outcome_notes' => 'Converted after a warm referral and focused portfolio review.',
        ]);
    }

    public function test_invalid_outcome_is_rejected(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Invalid Outcome Lead',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->from(route('opportunities.edit', $opportunity))->patch(route('opportunities.update', $opportunity), [
            'title' => 'Invalid Outcome Lead',
            'status' => 'Active',
            'outcome' => 'Maybe Later',
            'outcome_date' => '2026-06-10',
        ]);

        $response
            ->assertRedirect(route('opportunities.edit', $opportunity))
            ->assertSessionHasErrors('outcome');
    }

    public function test_outcome_date_is_required_when_outcome_is_present(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Missing Outcome Date Lead',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->from(route('opportunities.edit', $opportunity))->patch(route('opportunities.update', $opportunity), [
            'title' => 'Missing Outcome Date Lead',
            'status' => 'Active',
            'outcome' => 'Lost',
        ]);

        $response
            ->assertRedirect(route('opportunities.edit', $opportunity))
            ->assertSessionHasErrors('outcome_date');
    }

    public function test_outcome_analytics_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('outcome-analytics'));

        $response
            ->assertOk()
            ->assertSeeText('Which efforts are producing results?')
            ->assertSeeText('Total with outcomes')
            ->assertSeeText('Breakdown by Opportunity Type')
            ->assertSeeText('Outcome Lessons');
    }

    public function test_outcome_counts_are_correct(): void
    {
        $this->createOutcome('Won');
        $this->createOutcome('Won');
        $this->createOutcome('Lost');
        $this->createOutcome('Parked');
        $this->createOutcome(null);

        $summary = app(OutcomeAnalyticsService::class)->summary();

        $this->assertSame(4, $summary['total_with_outcomes']);
        $this->assertSame(2, $summary['counts']['Won']);
        $this->assertSame(1, $summary['counts']['Lost']);
        $this->assertSame(1, $summary['counts']['Parked']);
        $this->assertSame(0, $summary['counts']['Abandoned']);
        $this->assertSame(0, $summary['counts']['No Response']);
        $this->assertSame(0, $summary['counts']['Not Pursued']);
    }

    public function test_parked_opportunities_are_excluded_from_win_rate_denominator(): void
    {
        $this->createOutcome('Won');
        $this->createOutcome('Lost');
        $this->createOutcome('Parked');

        $analytics = app(OutcomeAnalyticsService::class);

        $this->assertSame(2, $analytics->finalOutcomeCount());
    }

    public function test_win_rate_calculation_is_correct(): void
    {
        $this->createOutcome('Won');
        $this->createOutcome('Won');
        $this->createOutcome('Lost');
        $this->createOutcome('Abandoned');
        $this->createOutcome('No Response');
        $this->createOutcome('Parked');

        $this->assertSame(40.0, app(OutcomeAnalyticsService::class)->winRate());
    }

    public function test_dashboard_outcome_snapshot_appears(): void
    {
        $user = User::factory()->create();
        $this->createOutcome('Won');
        $this->createOutcome('Lost');
        $this->createOutcome('Abandoned');
        $this->createOutcome('No Response');
        $this->createOutcome('Parked');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Outcome Snapshot')
            ->assertSeeText('Won')
            ->assertSeeText('Lost / Abandoned / No Response')
            ->assertSeeText('3')
            ->assertSeeText('25.0%')
            ->assertSee(route('outcome-analytics'), false);
    }

    public function test_recent_outcome_lessons_render(): void
    {
        $user = User::factory()->create();
        $objective = StrategicObjective::create([
            'name' => 'Grow advisory income',
            'priority' => 9,
        ]);
        $opportunity = $this->createOutcome('Won', [
            'title' => 'Advisory Retainer',
            'company' => 'Acme Inc.',
            'type' => 'Consulting',
            'status' => 'Closed',
            'outcome_date' => '2026-06-09',
            'outcome_notes' => 'Won because the portfolio project mapped directly to the buyer problem and a trusted contact introduced the work.',
            'income_potential' => 8,
            'probability_of_success' => 7,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 8,
            'skill_growth' => 7,
            'family_fit' => 8,
            'risk_level' => 3,
        ]);
        $opportunity->strategicObjectives()->attach($objective);

        $response = $this->actingAs($user)->get(route('outcome-analytics'));

        $response
            ->assertOk()
            ->assertSeeText('Advisory Retainer')
            ->assertSeeText('Acme Inc.')
            ->assertSeeText('Won')
            ->assertSeeText('Jun 9, 2026')
            ->assertSeeText('Grow advisory income')
            ->assertSeeText('Won because the portfolio project mapped directly to the buyer problem');
    }

    private function createOutcome(?string $outcome, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => fake()->sentence(3),
            'status' => 'Active',
            'outcome' => $outcome,
            'outcome_date' => $outcome ? '2026-06-10' : null,
        ], $attributes));
    }
}

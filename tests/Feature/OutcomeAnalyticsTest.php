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
            'outcome_reason' => 'strong_relationship',
            'outcome_notes' => 'Converted after a warm referral and focused portfolio review.',
            'lesson_learned' => 'Warm introductions consistently outperform direct applications.',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'outcome' => 'Won',
            'outcome_date' => '2026-06-10 00:00:00',
            'outcome_reason' => 'strong_relationship',
            'outcome_notes' => 'Converted after a warm referral and focused portfolio review.',
            'lesson_learned' => 'Warm introductions consistently outperform direct applications.',
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


    public function test_invalid_outcome_reason_is_rejected(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Invalid Outcome Reason Lead',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->from(route('opportunities.edit', $opportunity))->patch(route('opportunities.update', $opportunity), [
            'title' => 'Invalid Outcome Reason Lead',
            'status' => 'Active',
            'outcome' => 'Won',
            'outcome_date' => '2026-06-10',
            'outcome_reason' => 'competition',
        ]);

        $response
            ->assertRedirect(route('opportunities.edit', $opportunity))
            ->assertSessionHasErrors('outcome_reason');
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
            ->assertSeeText('Outcome Learning')
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


    public function test_analytics_include_outcome_reasons(): void
    {
        $this->createOutcome('Won', ['outcome_reason' => 'strong_relationship']);
        $this->createOutcome('Won', ['outcome_reason' => 'strong_relationship']);
        $this->createOutcome('Lost', ['outcome_reason' => 'competition']);
        $this->createOutcome('Abandoned', ['outcome_reason' => 'capacity_constraint']);
        $this->createOutcome('No Response', ['outcome_reason' => 'no_response']);

        $breakdowns = app(OutcomeAnalyticsService::class)->outcomeReasonBreakdowns();

        $this->assertSame('Strong Relationship', $breakdowns['wins']->first()['label']);
        $this->assertSame(2, $breakdowns['wins']->first()['count']);
        $this->assertSame('Competition', $breakdowns['losses']->first()['label']);
        $this->assertSame('Capacity Constraint', $breakdowns['abandonments']->first()['label']);
        $this->assertSame('No Response', $breakdowns['no_responses']->first()['label']);
    }

    public function test_opportunity_page_displays_lessons_learned(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createOutcome('Abandoned', [
            'title' => 'Laravel Consulting',
            'outcome_reason' => 'capacity_constraint',
            'lesson_learned' => 'Local networking produced higher ROI than cold outreach.',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Learning')
            ->assertSeeText('Abandoned')
            ->assertSeeText('Capacity Constraint')
            ->assertSeeText('Local networking produced higher ROI than cold outreach.');
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
            'outcome_reason' => 'referral',
            'outcome_notes' => 'Won because the portfolio project mapped directly to the buyer problem and a trusted contact introduced the work.',
            'lesson_learned' => 'Warm introductions made the buyer conversation easier to win.',
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
            ->assertSeeText('Referral')
            ->assertSeeText('Warm introductions made the buyer conversation easier to win')
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

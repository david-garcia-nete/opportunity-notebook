<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\StrategicObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrategicObjectiveOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_attach_an_objective_to_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Fractional consulting offer',
            'status' => 'Active',
        ]);
        $strategicObjective = StrategicObjective::create([
            'name' => 'Increase household income',
            'priority' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('opportunities.strategic-objectives.store', $opportunity), [
            'strategic_objective_id' => $strategicObjective->id,
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunity_strategic_objective', [
            'opportunity_id' => $opportunity->id,
            'strategic_objective_id' => $strategicObjective->id,
        ]);
    }

    public function test_authenticated_users_can_detach_an_objective_from_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Portfolio contract',
            'status' => 'Active',
        ]);
        $strategicObjective = StrategicObjective::create([
            'name' => 'Build software portfolio',
            'priority' => 8,
            'active' => true,
        ]);
        $opportunity->strategicObjectives()->attach($strategicObjective->id);

        $response = $this->actingAs($user)->delete(route('opportunities.strategic-objectives.destroy', [$opportunity, $strategicObjective]));

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseMissing('opportunity_strategic_objective', [
            'opportunity_id' => $opportunity->id,
            'strategic_objective_id' => $strategicObjective->id,
        ]);
    }

    public function test_opportunity_show_page_lists_linked_objectives(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Music licensing lead',
            'status' => 'Active',
        ]);
        $strategicObjective = StrategicObjective::create([
            'name' => 'Develop music career',
            'priority' => 7,
            'active' => true,
        ]);
        $opportunity->strategicObjectives()->attach($strategicObjective->id);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Strategic Objectives')
            ->assertSeeText('Develop music career')
            ->assertSeeText('Priority')
            ->assertSeeText('Remove');
    }

    public function test_objective_detail_lists_opportunities_by_score_with_status_and_next_action(): void
    {
        $user = User::factory()->create();
        $strategicObjective = StrategicObjective::create([
            'name' => 'Become self-employed',
            'description' => 'Replace salary with durable independent income.',
            'priority' => 9,
            'active' => true,
        ]);
        $lowerScoreOpportunity = $this->createScoredOpportunity([
            'title' => 'Small retainer',
            'status' => 'Idea',
        ], 4);
        $higherScoreOpportunity = $this->createScoredOpportunity([
            'title' => 'Premium consulting package',
            'status' => 'Active',
        ], 9);
        Action::create([
            'opportunity_id' => $higherScoreOpportunity->id,
            'title' => 'Send package proposal',
            'due_date' => today(),
        ]);
        $strategicObjective->opportunities()->attach([$lowerScoreOpportunity->id, $higherScoreOpportunity->id]);

        $response = $this->actingAs($user)->get(route('strategic-objectives.show', $strategicObjective));

        $response
            ->assertOk()
            ->assertSeeText('Replace salary with durable independent income.')
            ->assertSeeText('Linked Opportunities')
            ->assertSeeTextInOrder(['Premium consulting package', 'Small retainer'])
            ->assertSeeText((string) $higherScoreOpportunity->computedScore())
            ->assertSeeText('Active')
            ->assertSeeText('Send package proposal');
    }

    public function test_guests_cannot_attach_or_detach_objectives(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected opportunity',
            'status' => 'Active',
        ]);
        $strategicObjective = StrategicObjective::create([
            'name' => 'Protected objective',
            'priority' => 5,
            'active' => true,
        ]);

        $this->post(route('opportunities.strategic-objectives.store', $opportunity), [
            'strategic_objective_id' => $strategicObjective->id,
        ])->assertRedirect(route('login'));

        $this->delete(route('opportunities.strategic-objectives.destroy', [$opportunity, $strategicObjective]))->assertRedirect(route('login'));
    }

    private function createScoredOpportunity(array $attributes, int $factorValue): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Scored Opportunity',
            'status' => 'Active',
            'income_potential' => $factorValue,
            'probability_of_success' => $factorValue,
            'time_to_revenue' => 1,
            'strategic_alignment' => $factorValue,
            'personal_interest' => $factorValue,
            'skill_growth' => $factorValue,
            'family_fit' => $factorValue,
            'risk_level' => 1,
        ], $attributes));
    }
}

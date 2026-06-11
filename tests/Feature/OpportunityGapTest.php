<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\StrategicObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_opportunity_gaps(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Senior Backend Engineer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('opportunities.gaps.store', $opportunity), [
            'title' => 'AWS certification',
            'description' => 'Credential expected for this role.',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunity_gaps', [
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS certification',
            'description' => 'Credential expected for this role.',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
    }

    public function test_authenticated_users_can_edit_opportunity_gaps(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'AI Consultant',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'First client',
            'category' => 'Experience',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->patch(route('opportunities.gaps.update', [$opportunity, $gap]), [
            'title' => 'First paying client',
            'description' => 'Land one paid consulting engagement.',
            'category' => 'Experience',
            'status' => 'In Progress',
            'priority' => 'Critical',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunity_gaps', [
            'id' => $gap->id,
            'title' => 'First paying client',
            'description' => 'Land one paid consulting engagement.',
            'status' => 'In Progress',
            'priority' => 'Critical',
        ]);
    }

    public function test_authenticated_users_can_delete_opportunity_gaps(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Professional Musician',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Performance experience',
            'category' => 'Experience',
            'status' => 'Open',
            'priority' => 'Medium',
        ]);

        $response = $this->actingAs($user)->delete(route('opportunities.gaps.destroy', [$opportunity, $gap]));

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseMissing('opportunity_gaps', [
            'id' => $gap->id,
        ]);
    }

    public function test_opportunity_has_many_gaps_and_gap_belongs_to_opportunity(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Senior Backend Engineer',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Stronger portfolio',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $this->assertTrue($opportunity->opportunityGaps->contains($gap));
        $this->assertTrue($gap->opportunity->is($opportunity));
    }

    public function test_deleting_an_opportunity_cascades_to_related_gaps(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'AI Consultant',
            'status' => 'active',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Case studies',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $opportunity->delete();

        $this->assertDatabaseCount('opportunity_gaps', 0);
    }

    public function test_opportunity_show_page_displays_gap_progress_summary(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Professional Musician',
            'status' => 'active',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Repertoire',
            'category' => 'Skill',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Performance experience',
            'category' => 'Experience',
            'status' => 'In Progress',
            'priority' => 'High',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Audience growth',
            'category' => 'Networking',
            'status' => 'Complete',
            'priority' => 'Medium',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Gap Analysis')
            ->assertSeeText('What stands between me and this opportunity?')
            ->assertSeeTextInOrder(['Open', '1', 'In Progress', '1', 'Completed', '1'])
            ->assertSeeText('Repertoire')
            ->assertSeeText('Performance experience')
            ->assertSeeText('Audience growth');
    }

    public function test_dashboard_shows_high_value_opportunities_with_open_gaps(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createScoredOpportunity([
            'title' => 'Senior Backend Engineer',
            'company' => 'Acme Inc.',
        ], 9);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS certification',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed portfolio update',
            'category' => 'Portfolio',
            'status' => 'Complete',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('High-Value Opportunities With Critical Gaps')
            ->assertSeeText('Senior Backend Engineer')
            ->assertSeeText('Open Gap Count')
            ->assertSeeText('AWS certification')
            ->assertSeeText('Critical')
            ->assertDontSeeText('Completed portfolio update');
    }

    public function test_strategic_objective_page_shows_open_gap_counts_for_linked_opportunities(): void
    {
        $user = User::factory()->create();
        $objective = StrategicObjective::create([
            'name' => 'Increase income',
            'priority' => 10,
            'active' => true,
        ]);
        $opportunity = $this->createScoredOpportunity([
            'title' => 'AI Consultant',
        ], 8);
        $objective->opportunities()->attach($opportunity->id);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'First client',
            'category' => 'Experience',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Marketing website',
            'category' => 'Portfolio',
            'status' => 'In Progress',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->get(route('strategic-objectives.show', $objective));

        $response
            ->assertOk()
            ->assertSeeText('Open Gaps')
            ->assertSeeTextInOrder(['AI Consultant', (string) $opportunity->computedScore(), 'active', '1']);
    }

    private function createScoredOpportunity(array $attributes, int $factorValue): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Scored Opportunity',
            'status' => 'active',
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

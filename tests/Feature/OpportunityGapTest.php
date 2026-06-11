<?php

namespace Tests\Feature;

use App\Models\Action;
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

        $response->assertOk();
        $gapSummarySection = $this->dashboardSection($response->getContent(), 'dashboard-gap-summary');
        $recentActivitySection = $this->dashboardSection($response->getContent(), 'recent-activity');

        $this->assertStringContainsString('High-Value Opportunities With Critical Gaps', $gapSummarySection);
        $this->assertStringContainsString('Senior Backend Engineer', $gapSummarySection);
        $this->assertStringContainsString('Open Gap Count', $gapSummarySection);
        $this->assertStringContainsString('AWS certification', $gapSummarySection);
        $this->assertStringContainsString('Critical', $gapSummarySection);
        $this->assertStringNotContainsString('Completed portfolio update', $gapSummarySection);
        $this->assertStringContainsString('Completed portfolio update', $recentActivitySection);
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

    private function dashboardSection(string $content, string $testId): string
    {
        $matched = preg_match('/<section\b(?=[^>]*data-testid="'.preg_quote($testId, '/').'"[^>]*)[^>]*>(.*?)<\/section>/s', $content, $matches);

        $this->assertSame(1, $matched, 'Dashboard section [data-testid="'.$testId.'"] was not found.');

        return html_entity_decode(strip_tags($matches[0]));
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

    public function test_create_action_from_gap_prefills_action_fields(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Cloud Architect Role',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS Certification',
            'description' => 'Certification is expected for this role.',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);

        $response = $this->actingAs($user)->get(route('actions.create', ['opportunity_gap_id' => $gap->id]));

        $response
            ->assertOk()
            ->assertSee('Close Gap: AWS Certification')
            ->assertSeeText('Gap title: AWS Certification')
            ->assertSeeText('Gap description: Certification is expected for this role.')
            ->assertSeeText('Gap category: Certification')
            ->assertSeeText('Gap priority: Critical')
            ->assertSeeText('Related opportunity: Cloud Architect Role')
            ->assertSeeText('Opportunity name: Cloud Architect Role')
            ->assertSeeText('This action will be linked to Cloud Architect Role automatically.');
    }

    public function test_created_action_from_gap_links_to_correct_opportunity_and_gap(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Portfolio Consulting',
            'status' => 'active',
        ]);
        $otherOpportunity = Opportunity::create([
            'title' => 'Wrong Opportunity',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Build Portfolio Project',
            'description' => 'Need proof of work.',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->post(route('actions.store'), [
            'opportunity_id' => $otherOpportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Close Gap: Build Portfolio Project',
            'description' => 'Gap title: Build Portfolio Project',
        ]);

        $action = Action::firstOrFail();
        $response->assertRedirect(route('actions.show', $action));
        $this->assertTrue($action->opportunity->is($opportunity));
        $this->assertTrue($action->opportunityGap->is($gap));
        $this->assertDatabaseHas('actions', [
            'id' => $action->id,
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Close Gap: Build Portfolio Project',
        ]);
    }

    public function test_multiple_actions_can_be_linked_to_a_gap(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Data Engineering Contract',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Spark Experience',
            'category' => 'Experience',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Complete Spark tutorial',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Publish Spark notes',
            'completed_at' => now(),
        ]);

        $this->assertSame(2, $gap->actions()->count());
        $this->assertSame(1, $gap->openActions()->count());
        $this->assertSame(1, $gap->completedActions()->count());
    }

    public function test_gap_detail_page_shows_execution_counts(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Security Role',
            'status' => 'active',
        ]);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Security Certification',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Register for exam',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Choose study guide',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.gaps.show', [$opportunity, $gap]));

        $response
            ->assertOk()
            ->assertSeeText('Gap Execution')
            ->assertSeeTextInOrder(['Open Actions Linked To This Gap', '1'])
            ->assertSeeTextInOrder(['Completed Actions Linked To This Gap', '1'])
            ->assertSeeText('Register for exam')
            ->assertSeeText('Choose study guide')
            ->assertSeeText('Create Action');
    }


    public function test_opportunity_show_page_displays_gap_progress_counts(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Cloud Consultant',
            'status' => 'active',
        ]);
        $criticalOne = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS Certification',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        $criticalTwo = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Architecture Experience',
            'category' => 'Experience',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        $highGap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Portfolio Case Study',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $criticalOne->id,
            'title' => 'Schedule exam',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $criticalTwo->id,
            'title' => 'Finish lab',
            'completed_at' => now(),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $highGap->id,
            'title' => 'Draft case study',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Gap Progress')
            ->assertSeeTextInOrder(['Critical Gaps', '2'])
            ->assertSeeTextInOrder(['High Gaps', '1'])
            ->assertSeeTextInOrder(['Gap Actions Open', '2'])
            ->assertSeeTextInOrder(['Gap Actions Completed', '1']);
    }

}

<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\UserPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_opportunities_index(): void
    {
        $user = User::factory()->create();
        Opportunity::create([
            'title' => 'Senior Laravel Developer',
            'company' => 'Acme Inc.',
            'status' => 'idea',
            'score' => 80,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.index'));

        $response
            ->assertOk()
            ->assertSee('Opportunity Pipeline')
            ->assertSee('Senior Laravel Developer')
            ->assertSee('Acme Inc.')
            ->assertSee('idea')
            ->assertSee('80');
    }

    public function test_authenticated_users_can_access_opportunity_comparison_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('opportunities.compare'));

        $response
            ->assertOk()
            ->assertSee('Compare Opportunities')
            ->assertSee('Create opportunities first, then compare them here.');
    }

    public function test_opportunity_comparison_page_displays_active_opportunities(): void
    {
        $user = User::factory()->create();
        Opportunity::create([
            'title' => 'Fractional CTO Advisory',
            'company' => 'Acme Inc.',
            'status' => 'active',
            'income_potential' => 8,
            'probability_of_success' => 7,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 8,
            'skill_growth' => 7,
            'family_fit' => 6,
            'risk_level' => 3,
        ]);
        Opportunity::create([
            'title' => 'Closed Staff Role',
            'company' => 'Globex',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.compare'));

        $response
            ->assertOk()
            ->assertSee('Title')
            ->assertSee('Company')
            ->assertSee('Base Score')
            ->assertSee('Weighted Score')
            ->assertSee('Income Potential')
            ->assertSee('Linked Contacts Count')
            ->assertSee('Fractional CTO Advisory')
            ->assertSee('Acme Inc.')
            ->assertSee('40')
            ->assertDontSee('Closed Staff Role');
    }

    public function test_opportunity_comparison_page_orders_by_computed_score_without_preferences(): void
    {
        $user = User::factory()->create();
        Opportunity::create([
            'title' => 'Lower Comparison Priority',
            'status' => 'active',
            'income_potential' => 4,
            'probability_of_success' => 4,
            'time_to_revenue' => 8,
            'strategic_alignment' => 4,
            'personal_interest' => 4,
            'skill_growth' => 4,
            'family_fit' => 4,
            'risk_level' => 8,
        ]);
        Opportunity::create([
            'title' => 'Higher Comparison Priority',
            'status' => 'active',
            'income_potential' => 9,
            'probability_of_success' => 9,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 9,
            'skill_growth' => 9,
            'family_fit' => 9,
            'risk_level' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.compare'));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Higher Comparison Priority', 'Lower Comparison Priority']);
    }


    public function test_weighted_score_uses_user_preferences(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::create(array_merge(UserPreference::defaults(), [
            'user_id' => $user->id,
            'income_weight' => 10,
            'probability_weight' => 0,
            'time_to_revenue_weight' => 0,
            'strategic_alignment_weight' => 0,
            'personal_interest_weight' => 0,
            'skill_growth_weight' => 0,
            'family_fit_weight' => 0,
            'risk_weight' => 0,
        ]));
        $opportunity = Opportunity::create([
            'title' => 'Income Weighted Opportunity',
            'status' => 'active',
            'income_potential' => 9,
            'probability_of_success' => 2,
            'time_to_revenue' => 10,
            'strategic_alignment' => 2,
            'personal_interest' => 2,
            'skill_growth' => 2,
            'family_fit' => 2,
            'risk_level' => 10,
        ]);

        $this->assertSame(90, $opportunity->weightedScore($preference));
        $this->assertSame(['Income Potential'], $opportunity->weightedScoreContributors($preference)->all());
    }

    public function test_opportunity_comparison_page_orders_by_weighted_score_when_preferences_exist(): void
    {
        $user = User::factory()->create();
        UserPreference::create(array_merge(UserPreference::defaults(), [
            'user_id' => $user->id,
            'income_weight' => 1,
            'strategic_alignment_weight' => 10,
            'family_fit_weight' => 10,
            'risk_weight' => 0,
            'time_to_revenue_weight' => 0,
            'probability_weight' => 0,
            'personal_interest_weight' => 0,
            'skill_growth_weight' => 0,
        ]));
        Opportunity::create([
            'title' => 'Higher Base Income Option',
            'status' => 'active',
            'income_potential' => 10,
            'probability_of_success' => 10,
            'time_to_revenue' => 1,
            'strategic_alignment' => 1,
            'personal_interest' => 10,
            'skill_growth' => 10,
            'family_fit' => 1,
            'risk_level' => 1,
        ]);
        Opportunity::create([
            'title' => 'Better Personal Priority Fit',
            'status' => 'active',
            'income_potential' => 4,
            'probability_of_success' => 1,
            'time_to_revenue' => 10,
            'strategic_alignment' => 10,
            'personal_interest' => 1,
            'skill_growth' => 1,
            'family_fit' => 10,
            'risk_level' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.compare'));

        $response
            ->assertOk()
            ->assertSeeText('Weighted Score')
            ->assertSeeText('Major contributors: Strategic Alignment, Family Fit')
            ->assertSeeInOrder(['Better Personal Priority Fit', 'Higher Base Income Option']);
    }

    public function test_opportunity_comparison_page_shows_relationship_counts(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Counted Opportunity',
            'status' => 'active',
        ]);

        $contacts = collect([
            Contact::create(['name' => 'First Contact']),
            Contact::create(['name' => 'Second Contact']),
            Contact::create(['name' => 'Third Contact']),
        ]);
        $projects = collect([
            Project::create(['name' => 'Portfolio Project']),
            Project::create(['name' => 'Case Study Project']),
        ]);

        $opportunity->contacts()->attach($contacts->pluck('id'));
        $opportunity->projects()->attach($projects->pluck('id'));

        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Open follow up']);
        Action::create(['opportunity_id' => $opportunity->id, 'title' => 'Completed prep', 'completed_at' => now()]);

        Application::create(['opportunity_id' => $opportunity->id, 'applied_at' => now(), 'status' => 'submitted']);
        Application::create(['opportunity_id' => $opportunity->id, 'applied_at' => now(), 'status' => 'interviewing']);

        $response = $this->actingAs($user)->get(route('opportunities.compare'));

        $response
            ->assertOk()
            ->assertSee('Linked Contacts Count')
            ->assertSee('Linked Projects Count')
            ->assertSee('Open Actions Count')
            ->assertSee('Applications Count')
            ->assertSeeInOrder(['Counted Opportunity', '3', '2', '1', '2']);
    }

    public function test_authenticated_users_can_create_opportunities(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('opportunities.store'), [
            'title' => 'Contract Product Build',
            'company' => 'Northwind',
            'type' => 'contract',
            'status' => 'idea',
            'score' => 72,
            'notes' => 'Potential six-week Laravel build.',
            'income_potential' => 9,
            'probability_of_success' => 7,
            'time_to_revenue' => 3,
            'strategic_alignment' => 8,
            'personal_interest' => 9,
            'skill_growth' => 8,
            'family_fit' => 7,
            'risk_level' => 4,
        ]);

        $opportunity = Opportunity::first();

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunities', [
            'title' => 'Contract Product Build',
            'company' => 'Northwind',
            'type' => 'contract',
            'status' => 'idea',
            'score' => 72,
            'notes' => 'Potential six-week Laravel build.',
            'income_potential' => 9,
            'probability_of_success' => 7,
            'time_to_revenue' => 3,
            'strategic_alignment' => 8,
            'personal_interest' => 9,
            'skill_growth' => 8,
            'family_fit' => 7,
            'risk_level' => 4,
        ]);
    }


    public function test_user_can_mark_an_opportunity_as_focus(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('opportunities.store'), [
            'title' => 'Focused Advisory Lead',
            'company' => 'Acme Strategy',
            'status' => 'active',
            'is_focus' => '1',
            'focus_reason' => 'Best near-term income path.',
        ]);

        $opportunity = Opportunity::first();

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertTrue($opportunity->refresh()->is_focus);
        $this->assertNotNull($opportunity->focused_at);
        $this->assertSame('Best near-term income path.', $opportunity->focus_reason);
    }

    public function test_unmarking_focus_clears_focus_metadata(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Former Focus Lead',
            'status' => 'active',
            'is_focus' => true,
            'focused_at' => now(),
            'focus_reason' => 'Previously important.',
        ]);

        $this->actingAs($user)->patch(route('opportunities.update', $opportunity), [
            'title' => 'Former Focus Lead',
            'status' => 'parked',
        ])->assertRedirect(route('opportunities.show', $opportunity));

        $opportunity->refresh();

        $this->assertFalse($opportunity->is_focus);
        $this->assertNull($opportunity->focused_at);
        $this->assertNull($opportunity->focus_reason);
    }

    public function test_opportunity_index_can_filter_to_focus_opportunities(): void
    {
        $user = User::factory()->create();
        Opportunity::create([
            'title' => 'Focused Opportunity',
            'status' => 'active',
            'is_focus' => true,
            'focused_at' => now(),
        ]);
        Opportunity::create([
            'title' => 'Regular Opportunity',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.index', ['focus' => 1]));

        $response
            ->assertOk()
            ->assertSeeText('Focus Opportunities')
            ->assertSeeText('Focused Opportunity')
            ->assertDontSeeText('Regular Opportunity');
    }

    public function test_computed_score_uses_positive_factors_minus_time_and_risk(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Scored Opportunity',
            'status' => 'active',
            'income_potential' => 9,
            'probability_of_success' => 8,
            'time_to_revenue' => 3,
            'strategic_alignment' => 7,
            'personal_interest' => 6,
            'skill_growth' => 5,
            'family_fit' => 4,
            'risk_level' => 2,
        ]);

        $this->assertSame(34, $opportunity->computedScore());
    }


    public function test_opportunity_detects_nearest_incomplete_next_action(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Next Action Opportunity',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Later incomplete action',
            'due_date' => today()->addDays(5),
        ]);
        $nearestAction = Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Nearest incomplete action',
            'due_date' => today()->addDay(),
        ]);

        $this->assertTrue($nearestAction->is($opportunity->nextAction()));
    }

    public function test_opportunity_next_action_ignores_completed_actions(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Completed Action Opportunity',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed sooner action',
            'due_date' => today(),
            'completed_at' => now(),
        ]);
        $openAction = Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Open later action',
            'due_date' => today()->addDay(),
        ]);

        $this->assertTrue($openAction->is($opportunity->nextAction()));
    }

    public function test_opportunity_next_action_sorts_undated_actions_after_dated_actions(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Undated Action Opportunity',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Undated open action',
        ]);
        $datedAction = Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Dated open action',
            'due_date' => today()->addWeek(),
        ]);

        $this->assertTrue($datedAction->is($opportunity->nextAction()));
    }

    public function test_opportunity_missing_next_action_is_detected_for_open_opportunities_without_incomplete_actions(): void
    {
        $openOpportunity = Opportunity::create([
            'title' => 'Missing Next Action Opportunity',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $openOpportunity->id,
            'title' => 'Completed action only',
            'completed_at' => now(),
        ]);
        $parkedOpportunity = Opportunity::create([
            'title' => 'Parked Opportunity',
            'status' => 'parked',
        ]);

        $this->assertTrue($openOpportunity->missingNextAction());
        $this->assertFalse($parkedOpportunity->missingNextAction());
    }

    public function test_opportunity_show_displays_evaluation_data(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Evaluation Visible Role',
            'status' => 'active',
            'income_potential' => 8,
            'probability_of_success' => 7,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 6,
            'skill_growth' => 5,
            'family_fit' => 8,
            'risk_level' => 3,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSee('Evaluation')
            ->assertSee('Computed Score')
            ->assertSee('Income Potential')
            ->assertSee('Probability of Success')
            ->assertSee('The computed score is a decision aid')
            ->assertSee('38');
    }

    public function test_opportunity_index_ranks_by_computed_score(): void
    {
        $user = User::factory()->create();
        Opportunity::create([
            'title' => 'Lower Priority Opportunity',
            'status' => 'active',
            'income_potential' => 4,
            'probability_of_success' => 4,
            'time_to_revenue' => 8,
            'strategic_alignment' => 4,
            'personal_interest' => 4,
            'skill_growth' => 4,
            'family_fit' => 4,
            'risk_level' => 8,
        ]);
        Opportunity::create([
            'title' => 'Higher Priority Opportunity',
            'status' => 'active',
            'income_potential' => 9,
            'probability_of_success' => 9,
            'time_to_revenue' => 2,
            'strategic_alignment' => 9,
            'personal_interest' => 9,
            'skill_growth' => 9,
            'family_fit' => 9,
            'risk_level' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.index'));

        $response
            ->assertOk()
            ->assertSee('Computed Score')
            ->assertSeeInOrder(['Higher Priority Opportunity', 'Lower Priority Opportunity']);
    }


    public function test_opportunity_index_displays_next_action_information_and_missing_badge(): void
    {
        $user = User::factory()->create();
        $opportunityWithAction = Opportunity::create([
            'title' => 'Opportunity With Next Action',
            'status' => 'active',
            'income_potential' => 8,
        ]);
        Action::create([
            'opportunity_id' => $opportunityWithAction->id,
            'title' => 'Send proposal follow-up',
            'due_date' => today()->addDay(),
        ]);
        Opportunity::create([
            'title' => 'Opportunity Missing Action',
            'status' => 'active',
            'income_potential' => 7,
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.index'));

        $response
            ->assertOk()
            ->assertSeeText('Next Action')
            ->assertSeeText('Send proposal follow-up')
            ->assertSeeText('Due '.today()->addDay()->toFormattedDateString())
            ->assertSeeText('Missing next action');
    }

    public function test_opportunity_show_displays_next_action_information_and_create_action_prompt(): void
    {
        $user = User::factory()->create();
        $opportunityWithAction = Opportunity::create([
            'title' => 'Opportunity With Show Next Action',
            'status' => 'active',
        ]);
        $nextAction = Action::create([
            'opportunity_id' => $opportunityWithAction->id,
            'title' => 'Schedule hiring manager call',
            'due_date' => today()->addDays(2),
        ]);
        $missingOpportunity = Opportunity::create([
            'title' => 'Missing Show Next Action',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('opportunities.show', $opportunityWithAction))
            ->assertOk()
            ->assertSeeText('Next Action')
            ->assertSeeText('Schedule hiring manager call')
            ->assertSeeText($nextAction->due_date->toFormattedDateString())
            ->assertSeeText('Open')
            ->assertSee(route('actions.show', $nextAction), false)
            ->assertSee(route('actions.edit', $nextAction), false);

        $this->actingAs($user)
            ->get(route('opportunities.show', $missingOpportunity))
            ->assertOk()
            ->assertSeeText('Missing next action')
            ->assertSee(route('actions.create', ['opportunity_id' => $missingOpportunity->id]), false);
    }

    public function test_authenticated_users_can_update_opportunities(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Original Role',
            'company' => 'Acme Inc.',
            'status' => 'idea',
        ]);

        $response = $this->actingAs($user)->patch(route('opportunities.update', $opportunity), [
            'title' => 'Updated Role',
            'company' => 'Globex',
            'type' => 'full-time',
            'status' => 'active',
            'score' => 91,
            'notes' => 'High-priority opportunity.',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'title' => 'Updated Role',
            'company' => 'Globex',
            'type' => 'full-time',
            'status' => 'active',
            'score' => 91,
            'notes' => 'High-priority opportunity.',
        ]);
    }

    public function test_authenticated_users_can_delete_opportunities(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Role to Remove',
            'status' => 'idea',
        ]);

        $response = $this->actingAs($user)->delete(route('opportunities.destroy', $opportunity));

        $response->assertRedirect(route('opportunities.index'));
        $this->assertDatabaseMissing('opportunities', [
            'id' => $opportunity->id,
        ]);
    }

    public function test_dashboard_uses_real_opportunity_counts(): void
    {
        $user = User::factory()->create();
        Opportunity::create(['title' => 'Active Role', 'status' => 'active']);
        Opportunity::create(['title' => 'Rejected Role', 'status' => 'rejected']);
        Opportunity::create(['title' => 'Closed Role', 'status' => 'closed']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Opportunities')
            ->assertSee('Active Opportunities')
            ->assertSee('3')
            ->assertSee('1');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected Role',
            'status' => 'idea',
        ]);

        $this->get(route('opportunities.index'))->assertRedirect(route('login'));
        $this->get(route('opportunities.compare'))->assertRedirect(route('login'));
        $this->get(route('opportunities.create'))->assertRedirect(route('login'));
        $this->post(route('opportunities.store'), [
            'title' => 'Guest Role',
            'status' => 'idea',
        ])->assertRedirect(route('login'));
        $this->get(route('opportunities.show', $opportunity))->assertRedirect(route('login'));
        $this->get(route('opportunities.edit', $opportunity))->assertRedirect(route('login'));
        $this->patch(route('opportunities.update', $opportunity), [
            'title' => 'Guest Update',
            'status' => 'active',
        ])->assertRedirect(route('login'));
        $this->delete(route('opportunities.destroy', $opportunity))->assertRedirect(route('login'));
    }
}

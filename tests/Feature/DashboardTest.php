<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\StrategicObjective;
use App\Models\UserPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSeeText('What deserves attention today?');
        $response->assertSeeText('What needs attention?');
        $response->assertSeeText('No urgent actions. Consider creating new opportunities.');
        $response->assertSeeText('Top Ranked Opportunities');
        $response->assertSeeText('High-Value Opportunities Missing Next Action');
        $response->assertSeeText('High-Value Opportunities With Critical Gaps');
        $response->assertSeeText('Overdue Actions on High-Value Opportunities');
        $response->assertSeeText('Recent Applications for High-Value Opportunities');
        $response->assertSeeText('Contacts Requiring Follow-Up');
        $response->assertSeeText('Dormant High-Value Relationships');
        $response->assertSeeText('Opportunity Pipeline Summary');
        $response->assertSeeText('Opportunities');
        $response->assertSeeText('Active Opportunities');
    }

    public function test_top_ranked_opportunities_appear(): void
    {
        $user = User::factory()->create();
        $highValueOpportunity = $this->createScoredOpportunity([
            'title' => 'High Value Advisory',
            'company' => 'Acme Inc.',
            'status' => 'active',
        ], 9);
        $this->createScoredOpportunity([
            'title' => 'Lower Value Contract',
            'company' => 'Globex',
            'status' => 'active',
        ], 4);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Top Ranked Opportunities')
            ->assertSeeText('High Value Advisory')
            ->assertSeeText('Acme Inc.')
            ->assertSeeText('active')
            ->assertSeeText((string) $highValueOpportunity->computedScore())
            ->assertSee(route('opportunities.show', $highValueOpportunity), false);
    }


    public function test_dashboard_top_opportunities_use_weighted_score_when_preferences_exist(): void
    {
        $user = User::factory()->create();
        UserPreference::create(array_merge(UserPreference::defaults(), [
            'user_id' => $user->id,
            'income_weight' => 0,
            'probability_weight' => 0,
            'time_to_revenue_weight' => 0,
            'strategic_alignment_weight' => 10,
            'personal_interest_weight' => 0,
            'skill_growth_weight' => 0,
            'family_fit_weight' => 10,
            'risk_weight' => 0,
        ]));
        $higherBase = Opportunity::create([
            'title' => 'Higher Base Score Option',
            'company' => 'Base Co',
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
        $weightedBest = Opportunity::create([
            'title' => 'Priority Fit Option',
            'company' => 'Fit Co',
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

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Weighted Score')
            ->assertSeeInOrder(['Priority Fit Option', (string) $weightedBest->weightedScore($user->preference), 'Higher Base Score Option', (string) $higherBase->weightedScore($user->preference)]);
    }

    public function test_high_value_opportunity_with_no_open_action_appears(): void
    {
        $user = User::factory()->create();
        $stalledOpportunity = $this->createScoredOpportunity([
            'title' => 'Stalled High Value Opportunity',
            'status' => 'active',
        ], 9);
        Action::create([
            'opportunity_id' => $stalledOpportunity->id,
            'title' => 'Completed historical follow-up',
            'completed_at' => now(),
        ]);
        $movingOpportunity = $this->createScoredOpportunity([
            'title' => 'High Value With Next Action',
            'status' => 'active',
        ], 8);
        Action::create([
            'opportunity_id' => $movingOpportunity->id,
            'title' => 'Open follow-up',
        ]);
        $parkedOpportunity = $this->createScoredOpportunity([
            'title' => 'Parked High Value Opportunity',
            'status' => 'parked',
        ], 8);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('High-Value Opportunities Missing Next Action')
            ->assertSeeText('Stalled High Value Opportunity')
            ->assertSeeText('Base score: '.$stalledOpportunity->computedScore().' · Weighted score: —');

        $missingSectionStart = strpos($response->getContent(), 'High-Value Opportunities Missing Next Action');
        $missingSectionEnd = strpos($response->getContent(), 'Overdue Actions on High-Value Opportunities', $missingSectionStart);
        $missingSection = substr($response->getContent(), $missingSectionStart, $missingSectionEnd - $missingSectionStart);

        $this->assertStringNotContainsString('High Value With Next Action', $missingSection);
        $this->assertStringNotContainsString('Parked High Value Opportunity', $missingSection);
    }

    public function test_overdue_actions_tied_to_high_value_opportunities_appear(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createScoredOpportunity([
            'title' => 'Important Client Pursuit',
            'status' => 'active',
        ], 9);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send overdue proposal follow-up',
            'due_date' => today()->subDay(),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed overdue action',
            'due_date' => today()->subDay(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Overdue Actions on High-Value Opportunities')
            ->assertSeeText('Send overdue proposal follow-up')
            ->assertSeeText('Important Client Pursuit')
            ->assertSeeText('Opportunity score: '.$opportunity->computedScore())
            ->assertDontSeeText('Completed overdue action');
    }

    public function test_lower_value_opportunities_are_not_prioritized_above_higher_value_ones(): void
    {
        $user = User::factory()->create();
        $lowValueOpportunity = $this->createScoredOpportunity([
            'title' => 'Lower Priority Opportunity',
            'status' => 'active',
        ], 3);
        $highValueOpportunity = $this->createScoredOpportunity([
            'title' => 'Higher Priority Opportunity',
            'status' => 'active',
        ], 9);
        Action::create([
            'opportunity_id' => $lowValueOpportunity->id,
            'title' => 'Lower priority overdue action',
            'due_date' => today()->subDays(3),
        ]);
        Action::create([
            'opportunity_id' => $highValueOpportunity->id,
            'title' => 'Higher priority overdue action',
            'due_date' => today()->subDay(),
        ]);
        Application::create([
            'opportunity_id' => $lowValueOpportunity->id,
            'applied_at' => now(),
            'status' => 'submitted',
        ]);
        Application::create([
            'opportunity_id' => $highValueOpportunity->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeTextInOrder(['Higher Priority Opportunity', 'Lower Priority Opportunity'])
            ->assertSeeTextInOrder(['Higher priority overdue action', 'Lower priority overdue action'])
            ->assertSeeTextInOrder(['Recent Applications for High-Value Opportunities', 'Higher Priority Opportunity', 'Lower Priority Opportunity']);
    }

    public function test_dashboard_shows_top_objectives_summary(): void
    {
        $user = User::factory()->create();
        $objective = StrategicObjective::create([
            'name' => 'Increase household income',
            'priority' => 10,
            'active' => true,
        ]);
        $inactiveObjective = StrategicObjective::create([
            'name' => 'Inactive outcome',
            'priority' => 10,
            'active' => false,
        ]);
        $lowerScoreOpportunity = $this->createScoredOpportunity([
            'title' => 'Lower value consulting lead',
            'status' => 'active',
        ], 4);
        $higherScoreOpportunity = $this->createScoredOpportunity([
            'title' => 'Premium advisory package',
            'status' => 'active',
        ], 8);
        $objective->opportunities()->attach([$lowerScoreOpportunity->id, $higherScoreOpportunity->id]);
        $inactiveObjective->opportunities()->attach($higherScoreOpportunity->id);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Top Objectives')
            ->assertSeeText('Increase household income')
            ->assertSeeText('2 linked')
            ->assertSeeText('Premium advisory package')
            ->assertSeeText('Average opportunity score')
            ->assertSeeText('34')
            ->assertDontSeeText('Inactive outcome');
    }


    public function test_dashboard_shows_contacts_requiring_follow_up(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'Due Follow Up Contact']);
        $futureContact = Contact::create(['name' => 'Future Follow Up Contact']);
        $opportunity = $this->createScoredOpportunity([
            'title' => 'Referral Opportunity',
            'status' => 'active',
        ], 8);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subDays(3),
            'interaction_type' => 'Referral',
            'summary' => 'Asked for referral status.',
            'next_follow_up_date' => today(),
        ]);
        ContactInteraction::create([
            'contact_id' => $futureContact->id,
            'interaction_date' => today(),
            'interaction_type' => 'Email',
            'summary' => 'Future check-in.',
            'next_follow_up_date' => today()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Contacts Requiring Follow-Up')
            ->assertSeeText('Due Follow Up Contact')
            ->assertSeeText('Asked for referral status.')
            ->assertSeeText('Referral Opportunity')
            ->assertDontSeeText('Future Follow Up Contact');
    }

    public function test_dashboard_shows_dormant_high_value_relationships(): void
    {
        $user = User::factory()->create();
        $dormantContact = Contact::create(['name' => 'Dormant High Value Contact']);
        $recentContact = Contact::create(['name' => 'Recently Active Contact']);
        $lowValueContact = Contact::create(['name' => 'Dormant Low Value Contact']);
        $highValueOpportunity = $this->createScoredOpportunity([
            'title' => 'Executive Advisory Lead',
            'status' => 'active',
        ], 9);
        $recentHighValueOpportunity = $this->createScoredOpportunity([
            'title' => 'Recent Executive Lead',
            'status' => 'active',
        ], 8);
        $lowValueOpportunity = $this->createScoredOpportunity([
            'title' => 'Low Value Side Quest',
            'status' => 'active',
        ], 3);
        $dormantContact->opportunities()->attach($highValueOpportunity->id);
        $recentContact->opportunities()->attach($recentHighValueOpportunity->id);
        $lowValueContact->opportunities()->attach($lowValueOpportunity->id);
        ContactInteraction::create([
            'contact_id' => $dormantContact->id,
            'opportunity_id' => $highValueOpportunity->id,
            'interaction_date' => today()->subDays(31),
            'interaction_type' => 'Meeting',
            'summary' => 'Older strategic meeting.',
        ]);
        ContactInteraction::create([
            'contact_id' => $recentContact->id,
            'opportunity_id' => $recentHighValueOpportunity->id,
            'interaction_date' => today()->subDays(5),
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Recent strategic meeting.',
        ]);
        ContactInteraction::create([
            'contact_id' => $lowValueContact->id,
            'opportunity_id' => $lowValueOpportunity->id,
            'interaction_date' => today()->subDays(45),
            'interaction_type' => 'Email',
            'summary' => 'Older low-value exchange.',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Dormant High-Value Relationships')
            ->assertSeeText('Dormant High Value Contact')
            ->assertSeeText('Executive Advisory Lead')
            ->assertDontSeeText('Recently Active Contact')
            ->assertDontSeeText('Dormant Low Value Contact');
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

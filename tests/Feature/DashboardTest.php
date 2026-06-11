<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
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
        $response->assertSeeText('Current Focus Opportunities');
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


    public function test_focused_opportunity_appears_in_dashboard_focus_section(): void
    {
        $user = User::factory()->create();
        $focusedOpportunity = $this->createScoredOpportunity([
            'title' => 'Focused Client Advisory',
            'company' => 'Acme Inc.',
            'type' => 'consulting',
            'status' => 'active',
            'is_focus' => true,
            'focused_at' => now(),
            'focus_reason' => 'Highest leverage income path.',
        ], 8);
        Action::create([
            'opportunity_id' => $focusedOpportunity->id,
            'title' => 'Schedule scope call',
            'due_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $focusSection = $this->dashboardSection($response->getContent(), 'current-focus-opportunities');

        $response->assertOk();
        $this->assertStringContainsString('Focused Client Advisory', $focusSection);
        $this->assertStringContainsString('Acme Inc.', $focusSection);
        $this->assertStringContainsString('consulting', $focusSection);
        $this->assertStringContainsString('active', $focusSection);
        $this->assertStringContainsString('Score '.$focusedOpportunity->computedScore(), $focusSection);
        $this->assertStringContainsString('Next action: Schedule scope call', $focusSection);
        $this->assertStringContainsString('Highest leverage income path.', $focusSection);
    }

    public function test_non_focused_opportunity_does_not_appear_in_dashboard_focus_section(): void
    {
        $user = User::factory()->create();
        $this->createScoredOpportunity([
            'title' => 'Focused Retainer Lead',
            'status' => 'active',
            'is_focus' => true,
            'focused_at' => now(),
        ], 8);
        $this->createScoredOpportunity([
            'title' => 'Non Focused Strong Lead',
            'status' => 'active',
        ], 9);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $focusSection = $this->dashboardSection($response->getContent(), 'current-focus-opportunities');

        $response->assertOk();
        $this->assertStringContainsString('Focused Retainer Lead', $focusSection);
        $this->assertStringNotContainsString('Non Focused Strong Lead', $focusSection);
    }

    public function test_dashboard_warns_when_more_than_five_opportunities_are_focused(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $number) {
            $this->createScoredOpportunity([
                'title' => 'Focus Opportunity '.$number,
                'status' => 'active',
                'is_focus' => true,
                'focused_at' => now(),
            ], 7);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('You have more than 5 focus opportunities. Consider narrowing your attention.');
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

        $missingSection = $this->dashboardSection($response->getContent(), 'high-value-missing-next-action');

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

        $response->assertOk();
        $overdueSection = $this->dashboardSection($response->getContent(), 'overdue-high-value-actions');

        $this->assertStringContainsString('Overdue Actions on High-Value Opportunities', $overdueSection);
        $this->assertStringContainsString('Send overdue proposal follow-up', $overdueSection);
        $this->assertStringContainsString('Important Client Pursuit', $overdueSection);
        $this->assertStringContainsString('Opportunity score: '.$opportunity->computedScore(), $overdueSection);
        $this->assertStringNotContainsString('Completed overdue action', $overdueSection);
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

        $response->assertOk();
        $objectivesSection = $this->dashboardSection($response->getContent(), 'top-objectives');

        $this->assertStringContainsString('Top Objectives', $objectivesSection);
        $this->assertStringContainsString('Increase household income', $objectivesSection);
        $this->assertStringContainsString('2 linked', $objectivesSection);
        $this->assertStringContainsString('Premium advisory package', $objectivesSection);
        $this->assertStringContainsString('Average opportunity score', $objectivesSection);
        $this->assertStringContainsString('34', $objectivesSection);
        $this->assertStringNotContainsString('Inactive outcome', $objectivesSection);
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

        $response->assertOk();
        $followUpSection = $this->dashboardSection($response->getContent(), 'contacts-requiring-follow-up');

        $this->assertStringContainsString('Contacts Requiring Follow-Up', $followUpSection);
        $this->assertStringContainsString('Due Follow Up Contact', $followUpSection);
        $this->assertStringContainsString('Asked for referral status.', $followUpSection);
        $this->assertStringContainsString('Referral Opportunity', $followUpSection);
        $this->assertStringNotContainsString('Future Follow Up Contact', $followUpSection);
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

        $response->assertOk();
        $dormantSection = $this->dashboardSection($response->getContent(), 'dormant-high-value-relationships');

        $this->assertStringContainsString('Dormant High-Value Relationships', $dormantSection);
        $this->assertStringContainsString('Dormant High Value Contact', $dormantSection);
        $this->assertStringContainsString('Executive Advisory Lead', $dormantSection);
        $this->assertStringNotContainsString('Recently Active Contact', $dormantSection);
        $this->assertStringNotContainsString('Dormant Low Value Contact', $dormantSection);
    }

    private function dashboardSection(string $content, string $testId): string
    {
        $matched = preg_match('/<section\b(?=[^>]*data-testid="'.preg_quote($testId, '/').'"[^>]*)[^>]*>(.*?)<\/section>/s', $content, $matches);

        $this->assertSame(1, $matched, 'Dashboard section [data-testid="'.$testId.'"] was not found.');

        return html_entity_decode(strip_tags($matches[0]));
    }


    public function test_dashboard_shows_gaps_without_action_plans(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createScoredOpportunity([
            'title' => 'Cloud Advisory',
            'status' => 'active',
        ], 8);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS Certification',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        $plannedGap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Portfolio Project',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $plannedGap->id,
            'title' => 'Build portfolio project',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Medium Priority Gap',
            'category' => 'Skill',
            'status' => 'Open',
            'priority' => 'Medium',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed High Gap',
            'category' => 'Portfolio',
            'status' => 'Complete',
            'priority' => 'High',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'In Progress Critical Gap',
            'category' => 'Experience',
            'status' => 'In Progress',
            'priority' => 'Critical',
        ]);
        $completedActionGap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed Action Linked Gap',
            'category' => 'Networking',
            'status' => 'Open',
            'priority' => 'High',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $completedActionGap->id,
            'title' => 'Completed linked gap action',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $gapSection = $this->dashboardSection($response->getContent(), 'gaps-without-action-plans');

        $this->assertStringContainsString('Gaps Without Action Plans', $gapSection);
        $this->assertStringContainsString('AWS Certification', $gapSection);
        $this->assertStringContainsString('Critical', $gapSection);
        $this->assertStringContainsString('Cloud Advisory', $gapSection);
        $this->assertStringNotContainsString('Portfolio Project', $gapSection);
        $this->assertStringNotContainsString('Medium Priority Gap', $gapSection);
        $this->assertStringNotContainsString('Completed High Gap', $gapSection);
        $this->assertStringNotContainsString('In Progress Critical Gap', $gapSection);
        $this->assertStringNotContainsString('Completed Action Linked Gap', $gapSection);
        $this->assertStringNotContainsString('Completed linked gap action', $gapSection);
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

<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Application;
use App\Models\Opportunity;
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
        $response->assertSeeText('Overdue Actions on High-Value Opportunities');
        $response->assertSeeText('Recent Applications for High-Value Opportunities');
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
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('High-Value Opportunities Missing Next Action')
            ->assertSeeText('Stalled High Value Opportunity')
            ->assertSeeText('Computed score: '.$stalledOpportunity->computedScore());
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

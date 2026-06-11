<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_decision_for_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();

        $response = $this->actingAs($user)->post(route('opportunities.decisions.store', $opportunity), [
            'decision_type' => 'focus',
            'reason_category' => 'strategic_alignment',
            'notes' => 'This opportunity best matches the current career strategy.',
            'decided_at' => '2026-06-11 09:30:00',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunity_decisions', [
            'opportunity_id' => $opportunity->id,
            'decision_type' => 'focus',
            'reason_category' => 'strategic_alignment',
            'notes' => 'This opportunity best matches the current career strategy.',
        ]);
    }

    public function test_decision_appears_on_opportunity_show_page(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'decision_type' => 'park',
            'reason_category' => 'capacity',
            'notes' => 'Paused because current client commitments are too heavy.',
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Decision Log')
            ->assertSeeText('Park')
            ->assertSeeText('Capacity')
            ->assertSeeText('Paused because current client commitments are too heavy.');
    }

    public function test_invalid_decision_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();

        $response = $this->actingAs($user)->from(route('opportunities.show', $opportunity))->post(route('opportunities.decisions.store', $opportunity), [
            'decision_type' => 'ignore',
            'reason_category' => 'capacity',
            'decided_at' => now()->toDateTimeString(),
        ]);

        $response
            ->assertRedirect(route('opportunities.show', $opportunity))
            ->assertSessionHasErrors('decision_type');
        $this->assertDatabaseCount('opportunity_decisions', 0);
    }

    public function test_invalid_reason_category_is_rejected(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();

        $response = $this->actingAs($user)->from(route('opportunities.show', $opportunity))->post(route('opportunities.decisions.store', $opportunity), [
            'decision_type' => 'continue',
            'reason_category' => 'because_i_said_so',
            'decided_at' => now()->toDateTimeString(),
        ]);

        $response
            ->assertRedirect(route('opportunities.show', $opportunity))
            ->assertSessionHasErrors('reason_category');
        $this->assertDatabaseCount('opportunity_decisions', 0);
    }

    public function test_opportunity_decision_appears_in_timeline(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'decision_type' => 'reopen',
            'reason_category' => 'market_timing',
            'notes' => 'A new budget cycle made this worth revisiting.',
            'decided_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Decision Logged')
            ->assertSeeText('Reopen decision: Market Timing')
            ->assertSeeText('Reopen');
    }

    private function opportunity(array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Fractional Strategy Advisor',
            'status' => 'Active',
        ], $attributes));
    }
}

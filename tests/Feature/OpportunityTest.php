<?php

namespace Tests\Feature;

use App\Models\Opportunity;
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
        ]);
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

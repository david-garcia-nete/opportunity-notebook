<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_actions(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Senior Laravel Developer',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send follow-up email',
            'due_date' => today(),
        ]);
        Action::create([
            'title' => 'Update portfolio',
            'due_date' => today()->subDay(),
        ]);
        Action::create([
            'title' => 'Completed task',
            'due_date' => today()->subDay(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('actions.index'));

        $response
            ->assertOk()
            ->assertSee('Action Tracking')
            ->assertSee('Send follow-up email')
            ->assertSee('Senior Laravel Developer')
            ->assertSee('Open')
            ->assertSee('Overdue')
            ->assertSee('Completed');
    }

    public function test_authenticated_users_can_create_actions(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Contract Product Build',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('actions.store'), [
            'opportunity_id' => $opportunity->id,
            'title' => 'Apply to role',
            'description' => 'Tailor resume and submit application.',
            'due_date' => today()->toDateString(),
            'completed_at' => null,
        ]);

        $action = Action::first();

        $response->assertRedirect(route('actions.show', $action));
        $this->assertDatabaseHas('actions', [
            'opportunity_id' => $opportunity->id,
            'title' => 'Apply to role',
            'description' => 'Tailor resume and submit application.',
            'due_date' => today()->toDateString(),
            'completed_at' => null,
        ]);
    }

    public function test_authenticated_users_can_update_actions(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Original Opportunity',
            'status' => 'active',
        ]);
        $newOpportunity = Opportunity::create([
            'title' => 'New Opportunity',
            'status' => 'active',
        ]);
        $action = Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Original action',
        ]);

        $response = $this->actingAs($user)->patch(route('actions.update', $action), [
            'opportunity_id' => $newOpportunity->id,
            'title' => 'Schedule interview',
            'description' => 'Confirm availability with the recruiter.',
            'due_date' => today()->addDay()->toDateString(),
            'completed_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('actions.show', $action));
        $this->assertDatabaseHas('actions', [
            'id' => $action->id,
            'opportunity_id' => $newOpportunity->id,
            'title' => 'Schedule interview',
            'description' => 'Confirm availability with the recruiter.',
            'due_date' => today()->addDay()->toDateString(),
        ]);
        $this->assertNotNull($action->fresh()->completed_at);
    }

    public function test_authenticated_users_can_delete_actions(): void
    {
        $user = User::factory()->create();
        $action = Action::create([
            'title' => 'Action to remove',
        ]);

        $response = $this->actingAs($user)->delete(route('actions.destroy', $action));

        $response->assertRedirect(route('actions.index'));
        $this->assertDatabaseMissing('actions', [
            'id' => $action->id,
        ]);
    }

    public function test_opportunity_show_displays_related_actions(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Recruiter Lead',
            'status' => 'active',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Reach out to recruiter',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSee('Related Actions')
            ->assertSee('Reach out to recruiter')
            ->assertSee('Open')
            ->assertSee(route('actions.create', ['opportunity_id' => $opportunity->id]), false);
    }

    public function test_dashboard_action_metrics_use_real_counts(): void
    {
        $user = User::factory()->create();
        Action::create([
            'title' => 'Due today',
            'due_date' => today(),
        ]);
        Action::create([
            'title' => 'Also due today',
            'due_date' => today(),
        ]);
        Action::create([
            'title' => 'Overdue action',
            'due_date' => today()->subDay(),
        ]);
        Action::create([
            'title' => 'Completed overdue action',
            'due_date' => today()->subDay(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Actions Due Today')
            ->assertSeeText('Overdue Actions')
            ->assertSee('<span data-testid="actions-due-today-count">2</span>', false)
            ->assertSee('<span data-testid="overdue-actions-count">1</span>', false)
            ->assertSee('<span data-testid="pipeline-actions-count">4</span>', false)
            ->assertSee('<span data-testid="todays-focus-message">You have overdue actions that need attention.</span>', false);
    }

    public function test_dashboard_focus_uses_actions_due_today_when_none_are_overdue(): void
    {
        $user = User::factory()->create();
        Action::create([
            'title' => 'Send follow-up email',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('<span data-testid="todays-focus-message">You have actions due today.</span>', false);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $action = Action::create([
            'title' => 'Protected Action',
        ]);

        $this->get(route('actions.index'))->assertRedirect(route('login'));
        $this->get(route('actions.create'))->assertRedirect(route('login'));
        $this->post(route('actions.store'), [
            'title' => 'Guest Action',
        ])->assertRedirect(route('login'));
        $this->get(route('actions.show', $action))->assertRedirect(route('login'));
        $this->get(route('actions.edit', $action))->assertRedirect(route('login'));
        $this->patch(route('actions.update', $action), [
            'title' => 'Guest Update',
        ])->assertRedirect(route('login'));
        $this->delete(route('actions.destroy', $action))->assertRedirect(route('login'));
    }
}

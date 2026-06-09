<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_applications(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Senior Product Role',
            'status' => 'active',
        ]);
        $appliedAt = now()->setSecond(0);

        $response = $this->actingAs($user)->post(route('applications.store'), [
            'opportunity_id' => $opportunity->id,
            'applied_at' => $appliedAt->format('Y-m-d H:i:s'),
            'status' => 'submitted',
            'source' => 'Company site',
            'notes' => 'Submitted tailored cover letter.',
        ]);

        $application = Application::first();

        $response->assertRedirect(route('applications.show', $application));
        $this->assertDatabaseHas('applications', [
            'opportunity_id' => $opportunity->id,
            'applied_at' => $appliedAt->format('Y-m-d H:i:s'),
            'status' => 'submitted',
            'source' => 'Company site',
            'notes' => 'Submitted tailored cover letter.',
        ]);
    }

    public function test_authenticated_users_can_update_applications(): void
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
        $application = Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
        ]);
        $appliedAt = now()->setSecond(0);

        $response = $this->actingAs($user)->patch(route('applications.update', $application), [
            'opportunity_id' => $newOpportunity->id,
            'applied_at' => $appliedAt->format('Y-m-d H:i:s'),
            'status' => 'interviewing',
            'source' => 'Referral',
            'notes' => 'Moved to recruiter screen.',
        ]);

        $response->assertRedirect(route('applications.show', $application));
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'opportunity_id' => $newOpportunity->id,
            'applied_at' => $appliedAt->format('Y-m-d H:i:s'),
            'status' => 'interviewing',
            'source' => 'Referral',
            'notes' => 'Moved to recruiter screen.',
        ]);
    }

    public function test_authenticated_users_can_delete_applications(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Application Opportunity',
            'status' => 'active',
        ]);
        $application = Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now(),
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user)->delete(route('applications.destroy', $application));

        $response->assertRedirect(route('applications.index'));
        $this->assertDatabaseMissing('applications', [
            'id' => $application->id,
        ]);
    }

    public function test_opportunity_show_displays_related_applications(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Festival Submission',
            'status' => 'active',
        ]);
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now(),
            'status' => 'submitted',
            'source' => 'Festival portal',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSee('Applications')
            ->assertSee('submitted')
            ->assertSee('Festival portal')
            ->assertSee(route('applications.create', ['opportunity_id' => $opportunity->id]), false);
    }

    public function test_opportunity_has_many_applications_relationship(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Grant Application',
            'status' => 'active',
        ]);
        $application = Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now(),
            'status' => 'submitted',
        ]);

        $this->assertTrue($opportunity->applications->contains($application));
        $this->assertTrue($application->opportunity->is($opportunity));
    }

    public function test_dashboard_application_metrics_use_real_counts(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Contract Proposal',
            'status' => 'active',
        ]);
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
        ]);
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDays(6),
            'status' => 'interviewing',
        ]);
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDays(8),
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Applications This Week')
            ->assertSeeTextInOrder(['Applications This Week', '2'])
            ->assertSeeTextInOrder(['Pipeline', 'Applications', '3']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected Opportunity',
            'status' => 'active',
        ]);
        $application = Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now(),
            'status' => 'submitted',
        ]);

        $this->get(route('applications.index'))->assertRedirect(route('login'));
        $this->get(route('applications.create'))->assertRedirect(route('login'));
        $this->post(route('applications.store'), [
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'submitted',
        ])->assertRedirect(route('login'));
        $this->get(route('applications.show', $application))->assertRedirect(route('login'));
        $this->get(route('applications.edit', $application))->assertRedirect(route('login'));
        $this->patch(route('applications.update', $application), [
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'interviewing',
        ])->assertRedirect(route('login'));
        $this->delete(route('applications.destroy', $application))->assertRedirect(route('login'));
    }
}

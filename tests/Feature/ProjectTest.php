<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_projects_index(): void
    {
        $user = User::factory()->create();
        Project::create([
            'name' => 'Opportunity Notebook',
            'url' => 'https://example.com/opportunity-notebook',
            'description' => 'A portfolio app for career decisions.',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response
            ->assertOk()
            ->assertSeeText('Project Portfolio')
            ->assertSeeText('New Project')
            ->assertSeeText('Opportunity Notebook')
            ->assertSeeText('https://example.com/opportunity-notebook')
            ->assertSeeText('Active')
            ->assertSeeText('View')
            ->assertSeeText('Edit')
            ->assertSeeText('Delete');
    }

    public function test_authenticated_users_can_create_projects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'AI Playlist Builder',
            'url' => 'https://example.com/playlist-builder',
            'description' => 'A demo that supports music technology consulting.',
            'status' => 'Active',
        ]);

        $project = Project::first();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'name' => 'AI Playlist Builder',
            'url' => 'https://example.com/playlist-builder',
            'description' => 'A demo that supports music technology consulting.',
            'status' => 'Active',
        ]);
    }

    public function test_authenticated_users_can_update_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Jam Notebook',
            'url' => 'https://example.com/jam-notebook',
            'description' => 'Original description.',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->patch(route('projects.update', $project), [
            'name' => 'Jam Notebook Pro',
            'url' => 'https://example.com/jam-notebook-pro',
            'description' => 'Updated portfolio description.',
            'status' => 'Archived',
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Jam Notebook Pro',
            'url' => 'https://example.com/jam-notebook-pro',
            'description' => 'Updated portfolio description.',
            'status' => 'Archived',
        ]);
    }

    public function test_authenticated_users_can_delete_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Open source contribution',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::create([
            'name' => 'Protected Project',
            'status' => 'Active',
        ]);

        $this->get(route('projects.index'))->assertRedirect(route('login'));
        $this->get(route('projects.create'))->assertRedirect(route('login'));
        $this->post(route('projects.store'), [
            'name' => 'Guest Project',
            'status' => 'Active',
        ])->assertRedirect(route('login'));
        $this->get(route('projects.show', $project))->assertRedirect(route('login'));
        $this->get(route('projects.edit', $project))->assertRedirect(route('login'));
        $this->patch(route('projects.update', $project), [
            'name' => 'Updated Protected Project',
            'status' => 'Archived',
        ])->assertRedirect(route('login'));
        $this->delete(route('projects.destroy', $project))->assertRedirect(route('login'));
    }

    public function test_dashboard_uses_real_project_count(): void
    {
        $user = User::factory()->create();
        Project::create([
            'name' => 'Laravel portfolio app',
            'status' => 'Active',
        ]);
        Project::create([
            'name' => 'Music technology demo',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeTextInOrder(['Pipeline', 'Projects', '2']);
    }
}

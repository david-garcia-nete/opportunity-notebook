<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_attach_a_project_to_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Laravel Developer',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'name' => 'Opportunity Notebook',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->post(route('opportunities.projects.store', $opportunity), [
            'project_id' => $project->id,
            'notes' => 'Shows Laravel product thinking and implementation depth.',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunity_project', [
            'opportunity_id' => $opportunity->id,
            'project_id' => $project->id,
            'notes' => 'Shows Laravel product thinking and implementation depth.',
        ]);
    }

    public function test_authenticated_users_can_detach_a_project_from_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'AI Developer',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'name' => 'AI Playlist Builder',
            'status' => 'Active',
        ]);
        $opportunity->projects()->attach($project->id, [
            'notes' => 'Useful proof-of-work for AI workflows.',
        ]);

        $response = $this->actingAs($user)->delete(route('opportunities.projects.destroy', [$opportunity, $project]));

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseMissing('opportunity_project', [
            'opportunity_id' => $opportunity->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_project_appears_on_opportunity_show_page(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Laravel Developer',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'name' => 'Jam Notebook',
            'url' => 'https://example.com/jam-notebook',
            'status' => 'Active',
        ]);
        $opportunity->projects()->attach($project->id, [
            'notes' => 'Demonstrates a focused Laravel portfolio project.',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Projects')
            ->assertSeeText('Jam Notebook')
            ->assertSeeText('Active')
            ->assertSeeText('https://example.com/jam-notebook')
            ->assertSeeText('Demonstrates a focused Laravel portfolio project.');
    }

    public function test_opportunity_appears_on_project_show_page(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Opportunity Notebook',
            'status' => 'Active',
        ]);
        $opportunity = Opportunity::create([
            'title' => 'AI Developer',
            'company' => 'Globex',
            'status' => 'Idea',
        ]);
        $project->opportunities()->attach($opportunity->id, [
            'notes' => 'Connects project work to AI product roles.',
        ]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response
            ->assertOk()
            ->assertSeeText('Opportunities')
            ->assertSeeText('AI Developer')
            ->assertSeeText('Globex')
            ->assertSeeText('Idea')
            ->assertSeeText('Connects project work to AI product roles.');
    }

    public function test_pivot_notes_are_stored_correctly_when_attaching_from_project(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Opportunity Notebook',
            'status' => 'Active',
        ]);
        $opportunity = Opportunity::create([
            'title' => 'Product Engineer',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->post(route('projects.opportunities.store', $project), [
            'opportunity_id' => $opportunity->id,
            'notes' => 'Directly maps to product engineering responsibilities.',
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('opportunity_project', [
            'opportunity_id' => $opportunity->id,
            'project_id' => $project->id,
            'notes' => 'Directly maps to product engineering responsibilities.',
        ]);
    }

    public function test_guests_cannot_attach_relationships(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected Role',
            'status' => 'Idea',
        ]);
        $project = Project::create([
            'name' => 'Protected Project',
            'status' => 'Active',
        ]);

        $this->post(route('opportunities.projects.store', $opportunity), [
            'project_id' => $project->id,
        ])->assertRedirect(route('login'));

        $this->post(route('projects.opportunities.store', $project), [
            'opportunity_id' => $opportunity->id,
        ])->assertRedirect(route('login'));
    }

    public function test_guests_cannot_detach_relationships(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected Role',
            'status' => 'Idea',
        ]);
        $project = Project::create([
            'name' => 'Protected Project',
            'status' => 'Active',
        ]);
        $opportunity->projects()->attach($project->id);

        $this->delete(route('opportunities.projects.destroy', [$opportunity, $project]))
            ->assertRedirect(route('login'));

        $this->delete(route('projects.opportunities.destroy', [$project, $opportunity]))
            ->assertRedirect(route('login'));
    }
}

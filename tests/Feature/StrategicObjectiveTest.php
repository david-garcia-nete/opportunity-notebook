<?php

namespace Tests\Feature;

use App\Models\StrategicObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrategicObjectiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_strategic_objectives(): void
    {
        $user = User::factory()->create();
        StrategicObjective::create([
            'name' => 'Increase household income',
            'description' => 'Prioritize opportunities that materially improve monthly income.',
            'priority' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('strategic-objectives.index'));

        $response
            ->assertOk()
            ->assertSeeText('Strategic Objectives')
            ->assertSeeText('Increase household income')
            ->assertSeeText('Active');
    }

    public function test_authenticated_users_can_create_a_strategic_objective(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('strategic-objectives.store'), [
            'name' => 'Become self-employed',
            'description' => 'Build enough reliable income to leave traditional employment.',
            'priority' => 9,
            'active' => '1',
        ]);

        $strategicObjective = StrategicObjective::first();

        $response->assertRedirect(route('strategic-objectives.show', $strategicObjective));
        $this->assertDatabaseHas('strategic_objectives', [
            'name' => 'Become self-employed',
            'description' => 'Build enough reliable income to leave traditional employment.',
            'priority' => 9,
            'active' => true,
        ]);
    }

    public function test_authenticated_users_can_update_a_strategic_objective(): void
    {
        $user = User::factory()->create();
        $strategicObjective = StrategicObjective::create([
            'name' => 'Build portfolio',
            'priority' => 5,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('strategic-objectives.update', $strategicObjective), [
            'name' => 'Build software portfolio',
            'description' => 'Ship visible Laravel projects.',
            'priority' => 8,
        ]);

        $response->assertRedirect(route('strategic-objectives.show', $strategicObjective));
        $this->assertDatabaseHas('strategic_objectives', [
            'id' => $strategicObjective->id,
            'name' => 'Build software portfolio',
            'description' => 'Ship visible Laravel projects.',
            'priority' => 8,
            'active' => false,
        ]);
    }

    public function test_authenticated_users_can_delete_a_strategic_objective(): void
    {
        $user = User::factory()->create();
        $strategicObjective = StrategicObjective::create([
            'name' => 'Develop music career',
            'priority' => 6,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('strategic-objectives.destroy', $strategicObjective));

        $response->assertRedirect(route('strategic-objectives.index'));
        $this->assertDatabaseMissing('strategic_objectives', [
            'id' => $strategicObjective->id,
        ]);
    }

    public function test_guests_cannot_manage_strategic_objectives(): void
    {
        $strategicObjective = StrategicObjective::create([
            'name' => 'Improve work-life balance',
            'priority' => 7,
            'active' => true,
        ]);

        $this->get(route('strategic-objectives.index'))->assertRedirect(route('login'));
        $this->post(route('strategic-objectives.store'), [
            'name' => 'Build professional network',
            'priority' => 4,
        ])->assertRedirect(route('login'));
        $this->delete(route('strategic-objectives.destroy', $strategicObjective))->assertRedirect(route('login'));
    }
}

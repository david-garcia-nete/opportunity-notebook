<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preference_page_creates_default_preferences_for_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('preferences.edit'));

        $response
            ->assertOk()
            ->assertSeeText('Opportunity Weighting')
            ->assertSeeText('Income')
            ->assertSeeText('Family Fit');

        $this->assertDatabaseHas('user_preferences', array_merge([
            'user_id' => $user->id,
        ], UserPreference::defaults()));
    }

    public function test_users_can_update_weighting_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('preferences.update'), [
            'income_weight' => 10,
            'probability_weight' => 4,
            'time_to_revenue_weight' => 2,
            'strategic_alignment_weight' => 9,
            'personal_interest_weight' => 3,
            'skill_growth_weight' => 6,
            'family_fit_weight' => 8,
            'risk_weight' => 1,
        ]);

        $response
            ->assertRedirect(route('preferences.edit'))
            ->assertSessionHas('status', 'Opportunity weighting preferences updated.');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'income_weight' => 10,
            'probability_weight' => 4,
            'time_to_revenue_weight' => 2,
            'strategic_alignment_weight' => 9,
            'personal_interest_weight' => 3,
            'skill_growth_weight' => 6,
            'family_fit_weight' => 8,
            'risk_weight' => 1,
        ]);
    }
}

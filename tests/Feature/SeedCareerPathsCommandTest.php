<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\Theme;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedCareerPathsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_career_paths_command_creates_initial_personal_career_data(): void
    {
        $user = User::factory()->create();

        $exitCode = Artisan::call('opportunity:seed-career-paths');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Themes: 4 created, 0 updated, 0 unchanged', Artisan::output());
        $this->assertStringContainsString('Opportunities: 8 created, 0 updated, 0 unchanged', Artisan::output());
        $this->assertStringContainsString('Gaps: 21 created, 0 updated, 0 unchanged', Artisan::output());
        $this->assertStringContainsString('Actions: 15 created, 0 updated, 0 unchanged', Artisan::output());
        $this->assertStringContainsString('Preferences: 1 created, 0 updated, 0 unchanged', Artisan::output());

        $this->assertDatabaseCount('themes', 4);
        $this->assertDatabaseCount('opportunities', 8);
        $this->assertDatabaseCount('opportunity_gaps', 21);
        $this->assertDatabaseCount('actions', 15);

        $employment = Theme::where('name', 'Employment')->firstOrFail();
        $backend = Opportunity::where('title', 'Backend Laravel / PHP Developer')->firstOrFail();

        $this->assertTrue($backend->themes()->whereKey($employment->id)->exists());
        $this->assertTrue((bool) $backend->is_focus);
        $this->assertSame('Career Path', $backend->type);
        $this->assertSame('Active', $backend->status);
        $this->assertSame(8, $backend->income_potential);
        $this->assertSame(9, $backend->family_fit);
        $this->assertNotNull($backend->focused_at);

        $this->assertDatabaseHas('opportunity_gaps', [
            'opportunity_id' => $backend->id,
            'title' => 'Need updated resume',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $this->assertDatabaseHas('actions', [
            'opportunity_id' => $backend->id,
            'title' => 'Update resume',
            'due_date' => null,
            'completed_at' => null,
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'income_weight' => 9,
            'probability_weight' => 9,
            'time_to_revenue_weight' => 9,
            'strategic_alignment_weight' => 8,
            'personal_interest_weight' => 5,
            'skill_growth_weight' => 6,
            'family_fit_weight' => 10,
            'risk_weight' => 8,
        ]);
    }

    public function test_seed_career_paths_command_is_idempotent_and_updates_existing_records(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create(['name' => 'Employment', 'description' => 'Old description.', 'active' => false]);
        $opportunity = Opportunity::create([
            'title' => 'Backend Laravel / PHP Developer',
            'type' => 'Old Type',
            'status' => 'Idea',
            'income_potential' => 1,
        ]);
        $opportunity->themes()->attach($theme);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Need updated resume',
            'status' => 'Complete',
            'priority' => 'Low',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Update resume',
            'due_date' => now()->addWeek(),
            'completed_at' => now(),
        ]);
        UserPreference::create(['user_id' => $user->id, ...UserPreference::defaults()]);

        Artisan::call('opportunity:seed-career-paths');
        Artisan::call('opportunity:seed-career-paths');

        $this->assertDatabaseCount('themes', 4);
        $this->assertDatabaseCount('opportunities', 8);
        $this->assertDatabaseCount('opportunity_gaps', 21);
        $this->assertDatabaseCount('actions', 15);
        $this->assertDatabaseCount('user_preferences', 1);

        $backend = Opportunity::where('title', 'Backend Laravel / PHP Developer')->firstOrFail();
        $this->assertSame('Career Path', $backend->type);
        $this->assertSame('Active', $backend->status);
        $this->assertSame(8, $backend->income_potential);

        $gap = OpportunityGap::where('opportunity_id', $backend->id)
            ->where('title', 'Need updated resume')
            ->firstOrFail();
        $this->assertSame('Complete', $gap->status);
        $this->assertSame('High', $gap->priority);

        $action = Action::where('opportunity_id', $backend->id)
            ->where('title', 'Update resume')
            ->firstOrFail();
        $this->assertNotNull($action->completed_at);
        $this->assertNotNull($action->due_date);
    }
}

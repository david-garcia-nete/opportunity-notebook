<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\User;
use App\Services\DailyActionQueueService;
use App\Services\OpportunityTimelineService;
use App\Support\Statuses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatusNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_statuses_save_as_controlled_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('opportunities.store'), [
            'title' => 'Normalized Opportunity',
            'status' => Statuses::OPPORTUNITY_ACTIVE,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('opportunities', [
            'title' => 'Normalized Opportunity',
            'status' => Statuses::OPPORTUNITY_ACTIVE,
        ]);
    }

    public function test_invalid_statuses_fail_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('opportunities.create'))->post(route('opportunities.store'), [
            'title' => 'Invalid Opportunity',
            'status' => 'Maybe Later Somehow',
        ]);

        $response->assertRedirect(route('opportunities.create'));
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('opportunities', [
            'title' => 'Invalid Opportunity',
        ]);
    }

    public function test_forms_render_expected_status_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('opportunities.create'))
            ->assertOk()
            ->assertSee('value="'.Statuses::OPPORTUNITY_IDEA.'"', false)
            ->assertSee('value="'.Statuses::OPPORTUNITY_FOCUSED.'"', false)
            ->assertDontSee('name="status" type="text"', false);

        $this->actingAs($user)
            ->get(route('applications.create'))
            ->assertOk()
            ->assertSee('value="'.Statuses::APPLICATION_APPLIED.'"', false)
            ->assertSee('value="'.Statuses::APPLICATION_ACCEPTED.'"', false)
            ->assertDontSee('name="status" type="text"', false);

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('value="'.Statuses::PROJECT_ACTIVE.'"', false)
            ->assertSee('value="'.Statuses::PROJECT_ARCHIVED.'"', false)
            ->assertDontSee('name="status" type="text"', false);
    }

    public function test_dashboard_logic_uses_normalized_statuses(): void
    {
        $user = User::factory()->create();
        Opportunity::create(['title' => 'Visible Active', 'status' => 'open', 'income_potential' => 8]);
        Opportunity::create(['title' => 'Hidden Rejected', 'status' => 'Rejected', 'income_potential' => 10]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Visible Active');
        $response->assertDontSee('Hidden Rejected');
    }

    public function test_timeline_logic_uses_normalized_statuses(): void
    {
        $opportunity = Opportunity::create(['title' => 'Timeline Opportunity', 'status' => 'Active']);
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Finish certification',
            'category' => 'Certification',
            'priority' => 'High',
            'status' => 'completed',
        ]);

        $timeline = app(OpportunityTimelineService::class)->forOpportunity($opportunity->fresh());

        $this->assertTrue($timeline['history']->contains(fn (array $item) => $item['type_label'] === 'Gap Completed'
            && $item['title'] === $gap->title
            && $item['status'] === Statuses::GAP_COMPLETE));
    }

    public function test_queue_logic_uses_normalized_statuses(): void
    {
        $opportunity = Opportunity::create(['title' => 'Focus Opportunity', 'status' => 'Active', 'is_focus' => true]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Portfolio proof',
            'category' => 'Portfolio',
            'priority' => 'Critical',
            'status' => 'open',
        ]);

        $summary = app(DailyActionQueueService::class)->summary();
        $queue = app(DailyActionQueueService::class)->build();

        $this->assertSame(1, $summary['critical_gap_count']);
        $this->assertTrue($queue->contains(fn (array $item) => $item['title'] === 'Gap has no action plan: Portfolio proof'));
    }

    public function test_legacy_data_migration_normalizes_expected_values(): void
    {
        DB::table('opportunities')->insert(['title' => 'Legacy Opportunity', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        $opportunityId = DB::getPdo()->lastInsertId();
        DB::table('applications')->insert(['opportunity_id' => $opportunityId, 'applied_at' => now(), 'status' => 'submitted', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->insert(['name' => 'Legacy Project', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('opportunity_gaps')->insert(['opportunity_id' => $opportunityId, 'title' => 'Legacy Gap', 'category' => 'Skill', 'priority' => 'High', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);

        $migration = include database_path('migrations/2026_06_11_000007_normalize_workflow_statuses.php');
        $migration->up();

        $this->assertDatabaseHas('opportunities', ['title' => 'Legacy Opportunity', 'status' => Statuses::OPPORTUNITY_ACTIVE]);
        $this->assertDatabaseHas('applications', ['opportunity_id' => $opportunityId, 'status' => Statuses::APPLICATION_APPLIED]);
        $this->assertDatabaseHas('projects', ['name' => 'Legacy Project', 'status' => Statuses::PROJECT_COMPLETED]);
        $this->assertDatabaseHas('opportunity_gaps', ['title' => 'Legacy Gap', 'status' => Statuses::GAP_COMPLETE]);
    }
}

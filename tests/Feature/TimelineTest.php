<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_detail_page_shows_timeline_section(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Fractional CTO Role',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Timeline')
            ->assertSeeText('Upcoming')
            ->assertSeeText('Recent History');
    }

    public function test_completed_actions_appear_in_timeline_history(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send proposal',
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Action Completed')
            ->assertSeeText('Send proposal')
            ->assertSeeText('Completed');
    }

    public function test_open_actions_with_due_dates_appear_in_upcoming_timeline(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Prepare interview notes',
            'due_date' => today()->addDays(3),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeTextInOrder(['Upcoming', 'Action Due', 'Prepare interview notes', 'Open'])
            ->assertSeeText(today()->addDays(3)->toFormattedDateString());
    }

    public function test_applications_appear_in_timeline_history(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDays(2),
            'status' => 'submitted',
            'source' => 'Company portal',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Application Submitted')
            ->assertSeeText('Application via Company portal')
            ->assertSeeText('submitted');
    }

    public function test_contact_interactions_appear_in_timeline(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        $contact = Contact::create(['name' => 'Maria Lopez']);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subDay(),
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Discussed team priorities',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Contact Interaction')
            ->assertSeeText('Discussed team priorities')
            ->assertSeeText('Maria Lopez');
    }

    public function test_contact_follow_up_dates_appear_in_upcoming_timeline(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        $contact = Contact::create(['name' => 'Jamal Smith']);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subDay(),
            'interaction_type' => 'Email',
            'summary' => 'Asked for referral context',
            'next_follow_up_date' => today()->addWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeTextInOrder(['Upcoming', 'Follow-Up Due', 'Asked for referral context'])
            ->assertSeeText(today()->addWeek()->toFormattedDateString());
    }

    public function test_opportunity_gaps_appear_in_timeline(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Portfolio proof',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Gap Opened')
            ->assertSeeText('Portfolio proof')
            ->assertSeeText('Open');
    }

    public function test_global_timeline_page_loads(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity(['title' => 'Global Advisory']);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Draft outreach',
            'due_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('timeline.index'));

        $response
            ->assertOk()
            ->assertSeeText('Unified Timeline')
            ->assertSeeText('Global Advisory')
            ->assertSeeText('Draft outreach');
    }

    public function test_global_timeline_can_filter_to_focus_opportunities(): void
    {
        $user = User::factory()->create();
        $focused = $this->opportunity([
            'title' => 'Focused Consulting',
            'is_focus' => true,
            'focused_at' => now(),
        ]);
        $notFocused = $this->opportunity(['title' => 'Backlog Job']);
        Application::create([
            'opportunity_id' => $focused->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
        ]);
        Application::create([
            'opportunity_id' => $notFocused->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user)->get(route('timeline.index', ['focus' => 1]));

        $response
            ->assertOk()
            ->assertSeeText('Focused Consulting')
            ->assertDontSeeText('Backlog Job');
    }

    public function test_dashboard_recent_activity_section_appears(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity();
        Application::create([
            'opportunity_id' => $opportunity->id,
            'applied_at' => now()->subDay(),
            'status' => 'submitted',
            'source' => 'Referral',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Recent Activity')
            ->assertSeeText('View Timeline')
            ->assertSeeText('Application via Referral');
    }

    private function opportunity(array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Senior Product Advisor',
            'status' => 'active',
        ], $attributes));
    }
}

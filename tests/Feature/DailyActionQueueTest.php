<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyActionQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_queue_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('What should I do today?')
            ->assertSeeText('Daily Queue');
    }

    public function test_overdue_focus_actions_appear(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('AI Consulting');
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Complete portfolio update',
            'due_date' => today()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Complete portfolio update')
            ->assertSeeText('AI Consulting')
            ->assertSeeText('Priority 1 · Overdue focus action')
            ->assertSeeText('Complete or reschedule this overdue action today.');
    }

    public function test_due_today_focus_actions_appear(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('Retainer Client');
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send proposal draft',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Send proposal draft')
            ->assertSeeText('Priority 2 · Due today focus action')
            ->assertSeeText('Complete this action before adding new work.');
    }

    public function test_non_focus_opportunity_actions_do_not_outrank_focus_actions(): void
    {
        $user = User::factory()->create();
        $focusOpportunity = $this->createFocusOpportunity('Focused Fractional CTO');
        $nonFocusOpportunity = Opportunity::create([
            'title' => 'Non-Focus Job Lead',
            'status' => 'Active',
            'is_focus' => false,
        ]);
        Action::create([
            'opportunity_id' => $nonFocusOpportunity->id,
            'title' => 'Non-focus overdue task',
            'due_date' => today()->subDays(3),
        ]);
        Action::create([
            'opportunity_id' => $focusOpportunity->id,
            'title' => 'Focus task due today',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Focus task due today')
            ->assertDontSeeText('Non-focus overdue task');
    }

    public function test_missing_next_action_opportunities_appear(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('AI Consulting');

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('AI Consulting has no next action')
            ->assertSeeText('Priority 3 · Missing next action')
            ->assertSeeText('Create one concrete next action for this focus opportunity.');
    }

    public function test_due_follow_ups_appear(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'John Smith']);
        $opportunity = $this->createFocusOpportunity('Referral Opportunity');
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subWeek(),
            'interaction_type' => 'Email',
            'summary' => 'Asked about referral timeline.',
            'next_follow_up_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Follow up with John Smith')
            ->assertSeeText('Referral Opportunity')
            ->assertSeeText('Priority 4 · Contact follow-up due')
            ->assertSeeText('Reach out to John Smith and log the outcome.');
    }

    public function test_critical_gaps_with_actions_appear_as_gap_reminders(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('Portfolio Consulting');
        $gap = OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Portfolio gap',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'opportunity_gap_id' => $gap->id,
            'title' => 'Start portfolio case study',
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Portfolio gap remains open')
            ->assertSeeText('Priority 6 · Critical focus gap')
            ->assertSeeText('Close this critical gap before investing in lower-priority work.');
    }

    public function test_daily_queue_includes_gaps_without_action_plans(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('Cloud Consulting');
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'AWS Certification',
            'category' => 'Certification',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('Gap has no action plan: AWS Certification')
            ->assertSeeText('Priority 5 · Gap has no action plan')
            ->assertSeeText('Create one action that starts closing this Critical gap.')
            ->assertSeeText('Cloud Consulting');
    }

    public function test_queue_summary_counts_are_correct(): void
    {
        $user = User::factory()->create();
        $focusOne = $this->createFocusOpportunity('Focused One');
        $focusTwo = $this->createFocusOpportunity('Focused Two');
        Action::create([
            'opportunity_id' => $focusOne->id,
            'title' => 'Overdue focus action',
            'due_date' => today()->subDay(),
        ]);
        Action::create([
            'opportunity_id' => $focusOne->id,
            'title' => 'Due today focus action',
            'due_date' => today(),
        ]);
        $contact = Contact::create(['name' => 'Jane Carter']);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $focusOne->id,
            'interaction_date' => today()->subDays(2),
            'interaction_type' => 'Meeting',
            'summary' => 'Discussed intro.',
            'next_follow_up_date' => today()->subDay(),
        ]);
        OpportunityGap::create([
            'opportunity_id' => $focusTwo->id,
            'title' => 'Critical proof gap',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $focusTwo->id,
            'title' => 'High proof gap',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Focus Opportunities', '2'])
            ->assertSeeInOrder(['Queue Items', '6'])
            ->assertSeeInOrder(['Overdue Actions', '1'])
            ->assertSeeInOrder(['Due Today Actions', '1'])
            ->assertSeeInOrder(['Follow-Ups Due', '1'])
            ->assertSeeInOrder(['Critical Gaps', '1']);
    }

    public function test_empty_state_message_appears_when_queue_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('daily-queue'));

        $response
            ->assertOk()
            ->assertSeeText('You are caught up. Consider reviewing focus opportunities or sourcing new opportunities.');
    }


    public function test_dashboard_shows_today_queue_card(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->createFocusOpportunity('Dashboard Focus Lead');
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Dashboard queue preview action',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText("Today's Queue")
            ->assertSeeText('1 items')
            ->assertSeeText('Dashboard queue preview action')
            ->assertSee(route('daily-queue'), false);
    }

    private function createFocusOpportunity(string $title): Opportunity
    {
        return Opportunity::create([
            'title' => $title,
            'status' => 'Active',
            'is_focus' => true,
            'focused_at' => now(),
        ]);
    }
}

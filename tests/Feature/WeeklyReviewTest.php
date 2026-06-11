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

class WeeklyReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_review_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('weekly-review'));

        $response
            ->assertOk()
            ->assertSeeText('Choose → Commit → Execute → Review → Adjust')
            ->assertSeeText('Current Focus Opportunities')
            ->assertSeeText('Completed Actions This Week')
            ->assertSeeText('Overdue Actions')
            ->assertSeeText('Open High-Priority Gaps')
            ->assertSeeText('Contact Follow-Ups Due')
            ->assertSeeText('Are these still the right focus opportunities?')
            ->assertSeeText('What is the next concrete action?');
    }

    public function test_focus_opportunities_appear_and_non_focus_opportunities_do_not_appear_in_focus_section(): void
    {
        $user = User::factory()->create();
        $focusedOpportunity = Opportunity::create([
            'title' => 'Focused Advisory Sprint',
            'company' => 'Acme Advisory',
            'status' => 'active',
            'score' => 88,
            'is_focus' => true,
            'focused_at' => now(),
            'focus_reason' => 'Highest confidence path to income.',
        ]);
        Opportunity::create([
            'title' => 'Unfocused Research Idea',
            'company' => 'Back Burner Labs',
            'status' => 'idea',
            'is_focus' => false,
        ]);
        Action::create([
            'opportunity_id' => $focusedOpportunity->id,
            'title' => 'Draft advisory proposal',
            'due_date' => today()->addDay(),
        ]);
        Action::create([
            'opportunity_id' => $focusedOpportunity->id,
            'title' => 'Missed stakeholder follow-up',
            'due_date' => today()->subDay(),
        ]);
        OpportunityGap::create([
            'opportunity_id' => $focusedOpportunity->id,
            'title' => 'Clarify buying trigger',
            'status' => 'Open',
            'priority' => 'High',
        ]);

        $response = $this->actingAs($user)->get(route('weekly-review'));
        $focusSection = $this->pageSection($response->getContent(), 'Current Focus Opportunities', 'Completed Actions This Week');

        $response->assertOk();
        $this->assertStringContainsString('Focused Advisory Sprint', $focusSection);
        $this->assertStringContainsString('Acme Advisory', $focusSection);
        $this->assertStringContainsString('active', $focusSection);
        $this->assertStringContainsString('Score 88', $focusSection);
        $this->assertStringContainsString('Highest confidence path to income.', $focusSection);
        $this->assertStringContainsString('Draft advisory proposal', $focusSection);
        $this->assertStringContainsString('Open gaps', $focusSection);
        $this->assertStringContainsString('Overdue actions', $focusSection);
        $this->assertStringNotContainsString('Unfocused Research Idea', $focusSection);
    }

    public function test_completed_actions_from_current_week_appear_and_old_completed_actions_do_not(): void
    {
        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));
        $user = User::factory()->create();
        $opportunity = Opportunity::create(['title' => 'Weekly Progress Opportunity']);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed this week action',
            'completed_at' => now()->subDay(),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed last week action',
            'completed_at' => now()->subWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('weekly-review'));
        $completedSection = $this->pageSection($response->getContent(), 'Completed Actions This Week', 'Overdue Actions');

        $response->assertOk();
        $this->assertStringContainsString('Completed this week action', $completedSection);
        $this->assertStringContainsString('Weekly Progress Opportunity', $completedSection);
        $this->assertStringNotContainsString('Completed last week action', $completedSection);
    }

    public function test_overdue_incomplete_actions_appear_and_completed_overdue_actions_do_not(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create(['title' => 'Overdue Opportunity']);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Incomplete overdue action',
            'due_date' => today()->subDay(),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed overdue action',
            'due_date' => today()->subDays(2),
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('weekly-review'));
        $overdueSection = $this->pageSection($response->getContent(), 'Overdue Actions', 'Open High-Priority Gaps');

        $response->assertOk();
        $this->assertStringContainsString('Incomplete overdue action', $overdueSection);
        $this->assertStringContainsString('Overdue Opportunity', $overdueSection);
        $this->assertStringNotContainsString('Completed overdue action', $overdueSection);
    }

    public function test_critical_or_high_open_gaps_appear_and_completed_gaps_do_not(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create(['title' => 'Gap Review Opportunity']);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Critical open gap',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'High in-progress gap',
            'status' => 'In Progress',
            'priority' => 'High',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed high gap',
            'status' => 'Complete',
            'priority' => 'High',
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Medium open gap',
            'status' => 'Open',
            'priority' => 'Medium',
        ]);

        $response = $this->actingAs($user)->get(route('weekly-review'));
        $gapSection = $this->pageSection($response->getContent(), 'Open High-Priority Gaps', 'Contact Follow-Ups Due');

        $response->assertOk();
        $this->assertStringContainsString('Critical open gap', $gapSection);
        $this->assertStringContainsString('High in-progress gap', $gapSection);
        $this->assertStringContainsString('Gap Review Opportunity', $gapSection);
        $this->assertStringNotContainsString('Completed high gap', $gapSection);
        $this->assertStringNotContainsString('Medium open gap', $gapSection);
    }

    public function test_follow_ups_due_today_or_earlier_appear_and_future_follow_ups_do_not(): void
    {
        $user = User::factory()->create();
        $dueContact = Contact::create(['name' => 'Due Follow Up Contact']);
        $futureContact = Contact::create(['name' => 'Future Follow Up Contact']);
        $opportunity = Opportunity::create(['title' => 'Referral Review Opportunity']);
        ContactInteraction::create([
            'contact_id' => $dueContact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->subWeek(),
            'interaction_type' => 'Referral',
            'summary' => 'Due follow-up summary',
            'next_follow_up_date' => today(),
        ]);
        ContactInteraction::create([
            'contact_id' => $futureContact->id,
            'interaction_date' => today(),
            'interaction_type' => 'Email',
            'summary' => 'Future follow-up summary',
            'next_follow_up_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('weekly-review'));
        $followUpSection = $this->pageSection($response->getContent(), 'Contact Follow-Ups Due', '</x-app-layout>');

        $response->assertOk();
        $this->assertStringContainsString('Due Follow Up Contact', $followUpSection);
        $this->assertStringContainsString('Referral Review Opportunity', $followUpSection);
        $this->assertStringContainsString('Due follow-up summary', $followUpSection);
        $this->assertStringNotContainsString('Future Follow Up Contact', $followUpSection);
        $this->assertStringNotContainsString('Future follow-up summary', $followUpSection);
    }

    private function pageSection(string $content, string $startText, string $endText): string
    {
        $sectionStart = strpos($content, $startText);
        $sectionEnd = strpos($content, $endText, $sectionStart);

        if ($sectionStart === false) {
            return '';
        }

        if ($sectionEnd === false) {
            return substr($content, $sectionStart);
        }

        return substr($content, $sectionStart, $sectionEnd - $sectionStart);
    }
}

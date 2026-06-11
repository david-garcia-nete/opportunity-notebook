<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\OpportunityGap;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FocusReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_a_focus_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.focus.start'));

        $review = Review::first();

        $response->assertRedirect(route('reviews.focus.show', $review));
        $this->assertSame('focus', $review->review_type);
        $this->assertNotNull($review->started_at);
        $this->assertNull($review->completed_at);
    }

    public function test_focus_review_displays_focus_opportunities(): void
    {
        $user = User::factory()->create();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now()]);
        $focus = $this->focusOpportunity('Focused Advisory');
        $nonFocus = Opportunity::create(['title' => 'Passive Lead', 'status' => 'Active']);
        OpportunityGap::create(['opportunity_id' => $focus->id, 'title' => 'Portfolio proof', 'status' => 'Open', 'priority' => 'High']);
        Action::create(['opportunity_id' => $focus->id, 'title' => 'Overdue follow-up', 'due_date' => today()->subDay()]);
        $contact = Contact::create(['name' => 'Maria Lopez', 'organization' => 'Acme']);
        $focus->contacts()->attach($contact);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $focus->id,
            'interaction_date' => today()->subDay(),
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Discussed team priorities',
            'next_follow_up_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('reviews.focus.show', $review));

        $response
            ->assertOk()
            ->assertSeeText('Guided Focus Review')
            ->assertSeeText('Focused Advisory')
            ->assertDontSeeText('Passive Lead')
            ->assertSeeText('Weighted')
            ->assertSeeText('Forecast')
            ->assertSeeText('Readiness')
            ->assertSeeText('Portfolio proof')
            ->assertSeeText('Overdue follow-up')
            ->assertSeeText('Maria Lopez')
            ->assertSeeText('Discussed team priorities');

        $this->assertSame('Passive Lead', $nonFocus->title);
    }

    public function test_user_can_record_continue_decision_during_focus_review_and_link_it_to_review(): void
    {
        $user = User::factory()->create();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now()]);
        $opportunity = $this->focusOpportunity('Focused Consulting');

        $response = $this->actingAs($user)->post(route('reviews.focus.complete', $review), [
            'decisions' => [
                $opportunity->id => [
                    'decision_type' => 'continue',
                    'reason_category' => 'strategic_alignment',
                    'notes' => 'Still fits the plan.',
                ],
            ],
        ]);

        $response->assertRedirect(route('reviews.show', $review));

        $this->assertDatabaseHas('opportunity_decisions', [
            'opportunity_id' => $opportunity->id,
            'review_id' => $review->id,
            'decision_type' => 'continue',
            'reason_category' => 'strategic_alignment',
            'notes' => 'Still fits the plan.',
        ]);
        $this->assertNotNull($review->fresh()->completed_at);
    }

    public function test_optional_next_action_can_be_created(): void
    {
        $user = User::factory()->create();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now()]);
        $opportunity = $this->focusOpportunity('Focused Contract');

        $this->actingAs($user)->post(route('reviews.focus.complete', $review), [
            'decisions' => [
                $opportunity->id => [
                    'decision_type' => 'intensify',
                    'reason_category' => 'financial_return',
                    'notes' => 'Upside is worth more effort.',
                ],
            ],
            'next_actions' => [
                $opportunity->id => [
                    'title' => 'Send focused proposal',
                    'due_date' => today()->addDays(2)->toDateString(),
                    'description' => 'Clarify value and timeline.',
                ],
            ],
        ])->assertRedirect(route('reviews.show', $review));

        $this->assertDatabaseHas('actions', [
            'opportunity_id' => $opportunity->id,
            'title' => 'Send focused proposal',
            'due_date' => today()->addDays(2)->toDateString(),
            'description' => 'Clarify value and timeline.',
        ]);
    }

    public function test_completed_focus_review_appears_on_review_show_page(): void
    {
        $user = User::factory()->create();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now()]);
        $opportunity = $this->focusOpportunity('Focused Retainer');
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'review_id' => $review->id,
            'decision_type' => 'park',
            'reason_category' => 'capacity',
            'notes' => 'Not enough capacity this month.',
            'decided_at' => now(),
        ]);
        $review->update(['completed_at' => now()]);

        $response = $this->actingAs($user)->get(route('reviews.show', $review));

        $response
            ->assertOk()
            ->assertSeeText('Focus Review')
            ->assertSeeText('Opportunity decisions from this review')
            ->assertSeeText('Focused Retainer')
            ->assertSeeText('Park decision: Capacity')
            ->assertSeeText('Not enough capacity this month.');
    }

    public function test_invalid_decision_type_or_reason_category_is_rejected(): void
    {
        $user = User::factory()->create();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now()]);
        $opportunity = $this->focusOpportunity('Focused Sprint');

        $this->actingAs($user)
            ->from(route('reviews.focus.show', $review))
            ->post(route('reviews.focus.complete', $review), [
                'decisions' => [
                    $opportunity->id => [
                        'decision_type' => 'focus',
                        'reason_category' => 'made_up_reason',
                    ],
                ],
            ])
            ->assertRedirect(route('reviews.focus.show', $review))
            ->assertSessionHasErrors([
                'decisions.'.$opportunity->id.'.decision_type',
                'decisions.'.$opportunity->id.'.reason_category',
            ]);

        $this->assertDatabaseCount('opportunity_decisions', 0);
    }

    private function focusOpportunity(string $title): Opportunity
    {
        return Opportunity::create([
            'title' => $title,
            'status' => 'Active',
            'is_focus' => true,
            'focused_at' => now(),
            'income_potential' => 8,
            'probability_of_success' => 8,
            'time_to_revenue' => 3,
            'strategic_alignment' => 9,
            'personal_interest' => 8,
            'skill_growth' => 8,
            'family_fit' => 7,
            'risk_level' => 3,
        ]);
    }
}

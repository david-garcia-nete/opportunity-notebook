<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_weekly_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store'), [
            'review_type' => 'weekly',
            'summary' => 'Focused on higher-probability advisory leads.',
            'notes' => 'Keep outreach constrained to two warm intros next week.',
        ]);

        $review = Review::first();

        $response->assertRedirect(route('reviews.show', $review));
        $this->assertDatabaseHas('reviews', [
            'review_type' => 'weekly',
            'summary' => 'Focused on higher-probability advisory leads.',
            'notes' => 'Keep outreach constrained to two warm intros next week.',
        ]);
        $this->assertNotNull($review->completed_at);
    }

    public function test_invalid_review_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('reviews.create'))->post(route('reviews.store'), [
            'review_type' => 'quarterly',
            'summary' => 'This type is not part of the foundation taxonomy.',
        ]);

        $response
            ->assertRedirect(route('reviews.create'))
            ->assertSessionHasErrors('review_type');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_appears_on_reviews_index(): void
    {
        $user = User::factory()->create();
        Review::create([
            'review_type' => 'daily',
            'summary' => 'Cleared urgent next actions.',
            'completed_at' => now()->subDay(),
        ]);
        Review::create([
            'review_type' => 'weekly',
            'summary' => 'Chose one focus opportunity for the week.',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reviews.index'));

        $response
            ->assertOk()
            ->assertSeeText('Review Sessions')
            ->assertSeeText('Weekly')
            ->assertSeeText('Chose one focus opportunity for the week.')
            ->assertSeeText('Daily')
            ->assertSeeText('Cleared urgent next actions.');
    }

    public function test_review_show_page_displays_summary_and_notes(): void
    {
        $user = User::factory()->create();
        $review = Review::create([
            'review_type' => 'focus',
            'summary' => 'Moved attention to the strongest consulting lead.',
            'notes' => 'The portfolio project creates better proof than another cold application.',
            'completed_at' => '2026-06-11 10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reviews.show', $review));

        $response
            ->assertOk()
            ->assertSeeText('Focus Review')
            ->assertSeeText('Moved attention to the strongest consulting lead.')
            ->assertSeeText('The portfolio project creates better proof than another cold application.');
    }

    public function test_opportunity_decision_can_optionally_belong_to_a_review(): void
    {
        $user = User::factory()->create();
        $review = Review::create([
            'review_type' => 'portfolio',
            'summary' => 'Compared active opportunities.',
            'completed_at' => now(),
        ]);
        $opportunity = Opportunity::create([
            'title' => 'Fractional Strategy Advisor',
            'status' => 'Active',
        ]);

        $decision = OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'review_id' => $review->id,
            'decision_type' => 'focus',
            'reason_category' => 'strategic_alignment',
            'notes' => 'Best aligned with advisory positioning.',
            'decided_at' => now(),
        ]);

        $this->assertTrue($decision->review->is($review));
        $this->assertTrue($review->opportunityDecisions->first()->is($decision));

        $response = $this->actingAs($user)->get(route('reviews.show', $review));

        $response
            ->assertOk()
            ->assertSeeText('Fractional Strategy Advisor')
            ->assertSeeText('Focus decision: Strategic Alignment')
            ->assertSeeText('Best aligned with advisory positioning.');
    }
}

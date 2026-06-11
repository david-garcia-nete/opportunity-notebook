<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_contact_interactions_from_contact_context(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'Jordan Lee']);
        $opportunity = Opportunity::create([
            'title' => 'Senior Laravel Role',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('contact-interactions.store'), [
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today()->format('Y-m-d'),
            'interaction_type' => 'Email',
            'summary' => 'Jordan offered to introduce me to the hiring manager.',
            'outcome' => 'Introduction expected this week.',
            'next_follow_up_date' => today()->addDays(2)->format('Y-m-d'),
            'redirect_to' => 'contact',
        ]);

        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertDatabaseHas('contact_interactions', [
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_type' => 'Email',
            'summary' => 'Jordan offered to introduce me to the hiring manager.',
            'outcome' => 'Introduction expected this week.',
        ]);
    }

    public function test_authenticated_users_can_update_contact_interactions(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'Maria Santos']);
        $interaction = ContactInteraction::create([
            'contact_id' => $contact->id,
            'interaction_date' => today()->subDay(),
            'interaction_type' => 'Meeting',
            'summary' => 'Initial conversation.',
        ]);

        $response = $this->actingAs($user)->patch(route('contact-interactions.update', $interaction), [
            'contact_id' => $contact->id,
            'interaction_date' => today()->format('Y-m-d'),
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Discussed consulting needs and referral options.',
            'outcome' => 'Maria will send two introductions.',
            'next_follow_up_date' => today()->addWeek()->format('Y-m-d'),
            'redirect_to' => 'contact',
        ]);

        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertDatabaseHas('contact_interactions', [
            'id' => $interaction->id,
            'interaction_type' => 'Coffee Chat',
            'summary' => 'Discussed consulting needs and referral options.',
            'outcome' => 'Maria will send two introductions.',
        ]);
    }

    public function test_authenticated_users_can_delete_contact_interactions(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'Contact With History']);
        $interaction = ContactInteraction::create([
            'contact_id' => $contact->id,
            'interaction_date' => today(),
            'interaction_type' => 'Phone Call',
            'summary' => 'Quick check-in.',
        ]);

        $response = $this->actingAs($user)->delete(route('contact-interactions.destroy', $interaction), [
            'redirect_to' => 'contact',
        ]);

        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertDatabaseMissing('contact_interactions', [
            'id' => $interaction->id,
        ]);
    }

    public function test_contact_detail_shows_relationship_activity_and_metrics(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'High Influence Contact']);
        $activeOpportunity = $this->createScoredOpportunity([
            'title' => 'Premium Advisory Lead',
            'status' => 'active',
        ], 8);
        $closedOpportunity = $this->createScoredOpportunity([
            'title' => 'Closed Consulting Lead',
            'status' => 'closed',
        ], 4);
        $contact->opportunities()->attach([$activeOpportunity->id, $closedOpportunity->id]);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $activeOpportunity->id,
            'interaction_date' => today()->subDay(),
            'interaction_type' => 'Referral',
            'summary' => 'Discussed referral path for the advisory lead.',
            'outcome' => 'Contact will make an introduction.',
            'next_follow_up_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('contacts.show', $contact));

        $response
            ->assertOk()
            ->assertSeeText('Relationship Activity')
            ->assertSeeText('Total interactions')
            ->assertSeeText('Last interaction')
            ->assertSeeText('Upcoming follow-ups')
            ->assertSeeText('Referral')
            ->assertSeeText('Discussed referral path for the advisory lead.')
            ->assertSeeText('Contact will make an introduction.')
            ->assertSeeText('Linked opportunities')
            ->assertSeeText('Active opportunities')
            ->assertSeeText('Average opportunity score')
            ->assertSeeText('2')
            ->assertSeeText('1')
            ->assertSeeText('34');
    }

    public function test_opportunity_detail_shows_related_contact_activity(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['name' => 'Avery Recruiter']);
        $opportunity = Opportunity::create([
            'title' => 'Director Role',
            'status' => 'active',
        ]);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today(),
            'interaction_type' => 'Interview',
            'summary' => 'Prepared for panel interview.',
            'outcome' => 'Panel scheduled for Friday.',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Related Contact Activity')
            ->assertSeeText('Avery Recruiter')
            ->assertSeeText('Interview')
            ->assertSeeText('Prepared for panel interview.')
            ->assertSeeText('Panel scheduled for Friday.');
    }

    public function test_contact_and_opportunity_relationships_include_interactions(): void
    {
        $contact = Contact::create(['name' => 'Relationship Contact']);
        $opportunity = Opportunity::create([
            'title' => 'Relationship Opportunity',
            'status' => 'active',
        ]);
        $interaction = ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today(),
            'interaction_type' => 'Introduction',
            'summary' => 'Intro made.',
        ]);

        $this->assertTrue($contact->contactInteractions->contains($interaction));
        $this->assertTrue($opportunity->contactInteractions->contains($interaction));
        $this->assertTrue($interaction->contact->is($contact));
        $this->assertTrue($interaction->opportunity->is($opportunity));
    }

    private function createScoredOpportunity(array $attributes, int $factorValue): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Scored Opportunity',
            'status' => 'active',
            'income_potential' => $factorValue,
            'probability_of_success' => $factorValue,
            'time_to_revenue' => 1,
            'strategic_alignment' => $factorValue,
            'personal_interest' => $factorValue,
            'skill_growth' => $factorValue,
            'family_fit' => $factorValue,
            'risk_level' => 1,
        ], $attributes));
    }
}

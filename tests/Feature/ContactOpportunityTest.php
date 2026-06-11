<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_attach_a_contact_to_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Senior Laravel Role',
            'status' => 'Active',
        ]);
        $contact = Contact::create([
            'name' => 'Jordan Lee',
        ]);

        $response = $this->actingAs($user)->post(route('opportunities.contacts.store', $opportunity), [
            'contact_id' => $contact->id,
            'relationship_type' => 'Recruiter',
            'notes' => 'Initial screening call scheduled.',
        ]);

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseHas('contact_opportunity', [
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'relationship_type' => 'Recruiter',
            'notes' => 'Initial screening call scheduled.',
        ]);
    }

    public function test_authenticated_users_can_detach_a_contact_from_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Contract Product Build',
            'status' => 'Active',
        ]);
        $contact = Contact::create([
            'name' => 'Maria Santos',
        ]);
        $opportunity->contacts()->attach($contact->id, [
            'relationship_type' => 'Client',
        ]);

        $response = $this->actingAs($user)->delete(route('opportunities.contacts.destroy', [$opportunity, $contact]));

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertDatabaseMissing('contact_opportunity', [
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_authenticated_users_can_see_linked_contacts_on_opportunity_show_page(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::create([
            'title' => 'Fractional CTO Advisory',
            'status' => 'Active',
        ]);
        $contact = Contact::create([
            'name' => 'Taylor Morgan',
            'organization' => 'Northwind Studio',
            'email' => 'taylor@example.com',
        ]);
        $opportunity->contacts()->attach($contact->id, [
            'relationship_type' => 'Referral',
        ]);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Contacts')
            ->assertSeeText('Taylor Morgan')
            ->assertSeeText('Northwind Studio')
            ->assertSeeText('taylor@example.com')
            ->assertSeeText('Referral');
    }

    public function test_authenticated_users_can_see_linked_opportunities_on_contact_show_page(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create([
            'name' => 'Avery Chen',
        ]);
        $opportunity = Opportunity::create([
            'title' => 'AI Operations Consultant',
            'company' => 'Globex',
            'status' => 'Idea',
        ]);
        $contact->opportunities()->attach($opportunity->id, [
            'relationship_type' => 'Hiring Manager',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.show', $contact));

        $response
            ->assertOk()
            ->assertSeeText('Opportunities')
            ->assertSeeText('AI Operations Consultant')
            ->assertSeeText('Globex')
            ->assertSeeText('Idea')
            ->assertSeeText('Hiring Manager');
    }

    public function test_guests_cannot_attach_or_detach_links(): void
    {
        $opportunity = Opportunity::create([
            'title' => 'Protected Role',
            'status' => 'Idea',
        ]);
        $contact = Contact::create([
            'name' => 'Protected Contact',
        ]);

        $this->post(route('opportunities.contacts.store', $opportunity), [
            'contact_id' => $contact->id,
        ])->assertRedirect(route('login'));

        $this->delete(route('opportunities.contacts.destroy', [$opportunity, $contact]))
            ->assertRedirect(route('login'));

        $this->post(route('contacts.opportunities.store', $contact), [
            'opportunity_id' => $opportunity->id,
        ])->assertRedirect(route('login'));

        $this->delete(route('contacts.opportunities.destroy', [$contact, $opportunity]))
            ->assertRedirect(route('login'));
    }
}

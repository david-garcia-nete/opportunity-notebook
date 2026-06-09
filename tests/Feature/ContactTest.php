<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_contacts_index(): void
    {
        $user = User::factory()->create();
        Contact::create([
            'name' => 'Jordan Lee',
            'organization' => 'Acme Recruiting',
            'email' => 'jordan@example.com',
            'phone' => '555-123-4567',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.index'));

        $response
            ->assertOk()
            ->assertSee('Contact Management')
            ->assertSee('Jordan Lee')
            ->assertSee('Acme Recruiting')
            ->assertSee('jordan@example.com')
            ->assertSee('555-123-4567');
    }

    public function test_authenticated_users_can_create_contacts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Maria Santos',
            'organization' => 'Northwind Studio',
            'email' => 'maria@example.com',
            'phone' => '555-987-6543',
            'notes' => 'Studio owner interested in consulting help.',
        ]);

        $contact = Contact::first();

        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertDatabaseHas('contacts', [
            'name' => 'Maria Santos',
            'organization' => 'Northwind Studio',
            'email' => 'maria@example.com',
            'phone' => '555-987-6543',
            'notes' => 'Studio owner interested in consulting help.',
        ]);
    }

    public function test_authenticated_users_can_update_contacts(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create([
            'name' => 'Original Contact',
            'organization' => 'Original Org',
        ]);

        $response = $this->actingAs($user)->patch(route('contacts.update', $contact), [
            'name' => 'Updated Contact',
            'organization' => 'Updated Org',
            'email' => 'updated@example.com',
            'phone' => '555-111-2222',
            'notes' => 'Referral for a local business lead.',
        ]);

        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Updated Contact',
            'organization' => 'Updated Org',
            'email' => 'updated@example.com',
            'phone' => '555-111-2222',
            'notes' => 'Referral for a local business lead.',
        ]);
    }

    public function test_authenticated_users_can_delete_contacts(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create([
            'name' => 'Contact to Remove',
        ]);

        $response = $this->actingAs($user)->delete(route('contacts.destroy', $contact));

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $contact = Contact::create([
            'name' => 'Protected Contact',
        ]);

        $this->get(route('contacts.index'))->assertRedirect(route('login'));
        $this->get(route('contacts.create'))->assertRedirect(route('login'));
        $this->post(route('contacts.store'), [
            'name' => 'Guest Contact',
        ])->assertRedirect(route('login'));
        $this->get(route('contacts.show', $contact))->assertRedirect(route('login'));
        $this->get(route('contacts.edit', $contact))->assertRedirect(route('login'));
        $this->patch(route('contacts.update', $contact), [
            'name' => 'Guest Update',
        ])->assertRedirect(route('login'));
        $this->delete(route('contacts.destroy', $contact))->assertRedirect(route('login'));
    }

    public function test_dashboard_uses_real_contact_count(): void
    {
        $user = User::factory()->create();
        Contact::create(['name' => 'Recruiter Contact']);
        Contact::create(['name' => 'Business Owner Contact']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeTextInOrder(['Pipeline', 'Contacts', '2']);
    }
}

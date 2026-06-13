<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateDeploymentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_user_can_access_dashboard(): void
    {
        config(['opportunity.allowed_emails' => ['david@example.com', 'dad@example.com']]);
        $user = User::factory()->create(['email' => 'david@example.com']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_unapproved_authenticated_user_cannot_access_dashboard(): void
    {
        config(['opportunity.allowed_emails' => ['david@example.com', 'dad@example.com']]);
        $user = User::factory()->create(['email' => 'stranger@example.com']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertForbidden()
            ->assertSeeText('This account is not approved for this private family notebook.');
        $this->assertGuest();
    }

    public function test_login_still_works_for_an_approved_user(): void
    {
        config(['opportunity.allowed_emails' => ['david@example.com', 'dad@example.com']]);
        $user = User::factory()->create(['email' => 'dad@example.com']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}

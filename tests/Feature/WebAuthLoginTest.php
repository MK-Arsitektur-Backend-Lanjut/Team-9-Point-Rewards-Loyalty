<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_required_on_homepage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Login untuk mengakses sistem');
        $response->assertDontSee('Module 4 Testing Dashboard');
    }

    public function test_valid_web_login_redirects_to_dashboard_and_stores_session(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertEquals($user->id, auth()->id());
        $this->assertNotEmpty(session('jwt_token'));
        $this->assertEquals($user->email, session('user.email'));
    }

    public function test_user_can_register_from_web_and_be_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
        $this->assertNotEmpty(session('jwt_token'));
        $this->assertEquals('newuser@example.com', session('user.email'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('/');

        $response->assertStatus(200);
        $response->assertSee('Module 4 Testing Dashboard');
        $response->assertSee('Logout');
    }
}

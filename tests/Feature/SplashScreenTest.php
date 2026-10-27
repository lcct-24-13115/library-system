<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Splash screen: the book opens, then the sign-in page. Pure front end, no data involved.
 */
class SplashScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_splash_screen_that_leads_to_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome')
            ->assertSee('Saint Augustine Learning Resource Center')
            ->assertSee(route('login'), false)
            ->assertSee('salrc.fromSplash', false);
    }

    public function test_signed_in_users_skip_the_splash_and_go_home(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);

        $this->actingAs($librarian)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_the_login_page_knows_when_it_comes_from_the_splash(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('salrc.fromSplash', false)->assertSee('id="book-close-btn"', false);
    }
}

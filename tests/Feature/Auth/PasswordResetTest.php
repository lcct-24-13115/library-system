<?php

namespace Tests\Feature\Auth;

use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function patron(array $over = []): Patron
    {
        return Patron::create(array_merge([
            'id_number'   => '2024-0001',
            'name'        => 'Juan Dela Cruz',
            'patron_type' => 'Student',
            'status'      => 'active',
        ], $over));
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_screen_needs_verification_first(): void
    {
        $this->get('/reset-password')->assertRedirect(route('password.request'));
    }

    public function test_unknown_id_number_is_rejected(): void
    {
        $this->patron();

        $this->post('/forgot-password', ['id_number' => '9999-0000'])
            ->assertSessionHasErrors('id_number');

        $this->get('/reset-password')->assertRedirect(route('password.request'));
    }

    public function test_staff_email_cannot_self_reset(): void
    {
        $user = User::factory()->create();

        $this->post('/forgot-password', ['id_number' => $user->email])
            ->assertSessionHasErrors('id_number');
    }

    public function test_patron_can_reset_with_id_number(): void
    {
        $patron = $this->patron();
        $account = $patron->provisionAccount('2024-0001');

        $this->post('/forgot-password', ['id_number' => '2024-0001'])
            ->assertRedirect(route('password.reset'));

        $this->get('/reset-password')->assertOk();

        $this->post('/reset-password', ['password' => 'NewPass-12345', 'password_confirmation' => 'NewPass-12345'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPass-12345', $account->fresh()->password));
    }

    public function test_patron_without_account_gets_one_when_resetting(): void
    {
        $patron = $this->patron();

        $this->post('/forgot-password', ['id_number' => '2024-0001']);
        $this->post('/reset-password', ['password' => 'NewPass-12345', 'password_confirmation' => 'NewPass-12345']);

        $this->assertNotNull($patron->fresh()->account);
    }
}

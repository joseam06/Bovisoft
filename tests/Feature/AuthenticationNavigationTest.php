<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\FincasTestCase;

class AuthenticationNavigationTest extends FincasTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    public function test_old_worker_session_is_repaired_without_losing_authentication(): void
    {
        $user = User::factory()->create(['password' => 'Ficticia-Redirect-2026!', 'email_verified_at' => null, 'legacy_email_access' => true]);
        $this->withSession(['url.intended' => url('/sw.js'), '_previous.url' => url('/build/app.js')])->withHeader('Referer', url('/sw.js'))
            ->post('/login', ['email' => $user->email, 'password' => 'Ficticia-Redirect-2026!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertSee('Tu correo aún no está verificado')->assertDontSee('importScripts');
    }

    public function test_valid_protected_destination_is_preserved(): void
    {
        $user = User::factory()->create(['password' => 'Ficticia-Redirect-2026!']);
        $this->withSession(['url.intended' => url('/fincas')])->post('/login', ['email' => $user->email, 'password' => 'Ficticia-Redirect-2026!'])->assertRedirect(url('/fincas'));
        $this->get('/fincas')->assertOk();
    }

    public function test_wrong_password_from_old_worker_returns_to_login(): void
    {
        $user = User::factory()->create();
        $this->withHeader('Referer', url('/sw.js'))->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertRedirect(url('/login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_registration_still_requires_verification(): void
    {
        $this->withHeader('Referer', url('/sw.js'))->post('/register', ['name' => 'Prueba redirect', 'email' => 'redirect-new@example.test', 'password' => 'Ficticia-Redirect-2026!', 'password_confirmation' => 'Ficticia-Redirect-2026!'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get('/email/verify')->assertOk()->assertSee('Verifica tu correo');
    }
}

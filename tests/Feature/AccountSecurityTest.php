<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use App\Models\WorkspaceProfile;
use App\Notifications\AccountNotice;
use App\Notifications\AccountPasswordReset;
use App\Notifications\AccountVerification;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Tests\FincasTestCase;

class AccountSecurityTest extends FincasTestCase
{
    use RefreshDatabase;

    private string $password = 'Ficticia-SoloPruebas-2026!';

    private array $googleClaims = [];

    private bool $googleStubbed = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        Mail::fake();
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        // Real HTTP requests rebuild these guards; the multi-account feature
        // fixture reuses the application and must discard Sanctum's cached user.
        Auth::forgetGuards();
        session()->forget(['password_hash_web', 'password_hash_sanctum', 'account_version', 'account_version_user']);

        return parent::actingAs($user, $guard ?? 'web');
    }

    private function user(array $extra = []): User
    {
        return User::factory()->create($extra + ['password' => $this->password]);
    }

    private function farm(User $u): Finca
    {
        return Finca::create(['user_id' => $u->id, 'codigo' => 'SEC-'.$u->id, 'nombre' => 'Finca ficticia', 'area' => 10, 'activa' => true, 'latitud' => 4.5, 'longitud' => -74]);
    }

    private function emailLink(User $u, string $email): string
    {
        $this->actingAs($u, 'web')->post('/perfil/seguridad/correo', ['new_email' => $email, 'current_password' => $this->password])->assertRedirect()->assertSessionHasNoErrors();
        $url = '';
        Notification::assertSentOnDemand(AccountNotice::class, function ($n, $channels, $notifiable) use ($email, &$url) {
            if (($notifiable->routes['mail'] ?? null) !== $email) {
                return false;
            }$url = $n->url;

            return $n->title === 'Confirma tu nuevo correo';
        });
        $this->assertNotEmpty($url);

        return $url;
    }

    private function google(array $extra = []): void
    {
        $this->googleClaims = $extra + ['sub' => 'google-ficticio-1', 'email' => 'google-ficticio@example.test', 'email_verified' => true, 'name' => 'Productor ficticio'];
        if ($this->googleStubbed) {
            return;
        }$this->googleStubbed = true;
        Socialite::shouldReceive('driver')->with('google')->andReturnUsing(function () {
            $raw = $this->googleClaims;

            return (new GoogleProvider(request(), 'test-client', 'test-secret', 'http://localhost/auth/google/callback'))->setHttpClient(new Client(['handler' => HandlerStack::create(new MockHandler([
                new Response(200, [], json_encode(['access_token' => 'fictitious-access-only', 'token_type' => 'Bearer', 'expires_in' => 3600])), new Response(200, [], json_encode($raw)),
            ]))]));
        });
    }

    private function googleCallback(): \Illuminate\Testing\TestResponse
    {
        $state = session('state');
        $this->assertNotEmpty($state);

        return $this->get('/auth/google/callback?code=fictitious-code&state='.urlencode($state));
    }

    public function test_registration_verification_resend_expiration_and_address_correction(): void
    {
        $this->post('/register', ['name' => 'Registro ficticio', 'email' => 'Nuevo@Example.test', 'password' => $this->password, 'password_confirmation' => $this->password])->assertRedirect();
        $user = User::where('email_identity', 'nuevo@example.test')->firstOrFail();
        $this->assertFalse($user->legacy_email_access);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, AccountVerification::class);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->postJson('/api/offline/v1/workspace/snapshots')->assertForbidden();
        $this->get('/perfil')->assertOk();
        $this->post('/email/verification-notification')->assertRedirect();
        Notification::assertSentToTimes($user, AccountVerification::class, 2);
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expired)->assertForbidden();
        $url = $this->emailLink($user, 'corregido@example.test');
        $this->assertSame('nuevo@example.test', $user->fresh()->email);
        $this->get($url)->assertRedirect('/perfil');
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('corregido@example.test', $user->email);
        $this->getJson($url)->assertUnprocessable();
        $other = $this->user(['email_verified_at' => null]);
        $this->actingAs($other, 'web');
        Event::fake([Verified::class]);
        $signed = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->get($signed)->assertRedirect();
        $this->get($signed)->assertRedirect();
        Event::assertDispatchedTimes(Verified::class, 1);
        $this->assertTrue($other->fresh()->hasVerifiedEmail());
        $mail = (new AccountVerification)->toMail($other);
        $this->assertStringContainsString('BoviSoft', view($mail->view, $mail->viewData)->render());
        Mail::assertNothingSent();
    }

    public function test_legacy_access_email_transaction_collisions_old_links_and_alternative_routes(): void
    {
        $user = $this->user(['email' => 'ficticio@example.test', 'email_verified_at' => null, 'legacy_email_access' => true]);
        $farm = $this->farm($user);
        $this->actingAs($user, 'web');
        $this->get('/dashboard')->assertOk()->assertSee('Tu correo aún no está verificado');
        $this->postJson('/api/offline/v1/workspace/snapshots')->assertOk();
        $profile = WorkspaceProfile::where('user_id', $user->id)->firstOrFail();
        $version = $profile->version;
        $this->patchJson('/perfil', ['name' => $user->name, 'email' => 'bypass@example.test'])->assertUnprocessable();
        $this->putJson('/user/profile-information', ['name' => $user->name, 'email' => 'bypass@example.test'])->assertUnprocessable();
        $old = $this->emailLink($user, 'primero@example.test');
        $new = $this->emailLink($user, 'definitivo@example.test');
        $this->getJson($old)->assertUnprocessable();
        $reset = Password::broker()->createToken($user);
        $this->get($new)->assertRedirect('/perfil');
        $user->refresh();
        $this->assertSame('definitivo@example.test', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame($user->id, $farm->fresh()->user_id);
        $this->assertFalse(Password::broker()->tokenExists($user, $reset));
        $this->assertSame((int) $version, (int) $profile->fresh()->version, 'Cambiar credenciales no debe crear conflictos ficticios con nombres offline');
        app(\App\Services\WorkspaceRecords::class)->write($user, 'perfil', 'update', ['name' => 'Nombre pendiente', 'base_version' => $version], $profile->fresh());
        $this->assertSame('Nombre pendiente', $user->fresh()->name);
        $first = $this->user();
        $second = $this->user();
        $a = $this->emailLink($first, 'colision@example.test');
        $b = $this->emailLink($second, 'colision@example.test');
        $this->actingAs($first, 'web')->get($a)->assertRedirect('/perfil');
        $this->actingAs($second, 'web')->getJson($b)->assertUnprocessable();
        $this->assertSame(1, User::where('email_identity', 'colision@example.test')->count());
        $this->assertNotSame('colision@example.test', $second->fresh()->email);
        $this->actingAs($user, 'web')->get($b)->assertForbidden();
    }

    public function test_google_linking_state_claims_collisions_two_factor_and_safe_unlink(): void
    {
        $user = $this->user(['email_verified_at' => null, 'legacy_email_access' => true]);
        $farm = $this->farm($user);
        $this->actingAs($user, 'web');
        $this->google();
        $this->post('/perfil/seguridad/google/vincular', ['current_password' => $this->password])->assertRedirect();
        $this->get('/auth/google/callback?state=manipulado&code=x')->assertRedirect('/perfil');
        $this->assertNull($user->fresh()->google_id);
        $this->post('/perfil/seguridad/google/vincular', ['current_password' => $this->password])->assertRedirect();
        $this->googleCallback()->assertRedirect('/perfil');
        $proposal = session('account.google_proposal');
        $this->assertNotNull($proposal);
        $this->assertNull($user->fresh()->google_id);
        $this->post('/perfil/seguridad/google/confirmar', ['nonce' => $proposal['nonce'], 'replace_email' => 1])->assertRedirect('/perfil');
        $user->refresh();
        $this->assertSame('google-ficticio-1', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame($user->id, $farm->fresh()->user_id);
        $this->postJson('/perfil/seguridad/google/confirmar', ['nonce' => $proposal['nonce']])->assertForbidden();
        $this->delete('/perfil/seguridad/google', ['current_password' => $this->password])->assertRedirect('/perfil');
        $this->assertNull($user->fresh()->google_id);
        $this->assertTrue($user->fresh()->hasOwnPassword());
        Auth::guard('web')->logout();
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->get('/auth/google')->assertRedirect();
        $this->googleCallback()->assertRedirect('/login');
        $this->assertGuest();
        // Registration must never silently attach a provider to a matching email.
        $this->get('/auth/google/register')->assertRedirect();
        $this->googleCallback()->assertRedirect('/login');
        $this->assertSame(1, User::count());
        $user->refresh()->forceFill(['google_id' => 'google-ficticio-1', 'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => encrypt(json_encode(['recovery-test-only']))])->save();
        $this->get('/auth/google')->assertRedirect();
        $this->googleCallback()->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['recovery_code' => 'recovery-test-only'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        Auth::guard('web')->logout();
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->google(['sub' => 'nuevo-google', 'email' => 'nuevo-google@example.test', 'email_verified' => false]);
        $this->get('/auth/google/register')->assertRedirect();
        $this->googleCallback()->assertRedirect('/login');
        $this->assertSame(1, User::count());
        $this->google(['sub' => 'nuevo-google', 'email' => 'nuevo-google@example.test']);
        $this->get('/auth/google/register')->assertRedirect();
        $this->googleCallback()->assertRedirect('/dashboard');
        $created = User::where('google_id', 'nuevo-google')->firstOrFail();
        $this->assertTrue($created->hasVerifiedEmail());
        $this->assertFalse($created->password_login_enabled);
        $this->assertNull($created->password_set_at);
        $this->get('/auth/google/callback?state=reutilizado&code=x')->assertRedirect('/perfil');
        $this->assertSame(2, User::count());
    }

    public function test_verified_recovery_generic_responses_and_google_first_password(): void
    {
        $user = $this->user();
        $unverified = $this->user(['email_verified_at' => null, 'legacy_email_access' => true]);
        $a = $this->postJson('/forgot-password', ['email' => $user->email])->assertOk()->json();
        $b = $this->postJson('/forgot-password', ['email' => $unverified->email])->assertOk()->json();
        $c = $this->postJson('/forgot-password', ['email' => 'inexistente@example.test'])->assertOk()->json();
        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        Notification::assertSentTo($user, AccountPasswordReset::class);
        Notification::assertNotSentTo($unverified, AccountPasswordReset::class);
        $token = Password::broker()->createToken($user);
        $new = 'Nueva-Ficticia-2026!';
        $this->travel(61)->minutes();
        $expired = $this->postJson('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => $new, 'password_confirmation' => $new])->assertUnprocessable()->json();
        $unknown = $this->postJson('/reset-password', ['email' => 'inexistente@example.test', 'token' => $token, 'password' => $new, 'password_confirmation' => $new])->assertUnprocessable()->json();
        $this->assertSame($expired, $unknown);
        $this->travelBack();
        $token = Password::broker()->createToken($user);
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => $new, 'password_confirmation' => $new])->assertRedirect();
        $this->assertTrue(Hash::check($new, $user->fresh()->password));
        $this->postJson('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => $new, 'password_confirmation' => $new])->assertUnprocessable();
        $google = $this->user(['google_id' => 'google-ficticio-1', 'password_login_enabled' => false, 'password_set_at' => null]);
        $this->actingAs($google, 'web');
        Auth::guard('web')->login($google);
        $this->google(['email' => $google->email]);
        $this->patchJson('/perfil/password', ['password' => $new, 'password_confirmation' => $new])->assertUnprocessable();
        $this->deleteJson('/perfil/seguridad/google', ['current_password' => $this->password])->assertUnprocessable();
        $this->post('/perfil/seguridad/google/reauth')->assertRedirect();
        $this->googleCallback()->assertRedirect('/perfil');
        $this->put('/user/password', ['password' => $new, 'password_confirmation' => $new])->assertRedirect();
        $this->assertTrue($google->fresh()->hasOwnPassword());
        $this->assertSame((int) $google->fresh()->credentials_version, session('account_version'));
        $this->assertSame(Auth::guard('web')->user()->getAuthPassword(), session('password_hash_web'));
        // A new HTTP request resolves fresh guards; keep the real login session.
        $this->assertNotNull(session(Auth::guard('web')->getName()));
        Auth::forgetGuards();
        $this->get('/perfil')->assertOk();
        Auth::guard('web')->logout();
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->post('/login', ['email' => $google->email, 'password' => $new])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($google);
    }

    public function test_offline_safe_status_access_and_sensitive_templates(): void
    {
        $legacy = $this->user(['email_verified_at' => null]);
        $migration = require database_path('migrations/2026_11_24_000001_secure_account_access.php');
        $migration->down();
        $migration->up();
        $legacy->refresh();
        $this->assertTrue($legacy->legacy_email_access);
        $this->assertNull($legacy->email_verified_at);
        $this->farm($legacy);
        $this->actingAs($legacy, 'web');
        $s = $this->getJson('/offline/session')->assertOk()->json();
        $this->assertTrue($s['workspace_enabled']);
        $this->assertFalse($s['profile']['email_verified']);
        $this->assertFalse($s['profile']['google_linked']);
        foreach (['password', 'google_id', 'pending_email_token_hash', 'credentials_version'] as $key) {
            $this->assertArrayNotHasKey($key, $s['profile']);
        }
        $views = $this->getJson('/api/offline/v1/workspace/views')->assertOk()->json('views.perfil.edit');
        $this->assertStringNotContainsString('name="current_password"', $views);
        $this->assertStringNotContainsString('name="nonce"', $views);
        $new = $this->user(['email_verified_at' => null]);
        $this->actingAs($new, 'web');
        $this->assertFalse($this->getJson('/offline/session')->assertOk()->json('workspace_enabled'));
        $this->get('/perfil')->assertOk();
        $this->getJson('/api/offline/v1/workspace/changes?cursor=x')->assertForbidden();
        for ($i = 0; $i < 3; $i++) {
            $this->post('/email/verification-notification')->assertRedirect();
        }$this->post('/email/verification-notification')->assertStatus(429);
    }

    public function test_credential_revision_rejects_an_old_session_and_expired_identity_proof(): void
    {
        $user = $this->user(['credentials_version' => 2]);
        $farm = $this->farm($user);
        $this->actingAs($user, 'web')->withSession(['account_version_user' => $user->id, 'account_version' => 1]);
        $this->postJson('/api/offline/v1/workspace/snapshots', [])->assertUnauthorized();
        Auth::forgetGuards();
        $this->assertNull($this->getJson('/offline/session')->assertOk()->json('account_id'));
        $this->assertSame($user->id, $farm->fresh()->user_id);
        $this->actingAs($user, 'web')->withSession(['account.reauthenticated' => ['id' => $user->id, 'version' => 2, 'at' => time() - 301, 'method' => 'google']]);
        $this->patchJson('/perfil/password', ['password' => 'Nueva-Prueba-2026!', 'password_confirmation' => 'Nueva-Prueba-2026!'])->assertUnprocessable();
        $this->assertTrue(Hash::check($this->password, $user->fresh()->password));
    }
}

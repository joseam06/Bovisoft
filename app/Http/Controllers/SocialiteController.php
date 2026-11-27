<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        return $this->start($request, 'login');
    }

    public function registerWithGoogle(Request $request)
    {
        return $this->start($request, 'register');
    }

    public function link(Request $request)
    {
        return $this->start($request, 'link');
    }

    public function reauthenticate(Request $request)
    {
        return $this->start($request, 'reauth');
    }

    private function start(Request $request, string $purpose)
    {
        if (in_array($purpose, ['login', 'register'], true)) {
            abort_if(Auth::guard('web')->check(), 409, 'Usa Seguridad y acceso a la cuenta para vincular Google.');
        } else {
            abort_unless(Auth::guard('web')->check(), 403);
            if ($purpose === 'link') {
                app(AccountSecurity::class)->confirmIdentity($request, $request->user(), $request->all());
            }
            if ($purpose === 'reauth') {
                abort_unless($request->user()->google_id, 403);
            }
        }
        $request->session()->forget('account.google_proposal');
        $request->session()->put('account.oauth_intent', ['purpose' => $purpose, 'id' => $request->user()?->id,
            'version' => (int) ($request->user()?->credentials_version ?? 0), 'at' => time()]);

        return Socialite::driver('google')->with(['prompt' => in_array($purpose, ['link', 'reauth'], true) ? 'select_account consent' : 'select_account'])->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $intent = $request->session()->pull('account.oauth_intent');
        if (! $intent || $intent['at'] < time() - 300 || $intent['at'] > time()) {
            return $this->failure('La solicitud de Google venció o ya fue utilizada. Inicia el proceso nuevamente.');
        }
        $purpose = $intent['purpose'];
        $linking = in_array($purpose, ['link', 'reauth'], true);
        if ($linking && (! $request->user() || $request->user()->id !== $intent['id'] || (int) $request->user()->credentials_version !== $intent['version'])) {
            return $this->failure('La sesión cambió. No se vinculó ninguna cuenta.');
        }
        if (! $linking && Auth::guard('web')->check()) {
            return $this->failure('La sesión cambió. No se inició otra cuenta.');
        }
        try {
            // Socialite validates/consumes state and retrieves authenticated claims.
            // Never use stateless() or decode an unsigned ID token here.
            $provider = Socialite::driver('google')->user();
            $raw = $provider->getRaw();
            $sub = (string) $provider->getId();
            $email = User::normalizeEmail((string) $provider->getEmail());
            if (! $sub || strlen($sub) > 255 || ($raw['sub'] ?? null) !== $sub || ($raw['email_verified'] ?? false) !== true || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->failure('Google no confirmó de forma válida la identidad y la propiedad del correo.');
            }
            if ($purpose === 'reauth') {
                if (! hash_equals((string) $request->user()->google_id, $sub)) {
                    return $this->failure('Selecciona la cuenta de Google que ya está vinculada a BoviSoft.');
                }
                app(AccountSecurity::class)->rememberIdentity($request, $request->user(), 'google');

                return redirect('/perfil')->with('success', 'Identidad confirmada con Google durante cinco minutos.');
            }
            if ($purpose === 'link') {
                $request->session()->put('account.google_proposal', ['id' => $intent['id'], 'version' => $intent['version'], 'at' => time(),
                    'nonce' => Str::random(48), 'sub' => $sub, 'email' => $email]);

                return redirect('/perfil')->with('success', 'Revisa y confirma la vinculación de Google.');
            }
            $user = User::where('google_id', $sub)->first();
            if ($purpose === 'register') {
                if ($user || User::where('email_identity', $email)->exists()) {
                    return $this->failure('No se puede crear esta cuenta. Inicia sesión con tu método actual y vincula Google desde Mi perfil.');
                }
                $user = new User(['name' => Str::limit($provider->getName() ?: 'Productor', 255, ''), 'email' => $email, 'password' => Hash::make(Str::random(64))]);
                $user->forceFill(['google_id' => $sub, 'email_verified_at' => now(), 'password_login_enabled' => false, 'password_set_at' => null])->save();
            } elseif (! $user) {
                return $this->failure('Este acceso no está vinculado. Inicia sesión con tu método actual o utiliza Registro con Google.');
            }
            if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
                $request->session()->regenerate();
                $request->session()->put(['login.id' => $user->id, 'login.remember' => false]);

                return redirect()->route('two-factor.login');
            }
            Auth::guard('web')->login($user, false);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->failure('No se pudo completar el acceso con Google. No se fusionaron cuentas ni se reemplazaron vínculos.');
        }
    }

    private function failure(string $message)
    {
        return redirect(Auth::guard('web')->check() ? '/perfil' : '/login')->withErrors(['google' => $message]);
    }
}

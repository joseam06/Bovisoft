<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountSecurity
{
    use \App\Actions\Fortify\PasswordValidationRules;

    public function availableEmail(string $email, ?int $except = null): string
    {
        $email = User::normalizeEmail($email);
        Validator::make(['email' => $email], ['email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email_identity')->ignore($except)]], [
            'email.required' => 'Escribe un correo electrónico.',
            'email.email' => 'Escribe una dirección de correo válida.',
            'email.max' => 'El correo no puede superar 255 caracteres.',
            'email.unique' => 'Ese correo no está disponible. No se fusionaron ni modificaron otras cuentas.',
        ])->validate();

        return $email;
    }

    public function confirmIdentity(Request $request, User $user, array $input = []): void
    {
        if (! empty($input['current_password'])) {
            if (! $user->password_login_enabled || ! Hash::check($input['current_password'], $user->getAuthPassword())) {
                $this->fail('current_password', 'La contraseña actual no es correcta. Si accedes con Google, confirma tu identidad con Google.');
            }
            $this->rememberIdentity($request, $user, 'password');
        }
        $proof = $request->session()->get('account.reauthenticated');
        $now = now()->timestamp;
        if (! $proof || $proof['id'] !== $user->id || $proof['version'] !== (int) $user->credentials_version || $proof['at'] > $now || $proof['at'] < $now - 300) {
            $this->fail('identity', 'Confirma tu identidad con tu contraseña actual o con Google antes de continuar.');
        }
    }

    public function rememberIdentity(Request $request, User $user, string $method): void
    {
        $request->session()->put('account.reauthenticated', ['id' => $user->id, 'version' => (int) $user->credentials_version, 'at' => now()->timestamp, 'method' => $method]);
        $request->session()->put('auth.password_confirmed_at', time());
    }

    public function requestEmail(Request $request, array $input): void
    {
        $email = User::normalizeEmail((string) ($input['new_email'] ?? ''));
        $nonce = Str::random(64);
        $this->locked($request->user(), function (User $user) use ($request, $input, $email, $nonce) {
            $this->confirmIdentity($request, $user, $input);
            $email = $this->availableEmail($email, $user->id);
            if (User::normalizeEmail($user->email) === $email) {
                $this->fail('new_email', 'Escribe una dirección diferente de la actual.');
            }
            $user->forceFill(['pending_email' => $email, 'pending_email_token_hash' => hash('sha256', $nonce.'|'.$user->credentials_version), 'pending_email_expires_at' => now()->addMinutes(60)])->save();
        });
        $url = URL::temporarySignedRoute('account.email.confirm', now()->addMinutes(60), ['user' => $request->user()->id, 'nonce' => $nonce]);
        Notification::route('mail', $email)->notify(new AccountNotice('Confirma tu nuevo correo', ['Confirma que esta dirección es tuya. Tu correo actual no cambiará hasta completar este paso.', 'El enlace vence en 60 minutos y requiere iniciar sesión en tu cuenta de BoviSoft.'], 'Confirmar nuevo correo', $url));
    }

    public function confirmEmail(Request $request, int $owner, string $nonce): void
    {
        abort_unless($request->user()->id === $owner, 403);
        $old = null;
        $notify = false;
        $user = $this->locked($request->user(), function (User $user) use ($nonce, &$old, &$notify) {
            if (! $user->pending_email || ! $user->pending_email_expires_at || $user->pending_email_expires_at->isPast() || ! hash_equals($user->pending_email_token_hash ?? '', hash('sha256', $nonce.'|'.$user->credentials_version))) {
                $this->fail('email', 'La solicitud venció o ya fue utilizada. Solicita un nuevo cambio desde Mi perfil.');
            }
            $email = $this->availableEmail($user->pending_email, $user->id);
            $old = $user->email;
            $notify = $user->hasVerifiedEmail();
            $this->bump($user);
            $user->applyConfirmedEmail($email);
            $this->invalidateResetTokens([$old, $email]);

            return $user;
        });
        $this->retainSession($request, $user);
        if ($notify) {
            Notification::route('mail', $old)->notify(new AccountNotice('Cambió el correo de tu cuenta', ['El correo principal de tu cuenta de BoviSoft fue cambiado. Si no solicitaste este cambio, contacta al responsable del servicio.']));
        }
    }

    public function updatePassword(Request $request, User $original, array $input, bool $reset = false): void
    {
        Validator::make($input, ['password' => $this->passwordRules()])->validate();
        $user = $this->locked($original, function (User $user) use ($request, $input, $reset) {
            if ($reset) {
                if (! $user->hasVerifiedEmail() || User::normalizeEmail((string) ($input['email'] ?? '')) !== $user->email_identity || ! Password::broker()->tokenExists($user, (string) ($input['token'] ?? ''))) {
                    $this->fail('email', 'El enlace no es válido o ya venció. Solicita uno nuevo.');
                }
            } else {
                $this->confirmIdentity($request, $user, $input);
            }
            $this->bump($user);
            $user->forceFill(['password' => Hash::make($input['password']), 'password_login_enabled' => true, 'password_set_at' => now()])->save();
            $this->invalidateResetTokens([$user->email]);

            return $user;
        });
        $original->setRawAttributes($user->getAttributes(), true);
        if ($reset) {
            $this->revokeSessions($user, null);
        } else {
            $this->retainSession($request, $user);
        }
        if ($user->hasVerifiedEmail()) {
            $user->notify(new AccountNotice('Cambió tu contraseña', ['Se estableció una nueva contraseña para tu cuenta de BoviSoft. Si no lo hiciste tú, recupera el acceso desde el sitio y contacta al responsable del servicio.']));
        }
    }

    public function linkGoogle(Request $request, array $proposal, bool $replaceEmail, array $input = []): void
    {
        $old = null;
        $notifyOld = false;
        $user = $this->locked($request->user(), function (User $user) use ($request, $proposal, $replaceEmail, $input, &$old, &$notifyOld) {
            $this->confirmIdentity($request, $user, $input);
            abort_unless($proposal['id'] === $user->id && $proposal['version'] === (int) $user->credentials_version && $proposal['at'] >= time() - 300, 403);
            if ($user->google_id && $user->google_id !== $proposal['sub']) {
                $this->fail('google', 'La cuenta ya tiene otro vínculo. No se reemplazará automáticamente.');
            }
            if (User::where('google_id', $proposal['sub'])->where('id', '!=', $user->id)->exists()) {
                $this->fail('google', 'Esa cuenta de Google no está disponible para vincularse. No se fusionaron cuentas.');
            }
            $email = $this->availableEmail($proposal['email'], $user->id);
            $old = $user->email;
            $notifyOld = $user->hasVerifiedEmail() && User::normalizeEmail($old) !== $email && $replaceEmail;
            if ($request->session()->get('account.reauthenticated.method') === 'password' && ! $user->password_set_at) {
                $user->password_set_at = now();
            }
            $this->bump($user);
            $user->google_id = $proposal['sub'];
            if ($replaceEmail || User::normalizeEmail($old) === $email) {
                $user->applyConfirmedEmail($email);
            } else {
                $user->save();
            }
            $this->invalidateResetTokens([$old, $email]);

            return $user;
        });
        $request->session()->forget('account.google_proposal');
        $this->retainSession($request, $user);
        if ($notifyOld) {
            Notification::route('mail', $old)->notify(new AccountNotice('Cambió el correo de tu cuenta', ['Adoptaste como correo principal la dirección verificada de Google. Tu cuenta y tus registros conservan el mismo identificador.']));
        }
        if ($user->hasVerifiedEmail()) {
            $user->notify(new AccountNotice('Google vinculado a tu cuenta', ['Ahora puedes acceder también con Google. No se creó otra cuenta ni se trasladaron registros.']));
        }
    }

    public function unlinkGoogle(Request $request, array $input): void
    {
        $user = $this->locked($request->user(), function (User $user) use ($request, $input) {
            $this->confirmIdentity($request, $user, $input);
            if (! $user->google_id) {
                $this->fail('google', 'No hay una cuenta de Google vinculada.');
            }
            $knowsPassword = $user->hasOwnPassword() || ($user->password_login_enabled && $request->session()->get('account.reauthenticated.method') === 'password');
            if (! $knowsPassword || ! $user->hasVerifiedEmail()) {
                $this->fail('google', 'Antes de desvincular Google, establece una contraseña propia y verifica tu correo para conservar acceso y recuperación.');
            }
            if (! $user->password_set_at) {
                $user->password_set_at = now();
            }
            $this->bump($user);
            $user->google_id = null;
            $user->save();
            $this->invalidateResetTokens([$user->email]);

            return $user;
        });
        $this->retainSession($request, $user);
        $user->notify(new AccountNotice('Google desvinculado', ['Google dejó de ser un método de acceso. Tu correo verificado y tu contraseña propia siguen disponibles.']));
    }

    private function bump(User $user): void
    {
        $user->forceFill(['credentials_version' => (int) $user->credentials_version + 1, 'remember_token' => Str::random(60),
            'pending_email' => null, 'pending_email_token_hash' => null, 'pending_email_expires_at' => null]);
    }

    private function invalidateResetTokens(array $emails): void
    {
        DB::connection(config('auth.passwords.users.connection'))->table(config('auth.passwords.users.table', 'password_reset_tokens'))->whereIn('email', array_unique($emails))->delete();
    }

    public function revokeSessions(User $user, ?string $keep): void
    {
        if (config('session.driver') === 'database') {
            $query = DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id);
            if ($keep) {
                $query->where('id', '!=', $keep);
            }$query->delete();
        }
    }

    private function retainSession(Request $request, User $user): void
    {
        $this->revokeSessions($user, $request->session()->getId());
        $request->session()->forget(['account.reauthenticated', 'account.oauth_intent', 'account.google_proposal']);
        $request->session()->put('account_version', (int) $user->credentials_version);
        // Fortify uses web while the existing profile uses Sanctum/Jetstream.
        // Refresh both session checks after a legitimate password change.
        foreach (array_unique(array_filter(['web', Auth::getDefaultDriver(), config('jetstream.guard'), config('fortify.guard')])) as $guard) {
            $request->session()->put('password_hash_'.$guard, $user->getAuthPassword());
        }
        $request->session()->regenerate(true);
        Auth::guard('web')->setUser($user);
        if (Auth::getDefaultDriver() !== 'web') {
            Auth::guard()->setUser($user);
        }
    }

    private function locked(User $user, callable $action)
    {
        try {
            return app(WorkspaceJournal::class)->transaction($user->id, fn () => $action(User::whereKey($user->id)->lockForUpdate()->firstOrFail()));
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->fail('email', 'El correo o la cuenta de Google ya no están disponibles. No se modificó tu cuenta.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

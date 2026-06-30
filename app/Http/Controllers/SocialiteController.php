<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirige al usuario a la pantalla de login de Google.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Maneja la respuesta de Google tras la autenticación.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['login' => 'No se pudo autenticar con Google. Intenta de nuevo.']);
        }

        // Buscar usuario existente por google_id o por email
        $user = User::where('google_id', $googleUser->getId())->first()
             ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Si ya existe pero no tiene google_id vinculado, lo vinculamos
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    // Solo actualizar foto si no tiene una propia
                    'profile_photo_path' => $user->profile_photo_path ?? $googleUser->getAvatar(),
                ]);
            }
        } else {
            // Usuario nuevo: crear cuenta automáticamente
            $user = User::create([
                'name'                => $googleUser->getName(),
                'email'               => $googleUser->getEmail(),
                'google_id'           => $googleUser->getId(),
                'password'            => bcrypt(\Illuminate\Support\Str::random(24)),
                'profile_photo_path'  => $googleUser->getAvatar(),
                'email_verified_at'   => now(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }
}
<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;

class VerifiedPasswordBroker extends PasswordBroker
{
    public function getUser(#[\SensitiveParameter] array $credentials)
    {
        return User::where('email_identity', User::normalizeEmail((string) ($credentials['email'] ?? '')))->whereNotNull('email_verified_at')->first();
    }
}

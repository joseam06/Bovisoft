<?php

namespace App\Http\Responses;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse;

class PrivatePasswordResetFailure implements FailedPasswordResetResponse
{
    public function toResponse($request)
    {
        throw ValidationException::withMessages(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo para un correo verificado.']);
    }
}

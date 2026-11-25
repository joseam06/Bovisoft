<?php

namespace App\Http\Controllers;

use App\Services\AccountSecurity;
use Illuminate\Http\Request;

class AccountSecurityController extends Controller
{
    public function email(Request $request, AccountSecurity $security)
    {
        $security->requestEmail($request, $request->all());

        return redirect()->route('profile.edit')->with('success', 'Enviamos la confirmación al nuevo correo. Conservas tu correo actual hasta confirmarlo.');
    }

    public function confirmEmail(Request $request, int $user, string $nonce, AccountSecurity $security)
    {
        $security->confirmEmail($request, $user, $nonce);

        return redirect('/perfil')->with('success', 'Correo confirmado. Tu cuenta y tus registros mantienen el mismo identificador.');
    }

    public function confirmGoogle(Request $request, AccountSecurity $security)
    {
        $proposal = $request->session()->get('account.google_proposal');
        abort_unless($proposal && hash_equals($proposal['nonce'], (string) $request->input('nonce')), 403);
        $security->linkGoogle($request, $proposal, $request->boolean('replace_email'), $request->all());

        return redirect('/perfil')->with('success', 'Google quedó vinculado a tu misma cuenta.');
    }

    public function unlinkGoogle(Request $request, AccountSecurity $security)
    {
        $security->unlinkGoogle($request, $request->all());

        return redirect('/perfil')->with('success', 'Google quedó desvinculado. Conservas tu contraseña y tu correo verificado.');
    }
}

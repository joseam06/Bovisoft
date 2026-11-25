<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if(User::normalizeEmail($input['email'])!==$user->email_identity)throw \Illuminate\Validation\ValidationException::withMessages(['email'=>'Utiliza Cambiar correo electrónico en Seguridad y acceso a la cuenta. El correo anterior se conserva hasta confirmar el nuevo.']);
        if (isset($input['photo'])) $user->updateProfilePhoto($input['photo']);
        if($input['name']!==$user->name){
            $profile=\App\Models\WorkspaceProfile::firstOrCreate(['user_id'=>$user->id],['name'=>$user->name]);
            app(\App\Services\WorkspaceRecords::class)->write($user,'perfil','update',['name'=>$input['name'],'base_version'=>$input['base_version']??null],$profile);
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
}

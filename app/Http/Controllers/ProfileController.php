<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
            'offlineProfile' => \App\Models\WorkspaceProfile::firstOrCreate(['user_id'=>Auth::id()], ['name'=>Auth::user()->name]),
        ]);
    }

    public function update(Request $request)
    {
        app(\App\Actions\Fortify\UpdateUserProfileInformation::class)->update($request->user(),$request->all());

        return back()->with('success', 'Información actualizada correctamente.');
    }

    public function updatePassword(Request $request)
    {
        app(\App\Actions\Fortify\UpdateUserPassword::class)->update($request->user(),$request->all());

        return redirect()->route('profile.edit')->with('success_password', 'Contraseña actualizada correctamente.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'string'],
        ]);

        $user      = Auth::user();
        $photoData = $request->input('photo');

        if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $photoData)) {
            return back()->withErrors(['photo' => 'Formato de imagen no válido.']);
        }

        $base64  = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
        $decoded = base64_decode($base64);

        if (!$decoded) {
            return back()->withErrors(['photo' => 'No se pudo procesar la imagen.']);
        }

        if (strlen($decoded) > 5 * 1024 * 1024) {
            return back()->withErrors(['photo' => 'La imagen no puede superar los 5 MB.']);
        }

        // Eliminar foto anterior solo si es archivo local (no URL de Google)
        if (
            $user->profile_photo_path &&
            !str_starts_with($user->profile_photo_path, 'http') &&
            Storage::disk('public')->exists($user->profile_photo_path)
        ) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $filename = 'profile_photos/' . $user->id . '_' . time() . '.jpg';
        Storage::disk('public')->put($filename, $decoded);

        $user->profile_photo_path = $filename;
        $user->save();

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function deletePhoto()
    {
        $user = Auth::user();

        // Eliminar solo si es archivo local (no URL de Google)
        if (
            $user->profile_photo_path &&
            !str_starts_with($user->profile_photo_path, 'http') &&
            Storage::disk('public')->exists($user->profile_photo_path)
        ) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->profile_photo_path = null;
        $user->save();

        return back()->with('success', 'Foto de perfil eliminada.');
    }
}

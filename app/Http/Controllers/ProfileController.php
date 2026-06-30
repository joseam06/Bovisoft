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
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'name.max'       => 'El nombre no puede superar 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'Ingresa un correo electrónico válido.',
            'email.unique'   => 'Este correo ya está registrado por otro usuario.',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'Información actualizada correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Debes ingresar tu contraseña actual.',
            'password.required'         => 'La nueva contraseña es obligatoria.',
            'password.min'              => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'Las contraseñas no coinciden.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success_password', 'Contraseña actualizada correctamente.');
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
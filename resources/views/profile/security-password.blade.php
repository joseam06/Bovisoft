@if($user->hasOwnPassword())
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2" for="security-password-{{ $context }}">Contraseña actual para confirmar tu identidad</label>
    <input id="security-password-{{ $context }}" type="password" name="current_password" autocomplete="current-password" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500" placeholder="Puedes omitirla si acabas de confirmar con Google">
</div>
@endif

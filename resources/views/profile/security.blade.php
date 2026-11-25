<section id="seguridad" data-account-security class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6 space-y-5">
    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-shield-halved text-red-600" aria-hidden="true"></i> Seguridad y acceso a la cuenta</h2>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div class="min-w-0"><dt class="font-semibold text-gray-600">Correo electrónico actual</dt><dd data-account-security-email class="break-words text-gray-800">{{ $user->email }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Verificación</dt><dd data-account-security-verified class="font-semibold {{ $user->hasVerifiedEmail()?'text-green-700':'text-amber-800' }}">{{ $user->hasVerifiedEmail()?'Verificado':'Sin verificar' }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Google</dt><dd data-account-security-google>{{ $user->google_id?'Vinculado':'Sin vincular' }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Contraseña propia</dt><dd data-account-security-password>{{ $user->hasOwnPassword()?'Establecida':'Pendiente de establecer o confirmar' }}</dd></div>
    </dl>
    <p data-offline-security-note hidden class="text-sm text-gray-700">Último estado conocido. Verificar el correo, modificar credenciales y administrar Google requiere Internet.</p>
    <a data-online-security-link hidden href="{{ route('profile.edit') }}#seguridad" class="inline-block text-sm font-semibold text-red-800 underline py-3">Abrir las acciones de seguridad con conexión</a>
    @unless($offlineTemplate ?? false)
    <div data-online-sensitive class="space-y-6">
        @if($errors->any())<div role="alert" class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl space-y-1">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if(session('status')==='verification-link-sent')<p role="status" class="text-green-800">Enviamos un nuevo enlace de verificación a tu correo actual.</p>@endif
        @unless($user->hasVerifiedEmail())
        <div class="space-y-3">
            <p class="text-sm text-gray-700">Sin un correo verificado podrías perder el acceso si olvidas la contraseña. Puedes corregirlo sin recibir mensajes en tu dirección anterior, confirmando primero tu identidad.</p>
            <div class="flex flex-wrap items-center gap-3"><a href="{{ route('verification.notice') }}" class="text-red-800 underline font-semibold py-3">Verificar correo electrónico</a>
                <form action="{{ route('verification.send') }}" method="POST">@csrf<button class="px-4 py-3 bg-red-700 text-white font-semibold rounded-xl" type="submit">Enviar o reenviar verificación</button></form>
            </div>
        </div>
        @endunless
        @if($user->google_id)
        <form action="{{ route('account.google.reauth') }}" method="POST" class="space-y-2">@csrf
            <p class="text-sm text-gray-700">Puedes confirmar tu identidad con la cuenta de Google vinculada, sin conocer la contraseña aleatoria original.</p>
            <button type="submit" class="px-4 py-3 border-2 border-red-700 text-red-800 font-semibold rounded-xl">Confirmar identidad con Google</button>
        </form>
        @endif
        <form action="{{ route('account.email.request') }}" method="POST" class="border-t border-red-100 pt-5 space-y-4">@csrf
            <h3 class="font-bold text-gray-800">Cambiar correo electrónico</h3>
            <p class="text-sm text-gray-700">La dirección actual se conserva hasta que confirmes la nueva. El enlace vence en una hora y sustituye solicitudes anteriores.</p>
            @if($user->pending_email)<p class="text-sm text-amber-900">Confirmación pendiente para {{ $user->pending_email }}.</p>@endif
            <div><label for="new-email" class="block text-sm font-semibold text-gray-700 mb-2">Nuevo correo</label><input id="new-email" name="new_email" type="email" required maxlength="255" autocomplete="email" value="{{ old('new_email') }}" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500"></div>
            @include('profile.security-password',['context'=>'email'])
            <button type="submit" class="px-4 py-3 bg-red-700 text-white font-semibold rounded-xl">Enviar confirmación al nuevo correo</button>
        </form>
        @if($proposal=session('account.google_proposal'))
        <form action="{{ route('account.google.confirm') }}" method="POST" class="border-t border-red-100 pt-5 space-y-4">@csrf
            <h3 class="font-bold text-gray-800">Confirmar vinculación con Google</h3>
            <p class="text-sm text-gray-700 break-words">Google confirmó la dirección {{ $proposal['email'] }}. Se vinculará a tu misma cuenta y tus registros no se trasladarán.</p>
            <input type="hidden" name="nonce" value="{{ $proposal['nonce'] }}">
            @if(\App\Models\User::normalizeEmail($user->email)!==$proposal['email'])
                <label class="flex items-start gap-3 text-sm text-gray-800"><input type="checkbox" name="replace_email" value="1" class="mt-1 h-5 w-5 shrink-0"> Usar este correo verificado de Google como correo principal. Sustituirá mi dirección actual.</label>
            @endif
            <button type="submit" class="px-4 py-3 bg-red-700 text-white font-semibold rounded-xl">Confirmar vínculo con esta cuenta</button>
        </form>
        @elseif(!$user->google_id)
        <form action="{{ route('account.google.link') }}" method="POST" class="border-t border-red-100 pt-5 space-y-4">@csrf
            <h3 class="font-bold text-gray-800">Vincular con Google</h3>
            <p class="text-sm text-gray-700">Confirma tu identidad y selecciona tu cuenta de Google. Revisarás el vínculo antes de guardarlo.</p>
            @include('profile.security-password',['context'=>'google'])
            <button type="submit" class="px-4 py-3 border-2 border-red-700 text-red-800 font-semibold rounded-xl">Vincular con Google</button>
        </form>
        @else
        <form action="{{ route('account.google.unlink') }}" method="POST" class="border-t border-red-100 pt-5 space-y-4">@csrf @method('DELETE')
            <h3 class="font-bold text-gray-800">Desvincular Google</h3>
            <p class="text-sm text-gray-700">Necesitas correo verificado y contraseña propia para conservar otro acceso y la recuperación de tu cuenta.</p>
            @include('profile.security-password',['context'=>'unlink'])
            <button type="submit" class="px-4 py-3 border-2 border-red-700 text-red-800 font-semibold rounded-xl" @disabled(!$user->hasOwnPassword()||!$user->hasVerifiedEmail())>Desvincular Google</button>
        </form>
        @endif
    </div>
    @endunless
</section>

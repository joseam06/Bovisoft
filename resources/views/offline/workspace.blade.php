@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('content')
<div id="workspace-placeholder" class="glass-effect p-6 rounded-2xl shadow-xl">
    <h1 class="text-3xl font-bold text-gray-900">BOVISOFT</h1>
    <p class="mt-3 text-gray-700">Cargando los datos guardados de tu cuenta…</p>
    <a href="/login" class="inline-block mt-4 text-red-800 font-semibold underline">Iniciar sesión con Internet</a>
</div>
@endsection

@if ($errors->any())
    <div class="rounded-lg border border-red-400/40 bg-red-500/10 p-3 text-sm text-red-100">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

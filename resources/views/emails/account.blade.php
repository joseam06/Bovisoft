<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#fef2f2;font-family:Arial,sans-serif;color:#1f2937;padding:24px">
<table role="presentation" style="max-width:600px;width:100%;margin:auto;background:white;border-radius:16px;border:1px solid #fecaca" cellpadding="24"><tr><td style="background:#991b1b;border-radius:16px 16px 0 0;color:white">
<img src="{{ rtrim(config('app.url'),'/') }}/images/logowhite.png" alt="BoviSoft" width="48" height="48"><strong style="font-size:24px"> BoviSoft</strong>
</td></tr><tr><td><h1 style="font-size:22px;color:#991b1b">{{ $title }}</h1>
@foreach($lines as $line)<p style="line-height:1.6">{{ $line }}</p>@endforeach
@if($action && $url)<p style="margin:28px 0"><a href="{{ $url }}" style="background:#b91c1c;color:white;text-decoration:none;padding:14px 20px;border-radius:10px;display:inline-block">{{ $action }}</a></p>
<p style="font-size:12px;word-break:break-all">Si el botón no abre, copia este enlace en tu navegador:<br>{{ $url }}</p>@endif
<p style="color:#6b7280;font-size:13px">BoviSoft nunca te pedirá enviar tu contraseña por correo. Tus fincas y animales siguen asociados a tu misma cuenta.</p>
</td></tr></table></body></html>

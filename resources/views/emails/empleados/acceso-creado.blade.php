<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso creado</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f7fb; margin:0; padding:24px;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;">
        <div style="padding:18px 20px; background:#0f172a; color:#ffffff;">
            <div style="font-weight:700; font-size:16px;">MR-Lana ERP</div>
            <div style="opacity:.85; font-size:13px;">Acceso al sistema</div>
        </div>

        <div style="padding:20px;">
            <p style="margin:0 0 12px; color:#111827;">
                Hola <b>{{ $user->name }}</b>,
            </p>

            <p style="margin:0 0 14px; color:#374151;">
                Tu acceso al sistema fue creado. Usa estas credenciales:
            </p>

            <div style="border:1px solid #e5e7eb; border-radius:12px; padding:14px; background:#f9fafb;">
                <div style="margin-bottom:8px; color:#111827;">
                    <b>Usuario (email):</b> {{ $user->email }}
                </div>
                <div style="margin-bottom:8px; color:#111827;">
                    <b>Contraseña temporal:</b> {{ $plainPassword }}
                </div>
                <div style="color:#111827;">
                    <b>Rol:</b> {{ $user->rol ?? '—' }}
                </div>
            </div>

            <p style="margin:14px 0 0; color:#6b7280; font-size:13px;">
                Recomendación: cambia tu contraseña al iniciar sesión.
            </p>
        </div>

        <div style="padding:14px 20px; background:#f3f4f6; color:#6b7280; font-size:12px;">
            Si tú no solicitaste este acceso, reporta el incidente a Sistemas.
        </div>
    </div>
</body>
</html>

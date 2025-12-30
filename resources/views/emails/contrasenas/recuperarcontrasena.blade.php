<!doctype html>
<html lang="es" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restablecer contraseña</title>
</head>

<body style="margin:0; padding:0; background:#F2F2F2; font-family:Calibri, Arial, sans-serif;">
  <div style="height:18px; line-height:18px;">&nbsp;</div>

  <div align="center">
    <table role="presentation" width="623" cellspacing="0" cellpadding="0"
           style="width:623px; border-collapse:collapse; background:none;">

        <!-- HEADER -->
        <tr>
            <td style="text-align:center; padding:14px;">
                @if(!empty($cidHeader))
                <img
                    src="{{ $cidHeader }}"
                    width="188" height="71"
                    alt="MR-Lana"
                    style="display:inline-block; border:0; outline:none; text-decoration:none;">
                @endif
            </td>
        </tr>

      <!-- CARD -->
      <tr>
        <td style="background:#FFFFFF; padding:18px 16px;">

          <p style="margin:0; font-size:16pt; color:#000;">
            ¡Hola{{ !empty($user?->name) ? ' ' . e($user->name) : '' }}!
          </p>

          <div style="height:12px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Estás recibiendo este correo porque recibimos una solicitud para restablecer la contraseña de tu cuenta.
          </p>

          <div style="height:16px;">&nbsp;</div>

          <div style="text-align:center; padding:10px 0;">
            <a href="{{ $url }}"
               style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; font-weight:700;
                      padding:12px 18px; border-radius:10px; font-family:Arial, sans-serif;">
              Restablecer contraseña
            </a>
          </div>

          <div style="height:16px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Este enlace para restablecer contraseña expirará en <strong>{{ (int) $expireMinutes }}</strong> minutos.
          </p>

          <div style="height:14px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Si tú no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna acción.
          </p>

          <div style="height:16px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Si sospechas actividad no autorizada, contacta a Sistemas de inmediato.
          </p>

          <div style="height:18px;">&nbsp;</div>

          <p style="margin:0; color:#000;">
            Saludos,<br>
            <strong>MrLana</strong>
          </p>

          <div style="height:18px;">&nbsp;</div>

          <hr style="border:0; border-top:1px solid #e5e7eb; margin:0;">

          <div style="height:14px;">&nbsp;</div>

          <p style="margin:0; font-size:12px; color:#6b7280;">
            Si tienes problemas al hacer clic en el botón <strong>"Restablecer contraseña"</strong>, copia y pega la siguiente URL en tu navegador:
          </p>

          <div style="height:10px;">&nbsp;</div>

          <p style="margin:0; font-size:12px; color:#111827; word-break:break-all;">
            <a href="{{ $url }}" style="color:#0070C0; text-decoration:underline;">
              {{ $url }}
            </a>
          </p>

        </td>
      </tr>

      <!-- CIERRE -->
      <tr>
        <td style="background:#FFFFFF; padding:18px 16px;">
          <p style="margin:0; color:#000;">&nbsp;</p>
        </td>
      </tr>
    </table>
  </div>

  <div style="height:18px;">&nbsp;</div>

  <!-- FIRMA -->
  <div align="center">
    <table role="presentation" width="623" cellspacing="0" cellpadding="0">
        <tr>
            <td style="text-align:center;">
                @if(!empty($cidFirma))
                <img
                    src="{{ $cidFirma }}"
                    width="588" height="170"
                    alt="Firma"
                    style="display:inline-block; border:0; outline:none; text-decoration:none;">
                @endif
            </td>
        </tr>
    </table>
  </div>

  <div style="height:14px;">&nbsp;</div>
  <div align="center">
    <p style="font-size:12px; color:#6b7280; margin:0;">
      Este correo fue generado automáticamente.
    </p>
  </div>

  <div style="height:24px;">&nbsp;</div>
</body>
</html>

<!doctype html>
<html lang="es" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <title>Contraseña actualizada</title>
</head>

<body style="margin:0; padding:0; background:#F2F2F2; font-family:Calibri, Arial, sans-serif;">

  <!-- ESPACIADOR -->
  <div style="height:18px; line-height:18px;">&nbsp;</div>

  <!-- CONTENEDOR -->
  <div align="center">
    <table role="presentation" width="623" cellspacing="0" cellpadding="0"
           style="width:623px; border-collapse:collapse; background:none;">

      <!-- LOGO -->
      <tr>
        <td style="text-align:center; padding:14px;">
          @if(!empty($cidHeader))
            <img src="{{ $cidHeader }}" width="188" height="71"
                 alt="MR-Lana"
                 style="display:inline-block; border:0;">
          @endif
        </td>
      </tr>

      <!-- CARD -->
      <tr>
        <td style="background:#FFFFFF; padding:18px 16px;">

          <p style="margin:0; font-size:16pt; color:#000;">
            Hola <strong>{{ $user->name }}</strong>
          </p>

          <div style="height:12px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Tu contraseña de acceso a la plataforma
            <strong style="color:#0070C0;">ERP</strong>
            fue actualizada correctamente.
          </p>

          <div style="height:16px;">&nbsp;</div>

          <p style="margin:0;">
            <a href="https://erp.mr-lana.com/"
               style="color:#0070C0; text-decoration:underline;">
              Acceder a MR-Lana ERP
            </a>
          </p>

          <div style="height:18px;">&nbsp;</div>

          <p style="margin:0; color:#000;">
            Si tú no realizaste este cambio, comunícate de inmediato con el área de Sistemas.
          </p>

        </td>
      </tr>

      <!-- CIERRE -->
      <tr>
        <td style="background:#FFFFFF; padding:18px 16px;">
          <p style="margin:0; color:#000;">Saludos</p>
        </td>
      </tr>
    </table>
  </div>

  <!-- ESPACIADOR -->
  <div style="height:18px;">&nbsp;</div>

  <!-- FIRMA -->
  <div align="center">
    <table role="presentation" width="623" cellspacing="0" cellpadding="0">
      <tr>
        <td style="text-align:center;">
          @if(!empty($cidFirma))
            <img src="{{ $cidFirma }}" width="588" height="170"
                 alt="Firma"
                 style="display:inline-block; border:0;">
          @endif
        </td>
      </tr>
    </table>
  </div>

  <!-- FOOTER -->
  <div style="height:14px;">&nbsp;</div>
  <div align="center">
    <p style="font-size:12px; color:#6b7280;">
      Este correo fue generado automáticamente.
    </p>
  </div>

  <div style="height:24px;">&nbsp;</div>

</body>
</html>

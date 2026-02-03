<!doctype html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso creado</title>
</head>

<body bgcolor="#F2F2F2" lang="ES-MX" link="#467886" vlink="#96607D"
      style="margin:0; padding:0; background:#F2F2F2; word-wrap:break-word; font-family:Calibri, Arial, sans-serif;">

  <!-- ESPACIADOR SUPERIOR -->
  <div style="height:18px; line-height:18px;">&nbsp;</div>

  <!-- WRAPPER CENTRADO -->
  <div align="center">
    <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="623"
        style="width:623px; border-collapse:collapse; border:none;">
      <!-- LOGO -->
      <tr>
        <td valign="top" style="padding:14.4pt 5.75pt; text-align:center;">
            @if(!empty($cidHeader))
                <img src="{{ $cidHeader }}" width="188" height="71" style="display:inline-block;border:0;" alt="MR-Lana">
            @endif
        </td>
      </tr>

      <!-- CARD PRINCIPAL -->
      <tr>
        <td valign="top"
            style="background:#ffffff; padding:18pt 14.4pt;">

          <p style="margin:0; font-size:16pt; font-family:Calibri, Arial, sans-serif; color:#000;">
            Bienvenido/a
            <span style="font-weight:700;">
              {{ $user->name }}
            </span>
          </p>

          <div style="height:12px; line-height:12px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Tus credenciales de acceso a la plataforma
            <b><span style="color:#0070C0;">ERP</span></b>
            son las siguientes:
          </p>

          <div style="height:12px; line-height:12px;">&nbsp;</div>

          <!-- BLOQUE CREDENCIALES -->
          <div align="center">
            <table role="presentation" border="0" cellspacing="0" cellpadding="0"
                   style="background:#E8E8E8; border-collapse:collapse; border:none;">
              <tr>
                <td valign="top" style="padding:8px 12px;">
                  <p style="margin:0; text-align:right; font-family:Consolas,'Courier New',monospace; color:#000;">
                    Usuario:
                  </p>
                </td>
                <td valign="top" style="padding:8px 12px;">
                  <p style="margin:0; font-family:Consolas,'Courier New',monospace; color:#000;">
                    {{ $user->email }}
                  </p>
                </td>
              </tr>

              <tr>
                <td valign="top" style="padding:8px 12px;">
                  <p style="margin:0; text-align:right; font-family:Consolas,'Courier New',monospace; color:#000;">
                    Contraseña:
                  </p>
                </td>
                <td valign="top" style="padding:8px 12px;">
                  <p style="margin:0; font-family:Consolas,'Courier New',monospace; color:#000;">
                    {{ $plainPassword }}
                  </p>
                </td>
              </tr>

            </table>
          </div>

          <div style="height:12px; line-height:12px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt; color:#000;">
            Para acceder ingresa con estos datos a:
          </p>

          <div style="height:6px; line-height:6px;">&nbsp;</div>

          <p style="margin:0; font-size:12pt;">
            <b>
              <a href="https://erp.mr-lana.com/"
                 style="color:#0070C0; text-decoration:underline;">
                https://erp.mr-lana.com/
              </a>
            </b>
          </p>

          <div style="height:14px; line-height:14px;">&nbsp;</div>

          <p style="margin:0; color:#111827; font-size:13px;">
            Recomendación: cambia tu contraseña al iniciar sesión.
          </p>

          <div style="height:18px; line-height:18px;">&nbsp;</div>

          <p style="margin:0; color:#000;">
            Si necesita ayuda o tiene alguna pregunta, no dude en generar un ticket en:
          </p>

          <div style="height:10px; line-height:10px;">&nbsp;</div>

          <p style="margin:0;">
            <a href="https://soporte.mr-lana.com/" title="https://soporte.mr-lana.com/"
               style="color:#00B050; text-decoration:none;">
              <strong style="color:#00B050; font-family:Helvetica, Arial, sans-serif; font-size:11pt;">
                https://soporte.mr-lana.com/
              </strong>
            </a>
          </p>

        </td>
      </tr>

      <!-- CIERRE -->
      <tr>
        <td valign="top" style="background:#ffffff; padding:18pt 14.4pt;">
          <p style="margin:0; font-size:12pt; color:#000;">Saludos</p>
        </td>
      </tr>
    </table>
  </div>

  <!-- ESPACIADOR -->
  <div style="height:18px; line-height:18px;">&nbsp;</div>

  <!-- FIRMA / BANNER
  <div align="center">
    <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="623"
           style="width:623px; border-collapse:collapse; border:none;">
      <tr>
        <td style="text-align:center;">
            @if(!empty($cidFirma))
                <img src="{{ $cidFirma }}" width="588" height="170" style="display:inline-block;border:0;">
            @endif
        </td>
      </tr>
    </table>
  </div>
  -->

  <!-- FOOTER LEGAL -->
  <div style="height:14px; line-height:14px;">&nbsp;</div>
  <div align="center">
    <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="623"
           style="width:623px; border-collapse:collapse; border:none;">
      <tr>
        <td style="color:#6b7280; font-size:12px; font-family:Arial, sans-serif; padding:0 6px; text-align:center;">
          Si tú no solicitaste este acceso, reporta el incidente a Sistemas.
        </td>
      </tr>
    </table>
  </div>

  <div style="height:24px; line-height:24px;">&nbsp;</div>

</body>
</html>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobaciones listas</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(17,24,39,.08);">
                    <tr>
                        <td style="padding:22px 24px;background:linear-gradient(135deg,#0f172a,#111827);color:#fff;">
                            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;font-weight:700;">
                                Mr Lana · ERP
                            </div>
                            <div style="margin-top:6px;font-size:22px;font-weight:900;line-height:1.2;">
                                Comprobaciones listas para revisión
                            </div>
                            <div style="margin-top:8px;font-size:13px;opacity:.9;">
                                Enviado por: <strong>{{ $senderName }}</strong>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 24px;">
                            <div style="font-size:14px;line-height:1.6;color:#111827;">
                                Se reporta que ya se cargaron comprobaciones y están listas para revisión.
                            </div>

                            <div style="margin-top:14px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:14px;background:#f9fafb;">
                                <div style="font-size:12px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.06em;">
                                    Detalle
                                </div>

                                <table width="100%" cellspacing="0" cellpadding="0" style="margin-top:10px;font-size:14px;color:#111827;">
                                    <tr>
                                        <td style="padding:6px 0;color:#6b7280;width:140px;">Folio</td>
                                        <td style="padding:6px 0;font-weight:800;">{{ $req->folio ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6b7280;">Requisición</td>
                                        <td style="padding:6px 0;font-weight:800;">#{{ $req->id }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6b7280;">Monto total</td>
                                        <td style="padding:6px 0;font-weight:800;">
                                            ${{ number_format((float)($req->monto_total ?? 0), 2) }}
                                        </td>
                                    </tr>
                                </table>

                                <div style="margin-top:12px;font-size:13px;color:#111827;white-space:pre-line;">
                                    {{ $messageText }}
                                </div>
                            </div>

                            <div style="margin-top:18px;text-align:center;">
                                <a href="{{ $erpUrl }}"
                                   style="display:inline-block;background:#111827;color:#fff;text-decoration:none;
                                          padding:12px 18px;border-radius:12px;font-weight:900;font-size:14px;">
                                    Abrir ERP
                                </a>
                            </div>

                            <div style="margin-top:18px;font-size:12px;color:#6b7280;line-height:1.6;">
                                Si necesitas ayuda o tienes alguna pregunta, genera un ticket en:
                                <a href="{{ $supportUrl }}" style="color:#2563eb;text-decoration:none;font-weight:800;">
                                    {{ $supportUrl }}
                                </a>
                            </div>

                            <div style="margin-top:16px;font-size:12px;color:#9ca3af;">
                                Este correo fue generado automáticamente. Si no reconoces esta acción, contacta a soporte.
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="max-width:640px;margin-top:10px;font-size:11px;color:#9ca3af;text-align:center;">
                    © {{ date('Y') }} Mr Lana. Todos los derechos reservados.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

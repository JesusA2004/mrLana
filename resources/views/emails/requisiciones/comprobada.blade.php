<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisición comprobada</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:#ecfdf5; border-bottom:1px solid #d1fae5;">
                            <h1 style="margin:0; font-size:22px; line-height:1.3; color:#065f46;">
                                Requisición comprobada correctamente
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Hola,
                            </p>

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Te informamos que tu requisición ya fue <strong>comprobada de forma exitosa</strong>.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0; border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold; width:180px;">Folio</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">{{ $requisicion->folio ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Monto total</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">
                                        ${{ number_format((float)($requisicion->monto_total ?? 0), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Estatus final</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">COMPROBACION_ACEPTADA</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Con esto, el ciclo de esta requisición queda concluido.
                            </p>

                            <p style="margin:24px 0 0 0; font-size:13px; line-height:1.6; color:#64748b;">
                                Este es un mensaje automático del sistema.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

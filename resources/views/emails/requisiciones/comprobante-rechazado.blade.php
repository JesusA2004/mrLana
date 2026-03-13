<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante rechazado</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:#fff1f2; border-bottom:1px solid #fecdd3;">
                            <h1 style="margin:0; font-size:22px; line-height:1.3; color:#9f1239;">
                                Comprobante rechazado
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Hola,
                            </p>

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Uno de los comprobantes cargados para tu requisición fue <strong>rechazado</strong>.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0; border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold; width:180px;">Folio</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">{{ $requisicion->folio ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Tipo</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">{{ $comprobante->tipo_doc ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Fecha</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">{{ $comprobante->fecha_emision ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Monto</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">
                                        ${{ number_format((float)($comprobante->monto ?? 0), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:bold;">Motivo</td>
                                    <td style="padding:10px 12px; border:1px solid #e2e8f0;">
                                        {{ $comprobante->comentario_revision ?? 'Sin motivo especificado' }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Revisa el motivo y carga el comprobante correcto si aplica.
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

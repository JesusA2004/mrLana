<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajuste aplicado</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <h2 style="margin-bottom: 8px;">Tu ajuste fue aplicado</h2>

    <p>
        El ajuste solicitado para la requisición
        <strong>{{ $requisicion->folio ?? ('#' . $requisicion->id) }}</strong>
        ya fue aplicado correctamente.
    </p>

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse;">
        <tr>
            <td><strong>Tipo:</strong></td>
            <td>{{ $ajuste->tipo }}</td>
        </tr>
        <tr>
            <td><strong>Monto del ajuste:</strong></td>
            <td>${{ number_format((float) $ajuste->monto, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Monto anterior:</strong></td>
            <td>${{ number_format((float) $ajuste->monto_anterior, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Monto nuevo:</strong></td>
            <td>${{ number_format((float) $ajuste->monto_nuevo, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Motivo:</strong></td>
            <td>{{ $ajuste->motivo ?? '—' }}</td>
        </tr>
    </table>

    <p style="margin-top: 18px;">
        Ya puedes revisar tu requisición actualizada en el sistema.
    </p>
</body>
</html>

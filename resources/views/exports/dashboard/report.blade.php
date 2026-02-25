<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard {{ $role }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        .h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #555; margin: 0 0 14px 0; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        .grid { display: table; width: 100%; table-layout: fixed; }
        .col { display: table-cell; vertical-align: top; padding-right: 8px; }
    </style>
</head>
<body>
    <div class="h1">{{ $data['headline'] ?? 'Dashboard' }} — {{ $role }}</div>
    <p class="muted">Generado: {{ $generatedAt }}</p>

    <div class="card">
        <strong>KPIs</strong>
        <table style="margin-top:8px;">
            <thead>
                <tr><th>KPI</th><th>Valor</th></tr>
            </thead>
            <tbody>
                @foreach(($data['kpis'] ?? []) as $k)
                    <tr>
                        <td>{{ $k['label'] ?? '' }}</td>
                        <td>{{ $k['value'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid">
        <div class="col">
            <div class="card">
                <strong>Actividad (14 días)</strong>
                <table style="margin-top:8px;">
                    <thead><tr><th>Fecha</th><th>Cantidad</th></tr></thead>
                    <tbody>
                    @foreach(($data['activityDaily'] ?? []) as $p)
                        <tr>
                            <td>{{ $p['date'] ?? '' }}</td>
                            <td>{{ $p['value'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col">
            <div class="card">
                <strong>Montos (14 días)</strong>
                <table style="margin-top:8px;">
                    <thead><tr><th>Fecha</th><th>Monto</th></tr></thead>
                    <tbody>
                    @foreach(($data['amountsDaily'] ?? []) as $p)
                        <tr>
                            <td>{{ $p['date'] ?? '' }}</td>
                            <td>{{ number_format((float)($p['value'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(($role ?? '') === 'ADMIN')
        <div class="card">
            <strong>Distribución por estatus (30 días)</strong>
            <table style="margin-top:8px;">
                <thead><tr><th>Estatus</th><th>Cantidad</th></tr></thead>
                <tbody>
                @foreach(($data['statusMix'] ?? []) as $k => $v)
                    <tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card">
            <strong>Comprobantes por tipo (mes)</strong>
            <table style="margin-top:8px;">
                <thead><tr><th>Tipo</th><th>Cantidad</th></tr></thead>
                <tbody>
                @foreach(($data['comprobantesMix'] ?? []) as $k => $v)
                    <tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $meta['title'] ?? 'Reporte de Áreas' }}</title>
  <style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #111827; }
    .muted { color: #6b7280; }
    .header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 10px; }
    .title { font-size: 18px; font-weight: 700; margin:0; }
    .subtitle { margin:2px 0 0 0; }
    .meta { text-align:right; font-size: 11px; }
    .pill { display:inline-block; border:1px solid #e5e7eb; padding:4px 8px; border-radius: 999px; margin: 2px 4px 0 0; }
    table { width:100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border:1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    th { background:#f3f4f6; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: .02em; }
    .right { text-align:right; }
    .badge { font-weight:700; padding:2px 8px; border-radius: 999px; display:inline-block; }
    .ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
    .bad { background:#fff1f2; color:#9f1239; border:1px solid #fecdd3; }
  </style>
</head>
<body>

  <div class="header">
    <div>
      <p class="title">{{ $meta['title'] ?? 'Reporte de Áreas' }}</p>
      <p class="subtitle muted">{{ $meta['subtitle'] ?? 'Exportación con filtros actuales' }}</p>

      @if(!empty($filters) && is_array($filters))
        <div style="margin-top:6px;">
          @foreach($filters as $k => $v)
            @php $vv = is_array($v) ? implode(', ', $v) : (string) $v; @endphp
            @if(trim($vv) !== '')
              <span class="pill"><b>{{ $k }}:</b> <span class="muted">{{ $vv }}</span></span>
            @endif
          @endforeach
        </div>
      @endif
    </div>

    <div class="meta">
      <div><b>Generado:</b> {{ $meta['generated_at'] ?? now()->format('Y-m-d H:i') }}</div>
      @if(!empty($meta['generated_by']))
        <div><b>Por:</b> {{ $meta['generated_by'] }}</div>
      @endif
      <div><b>Total:</b> {{ $totals['total'] ?? count($rows ?? []) }}</div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:8%">ID</th>
        <th style="width:35%">Corporativo</th>
        <th>Área</th>
        <th style="width:12%">Estatus</th>
        <th style="width:14%">Creado</th>
        <th style="width:14%">Actualizado</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $r)
        <tr>
          <td>{{ $r['id'] ?? '—' }}</td>
          <td>{{ $r['corporativo'] ?? '—' }}</td>
          <td>{{ $r['nombre'] ?? '—' }}</td>
          <td class="right">
            @if(!empty($r['activo']))
              <span class="badge ok">Activo</span>
            @else
              <span class="badge bad">Baja</span>
            @endif
          </td>
          <td>{{ $r['created_at'] ?? '—' }}</td>
          <td>{{ $r['updated_at'] ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="muted">Sin resultados con los filtros actuales.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>

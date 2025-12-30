<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $meta['title'] ?? 'Reporte de Requisiciones' }}</title>
  <style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #111827; }
    .muted { color:#6b7280; }
    .header { display:flex; justify-content:space-between; align-items:flex-end; gap: 12px; margin-bottom: 10px; }
    .title { font-size: 18px; font-weight: 800; margin:0; }
    .subtitle { margin:2px 0 0 0; }
    .meta { text-align:right; font-size: 10.5px; min-width: 220px; }
    .pill { display:inline-block; border:1px solid #e5e7eb; padding:4px 8px; border-radius: 999px; margin: 2px 4px 0 0; }
    table { width:100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border:1px solid #e5e7eb; padding: 6px 7px; vertical-align: top; }
    th { background:#f3f4f6; font-weight: 800; text-transform: uppercase; font-size: 10px; letter-spacing: .02em; }
    .nowrap { white-space: nowrap; }
    .right { text-align:right; }
    .badge { font-weight:800; padding:2px 8px; border-radius: 999px; display:inline-block; font-size: 10px; }
    .b1 { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }   /* BORRADOR/CAPTURADA */
    .b2 { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }   /* POR_COMPROBAR */
    .b3 { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }   /* ACEPTADA/PAGADA/COMPROBADA */
    .b4 { background:#fff1f2; color:#9f1239; border:1px solid #fecdd3; }   /* RECHAZADA */
  </style>
</head>
<body>

  <div class="header">
    <div style="flex:1">
      <p class="title">{{ $meta['title'] ?? 'Reporte de Requisiciones' }}</p>
      <p class="subtitle muted">{{ $meta['subtitle'] ?? 'Exportación con filtros actuales' }}</p>

      @if(!empty($filters) && is_array($filters))
        <div style="margin-top:6px;">
          @foreach($filters as $k => $v)
            @php $vv = is_array($v) ? implode(', ', $v) : (string) $v; @endphp
            @if(trim((string)$vv) !== '')
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
        <th class="nowrap">Folio</th>
        <th class="nowrap">Tipo</th>
        <th class="nowrap">Estatus</th>
        <th>Comprador</th>
        <th>Corporativo</th>
        <th>Sucursal</th>
        <th>Solicitante</th>
        <th>Proveedor</th>
        <th>Concepto</th>
        <th class="right nowrap">Subtotal</th>
        <th class="right nowrap">IVA</th>
        <th class="right nowrap">Total</th>
        <th class="nowrap">Captura</th>
        <th class="nowrap">Pago</th>
        <th>Creada por</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $r)
        @php
          $st = strtoupper((string)($r['status'] ?? ''));
          $cls = 'b1';
          if (in_array($st, ['POR_COMPROBAR'], true)) $cls = 'b2';
          if (in_array($st, ['ACEPTADA','PAGADA','COMPROBADA'], true)) $cls = 'b3';
          if (in_array($st, ['RECHAZADA'], true)) $cls = 'b4';
        @endphp
        <tr>
          <td class="nowrap">{{ $r['folio'] ?? '—' }}</td>
          <td class="nowrap">{{ $r['tipo'] ?? '—' }}</td>
          <td class="nowrap"><span class="badge {{ $cls }}">{{ $r['status'] ?? '—' }}</span></td>
          <td>{{ $r['comprador'] ?? '—' }}</td>
          <td>{{ $r['corporativo'] ?? '—' }}</td>
          <td>{{ $r['sucursal'] ?? '—' }}</td>
          <td>{{ $r['solicitante'] ?? '—' }}</td>
          <td>{{ $r['proveedor'] ?? '—' }}</td>
          <td>{{ $r['concepto'] ?? '—' }}</td>
          <td class="right nowrap">{{ number_format((float)($r['subtotal'] ?? 0), 2, '.', ',') }}</td>
          <td class="right nowrap">{{ number_format((float)($r['iva'] ?? 0), 2, '.', ',') }}</td>
          <td class="right nowrap">{{ number_format((float)($r['total'] ?? 0), 2, '.', ',') }}</td>
          <td class="nowrap">{{ $r['fecha_captura'] ?? '—' }}</td>
          <td class="nowrap">{{ $r['fecha_pago'] ?? '—' }}</td>
          <td>{{ $r['created_by'] ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="15" class="muted">Sin resultados con los filtros actuales.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>

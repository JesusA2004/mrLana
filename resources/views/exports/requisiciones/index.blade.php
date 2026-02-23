<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $meta['title'] ?? 'Reporte de Requisiciones' }}</title>

  <style>
    /* DomPDF friendly */
    @page { margin: 18px 18px 44px 18px; } /* top right bottom left */

    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 10.5px; color: #111827; }

    .muted { color: #6b7280; }
    .strong { font-weight: 800; }
    .small { font-size: 9.5px; }

    /* Header / Footer fixed for every page */
    .header {
      position: fixed;
      top: -6px;
      left: 0;
      right: 0;
      height: 70px;
    }
    .footer {
      position: fixed;
      bottom: -26px;
      left: 0;
      right: 0;
      height: 28px;
      border-top: 1px solid #e5e7eb;
      padding-top: 6px;
      font-size: 9.5px;
      color: #6b7280;
    }

    /* Header block uses tables (more reliable than flex in DomPDF) */
    .hwrap { width: 100%; border-collapse: collapse; }
    .hwrap td { vertical-align: top; padding: 0; }

    .title { font-size: 16px; font-weight: 900; margin: 0; }
    .subtitle { margin: 2px 0 0 0; }

    .metaBox {
      text-align: right;
      font-size: 9.8px;
      line-height: 1.35;
      white-space: nowrap;
    }
    .metaLine b { color:#111827; }

    /* Filters pills */
    .filters { margin-top: 6px; }
    .pill {
      display: inline-block;
      border: 1px solid #e5e7eb;
      background: #f9fafb;
      padding: 3px 7px;
      border-radius: 999px;
      margin: 2px 6px 0 0;
      font-size: 9.6px;
      white-space: nowrap;
    }

    /* Content starts below header */
    .content { margin-top: 76px; }

    /* Table */
    table.report {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed; /* key to avoid “culero” stretching */
    }

    table.report thead th {
      background: #111827;
      color: #ffffff;
      font-weight: 900;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: .03em;
      padding: 7px 6px;
      border: 1px solid #111827;
    }

    table.report tbody td {
      border: 1px solid #e5e7eb;
      padding: 6px 6px;
      vertical-align: top;
    }

    table.report tbody tr:nth-child(even) td { background: #f9fafb; }

    .nowrap { white-space: nowrap; }
    .right { text-align: right; }
    .center { text-align: center; }

    /* Text clamp-ish for DomPDF */
    .cut {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* Status badges */
    .badge {
      display: inline-block;
      font-weight: 900;
      font-size: 9px;
      padding: 2px 7px;
      border-radius: 999px;
      border: 1px solid transparent;
      white-space: nowrap;
    }
    .b-draft { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }     /* BORRADOR/CAPTURADA */
    .b-pend  { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }     /* POR_COMPROBAR */
    .b-ok    { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }     /* PAGO_AUTORIZADO/PAGADA/COMPROBACION_ACEPTADA */
    .b-bad   { background:#fff1f2; color:#9f1239; border-color:#fecdd3; }     /* RECHAZADAS */
    .b-dead  { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }     /* ELIMINADA/otros */

    /* Column widths (landscape) - ajusta si agregas/quitas */
    th.c-folio, td.c-folio { width: 15%; }
    th.c-tipo, td.c-tipo { width: 6%; }
    th.c-status, td.c-status { width: 10%; }
    th.c-compr, td.c-compr { width: 9%; }
    th.c-corp, td.c-corp { width: 9%; }
    th.c-suc, td.c-suc { width: 9%; }
    th.c-sol, td.c-sol { width: 10%; }
    th.c-prov, td.c-prov { width: 8%; }
    th.c-conc, td.c-conc { width: 8%; }
    th.c-sub, td.c-sub { width: 6.5%; }
    th.c-iva, td.c-iva { width: 5.5%; }
    th.c-tot, td.c-tot { width: 6.5%; }
    th.c-cap, td.c-cap { width: 7.5%; }
    th.c-pag, td.c-pag { width: 6%; }
    th.c-by, td.c-by { width: 7%; }
  </style>
</head>

<body>
  @php
    // Helpers “DomPDF-safe”
    $money = function($v) {
      $n = (float)($v ?? 0);
      return number_format($n, 2, '.', ',');
    };

    $statusClass = function(string $st) {
      $st = strtoupper(trim($st));
      if (in_array($st, ['BORRADOR','CAPTURADA'], true)) return 'b-draft';
      if (in_array($st, ['POR_COMPROBAR'], true)) return 'b-pend';
      if (in_array($st, ['PAGO_AUTORIZADO','PAGADA','COMPROBACION_ACEPTADA'], true)) return 'b-ok';
      if (in_array($st, ['PAGO_RECHAZADO','COMPROBACION_RECHAZADA'], true)) return 'b-bad';
      if (in_array($st, ['ELIMINADA'], true)) return 'b-dead';
      return 'b-dead';
    };

    // Totales (por si quieres mostrar arriba o abajo)
    $totalRows = (int)($totals['total'] ?? (is_array($rows ?? null) ? count($rows) : 0));
    $sumSubtotal = 0.0; $sumIva = 0.0; $sumTotal = 0.0;
    foreach(($rows ?? []) as $rr){
      $sumSubtotal += (float)($rr['subtotal'] ?? 0);
      $sumIva      += (float)($rr['iva'] ?? 0);
      $sumTotal    += (float)($rr['total'] ?? 0);
    }
  @endphp

  <!-- HEADER -->
  <div class="header">
    <table class="hwrap">
      <tr>
        <td style="width: 70%;">
          <div class="title">{{ $meta['title'] ?? 'Reporte de Requisiciones' }}</div>
          <div class="subtitle muted">{{ $meta['subtitle'] ?? 'Exportación con filtros actuales' }}</div>

          @if(!empty($filters) && is_array($filters))
            <div class="filters">
              @foreach($filters as $k => $v)
                @php $vv = is_array($v) ? implode(', ', $v) : (string)$v; @endphp
                @if(trim($vv) !== '')
                  <span class="pill"><span class="strong">{{ $k }}:</span> <span class="muted">{{ $vv }}</span></span>
                @endif
              @endforeach
            </div>
          @endif
        </td>

        <td style="width: 30%;" class="metaBox">
          <div class="metaLine"><b>Generado:</b> {{ $meta['generated_at'] ?? now()->format('Y-m-d H:i') }}</div>
          @if(!empty($meta['generated_by']))
            <div class="metaLine"><b>Por:</b> {{ $meta['generated_by'] }}</div>
          @endif
          <div class="metaLine"><b>Total registros:</b> {{ $totalRows }}</div>
          <div class="metaLine"><b>Subtotal:</b> {{ $money($sumSubtotal) }}</div>
          <div class="metaLine"><b>IVA:</b> {{ $money($sumIva) }}</div>
          <div class="metaLine"><b>Total:</b> {{ $money($sumTotal) }}</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <table style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:50%; text-align:left;">
          {{ $meta['footer_left'] ?? 'ERP MR-Lana' }}
        </td>
        <td style="width:50%; text-align:right;">
          Página <span class="pageNumber"></span> de <span class="totalPages"></span>
        </td>
      </tr>
    </table>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <table class="report">
      <thead>
        <tr>
          <th class="c-folio">Folio</th>
          <th class="c-tipo">Tipo</th>
          <th class="c-status">Estatus</th>
          <th class="c-compr">Comprador</th>
          <th class="c-corp">Corporativo</th>
          <th class="c-suc">Sucursal</th>
          <th class="c-sol">Solicitante</th>
          <th class="c-prov">Proveedor</th>
          <th class="c-conc">Concepto</th>
          <th class="c-sub right">Subtotal</th>
          <th class="c-iva right">IVA</th>
          <th class="c-tot right">Total</th>
          <th class="c-cap">Captura</th>
          <th class="c-pag">Pago</th>
          <th class="c-by">Creada por</th>
        </tr>
      </thead>

      <tbody>
        @forelse(($rows ?? []) as $r)
          @php
            $st = strtoupper((string)($r['status'] ?? ''));
            $cls = $statusClass($st);
          @endphp
          <tr>
            <td class="c-folio nowrap cut">{{ $r['folio'] ?? '—' }}</td>
            <td class="c-tipo nowrap cut">{{ $r['tipo'] ?? '—' }}</td>
            <td class="c-status nowrap">
              <span class="badge {{ $cls }}">{{ $r['status'] ?? '—' }}</span>
            </td>
            <td class="c-compr cut">{{ $r['comprador'] ?? '—' }}</td>
            <td class="c-corp cut">{{ $r['corporativo'] ?? '—' }}</td>
            <td class="c-suc cut">{{ $r['sucursal'] ?? '—' }}</td>
            <td class="c-sol cut">{{ $r['solicitante'] ?? '—' }}</td>
            <td class="c-prov cut">{{ $r['proveedor'] ?? '—' }}</td>
            <td class="c-conc cut">{{ $r['concepto'] ?? '—' }}</td>
            <td class="c-sub right nowrap">{{ $money($r['subtotal'] ?? 0) }}</td>
            <td class="c-iva right nowrap">{{ $money($r['iva'] ?? 0) }}</td>
            <td class="c-tot right nowrap strong">{{ $money($r['total'] ?? 0) }}</td>
            <td class="c-cap nowrap small">{{ $r['fecha_captura'] ?? '—' }}</td>
            <td class="c-pag nowrap small">{{ $r['fecha_pago'] ?? '—' }}</td>
            <td class="c-by cut">{{ $r['created_by'] ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="15" class="muted" style="padding:10px;">
              Sin resultados con los filtros actuales.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- DomPDF page numbers --}}
  <script type="text/php">
    if (isset($pdf)) {
      $pdf->page_text(760, 560, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(107,114,128));
    }
  </script>
</body>
</html>

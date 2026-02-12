<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $requisicion->folio ?? 'Requisición' }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
    .muted { color:#6B7280; }
    .h1 { font-size: 18px; font-weight: 800; margin: 0 0 6px; }
    .card { border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
    .row { display: table; width: 100%; }
    .col { display: table-cell; vertical-align: top; }
    .col-50 { width: 50%; }
    .k { font-size: 10px; letter-spacing: .08em; text-transform: uppercase; font-weight: 800; color:#6B7280; }
    .v { font-size: 12px; font-weight: 700; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #E5E7EB; padding: 8px 6px; }
    th { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color:#6B7280; text-align: left; }
    td.num { text-align: right; }
    .tot { text-align: right; font-weight: 800; }
  </style>
</head>
<body>

  <div class="card">
    <div class="h1">Requisición {{ $requisicion->folio ?? '' }}</div>
    <div class="muted">
      Estado: <strong>{{ $requisicion->status ?? '—' }}</strong> •
      Capturada: <strong>{{ optional($requisicion->created_at)->format('d M Y, h:i a') }}</strong> •
      Actualizada: <strong>{{ optional($requisicion->updated_at)->format('d M Y, h:i a') }}</strong>
    </div>
  </div>

  <div class="card">
    <div class="row">
      <div class="col col-50">
        <div class="k">Comprador</div>
        <div class="v">{{ $requisicion->comprador->nombre ?? '—' }}</div>
      </div>
      <div class="col col-50">
        <div class="k">Sucursal</div>
        <div class="v">{{ $requisicion->sucursal->nombre ?? '—' }}</div>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="row">
      <div class="col col-50">
        <div class="k">Solicitante</div>
        <div class="v">
          {{ $requisicion->solicitante
              ? trim(($requisicion->solicitante->nombre ?? '').' '.($requisicion->solicitante->apellido_paterno ?? '').' '.($requisicion->solicitante->apellido_materno ?? ''))
              : '—'
          }}
        </div>
      </div>
      <div class="col col-50">
        <div class="k">Proveedor</div>
        <div class="v">{{ $requisicion->proveedor->razon_social ?? ($requisicion->proveedor->nombre ?? '—') }}</div>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="row">
      <div class="col col-50">
        <div class="k">Concepto</div>
        <div class="v">{{ $requisicion->concepto->nombre ?? '—' }}</div>
      </div>
      <div class="col col-50">
        <div class="k">Fechas</div>
        <div class="v">
          Solicitud: {{ optional($requisicion->fecha_solicitud)->format('d M Y') ?? '—' }} |
          Autorización: {{ optional($requisicion->fecha_autorizacion)->format('d M Y') ?? '—' }} |
          Pago: {{ optional($requisicion->fecha_pago)->format('d M Y') ?? '—' }}
        </div>
      </div>
    </div>

    <div style="height:10px"></div>
    <div class="k">Observaciones</div>
    <div class="v" style="font-weight:600">{{ $requisicion->observaciones ?? '—' }}</div>
  </div>

  <div class="card">
    <div class="k" style="margin-bottom:6px">Items</div>

    <table>
      <thead>
        <tr>
          <th style="width:70px">Cant.</th>
          <th style="width:140px">Sucursal</th>
          <th>Descripción</th>
          <th style="width:110px; text-align:right">Importe</th>
          <th style="width:80px; text-align:right">IVA</th>
          <th style="width:110px; text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach(($requisicion->detalles ?? []) as $d)
          <tr>
            <td>{{ $d->cantidad ?? '—' }}</td>
            <td>{{ $d->sucursal->nombre ?? '—' }}</td>
            <td>{{ $d->descripcion ?? '—' }}</td>
            <td class="num">${{ number_format((float)($d->subtotal ?? 0), 2) }}</td>
            <td class="num">${{ number_format((float)($d->iva ?? 0), 2) }}</td>
            <td class="num"><strong>${{ number_format((float)($d->total ?? 0), 2) }}</strong></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div style="height:10px"></div>

    <div class="tot">
      Subtotal: ${{ number_format((float)($requisicion->monto_subtotal ?? 0), 2) }} <br>
      Total: ${{ number_format((float)($requisicion->monto_total ?? 0), 2) }}
    </div>
  </div>

</body>
</html>

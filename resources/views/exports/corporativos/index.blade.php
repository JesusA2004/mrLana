@extends('exports.layouts.base-report')

@section('content')
    <table>
        <thead>
        <tr>
            <th style="width: 26px;">#</th>
            <th>Corporativo</th>
            <th style="width: 90px;">RFC</th>
            <th style="width: 70px;">Código</th>
            <th style="width: 90px;">Teléfono</th>
            <th>Email</th>
            <th>Dirección</th>
            <th style="width: 70px;">Sucursales</th>
            <th style="width: 55px;">Áreas</th>
            <th style="width: 70px;">Estatus</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $r)
            <tr>
                <td class="muted">{{ $i + 1 }}</td>
                <td><strong>{{ $r['nombre'] ?? '' }}</strong></td>
                <td>{{ $r['rfc'] ?? '' }}</td>
                <td>{{ $r['codigo'] ?? '' }}</td>
                <td>{{ $r['telefono'] ?? '' }}</td>
                <td>{{ $r['email'] ?? '' }}</td>
                <td>{{ $r['direccion'] ?? '' }}</td>
                <td class="muted" style="text-align:right;">{{ $r['sucursales_count'] ?? 0 }}</td>
                <td class="muted" style="text-align:right;">{{ $r['areas_count'] ?? 0 }}</td>
                <td>
                    @php($ok = ($r['activo'] ?? false) ? true : false)
                    <span class="badge {{ $ok ? 'badge-on' : 'badge-off' }}">
                        {{ $r['estatus_label'] ?? ($ok ? 'Activo' : 'Baja') }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="muted" style="text-align:center; padding: 18px;">
                    No hay registros con los filtros actuales.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:10px" class="muted">
        Total registros: <strong>{{ $totals['total'] ?? count($rows) }}</strong>
    </div>
@endsection

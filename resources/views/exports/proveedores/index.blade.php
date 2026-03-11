@extends('exports.layouts.base-report')

@section('content')
    <table>
        <thead>
        <tr>
            <th style="width: 26px;">#</th>
            <th>Razón social</th>
            <th>RFC</th>
            <th>CLABE</th>
            <th>Banco</th>
            <th style="width: 70px;">Estatus</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r['razon_social'] ?? '—' }}</td>
                <td>{{ $r['rfc'] ?? '—' }}</td>
                <td>{{ $r['clabe'] ?? '—' }}</td>
                <td>{{ $r['banco'] ?? '—' }}</td>
                <td>
                    @if(strtoupper((string)($r['status'] ?? '')) === 'ACTIVO')
                        <span class="badge badge-ok">Activo</span>
                    @else
                        <span class="badge badge-off">Inactivo</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted" style="text-align:center; padding: 18px;">
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

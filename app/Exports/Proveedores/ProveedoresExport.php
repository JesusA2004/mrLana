<?php

namespace App\Exports\Proveedores;

use App\Exports\Core\BaseReportExport;

class ProveedoresExport extends BaseReportExport {

    protected function headings(): array {
        return ['Razón social','RFC','CLABE','Banco','Estatus','Fecha de alta'];
    }

    protected function mapRow(array $r): array {
        return [
            $r['razon_social'] ?? '—',
            $r['rfc']          ?? '—',
            $r['clabe']        ?? '—',
            $r['banco']        ?? '—',
            $r['status']       ?? '—',
            $r['created_at']   ?? '—',
        ];
    }

    protected function columnWidths(): array {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 12,
            'F' => 18,
        ];
    }

}

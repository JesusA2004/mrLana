<?php

namespace App\Exports\Sucursales;

use App\Exports\Core\BaseReportExport;

class SucursalesExport extends BaseReportExport
{
    protected function headings(): array
    {
        return [
            'ID',
            'Corporativo',
            'Sucursal',
            'Código',
            'Ciudad',
            'Estado',
            'Dirección',
            'Estatus',
            'Creado',
            'Actualizado',
        ];
    }

    protected function mapRow(array $r): array
    {
        return [
            $r['id'] ?? '—',
            $r['corporativo'] ?? '—',
            $r['sucursal'] ?? '—',
            $r['codigo'] ?? '—',
            $r['ciudad'] ?? '—',
            $r['estado'] ?? '—',
            $r['direccion'] ?? '—',
            (($r['activo'] ?? false) ? 'Activo' : 'Baja'),
            $r['created_at'] ?? '—',
            $r['updated_at'] ?? '—',
        ];
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 26,
            'C' => 26,
            'D' => 14,
            'E' => 16,
            'F' => 14,
            'G' => 38,
            'H' => 12,
            'I' => 18,
            'J' => 18,
        ];
    }
}

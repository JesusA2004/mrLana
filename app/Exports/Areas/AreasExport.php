<?php

namespace App\Exports\Areas;

use App\Exports\Core\BaseReportExport;

class AreasExport extends BaseReportExport
{
    protected function headings(): array
    {
        return [
            'ID',
            'Corporativo',
            'Área',
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
            $r['nombre'] ?? '—',
            (($r['activo'] ?? false) ? 'Activo' : 'Baja'),
            $r['created_at'] ?? '—',
            $r['updated_at'] ?? '—',
        ];
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 30,
            'C' => 34,
            'D' => 12,
            'E' => 18,
            'F' => 18,
        ];
    }
}

<?php

namespace App\Exports\Conceptos;

use App\Exports\Core\BaseReportExport;

class ConceptosExport extends BaseReportExport
{
    protected function headings(): array
    {
        return [
            'ID',
            'Concepto',
            'Estatus',
            'Creado',
            'Actualizado',
        ];
    }

    protected function mapRow(array $r): array
    {
        return [
            $r['id'] ?? '—',
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
            'B' => 44,
            'C' => 12,
            'D' => 18,
            'E' => 18,
        ];
    }
}

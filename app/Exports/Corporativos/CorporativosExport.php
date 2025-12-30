<?php

namespace App\Exports\Corporativos;

use App\Exports\Core\BaseReportExport;

class CorporativosExport extends BaseReportExport {

    protected function headings(): array {
        return [
            'ID',
            'Corporativo',
            'RFC',
            'Código',
            'Teléfono',
            'Email',
            'Dirección',
            'Sucursales',
            'Áreas',
            'Estatus',
            'Creado',
        ];
    }

    protected function mapRow(array $r): array {
        return [
            (string)($r['id'] ?? ''),
            (string)($r['nombre'] ?? ''),
            (string)($r['rfc'] ?? ''),
            (string)($r['codigo'] ?? ''),
            (string)($r['telefono'] ?? ''),
            (string)($r['email'] ?? ''),
            (string)($r['direccion'] ?? ''),
            (string)($r['sucursales_count'] ?? 0),
            (string)($r['areas_count'] ?? 0),
            (string)($r['estatus_label'] ?? ''),
            (string)($r['created_at'] ?? ''),
        ];
    }

    protected function columnWidths(): array {
        return [
            'A' => 8,
            'B' => 30,
            'C' => 18,
            'D' => 14,
            'E' => 16,
            'F' => 28,
            'G' => 40,
            'H' => 12,
            'I' => 10,
            'J' => 12,
            'K' => 18,
        ];
    }

}

<?php

namespace App\Exports\Requisiciones;

use App\Exports\Core\BaseReportExport;

class RequisicionesExport extends BaseReportExport
{
    protected function headings(): array
    {
        return [
            'Folio',
            'Tipo',
            'Estatus',
            'Comprador',
            'Corporativo',
            'Sucursal',
            'Solicitante',
            'Proveedor',
            'Concepto',
            'Subtotal',
            'IVA',
            'Total',
            'Fecha captura',
            'Fecha pago',
            'Creada por',
        ];
    }

    protected function mapRow(array $r): array
    {
        return [
            $r['folio'] ?? '—',
            $r['tipo'] ?? '—',
            $r['status'] ?? '—',
            $r['comprador'] ?? '—',
            $r['corporativo'] ?? '—',
            $r['sucursal'] ?? '—',
            $r['solicitante'] ?? '—',
            $r['proveedor'] ?? '—',
            $r['concepto'] ?? '—',
            $this->money($r['subtotal'] ?? 0),
            $this->money($r['iva'] ?? 0),
            $this->money($r['total'] ?? 0),
            $r['fecha_captura'] ?? '—',
            $r['fecha_pago'] ?? '—',
            $r['created_by'] ?? '—',
        ];
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 14,
            'C' => 16,
            'D' => 22,
            'E' => 22,
            'F' => 20,
            'G' => 24,
            'H' => 24,
            'I' => 22,
            'J' => 14,
            'K' => 12,
            'L' => 14,
            'M' => 18,
            'N' => 14,
            'O' => 22,
        ];
    }

    private function money($v): string
    {
        $n = is_numeric($v) ? (float) $v : 0.0;
        return number_format($n, 2, '.', '');
    }
}

<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequisicionesDataSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Requisiciones';
    }

    public function headings(): array
    {
        return [
            'Folio',
            'Fecha captura',
            'Tipo',
            'Estatus',
            'Comprador',
            'Sucursal',
            'Solicitante',
            'Proveedor',
            'Concepto',
            'Subtotal',
            'Total',
        ];
    }

    public function array(): array
    {
        return array_map(function ($r) {
            return [
                $r['folio'] ?? '',
                $r['fecha_captura'] ?? '',
                $r['tipo'] ?? '',
                $r['estatus'] ?? '',
                $r['comprador'] ?? '',
                $r['sucursal'] ?? '',
                $r['solicitante'] ?? '',
                $r['proveedor'] ?? '',
                $r['concepto'] ?? '',
                (float)($r['subtotal'] ?? 0),
                (float)($r['total'] ?? 0),
            ];
        }, $this->rows);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports\Dashboard\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ComprobantesMixSheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Comprobantes Mes';
    }

    public function array(): array
    {
        $rows = [['Tipo', 'Cantidad']];

        foreach (($this->data['comprobantesMix'] ?? []) as $k => $v) {
            $rows[] = [(string)$k, (float)$v];
        }

        return $rows;
    }
}

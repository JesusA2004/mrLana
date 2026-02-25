<?php

namespace App\Exports\Dashboard\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AmountsDailySheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Montos 14d';
    }

    public function array(): array
    {
        $rows = [['Fecha', 'Monto']];

        foreach (($this->data['amountsDaily'] ?? []) as $p) {
            $rows[] = [
                (string)($p['date'] ?? ''),
                (float)($p['value'] ?? 0),
            ];
        }

        return $rows;
    }
}

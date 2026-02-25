<?php

namespace App\Exports\Dashboard\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KpisSheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'KPIs';
    }

    public function array(): array
    {
        $rows = [['KPI', 'Valor']];

        foreach (($this->data['kpis'] ?? []) as $k) {
            $rows[] = [
                (string)($k['label'] ?? ''),
                (string)($k['value'] ?? ''),
            ];
        }

        return $rows;
    }
}

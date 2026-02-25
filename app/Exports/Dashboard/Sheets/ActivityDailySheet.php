<?php

namespace App\Exports\Dashboard\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActivityDailySheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Actividad 14d';
    }

    public function array(): array
    {
        $rows = [['Fecha', 'Cantidad']];

        foreach (($this->data['activityDaily'] ?? []) as $p) {
            $rows[] = [
                (string)($p['date'] ?? ''),
                (float)($p['value'] ?? 0),
            ];
        }

        return $rows;
    }
}

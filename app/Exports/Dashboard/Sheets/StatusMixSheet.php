<?php

namespace App\Exports\Dashboard\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StatusMixSheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Estatus 30d';
    }

    public function array(): array
    {
        $rows = [['Estatus', 'Cantidad']];

        foreach (($this->data['statusMix'] ?? []) as $k => $v) {
            $rows[] = [(string)$k, (float)$v];
        }

        return $rows;
    }
}

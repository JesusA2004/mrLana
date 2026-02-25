<?php

namespace App\Exports\Dashboard;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExcelExport implements WithMultipleSheets
{
    public function __construct(
        private string $role,
        private string $generatedAt,
        private array $data
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new Sheets\ResumenSheet($this->role, $this->generatedAt, $this->data);
        $sheets[] = new Sheets\KpisSheet($this->data);

        $sheets[] = new Sheets\ActivityDailySheet($this->data);
        $sheets[] = new Sheets\AmountsDailySheet($this->data);

        if ($this->role === 'ADMIN') {
            $sheets[] = new Sheets\StatusMixSheet($this->data);
            $sheets[] = new Sheets\ComprobantesMixSheet($this->data);
        }

        return $sheets;
    }
}

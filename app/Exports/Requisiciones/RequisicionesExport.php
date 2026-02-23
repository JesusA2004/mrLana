<?php

namespace App\Exports\Requisiciones;

use App\Exports\Sheets\RequisicionesDataSheet;
use App\Exports\Sheets\RequisicionesFiltersSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RequisicionesExport implements WithMultipleSheets {

    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /** @var array<string, string> */
    private array $filters;

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, string> $filters
     */
    public function __construct(array $rows, array $filters = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new RequisicionesDataSheet($this->rows),
            new RequisicionesFiltersSheet($this->filters),
        ];
    }
}

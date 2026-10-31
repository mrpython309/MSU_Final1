<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AssetExcelExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithCustomValueBinder, WithColumnFormatting, WithColumnWidths
{
    /**
     * Columns that must stay exact text. Long numeric serials are otherwise
     * stored as Excel numbers and shown in scientific notation.
     *
     * @param  Collection<int, array<int, mixed>>  $rows
     * @param  list<string>  $headings
     * @param  list<string>  $textColumns
     */
    public function __construct(
        private readonly Collection $rows,
        private readonly array $headings,
        private readonly array $textColumns = ['D'],
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->textColumns as $column) {
            $formats[$column] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }

    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->textColumns as $column) {
            $widths[$column] = 28;
        }

        return $widths;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), $this->textColumns, true)) {
            $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}

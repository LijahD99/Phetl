<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Generator;
use Phetl\Contracts\ExtractorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Extracts data from Excel files (.xlsx, .xls).
 *
 * Supports multiple sheets, formulas, and various data types.
 * Uses lazy evaluation for memory efficiency with large files.
 */
final class ExcelExtractor implements ExtractorInterface
{
    /**
     * @param string $filePath Path to the Excel file
     * @param string|int|null $sheet Sheet name or index (0-based), null for active sheet
     */
    public function __construct(
        private readonly string $filePath,
        private readonly string|int|null $sheet = null
    ) {
        $this->validate();
    }

    /**
     * @return Generator<int, array<int|string, mixed>>
     */
    public function extract(): Generator
    {
        $spreadsheet = IOFactory::load($this->filePath);

        // Get the specified worksheet
        $worksheet = $this->getWorksheet($spreadsheet);

        // Get the highest row and column
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // If sheet is empty, return early
        if ($highestRow === 1 && $worksheet->getCell('A1')->getValue() === null) {
            return;
        }

        // Iterate through rows
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $worksheet->getCell($coordinate);
                $value = $cell->getCalculatedValue(); // Evaluates formulas

                $rowData[] = $value;
            }

            yield $rowData;
        }
    }

    /**
     * Get the worksheet to extract from.
     */
    private function getWorksheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): Worksheet
    {
        // If no sheet specified, use active sheet
        if ($this->sheet === null) {
            return $spreadsheet->getActiveSheet();
        }

        // Sheet specified by name
        if (is_string($this->sheet)) {
            $sheet = $spreadsheet->getSheetByName($this->sheet);
            if ($sheet === null) {
                throw new InvalidArgumentException(
                    sprintf('Sheet "%s" not found in Excel file', $this->sheet)
                );
            }
            return $sheet;
        }

        // Sheet specified by index
        try {
            return $spreadsheet->getSheet($this->sheet);
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Sheet index %d not found in Excel file', $this->sheet)
            );
        }
    }

    /**
     * Validate file exists and sheet is valid.
     */
    private function validate(): void
    {
        if (!file_exists($this->filePath)) {
            throw new InvalidArgumentException('Excel file does not exist: ' . $this->filePath);
        }

        // If a specific sheet is requested, validate it exists
        if ($this->sheet !== null) {
            $spreadsheet = IOFactory::load($this->filePath);

            if (is_string($this->sheet)) {
                $sheet = $spreadsheet->getSheetByName($this->sheet);
                if ($sheet === null) {
                    throw new InvalidArgumentException(
                        sprintf('Sheet "%s" not found in Excel file', $this->sheet)
                    );
                }
            } else {
                try {
                    $spreadsheet->getSheet($this->sheet);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    throw new InvalidArgumentException(
                        sprintf('Sheet index %d not found in Excel file', $this->sheet)
                    );
                }
            }
        }
    }
}

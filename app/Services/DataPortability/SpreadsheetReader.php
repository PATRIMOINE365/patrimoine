<?php

namespace App\Services\DataPortability;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

/**
 * Parse an uploaded Registry backup file (CSV or XLSX) into header-keyed
 * rows for one entity set.
 *
 * The header row is a machine contract: RegistryExportService writes
 * STABLE untranslated column keys, and this reader validates that the
 * columns required to match rows (the exported id plus the natural-key
 * columns) are present before any import work starts.
 *
 * A multi-sheet full-backup workbook is supported transparently: when an
 * XLSX file contains a sheet named after the requested entity, that sheet
 * is read; otherwise the first sheet is used.
 *
 * Failures are reported as RuntimeException; the controller converts them
 * into a 422 validation response.
 */
class SpreadsheetReader
{
    /**
     * Columns an import file must provide per entity: the exported id and
     * the natural-key columns used for idempotent matching.
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED_COLUMNS = [
        'parties' => [
            'id',
            'type',
            'name',
        ],

        'buildings' => [
            'id',
            'name',
        ],

        'units' => [
            'id',
            'building_id',
            'name',
        ],

        'leases' => [
            'id',
            'unit_id',
            'tenant_id',
            'start_date',
        ],
    ];

    /**
     * Read the uploaded file into header-keyed data rows.
     *
     * @return list<array<string, string>>
     */
    public function rows(
        UploadedFile $file,
        string $entity
    ): array {
        $required =
            self::REQUIRED_COLUMNS[$entity]
            ?? throw new RuntimeException(
                "Unknown Registry import entity [{$entity}]."
            );

        $raw =
            $this->isXlsx($file)
                ? $this->xlsxRows(
                    $file->getRealPath(),
                    $entity
                )
                : $this->csvRows(
                    $file->getRealPath()
                );

        if ($raw === []) {
            throw new RuntimeException(
                'The uploaded file contains no rows.'
            );
        }

        /*
         * First row is the stable-key header.
         */
        $header =
            array_map(
                static fn (
                    string $key
                ): string =>
                    trim($key),
                array_shift($raw)
            );

        $missing =
            array_values(
                array_diff(
                    $required,
                    $header
                )
            );

        if ($missing !== []) {
            throw new RuntimeException(
                "The file is missing required {$entity} columns: "
                .implode(', ', $missing)
                .'.'
            );
        }

        $rows = [];

        foreach ($raw as $cells) {
            $row = [];

            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $row[$key] =
                    $cells[$index]
                    ?? '';
            }

            /*
             * Fully-empty spreadsheet rows (a common trailing artifact)
             * are ignored rather than counted as skipped data.
             */
            if (
                array_filter(
                    $row,
                    static fn (
                        string $value
                    ): bool =>
                        trim($value) !== ''
                ) === []
            ) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Detect XLSX uploads by extension or the ZIP magic prefix, so a
     * mislabelled workbook is still parsed correctly.
     */
    private function isXlsx(
        UploadedFile $file
    ): bool {
        if (
            strtolower(
                (string) $file->getClientOriginalExtension()
            ) === 'xlsx'
        ) {
            return true;
        }

        $handle =
            fopen(
                (string) $file->getRealPath(),
                'r'
            );

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to open the uploaded file.'
            );
        }

        $prefix =
            (string) fread(
                $handle,
                2
            );

        fclose(
            $handle
        );

        return $prefix === 'PK';
    }

    /**
     * Raw cell matrix from a CSV file, stripping the UTF-8 BOM the export
     * prefixes for spreadsheet applications.
     *
     * @return list<list<string>>
     */
    private function csvRows(
        string $path
    ): array {
        $stream =
            fopen(
                $path,
                'r'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to open the uploaded CSV file.'
            );
        }

        /*
         * Drop the BOM when present; rewind otherwise so no data is lost.
         */
        $bom =
            (string) fread(
                $stream,
                3
            );

        if ($bom !== "\xEF\xBB\xBF") {
            rewind(
                $stream
            );
        }

        $rows = [];

        while (
            ($cells = fgetcsv(
                $stream,
                0,
                ',',
                '"',
                ''
            )) !== false
        ) {
            $rows[] =
                array_map(
                    static fn (
                        ?string $cell
                    ): string =>
                        (string) $cell,
                    $cells
                );
        }

        fclose(
            $stream
        );

        return $rows;
    }

    /**
     * Raw cell matrix from an XLSX workbook via the openspout Reader.
     *
     * Prefers the sheet named after the entity (full-backup workbooks);
     * falls back to the first sheet for single-entity exports.
     *
     * @return list<list<string>>
     */
    private function xlsxRows(
        string $path,
        string $entity
    ): array {
        $reader =
            new XlsxReader();

        try {
            $reader->open(
                $path
            );
        } catch (Throwable) {
            throw new RuntimeException(
                'Unable to read the uploaded XLSX file.'
            );
        }

        try {
            $matched = null;
            $first = null;

            foreach (
                $reader->getSheetIterator()
                as $sheet
            ) {
                $first ??= $sheet;

                if (
                    strcasecmp(
                        $sheet->getName(),
                        $entity
                    ) === 0
                ) {
                    $matched = $sheet;

                    break;
                }
            }

            $sheet =
                $matched
                ?? $first;

            if ($sheet === null) {
                return [];
            }

            $rows = [];

            foreach (
                $sheet->getRowIterator()
                as $row
            ) {
                $rows[] =
                    array_map(
                        fn (
                            mixed $cell
                        ): string =>
                            $this->cell($cell),
                        $row->toArray()
                    );
            }

            return $rows;
        } finally {
            $reader->close();
        }
    }

    /**
     * Normalize one XLSX cell to the string form the import understands.
     *
     * Spreadsheet applications retype cells: dates become DateTime
     * objects and ids become floats. Everything is folded back to the
     * stable export string forms.
     */
    private function cell(
        mixed $cell
    ): string {
        if ($cell === null) {
            return '';
        }

        if ($cell instanceof DateTimeInterface) {
            return $cell->format('Y-m-d');
        }

        if (is_bool($cell)) {
            return $cell ? '1' : '0';
        }

        if (
            is_float($cell)
            && floor($cell) === $cell
            && abs($cell) < PHP_INT_MAX
        ) {
            return (string) (int) $cell;
        }

        return (string) $cell;
    }
}

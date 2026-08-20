<?php

namespace App\Services;

use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class FinancialJournalExportService
{
    public function __construct(
        private readonly ApplicationPresentationFormatter $formatter
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        $columns = __(
            'financial_journal.columns'
        );

        if (! is_array($columns)) {
            throw new RuntimeException(
                'Financial Journal export columns are unavailable.'
            );
        }

        return $columns;
    }

    /**
     * @param Collection<int, JournalEntry> $entries
     * @return list<array<string, string>>
     */
    public function rows(
        Collection $entries
    ): array {
        $rows = [];

        foreach ($entries as $entry) {
            $entry->loadMissing([
                'lines',
                'reversedEntry:id,journal_number',
                'reversalEntry:id,journal_number',
            ]);

            if ($entry->lines->isEmpty()) {
                $rows[] =
                    $this->row(
                        $entry
                    );

                continue;
            }

            foreach ($entry->lines as $line) {
                $rows[] =
                    $this->row(
                        $entry,
                        $line
                    );
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function row(
        JournalEntry $entry,
        mixed $line = null
    ): array {
        return [
            'journal_number' =>
                (string) $entry->journal_number,

            'journal_date' =>
                $this->formatter->date(
                    $entry->journal_date
                ),

            'posted_at' =>
                $entry->posted_at
                    ? $entry->posted_at->format(
                        'd-m-Y H:i:s'
                    )
                    : '',

            'entry_kind' =>
                $this->entryKind(
                    $entry->entry_kind
                ),

            'transaction_type' =>
                $this->humanize(
                    (string) $entry->transaction_type
                ),

            'description' =>
                (string) (
                    $entry->description
                    ?? ''
                ),

            'actor' =>
                (string) (
                    $entry->actor_name_snapshot
                    ?? ''
                ),

            'source_type' =>
                $this->sourceType(
                    $entry->source_type
                ),

            'source_id' =>
                $entry->source_id === null
                    ? ''
                    : (string) $entry->source_id,

            'reversal_reference' =>
                $this->reversalReference(
                    $entry
                ),

            'account_code' =>
                (string) (
                    $line?->account_code_snapshot
                    ?? ''
                ),

            'account_name' =>
                (string) (
                    $line?->account_name_snapshot
                    ?? ''
                ),

            'account_type' =>
                $this->humanize(
                    (string) (
                        $line?->account_type_snapshot
                        ?? ''
                    )
                ),

            'debit' =>
                $this->formatter->money(
                    (int) (
                        $line?->debit_amount
                        ?? 0
                    )
                ),

            'credit' =>
                $this->formatter->money(
                    (int) (
                        $line?->credit_amount
                        ?? 0
                    )
                ),

            'memo' =>
                (string) (
                    $line?->memo
                    ?? ''
                ),
        ];
    }

    /**
     * @param Collection<int, JournalEntry> $entries
     * @param array<string, mixed> $filters
     */
    public function pdf(
        Collection $entries,
        array $filters
    ): string {
        return Pdf::loadView(
            'financial-journal.export',
            [
                'title' =>
                    __(
                        'financial_journal.title'
                    ),

                'columns' =>
                    $this->columns(),

                'rows' =>
                    $this->rows(
                        $entries
                    ),

                'filters' =>
                    $filters,

                'generatedAt' =>
                    now()->format(
                        'd-m-Y H:i:s'
                    ),
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->output();
    }

    /**
     * @param Collection<int, JournalEntry> $entries
     */
    public function csv(
        Collection $entries
    ): string {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create Financial Journal CSV stream.'
            );
        }

        $columns =
            $this->columns();

        fputcsv(
            $stream,
            array_values(
                $columns
            ),
            ',',
            '"',
            ''
        );

        foreach (
            $this->rows(
                $entries
            ) as $row
        ) {
            fputcsv(
                $stream,
                array_map(
                    static fn (
                        string $key
                    ): string =>
                        $row[$key]
                        ?? '',
                    array_keys(
                        $columns
                    )
                ),
                ',',
                '"',
                ''
            );
        }

        rewind(
            $stream
        );

        $contents =
            stream_get_contents(
                $stream
            );

        fclose(
            $stream
        );

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read Financial Journal CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    /**
     * @param Collection<int, JournalEntry> $entries
     */
    public function xlsx(
        Collection $entries
    ): string {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-journal-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary XLSX file.'
            );
        }

        $writer =
            new Writer();

        try {
            $writer->openToFile(
                $path
            );

            $columns =
                $this->columns();

            $writer->addRow(
                Row::fromValues(
                    array_values(
                        $columns
                    )
                )
            );

            foreach (
                $this->rows(
                    $entries
                ) as $row
            ) {
                $writer->addRow(
                    Row::fromValues(
                        array_map(
                            static fn (
                                string $key
                            ): string =>
                                $row[$key]
                                ?? '',
                            array_keys(
                                $columns
                            )
                        )
                    )
                );
            }

            $writer->close();

            $contents =
                file_get_contents(
                    $path
                );

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read generated XLSX file.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink(
                    $path
                );
            }
        }
    }

    private function entryKind(
        ?string $kind
    ): string {
        if (! $kind) {
            return '';
        }

        $key =
            'financial_journal.entry_kinds.'
            .$kind;

        $translated =
            __(
                $key
            );

        return is_string($translated)
            && $translated !== $key
                ? $translated
                : $this->humanize(
                    $kind
                );
    }

    private function reversalReference(
        JournalEntry $entry
    ): string {
        if (
            $entry->reversedEntry
                ?->journal_number
        ) {
            return (string) $entry
                ->reversedEntry
                ->journal_number;
        }

        if (
            $entry->reversalEntry
                ?->journal_number
        ) {
            return (string) $entry
                ->reversalEntry
                ->journal_number;
        }

        return '';
    }

    private function sourceType(
        ?string $sourceType
    ): string {
        if (! $sourceType) {
            return '';
        }

        $parts =
            explode(
                '\\',
                $sourceType
            );

        return (string) end(
            $parts
        );
    }

    private function humanize(
        string $value
    ): string {
        if ($value === '') {
            return '';
        }

        return ucwords(
            str_replace(
                [
                    '.',
                    '_',
                    '-',
                ],
                ' ',
                $value
            )
        );
    }
}

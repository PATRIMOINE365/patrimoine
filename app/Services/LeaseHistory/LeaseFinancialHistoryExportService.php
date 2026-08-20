<?php

namespace App\Services\LeaseHistory;

use App\Models\Lease;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class LeaseFinancialHistoryExportService
{
    public function __construct(
        private readonly LeaseFinancialHistoryService $history,
        private readonly ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * @return array{
     *     lease: array<string, mixed>,
     *     events: list<array<string, mixed>>
     * }
     */
    public function projection(
        Lease $lease
    ): array {
        return $this->history->generate(
            $lease
        );
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'date' => __('ui.leases.financial_history_export_date'),

            'type' => __('ui.leases.financial_history_export_type'),

            'reference' => __('ui.leases.financial_history_export_reference'),

            'description' => __('ui.leases.financial_history_export_description'),

            'fund' => __('ui.leases.financial_history_export_fund'),

            'payment_method' => __('ui.leases.financial_history_export_payment_method'),

            'amount' => __('ui.leases.financial_history_export_amount'),

            'document' => __('ui.leases.financial_history_export_document'),
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function rows(
        Lease $lease
    ): array {
        $projection =
            $this->projection(
                $lease
            );

        return collect(
            $projection['events']
            ?? []
        )
            ->map(
                fn (array $event): array => [
                    'date' => $this->formatter->date(
                        $event['occurred_on']
                        ?? null
                    ),

                    'type' => $this->eventTypeLabel(
                        $event['event_type']
                        ?? null
                    ),

                    'reference' => (string) (
                        $event['reference']
                        ?? ''
                    ),

                    'description' => $this->descriptionLabel(
                        $event['description']
                        ?? null
                    ),

                    'fund' => $this->fundLabel(
                        $event['fund_type']
                        ?? null
                    ),

                    'payment_method' => $this->paymentMethodLabel(
                        $event['payment_method']
                        ?? null
                    ),

                    'amount' => $this->formatter->money(
                        $event['amount']
                        ?? 0
                    ),

                    'document' => $this->documentLabel(
                        $event['document']
                        ?? null
                    ),
                ]
            )
            ->values()
            ->all();
    }

    public function pdf(
        Lease $lease
    ): string {
        $projection =
            $this->projection(
                $lease
            );

        return Pdf::loadView(
            'lease-financial-history.export',
            [
                'leaseContext' => $projection['lease'],

                'columns' => $this->columns(),

                'rows' => $this->rows(
                    $lease
                ),

                'generatedAt' => now(),
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->output();
    }

    public function csv(
        Lease $lease
    ): string {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create financial history CSV stream.'
            );
        }

        fputcsv(
            $stream,
            array_values(
                $this->columns()
            ),
            ',',
            '"',
            ''
        );

        foreach (
            $this->rows(
                $lease
            ) as $row
        ) {
            fputcsv(
                $stream,
                array_values(
                    $row
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
                'Unable to read financial history CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    public function xlsx(
        Lease $lease
    ): string {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-history-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary XLSX file.'
            );
        }

        $writer =
            new Writer;

        try {
            $writer->openToFile(
                $path
            );

            $writer->addRow(
                Row::fromValues(
                    array_values(
                        $this->columns()
                    )
                )
            );

            foreach (
                $this->rows(
                    $lease
                ) as $row
            ) {
                $writer->addRow(
                    Row::fromValues(
                        array_values(
                            $row
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
                    'Unable to read financial history XLSX.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function eventTypeLabel(
        ?string $type
    ): string {
        if (! $type) {
            return '';
        }

        $key =
            'ui.leases.financial_history_event_'
            .$type;

        $translated =
            __(
                $key
            );

        return $translated !== $key
            ? $translated
            : $this->humanize(
                $type
            );
    }

    private function descriptionLabel(
        ?string $description
    ): string {
        return $description
            ? $this->humanize(
                $description
            )
            : '';
    }

    private function fundLabel(
        ?string $fund
    ): string {
        if (! $fund) {
            return '';
        }

        $key =
            'ui.leases.financial_history_fund_'
            .$fund;

        $translated =
            __(
                $key
            );

        return $translated !== $key
            ? $translated
            : $this->humanize(
                $fund
            );
    }

    private function paymentMethodLabel(
        ?string $method
    ): string {
        if (! $method) {
            return '';
        }

        $normalized =
            $method === 'momo'
                ? 'mobile_payment'
                : $method;

        $key =
            'ui.leases.financial_history_method_'
            .$normalized;

        $translated =
            __(
                $key
            );

        return $translated !== $key
            ? $translated
            : $this->humanize(
                $method
            );
    }

    /**
     * @param  array<string, mixed>|null  $document
     */
    private function documentLabel(
        ?array $document
    ): string {
        if (! $document) {
            return '';
        }

        return $this->humanize(
            (string) (
                $document['type']
                ?? ''
            )
        );
    }

    private function humanize(
        string $value
    ): string {
        return ucwords(
            str_replace(
                [
                    '_',
                    '-',
                ],
                ' ',
                $value
            )
        );
    }
}

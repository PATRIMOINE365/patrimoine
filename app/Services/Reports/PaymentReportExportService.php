<?php

namespace App\Services\Reports;

use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class PaymentReportExportService
{
    public function __construct(
        private readonly PaymentReportService $reports,
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function projection(
        array $filters = []
    ): array {
        return $this->reports->generate(
            $filters
        );
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'date' => __('ui.reports.date'),

            'payment_number' => __('ui.reports.payment_number'),

            'tenant' => __('ui.reports.tenant'),

            'property' => __('ui.reports.property'),

            'payment_method' => __('ui.reports.payment_method_label'),

            'cash_receiver' => __('ui.reports.cash_receiver'),

            'reference' => __('ui.reports.reference'),

            'amount' => __('ui.reports.amount'),
        ];
    }

    /**
     * The already-calculated projection may be supplied to avoid
     * regenerating the report for one export.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>|null  $projection
     * @return list<array<string, string>>
     */
    public function rows(
        array $filters = [],
        ?array $projection = null
    ): array {
        $projection ??=
            $this->projection(
                $filters
            );

        return collect(
            $projection['payments']
            ?? []
        )
            ->map(
                fn (array $payment): array => [
                    'date' => $this->formatter->date(
                        $payment['payment_date']
                        ?? null
                    ),

                    'payment_number' => (string) (
                        $payment['payment_number']
                        ?? ''
                    ),

                    'tenant' => (string) (
                        $payment['tenant']['name']
                        ?? ''
                    ),

                    'property' => collect([
                        $payment['building']['name']
                            ?? null,

                        $payment['unit']['name']
                            ?? null,
                    ])
                        ->filter()
                        ->implode(' · '),

                    'payment_method' => $this->paymentMethodLabel(
                        $payment['payment_method']
                        ?? null
                    ),

                    'cash_receiver' => (string) (
                        $payment['cash_receiver_name']
                        ?? ''
                    ),

                    'reference' => (string) (
                        $payment['reference']
                        ?? ''
                    ),

                    'amount' => $this->formatter->money(
                        $payment['amount']
                        ?? 0
                    ),
                ]
            )
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function pdf(
        array $filters = []
    ): string {
        $projection =
            $this->projection(
                $filters
            );

        return Pdf::loadView(
            'reports.payments-export',
            [
                'columns' => $this->columns(),

                'rows' => $this->rows(
                    $filters,
                    $projection
                ),

                'summary' => $projection['summary']
                    ?? [],

                'filters' => $projection['filters']
                    ?? [],

                'managingOrganisation' => $this->identity->managingOrganisation(),

                'generatedAt' => now(),

                'formatter' => $this->formatter,
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->output();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function csv(
        array $filters = []
    ): string {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create Payment Report CSV stream.'
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
                $filters
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
                'Unable to read Payment Report CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function xlsx(
        array $filters = []
    ): string {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-payments-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary Payment Report XLSX file.'
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
                    $filters
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
                    'Unable to read Payment Report XLSX.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function paymentMethodLabel(
        ?string $method
    ): string {
        return match ($method) {
            'cash' => __('ui.reports.payment_method_cash'),

            'bank_transfer' => __('ui.reports.payment_method_bank'),

            'momo' => __('ui.reports.payment_method_mobile'),

            'cheque' => __('ui.reports.payment_method_cheque'),

            default => $method
                    ? ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $method
                        )
                    )
                    : '',
        };
    }
}

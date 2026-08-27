<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `cheque` as a fourth supported payment channel.
 *
 * Patrimoine shipped with three channels — cash, bank transfer and mobile
 * payment. Cheques are widely used by the francophone Etude/notary clients,
 * so the channel becomes a first-class option everywhere a payment method
 * is recorded.
 *
 * The four affected columns are MySQL ENUMs, which have to be widened in
 * place. Databases created from scratch (including the SQLite test database)
 * already carry the fourth value from the original create migrations, so
 * this migration only has work to do on MySQL/MariaDB installations that
 * predate it.
 */
return new class extends Migration
{
    /**
     * Columns holding a payment-method enumeration, and whether the column
     * accepts NULL.
     *
     * @var array<int, array{table: string, column: string, nullable: bool}>
     */
    private array $columns = [
        [
            'table' => 'payments',
            'column' => 'payment_method',
            'nullable' => false,
        ],
        [
            'table' => 'owner_payouts',
            'column' => 'payment_method',
            'nullable' => false,
        ],
        [
            'table' => 'tenant_fund_transactions',
            'column' => 'payment_method',
            'nullable' => true,
        ],
        [
            'table' => 'withdrawal_receipts',
            'column' => 'payment_method',
            'nullable' => false,
        ],
    ];

    public function up(): void
    {
        $this->applyMethods([
            'cash',
            'bank_transfer',
            'momo',
            'cheque',
        ]);
    }

    /**
     * Narrow the enumeration back to the original three channels.
     *
     * Rolling back is refused while cheque payments exist, because dropping
     * the value would silently rewrite recorded financial history.
     */
    public function down(): void
    {
        foreach ($this->columns as $definition) {
            if (
                ! Schema::hasTable($definition['table'])
                || ! Schema::hasColumn(
                    $definition['table'],
                    $definition['column']
                )
            ) {
                continue;
            }

            $recorded =
                DB::table($definition['table'])
                    ->where(
                        $definition['column'],
                        'cheque'
                    )
                    ->count();

            if ($recorded > 0) {
                throw new RuntimeException(
                    sprintf(
                        'Cannot remove the cheque payment method: %d %s row(s) still use it.',
                        $recorded,
                        $definition['table']
                    )
                );
            }
        }

        $this->applyMethods([
            'cash',
            'bank_transfer',
            'momo',
        ]);
    }

    /**
     * Redefine every payment-method enumeration.
     *
     * @param array<int, string> $methods
     */
    private function applyMethods(array $methods): void
    {
        $driver = DB::connection()->getDriverName();

        /*
         * Only MySQL/MariaDB store these as a true ENUM that must be
         * altered. Other drivers build the column from the create
         * migrations, which already list every supported channel.
         */
        if (
            $driver !== 'mysql'
            && $driver !== 'mariadb'
        ) {
            return;
        }

        $values = implode(
            ', ',
            array_map(
                fn (string $method): string => "'".$method."'",
                $methods
            )
        );

        foreach ($this->columns as $definition) {
            if (
                ! Schema::hasTable($definition['table'])
                || ! Schema::hasColumn(
                    $definition['table'],
                    $definition['column']
                )
            ) {
                continue;
            }

            DB::statement(
                sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` ENUM(%s) %s',
                    $definition['table'],
                    $definition['column'],
                    $values,
                    $definition['nullable']
                        ? 'NULL'
                        : 'NOT NULL'
                )
            );
        }
    }
};

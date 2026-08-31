<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.42: archiving, for the records that cannot be deleted.
 *
 * A party who has held a lease, a property with a ledger behind it, a unit
 * that has been let, a lease that has taken money — none of these can be
 * removed without tearing a hole in the accounting, and Patrimoine refuses
 * to. That was the right answer to the wrong question: the operator does
 * not want it gone, they want it out of the way.
 *
 * Archiving takes it out of the lists and out of the pickers, so nobody is
 * offered it when building something new, and changes nothing else. Every
 * invoice, receipt, ledger row and audit entry still names it, because
 * nothing about the record itself has moved. It is one column and it is
 * reversible.
 *
 * The user is recorded because taking a property out of the lists is the
 * sort of thing somebody asks about a month later.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'parties',
        'buildings',
        'units',
        'leases',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table(
                $table,
                function (Blueprint $blueprint): void {
                    $blueprint->timestamp('archived_at')
                        ->nullable();

                    $blueprint->foreignId('archived_by_user_id')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    /*
                     * Every list filters on this, so it is worth an index
                     * even though most rows will never be archived.
                     */
                    $blueprint->index('archived_at');
                }
            );
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table(
                $table,
                function (Blueprint $blueprint) use ($table): void {
                    $blueprint->dropIndex($table.'_archived_at_index');

                    $blueprint->dropConstrainedForeignId(
                        'archived_by_user_id'
                    );

                    $blueprint->dropColumn('archived_at');
                }
            );
        }
    }
};

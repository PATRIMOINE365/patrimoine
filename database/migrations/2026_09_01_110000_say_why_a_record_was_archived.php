<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.43: why a record was put away.
 *
 * Archiving reads like deletion to the person doing it — the record leaves
 * every list and every picker, and to anybody who did not archive it, it
 * has simply gone. So it is now asked for in the same way deletion is:
 * a drawer that says plainly what will happen, and a reason.
 *
 * The reason lives on the row rather than only in the activity log,
 * because the archive page is where somebody goes to ask why something is
 * not in the lists any more, and answering that should not require
 * reading an audit trail.
 *
 * Restoring clears it: a record that is back in the lists is not archived
 * for any reason at all. The reason given for the restore goes to the
 * activity log, which is where a thing that has already happened belongs.
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
                    $blueprint->string('archived_reason', 500)
                        ->nullable()
                        ->after('archived_by_user_id');
                }
            );
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table(
                $table,
                function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('archived_reason');
                }
            );
        }
    }
};

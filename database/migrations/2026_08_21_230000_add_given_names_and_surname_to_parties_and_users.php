<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.7 structured person names.
 *
 * People (person Parties and application Users) get explicit
 * `given_names` + `surname` columns. The existing `name` column REMAINS
 * the canonical composed display name — every list, document, export and
 * e-mail keeps reading `name`, while forms edit the two structured parts.
 *
 * Backfill rule (per the release specification): the last space-separated
 * word of the current full name becomes the surname; everything before it
 * becomes the given names. Single-word names become surname-only.
 * Organisations and associations are left untouched — structured names
 * apply to people only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table): void {
            $table->string('given_names')->nullable()->after('name');
            $table->string('surname')->nullable()->after('given_names');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('given_names')->nullable()->after('name');
            $table->string('surname')->nullable()->after('given_names');
        });

        $this->backfill('parties', onlyPersons: true);
        $this->backfill('users', onlyPersons: false);
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table): void {
            $table->dropColumn(['given_names', 'surname']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['given_names', 'surname']);
        });
    }

    /**
     * Split existing full names in place, in chunks so the migration stays
     * safe on large datasets.
     */
    private function backfill(string $table, bool $onlyPersons): void
    {
        $query = DB::table($table)->select('id', 'name');

        if ($onlyPersons) {
            $query->where('type', 'person');
        }

        $query->orderBy('id')->chunk(500, function ($rows) use ($table): void {
            foreach ($rows as $row) {
                $name = trim((string) $row->name);

                if ($name === '') {
                    continue;
                }

                $lastSpace = strrpos($name, ' ');

                [$given, $surname] = $lastSpace === false
                    ? [null, $name]
                    : [
                        trim(substr($name, 0, $lastSpace)),
                        trim(substr($name, $lastSpace + 1)),
                    ];

                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        'given_names' => $given,
                        'surname' => $surname,
                    ]);
            }
        });
    }
};

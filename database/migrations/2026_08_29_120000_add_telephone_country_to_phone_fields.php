<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.30: give every telephone number the country it was dialled from.
 *
 * Numbers were free text until now, which is enough to print on an invoice
 * and not enough to send anybody a one-time code. From here the number
 * column holds E.164 and the new column beside it holds the ISO country, so
 * the right flag can be shown again — a shared calling code such as +1 can
 * never tell Canada from the United States on its own.
 *
 * Numbers already recorded are converted only where they can be read with
 * certainty: one that already starts with a calling code is rewritten, and
 * one that does not is left exactly as it was typed, with no country. Those
 * are normalised the next time somebody edits the record, and every screen
 * shows them meanwhile.
 */
return new class extends Migration
{
    /**
     * The number columns, by table.
     *
     * @var array<string, array<int, string>>
     */
    private array $columns = [
        'parties' => [
            'phone',
            'alternate_phone',
            'contact_person_phone',
        ],

        'users' => [
            'phone',
        ],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->char($column.'_country', 2)
                        ->nullable()
                        ->after($column);
                }
            });
        }

        $this->convertExistingNumbers();
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->dropColumn($column.'_country');
                }
            });
        }
    }

    /**
     * Read what can be read, and leave the rest alone.
     */
    private function convertExistingNumbers(): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->select(['id', $column])
                    ->whereNotNull($column)
                    ->where($column, 'like', '+%')
                    ->orderBy('id')
                    ->chunk(200, function ($rows) use ($table, $column): void {
                        foreach ($rows as $row) {
                            /*
                             * Spaces, brackets and dashes go; the plus and
                             * the digits stay.
                             */
                            $normalised = '+'.preg_replace(
                                '/[^0-9]/',
                                '',
                                substr((string) $row->{$column}, 1)
                            );

                            $country = PhoneNumber::countryFor($normalised);

                            if ($country === null || ! PhoneNumber::isE164($normalised)) {
                                continue;
                            }

                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([
                                    $column => $normalised,
                                    $column.'_country' => $country,
                                ]);
                        }
                    });
            }
        }
    }
};

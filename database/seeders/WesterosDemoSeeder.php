<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\LeaseInitializationService;
use App\Services\PaymentAllocationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rich local-development dataset inspired by Game of Thrones and
 * House of the Dragon.
 *
 * IMPORTANT:
 * - Explicitly invoked only.
 * - Never registered in DatabaseSeeder.
 * - Uses Patrimoine's real invoice/payment/accounting services.
 */
class WesterosDemoSeeder extends Seeder
{
    private array $owners = [];

    private array $tenants = [];

    private array $agents = [];

    private array $buildings = [];

    private int $phoneSequence = 200000000;

    private int $paymentSequence = 1;

    public function run(): void
    {
        /*
         * V1.1.0 multi-tenancy: demo data belongs to a demo
         * organisation; every row created below is stamped through
         * the bound organisation context.
         */
        $demoOrganisation =
            \App\Models\Organisation::query()->firstOrCreate(
                ['name' => 'Demo Organisation'],
                ['status' => 'active']
            );

        \App\Support\OrganisationContext::runAs(
            (int) $demoOrganisation->id,
            function (): void {
                $this->seedScoped();
            }
        );
    }

    /**
     * The original seeding body, executed with the demo
     * organisation context bound.
     */
    private function seedScoped(): void
    {
        if (
            Building::query()
                ->where('notes', 'like', '%WESTEROS DEMO DATASET%')
                ->exists()
        ) {
            throw new \RuntimeException(
                'Westeros demo data already exists. Reset the database before running this seeder again.'
            );
        }

        DB::transaction(function (): void {
            $this->createOwners();
            $this->createAgents();
            $this->createTenants();
            $this->createBuildingsAndUnits();
            $this->createLeases();
        });

        $this->command?->newLine();
        $this->command?->info('Westeros demo dataset created successfully.');
        $this->command?->info('Parties:   '.Party::count());
        $this->command?->info('Buildings: '.Building::count());
        $this->command?->info('Units:     '.Unit::count());
        $this->command?->info('Leases:    '.Lease::count());
        $this->command?->info(
            'Invoices:  '.Invoice::count()
        );
        $this->command?->info(
            'Payments:  '.Payment::count()
        );
        $this->command?->info(
            'Allocations: '.PaymentAllocation::count()
        );
    }

    private function createOwners(): void
    {
        $owners = [
            ['stark', 'House Stark', 'Catelyn Stark'],
            ['targaryen', 'House Targaryen', 'Rhaenyra Targaryen'],
            ['velaryon', 'House Velaryon', 'Corlys Velaryon'],
            ['lannister', 'House Lannister', 'Tyrion Lannister'],
            ['hightower', 'House Hightower', 'Otto Hightower'],
            ['baratheon', 'House Baratheon', 'Stannis Baratheon'],
            ['tyrell', 'House Tyrell', 'Olenna Tyrell'],
            ['arryn', 'House Arryn', 'Yohn Royce'],
            ['martell', 'House Martell', 'Doran Martell'],
            ['tully', 'House Tully', 'Edmure Tully'],
        ];

        foreach ($owners as [$key, $name, $contact]) {
            $party = Party::create([
                'type' => 'organisation',
                'legal_name' => $name,
                'phone' => $this->nextPhone(),
                'email' => "{$key}@westeros.patrimoine.test",
                'address' => "{$name}, Westeros",
                'contact_person_name' => $contact,
                'contact_person_phone' => $this->nextPhone(),
                'contact_person_email' => "{$key}.contact@westeros.patrimoine.test",
                'registration_number' => 'WEST-'.strtoupper($key),
                'vat_tin' => 'TIN-'.strtoupper($key),
                'bank_name' => 'Iron Bank of Braavos',
                'bank_account_name' => $name,
                'bank_account_number' => (string) random_int(1000000000, 9999999999),
                'bank_branch' => 'Braavos',
                'notes' => 'WESTEROS DEMO DATASET - Property owner.',
            ]);

            PartyRole::create([
                'party_id' => $party->id,
                'role' => 'owner',
            ]);

            $this->owners[$key] = $party;
        }
    }

    private function createAgents(): void
    {
        $names = [
            'varys' => 'Lord Varys',
            'baelish' => 'Petyr Baelish',
            'otto' => 'Otto Hightower',
            'larys' => 'Larys Strong',
            'davos' => 'Davos Seaworth',
            'bronn' => 'Bronn of the Blackwater',
            'margaery' => 'Margaery Tyrell',
            'mysaria_agent' => 'Mysaria of Lys',
        ];

        foreach ($names as $key => $name) {
            $party = $this->person(
                $name,
                $key.'.agent',
                'WESTEROS DEMO DATASET - Property agent.'
            );

            PartyRole::create([
                'party_id' => $party->id,
                'role' => 'agent',
            ]);

            $this->agents[$key] = $party;
        }
    }

    private function createTenants(): void
    {
        $names = [
            'Jon Snow',
            'Daenerys Targaryen',
            'Tyrion Lannister',
            'Arya Stark',
            'Sansa Stark',
            'Bran Stark',
            'Robb Stark',
            'Theon Greyjoy',
            'Samwell Tarly',
            'Gilly',
            'Brienne of Tarth',
            'Jaime Lannister',
            'Cersei Lannister',
            'Sandor Clegane',
            'Tormund Giantsbane',
            'Grey Worm',
            'Missandei',
            'Jorah Mormont',
            'Melisandre',
            'Gendry Baratheon',
            'Rhaenyra Targaryen',
            'Daemon Targaryen',
            'Alicent Hightower',
            'Aemond Targaryen',
            'Helaena Targaryen',
            'Jacaerys Velaryon',
            'Lucerys Velaryon',
            'Baela Targaryen',
            'Rhaena Targaryen',
            'Criston Cole',
            'Harwin Strong',
            'Laena Velaryon',
            'Laenor Velaryon',
            'Corlys Velaryon',
            'Rhaenys Targaryen',
            'Aegon Targaryen',
            'Hugh Hammer',
            'Ulf White',
            'Addam of Hull',
            'Alyn of Hull',
            'Nettles',
            'Lyonel Strong',
            'Pycelle',
            'Qyburn',
            'Podrick Payne',
            'Ygritte',
            'Daario Naharis',
            'Yara Greyjoy',
        ];

        foreach ($names as $index => $name) {
            $key = 'tenant_'.($index + 1);

            $party = $this->person(
                $name,
                $key,
                'WESTEROS DEMO DATASET - Tenant.'
            );

            PartyRole::create([
                'party_id' => $party->id,
                'role' => 'tenant',
            ]);

            $this->tenants[] = $party;
        }
    }

    private function createBuildingsAndUnits(): void
    {
        $definitions = [
            [
                'red_keep',
                'The Red Keep',
                'King\'s Landing',
                ['targaryen' => 60, 'lannister' => 40],
                [
                    'Tower of the Hand',
                    'Maegor Suite',
                    'Blackwater Residence',
                    'Queen\'s Chambers',
                    'Dragon Hall',
                    'Kingsguard Apartment',
                ],
            ],
            [
                'winterfell',
                'Winterfell',
                'The North',
                ['stark' => 100],
                [
                    'Godswood House',
                    'Broken Tower',
                    'Great Keep Suite',
                    'Crypt View',
                    'Wolfswood Apartment',
                    'North Gate Residence',
                ],
            ],
            [
                'dragonstone',
                'Dragonstone',
                'Blackwater Bay',
                ['targaryen' => 75, 'velaryon' => 25],
                [
                    'Dragonmont I',
                    'Dragonmont II',
                    'Painted Table Suite',
                    'Sea View I',
                    'Sea View II',
                    'Stone Drum Residence',
                ],
            ],
            [
                'driftmark',
                'High Tide',
                'Driftmark',
                ['velaryon' => 100],
                [
                    'Sea Snake Suite',
                    'Tide Hall I',
                    'Tide Hall II',
                    'Hull Residence',
                    'Spicetown Apartment',
                ],
            ],
            [
                'casterly',
                'Casterly Rock',
                'Westerlands',
                ['lannister' => 100],
                [
                    'Lion Tower I',
                    'Lion Tower II',
                    'Golden Gallery',
                    'Rock Residence',
                    'Sunset Sea Suite',
                ],
            ],
            [
                'oldtown',
                'Oldtown Citadel',
                'Oldtown',
                ['hightower' => 70, 'tyrell' => 30],
                [
                    'Ravenry I',
                    'Ravenry II',
                    'Archmaester Suite',
                    'Honeywine Residence',
                    'Beacon Apartment',
                ],
            ],
            [
                'storms_end',
                'Storm\'s End',
                'Stormlands',
                ['baratheon' => 100],
                [
                    'Drum Tower I',
                    'Drum Tower II',
                    'Shipbreaker Suite',
                    'Storm Hall',
                    'Rainwood Residence',
                ],
            ],
            [
                'highgarden',
                'Highgarden',
                'The Reach',
                ['tyrell' => 100],
                [
                    'Rose Suite I',
                    'Rose Suite II',
                    'Garden Residence',
                    'Mander View',
                    'Golden Rose Apartment',
                ],
            ],
            [
                'eyrie',
                'The Eyrie',
                'Vale of Arryn',
                ['arryn' => 100],
                [
                    'Moon Door Suite',
                    'Sky Cell Residence',
                    'Falcon Tower I',
                    'Falcon Tower II',
                    'Vale View Apartment',
                ],
            ],
            [
                'sunspear',
                'Sunspear',
                'Dorne',
                ['martell' => 100],
                [
                    'Tower of the Sun',
                    'Water Gardens I',
                    'Water Gardens II',
                    'Sandship Residence',
                    'Dornish Sea Suite',
                ],
            ],
            [
                'riverrun',
                'Riverrun',
                'Riverlands',
                ['tully' => 80, 'stark' => 20],
                [
                    'Red Fork Suite',
                    'Blue Fork Suite',
                    'River Gate Residence',
                    'Trident Apartment',
                    'Water Wheel House',
                ],
            ],
            [
                'harrenhal',
                'Harrenhal',
                'Riverlands',
                ['tully' => 50, 'hightower' => 50],
                [
                    'Tower of Dread',
                    'Kingspyre Residence',
                    'Widow Tower',
                    'Ghost Hall',
                    'Gods Eye Suite',
                    'Strong Tower',
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            [
                $key,
                $name,
                $location,
                $ownership,
                $units,
            ] = $definition;

            $building = Building::create([
                'name' => $name,
                'description' => "Prestigious managed property located in {$location}.",
                'address' => "{$name}, {$location}, Westeros",
                'location' => $location,
                'notes' => 'WESTEROS DEMO DATASET',
            ]);

            foreach ($ownership as $ownerKey => $percentage) {
                BuildingOwner::create([
                    'building_id' => $building->id,
                    'party_id' => $this->owners[$ownerKey]->id,
                    'ownership_percentage' => $percentage,
                ]);
            }

            $createdUnits = [];

            foreach ($units as $unitName) {
                $createdUnits[] = Unit::create([
                    'building_id' => $building->id,
                    'name' => $unitName,
                    'description' => "WESTEROS DEMO DATASET - {$unitName} at {$name}.",
                ]);
            }

            $this->buildings[$key] = [
                'building' => $building,
                'units' => $createdUnits,
            ];
        }
    }

    private function createLeases(): void
    {
        /*
         * Deliberately leave a meaningful number of Units vacant.
         *
         * The first 44 units receive Leases. Remaining Units stay vacant,
         * giving Dashboard occupancy figures something useful to display.
         */
        $allUnits = collect($this->buildings)
            ->flatMap(fn (array $entry) => $entry['units'])
            ->values();

        $frequencies = [
            'monthly',
            'monthly',
            'monthly',
            'monthly',
            'quarterly',
            'quarterly',
            'bi_yearly',
            'yearly',
        ];

        $agents = array_values($this->agents);

        $today = Carbon::today();

        foreach ($allUnits->take(44) as $index => $unit) {
            $tenant = $this->tenants[$index];

            /*
             * Spread lease beginnings across the preceding year.
             *
             * This creates useful historical invoice/payment data while
             * keeping the number of generated records manageable.
             */
            $monthsAgo = 2 + ($index % 11);

            $startDate = $today
                ->copy()
                ->subMonthsNoOverflow($monthsAgo)
                ->startOfMonth()
                ->addDays($index % 18);

            $frequency =
                $frequencies[$index % count($frequencies)];

            $rent =
                2500
                + (($index % 10) * 750)
                + ((int) floor($index / 10) * 500);

            $vatRate =
                match ($index % 5) {
                    0 => 0,
                    1 => 15,
                    default => 18,
                };

            $managementType =
                match ($index % 4) {
                    0 => 'percentage',
                    1 => 'fixed',
                    default => 'none',
                };

            $managementValue =
                match ($managementType) {
                    'percentage' => 8,
                    'fixed' => 350,
                    default => 0,
                };

            $agent =
                ($index % 3 === 0)
                    ? $agents[$index % count($agents)]
                    : null;

            /*
             * Every sixth lease has a contractual advance.
             *
             * Some of these are actually recorded as historically received
             * through LeaseInitializationService.
             */
            $hasAdvance = $index % 6 === 0;

            $advanceAmount =
                $hasAdvance
                    ? $rent * 6
                    : 0;

            $reserveAmount =
                $hasAdvance
                    ? $rent * 2
                    : 0;

            $securityDeposit =
                ($index % 4 === 0)
                    ? $rent * 2
                    : $rent;

            $lease = Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'agent_id' => $agent?->id,

                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate
                    ->copy()
                    ->addYears(2)
                    ->subDay()
                    ->toDateString(),

                'status' => 'active',

                'rent_amount' => $rent,
                'payment_frequency' => $frequency,

                /*
                 * Mix default contractual due dates with explicit overrides.
                 */
                'due_day' => $index % 4 === 0
                        ? 1
                        : (
                            $index % 4 === 1
                                ? 5
                                : null
                        ),

                'vat_rate' => $vatRate,
                'proration_amount' => $index % 13 === 0
                        ? 0
                        : null,

                'security_deposit_amount' => $securityDeposit,

                'advance_payment_amount' => $advanceAmount,

                'rent_reserve_amount' => $reserveAmount,

                'rent_increment_type' => $index % 9 === 0
                        ? 'percentage'
                        : 'none',

                'rent_increment_value' => $index % 9 === 0
                        ? 7.5
                        : 0,

                'next_rent_increment_date' => $index % 9 === 0
                        ? $today
                            ->copy()
                            ->addMonths(4 + ($index % 4))
                            ->toDateString()
                        : null,

                'management_fee_type' => $managementType,

                'management_fee_value' => $managementValue,

                'agent_commission_amount' => $agent !== null
                        ? (int) round($rent * 0.5)
                        : 0,

                'notes' => 'WESTEROS DEMO DATASET - Rich operational test Lease.',
            ]);

            /*
             * Use the real Lease initialization service.
             *
             * It generates every due historical rent Invoice and, where
             * requested, reconstructs opening advance money correctly.
             */
            $openingData = [];

            if ($hasAdvance && $index % 12 === 0) {
                $openingData = [
                    'advance_received' => true,
                    'advance_received_date' => $startDate->toDateString(),
                    'advance_received_method' => 'bank_transfer',
                    'advance_received_reference' => sprintf(
                        'WEST-ADV-%04d',
                        $index + 1
                    ),
                ];
            }

            app(LeaseInitializationService::class)
                ->initialize(
                    $lease,
                    $openingData,
                    $today
                );

            /*
             * Now generate deliberately different payment positions.
             *
             * 0 = fully paid
             * 1 = partially paid
             * 2 = unpaid
             * 3 = fully paid
             * 4 = overpaid
             *
             * Opening-advance leases are left alone because their cash was
             * already processed by LeaseInitializationService.
             */
            if (! ($hasAdvance && $index % 12 === 0)) {
                $this->createPaymentScenario(
                    $lease,
                    $index
                );
            }
        }

        /*
         * Add a few draft leases to populate lifecycle views without
         * affecting occupancy or generating invoices.
         */
        foreach (
            $allUnits->slice(44, 4)->values() as $offset => $unit
        ) {
            $tenantIndex = 44 + $offset;

            if (! isset($this->tenants[$tenantIndex])) {
                break;
            }

            Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $this->tenants[$tenantIndex]->id,
                'agent_id' => $agents[$offset % count($agents)]->id,
                'start_date' => $today->copy()->addMonth()->toDateString(),
                'end_date' => $today->copy()->addYears(2)->toDateString(),
                'status' => 'draft',
                'rent_amount' => 5000 + ($offset * 1000),
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 18,
                'security_deposit_amount' => 5000 + ($offset * 1000),
                'advance_payment_amount' => 0,
                'rent_reserve_amount' => 0,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'management_fee_type' => 'percentage',
                'management_fee_value' => 10,
                'agent_commission_amount' => 1500,
                'notes' => 'WESTEROS DEMO DATASET - Future draft Lease.',
            ]);
        }
    }

    private function createPaymentScenario(
        Lease $lease,
        int $index
    ): void {
        $lease->refresh();

        $invoices = $lease->invoices()
            ->whereIn('status', ['issued', 'partial'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $outstanding =
            (int) $invoices->sum(
                fn ($invoice) => $invoice->outstandingAmount()
            );

        if ($outstanding <= 0) {
            return;
        }

        $scenario = $index % 5;

        $amount = match ($scenario) {
            /*
             * Full settlement.
             */
            0, 3 => $outstanding,

            /*
             * Approximately half paid.
             */
            1 => max(
                1,
                (int) round($outstanding * 0.5)
            ),

            /*
             * Completely unpaid.
             */
            2 => 0,

            /*
             * Overpayment leaves unapplied tenant cash.
             */
            4 => $outstanding + $lease->rent_amount,

            default => 0,
        };

        if ($amount <= 0) {
            return;
        }

        $methods = [
            'bank_transfer',
            'momo',
            'cash',
        ];

        $method =
            $methods[$index % count($methods)];

        $paymentDate =
            Carbon::today()
                ->subDays(
                    2 + ($index % 70)
                );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => $amount,
            'payment_date' => $paymentDate->toDateString(),
            'payment_method' => $method,
            'reference' => sprintf(
                'WEST-PAY-%06d',
                $this->paymentSequence++
            ),
            'collector_name' => $method === 'cash'
                    ? 'Ser Bronn - Demo Collector'
                    : null,
            'notes' => 'WESTEROS DEMO DATASET - Tenant rent payment.',
            'is_opening_advance' => false,
        ]);

        /*
         * This is critical:
         *
         * Do NOT manually create PaymentAllocation records here.
         * The real service performs FIFO and generates the corresponding
         * owner rent entitlement and management-fee accounting.
         */
        app(PaymentAllocationService::class)
            ->allocate($payment);
    }

    private function person(
        string $name,
        string $emailKey,
        string $notes
    ): Party {
        return Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => $this->nextPhone(),
            'alternate_phone' => $this->nextPhone(),
            'email' => strtolower($emailKey)
                .'@westeros.patrimoine.test',
            'address' => 'Westeros',
            'id_number' => 'WEST-ID-'.strtoupper(
                str_replace(
                    ['.', '_', ' '],
                    '-',
                    $emailKey
                )
            ),
            'notes' => $notes,
        ]);
    }

    private function nextPhone(): string
    {
        return '0'.($this->phoneSequence++);
    }
}

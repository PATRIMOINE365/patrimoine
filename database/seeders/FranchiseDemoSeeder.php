<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Lease;
use App\Models\OwnerExpense;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\LeaseInitializationService;
use App\Services\OwnerAccountingService;
use App\Services\PaymentAllocationService;
use App\Services\RentIncrementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * FRANCHISE DEMO DATASET (v1.0.6)
 *
 * Large, varied test dataset drawn from Game of Thrones, House of the
 * Dragon, The Lord of the Rings and Harry Potter:
 *
 *   100 tenants  ·  60 owners (45 people, 12 organisations, 3 associations)
 *   8 agents     ·  67 buildings  ·  exactly 1,000 units  ·  800 leases
 *   (450 active, 60 in notice, 200 terminated, 90 draft)
 *
 * Financial history is produced by the REAL services — invoice generation,
 * FIFO payment allocation, owner rent entitlement, management fees, opening
 * advances, tenant funds and expense allocation — never by hand-written
 * ledger rows, so every derived balance in the app is genuine.
 *
 * Deterministic by construction (index-based variety, no randomness), and
 * refuses to run twice.
 *
 *   php artisan db:seed --class=FranchiseDemoSeeder --force
 */
class FranchiseDemoSeeder extends Seeder
{
    private const MARKER = 'FRANCHISE DEMO DATASET';

    private int $phoneSequence = 240000001;

    private int $paymentSequence = 1;

    /** @var array<int, Party> */
    private array $tenants = [];

    /** @var array<int, Party> */
    private array $owners = [];

    /** @var array<int, Party> */
    private array $agents = [];

    public function run(): void
    {
        if (
            Party::query()
                ->where('notes', 'like', '%'.self::MARKER.'%')
                ->exists()
        ) {
            throw new RuntimeException(
                'The franchise demo dataset is already seeded. Refusing to duplicate it.'
            );
        }

        $today = Carbon::today();

        $this->createTenants();
        $this->createOwners();
        $this->createAgents();

        $units = $this->createBuildingsAndUnits();

        $this->createLeases($units, $today);

        $this->createOwnerExpenses($today);

        $this->command?->info(sprintf(
            'Franchise demo seeded: %d parties, %d buildings, %d units, %d leases, %d payments.',
            Party::where('notes', 'like', '%'.self::MARKER.'%')->count(),
            Building::count(),
            Unit::count(),
            Lease::count(),
            Payment::count(),
        ));
    }

    /* ---------------------------------------------------------------------
     * Parties
     * ------------------------------------------------------------------ */

    private function createTenants(): void
    {
        $names = [
            // Game of Thrones (35)
            'Jon Snow', 'Arya Stark', 'Sansa Stark', 'Bran Stark',
            'Rickon Stark', 'Robb Stark', 'Theon Greyjoy', 'Yara Greyjoy',
            'Samwell Tarly', 'Gilly', 'Davos Seaworth', 'Gendry Waters',
            'Sandor Clegane', 'Podrick Payne', 'Tormund Giantsbane',
            'Ygritte', 'Osha', 'Shae', 'Missandei', 'Grey Worm',
            'Daario Naharis', 'Jorah Mormont', 'Lyanna Mormont',
            'Meera Reed', 'Jojen Reed', 'Edmure Tully', 'Brynden Tully',
            'Beric Dondarrion', 'Thoros of Myr', 'Hot Pie',
            'Lommy Greenhands', 'Jaqen Hghar', 'Syrio Forel',
            'Salladhor Saan', 'Alys Karstark',

            // House of the Dragon (15)
            'Rhaenyra Targaryen', 'Daemon Targaryen', 'Alicent Hightower',
            'Criston Cole', 'Harwin Strong', 'Larys Strong',
            'Jacaerys Velaryon', 'Lucerys Velaryon', 'Baela Targaryen',
            'Rhaena Targaryen', 'Mysaria of Lys', 'Laenor Velaryon',
            'Laena Velaryon', 'Erryk Cargyll', 'Arryk Cargyll',

            // The Lord of the Rings (25)
            'Frodo Baggins', 'Samwise Gamgee', 'Meriadoc Brandybuck',
            'Peregrin Took', 'Bilbo Baggins', 'Rosie Cotton',
            'Fredegar Bolger', 'Hamfast Gamgee', 'Lobelia Sackville-Baggins',
            'Barliman Butterbur', 'Eowyn of Rohan', 'Eomer Eadig',
            'Faramir of Gondor', 'Boromir of Gondor', 'Arwen Undomiel',
            'Legolas Greenleaf', 'Gimli Lockbearer', 'Haldir of Lorien',
            'Glorfindel', 'Grima Wormtongue', 'Beregond of the Guard',
            'Bard Bowman', 'Sigrid Bowman', 'Tilda Bowman', 'Bain Bowman',

            // Harry Potter (25)
            'Harry Potter', 'Hermione Granger', 'Ron Weasley',
            'Ginny Weasley', 'Neville Longbottom', 'Luna Lovegood',
            'Dean Thomas', 'Seamus Finnigan', 'Cho Chang', 'Cedric Diggory',
            'Hannah Abbott', 'Susan Bones', 'Ernie Macmillan',
            'Justin Finch-Fletchley', 'Padma Patil', 'Parvati Patil',
            'Lavender Brown', 'Oliver Wood', 'Angelina Johnson',
            'Katie Bell', 'Lee Jordan', 'Colin Creevey', 'Romilda Vane',
            'Blaise Zabini', 'Pansy Parkinson',
        ];

        foreach ($names as $index => $name) {
            /*
             * Every 11th tenant deliberately has no email so the "tenant
             * has no email address" delivery paths can be exercised.
             */
            $tenant = Party::create([
                'type' => $index % 10 === 7 ? 'organisation' : 'person',
                'name' => $name,
                'phone' => $this->nextPhone(),
                'alternate_phone' => $index % 3 === 0 ? $this->nextPhone() : null,
                'email' => $index % 11 === 10
                    ? null
                    : $this->emailFor($name),
                'address' => $this->addressFor($index),
                'id_number' => sprintf('FD-TEN-%04d', $index + 1),
                'notes' => self::MARKER.' - Tenant.',
            ]);

            $tenant->roles()->create(['role' => 'tenant']);

            $this->tenants[] = $tenant;
        }
    }

    private function createOwners(): void
    {
        $people = [
            'Tywin Lannister', 'Olenna Tyrell', 'Mace Tyrell',
            'Doran Martell', 'Oberyn Martell', 'Walder Frey',
            'Roose Bolton', 'Petyr Baelish', 'Varys', 'Stannis Baratheon',
            'Renly Baratheon', 'Euron Greyjoy', 'Balon Greyjoy',
            'Randyll Tarly', 'Yohn Royce', 'Anya Waynwood',
            'Wyman Manderly', 'Robett Glover', 'Otto Hightower',
            'Corlys Velaryon', 'Rhaenys Targaryen', 'Viserys Targaryen',
            'Aemond Targaryen', 'Aegon Targaryen', 'Lyonel Strong',
            'Elrond Halfelven', 'Galadriel of Lorien', 'Celeborn of Doriath',
            'Theoden Ednew', 'Denethor of Gondor', 'Thranduil Oropherion',
            'Thorin Oakenshield', 'Dain Ironfoot', 'Otho Sackville-Baggins',
            'Tom Bombadil', 'Lucius Malfoy', 'Narcissa Malfoy',
            'Horace Slughorn', 'Garrick Ollivander', 'Madam Rosmerta',
            'Aberforth Dumbledore', 'Xenophilius Lovegood', 'Amelia Bones',
            'Augusta Longbottom', 'Andromeda Tonks',
        ];

        $organisations = [
            'Iron Bank of Braavos', 'House Lannister Holdings',
            'Hightower Property Group', 'Velaryon Maritime Holdings',
            'House Tyrell Estates', 'Gringotts Wizarding Bank',
            'Weasleys Wizard Wheezes Ltd', 'Daily Prophet Properties',
            'Mithril Ventures', 'Erebor Mining Company',
            'Prancing Pony Hospitality', 'Dragonstone Trust',
        ];

        $associations = [
            'Nights Watch Brotherhood',
            'Order of the Phoenix Cooperative',
            'Fellowship Housing Association',
        ];

        $index = 0;

        foreach ($people as $name) {
            $this->owners[] = $this->owner($name, 'person', $index++);
        }

        foreach ($organisations as $name) {
            $this->owners[] = $this->owner($name, 'organisation', $index++);
        }

        foreach ($associations as $name) {
            $this->owners[] = $this->owner($name, 'association', $index++);
        }
    }

    private function owner(
        string $name,
        string $type,
        int $index
    ): Party {
        $party = Party::create([
            'type' => $type,
            'name' => $name,
            'legal_name' => $type === 'person' ? null : $name,
            'phone' => $this->nextPhone(),
            'email' => $this->emailFor($name),
            'address' => $this->addressFor($index),

            // Non-person owners carry a contact person and registration.
            'contact_person_name' => $type === 'person'
                ? null
                : 'Steward '.($index + 1),
            'contact_person_phone' => $type === 'person'
                ? null
                : $this->nextPhone(),
            'registration_number' => $type === 'person'
                ? null
                : sprintf('FD-REG-%04d', $index + 1),
            'vat_tin' => $index % 4 === 0
                ? sprintf('FD-TIN-%06d', $index + 1)
                : null,

            // Roughly half the owners have payout banking details on file.
            'bank_name' => $index % 2 === 0 ? 'Iron Bank of Braavos' : null,
            'bank_account_name' => $index % 2 === 0 ? $name : null,
            'bank_account_number' => $index % 2 === 0
                ? sprintf('0011%08d', $index + 1)
                : null,
            'bank_branch' => $index % 2 === 0 ? 'Braavos Main' : null,

            'id_number' => sprintf('FD-OWN-%04d', $index + 1),
            'notes' => self::MARKER.' - Owner.',
        ]);

        $party->roles()->create(['role' => 'owner']);

        return $party;
    }

    private function createAgents(): void
    {
        $names = [
            'Bronn of the Blackwater', 'Mundungus Fletcher', 'Rita Skeeter',
            'Nob of Bree', 'Jeyne Poole', 'Marillion the Bard',
            'Dedalus Diggle', 'Radagast the Brown',
        ];

        foreach ($names as $index => $name) {
            $agent = Party::create([
                'type' => 'person',
                'name' => $name,
                'phone' => $this->nextPhone(),
                'email' => $this->emailFor($name),
                'address' => $this->addressFor($index),
                'id_number' => sprintf('FD-AGT-%04d', $index + 1),
                'notes' => self::MARKER.' - Letting agent.',
            ]);

            $agent->roles()->create(['role' => 'agent']);

            $this->agents[] = $agent;
        }
    }

    /* ---------------------------------------------------------------------
     * Buildings and units
     * ------------------------------------------------------------------ */

    /**
     * 67 buildings whose unit counts sum to exactly 1,000.
     *
     * @return array<int, Unit>
     */
    private function createBuildingsAndUnits(): array
    {
        $buildings = [
            // Game of Thrones (482 units)
            ['Winterfell Keep', 'The North', 36],
            ['The Red Keep', "King's Landing", 44],
            ['Casterly Rock', 'The Westerlands', 40],
            ['Highgarden Residences', 'The Reach', 32],
            ["Storm's End Court", 'The Stormlands', 24],
            ['The Eyrie Heights', 'The Vale', 18],
            ['Riverrun Terrace', 'The Riverlands', 22],
            ['Dragonstone Citadel', 'Blackwater Bay', 28],
            ['Harrenhal Yards', 'The Riverlands', 30],
            ['Sunspear Gardens', 'Dorne', 20],
            ['Pyke Tower', 'The Iron Islands', 12],
            ['Oldtown Court', 'The Reach', 26],
            ["King's Landing Heights", "King's Landing", 38],
            ['Braavos Waterfront', 'Braavos', 24],
            ['White Harbor Quays', 'The North', 16],
            ['Castle Black Lodge', 'The Wall', 8],
            ['Horn Hill Villas', 'The Reach', 6],
            ['Bear Island Cabin', 'The North', 1],
            ['Tarth Sapphire House', 'The Stormlands', 1],
            ['The Twins Crossing', 'The Riverlands', 14],
            ['Dreadfort Annex', 'The North', 9],
            ['Karhold Court', 'The North', 7],
            ['Deepwood Motte', 'The North', 5],
            ['Volantis Riverside', 'Volantis', 21],

            // The Lord of the Rings (353 units)
            ['Bag End Terrace', 'Hobbiton', 4],
            ['Rivendell Residences', 'Rivendell', 26],
            ['Minas Tirith Towers', 'Gondor', 42],
            ['Edoras Lodge', 'Rohan', 12],
            ["Helm's Deep Court", 'Rohan', 18],
            ['Isengard Tower', 'Nan Curunir', 15],
            ['Lothlorien Gardens', 'Lorien', 20],
            ['Bree Crossing Inn', 'Bree', 10],
            ['Hobbiton Row', 'The Shire', 16],
            ['Erebor Heights', 'The Lonely Mountain', 34],
            ['Dale Riverside', 'Dale', 22],
            ['Grey Havens Marina', 'Lindon', 14],
            ['Osgiliath Court', 'Gondor', 19],
            ['Michel Delving Mews', 'The Shire', 8],
            ['Buckland Hall', 'Buckland', 6],
            ['Weathertop Point', 'Eriador', 1],
            ['Rohan Plains Estate', 'Rohan', 11],
            ['Gondor Gate Apartments', 'Gondor', 28],
            ['Mirkwood Glade', 'Mirkwood', 13],
            ['Laketown Piers', 'Esgaroth', 17],
            ['Moria Deep Lofts', 'Khazad-dum', 9],
            ['Fangorn Edge', 'Fangorn', 3],
            ['Grey Company House', 'Eriador', 5],

            // Harry Potter (165 units)
            ['Hogwarts Court', 'Scottish Highlands', 30],
            ['Hogsmeade Terrace', 'Hogsmeade', 14],
            ['Diagon Alley Chambers', 'London', 24],
            ['Grimmauld Place', 'London', 12],
            ["Godric's Hollow Cottages", "Godric's Hollow", 8],
            ['The Burrow Annex', 'Ottery St Catchpole', 5],
            ['Malfoy Manor', 'Wiltshire', 1],
            ['Shell Cottage', 'Tinworth', 1],
            ["Spinner's End Row", 'Cokeworth', 7],
            ['Privet Close', 'Little Whinging', 10],
            ['Ministry Atrium Offices', 'London', 16],
            ['Beauxbatons Villas', 'Pyrenees', 9],
            ['Durmstrang Hall', 'The Far North', 6],
            ['Ottery St Catchpole Mews', 'Devon', 4],
            ['Knockturn Alley Units', 'London', 5],
            ["Hog's Head Rooms", 'Hogsmeade', 3],
            ['Leaky Cauldron Lodgings', 'London', 6],
            ['Nurmengard Keep', 'The Alps', 1],
            ['Azkaban Point', 'The North Sea', 1],
            ['Flourish and Blotts House', 'London', 2],
        ];

        /*
         * Ownership split patterns, cycled per building. Percentages always
         * total 100 — the domain invariant OwnerAccountingService enforces.
         */
        $splits = [
            [100.00],
            [50.00, 50.00],
            [60.00, 40.00],
            [100.00],
            [70.00, 30.00],
            [40.00, 30.00, 30.00],
            [100.00],
            [80.00, 20.00],
            [50.00, 25.00, 25.00],
            [40.00, 30.00, 20.00, 10.00],
            [100.00],
            [25.00, 25.00, 25.00, 25.00],
        ];

        $unitFormats = [
            'Apartment %d', 'Suite %d', 'Flat %d', 'Chamber %d',
            'Room %d', 'Shop %d', 'Loft %d', 'Wing %d',
        ];

        $units = [];
        $ownerCursor = 0;

        foreach ($buildings as $bIndex => [$name, $location, $unitCount]) {
            $building = Building::create([
                'name' => $name,
                'description' => 'Demo property from the franchise dataset.',
                'address' => $name.', '.$location,
                'location' => $location,
                'notes' => self::MARKER,
            ]);

            /*
             * Assign owners round-robin so all 60 owners hold property and
             * many hold several buildings (consolidated ledger testing).
             */
            foreach ($splits[$bIndex % count($splits)] as $percentage) {
                BuildingOwner::create([
                    'building_id' => $building->id,
                    'party_id' => $this->owners[$ownerCursor % count($this->owners)]->id,
                    'ownership_percentage' => $percentage,
                ]);

                $ownerCursor++;
            }

            $format = $unitFormats[$bIndex % count($unitFormats)];

            for ($u = 1; $u <= $unitCount; $u++) {
                $units[] = Unit::create([
                    'building_id' => $building->id,
                    'name' => sprintf($format, $u),
                    'description' => $u % 7 === 0
                        ? 'Corner unit with private terrace.'
                        : null,
                ]);
            }
        }

        return $units;
    }

    /* ---------------------------------------------------------------------
     * Leases
     * ------------------------------------------------------------------ */

    /**
     * 800 leases: 450 active, 60 in notice, 200 terminated, 90 draft.
     *
     * Active and notice leases each occupy a DISTINCT unit (0..509) to
     * respect the one-active-lease-per-unit rule. Terminated leases are
     * historical and may share units with current occupancy.
     *
     * @param array<int, Unit> $units
     */
    private function createLeases(array $units, Carbon $today): void
    {
        $frequencies = ['monthly', 'monthly', 'monthly', 'monthly', 'monthly', 'monthly', 'monthly', 'quarterly', 'quarterly', 'bi_yearly', 'yearly'];
        $rents = [400, 650, 900, 1200, 1500, 1800, 2200, 2600, 3000, 3500, 4200, 5000, 6500, 8000, 10000, 15000];
        $vatRates = [18.00, 18.00, 18.00, 18.00, 0.00, 15.00];

        $initializer = app(LeaseInitializationService::class);
        $increments = app(RentIncrementService::class);

        // ----- 450 active + 60 notice (units 0..509) --------------------
        for ($i = 0; $i < 510; $i++) {
            $isNotice = $i >= 450;

            /*
             * Every 15th lease is older than a year and quarterly, so a
             * rent increment can legally be scheduled on it (the service
             * enforces a 12-month minimum interval from the start date).
             */
            $incrementCandidate = $i % 15 === 0;

            $startDate = $incrementCandidate
                ? $today->copy()->subMonths(15)->subDays($i % 20)
                : $today->copy()->subMonths(2 + ($i % 9))->subDays($i % 25);

            $frequency = $incrementCandidate
                ? 'quarterly'
                : $frequencies[$i % count($frequencies)];

            $rent = $rents[$i % count($rents)];

            $hasAdvance = $i % 6 === 0;
            $advance = $hasAdvance ? $rent * (4 + ($i % 4)) : 0;
            $reserve = $hasAdvance ? $rent * 2 : 0;

            [$feeType, $feeValue] = match ($i % 5) {
                0, 1 => ['percentage', 10.00],
                2 => ['percentage', 5.00],
                3 => ['fixed', (int) round($rent * 0.08)],
                default => ['none', 0],
            };

            $agent = $i % 4 === 0
                ? $this->agents[$i % count($this->agents)]
                : null;

            $lease = Lease::create([
                'unit_id' => $units[$i]->id,
                'tenant_id' => $this->tenants[$i % count($this->tenants)]->id,
                'agent_id' => $agent?->id,

                'start_date' => $startDate->toDateString(),
                // A third of leases are open-ended.
                'end_date' => $i % 3 === 0
                    ? null
                    : $startDate->copy()->addYears(1 + ($i % 3))->subDay()->toDateString(),

                'status' => $isNotice ? 'notice' : 'active',
                'termination_notice_date' => $isNotice
                    ? $today->copy()->subDays(5 + ($i % 40))->toDateString()
                    : null,

                'rent_amount' => $rent,
                'payment_frequency' => $frequency,
                'due_day' => match ($i % 5) {
                    0 => 1,
                    1 => 5,
                    2 => 15,
                    default => null,
                },
                'vat_rate' => $vatRates[$i % count($vatRates)],
                'proration_amount' => $i % 17 === 0 ? 0 : null,

                'security_deposit_amount' => $rent * ($i % 4 === 0 ? 2 : 1),
                'advance_payment_amount' => $advance,
                'rent_reserve_amount' => $reserve,

                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'next_rent_increment_date' => null,

                'management_fee_type' => $feeType,
                'management_fee_value' => $feeValue,
                'agent_commission_amount' => $agent !== null
                    ? (int) round($rent * 0.5)
                    : 0,

                'notes' => self::MARKER.' - Operational lease.',
            ]);

            /*
             * Reconstruct opening advance money for half the advance
             * leases; the initializer then funds the rent reserve, settles
             * historical invoices FIFO and banks the surplus as consumable
             * advance — all through the real engine.
             */
            $openingData = [];

            if ($hasAdvance && $i % 12 === 0) {
                $openingData = [
                    'advance_received' => true,
                    'advance_received_date' => $startDate->toDateString(),
                    'advance_received_method' => ['bank_transfer', 'momo', 'cash'][$i % 3],
                    'advance_received_reference' => sprintf('FD-ADV-%04d', $i + 1),
                ];

                if ($openingData['advance_received_method'] === 'cash') {
                    $openingData['advance_received_collector'] = 'Bronn of the Blackwater';
                }
            }

            $initializer->initialize($lease, $openingData, $today);

            if (! ($hasAdvance && $i % 12 === 0)) {
                $this->createPaymentScenario($lease, $i, $today);
            }

            if ($incrementCandidate) {
                $increments->schedule(
                    $lease,
                    $i % 30 === 0 ? 'fixed' : 'percentage',
                    $i % 30 === 0 ? (int) round($rent * 0.1) : 8.0,
                    $today->copy()->addMonths(1 + ($i % 3))->toDateString(),
                );
            }
        }

        // ----- 200 terminated (historical, varied unit reuse) ------------
        for ($i = 0; $i < 200; $i++) {
            $start = $today->copy()->subMonths(18 + ($i % 18));
            $end = $start->copy()->addMonths(6 + ($i % 10));
            $rent = $rents[($i + 3) % count($rents)];

            Lease::create([
                'unit_id' => $units[($i * 5) % 1000]->id,
                'tenant_id' => $this->tenants[($i + 37) % count($this->tenants)]->id,
                'agent_id' => null,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'terminated',
                'termination_notice_date' => $end->copy()->subMonths(2)->toDateString(),
                'rent_amount' => $rent,
                'payment_frequency' => $i % 2 === 0 ? 'monthly' : 'quarterly',
                'due_day' => null,
                'vat_rate' => 18.00,
                'proration_amount' => null,
                'security_deposit_amount' => $i % 3 === 0 ? $rent * 2 : $rent,
                'advance_payment_amount' => 0,
                'rent_reserve_amount' => 0,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'next_rent_increment_date' => null,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
                'notes' => self::MARKER.' - Historical terminated lease.',
            ]);
        }

        // ----- 90 draft ---------------------------------------------------
        for ($i = 0; $i < 90; $i++) {
            $start = $today->copy()->addDays(7 + ($i % 60));
            $rent = $rents[($i + 7) % count($rents)];

            Lease::create([
                'unit_id' => $units[510 + ($i * 5) % 490]->id,
                'tenant_id' => $this->tenants[($i + 61) % count($this->tenants)]->id,
                'agent_id' => $i % 5 === 0
                    ? $this->agents[$i % count($this->agents)]->id
                    : null,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addYears(2)->subDay()->toDateString(),
                'status' => 'draft',
                'rent_amount' => $rent,
                'payment_frequency' => $i % 3 === 0 ? 'quarterly' : 'monthly',
                'due_day' => $i % 4 === 0 ? 1 : null,
                'vat_rate' => 18.00,
                'proration_amount' => null,
                'security_deposit_amount' => $rent,
                'advance_payment_amount' => $i % 6 === 0 ? $rent * 4 : 0,
                'rent_reserve_amount' => $i % 6 === 0 ? $rent : 0,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'next_rent_increment_date' => null,
                'management_fee_type' => $i % 2 === 0 ? 'percentage' : 'none',
                'management_fee_value' => $i % 2 === 0 ? 10.00 : 0,
                'agent_commission_amount' => $i % 5 === 0
                    ? (int) round($rent * 0.5)
                    : 0,
                'notes' => self::MARKER.' - Draft lease pending activation.',
            ]);
        }
    }

    /**
     * Varied payment positions, allocated by the real FIFO service:
     *
     *   0,3 fully paid · 1 half paid · 2 unpaid · 4 overpaid (unapplied
     *   tenant cash available for fund classification in the UI).
     */
    private function createPaymentScenario(
        Lease $lease,
        int $index,
        Carbon $today
    ): void {
        $outstanding = $lease
            ->invoices()
            ->whereIn('status', ['issued', 'partial'])
            ->get()
            ->sum(fn ($invoice) => $invoice->outstandingAmount());

        if ($outstanding <= 0) {
            return;
        }

        $amount = match ($index % 5) {
            0, 3 => $outstanding,
            1 => max(1, (int) round($outstanding * 0.5)),
            2 => 0,
            4 => $outstanding + $lease->rent_amount,
            default => 0,
        };

        if ($amount <= 0) {
            return;
        }

        $method = ['bank_transfer', 'momo', 'cash'][$index % 3];

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => $amount,
            'payment_date' => $today->copy()->subDays(1 + ($index % 75))->toDateString(),
            'payment_method' => $method,
            'reference' => sprintf('FD-PAY-%06d', $this->paymentSequence++),
            'collector_name' => $method === 'cash'
                ? 'Bronn of the Blackwater'
                : null,
            'notes' => self::MARKER.' - Tenant rent payment.',
            'is_opening_advance' => false,
        ]);

        // FIFO allocation + owner entitlement + management fees, for real.
        app(PaymentAllocationService::class)->allocate($payment);
    }

    /* ---------------------------------------------------------------------
     * Owner-side activity
     * ------------------------------------------------------------------ */

    /**
     * Property expenses on the first 30 buildings, allocated across their
     * owners by the real accounting service so ledgers show debits too.
     */
    private function createOwnerExpenses(Carbon $today): void
    {
        $service = app(OwnerAccountingService::class);

        $descriptions = [
            'Roof repairs after storm damage',
            'Borehole pump replacement',
            'Compound wall repainting',
            'Security lighting installation',
            'Plumbing maintenance',
            'Generator servicing',
        ];

        foreach (Building::query()->orderBy('id')->limit(30)->get() as $index => $building) {
            $expense = OwnerExpense::create([
                'building_id' => $building->id,
                'unit_id' => null,
                'description' => $descriptions[$index % count($descriptions)],
                'amount' => 300 + ($index * 85),
                'expense_date' => $today->copy()->subDays(3 + ($index * 2))->toDateString(),
                'reference' => sprintf('FD-EXP-%04d', $index + 1),
                'notes' => self::MARKER,
            ]);

            $service->allocateExpense($expense);
        }
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    private function emailFor(string $name): string
    {
        return strtolower(
            preg_replace('/[^a-z0-9]+/i', '.', trim($name))
        ).'@demo.patrimoine.test';
    }

    private function addressFor(int $index): string
    {
        $streets = [
            'Kingsroad', 'Steel Street', 'River Row', 'The Street of Silk',
            'Bagshot Row', 'Rath Dinen', 'Diagon Alley', 'Charing Cross Road',
            'High Street Hogsmeade', 'Silverfish Lane',
        ];

        return sprintf(
            'No. %d %s',
            1 + ($index % 90),
            $streets[$index % count($streets)]
        );
    }

    private function nextPhone(): string
    {
        return '0'.($this->phoneSequence++);
    }
}

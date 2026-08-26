<?php

namespace Database\Seeders;

use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Lease;
use App\Models\License;
use App\Models\Organisation;
use App\Models\OwnerExpense;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use App\Services\LeaseInitializationService;
use App\Services\OwnerAccountingService;
use App\Services\PaymentAllocationService;
use App\Services\RegistrationService;
use App\Services\RentIncrementService;
use App\Support\OrganisationContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * SHOWCASE DATASET — the organisation used for marketing screenshots.
 *
 * A small but financially complete letting agency: enough parties,
 * buildings, leases, payments and expenses that every screen looks like a
 * working business, without the volume of the franchise dataset.
 *
 * Everything here is invented. Personal names are common given/family
 * names from Ghana, Togo and Nigeria; company, building and street names
 * are made up; email domains use the RFC 2606 ".example" reserved TLD so
 * they can never reach a real mailbox. Nothing is borrowed from a
 * copyrighted work or a real business — these images are published on
 * patrimoine365.com.
 *
 * Financial history is produced by the real services (lease
 * initialization, FIFO allocation, owner accounting), so every balance
 * on screen is genuinely derived rather than typed.
 *
 *   php artisan db:seed --class=ShowcaseDemoSeeder --force
 */
class ShowcaseDemoSeeder extends Seeder
{
    /*
     * Two editions exist, one per language, because journal descriptions
     * are written in the organisation's language and frozen at posting
     * time. Set SHOWCASE_NAME / SHOWCASE_LANG / SHOWCASE_CURRENCY to pick.
     */
    private function organisationName(): string
    {
        return (string) (env('SHOWCASE_NAME') ?: 'Akwaba Property Management');
    }

    private function language(): string
    {
        return env('SHOWCASE_LANG') === 'fr' ? 'fr' : 'en';
    }

    private function currency(): string
    {
        return env('SHOWCASE_CURRENCY') === 'FCFA' ? 'FCFA' : 'GHS';
    }

    /**
     * User email addresses are unique across the whole platform, so the
     * two editions need distinct sign-ins.
     */
    private function adminEmail(): string
    {
        return $this->language() === 'fr'
            ? 'ama.mensah.fr@akwaba.example'
            : 'ama.mensah@akwaba.example';
    }

    private const MARKER = 'SHOWCASE DATASET';

    private int $phoneSequence = 1;

    private int $paymentSequence = 1;

    /** @var array<int, Party> */
    private array $tenants = [];

    /** @var array<int, Party> */
    private array $owners = [];

    /** @var array<int, Party> */
    private array $agents = [];

    public function run(): void
    {
        if (Organisation::query()->where('name', $this->organisationName())->exists()) {
            throw new RuntimeException(
                'The showcase organisation already exists. Refusing to duplicate it.'
            );
        }

        /*
         * Registration sends a verification email and a signup alert.
         * Route mail to the array transport for the duration of the seed
         * so nothing leaves the server.
         */
        config(['mail.default' => 'array']);

        $user = app(RegistrationService::class)->register(
            [
                'organisation_name' => $this->organisationName(),
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'email' => $this->adminEmail(),
                'phone' => '+233 24 000 0100',
                'password' => 'ShowcaseDemo!2026',
                'language' => $this->language(),
            ],
            Request::create('/api/auth/register', 'POST')
        );

        /*
         * The showcase account is used for screenshots, so it must be
         * able to sign in and must never hit a plan wall.
         */
        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_token_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        $organisationId = (int) $user->organisation_id;

        License::create([
            'organisation_id' => $organisationId,
            'plan' => 'professional',
            'starts_on' => now()->toDateString(),
            'expires_on' => null,
            'notes' => self::MARKER.' — perpetual licence for screenshots.',
        ]);

        /*
         * Registration always writes GHS; switch the presentation currency
         * for this edition, and post every journal entry in the
         * organisation's own language.
         */
        OrganisationContext::runAs(
            $organisationId,
            function (): void {
                ApplicationSetting::query()->firstOrFail()->forceFill([
                    'currency' => $this->currency(),
                ])->save();

                app()->setLocale($this->language());

                $this->seedScoped();
            }
        );

        $this->command?->info(sprintf(
            'Showcase seeded in organisation #%d: %d parties, %d buildings, %d units, %d leases, %d payments.',
            $organisationId,
            Party::withoutGlobalScopes()->where('organisation_id', $organisationId)->count(),
            Building::withoutGlobalScopes()->where('organisation_id', $organisationId)->count(),
            Unit::withoutGlobalScopes()->where('organisation_id', $organisationId)->count(),
            Lease::withoutGlobalScopes()->where('organisation_id', $organisationId)->count(),
            Payment::withoutGlobalScopes()->where('organisation_id', $organisationId)->count(),
        ));
    }

    private function seedScoped(): void
    {
        $today = Carbon::today();

        $this->createTenants();
        $this->createOwners();
        $this->createAgents();

        $units = $this->createBuildingsAndUnits();

        $this->createLeases($units, $today);
        $this->createOwnerExpenses($today);
    }

    /* ------------------------------------------------------------------
     * Parties
     * --------------------------------------------------------------- */

    private function createTenants(): void
    {
        $names = [
            'Kwame Mensah', 'Ama Owusu', 'Kofi Boateng', 'Akosua Asante',
            'Yaw Darko', 'Abena Agyeman', 'Kojo Appiah', 'Adwoa Bediako',
            'Kossi Adjovi', 'Afi Dogbe', 'Yao Amegan', 'Akouvi Lawson',
            'Chidi Okafor', 'Ngozi Adeyemi', 'Emeka Nwosu', 'Folake Adebayo',
        ];

        foreach ($names as $index => $name) {
            $tenant = Party::create([
                'type' => 'person',
                'name' => $name,
                'phone' => $this->nextPhone($index),
                'email' => $this->emailFor($name),
                'address' => $this->addressFor($index),
                'id_number' => sprintf('GHA-%06d', 100200 + $index),
                'notes' => self::MARKER.' — Tenant.',
            ]);

            $tenant->roles()->create(['role' => 'tenant']);

            $this->tenants[] = $tenant;
        }
    }

    private function createOwners(): void
    {
        $people = [
            'Kwabena Sarpong', 'Yaa Nyarko', 'Sena Klutse', 'Olumide Balogun',
        ];

        $organisations = [
            'Harmattan Holdings Ltd', 'Baobab Estates Ltd',
        ];

        $index = 0;

        foreach ($people as $name) {
            $this->owners[] = $this->owner($name, 'person', $index++);
        }

        foreach ($organisations as $name) {
            $this->owners[] = $this->owner($name, 'organisation', $index++);
        }
    }

    private function owner(string $name, string $type, int $index): Party
    {
        $party = Party::create([
            'type' => $type,
            'name' => $name,
            'legal_name' => $type === 'person' ? null : $name,
            'phone' => $this->nextPhone(40 + $index),
            'email' => $this->emailFor($name),
            'address' => $this->addressFor(30 + $index),

            'contact_person_name' => $type === 'person' ? null : 'Yaw Otoo',
            'contact_person_phone' => $type === 'person'
                ? null
                : $this->nextPhone(60 + $index),
            'registration_number' => $type === 'person'
                ? null
                : sprintf('CS-%06d', 442100 + $index),

            'bank_name' => $index % 2 === 0 ? 'Harbour Bank' : null,
            'bank_account_name' => $index % 2 === 0 ? $name : null,
            'bank_account_number' => $index % 2 === 0
                ? sprintf('0041%08d', 3300 + $index)
                : null,
            'bank_branch' => $index % 2 === 0 ? 'Ridge Branch' : null,

            'id_number' => sprintf('GHA-%06d', 500400 + $index),
            'notes' => self::MARKER.' — Owner.',
        ]);

        $party->roles()->create(['role' => 'owner']);

        return $party;
    }

    private function createAgents(): void
    {
        foreach (['Selorm Ahiable', 'Fatima Sule'] as $index => $name) {
            $agent = Party::create([
                'type' => 'person',
                'name' => $name,
                'phone' => $this->nextPhone(80 + $index),
                'email' => $this->emailFor($name),
                'address' => $this->addressFor(50 + $index),
                'id_number' => sprintf('GHA-%06d', 700600 + $index),
                'notes' => self::MARKER.' — Letting agent.',
            ]);

            $agent->roles()->create(['role' => 'agent']);

            $this->agents[] = $agent;
        }
    }

    /* ------------------------------------------------------------------
     * Buildings and units
     * --------------------------------------------------------------- */

    /** @return array<int, Unit> */
    private function createBuildingsAndUnits(): array
    {
        /*
         * Twelve units against nine occupying leases keeps occupancy at a
         * healthy 75% — a working agency with room to let, rather than a
         * mostly empty portfolio.
         *
         * [name, street, city, units, unit label]
         */
        $buildings = [
            ['Palm Court Residences', '12 Akoko Close', 'Accra', 3, 'Apartment %d'],
            ['Baobab Heights', '45 Harmattan Road', 'Accra', 3, 'Flat %d'],
            ['Lagoon View Apartments', '8 Pelican Street', 'Lomé', 2, 'Apartment %d'],
            ['Sunbird Court', '23 Kapok Avenue', 'Lagos', 2, 'Suite %d'],
            ['Acacia Place', '4 Flamboyant Lane', 'Accra', 2, 'Shop %d'],
        ];

        // Sole ownership, an even pair, and a majority split.
        $splits = [
            [100.00],
            [60.00, 40.00],
            [100.00],
            [50.00, 50.00],
            [100.00],
        ];

        $units = [];
        $ownerCursor = 0;

        foreach ($buildings as $bIndex => [$name, $street, $city, $unitCount, $format]) {
            $building = Building::create([
                'name' => $name,
                'description' => 'Managed residential and commercial property.',
                'address' => $street.', '.$city,
                'location' => $city,
                'notes' => self::MARKER,
            ]);

            foreach ($splits[$bIndex] as $percentage) {
                BuildingOwner::create([
                    'building_id' => $building->id,
                    'party_id' => $this->owners[$ownerCursor % count($this->owners)]->id,
                    'ownership_percentage' => $percentage,
                ]);

                $ownerCursor++;
            }

            for ($u = 1; $u <= $unitCount; $u++) {
                $units[] = Unit::create([
                    'building_id' => $building->id,
                    'name' => sprintf($format, $u),
                    'is_commercial' => str_starts_with($format, 'Shop'),
                    'description' => $u === 1 ? 'Corner unit with private terrace.' : null,
                ]);
            }
        }

        return $units;
    }

    /* ------------------------------------------------------------------
     * Leases
     * --------------------------------------------------------------- */

    /**
     * Ten leases: seven running, two in notice, one draft — with varied
     * rents, frequencies, advances, deposits and management fees so the
     * portfolio reads as a real book of business.
     *
     * @param array<int, Unit> $units
     */
    private function createLeases(array $units, Carbon $today): void
    {
        $initializer = app(LeaseInitializationService::class);
        $increments = app(RentIncrementService::class);

        // [months running, rent, frequency, status, advance months, fee]
        $plan = [
            [14, 2400, 'monthly',   'active', 0,  ['percentage', 10.00]],
            [9,  3600, 'monthly',   'active', 6,  ['percentage', 10.00]],
            [6,  1800, 'monthly',   'active', 0,  ['percentage', 8.00]],
            [16, 5200, 'quarterly', 'active', 0,  ['percentage', 10.00]],
            [4,  2900, 'monthly',   'active', 4,  ['fixed', 250]],
            [11, 7500, 'monthly',   'active', 0,  ['percentage', 12.50]],
            [7,  4100, 'monthly',   'active', 0,  ['none', 0]],
            [13, 3300, 'monthly',   'notice', 0,  ['percentage', 10.00]],
            [8,  6400, 'quarterly', 'notice', 0,  ['percentage', 10.00]],
        ];

        foreach ($plan as $i => [$months, $rent, $frequency, $status, $advanceMonths, $fee]) {
            [$feeType, $feeValue] = $fee;

            $startDate = $today->copy()->subMonths($months)->startOfMonth();

            $agent = $i % 3 === 0
                ? $this->agents[$i % count($this->agents)]
                : null;

            $lease = Lease::create([
                'unit_id' => $units[$i]->id,
                'tenant_id' => $this->tenants[$i]->id,
                'agent_id' => $agent?->id,

                'start_date' => $startDate->toDateString(),
                'end_date' => $i % 3 === 0
                    ? null
                    : $startDate->copy()->addYears(2)->subDay()->toDateString(),

                'status' => $status,
                'termination_notice_date' => $status === 'notice'
                    ? $today->copy()->subDays(12 + $i)->toDateString()
                    : null,

                'rent_amount' => $rent,
                'payment_frequency' => $frequency,
                'due_day' => $i % 2 === 0 ? 1 : 5,
                'vat_rate' => 0,
                'proration_amount' => null,

                'security_deposit_amount' => $rent * ($i % 3 === 0 ? 2 : 1),
                'advance_payment_amount' => $advanceMonths > 0 ? $rent * $advanceMonths : 0,
                'rent_reserve_amount' => $advanceMonths > 0 ? $rent * 2 : 0,

                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'next_rent_increment_date' => null,

                'management_fee_type' => $feeType,
                'management_fee_value' => $feeValue,
                'agent_commission_amount' => $agent !== null ? (int) round($rent * 0.5) : 0,

                'notes' => self::MARKER.' — Operational lease.',
            ]);

            $openingData = [];

            if ($advanceMonths > 0) {
                $openingData = [
                    'advance_received' => true,
                    'advance_received_date' => $startDate->toDateString(),
                    'advance_received_method' => ['bank_transfer', 'momo'][$i % 2],
                    'advance_received_reference' => sprintf('ADV-%04d', 1000 + $i),
                ];
            }

            $initializer->initialize($lease, $openingData, $today);

            if ($advanceMonths === 0) {
                $this->createPaymentScenario($lease, $i, $today);
            }

            /*
             * Leases running more than a year can legally carry a
             * scheduled increment, which puts the dashboard's rent
             * increment tile to work.
             */
            if ($months >= 13) {
                $increments->schedule(
                    $lease,
                    'percentage',
                    7.5,
                    $today->copy()->addMonths(2)->startOfMonth()->toDateString(),
                );
            }
        }

        // One lease still being prepared.
        $draftRent = 4800;

        Lease::create([
            'unit_id' => $units[9]->id,
            'tenant_id' => $this->tenants[9]->id,
            'agent_id' => $this->agents[0]->id,
            'start_date' => $today->copy()->addDays(21)->toDateString(),
            'end_date' => $today->copy()->addDays(21)->addYears(2)->subDay()->toDateString(),
            'status' => 'draft',
            'rent_amount' => $draftRent,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'proration_amount' => null,
            'security_deposit_amount' => $draftRent,
            'advance_payment_amount' => $draftRent * 3,
            'rent_reserve_amount' => $draftRent,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'next_rent_increment_date' => null,
            'management_fee_type' => 'percentage',
            'management_fee_value' => 10.00,
            'agent_commission_amount' => (int) round($draftRent * 0.5),
            'notes' => self::MARKER.' — Draft lease pending activation.',
        ]);
    }

    /**
     * Varied payment positions so the dashboard shows collections,
     * arrears and part-payments rather than a uniform picture.
     */
    private function createPaymentScenario(Lease $lease, int $index, Carbon $today): void
    {
        $outstanding = $lease
            ->invoices()
            ->whereIn('status', ['issued', 'partial'])
            ->get()
            ->sum(fn ($invoice) => $invoice->outstandingAmount());

        if ($outstanding <= 0) {
            return;
        }

        /*
         * Most tenants are up to date. One small tenancy is in arrears
         * and one mid-sized tenancy is part paid — enough for the arrears
         * and part-payment features to show, while keeping the overall
         * book healthy. Deliberately pinned to the smaller leases so a
         * single large tenancy cannot dominate the arrears figure.
         */
        $amount = match ($index) {
            2 => 0,
            6 => max(1, (int) round($outstanding * 0.6)),
            default => $outstanding,
        };

        if ($amount <= 0) {
            return;
        }

        $method = ['bank_transfer', 'momo', 'cash'][$index % 3];

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => $amount,
            'payment_date' => $today->copy()->subDays(2 + ($index * 3))->toDateString(),
            'payment_method' => $method,
            'reference' => sprintf('PAY-%06d', $this->paymentSequence++),
            'collector_name' => $method === 'cash' ? 'Selorm Ahiable' : null,
            'notes' => self::MARKER.' — Tenant rent payment.',
            'is_opening_advance' => false,
        ]);

        app(PaymentAllocationService::class)->allocate($payment);
    }

    /* ------------------------------------------------------------------
     * Owner-side activity
     * --------------------------------------------------------------- */

    private function createOwnerExpenses(Carbon $today): void
    {
        $service = app(OwnerAccountingService::class);

        $expenses = [
            ['Roof repairs after storm damage', 1850],
            ['Borehole pump replacement', 2400],
            ['Compound repainting', 3100],
            ['Standby generator servicing', 950],
        ];

        foreach (Building::query()->orderBy('id')->limit(4)->get() as $index => $building) {
            [$description, $amount] = $expenses[$index];

            $expense = OwnerExpense::create([
                'building_id' => $building->id,
                'unit_id' => null,
                'description' => $description,
                'amount' => $amount,
                'expense_date' => $today->copy()->subDays(6 + ($index * 5))->toDateString(),
                'reference' => sprintf('EXP-%04d', 2100 + $index),
                'notes' => self::MARKER,
            ]);

            $service->allocateExpense($expense);
        }
    }

    /* ------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    private function emailFor(string $name): string
    {
        return strtolower(
            preg_replace('/[^a-z0-9]+/i', '.', trim($name))
        ).'@akwaba.example';
    }

    private function addressFor(int $index): string
    {
        $streets = [
            'Akoko Close', 'Harmattan Road', 'Pelican Street', 'Kapok Avenue',
            'Flamboyant Lane', 'Tamarind Walk', 'Silk Cotton Road',
            'Weaverbird Street',
        ];

        $cities = ['Accra', 'Lomé', 'Lagos'];

        return sprintf(
            '%d %s, %s',
            2 + ($index * 3) % 88,
            $streets[$index % count($streets)],
            $cities[$index % count($cities)]
        );
    }

    /**
     * Plausible Ghana / Togo / Nigeria mobile formats, generated from a
     * fixed sequence so the dataset is deterministic.
     */
    private function nextPhone(int $seed): string
    {
        $n = $this->phoneSequence++;

        return match ($seed % 3) {
            0 => sprintf('+233 24 %03d %04d', 100 + $n, 1000 + ($n * 7) % 9000),
            1 => sprintf('+228 90 %02d %02d %02d', 10 + $n % 80, ($n * 3) % 99, ($n * 5) % 99),
            default => sprintf('+234 80 %04d %04d', 1000 + ($n * 11) % 8999, 2000 + ($n * 13) % 7999),
        };
    }
}

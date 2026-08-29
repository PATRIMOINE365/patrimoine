<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\OrganisationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Read a customer organisation's operational records from the console.
 *
 * Support cannot answer "what does their data actually look like?" from
 * the organisation summary alone, so this exposes the same records the
 * customer sees, for one organisation at a time.
 *
 * Every query runs inside OrganisationContext::runAs(), so the ordinary
 * tenant scopes still apply and a bug here can only ever return the
 * organisation that was asked for. Nothing uses withoutGlobalScopes().
 */
class AdminOrganisationDataController extends Controller
{
    /**
     * Record collections this endpoint can return.
     */
    private const DATASETS = [
        'parties',
        'buildings',
        'units',
        'leases',
        'invoices',
        'payments',
    ];

    public function index(
        Request $request,
        Organisation $organisation
    ): JsonResponse {
        $validated = $request->validate([
            'dataset' => ['nullable', Rule::in(self::DATASETS)],
            'search' => ['nullable', 'string', 'max:191'],
        ]);

        $dataset = $validated['dataset'] ?? 'leases';
        $search = trim((string) ($validated['search'] ?? ''));

        $payload = OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): array => $this->load($dataset, $search)
        );

        return response()->json([
            'organisation' => [
                'id' => (int) $organisation->id,
                'name' => $organisation->name,
            ],

            'dataset' => $dataset,
            'counts' => OrganisationContext::runAs(
                (int) $organisation->id,
                fn (): array => $this->counts()
            ),

            'data' => $payload,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'parties' => Party::query()->count(),
            'buildings' => Building::query()->count(),
            'units' => Unit::query()->count(),
            'leases' => Lease::query()->count(),
            'invoices' => Invoice::query()->count(),
            'payments' => Payment::query()->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(string $dataset, string $search): array
    {
        return match ($dataset) {
            'parties' => $this->parties($search),
            'buildings' => $this->buildings($search),
            'units' => $this->units($search),
            'invoices' => $this->invoices($search),
            'payments' => $this->payments($search),
            default => $this->leases($search),
        };
    }

    /**
     * Owners, tenants and agents are all Parties distinguished by role,
     * so they are returned together with their roles attached rather than
     * as three near-identical lists.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parties(string $search): array
    {
        $query = Party::query()->with('roles');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('legal_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Party $party): array => [
                'id' => $party->id,
                'type' => $party->type,
                'name' => $party->name,
                'legal_name' => $party->legal_name,
                'email' => $party->email,
                'phone' => $party->phone,
                'phone_country' => $party->phone_country,
                'roles' => $party->roles
                    ->pluck('role')
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildings(string $search): array
    {
        $query = Building::query()
            ->withCount('units')
            ->with('ownerships.party');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Building $building): array => [
                'id' => $building->id,
                'name' => $building->name,
                'address' => $building->address,
                'units_count' => $building->units_count,
                'owners' => $building->ownerships
                    ->map(fn ($ownership): array => [
                        'party_id' => $ownership->party_id,
                        'name' => $ownership->party?->name,
                        'percentage' => (float) $ownership->ownership_percentage,
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function units(string $search): array
    {
        $query = Unit::query()->with('building');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query
            ->orderBy('building_id')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'building_id' => $unit->building_id,
                'building_name' => $unit->building?->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leases(string $search): array
    {
        $query = Lease::query()
            ->with(['unit.building', 'tenant', 'agent']);

        if ($search !== '') {
            $query->whereHas(
                'tenant',
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')
            );
        }

        return $query
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Lease $lease): array => [
                'id' => $lease->id,
                'status' => $lease->status,
                'tenant_id' => $lease->tenant_id,
                'tenant_name' => $lease->tenant?->name,
                'agent_name' => $lease->agent?->name,
                'unit_id' => $lease->unit_id,
                'unit_name' => $lease->unit?->name,
                'building_name' => $lease->unit?->building?->name,
                'start_date' => $lease->start_date?->toDateString(),
                'end_date' => $lease->end_date?->toDateString(),
                'rent_amount' => (int) $lease->rent_amount,
                'payment_frequency' => $lease->payment_frequency,
                'vat_rate' => $lease->vat_rate,
                'management_fee_type' => $lease->management_fee_type,
                'management_fee_value' => $lease->management_fee_value,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invoices(string $search): array
    {
        $query = Invoice::query()->with('lease.tenant');

        if ($search !== '') {
            $query->where('invoice_number', 'like', '%'.$search.'%');
        }

        return $query
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'lease_id' => $invoice->lease_id,
                'tenant_name' => $invoice->lease?->tenant?->name,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total_amount' => (int) $invoice->total_amount,
                'vat_amount' => (int) $invoice->vat_amount,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function payments(string $search): array
    {
        $query = Payment::query()->with('lease.tenant');

        if ($search !== '') {
            $query->where('reference', 'like', '%'.$search.'%');
        }

        return $query
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'lease_id' => $payment->lease_id,
                'tenant_name' => $payment->lease?->tenant?->name,
                'amount' => (int) $payment->amount,
                'payment_date' => $payment->payment_date?->toDateString(),
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
            ])
            ->all();
    }
}

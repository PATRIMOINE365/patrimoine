<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LicenseIssuedMail;
use App\Models\License;
use App\Models\Organisation;
use App\Models\User;
use App\Services\PlatformAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Licence issuance for the platform console.
 *
 * Every issuance is a new licence row — history is never rewritten.
 * The organisation's administrators are notified by email; revocation
 * is quiet by design (the operator communicates it personally).
 */
class AdminLicenseController extends Controller
{
    /**
     * Issue (or extend, by issuing anew) a licence.
     */
    public function store(
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'organisation_id' => [
                'required',
                'integer',
                Rule::exists('organisations', 'id')->where(
                    fn ($query) => $query->where('is_platform', false)
                ),
            ],
            'plan' => [
                'required',
                Rule::in(array_keys((array) config('licensing.plans'))),
            ],
            'starts_on' => ['required', 'date'],
            'expires_on' => [
                'nullable',
                'date',
                'after_or_equal:starts_on',
            ],
            'amount' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_method' => [
                'nullable',
                Rule::in(['bank_transfer', 'momo', 'cash', 'cheque', 'other']),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $organisation = Organisation::query()
            ->findOrFail($validated['organisation_id']);

        $license = License::create($validated);

        $audit->record(
            action: 'platform.license_issued',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'license',
            entityId: $license->id,
            entityLabel: $license->plan,
            metadata: [
                'plan' => $license->plan,
                'starts_on' => $license->starts_on?->toDateString(),
                'expires_on' => $license->expires_on?->toDateString(),
                'amount' => $license->amount,
                'payment_reference' => $license->payment_reference,
            ],
        );

        $this->notifyOrganisationAdministrators(
            $organisation,
            $license
        );

        return response()->json(
            [
                'message' => 'License issued.',
                'license_id' => $license->id,
            ],
            201
        );
    }

    /**
     * Revoke a licence: it stops entitling immediately but stays in
     * the history.
     */
    public function revoke(
        int $licenseId,
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $license = License::query()
            ->whereNull('revoked_at')
            ->findOrFail($licenseId);

        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($license->organisation_id);

        $license->forceFill([
            'revoked_at' => now(),
        ])->save();

        $audit->record(
            action: 'platform.license_revoked',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'license',
            entityId: $license->id,
            entityLabel: $license->plan,
            metadata: [
                'plan' => $license->plan,
            ],
        );

        return response()->json([
            'message' => 'License revoked.',
        ]);
    }

    /**
     * Email the customer's administrators about their new licence, in
     * the organisation's configured language.
     */
    private function notifyOrganisationAdministrators(
        Organisation $organisation,
        License $license
    ): void {
        $language = (string) (
            \Illuminate\Support\Facades\DB::table('application_settings')
                ->where('organisation_id', $organisation->id)
                ->value('language')
            ?? 'en'
        );

        $administrators = User::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->where('role', 'administrator')
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        foreach ($administrators as $administrator) {
            Mail::to($administrator->email)
                ->locale($language)
                ->send(
                    new LicenseIssuedMail(
                        user: $administrator,
                        organisationName: $organisation->name,
                        license: $license
                    )
                );
        }
    }
}

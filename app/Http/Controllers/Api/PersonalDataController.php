<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\OrganisationContext;
use App\Support\PersonalData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Answering a person who asks what is held about them, or asks to be
 * forgotten.
 *
 * Three routes, because there are three different people asking:
 *
 *   - a colleague asking for their own account data. They need nobody's
 *     permission for that, so any signed-in user may take their own;
 *
 *   - an administrator producing a tenant's or owner's data, because the
 *     organisation is the controller for those people and the request
 *     properly belongs to it;
 *
 *   - an administrator taking the organisation's own copy of everything.
 *
 * Everything is served as JSON. Article 20 asks for a structured, commonly
 * used, machine-readable format, and a spreadsheet of thirty related tables
 * is none of those things.
 */
class PersonalDataController extends Controller
{
    /**
     * My own data.
     */
    public function me(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->download(
            PersonalData::forUser($user),
            'patrimoine-my-data-'.now()->format('Y-m-d').'.json'
        );
    }

    /**
     * Everything held about one tenant, owner or agent.
     */
    public function party(
        Party $party,
        Request $request,
        ActivityLogService $activityLog
    ): StreamedResponse {
        /*
         * Producing somebody's data is itself something that happened to
         * them, so it is recorded like anything else.
         */
        $activityLog->record(
            action: 'party.data_exported',
            request: $request,
            entityType: 'party',
            entityId: $party->id,
            entityLabel: $party->name,
        );

        return $this->download(
            PersonalData::forParty($party),
            'patrimoine-party-'.$party->id.'-'.now()->format('Y-m-d').'.json'
        );
    }

    /**
     * The organisation's own copy of everything.
     */
    public function organisation(
        Request $request,
        ActivityLogService $activityLog
    ): StreamedResponse {
        $organisationId = (int) OrganisationContext::id();

        $activityLog->record(
            action: 'organisation.data_exported',
            request: $request,
            entityType: 'organisation',
            entityId: $organisationId,
        );

        return $this->download(
            PersonalData::forOrganisation($organisationId),
            'patrimoine-organisation-'.now()->format('Y-m-d').'.json'
        );
    }

    /**
     * Erase a person, keeping the accounts.
     *
     * Guarded the way the account closure is guarded, and for the same
     * reason: it cannot be undone, and the thing it destroys is somebody's
     * identity rather than a row anybody can retype.
     */
    public function erase(
        Party $party,
        Request $request,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validate([
            'name_confirmation' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        /** @var User $administrator */
        $administrator = $request->user();

        if ($party->erased_at !== null) {
            throw ValidationException::withMessages([
                'party' => [__('api.personal_data.already_erased')],
            ]);
        }

        /*
         * The managing organisation is the customer's own company. Erasing
         * it would leave every document it produces without a producer.
         */
        if ($party->roles->contains('role', 'managing_organisation')) {
            throw ValidationException::withMessages([
                'party' => [__('api.personal_data.cannot_erase_managing')],
            ]);
        }

        if (! Hash::check($validated['password'], $administrator->password)) {
            throw ValidationException::withMessages([
                'password' => [__('api.auth.password_confirmation_failed')],
            ]);
        }

        if ($validated['name_confirmation'] !== $party->name) {
            throw ValidationException::withMessages([
                'name_confirmation' => [
                    __('api.personal_data.name_confirmation_mismatch'),
                ],
            ]);
        }

        $was = $party->name;

        $reference = PersonalData::erase($party);

        /*
         * The log records that an erasure happened and who asked for it. It
         * deliberately does NOT record the name that was erased — writing it
         * into an append-only log would put back the very thing that was
         * just destroyed.
         */
        $activityLog->record(
            action: 'party.erased',
            request: $request,
            entityType: 'party',
            entityId: $party->id,
            entityLabel: $reference,
            metadata: ['reference' => $reference],
        );

        return response()->json([
            'message' => __('api.personal_data.erased', ['reference' => $reference]),
            'reference' => $reference,
            'was' => $was,
        ]);
    }

    /**
     * Send an array as a downloadable JSON file.
     */
    private function download(array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($data): void {
                echo json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                );
            },
            $filename,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }
}

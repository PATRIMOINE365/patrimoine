<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailChangeRequest;
use App\Services\EmailChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The three-step change of a signed-in user's own email address.
 *
 * PATCH /api/auth/me refuses a changed email outright; this controller
 * is the only self-service way an address moves. Each step wants proof
 * the previous one cannot supply: the password first, then the current
 * mailbox, then the new one — so a stolen bearer token on its own can
 * never redirect an account's recovery.
 */
class EmailChangeController extends Controller
{
    public function __construct(
        private readonly EmailChangeService $emailChanges
    ) {}

    /**
     * The user's open request, if any, so a reopened dialog resumes
     * where it left off instead of silently starting a second flow.
     */
    public function show(Request $request): JsonResponse
    {
        $change = $this->emailChanges->pendingFor(
            $request->user()
        );

        return response()->json([
            'pending' => $change === null
                ? null
                : $this->serialize($change),
        ]);
    }

    /**
     * Step 1 — new address plus current password.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'current_password' => ['required', 'string'],
        ]);

        $change = $this->emailChanges->initiate(
            $request->user(),
            $validated['email'],
            $validated['current_password'],
            $request
        );

        return response()->json(
            [
                'message' => __('api.email_change.code_sent_current'),
                'change' => $this->serialize($change),
            ],
            201
        );
    }

    /**
     * Step 2 — the code from the CURRENT mailbox.
     */
    public function verifyCurrent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $change = $this->emailChanges->verifyCurrentMailbox(
            $request->user(),
            $validated['token'],
            trim($validated['code'])
        );

        return response()->json([
            'message' => __('api.email_change.code_sent_proposed'),
            'change' => $this->serialize($change),
        ]);
    }

    /**
     * Step 3 — the code from the NEW mailbox; the change happens here.
     *
     * Every session is rotated with the address, so the response carries
     * the fresh token this browser continues on.
     */
    public function verifyProposed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->emailChanges->verifyProposedMailbox(
            $request->user(),
            $validated['token'],
            trim($validated['code']),
            $request
        );

        return response()->json([
            'message' => __('api.email_change.completed', [
                'email' => $result['change']->proposed_email,
            ]),
            'email' => $result['change']->proposed_email,
            'token' => $result['token'],
        ]);
    }

    /**
     * Send the outstanding step's code again.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $change = $this->emailChanges->resend(
            $request->user(),
            $validated['token']
        );

        return response()->json([
            'message' => $change->awaitingCurrentMailbox()
                ? __('api.email_change.code_sent_current')
                : __('api.email_change.code_sent_proposed'),
            'change' => $this->serialize($change),
        ]);
    }

    /**
     * Abandon the open request. The account is untouched either way.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->emailChanges->cancel($request->user());

        return response()->json([
            'message' => __('api.email_change.cancelled'),
        ]);
    }

    /**
     * What the browser needs to drive the dialog — never a code, never
     * a hash.
     *
     * @return array<string, mixed>
     */
    private function serialize(EmailChangeRequest $change): array
    {
        return [
            'token' => $change->token,
            'proposed_email' => $change->proposed_email,
            'step' => $change->awaitingCurrentMailbox()
                ? 'verify_current'
                : 'verify_proposed',
            'code_expires_at' => $change->code_expires_at?->toIso8601String(),
            'resends_left' => max(
                0,
                EmailChangeRequest::MAX_RESENDS - (int) $change->resends
            ),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\Support\ResendMailboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * The platform support mailbox.
 *
 * Shows what Patrimoine has sent, what has arrived at any
 * @patrimoine365.com address, and lets staff answer from one place
 * instead of a separate mail client.
 */
class AdminEmailController extends Controller
{
    public function __construct(
        private readonly ResendMailboxService $mailbox,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    /**
     * List sent or received mail.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'box' => ['nullable', Rule::in(['sent', 'received'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'after' => ['nullable', 'string', 'max:191'],
        ]);

        $box = $validated['box'] ?? 'received';

        if (! $this->mailbox->configured()) {
            /*
             * Environments without a Resend key (local, CI) must show an
             * empty mailbox rather than an error page.
             */
            return response()->json([
                'box' => $box,
                'configured' => false,
                'data' => [],
                'has_more' => false,
            ]);
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $after = $validated['after'] ?? null;

        $page = $box === 'sent'
            ? $this->mailbox->sent($limit, $after)
            : $this->mailbox->received($limit, $after);

        return response()->json([
            'box' => $box,
            'configured' => true,
            'data' => array_map(
                fn (array $email): array => $this->summarise($email, $box),
                $page['data']
            ),
            'has_more' => $page['has_more'],
        ]);
    }

    /**
     * Retrieve one message with its body.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'box' => ['required', Rule::in(['sent', 'received'])],
        ]);

        $this->assertConfigured();

        $email = $validated['box'] === 'sent'
            ? $this->mailbox->showSent($id)
            : $this->mailbox->showReceived($id);

        return response()->json([
            'box' => $validated['box'],
            'email' => $email,
        ]);
    }

    /**
     * Send a support email.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1', 'max:20'],
            'to.*' => ['required', 'email:rfc', 'max:191'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],

            /*
             * Staff choose which product mailbox the message comes from.
             * Only the configured @patrimoine365.com mailboxes are
             * accepted, so the console can never send as an arbitrary
             * address on the verified domain.
             */
            'from' => [
                'nullable',
                Rule::in(array_values(config('legal.mailboxes', []))),
            ],

            'reply_to' => ['nullable', 'email:rfc', 'max:191'],
        ]);

        $this->assertConfigured();

        $from = $validated['from']
            ?? config('legal.mailboxes.support');

        $result = $this->mailbox->send(
            to: $validated['to'],
            subject: $validated['subject'],
            body: $validated['body'],
            from: $from,
            replyTo: $validated['reply_to'] ?? null,
        );

        /*
         * Outbound support mail is a staff action on a customer's behalf
         * and belongs in the platform audit trail, not only in Resend.
         */
        $this->activityLog->record(
            action: 'platform.email.sent',
            actor: $request->user(),
            request: $request,
            entityType: 'email',
            entityId: null,
            entityLabel: $validated['subject'],
            snapshot: [
                'from' => $from,
                'to' => $validated['to'],
                'subject' => $validated['subject'],
            ],
            metadata: [
                'resend_id' => $result['id'] ?? null,
                'reply_to' => $validated['reply_to'] ?? null,
            ],
        );

        return response()->json([
            'sent' => true,
            'id' => $result['id'] ?? null,
        ], 201);
    }

    /**
     * Mailboxes staff may send from.
     */
    public function mailboxes(): JsonResponse
    {
        return response()->json([
            'default' => config('legal.mailboxes.support'),
            'mailboxes' => array_values(
                config('legal.mailboxes', [])
            ),
        ]);
    }

    private function assertConfigured(): void
    {
        if (! $this->mailbox->configured()) {
            throw new RuntimeException(
                'Support mail is not configured in this environment.'
            );
        }
    }

    /**
     * Flatten one Resend record into what the console list needs.
     *
     * @param array<string, mixed> $email
     * @return array<string, mixed>
     */
    private function summarise(array $email, string $box): array
    {
        return [
            'id' => $email['id'] ?? null,
            'box' => $box,
            'from' => $email['from'] ?? null,
            'to' => $email['to'] ?? [],
            'cc' => $email['cc'] ?? null,
            'subject' => $email['subject'] ?? null,
            'created_at' => $email['created_at'] ?? null,

            /*
             * Delivery state exists for sent mail only.
             */
            'status' => $email['last_event'] ?? null,

            'has_attachments' => ! empty($email['attachments']),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportMessageRequest;
use App\Mail\SupportMessageMail;
use App\Models\Organisation;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * V1.0.36: writing to support without leaving Patrimoine.
 *
 * Every role may write, because being unable to do your work is exactly
 * the thing a Viewer needs to report, and making somebody find an
 * administrator first would only delay it.
 *
 * There is no ticket store behind this: the message becomes an e-mail to
 * the support mailbox, with the writer as Reply-To. The Activity Log
 * records that it was sent — not what it said, which is correspondence
 * rather than a change to the organisation's records.
 */
class SupportMessageController extends Controller
{
    public function store(
        StoreSupportMessageRequest $request,
        ActivityLogService $activityLog
    ): JsonResponse {
        $user = $request->user();

        $organisation = $user->organisation_id === null
            ? null
            : Organisation::query()->find($user->organisation_id);

        $subject = trim((string) $request->validated('subject'));
        $body = trim((string) $request->validated('message'));

        /*
         * A message that never left is not a message sent, so unlike the
         * signup alert this failure is reported rather than swallowed:
         * the customer is waiting for an answer that would never come.
         */
        try {
            Mail::to(
                (string) config('legal.mailboxes.support', 'support@patrimoine365.com')
            )->send(
                new SupportMessageMail(
                    author: $user,
                    organisation: $organisation,
                    subjectLine: $subject,
                    body: $body,
                    pageLanguage: (string) app()->getLocale()
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'message' => [__('api.support.send_failed')],
            ]);
        }

        $activityLog->record(
            action: 'support.message_sent',
            request: $request,
            entityType: 'support_message',
            entityLabel: $subject,
        );

        return response()->json([
            'message' => __('api.support.sent'),
        ]);
    }
}

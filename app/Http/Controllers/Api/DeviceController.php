<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The devices signed in to one account.
 *
 * A person who loses a phone needs two things within the minute: to see
 * that the phone is in the list, and to take it out. Neither is possible
 * unless the token was named for the device when it was minted, which is
 * why AccessTokenService does that and why it cannot be added afterwards.
 *
 * Every route here is the acting user's own. There is no identifier that
 * reaches another account: the query is always scoped to the token owner,
 * so a device belonging to somebody else is simply not found.
 */
class DeviceController extends Controller
{
    /**
     * List the devices currently signed in, newest use first.
     */
    public function index(Request $request): JsonResponse
    {
        $devices = PersonalAccessToken::query()
            ->where('tokenable_type', $request->user()->getMorphClass())
            ->where('tokenable_id', $request->user()->getKey())
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(
                fn (PersonalAccessToken $token): array => $token->toDevice()
            )
            ->values();

        return response()->json([
            'data' => $devices,
        ]);
    }

    /**
     * Revoke one device.
     *
     * Revoking the device making the request is allowed and is simply a
     * sign-out; the browser notices the next 401 and returns to the
     * sign-in screen like any other expiry.
     */
    public function destroy(
        Request $request,
        int $device,
        ActivityLogService $activityLog
    ): JsonResponse {
        $token = PersonalAccessToken::query()
            ->where('tokenable_type', $request->user()->getMorphClass())
            ->where('tokenable_id', $request->user()->getKey())
            ->find($device);

        if ($token === null) {
            abort(404, __('api.not_found'));
        }

        $wasCurrent = $token->isCurrent();

        $name = (string) $token->name;

        $token->delete();

        $activityLog->record(
            action: 'auth.device_revoked',
            request: $request,
            entityType: 'user',
            entityId: $request->user()->getKey(),
            entityLabel: $request->user()->name,
            metadata: [
                'device' => $name,
                'current_device' => $wasCurrent,
            ],
        );

        return response()->json([
            'message' => __('api.auth.device_revoked'),
            'signed_out' => $wasCurrent,
        ]);
    }

    /**
     * Revoke every device except the one making the request.
     *
     * The one deliberate exception keeps this from being a way to lock
     * yourself out of the screen you are standing on.
     */
    public function destroyOthers(
        Request $request,
        ActivityLogService $activityLog
    ): JsonResponse {
        $current = $request->user()->currentAccessToken();

        $query = PersonalAccessToken::query()
            ->where('tokenable_type', $request->user()->getMorphClass())
            ->where('tokenable_id', $request->user()->getKey());

        if ($current !== null) {
            $query->where('id', '!=', $current->getKey());
        }

        $revoked = $query->count();

        $query->delete();

        if ($revoked > 0) {
            $activityLog->record(
                action: 'auth.devices_revoked',
                request: $request,
                entityType: 'user',
                entityId: $request->user()->getKey(),
                entityLabel: $request->user()->name,
                metadata: [
                    'revoked' => $revoked,
                ],
            );
        }

        return response()->json([
            'message' => __('api.auth.devices_revoked'),
            'revoked' => $revoked,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated user's profile photo.
 *
 * Uploaded images are re-encoded server-side into a small square JPEG
 * (see ProfilePhotoService) and stored in the users row itself; the
 * photo travels to clients as a data URI inside the /auth/me payload,
 * so no file is ever written to disk and no URL can be probed.
 */
class ProfilePhotoController extends Controller
{
    /**
     * Set (or replace) the profile photo.
     */
    public function store(
        Request $request,
        ProfilePhotoService $photos,
        ActivityLogService $activityLog
    ): JsonResponse {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'max:5120',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        [$bytes, $mime] = $photos->process(
            (string) $request->file('photo')->get()
        );

        $user->forceFill([
            'profile_photo' => $bytes,
            'profile_photo_mime' => $mime,
        ])->save();

        $activityLog->record(
            action: 'user.updated',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            metadata: [
                'profile_photo_changed' => true,
            ],
        );

        return response()->json([
            'message' => __('api.profile.photo_updated'),
            'avatar' => 'data:'.$mime.';base64,'.base64_encode($bytes),
        ]);
    }

    /**
     * Remove the profile photo.
     */
    public function destroy(
        Request $request,
        ActivityLogService $activityLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'profile_photo' => null,
            'profile_photo_mime' => null,
        ])->save();

        $activityLog->record(
            action: 'user.updated',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            metadata: [
                'profile_photo_removed' => true,
            ],
        );

        return response()->json([
            'message' => __('api.profile.photo_removed'),
            'avatar' => null,
        ]);
    }
}

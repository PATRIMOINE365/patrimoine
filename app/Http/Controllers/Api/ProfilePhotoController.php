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
 * Two pictures are stored from one upload. The small square is what every
 * screen shows and travels inside /auth/me as a data URI, so no file is
 * written to disk and no URL can be probed. The optimised whole picture is
 * kept beside it, with where the frame sat, so its owner can reframe it
 * later without going to find the file again — that one is only ever sent
 * back to the person it belongs to, when they reopen the cropper.
 *
 * Both are re-encoded server-side (see ProfilePhotoService); the uploaded
 * bytes are never stored.
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
            /*
             * The square the browser cut out.
             */
            'photo' => [
                'required',
                'file',
                'max:5120',
            ],

            /*
             * The whole picture behind it. Optional: an older client, or
             * a direct API call, may send only the square — the photo
             * still works, it simply cannot be reframed later.
             */
            'source' => [
                'nullable',
                'file',
                'max:15360',
            ],

            'crop' => [
                'nullable',
                'string',
                'max:128',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        [$bytes, $mime] = $photos->process(
            (string) $request->file('photo')->get()
        );

        $attributes = [
            'profile_photo' => $bytes,
            'profile_photo_mime' => $mime,
        ];

        if ($request->hasFile('source')) {
            [$sourceBytes, $sourceMime] = $photos->processSource(
                (string) $request->file('source')->get()
            );

            $attributes['profile_photo_source'] = $sourceBytes;
            $attributes['profile_photo_source_mime'] = $sourceMime;
            $attributes['profile_photo_crop'] = $request->input('crop');
        }

        $user->forceFill($attributes)->save();

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
     * The picture behind the current photo, for reframing it.
     *
     * Only ever the caller's own: there is no identifier to change.
     */
    public function source(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->profile_photo_source === null) {
            return response()->json([
                'source' => null,
                'crop' => null,
            ]);
        }

        return response()->json([
            'source' => 'data:'
                .$user->profile_photo_source_mime
                .';base64,'
                .base64_encode($user->profile_photo_source),

            'crop' => $user->profile_photo_crop,
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
            'profile_photo_source' => null,
            'profile_photo_source_mime' => null,
            'profile_photo_crop' => null,
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

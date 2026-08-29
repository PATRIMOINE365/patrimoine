<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The release-by-release history, for platform staff.
 *
 * Customers read a shortened log in Help, written in fives: nobody is on
 * an old version and nobody can go back to one, so thirty entries of
 * things that are all simply true now buries the two or three worth
 * knowing. Support answering a question about when something changed
 * needs the whole thing, and reads it here.
 *
 * Always English — the console is staff-facing and deliberately
 * monolingual, so the history reads the same for everyone answering.
 */
class AdminReleaseLogController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $entries = trans('releases.entries', [], 'en');

        return response()->json([
            'current_version' => (string) config('patrimoine.release'),
            'entries' => is_array($entries) ? $entries : [],
        ]);
    }
}

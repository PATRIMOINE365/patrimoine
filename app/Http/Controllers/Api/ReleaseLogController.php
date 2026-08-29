<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The update log a customer reads.
 *
 * Patrimoine is one running service: nobody is on an old version and
 * nobody can return to one, so a release-by-release history is an archive
 * of things that are simply true now. It is written in fives instead —
 * each entry covering the releases up to and including its own — and the
 * entry still being filled is shown under the version actually running,
 * because that is the number a customer can check against their own
 * screen.
 *
 * The release-by-release history is unchanged and lives in the
 * administration console.
 */
class ReleaseLogController extends Controller
{
    public function index(): JsonResponse
    {
        $current = (string) config('patrimoine.release');

        return response()->json([
            'current_version' => $current,
            'title' => __('release_summaries.title'),
            'entries' => $this->entries($current),
        ]);
    }

    /**
     * The summaries a customer should see, newest first.
     *
     * @return array<int, array<string, string>>
     */
    private function entries(string $current): array
    {
        $entries = __('release_summaries.entries');

        if (! is_array($entries)) {
            return [];
        }

        $visible = [];

        foreach ($entries as $entry) {
            $through = (string) ($entry['through'] ?? '');

            if ($through === '') {
                continue;
            }

            $reached = version_compare($through, $current, '<=');

            /*
             * An entry whose own number has been reached is finished and
             * carries it. The one still being filled is ahead of the
             * running version, so it borrows it — anything further ahead
             * than that describes releases nobody has yet.
             */
            if ($reached) {
                $visible[] = [
                    'version' => $through,
                    'date' => (string) ($entry['date'] ?? ''),
                    'summary' => (string) ($entry['summary'] ?? ''),
                ];

                continue;
            }

            if ($visible === []) {
                $visible[] = [
                    'version' => $current,
                    'date' => (string) ($entry['date'] ?? ''),
                    'summary' => (string) ($entry['summary'] ?? ''),
                ];
            }
        }

        return $visible;
    }
}

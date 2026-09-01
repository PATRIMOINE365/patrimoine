<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\ArchiveService;
use App\Support\ErrorCodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The archive: what has been put out of the way, and putting it back.
 *
 * Archiving is the answer to a record Patrimoine will not delete because
 * the accounting still refers to it. Nothing about the record moves — it
 * simply stops appearing in the lists and in the pickers that build new
 * records — so this controller only ever sets or clears one column.
 */
class ArchiveController extends Controller
{
    public function __construct(
        private ArchiveService $archive,
        private ActivityLogService $activityLog
    ) {}

    /**
     * Everything archived, newest first.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->archive->listing(),
        ]);
    }

    /**
     * Put a record out of the way.
     *
     * V1.0.43: a reason is required. Archiving reads like deletion to
     * everybody who did not do it — the record leaves every list and every
     * picker — so it is asked for the way deletion is asked for, and the
     * answer is kept where the next person will look for it.
     */
    public function store(
        Request $request,
        string $kind,
        int $id
    ): JsonResponse {
        $record = $this->find($kind, $id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        /*
         * A lease has to be closed properly before it can be tidied away.
         * Archiving a running letting hides a tenancy that is still
         * billing and still owed money.
         */
        $blocked = $this->archive->blockedReason($record);

        if ($blocked !== null) {
            return response()->json(
                [
                    'message' => __($blocked),
                    'code' => ErrorCodes::forKey($blocked),
                ],
                422
            );
        }

        /*
         * Archive is what a record offers INSTEAD of Delete, so a record
         * that can still be deleted must not be archivable: offering both
         * would leave two ways to make the same thing disappear, one of
         * which is reversible and one of which is not.
         */
        if (! $this->archive->isArchivable($record)) {
            return response()->json(
                /*
                 * The central handler only reaches responses that came
                 * from an exception, and this is an ordinary refusal — so
                 * the code is attached here. Looked up from the catalogue
                 * by its message key rather than written out, which is
                 * what stops a code and its explanation drifting apart.
                 */
                [
                    'message' => __('api.archive.not_archivable'),
                    'code' => ErrorCodes::forKey('api.archive.not_archivable'),
                ],
                422
            );
        }

        $this->archive->archive(
            $record,
            $request->user(),
            $validated['reason']
        );

        $this->activityLog->record(
            action: 'record.archived',
            request: $request,
            entityType: $kind,
            entityId: $record->id,
            entityLabel: (string) ($record->name ?? $record->id),
            metadata: ['reason' => $validated['reason']],
        );

        return response()->json([
            'archived' => true,
        ]);
    }

    /**
     * Put it back.
     *
     * Restoring returns a record to every list and every picker in the
     * product, which is as much of a change as taking it out was, so it
     * asks for a reason too. There is nowhere on the row to keep it — a
     * restored record is not archived — so it goes to the activity log.
     */
    public function destroy(
        Request $request,
        string $kind,
        int $id
    ): JsonResponse {
        $record = $this->find($kind, $id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->archive->restore($record);

        $this->activityLog->record(
            action: 'record.restored',
            request: $request,
            entityType: $kind,
            entityId: $record->id,
            entityLabel: (string) ($record->name ?? $record->id),
            metadata: ['reason' => $validated['reason']],
        );

        return response()->json([
            'restored' => true,
        ]);
    }

    /**
     * Resolve one of the four kinds, inside this organisation.
     */
    private function find(string $kind, int $id): Model
    {
        $class = ArchiveService::KINDS[$kind] ?? null;

        if ($class === null) {
            throw new NotFoundHttpException();
        }

        /*
         * findOrFail goes through the organisation scope, so an id from
         * another organisation answers 404 exactly as it does everywhere
         * else rather than confirming the record exists.
         */
        return $class::query()->findOrFail($id);
    }
}

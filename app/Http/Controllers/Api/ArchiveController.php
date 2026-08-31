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
     */
    public function store(
        Request $request,
        string $kind,
        int $id
    ): JsonResponse {
        $record = $this->find($kind, $id);

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
            $request->user()
        );

        $this->activityLog->record(
            action: 'record.archived',
            request: $request,
            entityType: $kind,
            entityId: $record->id,
            entityLabel: (string) ($record->name ?? $record->id),
        );

        return response()->json([
            'archived' => true,
        ]);
    }

    /**
     * Put it back.
     */
    public function destroy(
        Request $request,
        string $kind,
        int $id
    ): JsonResponse {
        $record = $this->find($kind, $id);

        $this->archive->restore($record);

        $this->activityLog->record(
            action: 'record.restored',
            request: $request,
            entityType: $kind,
            entityId: $record->id,
            entityLabel: (string) ($record->name ?? $record->id),
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

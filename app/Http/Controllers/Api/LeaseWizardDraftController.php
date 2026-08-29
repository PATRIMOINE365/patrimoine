<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseWizardDraft;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unfinished guided assistants.
 *
 * A lease needs a unit and a tenant before it can exist at all, so an
 * assistant abandoned on page three had nowhere to be saved. What is kept
 * here is the assistant itself — its field values, whatever they are —
 * and it is never validated, because half of it is expected to be blank.
 *
 * Everything is scoped to the organisation by the model. The list is the
 * organisation's, not the individual's: a colleague finishing a letting
 * somebody else started is the ordinary case in a lettings office.
 */
class LeaseWizardDraftController extends Controller
{
    /**
     * Every unfinished assistant, most recently touched first.
     */
    public function index(): JsonResponse
    {
        $drafts = LeaseWizardDraft::query()
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $drafts->map(
                fn (LeaseWizardDraft $draft): array => $this->summary($draft)
            ),
        ]);
    }

    /**
     * Start one, or overwrite the one being worked on.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            /*
             * Sent back when an assistant that was already saved is saved
             * again, so pressing the button twice does not leave two.
             */
            'id' => [
                'nullable',
                'integer',
            ],

            'payload' => [
                'required',
                'array',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        $draft = null;

        if (($validated['id'] ?? null) !== null) {
            $draft = LeaseWizardDraft::query()
                ->whereKey($validated['id'])
                ->first();
        }

        if ($draft === null) {
            $draft = new LeaseWizardDraft();

            $draft->user_id = $user->id;

            /*
             * Snapshotted: the list should still read sensibly after the
             * account that started it is gone.
             */
            $draft->author_name = $user->name;
        }

        $draft->payload = $validated['payload'];

        $draft->save();

        return response()->json(
            [
                'message' => __('api.lease_wizard.draft_saved'),
                'draft' => $this->summary($draft),
            ],
            $draft->wasRecentlyCreated ? 201 : 200
        );
    }

    /**
     * Everything needed to carry on where it was left.
     */
    public function show(int $draft): JsonResponse
    {
        $record = LeaseWizardDraft::query()
            ->whereKey($draft)
            ->firstOrFail();

        return response()->json(
            $this->summary($record) + [
                'payload' => $record->payload,
            ]
        );
    }

    /**
     * Throw one away.
     */
    public function destroy(int $draft): JsonResponse
    {
        LeaseWizardDraft::query()
            ->whereKey($draft)
            ->firstOrFail()
            ->delete();

        return response()->json([
            'message' => __('api.lease_wizard.draft_discarded'),
        ]);
    }

    /**
     * What a row in the list needs.
     *
     * The date travels as an instant rather than a sentence: it is
     * formatted where it is read, in the reader's language.
     *
     * @return array<string, mixed>
     */
    private function summary(LeaseWizardDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'author' => $draft->label(),
            'started_at' => $draft->created_at?->toIso8601String(),
            'updated_at' => $draft->updated_at?->toIso8601String(),
        ];
    }
}

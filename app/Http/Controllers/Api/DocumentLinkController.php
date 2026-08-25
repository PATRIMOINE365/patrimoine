<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Issues short-lived signed URLs for PDF document endpoints.
 *
 * The browser exchanges an authenticated request for a signed URL and
 * immediately opens that URL in a new tab, letting the browser stream
 * and render the PDF natively instead of buffering it into a blob.
 */
class DocumentLinkController extends Controller
{
    /**
     * Issue a signed link for one document endpoint.
     */
    public function store(
        Request $request,
        DocumentLinkService $links
    ): JsonResponse {
        $validated = $request->validate([
            'endpoint' => [
                'required',
                'string',
                'max:2048',
            ],
        ]);

        try {
            $url = $links->issue(
                $validated['endpoint'],
                $request->user()
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'endpoint' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json([
            'url' => $url,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ErrorCodes;
use Illuminate\Http\JsonResponse;

/**
 * The error catalogue, for the Error codes tab inside Patrimoine.
 *
 * The same words the public page at /errors shows, in the organisation's
 * language, so somebody already signed in does not have to leave the
 * application to understand what they just read.
 */
class ErrorCodeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $families = [];
        $codes = [];

        foreach (array_keys(ErrorCodes::all()) as $code) {
            $text = ErrorCodes::text($code);

            if ($text === null) {
                continue;
            }

            $family = ErrorCodes::family($code) ?? 9;
            $name = ErrorCodes::familyName($family) ?? 'system';

            $families[$family] ??= [
                'family' => $family,
                'name' => __("ui.errors.family_{$name}"),
            ];

            $codes[] = [
                'code' => $code,
                'family' => $family,
                'severity' => ErrorCodes::severity($code) ?? 'fix_yourself',
                'title' => $text['title'] ?? $code,
                'what' => $text['what'] ?? '',
                'fix' => $text['fix'] ?? '',
                'needs_support' => ErrorCodes::severity($code) === 'contact_us',
            ];
        }

        ksort($families);

        return response()->json([
            'families' => array_values($families),
            'codes' => $codes,
            'contact' => array_merge(
                ErrorCodes::contact(),
                ['phone_display' => config('legal.support.phone_display')]
            ),
        ]);
    }
}

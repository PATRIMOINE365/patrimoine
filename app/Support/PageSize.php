<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * How many rows a list returns in one page.
 *
 * The browser offers a reader 25, 50 or 100 rows
 * (resources/js/pagination.js). This class is the server's half of that
 * agreement: anything else asked for — a typo, a hand-edited query string,
 * a stale bookmark — quietly becomes the default rather than an error,
 * because a page size is a preference, not an instruction that can fail.
 *
 * Customer-facing controllers predate this class and clamp `per_page`
 * themselves between 1 and 100, which already admits every offered size.
 * They are deliberately left alone: internal callers such as the reports
 * workspace ask for sizes no menu offers.
 */
final class PageSize
{
    /**
     * The sizes a reader may choose between.
     */
    public const OPTIONS = [25, 50, 100];

    /**
     * The size used when none was asked for.
     */
    public const DEFAULT = 25;

    /**
     * The page size for this request.
     */
    public static function fromRequest(
        Request $request,
        string $key = 'per_page'
    ): int {
        $requested = (int) $request->input($key, self::DEFAULT);

        return in_array($requested, self::OPTIONS, true)
            ? $requested
            : self::DEFAULT;
    }
}

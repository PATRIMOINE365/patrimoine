<?php

namespace App\Http\Controllers;

use App\Services\ApplicationLocaleService;
use App\Support\ErrorCodes;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * The Error codes page.
 *
 * Public on purpose. Somebody who cannot sign in is exactly the person
 * who needs to look up the code they are staring at, so this page asks
 * for nothing and works while the rest of the application does not.
 *
 * /errors            the whole catalogue, grouped by family
 * /errors/PM-4045    the same page, opened at one code
 *
 * The language follows ?lang= when given, then the language the browser
 * last confirmed, so a French customer who arrives from a French screen
 * keeps reading French.
 */
class ErrorReferenceController extends Controller
{
    public function __invoke(
        Request $request,
        ApplicationLocaleService $locale,
        ?string $code = null
    ): View {
        $language = $this->language($request, $locale);

        app()->setLocale($language);

        $code = $code === null
            ? null
            : strtoupper(trim($code));

        $catalogue = $this->catalogue($language);

        /*
         * An unknown code is not an error page of its own: the reference
         * opens normally and says that code is not one of ours, which is
         * more useful than a dead end.
         */
        $known = $code !== null && isset(ErrorCodes::all()[$code]);

        return view('errors-reference', [
            'language' => $language,
            'code' => $code,
            'known' => $known,
            'focused' => $known ? $this->entry($code, $language) : null,
            'families' => $catalogue,
            'contact' => ErrorCodes::contact(),
            'total' => count(ErrorCodes::all()),
        ]);
    }

    /**
     * Which language to read the catalogue in.
     */
    private function language(
        Request $request,
        ApplicationLocaleService $locale
    ): string {
        $requested = strtolower((string) $request->query('lang', ''));

        if (in_array($requested, ['en', 'fr'], true)) {
            return $requested;
        }

        $confirmed = $request->cookie(
            ApplicationLocaleService::LANGUAGE_COOKIE
        );

        if (in_array($confirmed, ['en', 'fr'], true)) {
            return $confirmed;
        }

        /*
         * The organisation language comes from the database, and this is
         * the one page that has to work when the database does not: it
         * is where somebody looks up "Patrimoine is briefly unavailable".
         * A failure here falls back to the configured default rather
         * than taking the page down with it.
         */
        try {
            return $locale->language();
        } catch (Throwable) {
            return (string) config('patrimoine.defaults.language', 'en');
        }
    }

    /**
     * Every code, grouped by family and ready to render.
     *
     * @return array<int, array{name: string, codes: array<int, array<string, mixed>>}>
     */
    private function catalogue(string $language): array
    {
        $families = [];

        foreach (array_keys(ErrorCodes::all()) as $code) {
            $entry = $this->entry($code, $language);

            if ($entry === null) {
                continue;
            }

            $family = $entry['family'];

            $families[$family]['name'] ??= __(
                "ui.errors.family_{$entry['family_name']}",
                [],
                $language
            );

            $families[$family]['codes'][] = $entry;
        }

        ksort($families);

        return $families;
    }

    /**
     * One code, with its words and what it means for the reader.
     *
     * @return array<string, mixed>|null
     */
    private function entry(string $code, string $language): ?array
    {
        $text = ErrorCodes::text($code, $language);

        if ($text === null) {
            return null;
        }

        $family = ErrorCodes::family($code) ?? 9;

        return [
            'code' => $code,
            'title' => $text['title'] ?? $code,
            'what' => $text['what'] ?? '',
            'fix' => $text['fix'] ?? '',
            'severity' => ErrorCodes::severity($code) ?? 'fix_yourself',
            'family' => $family,
            'family_name' => ErrorCodes::familyName($family) ?? 'system',
            'needs_support' => ErrorCodes::severity($code) === 'contact_us',
        ];
    }
}

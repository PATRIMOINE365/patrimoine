<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Guide;
use Illuminate\Http\JsonResponse;

/**
 * The how-to guide, in the organisation's own language.
 *
 * Read-only and open to every role: a Viewer who may not record a payment
 * still needs to know how one is recorded, and hiding the manual from them
 * would teach nobody anything.
 *
 * The locale is already applied by the time this runs (ApplyApplicationLocale),
 * which is why nothing here has to work it out.
 */
class GuideController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        $locale = in_array($locale, Guide::LOCALES, true) ? $locale : 'en';

        return response()->json([
            'locale' => $locale,
            'title' => __('guide.title'),
            'description' => __('guide.description'),
            'categories' => Guide::for($locale)['categories'],
            /*
             * Where the browser looks for the pictures. They are per
             * language, because a French reader is shown French screens.
             */
            'shots' => '/guide/'.$locale,
        ]);
    }
}

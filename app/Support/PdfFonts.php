<?php

namespace App\Support;

use Dompdf\Dompdf;

/**
 * Give the PDF renderer Inter, so a document reads as the same product as
 * the screen it was generated from.
 *
 * WHY THIS IS NOT A @font-face RULE.
 *
 * It was, first. Every PDF template includes documents/partials/fonts.blade
 * .php, and every one of them still came out in DejaVu Sans: dompdf accepted
 * the stylesheet without complaint and quietly ignored the faces, in all
 * three of the URL forms it documents — an absolute path, a file:// URI and
 * a path relative to the chroot. Nothing was logged, and the only way to
 * know was to read the BaseFont entries back out of the finished PDF.
 *
 * registerFont() is dompdf's own API for this and it works first time. It
 * also fails LOUDLY, which the stylesheet route did not.
 *
 * The stylesheet still declares the faces, because a font stack of
 * `'Inter', 'DejaVu Sans'` is what makes the fallback work if this ever
 * stops registering — an invoice that looks slightly wrong is recoverable,
 * one that does not render is not.
 */
final class PdfFonts
{
    /**
     * Weight by file. dompdf takes a CSS weight keyword or a number.
     *
     * @var array<string, string>
     */
    private const WEIGHTS = [
        'normal' => 'Inter-Regular.ttf',
        '500' => 'Inter-Medium.ttf',
        '600' => 'Inter-SemiBold.ttf',
        'bold' => 'Inter-Bold.ttf',
    ];

    public const FAMILY = 'Inter';

    /**
     * Register the family with one renderer.
     *
     * Silently does nothing when a file is missing. A deployment that
     * somehow shipped without the fonts should still be able to send an
     * invoice.
     */
    public static function register(Dompdf $dompdf): void
    {
        /*
         * dompdf converts each font once and caches the result. The
         * directory is storage/fonts (see config/dompdf.php) and it will not
         * exist on a fresh deployment.
         */
        $cache = $dompdf->getOptions()->getFontDir();

        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }

        $metrics = $dompdf->getFontMetrics();

        foreach (self::WEIGHTS as $weight => $file) {
            $path = resource_path('fonts/' . $file);

            if (! is_readable($path)) {
                continue;
            }

            $metrics->registerFont(
                [
                    'family' => self::FAMILY,
                    'style' => 'normal',
                    'weight' => $weight,
                ],
                $path
            );
        }
    }
}

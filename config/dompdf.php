<?php

/*
|--------------------------------------------------------------------------
| Dompdf Overrides
|--------------------------------------------------------------------------
|
| Patrimoine only overrides what it must; every other option continues to
| come from the barryvdh/laravel-dompdf package defaults through config
| merging.
|
*/

return [

    'options' => [

        /*
         * Embed only the glyphs a document actually uses instead of the
         * complete DejaVu font family.
         *
         * Without subsetting every receipt, voucher and invoice carried
         * the full ~850KB font payload, which made "open PDF" feel like
         * a hung download on slow connections. Subsetting brings a
         * typical document down to a few dozen kilobytes while keeping
         * full support for French accents and the GH₵ currency symbol.
         */
        'enable_font_subsetting' => true,

        /*
         * Where dompdf keeps the converted fonts it registers.
         *
         * The package default is inside vendor/, which is writable in a
         * container built as root and is NOT writable by the subscription
         * user on the Plesk box the production application runs as. A font
         * it cannot cache is a font it silently declines to register, so
         * this points at storage/, which the application owns everywhere.
         */
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),

        /*
         * Where dompdf is allowed to read files from.
         *
         * The package's default is its OWN package directory, so
         * resources/fonts is outside it and every attempt to register Inter
         * was refused — silently, with the document falling back to DejaVu
         * Sans and still rendering. See App\Support\PdfFonts.
         *
         * Both paths, and only these two: dompdf's own directory so its
         * bundled DejaVu keeps loading, and our font directory. Widening
         * this to base_path() would let anything a template could be made
         * to reference read anywhere in the application.
         */
        'chroot' => [
            base_path('vendor/dompdf/dompdf'),
            resource_path('fonts'),
            storage_path('fonts'),
        ],

    ],

];

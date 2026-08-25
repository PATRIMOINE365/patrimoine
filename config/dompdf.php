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

    ],

];

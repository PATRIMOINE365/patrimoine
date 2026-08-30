{{--
    Inter for the PDF renderer.

    Its own partial because eleven templates need it and only fourteen of
    them include base-styles: the reports, the registry and the exports
    render through the same dompdf and would otherwise quietly come out in a
    different typeface from the invoices.

    DejaVu Sans stays in every font stack behind Inter. It is dompdf's own
    bundled family, so if a font file ever fails to resolve — an unwritable
    font cache after a deploy, a missing file — the document still renders.
    An invoice that looks slightly wrong is recoverable; one that does not
    render is not.

    Inter carries everything these documents need: ₵ (U+20B5), the full
    French set, guillemets, and the narrow no-break space that French number
    formatting puts between thousands.
--}}
@php
    /*
     * dompdf resolves a @font-face src as a filesystem path. The separators
     * are normalised because a Windows checkout would otherwise emit
     * backslashes into the stylesheet.
     */
    $patrimoineFontFace = static fn (string $file): string => str_replace(
        DIRECTORY_SEPARATOR,
        '/',
        resource_path('fonts/' . $file)
    );
@endphp

<style>
    @font-face {
        font-family: 'Inter';
        font-style: normal;
        font-weight: 400;
        src: url('{{ $patrimoineFontFace('Inter-Regular.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'Inter';
        font-style: normal;
        font-weight: 500;
        src: url('{{ $patrimoineFontFace('Inter-Medium.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'Inter';
        font-style: normal;
        font-weight: 600;
        src: url('{{ $patrimoineFontFace('Inter-SemiBold.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'Inter';
        font-style: normal;
        font-weight: 700;
        src: url('{{ $patrimoineFontFace('Inter-Bold.ttf') }}') format('truetype');
    }
</style>

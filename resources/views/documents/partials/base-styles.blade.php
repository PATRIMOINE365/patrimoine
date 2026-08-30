{{--
    Shared visual baseline for every generated PDF document.

    Included AFTER each template's own <style> block, so these rules settle
    the identity while the template keeps its own layout.

    THE PRINT RULE, and it is different from the product's:

    The brand puts documents on Warm Ivory. Paper does not work that way — a
    full ivory flood costs toner on every page and prints muddy on an office
    laser, and these are invoices and receipts people file. So the PAPER
    STAYS WHITE, and Warm Ivory becomes the fill: table headers, callouts,
    the letterhead band's counterpart. The identity arrives through the
    letterhead, the green headings and the ivory table chrome, not through
    the sheet.

    Colour: every value in every document is now a brand colour. Ink for
    text (15.3:1), the supporting grey at 6.5:1, Slate at 4.5:1, Patrimoine
    Green for headings, Border for rules.
--}}
@include('documents.partials.fonts')

<style>
    /*
     * The font faces themselves live in partials/fonts.blade.php, which
     * this includes so the fourteen document templates need only one line.
     */

    body,
    table,
    td,
    th,
    p,
    div,
    span,
    h1, h2, h3, h4 {
        font-family: 'Inter', 'DejaVu Sans', sans-serif;
    }

    /* ------------------------------------------------------------ type */

    h1,
    .document-title {
        color: #123D35;
        font-size: 24pt;
        line-height: 30pt;
        font-weight: 600;
        letter-spacing: -0.3pt;
    }

    h2,
    .section-title {
        color: #0B6449;
        font-size: 12pt;
        line-height: 16pt;
        font-weight: 600;
    }

    /* ----------------------------------------------------------- table */

    table th {
        background-color: #F7F5EF !important;
        color: #123D35;
        border-color: #DDE6E2 !important;
        font-weight: 600;
    }

    table td {
        border-color: #DDE6E2 !important;
    }

    /*
     * Figures line up down a column. Money that does not line up is money
     * somebody has to read twice, and these documents exist to be checked.
     */
    td,
    th,
    .amount,
    .figure {
        font-variant-numeric: tabular-nums;
    }
</style>

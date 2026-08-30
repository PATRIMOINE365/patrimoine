{{--
    Shared organisation letterhead for every generated PDF document
    (V1.1.0).

    Uses $managingOrganisation when the rendering service supplied it
    and resolves the bound organisation's identity otherwise, so every
    voucher, receipt and bill opens with the same professional identity
    block regardless of which service produced it.
--}}
@php
    $letterheadOrganisation =
        $managingOrganisation
        ?? app(\App\Services\ApplicationIdentityService::class)
            ->managingOrganisation();

    $letterheadName =
        $letterheadOrganisation?->legal_name
        ?? $letterheadOrganisation?->name
        ?? config('legal.product.name');

    $letterheadContact =
        collect([
            $letterheadOrganisation?->address,
            $letterheadOrganisation?->phone_display,
            $letterheadOrganisation?->email,
        ])
            ->filter()
            ->implode('  ·  ');
@endphp

<style>
    /*
     * The letterhead is the CUSTOMER's identity, not ours: these are their
     * invoices and receipts, sent to their tenants. Patrimoine appears once,
     * small, in the footer. The brand shows up here only in the rule and the
     * colour of the name.
     */
    .pm-doc-letterhead {
        margin-bottom: 20px;
        border-bottom: 2px solid #123D35;
        padding-bottom: 12px;
    }

    .pm-doc-letterhead-name {
        font-size: 20px;
        line-height: 26px;
        font-weight: 600;
        letter-spacing: -0.2px;
        color: #123D35;
    }

    .pm-doc-letterhead-contact {
        margin-top: 4px;
        font-size: 9px;
        line-height: 13px;
        color: #66736F;
    }
</style>

<div class="pm-doc-letterhead">
    <div class="pm-doc-letterhead-name">
        {{ $letterheadName }}
    </div>

    @if($letterheadContact !== '')
        <div class="pm-doc-letterhead-contact">
            {{ $letterheadContact }}
        </div>
    @endif
</div>

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
    .pm-doc-letterhead {
        margin-bottom: 20px;
        border-bottom: 2px solid #1d5c3f;
        padding-bottom: 12px;
    }

    .pm-doc-letterhead-name {
        font-size: 20px;
        font-weight: bold;
        color: #123527;
    }

    .pm-doc-letterhead-contact {
        margin-top: 3px;
        font-size: 9px;
        color: #666666;
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

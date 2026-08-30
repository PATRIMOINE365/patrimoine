{{--
    Shared closing line for generated PDF documents (V1.1.0).
--}}
@php
    $footerOrganisation =
        $managingOrganisation
        ?? app(\App\Services\ApplicationIdentityService::class)
            ->managingOrganisation();

    $footerName =
        $footerOrganisation?->legal_name
        ?? $footerOrganisation?->name
        ?? config('legal.product.name');
@endphp

<style>
    .pm-doc-footer {
        margin-top: 32px;
        padding-top: 10px;
        border-top: 1px solid #DDE6E2;
        text-align: center;
        font-size: 9px;
        line-height: 13px;
        color: #66736F;
    }
</style>

<div class="pm-doc-footer">
    {{ $footerName }}
    ·
    Patrimoine <span style="color: #0E7A56;">365</span>
    ·
    {{ now()->format('Y-m-d') }}
</div>

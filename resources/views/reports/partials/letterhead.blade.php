{{--
    Shared organisation letterhead for every report PDF.

    Expects:
    - $managingOrganisation (nullable Party)
    - $title (optional report title line)

    The partial carries its own styles so each export layout renders an
    identical organisation identity block.
--}}
<style>
    .pm-letterhead {
        margin-bottom: 18px;
        border-bottom: 2px solid #123D35;
        padding-bottom: 12px;
    }

    .pm-letterhead-brand {
        font-size: 20px;
        font-weight: bold;
        color: #17201E;
    }

    .pm-letterhead-subtitle {
        margin-top: 2px;
        color: #66736F;
        font-size: 9px;
    }

    .pm-letterhead-title {
        margin-top: 12px;
        font-size: 16px;
        font-weight: bold;
        color: #17201E;
    }
</style>

<div class="pm-letterhead">
    <div class="pm-letterhead-brand">
        {{ $managingOrganisation?->legal_name
            ?? $managingOrganisation?->name
            ?? 'Patrimoine' }}
    </div>

    <div class="pm-letterhead-subtitle">
        {{ __('reports.property_management') }}
    </div>

    @if($managingOrganisation)
        @if($managingOrganisation->address)
            <div class="pm-letterhead-subtitle">
                {{ $managingOrganisation->address }}
            </div>
        @endif

        @if($managingOrganisation->phone || $managingOrganisation->email)
            <div class="pm-letterhead-subtitle">
                {{ $managingOrganisation->phone_display }}

                @if(
                    $managingOrganisation->phone
                    && $managingOrganisation->email
                )
                    |
                @endif

                {{ $managingOrganisation->email }}
            </div>
        @endif

        @if($managingOrganisation->vat_tin)
            <div class="pm-letterhead-subtitle">
                {{ __('reports.vat_tin') }}: {{ $managingOrganisation->vat_tin }}
            </div>
        @endif
    @endif

    @if(! empty($title))
        <div class="pm-letterhead-title">
            {{ $title }}
        </div>
    @endif
</div>

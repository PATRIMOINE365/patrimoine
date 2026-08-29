{{--
    Shared sign-off for organisation-sent transactional email (V1.1.0):
    the sending organisation's name and contact details.
--}}
<p style="margin:8px 0 0 0;">
    {{ __('emails.common.regards') }},<br>

    <strong>
        {{ $managingOrganisation?->legal_name
            ?? $managingOrganisation?->name
            ?? config('legal.product.name') }}
    </strong>

    @if($managingOrganisation?->phone || $managingOrganisation?->email)
        <br>
        <span style="color:#51615a; font-size:13px;">
            @if($managingOrganisation?->phone)
                {{ $managingOrganisation->phone_display }}
            @endif
            @if($managingOrganisation?->phone && $managingOrganisation?->email)
                &nbsp;·&nbsp;
            @endif
            @if($managingOrganisation?->email)
                {{ $managingOrganisation->email }}
            @endif
        </span>
    @endif
</p>

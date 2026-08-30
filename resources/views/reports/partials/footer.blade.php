{{--
    Shared report PDF footer: organisation attribution plus the
    generated-at stamp, formatted through ApplicationPresentationFormatter
    so timestamp presentation follows the configured language.

    Expects:
    - $managingOrganisation (nullable Party)
    - $generatedAt (optional DateTimeInterface)
    - $formatter (ApplicationPresentationFormatter, required with $generatedAt)
--}}
<style>
    .pm-report-footer {
        margin-top: 24px;
        border-top: 1px solid #DDE6E2;
        padding-top: 8px;
        color: #7E8C87;
        font-size: 8px;
    }
</style>

<div class="pm-report-footer">
    {{ __('reports.generated_by_patrimoine_for') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? __('reports.this_installation') }}.

    @if(! empty($generatedAt))
        {{ __('reports.generated') }}
        {{ $formatter->dateTime($generatedAt) }}
    @endif
</div>

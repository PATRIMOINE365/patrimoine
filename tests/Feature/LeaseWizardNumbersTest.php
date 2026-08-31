<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The assistant's numbers have to be readable and unambiguous.
 *
 * This exists because they were neither. Not one of the eleven numeric
 * fields on the lease assistant carried data-money-input, so a rent of
 * 1250000 was typed and shown as 1250000 — no grouping in either
 * currency, while every other money field in Patrimoine groups as you
 * type. And the two fields whose meaning depends on a setting above them
 * — the management fee and the rent increase, each of which is either a
 * percentage or an amount — said which nowhere at all.
 *
 * The rules are: a whole-currency field groups as it is typed, and a
 * field whose unit is not obvious from its label carries that unit at
 * the end of the number.
 */
class LeaseWizardNumbersTest extends TestCase
{
    private function markup(): string
    {
        return file_get_contents(
            resource_path('views/app/lease-wizard.blade.php')
        );
    }

    /**
     * Every whole-currency field on the assistant.
     *
     * Deliberately a list rather than a pattern: due day, VAT and the
     * percentages are numeric too and must NOT be grouped.
     *
     * @return array<int, string>
     */
    private function moneyFields(): array
    {
        return [
            'wizard-agent-commission',
            'wizard-rent-amount',
            'wizard-proration',
            'wizard-deposit',
            'wizard-reserve',
            'wizard-advance-amount',
        ];
    }

    public function test_every_money_field_groups_as_it_is_typed(): void
    {
        $markup = $this->markup();

        foreach ($this->moneyFields() as $field) {
            $this->assertMatchesRegularExpression(
                '/id="'.preg_quote($field, '/').'"[^>]*\s+data-money-input/s',
                $markup,
                $field.' does not group its thousands as the operator types.'
            );
        }
    }

    public function test_every_money_field_says_what_currency_it_is(): void
    {
        $markup = $this->markup();

        foreach ($this->moneyFields() as $field) {
            $this->assertStringContainsString(
                'id="'.$field.'-unit"',
                $markup,
                $field.' does not carry its currency.'
            );
        }
    }

    /**
     * The two fields that mean different things on different settings.
     */
    public function test_the_dual_mode_fields_carry_a_unit(): void
    {
        $markup = $this->markup();

        foreach (['wizard-fee-unit', 'wizard-increment-unit'] as $unit) {
            $this->assertStringContainsString(
                'id="'.$unit.'"',
                $markup,
                $unit.' is missing, so the number does not say whether it is a '
                .'percentage or an amount.'
            );
        }

        $script = file_get_contents(
            resource_path('js/lease-wizard.js')
        );

        foreach (['applyFeeType', 'applyIncrementType'] as $handler) {
            $this->assertStringContainsString(
                "on('wizard-",
                $script
            );

            $this->assertStringContainsString(
                'function '.$handler.'(',
                $script,
                $handler.' does not exist, so nothing sets the unit.'
            );
        }

        $this->assertStringContainsString(
            "on('wizard-fee-type', 'change', applyFeeType)",
            $script,
            'Changing the fee type does not update the unit beside the value.'
        );
    }

    /**
     * A percentage read as money, or money read as a percentage, is a
     * silent wrong number rather than a visible one.
     */
    public function test_a_dual_mode_field_is_read_the_way_it_is_written(): void
    {
        $script = file_get_contents(
            resource_path('js/lease-wizard.js')
        );

        $this->assertStringContainsString(
            'function dualValue(',
            $script
        );

        foreach (
            ['wizard-increment-value', 'wizard-fee-value'] as $field
        ) {
            $this->assertMatchesRegularExpression(
                '/dualValue\(\s*\''.preg_quote($field, '/').'\'/s',
                $script,
                $field.' is read with a fixed reader, so a grouped amount '
                .'would be misread as a decimal.'
            );
        }
    }

    /**
     * The cashier is shown, not asked for.
     */
    public function test_the_cashier_is_shown_and_cannot_be_typed(): void
    {
        $this->assertMatchesRegularExpression(
            '/id="wizard-advance-collector"[^>]*\s+readonly/s',
            $this->markup(),
            'The cashier must be shown as the signed-in user, never typed.'
        );
    }

    /**
     * The check page is the last thing read before anything is created.
     */
    public function test_the_review_page_formats_its_money(): void
    {
        $script = file_get_contents(
            resource_path('js/lease-wizard.js')
        );

        foreach (
            [
                'wizard-rent-amount',
                'wizard-deposit',
                'wizard-reserve',
                'wizard-advance-amount',
            ] as $field
        ) {
            $this->assertMatchesRegularExpression(
                '/money\(\''.preg_quote($field, '/').'\'\)/s',
                $script,
                $field.' is printed on the check page as raw digits.'
            );
        }
    }

    /**
     * A refusal without its code is a refusal nobody can look up.
     */
    public function test_the_error_box_carries_the_error_code(): void
    {
        $script = file_get_contents(
            resource_path('js/lease-wizard.js')
        );

        $this->assertStringContainsString(
            'messageWithErrorCode(message, code)',
            $script,
            'The assistant shows refusals without the PM code that explains them.'
        );

        $this->assertStringContainsString(
            'payload?.code ?? null',
            $script,
            'The code the server sent is not passed to the error box.'
        );
    }
}

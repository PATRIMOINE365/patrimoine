<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Protect the V1.0.5 Cash Receiver browser contract.
 *
 * Backend attribution is covered independently by
 * CashReceiverRuntimeAttributionTest and the payment/owner API tests.
 *
 * These assertions protect the browser/read-side contract:
 *
 * - authenticated User identity is exposed to financial forms;
 * - normal cash-receiver controls are locked;
 * - electronic methods hide the receiver;
 * - legacy Collector terminology is no longer presented as an editable
 *   operational concept;
 * - normalized User snapshots fall back to legacy collector_name only for
 *   historical records.
 */
class CashReceiverUiNormalizationTest extends TestCase
{
    public function test_authenticated_user_is_exposed_to_cash_receiver_ui(): void
    {
        $source = file_get_contents(
            resource_path('js/auth.js')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'V1.0.5 CASH RECEIVER UI NORMALIZATION',
            $source
        );

        $this->assertStringContainsString(
            'document.body.dataset.currentUserName',
            $source
        );

        $this->assertStringContainsString(
            'document.body.dataset.currentUserId',
            $source
        );
    }

    public function test_cash_receiver_is_locked_and_cash_only(): void
    {
        $source = file_get_contents(
            resource_path('js/auth.js')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "method.value === 'cash'",
            $source
        );

        $this->assertStringContainsString(
            'input.readOnly =',
            $source
        );

        $this->assertStringContainsString(
            "input.setAttribute(\n        'aria-readonly'",
            $source
        );

        $this->assertStringContainsString(
            "container.hidden =\n                true",
            $source
        );

        $this->assertStringContainsString(
            'currentUserName',
            $source
        );
    }

    public function test_dynamic_drawers_are_normalized(): void
    {
        $source = file_get_contents(
            resource_path('js/auth.js')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'MutationObserver',
            $source
        );

        $this->assertStringContainsString(
            'syncPatrimoineCashReceiverUi',
            $source
        );
    }

    public function test_operational_translation_uses_cash_receiver_term(): void
    {
        $english = file_get_contents(
            lang_path('en/ui.php')
        );

        $french = file_get_contents(
            lang_path('fr/ui.php')
        );

        $browser = file_get_contents(
            resource_path('js/translations.js')
        );

        $this->assertIsString($english);
        $this->assertIsString($french);
        $this->assertIsString($browser);

        $this->assertStringContainsString(
            "'cash_collector' => 'Cash Receiver'",
            $english
        );

        $this->assertStringContainsString(
            "'cash_collector' => 'Réceptionnaire des espèces'",
            $french
        );

        $this->assertStringContainsString(
            "'leases.cash_collector': 'Cash Receiver'",
            $browser
        );

        $this->assertStringContainsString(
            "'leases.cash_collector': 'Réceptionnaire des espèces'",
            $browser
        );
    }

    public function test_french_payment_form_does_not_invite_typed_cash_receiver(): void
    {
        $browser = file_get_contents(
            resource_path('js/translations.js')
        );

        $this->assertIsString($browser);

        $this->assertStringContainsString(
            "'payments.collector_placeholder': 'Défini automatiquement selon l’utilisateur connecté'",
            $browser
        );

        $this->assertStringContainsString(
            "'payments.collector_help': 'Défini automatiquement selon l’utilisateur connecté pour les paiements en espèces.'",
            $browser
        );

        $this->assertStringNotContainsString(
            "'payments.collector_placeholder': 'Nom de la personne ayant reçu les espèces'",
            $browser
        );

        $this->assertStringNotContainsString(
            "'payments.collector_help': 'Obligatoire pour les paiements en espèces à des fins de traçabilité.'",
            $browser
        );
    }

    public function test_financial_documents_use_cash_receiver_terminology(): void
    {
        $english = file_get_contents(
            lang_path('en/documents.php')
        );

        $french = file_get_contents(
            lang_path('fr/documents.php')
        );

        $this->assertIsString($english);
        $this->assertIsString($french);

        $this->assertStringContainsString(
            "'collector' => 'Cash Receiver'",
            $english
        );

        $this->assertStringContainsString(
            "'collector' => 'Réceptionnaire des espèces'",
            $french
        );
    }


    public function test_receipts_prefer_normalized_cash_receiver_snapshot(): void
    {
        $paymentReceipt = file_get_contents(
            resource_path(
                'views/documents/receipt.blade.php'
            )
        );

        $ownerReceipt = file_get_contents(
            resource_path(
                'views/documents/owner-deposit-receipt.blade.php'
            )
        );

        $this->assertIsString($paymentReceipt);
        $this->assertIsString($ownerReceipt);

        $this->assertStringContainsString(
            '$payment->cash_receiver_name ?? $payment->collector_name',
            $paymentReceipt
        );

        $this->assertStringContainsString(
            '$transaction->cash_receiver_name ?? $transaction->collector_name',
            $ownerReceipt
        );
    }

    public function test_legacy_collector_storage_remains_available_for_history(): void
    {
        $payment = file_get_contents(
            app_path('Models/Payment.php')
        );

        $ownerTransaction = file_get_contents(
            app_path('Models/OwnerTransaction.php')
        );

        $this->assertIsString($payment);
        $this->assertIsString($ownerTransaction);

        $this->assertStringContainsString(
            "'collector_name'",
            $payment
        );

        $this->assertStringContainsString(
            "'cash_receiver_name'",
            $payment
        );

        $this->assertStringContainsString(
            "'collector_name'",
            $ownerTransaction
        );

        $this->assertStringContainsString(
            "'cash_receiver_name'",
            $ownerTransaction
        );
    }
}

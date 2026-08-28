<?php

return [
    'title' => 'Journal financier',
    'generated_at' => 'Généré le',
    'filters' => 'Filtres',

    'entry_kinds' => [
        'financial' => 'Financière',
        'reversal' => 'Contre-passation',
        'informational' => 'Informative',
    ],

    'columns' => [
        'journal_number' => 'Numéro de journal',
        'journal_date' => 'Date du journal',
        'posted_at' => 'Horodatage',
        'entry_kind' => 'Type d’écriture',
        'transaction_type' => 'Type de transaction',
        'description' => 'Description',
        'actor' => 'Utilisateur',
        'source_type' => 'Type de source',
        'source_id' => 'ID source',
        'reversal_reference' => 'Référence de contre-passation',
        'account_code' => 'Code du compte',
        'account_name' => 'Nom du compte',
        'account_type' => 'Type de compte',
        'debit' => 'Débit',
        'credit' => 'Crédit',
        'memo' => 'Libellé',
    ],
    /*
    |--------------------------------------------------------------------------
    | Libellés des écritures comptabilisées
    |--------------------------------------------------------------------------
    |
    | Inscrits sur l'écriture au moment de la comptabilisation, dans la
    | langue de l'organisation, et figés définitivement.
    |
    */
    'descriptions' => [
        'owner_deposit' => 'Dépôt propriétaire n° :reference',
        'owner_payout' => 'Versement propriétaire n° :reference',
        'owner_rent_entitlement' => 'Droit du propriétaire sur l\'imputation de paiement n° :reference',
        'management_fee' => 'Frais de gestion sur l\'imputation de paiement n° :reference',
        'management_fee_vat' => 'TVA sur les frais de gestion pour l\'imputation de paiement n° :reference',
        'owner_adjustment' => 'Ajustement du solde du compte propriétaire.',

        'rent_invoice' => 'Facture de loyer :reference',
        'rent_receipt' => 'Imputation d\'encaissement de loyer n° :reference',

        'security_deposit_applied' => 'Caution imputée :reference',
        'security_deposit_debt_invoice' => 'Facture de dette sur caution :reference',
        'security_deposit_refund' => 'Remboursement de caution :reference',

        'expense_invoice_settlement' => 'Règlement de facture de dépense n° :reference',
        'tenant_fund_adjustment' => 'Ajustement du solde des fonds locataire.',
        'tenant_fund_withdrawal' => 'Retrait des fonds locataire n° :reference',
        'tenant_fund_transfer' => 'Transfert entre fonds locataire.',

        'rent_reserve_consumption' => 'Consommation de la réserve de loyer pour la facture :reference',
        'consumable_advance_consumption' => 'Consommation de l\'avance consommable pour la facture :reference',

        'rent_reserve_funding' => 'Alimentation de la réserve de loyer :reference',
        'consumable_advance_funding' => 'Alimentation de l\'avance consommable :reference',
        'security_deposit_funding' => 'Alimentation de la caution :reference',
        'tenant_fund_funding' => 'Alimentation des fonds locataire :reference',

        'reversal' => 'Contre-passation de :number : :reason',
    ],
    /*
    |--------------------------------------------------------------------------
    | Libellés des types d'opération
    |--------------------------------------------------------------------------
    |
    | Affichés sous chaque écriture. Un type absent d'ici retombe sur son
    | identifiant mis en forme.
    |
    */
    'transaction_types' => [
        'rent_invoice' => 'Facture de loyer',
        'rent_receipt' => 'Encaissement de loyer',
        'owner_deposit' => 'Dépôt propriétaire',
        'owner_payout' => 'Versement propriétaire',
        'owner_expense' => 'Charge propriétaire',
        'owner_rent_entitlement' => 'Droit du propriétaire',
        'owner_adjustment' => 'Ajustement propriétaire',
        'management_fee' => 'Frais de gestion',
        'management_fee_vat' => 'TVA sur frais de gestion',
        'advance_consumption' => 'Consommation d’avance',
        'rent_reserve_consumption' => 'Consommation de réserve',
        'rent_reserve_funding' => 'Alimentation de réserve',
        'consumable_advance_funding' => 'Alimentation d’avance',
        'security_deposit_funding' => 'Alimentation de caution',
        'security_deposit_settlement' => 'Règlement de caution',
        'security_deposit_refund' => 'Remboursement de caution',
        'security_deposit_debt' => 'Dette sur caution',
        'tenant_fund_funding' => 'Alimentation de fonds locataire',
        'tenant_fund_expense' => 'Dépense sur fonds locataire',
        'tenant_fund_transfer' => 'Transfert de fonds locataire',
        'tenant_expense_settlement' => 'Règlement de dépense locataire',
        'journal_reversal' => 'Contre-passation',
        'v1_0_5_opening_balance' => 'Solde d’ouverture',
    ],
];

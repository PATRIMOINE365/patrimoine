<?php

return [
    'title' => 'Financial Journal',
    'generated_at' => 'Generated',
    'filters' => 'Filters',

    'entry_kinds' => [
        'financial' => 'Financial',
        'reversal' => 'Reversal',
        'informational' => 'Informational',
    ],

    'columns' => [
        'journal_number' => 'Journal Number',
        'journal_date' => 'Journal Date',
        'posted_at' => 'Posted At',
        'entry_kind' => 'Entry Kind',
        'transaction_type' => 'Transaction Type',
        'description' => 'Description',
        'actor' => 'Actor',
        'source_type' => 'Source Type',
        'source_id' => 'Source ID',
        'reversal_reference' => 'Reversal Reference',
        'account_code' => 'Account Code',
        'account_name' => 'Account Name',
        'account_type' => 'Account Type',
        'debit' => 'Debit',
        'credit' => 'Credit',
        'memo' => 'Memo',
    ],
    /*
    |--------------------------------------------------------------------------
    | Posted entry descriptions
    |--------------------------------------------------------------------------
    |
    | Written onto the journal entry at posting time, in the organisation's
    | own language, and frozen there for good.
    |
    */
    'descriptions' => [
        'owner_deposit' => 'Owner Deposit #:reference',
        'owner_payout' => 'Owner Payout #:reference',
        'owner_rent_entitlement' => 'Owner rent entitlement for payment allocation #:reference',
        'management_fee' => 'Management fee for payment allocation #:reference',
        'owner_adjustment' => 'Owner Account balance adjustment.',

        'rent_invoice' => 'Rent invoice :reference',
        'rent_receipt' => 'Rent receipt allocation #:reference',

        'security_deposit_applied' => 'Security Deposit applied :reference',
        'security_deposit_debt_invoice' => 'Security Deposit debt invoice :reference',
        'security_deposit_refund' => 'Security Deposit refund :reference',

        'expense_invoice_settlement' => 'Expense invoice settlement #:reference',
        'tenant_fund_adjustment' => 'Tenant fund balance adjustment.',
        'tenant_fund_withdrawal' => 'Tenant fund withdrawal #:reference',
        'tenant_fund_transfer' => 'Tenant fund transfer.',

        'rent_reserve_consumption' => 'Rent Reserve consumption for rent invoice :reference',
        'consumable_advance_consumption' => 'Consumable Advance consumption for rent invoice :reference',

        'rent_reserve_funding' => 'Rent Reserve funding :reference',
        'consumable_advance_funding' => 'Consumable Advance funding :reference',
        'security_deposit_funding' => 'Security Deposit funding :reference',
        'tenant_fund_funding' => 'Tenant fund funding :reference',

        'reversal' => 'Reversal of :number: :reason',
    ],
];

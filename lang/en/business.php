<?php

return [
    'fund_accounts' => [
        'not_rent_reserve' => 'The selected tenant fund account is not a Rent Reserve account.',
        'not_consumable_advance' => 'The selected tenant fund account is not a Consumable Advance account.',
    ],

    'rent_reserve' => [
        'wrong_account_type' => 'Only a Rent Reserve account can be consumed by this service.',
        'account_closed' => 'Rent Reserve account is closed.',
        'before_notice' => 'Rent Reserve cannot be consumed before termination notice.',
        'wrong_invoice_lease' => 'The Invoice does not belong to the Rent Reserve Lease.',
        'rent_only' => 'Rent Reserve can only settle rent invoices.',
        'amount_positive' => 'Rent Reserve consumption amount must be greater than zero.',
        'insufficient_balance' => 'Rent Reserve balance is insufficient.',
        'exceeds_invoice' => 'Rent Reserve consumption exceeds the Invoice outstanding amount.',
        'no_ownership' => 'Building has no ownership allocations.',
        'ownership_total' => 'Building ownership percentages must total 100%.',
    ],

    'consumable_advance' => [
        'wrong_account_type' => 'Only a Consumable Advance account can be consumed by this service.',
        'account_closed' => 'Consumable Advance account is closed.',
        'draft_lease' => 'Consumable Advance cannot be used for a draft Lease.',
        'wrong_invoice_lease' => 'The Invoice does not belong to the Consumable Advance Lease.',
        'rent_only' => 'Consumable Advance can only settle rent invoices.',
        'amount_positive' => 'Consumable Advance amount must be greater than zero.',
        'insufficient_balance' => 'Consumable Advance balance is insufficient.',
        'exceeds_invoice' => 'Consumable Advance exceeds the Invoice outstanding amount.',
        'no_ownership' => 'Building has no ownership allocations.',
        'ownership_total' => 'Building ownership percentages must total 100%.',
    ],

    'owner' => [
        'deposit_positive' => 'Owner deposit amount must be greater than zero.',
        'deposit_payment_method' => 'Unsupported owner deposit payment method.',
        'deposit_purpose' => 'Unsupported owner deposit purpose.',
        'cash_collector_required' => 'Cash Receiver could not be determined for this cash owner deposit.',
        'adjustment_direction' => 'Owner adjustment direction must be credit or debit.',
        'adjustment_positive' => 'Owner adjustment amount must be greater than zero.',
        'adjustment_reason' => 'Owner adjustment reason is required.',
        'no_ownership' => 'Building has no ownership allocations.',
        'ownership_total' => 'Building ownership percentages must total 100%.',
        'payout_positive' => 'Owner payout amount must be greater than zero.',
        'reserve_transfer_positive' => 'Account transfer amount must be greater than zero.',
        'reserve_transfer_reason_required' => 'Account transfer reason is required.',
        'reserve_transfer_exceeds_source' => 'Account transfer exceeds the available source account balance.',
        'payout_no_funds' => 'Owner has no funds available for payout.',
        'payout_exceeds_balance' => 'Owner payout exceeds available balance.',
        'payout_allocation_failed' => 'Unable to fully allocate owner payout to net available credits.',
        'deposit_receipt_only' => 'Only Owner deposits may generate an Owner deposit receipt.',
    ],

    'security_deposit' => [
        'deductions_after_settlement' => 'Security deposit deductions cannot be changed after final settlement.',
        'deductions_terminated_only' => 'Security deposit deductions can only be recorded for a terminated Lease.',
        'account_missing' => 'No security deposit account exists for this Lease.',
        'already_settled' => 'Security deposit has already been settled for this Lease.',
        'account_missing' => 'No security deposit account exists for this Lease.',
        'negative_balance' => 'Security deposit account has an invalid negative balance.',
    ],

    'email' => [
        'tenant_email_missing' => 'Tenant does not have an email address.',
        'owner_email_missing' => 'Owner does not have an email address.',
    ],
];

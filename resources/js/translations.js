/*
|--------------------------------------------------------------------------
| Patrimoine Browser Translations
|--------------------------------------------------------------------------
|
| User-facing browser text is translated from stable keys.
|
| English is the compatibility fallback language. A missing key in another
| language therefore falls back to English rather than exposing the key or
| preventing the application from rendering.
|
| Translation belongs to presentation only. Business values, API field
| names and persisted domain values must never be translated here.
|
*/

export const translations = {
    en: {
        /* ---- V1.0.7 additions ---- */
        'leases.record_deduction':
            'Record a security deposit deduction',
        'leases.record_deduction_description':
            'Itemized deductions reduce the refundable deposit before settlement.',
        'leases.deduction_description':
            'Description',
        'leases.deduction_amount':
            'Amount',
        'leases.deduction_fields_required':
            'Provide a description, a whole amount greater than zero, and a date.',
        'leases.deduction_record_failed':
            'Unable to record the deduction.',
        'release.summary_line':
            'This update brings new features and improvements across Patrimoine 365.',
        'release.view_details':
            'View the full update log',
        'dashboard.occupancy_rate':
            'Occupancy Rate',
        'dashboard.occupied':
            'Occupied',
        'dashboard.vacant':
            'Vacant',
        'dashboard.vacant_commercial':
            'Commercial vacant',
        'dashboard.vacant_residential':
            'Residential vacant',
        'dashboard.collections_trend':
            'Collections Trend',
        'dashboard.collections_trend_description':
            'Rent collected over the last six months.',
        'dashboard.funds_held':
            'Funds Held',
        'dashboard.funds_held_description':
            'Balances currently held on behalf of owners and tenants.',
        'dashboard.tenant_funds_held':
            'Tenant Funds Held',
        'dashboard.expiring_leases':
            'Expiring Leases',
        'dashboard.expiring_leases_description':
            'Leases ending within the next 90 days.',
        'dashboard.upcoming_increments':
            'Upcoming Rent Increments',
        'dashboard.upcoming_increments_description':
            'Rent increases taking effect within the next 60 days.',
        'dashboard.ends':
            'Ends',
        'dashboard.effective':
            'Effective',
        'dashboard.no_expiring_leases':
            'No leases expire within the next 90 days.',
        'dashboard.no_increments':
            'No rent increments are scheduled.',
        'dashboard.no_collections':
            'No collections recorded yet.',
        /* ---- V1.0.9 additions ---- */
        'dashboard.management_fees_this_month':
            'Management Fees This Month',
        'dashboard.more_records':
            '+:count more',
        'dashboard.paid_of_total':
            ':paid of :total paid',
        'dashboard.increments_count_aria':
            'Number of rent increments taking effect within the next 60 days',
        'dashboard.unable_to_load_section':
            'Unable to load this section.',
        /* ---- end V1.0.9 additions ---- */
        'activity_metadata.format':
            'Format',

        'activity_metadata.report_type':
            'Report Type',

        'activity_metadata.document_type':
            'Document Type',

        'activity_metadata.delivery':
            'Delivery',

        'activity_metadata.reference':
            'Reference',

        'activity_metadata.invitation_sent':
            'Invitation Sent',

        'activity_metadata.source':
            'Source',

        'activity_actions.auth.login':
            'Signed In',

        'activity_actions.auth.login_failed':
            'Sign-In Failed',

        'activity_actions.auth.logout':
            'Signed Out',

        'activity_actions.user.created':
            'User Created',

        'activity_actions.user.updated':
            'User Updated',

        'activity_actions.user.deleted':
            'User Deleted',

        'activity_actions.user.invitation_resent':
            'User Invitation Resent',

        'activity_actions.user.password_reset_requested':
            'User Password Reset Requested',

        'activity_actions.user.invitation_accepted':
            'User Invitation Accepted',

        'activity_actions.user.password_reset':
            'Password Reset Completed',

        'activity_actions.user.password_changed':
            'Password Changed',

        'activity_actions.party.created':
            'Party Created',

        'activity_actions.party.updated':
            'Party Updated',

        'activity_actions.party.deleted':
            'Party Deleted',

        'activity_actions.building.created':
            'Building Created',

        'activity_actions.building.updated':
            'Building Updated',

        'activity_actions.building.deleted':
            'Building Deleted',

        'activity_actions.unit.created':
            'Unit Created',

        'activity_actions.unit.updated':
            'Unit Updated',

        'activity_actions.unit.deleted':
            'Unit Deleted',

        /* ---- V1.0.35: actions that were written but never named ---- */
        'activity_actions.adjustment_voucher.downloaded':
            'Adjustment Voucher Downloaded',
        'activity_actions.auth.email_verified':
            'E-mail Address Verified',
        'activity_actions.auth.invitation_accepted':
            'Invitation Accepted',
        'activity_actions.auth.password_changed':
            'Password Changed',
        'activity_actions.auth.password_reset':
            'Password Reset',
        'activity_actions.expense_invoice.created':
            'Tenant Expense Invoice Created',
        'activity_actions.invoice_account_payment.cancelled':
            'Invoice Payment From Account Cancelled',
        'activity_actions.invoice_account_payment.recorded':
            'Invoice Paid From Account',
        'activity_actions.invoice_payment_receipt.downloaded':
            'Invoice Payment Receipt Downloaded',
        'activity_actions.lease.extended':
            'Lease Extended',
        'activity_actions.lease.rent_increment_cancelled':
            'Rent Increase Cancelled',
        'activity_actions.lease.rent_increment_scheduled':
            'Rent Increase Scheduled',
        'activity_actions.lease.termination_cancelled':
            'Lease Termination Cancelled',
        'activity_actions.lease.termination_completed':
            'Lease Termination Completed',
        'activity_actions.lease.termination_initiated':
            'Lease Termination Started',
        'activity_actions.lease.termination_notice_downloaded':
            'Termination Notice Downloaded',
        'activity_actions.organisation.closed_by_customer':
            'Account Closed By Customer',
        'activity_actions.organisation.data_exported':
            'Organisation Data Exported',
        'activity_actions.organisation.registered':
            'Organisation Registered',
        'activity_actions.owner_expense_bill.downloaded':
            'Expense Bill Downloaded',
        'activity_actions.owner_expense_bill.recorded':
            'Expense Bill Recorded',
        'activity_actions.owner_expense_bill.resent':
            'Expense Bill Resent',
        'activity_actions.owner_expense_bill_payment.cancelled':
            'Expense Bill Payment Cancelled',
        'activity_actions.owner_expense_bill_payment.recorded':
            'Expense Bill Paid',
        'activity_actions.owner_expense_bill_payment_receipt.downloaded':
            'Expense Bill Payment Receipt Downloaded',
        'activity_actions.owner_payout_receipt.downloaded':
            'Owner Payout Receipt Downloaded',
        'activity_actions.owner_reserve_transfer.recorded':
            'Transfer Between Owner Accounts',
        'activity_actions.owner_reserve_transfer_voucher.downloaded':
            'Owner Transfer Voucher Downloaded',
        'activity_actions.owner_reserve_transfer_voucher.resent':
            'Owner Transfer Voucher Resent',
        'activity_actions.party.data_exported':
            'Party Data Exported',
        'activity_actions.party.erased':
            'Party Erased',
        'activity_actions.registry.exported':
            'Registry Exported',
        'activity_actions.registry.imported':
            'Registry Imported',
        'activity_actions.security_deposit.deduction_added':
            'Security Deposit Deduction Added',
        'activity_actions.tenant_adjustment.recorded':
            'Tenant Balance Adjusted',
        'activity_actions.tenant_expense.recorded':
            'Tenant Expense Recorded',
        'activity_actions.tenant_expense_voucher.downloaded':
            'Tenant Expense Voucher Downloaded',
        'activity_actions.tenant_expense_voucher.resent':
            'Tenant Expense Voucher Resent',
        'activity_actions.tenant_fund.deposit':
            'Tenant Fund Deposit',
        'activity_actions.tenant_fund.transfer_recorded':
            'Transfer Between Tenant Accounts',
        'activity_actions.tenant_fund_transfer_voucher.downloaded':
            'Tenant Transfer Voucher Downloaded',
        'activity_actions.tenant_fund_transfer_voucher.resent':
            'Tenant Transfer Voucher Resent',
        'activity_actions.tenant_withdrawal.recorded':
            'Tenant Withdrawal Recorded',
        'activity_actions.lease.created':
            'Lease Created',

        'activity_actions.lease.updated':
            'Lease Updated',

        'activity_actions.lease.deleted':
            'Lease Deleted',

        'activity_actions.managing_organisation.created':
            'Managing Organisation Configured',

        'activity_actions.managing_organisation.updated':
            'Managing Organisation Updated',

        'activity_actions.payment.recorded':
            'Payment Recorded',

        'activity_actions.tenant_fund.classified':
            'Tenant Funds Classified',

        'activity_actions.rent_reserve.consumed':
            'Rent Reserve Consumed',

        'activity_actions.consumable_advance.consumed':
            'Consumable Advance Consumed',

        'activity_actions.security_deposit.deduction_recorded':
            'Security Deposit Deduction Recorded',

        'activity_actions.security_deposit.settled':
            'Security Deposit Settled',

        'activity_actions.owner_expense.recorded':
            'Owner Expense Recorded',

        'activity_actions.owner_deposit.recorded':
            'Owner Deposit Recorded',

        'activity_actions.owner_adjustment.recorded':
            'Owner Adjustment Recorded',

        'activity_actions.owner_payout.recorded':
            'Owner Payout Recorded',

        'activity_actions.invoice.downloaded':
            'Invoice Downloaded',

        'activity_actions.receipt.downloaded':
            'Receipt Downloaded',

        'activity_actions.owner_deposit_receipt.downloaded':
            'Owner Deposit Receipt Downloaded',

        'activity_actions.security_deposit_voucher.downloaded':
            'Security Deposit Voucher Downloaded',

        'activity_actions.invoice.resent':
            'Invoice Resent',

        'activity_actions.email.suppressed':
            'Email Withheld',

        'activity_actions.receipt.resent':
            'Receipt Resent',

        'activity_actions.report.exported':
            'Report Exported',

        'activity_actions.activity_log.exported':
            'Activity Log Exported',

        'activity_entities.user':
            'User',

        'activity_entities.party':
            'Party',

        'activity_entities.building':
            'Building',

        'activity_entities.unit':
            'Unit',

        'activity_entities.lease':
            'Lease',

        'activity_entities.payment':
            'Payment',

        'activity_entities.invoice':
            'Invoice',

        'activity_entities.receipt':
            'Receipt',

        'activity_entities.tenant_fund':
            'Tenant Fund',

        'activity_entities.rent_reserve':
            'Rent Reserve',

        'activity_entities.consumable_advance':
            'Consumable Advance',

        'activity_entities.security_deposit':
            'Security Deposit',

        'activity_entities.owner_expense':
            'Owner Expense',

        'activity_entities.owner_account':
            'Owner Account',

        'activity_entities.owner_transaction':
            'Owner Transaction',

        'activity_entities.owner_payout':
            'Owner Payout',

        'activity_entities.managing_organisation':
            'Managing Organisation',

        'activity_entities.report':
            'Report',

        'activity_entities.activity_log':
            'Activity Log',

        'shell.my_profile':
            'My Profile',

        'shell.my_profile_description':
            'Update my profile',

        'shell.profile_description':
            'Update your account information.',

        'password.section':
            'Password',

        'password.profile_new_help':
            'Leave blank to keep your current password.',

        'password.profile_current_help':
            'Required only when setting a new password.',

        'tenants.payment_method_cash':
            'Cash',

        'tenants.payment_method_bank_transfer':
            'Bank Transfer',

        'tenants.payment_method_momo':
            'Mobile Payment',

        'tenants.payment_method_cheque':
            'Cheque',

        'owners.collector_placeholder':
            'Automatically set to the logged-in User',

        'financial_journal.transaction_types.management_fee_vat':
            'Management fee VAT',

        'errors.heading':
            'Error codes',

        'errors.intro':
            'Every message Patrimoine shows when something does not work carries a code. Look it up here to see what happened, and what to do next.',

        'errors.search_label':
            'Search',

        'errors.search_placeholder':
            'Search a code, or words from the message',

        'errors.what_happened':
            'What happened',

        'errors.what_to_do':
            'What to do',

        'errors.no_matches':
            'Nothing matches what you typed. Try fewer words, or the code itself.',

        'errors.severity_fix_yourself':
            'You can put this right',

        'errors.severity_try_again':
            'Worth trying again',

        'errors.severity_ask_admin':
            'An administrator can help',

        'errors.severity_contact_us':
            'This one is ours',

        'errors.explain_code':
            'What does this mean?',

        'wizard.title':
            'Guided lease',

        'wizard.eyebrow':
            'Leases',

        'wizard.heading':
            'Create a lease, step by step',

        'wizard.subtitle':
            'Everything a letting needs, in one go. Nothing is saved until the last page.',

        'wizard.cancel':
            'Cancel',

        'wizard.launch':
            'Guided lease',

        'wizard.invite_title':
            'Create your first lease',

        'wizard.invite_text':
            'The guided assistant sets up the property, the owner, the tenant and the lease in one sitting.',

        'wizard.step_counter':
            'Step :current of :total',

        'wizard.back':
            'Back',

        'wizard.next':
            'Next',

        'wizard.save_draft':
            'Save as draft',

        'wizard.drafts_title':
            "Unfinished assistants",

        'wizard.drafts_note':
            "Started but not finished. Continue where it was left, or throw it away.",

        'wizard.drafts_continue':
            "Continue",

        'wizard.drafts_discard':
            "Discard",

        'wizard.drafts_discard_confirm':
            "Discard?",

        'wizard.drafts_discard_failed':
            "This one could not be discarded. Try again.",

        'wizard.draft_missing':
            "That assistant is no longer there. It may have been finished or discarded.",

        'wizard.create_activate':
            'Save and activate',

        'wizard.saving':
            'Saving…',

        'wizard.load_failed':
            'Your properties and parties could not be loaded.',

        'wizard.save_failed':
            'The lease could not be created. Nothing was saved.',

        'wizard.step1_title':
            'A few words first',

        'wizard.step2_title':
            'Property and unit',

        'wizard.step3_title':
            'Who owns it',

        'wizard.step4_title':
            'Who is renting',

        'wizard.step5_title':
            'Is an agent involved',

        'wizard.step6_title':
            'How long the lease runs',

        'wizard.step7_title':
            'Notice and rent increases',

        'wizard.step8_title':
            'Rent and advance payment',

        'wizard.step9_title':
            'Fees and commission',

        'wizard.step10_title':
            'Check and create',

        'wizard.glossary_party_term':
            'Party',

        'wizard.glossary_party_text':
            'Anyone you deal with: a person, a company or an association.',

        'wizard.glossary_owner_term':
            'Owner',

        'wizard.glossary_owner_text':
            'The party the property belongs to. You collect rent on their behalf.',

        'wizard.glossary_tenant_term':
            'Tenant',

        'wizard.glossary_tenant_text':
            'The party who occupies the unit and pays the rent.',

        'wizard.glossary_agent_term':
            'Agent',

        'wizard.glossary_agent_text':
            'A party who introduced the tenant and is paid a commission. Optional.',

        'wizard.glossary_property_term':
            'Property',

        'wizard.glossary_property_text':
            'A building or a plot. It holds one or more units.',

        'wizard.glossary_unit_term':
            'Unit',

        'wizard.glossary_unit_text':
            'What is actually let: a flat, a shop, an office.',

        'wizard.glossary_lease_term':
            'Lease',

        'wizard.glossary_lease_text':
            'The agreement between one tenant and one unit, with its rent and its dates.',

        'wizard.step1_note':
            'You can go back at any point. Nothing is saved until you finish.',

        'wizard.property':
            'Property',

        'wizard.use_existing_property':
            'Use a property I already have',

        'wizard.add_new_property':
            'Add a new property',

        'wizard.choose_property':
            'Choose the property',

        'wizard.property_name':
            'Property name',

        'wizard.property_address':
            'Address',

        'wizard.unit':
            'Unit',

        'wizard.use_existing_unit':
            'Use an existing unit',

        'wizard.add_new_unit':
            'Add a new unit',

        'wizard.choose_unit':
            'Choose the unit',

        'wizard.vacant_units_only':
            'Only vacant units are listed. A unit can carry one active lease at a time.',

        'wizard.unit_name':
            'Unit name or number',

        'wizard.unit_commercial':
            'This is a commercial unit',

        'wizard.step3_note':
            'Shares must add up to 100%. This page is skipped when the property already has its owners.',

        'wizard.add_owner':
            'Add another owner',

        'wizard.owner':
            'Owner',

        'wizard.share':
            'Share (%)',

        'wizard.choose_owner':
            'Choose the owner',

        'wizard.owner_total':
            'Total: :total%',

        'wizard.remove':
            'Remove',

        'wizard.use_existing_party':
            'Choose someone already recorded',

        'wizard.add_new_party':
            'Add someone new',

        'wizard.party_type':
            'Kind',

        'wizard.person':
            'Person',

        'wizard.organisation':
            'Organisation',

        'wizard.given_names':
            'Given names',

        'wizard.surname':
            'Surname',

        'wizard.legal_name':
            'Legal name',

        'wizard.contact_name':
            'Contact person',

        'wizard.phone':
            'Phone',

        'wizard.email':
            'Email',

        'wizard.tenant':
            'Tenant',

        'wizard.choose_tenant':
            'Choose the tenant',

        'wizard.agent':
            'Agent',

        'wizard.no_agent':
            'No agent',

        'wizard.choose_agent':
            'Choose the agent',

        'wizard.agent_commission':
            'Commission',

        'wizard.agent_commission_help':
            'Paid once, out of the owner’s money.',

        'wizard.start_date':
            'Start date',

        'wizard.duration':
            'Duration',

        'wizard.duration_12':
            '12 months',

        'wizard.duration_6':
            '6 months',

        'wizard.duration_24':
            '24 months',

        'wizard.duration_custom':
            'Choose the end date myself',

        'wizard.duration_open':
            'No end date',

        'wizard.end_date':
            'End date',

        'wizard.end_date_help':
            'An end date is not required. Without one, the lease runs until somebody terminates it.',

        'wizard.notice_date':
            'Notice date',

        'wizard.notice_date_help':
            'When notice must be given. Leave empty if there is no agreed date.',

        'wizard.increment_type':
            'Rent increase',

        'wizard.increment_none':
            'No planned increase',

        'wizard.increment_percentage':
            'A percentage',

        'wizard.increment_fixed':
            'A fixed amount',

        'wizard.increment_value':
            'Increase',

        'wizard.increment_date':
            'Next increase date',

        'wizard.rent_amount':
            'Rent',

        'wizard.frequency':
            'Paid every',

        'wizard.frequency_monthly':
            'Month',

        'wizard.frequency_quarterly':
            'Quarter',

        'wizard.frequency_bi_yearly':
            'Six months',

        'wizard.frequency_yearly':
            'Year',

        'wizard.due_day':
            'Due day',

        'wizard.due_day_help':
            'Leave empty to use the start date’s day.',

        'wizard.proration':
            'First period adjustment',

        'wizard.proration_help':
            'Leave empty and Patrimoine works it out.',

        'wizard.security_deposit':
            'Security deposit',

        'wizard.rent_reserve':
            'Rent reserve',

        'wizard.advance_amount':
            'Advance payment',

        'wizard.advance_received':
            'Already received',

        'wizard.advance_date':
            'Received on',

        'wizard.advance_method':
            'Received by',

        'wizard.method_cash':
            'Cash',

        'wizard.method_bank_transfer':
            'Bank transfer',

        'wizard.method_cheque':
            'Cheque',

        'wizard.method_mobile_money':
            'Mobile money',

        'wizard.advance_reference':
            'Reference',

        'wizard.fee_type':
            'Management fee',

        'wizard.fee_percentage':
            'Percentage of rent',

        'wizard.fee_fixed':
            'Fixed amount',

        'wizard.fee_none':
            'No fee',

        'wizard.fee_value':
            'Fee',

        'wizard.fee_vat':
            'VAT on the fee (%)',

        'wizard.fee_vat_help':
            'VAT is charged on your fee and billed to the owner, never on the rent.',

        'wizard.commission_echo':
            'Agent commission entered earlier:',

        'wizard.step10_note':
            'Creating the lease also creates everything above it. A draft can be activated later from the Leases page.',

        'parties.email_policy':
            'Email communications',

        'parties.email_policy_description':
            'Whether Patrimoine may send documents and notices to this party.',

        'parties.email_policy_inherit':
            'Follow organisation setting',

        'parties.email_policy_always':
            'Always email this party',

        'parties.email_policy_never':
            'Never email this party',

        'parties.email_policy_help':
            'Invoices, receipts, reminders, notices and vouchers. Emails to Patrimoine users are never affected.',

        'parties.emails_off':
            'No emails',

        'parties.given_names':
            'Given names',
        'parties.surname':
            'Surname',
        'parties.has_email':
            'Has email',
        'parties.has_email_all':
            'Email: All',
        'parties.has_email_yes':
            'Has email',
        'parties.has_email_no':
            'No email',
        'parties.sort_by_surname':
            'Sort by surname',
        'parties.sort_presentation_only':
            'Display only — sorts the loaded page',
        'parties.delete_party':
            'Delete Party',
        'parties.delete_party_description':
            'This action is permanent and cannot be undone.',
        'parties.delete_party_prompt':
            'You are about to delete:',
        'parties.deleting_party':
            'Deleting…',
        'reports.occupancy_report':
            'Occupancy',
        'reports.occupancy_report_summary':
            'Portfolio-wide occupancy and vacancy by building.',
        'reports.occupancy_report_description':
            'Occupied and vacant units across the portfolio, split by classification and building.',
        'reports.arrears_report':
            'Arrears Aging',
        'reports.arrears_report_summary':
            'Outstanding tenant balances by age bucket.',
        'reports.arrears_report_description':
            'Outstanding invoices grouped into aging buckets per tenant.',
        'reports.funds_report':
            'Funds Held',
        'reports.funds_report_summary':
            'Tenant and owner funds currently held.',
        'reports.funds_report_description':
            'Rent reserves, consumable advances, security deposits and owner balances held.',
        'reports.as_of_heading':
            'Reference date',
        'reports.as_of_description':
            'Optional snapshot date. Leave empty for today.',
        'reports.as_of':
            'As of',
        'reports.occupied':
            'Occupied',
        'reports.vacant':
            'Vacant',
        'reports.occupancy_rate':
            'Occupancy rate',
        'reports.occupancy_by_classification':
            'Commercial vs residential',
        'reports.commercial':
            'Commercial',
        'reports.residential':
            'Residential',
        'reports.commercial_units':
            'Commercial units',
        'reports.aging_current':
            'Current',
        'reports.aging_31_60':
            '31-60 days',
        'reports.aging_61_90':
            '61-90 days',
        'reports.aging_over_90':
            'Over 90 days',
        'reports.total_arrears':
            'Total arrears',
        'reports.open_invoices':
            'Open invoices',
        'reports.tenants_in_arrears':
            'Tenants in arrears',
        'reports.total_held':
            'Total held',
        'reports.owner_funds':
            'Owner funds',
        'reports.balance':
            'Balance',
        'reports.account_count':
            ':count accounts',
        'reports.select_date':
            'Select date',
        'reports.date_placeholder':
            'dd/mm/yyyy',
        'reports.reset_filters':
            'Reset filters',
        'reports.stale_results':
            'Results are out of date — run the report again.',
        'reports.result_rows':
            ':count rows',
        'properties.filter_units_label':
            'Filter units by classification',
        'properties.occupied': 'Occupied',
        'properties.search_owner_placeholder': 'Search owner by name, phone or email…',
        'properties.no_matching_owners': 'No matching owners found.',
        'properties.vacant': 'Vacant',
        'properties.filter_all_units':
            'All units',
        'properties.commercial':
            'Commercial',
        'properties.residential':
            'Residential',
        'properties.classification':
            'Classification',
        'properties.actions':
            'Actions',
        'properties.delete':
            'Delete',
        'properties.no_units_match_filter':
            'No units match the current filter.',
        'properties.commercial_unit':
            'Commercial unit',
        'properties.commercial_unit_help':
            'Mark this unit as leased for business or commercial use.',
        'properties.given_names':
            'Given names',
        'properties.surname':
            'Surname',
        'properties.delete_property':
            'Delete property',
        'properties.delete_property_description':
            'Permanently remove this property and its records.',
        'properties.delete_property_warning':
            'This action cannot be undone. Properties with dependent records cannot be deleted.',
        'properties.type_name_to_confirm':
            'Type the property name to confirm',
        'properties.deleting':
            'Deleting…',
        'properties.property_deleted':
            'Property deleted.',
        'properties.unable_to_delete_property':
            'Unable to delete the property.',
        'properties.delete_unit':
            'Delete unit',
        'properties.delete_unit_description':
            'Permanently remove this unit from its property.',
        'properties.delete_unit_warning':
            'This action cannot be undone. Units with dependent records cannot be deleted.',
        'properties.unit_deleted':
            'Unit deleted.',
        'properties.unable_to_delete_unit':
            'Unable to delete the unit.',
        'properties.property_created':
            'Property created.',
        'properties.property_updated':
            'Property updated.',
        'properties.unit_added':
            'Unit added.',
        'properties.unit_updated':
            'Unit updated.',
        'owners.accounts':
            'Accounts',
        'owners.owner_accounts_title':
            'Owner Accounts',
        'owners.owner_accounts_description':
            'Consolidated financial position for this Property Owner.',
        'owners.consolidated_account_note':
            'Each owner has one consolidated account: every property, deposit, expense and payout settles into this single balance.',
        'owners.recent_activity':
            'Recent Activity',
        'owners.recent_activity_description':
            'Latest ledger movements already loaded for this owner.',
        'owners.date':
            'Date',
        'owners.type':
            'Type',
        'owners.expense_bill_title':
            'Record Expense Bill',
        'owners.expense_bill_description':
            'Bill one or more expense lines directly to this Property Owner\'s consolidated account.',
        'owners.property_expense_switch_hint':
            'Need to record an expense against a specific Building instead?',
        'owners.bill_date':
            'Bill Date',
        'owners.pay': 'Pay',
        'owners.cancel_payment': 'Cancel Payment',
        'owners.expenses': 'Expenses',
        'owners.expense_bills_description': 'Expense bills stay unpaid until settled from an owner account through the Pay action; a payment can be cancelled again.',
        'owners.no_expense_bills': 'No expense bills recorded yet.',
        'owners.expense_bill': 'Expense Bill',
        'owners.paid': 'Paid',
        'owners.outstanding': 'Outstanding',
        'owners.status': 'Status',
        'owners.actions': 'Actions',
        'owners.bill_status_unpaid': 'Unpaid',
        'owners.bill_status_partial': 'Partially Paid',
        'owners.bill_status_paid': 'Paid',
        'owners.pay_bill_title': 'Pay Expense Bill',
        'owners.pay_bill_description': "Settle this bill from one side of the owner's money.",
        'owners.pay_source_account': 'Source Account',
        'owners.deposit_account': 'Deposit / Expense Account',
        'owners.payout_account': 'Payout Account',
        'owners.transaction_date': 'Date',
        'owners.pay_fields_required': 'Source account, amount and date are required.',
        'owners.pay_exceeds_bill': 'Payment exceeds the outstanding bill amount.',
        'owners.pay_exceeds_payout': 'Payment exceeds the available Payout account balance.',
        'owners.pay_review_title': 'Review Payment',
        'owners.pay_review_description': 'Confirm the payment exactly as it will be recorded.',
        'owners.unable_to_pay_bill': 'Unable to record the bill payment.',
        'owners.cancel_payment_title': 'Cancel Payment',
        'owners.cancel_payment_description': 'Reverts the most recent payment of this bill. The reversal is recorded in the journal and activity log.',
        'owners.cancellation_reason': 'Cancellation Reason',
        'owners.cancellation_reason_required': 'A cancellation reason is required.',
        'owners.unable_to_cancel_payment': 'Unable to cancel the bill payment.',
        'owners.unable_to_resend_bill': 'Unable to resend the expense bill.',
        'owners.expense_lines':
            'Expense Lines',
        'owners.add_line':
            'Add line',
        'owners.bill_total':
            'Bill Total',
        'owners.line_description_placeholder':
            'e.g. Plumbing repair',
        'owners.remove_line':
            'Remove line',
        'owners.expense_bill_lines_required':
            'Add at least one expense line.',
        'owners.expense_bill_line_invalid':
            'Every line needs a description and a whole amount greater than zero.',
        'owners.unable_to_record_bill':
            'Unable to record the expense bill.',
        'owners.expense_bill_recorded':
            'Expense bill {number} recorded.',
        'owners.download_bill':
            'Download bill',
        'owners.email_to_owner':
            'Email to owner',
        'owners.sending_email':
            'Sending…',
        'owners.email_sent':
            'The bill was emailed to the owner.',
        'owners.email_failed':
            'Unable to email the bill.',
        'shell.help':
            'Help',
        'shell.update_log':
            'Update log',
        'notifications.loading':
            'Loading notifications…',
        'notifications.unable_load':
            'Unable to load notifications.',
        'notifications.empty':
            'You\'re all caught up.',
        'notifications.rent_overdue_title':
            'Overdue rent',
        'notifications.rent_overdue_body':
            '{count} unpaid invoices — {amount} outstanding',
        'notifications.rent_overdue_body_one':
            '1 unpaid invoice — {amount} outstanding',
        'notifications.rent_due_soon_title':
            'Rent due soon',
        'notifications.rent_due_soon_body':
            '{count} invoices due within 7 days — {amount}',
        'notifications.rent_due_soon_body_one':
            '1 invoice due within 7 days — {amount}',
        'notifications.expenses_unpaid_title':
            'Unpaid expense invoices',
        'notifications.expenses_unpaid_body':
            '{count} expense invoices awaiting payment — {amount}',
        'notifications.expenses_unpaid_body_one':
            '1 expense invoice awaiting payment — {amount}',
        'notifications.owner_bills_unpaid_title':
            'Unpaid owner expense bills',
        'notifications.owner_bills_unpaid_body':
            '{count} owner bills awaiting payment — {amount}',
        'notifications.owner_bills_unpaid_body_one':
            '1 owner bill awaiting payment — {amount}',
        'notifications.leases_expiring_title':
            'Leases expiring',
        'notifications.leases_expiring_body':
            '{count} leases end within 90 days',
        'notifications.leases_expiring_body_one':
            '1 lease ends within 90 days',
        'notifications.increments_upcoming_title':
            'Upcoming rent increments',
        'notifications.increments_upcoming_body':
            '{count} rent increments take effect within 60 days',
        'notifications.increments_upcoming_body_one':
            '1 rent increment takes effect within 60 days',
        'notifications.release_notes_title':
            'What\'s new in Patrimoine v{release}',
        'notifications.release_notes_body':
            'See what changed in this update.',
        'settings.about':
            'About',
        'settings.application_version':
            'Application version',
        'settings.view_update_log':
            'View update log',
        'settings.backup_restore':
            'Data backup & restore',
        'settings.backup_restore_description':
            'Export the Registry as restorable backup files, or restore a previous backup.',
        'settings.backup_financial_note':
            'Financial history (payments, invoices, journal and funds) is not part of backups. It cannot be exported or restored here.',
        'settings.export_heading':
            'Export',
        'settings.entity_parties':
            'Parties',
        'settings.entity_buildings':
            'Buildings',
        'settings.entity_units':
            'Units',
        'settings.entity_leases':
            'Leases',
        'settings.entity_full':
            'Full backup (all entities)',
        'settings.export_full':
            'Full Backup',
        'settings.exporting':
            'Exporting…',
        'settings.unable_export':
            'Unable to export the Registry.',
        'settings.import_heading':
            'Import / restore',
        'settings.choose_file':
            'Choose file…',
        'settings.no_file_selected':
            'No file selected',
        'settings.import_file':
            'Backup file',
        'settings.import_entity':
            'Data set',
        'settings.dry_run':
            'Dry run (validate without saving)',
        'settings.dry_run_help':
            'The dry run reads the file and reports what would change without touching any data. Apply the restore afterwards from the result.',
        'settings.run_import':
            'Run import',
        'settings.importing':
            'Importing…',
        'settings.import_select_file':
            'Choose a backup file first.',
        'settings.import_result_heading':
            'Import result',
        'settings.import_dry_run_notice':
            'Dry run — no data was changed.',
        'settings.import_created':
            'Created',
        'settings.import_updated':
            'Updated',
        'settings.import_unchanged':
            'Unchanged',
        'settings.import_skipped':
            'Skipped',
        'settings.import_skipped_row':
            'Row {row}: {reason}',
        'settings.unable_import':
            'Unable to import the backup.',
        'users.given_names':
            'Given names',
        'users.surname':
            'Surname',
        'activity_log.export_xlsx':
            'XLSX',
        'leases.building':
            'Building',
        'leases.all_buildings':
            'All Buildings',
        'leases.all_frequencies':
            'All Frequencies',
        'leases.expiring_before':
            'Expiring before',
        'leases.rent_increments':
            'Rent Increments',
        'leases.rent_increments_description':
            'Review scheduled, applied and cancelled rent increases for this Lease.',
        'leases.rent_increments_loading':
            'Loading rent increments…',
        'leases.no_rent_increments':
            'No rent increments recorded for this Lease.',
        'leases.increments_unable_load':
            'Unable to load rent increments.',
        'leases.schedule_increment':
            'Schedule increment',
        'leases.schedule_increment_description':
            'The new rent takes effect automatically on the effective date.',
        'leases.effective_date':
            'Effective date',
        'leases.increment_status_scheduled':
            'Scheduled',
        'leases.increment_status_applied':
            'Applied',
        'leases.increment_status_cancelled':
            'Cancelled',
        'leases.notification_sent':
            'Notification sent',
        'leases.applied_on':
            'Applied on',
        'leases.cancelled_on':
            'Cancelled on',
        'leases.cancel_increment':
            'Cancel increment',
        'leases.confirm_cancel_increment':
            'Cancel this scheduled rent increment?',
        'leases.increment_invalid_date':
            'Enter a valid effective date.',
        'leases.increment_schedule_failed':
            'Unable to schedule the rent increment.',
        'leases.increment_cancel_failed':
            'Unable to cancel the rent increment.',
        'tenants.deposit_title':
            'Record Tenant Deposit',
        'tenants.withdrawal_title':
            'Record Tenant Withdrawal',
        'tenants.expense_title':
            'Record Tenant Expense',
        'tenants.adjustment_title':
            'Record Balance Adjustment',
        'tenants.accounts':
            'Accounts',
        'tenants.accounts_description':
            'All fund accounts held for this Tenant across their leases.',
        'tenants.account_status.active':
            'Active',
        'tenants.account_status.closed':
            'Closed',
        'tenants.all_leases':
            'All leases',
        'tenants.any_account_help':
            'Accounts from all of this Tenant\'s leases are listed; optionally narrow the list by lease.',
        'tenants.loading_accounts':
            'Loading accounts…',
        'tenants.no_accounts':
            'No fund accounts exist for this Tenant.',
        'tenants.total_held_funds':
            'Total held funds',
        'tenants.transfer':
            'Transfer',
        'tenants.transfer_description':
            'Move held funds between two of this Tenant\'s accounts. A reason is required.',
        'tenants.source_account':
            'Source Account',
        'tenants.destination_account':
            'Destination Account',
        'tenants.select_source_account':
            'Select source account…',
        'tenants.select_destination_account':
            'Select destination account…',
        'tenants.source_balance':
            'Source Balance',
        'tenants.destination_balance':
            'Destination Balance',
        'tenants.resulting_source_balance':
            'Resulting Source Balance',
        'tenants.resulting_destination_balance':
            'Resulting Destination Balance',
        'tenants.transfer_reason_placeholder':
            'Explain why these funds are being moved…',
        'tenants.transfer_required_fields':
            'Complete all required transfer fields, including the reason.',
        'tenants.transfer_same_account':
            'Source and destination accounts must be different.',
        'tenants.transfer_exceeds_balance':
            'The amount cannot exceed the source account balance.',
        'tenants.no_transferable_accounts':
            'At least two active fund accounts are required for a Transfer.',
        'tenants.transfer_recorded':
            'Transfer recorded successfully.',
        'tenants.transfers':
            'Transfers',
        'tenants.transfers_description':
            'Fund-to-fund transfers with their official receipts.',
        'tenants.no_transfers':
            'No transfers recorded yet.',
        'tenants.expense': 'Expense',
        'tenants.expense_title': 'Record Tenant Expense',
        'tenants.expense_description_text': "Settle a lease-specific expense from one of this tenant's fund accounts. The account can never go negative.",
        'tenants.expense_description_label': 'Expense description',
        'tenants.expense_recorded': 'Tenant expense recorded successfully.',
        'tenants.pay': 'Pay',
        'tenants.cancel_payment': 'Cancel Payment',
        'tenants.download_invoice': 'Download Invoice',
        'tenants.expense_invoices_description': 'Expenses billed to this tenant as EXP invoices. Pay settles them from a fund account; a payment can be cancelled again.',
        'tenants.no_expense_invoices': 'No expense invoices recorded yet.',
        'tenants.expense_invoice_help': 'Recording creates an unpaid EXP invoice. Money leaves a fund account only when the invoice is paid through the Pay action.',
        'tenants.expense_invoice_created': 'Expense invoice created successfully.',
        'tenants.pay_invoice_title': 'Pay Invoice',
        'tenants.pay_invoice_description': "Settle this invoice from one of the lease's fund accounts.",
        'tenants.pay_fields_required': 'Account, amount and date are required.',
        'tenants.pay_exceeds_balance': 'Payment exceeds the available account balance.',
        'tenants.pay_exceeds_invoice': 'Payment exceeds the outstanding invoice amount.',
        'tenants.pay_review_title': 'Review Payment',
        'tenants.pay_review_description': 'Confirm the payment exactly as it will be recorded.',
        'tenants.payment_recorded': 'Invoice payment recorded successfully.',
        'tenants.cancel_payment_title': 'Cancel Payment',
        'tenants.cancel_payment_description': 'Reverts the most recent payment of this invoice. The reversal is recorded in the journal and activity log.',
        'tenants.cancellation_reason': 'Cancellation Reason',
        'tenants.cancellation_reason_required': 'A cancellation reason is required.',
        'tenants.payment_cancelled': 'Payment cancelled and reverted successfully.',
        'tenants.expenses': 'Expenses',
        'tenants.expenses_description': "Lease-specific expenses settled from this tenant's fund accounts, with their official vouchers.",
        'tenants.no_expenses': 'No expenses recorded yet.',
        'tenants.review': 'Review',
        'danger.title': 'Confirm irreversible deletion',
        'danger.entity_prefix': 'You are about to permanently delete:',
        'danger.entity_generic': 'You are about to permanently delete this record.',
        'danger.acknowledgement': 'I understand this action is irreversible, that the record and its history cannot be recovered, and I accept the risk.',
        'danger.password_label': 'Enter your password to confirm',
        'danger.cancel': 'Cancel',
        'danger.confirm': 'Delete permanently',
        'danger.verification_failed': 'Password verification failed.',
        'tenants.back': 'Back',
        'tenants.confirm': 'Confirm',
        'tenants.expense_lines': 'Expense lines',
        'tenants.add_line': 'Add line',
        'tenants.remove_line': 'Remove line',
        'tenants.expense_total': 'Total',
        'tenants.expense_line_description_placeholder': 'What was this expense for?',
        'tenants.expense_fields_required': 'Select an account, date and payment method first.',
        'tenants.expense_line_invalid': 'Every line needs a description and an amount greater than zero.',
        'tenants.expense_exceeds_balance': 'The total exceeds the available fund balance.',
        'tenants.expense_review_title': 'Verify this expense',
        'tenants.expense_review_description': 'Nothing is recorded until you confirm. The tenant receives the itemized voucher by email.',
        'tenants.source_fund': 'Source',
        'tenants.description': 'Description',
        'tenants.category.expense': 'Expense',
        'tenants.voucher':
            'Receipt',
        'tenants.from_fund':
            'From',
        'tenants.to_fund':
            'To',
        'tenants.unable_to_open_voucher':
            'Unable to open the transfer receipt.',
        'tenants.unable_to_resend_voucher':
            'Unable to resend the transfer receipt.',
        'tenants.download_voucher':
            'Download receipt',
        'tenants.download_receipt':
            'Download receipt',
        'tenants.unable_to_open_document':
            'Unable to open document.',
        'tenants.adjustment_no_change':
            'The corrected balance is already the current balance.',
        'tenants.adjustment_negative_balance':
            'Tenant fund balances cannot be adjusted below zero.',
        /* ---- end V1.0.7 additions ---- */
        'product.property_management':
            'Property Management',

        'login.title':
            'Sign in — Patrimoine 365',

        'login.hero_kicker':
            'Property management, minus the drama',

        'login.hero_title':
            'Rent day, minus the drama.',

        'login.hero_description':
            'Leases, invoices, owners and tenants in perfect order — with real double-entry accounting quietly doing the math underneath. You collect the rent. We keep the receipts.',

        'login.hero_image_label':
            'Patrimoine 365 dashboard preview',

        'login.product_name':
            '© 2026 Patrimoine 365. All rights reserved.',

        'login.switch_to_dark':
            'Switch to dark mode',

        'login.switch_to_light':
            'Switch to light mode',

        'login.switch_language':
            'Switch language',

        'login.welcome':
            'Welcome back',

        'login.description':
            'Sign in to access the property management workspace.',

        'login.email':
            'Email address',

        'login.email_placeholder':
            'name@example.com',

        'login.password':
            'Password',

        'login.password_placeholder':
            'Enter your password',

        'login.sign_in':
            'Sign in',

        'login.signing_in':
            'Signing in…',

        'login.unable_to_sign_in':
            'Unable to sign in.',

        'login.secure_access':
            'Secure access to Patrimoine 365.',

        'password.forgot_link':
            'Forgot password?',
        'password.forgot_title':
            'Forgot password — Patrimoine',
        'password.forgot_heading':
            'Forgot your password?',
        'password.forgot_description':
            'Enter your email address and we will send you a password reset link.',
        'password.send_reset':
            'Send reset link',
        'password.sending':
            'Sending…',
        'password.reset_requested':
            'If the account is eligible, a password reset link has been sent.',
        'password.back_to_login':
            'Back to sign in',
        'password.reset_title':
            'Reset password — Patrimoine',
        'password.reset_heading':
            'Reset your password',
        'password.reset_description':
            'Choose a new password for your Patrimoine account.',
        'password.new_password':
            'New password',
        'password.confirm_password':
            'Confirm password',
        'password.reset_action':
            'Reset password',
        'password.resetting':
            'Resetting…',
        'password.reset_complete':
            'Your password has been reset successfully.',
        'password.invitation_title':
            'Set password — Patrimoine',
        'password.invitation_heading':
            'Set your password',
        'password.invitation_description':
            'Create a password to activate your Patrimoine account.',
        'password.set_password':
            'Set password',
        'password.setting_password':
            'Setting password…',
        'password.invitation_complete':
            'Your password has been set successfully.',
        'password.current_password':
            'Current password',
        'password.profile_updated':
            'Your profile has been updated.',
        'password.profile_current_required':
            'Enter your current password to set a new password.',

'password.change_action':
            'Change password',
        'password.change_heading':
            'Change password',
        'password.change_description':
            'Update the password for your account.',
        'password.changing':
            'Changing…',
        'password.confirmation_mismatch':
            'The password confirmation does not match.',
        'password.request_failed':
            'That password request did not go through.',
        'login.missing_api_token':
            'Authentication succeeded but no API token was returned.',

        'login.no_account':
            'New to Patrimoine 365?',

        'navigation.license':
            'License',

        'license.title':
            'License & plan — Patrimoine 365',

        'license.eyebrow':
            'Subscription',

        'license.heading':
            'License & plan',

        'license.description':
            'Your organisation\'s current plan, usage against its limits, and what each plan includes.',

        'license.current_plan':
            'Current plan',

        'license.upgrade_hint':
            'To subscribe, extend or change plans, contact',

        'license.footnotes':
            'Every new organisation starts with a 30-day Professional trial — no payment card required. Prices in USD; annual billing gives two months free. Above 1 000 active leases, talk to us. Financial integrity and transactional document email are identical on every plan, and sign-in email is never blocked.',

        'license.unable':
            'Unable to load licensing information.',

        'license.unlimited':
            'Unlimited',

        'license.trial_until':
            'Professional trial until',

        'license.plan_free':
            'Free',

        'license.plan_standard':
            'Standard',

        'license.plan_professional':
            'Professional',

        'license.usage_users':
            'Internal users',

        'license.usage_active_leases':
            'Active leases',

        'license.usage_parties':
            'Parties',

        'license.usage_emails':
            'Emails this month',

        'login.create_organisation':
            'Create your organisation',

        'login.mfa_heading':
            'Check your email',

        'login.mfa_description':
            'We sent a 6-digit code to',

        'login.mfa_code_label':
            'Verification code',

        'login.mfa_verify':
            'Verify and sign in',

        'login.mfa_verifying':
            'Verifying\u2026',

        'login.mfa_back':
            'Back to sign in',

        'login.mfa_resend':
            'Resend code',

        'signup.title':
            'Create your organisation \u2014 Patrimoine 365',

        'signup.heading':
            'Create your organisation',

        'signup.description':
            'Start your 30-day Professional trial. No payment card required.',

        'signup.organisation_name':
            'Organisation name',

        'signup.organisation_name_placeholder':
            'Acme Properties Ltd',

        'signup.given_names':
            'Given names',

        'signup.surname':
            'Surname',

        'signup.email':
            'Email address',

        'signup.phone':
            'Phone (optional)',

        'signup.password':
            'Password',

        'signup.password_help':
            'At least 10 characters, with letters and numbers.',

        'signup.password_confirmation':
            'Confirm password',

        'signup.accept_prefix':
            'I accept the',

        'signup.terms_link':
            'Terms of Service',

        'signup.accept_and':
            'and the',

        'signup.privacy_link':
            'Privacy Policy',

        'signup.submit':
            'Create organisation',

        'signup.submitting':
            'Creating\u2026',

        'signup.unable':
            'Unable to create your organisation.',

        'signup.have_account':
            'Already have an account?',

        'signup.sign_in_link':
            'Sign in',

        'signup.done_heading':
            'Check your email',

        'signup.done_description':
            'We sent a verification link to',

        'signup.done_back_to_login':
            'Back to sign in',

        'signup.resend_hint':
            'No email after a minute or two? Check your spam folder first.',

        'signup.resend_button':
            'Resend the verification email',

        'login.resend_verification':
            'Resend the verification email',

        'verify_email.title':
            'Verify your email \u2014 Patrimoine 365',

        'verify_email.pending_heading':
            'Verifying\u2026',

        'verify_email.pending_description':
            'One moment while we confirm your email address.',

        'verify_email.success_heading':
            'Email verified',

        'verify_email.success_description':
            'Your email address has been confirmed. You can now sign in to your organisation.',

        'verify_email.continue':
            'Continue to sign in',

        'verify_email.failed_heading':
            'Link invalid or expired',

        'verify_email.failed_description':
            'This verification link is no longer valid. Enter your email address and we will send you a fresh one.',

        'verify_email.resend':
            'Send new link',

        'verify_email.resent':
            'If that address needs verification, a new link has been sent.',

        'verify_email.resend_failed':
            'Unable to send a new link right now.',

        'verify_email.back_to_login':
            'Back to sign in',

        'navigation.workspace':
            'Workspace',

        'navigation.dashboard':
            'Dashboard',

        'navigation.properties':
            'Properties',

        'navigation.parties':
            'Parties',

        'navigation.leases':
            'Leases',

        'navigation.payments':
            'Payments',

        'navigation.finance':
            'Finance',

        'navigation.manage':
            'Manage',

        'shell.refresh':
            'Refresh',

        'shell.notifications':
            'Notifications',

        'shell.whats_new':
            "What's new",

        'shell.appearance':
            'Appearance',

        'shell.theme_light':
            'Light',

        'shell.theme_dark':
            'Dark',

        'shell.theme_system':
            'System',

        'release.v104_heading':
            'You are now on Patrimoine v1.0.4',

        'release.v104_ui':
            'Updated interface for a cleaner experience.',

        'release.v104_fixes':
            'Usability and localisation fixes.',

        'navigation.tenants':
            'Tenants',

        'navigation.owners':
            'Owners',

        'navigation.accounting':
            'Accounting',

        'accounting.title':
            'Accounting',

        'accounting.subtitle':
            'What your organisation has earned in management fees, and the VAT you have charged on them.',

        'accounting.fee_income':
            'Fee income',

        'accounting.fee_income_hint':
            'Management fees charged to owners.',

        'accounting.vat_charged':
            'VAT charged',

        'accounting.vat_charged_hint':
            'Collected on your fees and owed onward.',

        'accounting.charged_to_owners':
            'Total charged to owners',

        'accounting.charged_to_owners_hint':
            'Fees plus the VAT on them.',

        'accounting.from':
            'From',

        'accounting.to':
            'To',

        'accounting.apply':
            'Apply',

        'accounting.reset':
            'Reset',

        'accounting.transactions':
            'Charges',

        'accounting.date':
            'Date',

        'accounting.type':
            'Type',

        'accounting.owner':
            'Owner',

        'accounting.property':
            'Property',

        'accounting.reference':
            'Reference',

        'accounting.amount':
            'Amount',

        'accounting.management_fee':
            'Management fee',

        'accounting.management_fee_vat':
            'VAT on fee',

        'accounting.empty':
            'No management fees were charged in this period.',

        'accounting.capped':
            'Showing the 200 most recent charges. The Financial Journal holds the complete record.',

        'accounting.vat_note':
            'VAT shown here is collected on behalf of the tax authority. It is not income.',

        'navigation.reports':
            'Reports',

        'navigation.settings':
            'Settings',

        'navigation.sign_out':
            'Sign out',
        'navigation.sign_out_description':
            'Sign out of Patrimoine',

        'user.property_manager':
            'Property Manager',

        'dashboard.title':
            'Dashboard — Patrimoine',

        'dashboard.overview':
            'Overview',

        'dashboard.heading':
            'Dashboard',

        'dashboard.description':
            'Current portfolio and financial position.',

        'dashboard.buildings':
            'Buildings',

        'dashboard.total_units':
            'Total Units',

        'dashboard.rent_due':
            'Rent Due',

        'dashboard.rent_overdue':
            'Rent Overdue',

        'dashboard.collected_this_month':
            'Collected This Month',

        'dashboard.owner_funds_held':
            'Owner Funds Held',

        'dashboard.overdue_rent':
            'Overdue Rent',

        'dashboard.overdue_description':
            'Outstanding obligations requiring attention.',

        'dashboard.upcoming_rent':
            'Upcoming Rent',

        'dashboard.upcoming_description':
            'Rent obligations becoming due soon.',

        'dashboard.loading':
            'Loading…',

        'dashboard.no_records':
            'No records to display.',

        'dashboard.tenant':
            'Tenant',

        'dashboard.due':
            'Due',

        'dashboard.unable_to_load':
            'Unable to load dashboard information.',

        'language.en':
            'English',

        'language.fr':
            'French',

        'currency.GHS':
            'GHS',

        'currency.FCFA':
            'FCFA',

        'navigation.activity_log':
            'Activity Log',

        'navigation.financial_journal':
            'Financial Journal',

        'navigation.users':
            'Users',

        'navigation.platform_console':
            'Administration',

        'profile.photo':
            "Photograph",
        'profile.photo_hint':
            "Shown at the top of the screen and beside your name wherever your account appears.",
        'profile.photo_choose':
            "Choose a picture",
        'profile.photo_reframe':
            "Reframe",
        'profile.photo_remove':
            "Remove",
        'profile.photo_zoom':
            "Zoom",
        'profile.photo_save':
            "Use this framing",
        'profile.photo_cancel':
            "Cancel",
        'profile.photo_drag':
            "Drag to move, scroll or use the slider to zoom.",
        'profile.photo_unreadable':
            "This picture could not be opened. If it came from an iPhone, try saving it as JPEG first.",

        'phone.country':
            'Country',
        'phone.select':
            'Country',
        'phone.search':
            'Country or code',
        'phone.none':
            'No country matches',

        'roles.administrator':
            'Administrator',
        'roles.property_manager':
            'Property Manager',
        'roles.viewer':
            'Viewer',

        'financial_journal.transaction_types.rent_invoice':
            'Rent Invoice',

        'financial_journal.transaction_types.rent_receipt':
            'Rent Receipt',

        'financial_journal.transaction_types.owner_deposit':
            'Owner Deposit',

        'financial_journal.transaction_types.owner_payout':
            'Owner Payout',

        'financial_journal.transaction_types.owner_expense':
            'Owner Expense',

        'financial_journal.transaction_types.owner_rent_entitlement':
            'Owner Rent Entitlement',

        'financial_journal.transaction_types.owner_adjustment':
            'Owner Adjustment',

        'financial_journal.transaction_types.management_fee':
            'Management Fee',

        'financial_journal.transaction_types.advance_consumption':
            'Advance Consumption',

        'financial_journal.transaction_types.rent_reserve_consumption':
            'Rent Reserve Consumption',

        'financial_journal.transaction_types.rent_reserve_funding':
            'Rent Reserve Funding',

        'financial_journal.transaction_types.consumable_advance_funding':
            'Consumable Advance Funding',

        'financial_journal.transaction_types.security_deposit_funding':
            'Security Deposit Funding',

        'financial_journal.transaction_types.security_deposit_settlement':
            'Security Deposit Settlement',

        'financial_journal.transaction_types.security_deposit_refund':
            'Security Deposit Refund',

        'financial_journal.transaction_types.security_deposit_debt':
            'Security Deposit Debt',

        'financial_journal.transaction_types.tenant_fund_funding':
            'Tenant Fund Funding',

        'financial_journal.transaction_types.tenant_fund_expense':
            'Tenant Fund Expense',

        'financial_journal.transaction_types.tenant_fund_transfer':
            'Tenant Fund Transfer',

        'financial_journal.transaction_types.tenant_expense_settlement':
            'Tenant Expense Settlement',

        'financial_journal.transaction_types.journal_reversal':
            'Journal Reversal',

        'financial_journal.transaction_types.v1_0_5_opening_balance':
            'Opening Balance',

        'financial_journal.title':
            'Financial Journal — Patrimoine',

        'financial_journal.administration':
            'Administration',

        'financial_journal.heading':
            'Financial Journal',

        'financial_journal.description':
            "Review Patrimoine's permanent double-entry accounting record.",

        'financial_journal.loading':
            'Loading Financial Journal...',

        'financial_journal.search':
            'Search',

        'financial_journal.search_placeholder':
            'Journal number, description, actor, account or source...',

        'financial_journal.from':
            'From',

        'financial_journal.to':
            'To',

        'financial_journal.entry_kind':
            'Entry Kind',

        'financial_journal.all_entry_kinds':
            'All Entry Kinds',

        'financial_journal.kind_financial':
            'Financial',

        'financial_journal.kind_reversal':
            'Reversal',

        'financial_journal.kind_informational':
            'Informational',

        'financial_journal.transaction_type':
            'Transaction Type',

        'financial_journal.all_transaction_types':
            'All Transaction Types',

        'financial_journal.account':
            'Account',

        'financial_journal.all_accounts':
            'All Accounts',

        'financial_journal.clear_filters':
            'Clear Filters',

        'financial_journal.export_pdf':
            'PDF',

        'financial_journal.export_csv':
            'CSV',

        'financial_journal.export_xlsx':
            'XLSX',

        'financial_journal.exporting':
            'Exporting...',

        'financial_journal.unable_export':
            'Unable to export the Financial Journal.',

        'financial_journal.unable_load':
            'Unable to load the Financial Journal.',

        'financial_journal.none_found':
            'No Journal entries found',

        'financial_journal.none_found_description':
            'No Journal entries match the selected filters.',

        'financial_journal.view_details':
            'View Details',

        'financial_journal.page_of':
            'Page :current of :last',

        'financial_journal.previous':
            'Previous',

        'financial_journal.next':
            'Next',

        'financial_journal.close':
            'Close',

        'financial_journal.detail_heading':
            'Journal Entry',

        'financial_journal.detail_description':
            'Immutable accounting transaction details.',

        'financial_journal.loading_detail':
            'Loading Journal entry...',

        'financial_journal.unable_load_detail':
            'Unable to load Journal entry details.',

        'financial_journal.debit':
            'Debit',

        'financial_journal.credit':
            'Credit',

        'financial_journal.actor':
            'Actor',

        'financial_journal.source':
            'Source',

        'financial_journal.balance_status':
            'Balance Status',

        'financial_journal.balanced':
            'Balanced',

        'financial_journal.unbalanced':
            'Unbalanced',

        'financial_journal.description_label':
            'Description',

        'financial_journal.accounting_lines':
            'Accounting Lines',

        'financial_journal.line_count':
            ':count line(s)',

        'financial_journal.no_lines':
            'This informational entry has no accounting lines.',

        'financial_journal.total_debit':
            'Total Debit',

        'financial_journal.total_credit':
            'Total Credit',

        'financial_journal.reversal_context':
            'Reversal Information',

        'financial_journal.reversal_of':
            'Reversal Of',

        'financial_journal.reversed_by':
            'Reversed By',

        'financial_journal.reversal_reason':
            'Reversal Reason',

        'financial_journal.reversed':
            'Reversed',

        'financial_journal.not_available':
            'Not available',


        'activity_log.title':
            'Activity Log — Patrimoine',
        'activity_log.administration':
            'Administration',
        'activity_log.heading':
            'Activity Log',
        'activity_log.description':
            'Review meaningful human actions recorded by Patrimoine.',
        'activity_log.search':
            'Search',
        'activity_log.search_placeholder':
            'Search actor, action, record, IP, browser, device or historical context...',
        'activity_log.from':
            'From',
        'activity_log.to':
            'To',
        'activity_log.user':
            'User',
        'activity_log.all_users':
            'All Users',
        'activity_log.role':
            'Role',
        'activity_log.all_roles':
            'All Roles',
        'activity_log.action':
            'Action',
        'activity_log.action_placeholder':
            'e.g. payment.recorded',
        'activity_log.entity_type':
            'Record Type',
        'activity_log.entity_type_placeholder':
            'e.g. payment',
        'activity_log.clear_filters':
            'Clear Filters',
        'activity_log.export_pdf':
            'PDF',
        'activity_log.export_csv':
            'CSV',
        'activity_log.exporting':
            'Exporting...',
        'activity_log.unable_export':
            'Unable to export Activity Log.',
        'activity_log.loading':
            'Loading activity...',
        'activity_log.none_found':
            'No activity found',
        'activity_log.none_found_description':
            'No recorded activity matches the current filters.',
        'activity_log.view_details':
            'View Details',
        'activity_log.page_of':
            'Page :current of :last',
        'activity_log.previous':
            'Previous',
        'activity_log.next':
            'Next',
        'activity_log.detail_heading':
            'Activity Details',
        'activity_log.detail_description':
            'Immutable historical information recorded for this action.',
        'activity_log.loading_detail':
            'Loading activity details...',
        'activity_log.unable_load':
            'Unable to load Activity Log.',
        'activity_log.unable_load_detail':
            'Unable to load activity details.',
        'activity_log.close':
            'Close',
        'activity_log.event':
            'Event',
        'activity_log.timestamp':
            'Timestamp',
        'activity_log.actor':
            'Actor',
        'activity_log.email':
            'Email',
        'activity_log.ip_address':
            'IP Address',
        'activity_log.browser':
            'Browser',
        'activity_log.platform':
            'Platform',
        'activity_log.device':
            'Device',
        'activity_log.user_agent':
            'User Agent',
        'activity_log.entity':
            'Record',
        'activity_log.before_values':
            'Before',
        'activity_log.after_values':
            'After',
        'activity_log.snapshot':
            'Snapshot',
        'activity_log.metadata':
            'Additional Context',
        'activity_log.not_available':
            'Not available',
        'activity_log.unknown_actor':
            'Unknown actor',

        'users.title':
            'Users — Patrimoine',
        'users.administration':
            'Administration',
        'users.heading':
            'User Management',
        'users.description':
            'Manage application users, roles and account access.',
        'users.add_user':
            'Add User',
        'users.edit_user':
            'Edit User',
        'users.create_user':
            'Create User',
        'users.create_description':
            'Create an application user and send a secure password-setup invitation.',
        'users.edit_description':
            'Update this application user’s identity, role or account status.',
        'users.name':
            'Name',
        'users.email':
            'Email',
        'users.phone':
            'Phone',
        'users.role':
            'Role',
        'users.status':
            'Status',
        'users.search':
            'Search',
        'users.search_placeholder':
            'Search name, email or phone...',
        'users.all_roles':
            'All Roles',
        'users.all_statuses':
            'All Statuses',
        'users.active':
            'Active',
        'users.inactive':
            'Inactive',
        'users.active_account':
            'Active account',
        'users.active_account_help':
            'Inactive users cannot sign in.',
        'users.invitation_pending':
            'Invitation pending',
        'users.you':
            'You',
        'users.loading':
            'Loading users...',
        'users.none_found':
            'No users found',
        'users.none_found_description':
            'Create a user or change the current filters.',
        'users.edit':
            'Edit',
        'users.delete':
            'Delete',
        'users.resend_invitation':
            'Resend invitation',
        'users.send_password_reset':
            'Send password reset',
        'users.cancel':
            'Cancel',

        'actions.save':
            'Save',
        'actions.cancel':
            'Cancel',
        'actions.close':
            'Close',
        'users.close':
            'Close',
        'users.save_changes':
            'Save Changes',
        'users.saving':
            'Saving...',
        'users.creating':
            'Creating...',
        'users.created':
            'User created and invitation sent successfully.',
        'users.updated':
            'User updated successfully.',
        'users.deleted':
            'User deleted successfully.',
        'users.invitation_resent':
            'A new invitation has been sent. Previous invitation links are no longer valid.',
        'users.reset_sent':
            'The password reset workflow has been initiated.',
        'users.resend_confirmation':
            'Send a new invitation to :name? Previous invitation links will stop working.',
        'users.reset_confirmation':
            'Send a password reset link to :name?',
        'users.delete_confirmation':
            'Delete :name? This cannot be undone.',
        'users.unable_load':
            'Unable to load users.',
        'users.unable_create':
            'Unable to create user.',
        'users.unable_update':
            'Unable to update user.',
        'users.unable_delete':
            'Unable to delete user.',
        'users.action_failed':
            'That change to the user was not saved.',
        'users.page_of':
            'Page :current of :last',
        'users.previous':
            'Previous',
        'users.next':
            'Next',

        'settings.title':
            'Settings — Patrimoine',

        'settings.administration':
            'Administration',

        'settings.heading':
            'Settings',

        'settings.description':
            'Configure the organisation operating this Patrimoine installation.',

        'settings.managing_organisation':
            'Managing Organisation',

        'settings.managing_organisation_description':
            'This organisation represents the company or entity managing the property portfolio in this Patrimoine installation.',

        'settings.organisation_details':
            'Organisation Details',

        'settings.legal_name':
            'Legal Name',

        'settings.legal_name_placeholder':
            'e.g. Apotica Company Limited',

        'settings.address':
            'Address',

        'settings.address_placeholder':
            'Organisation address',

        'settings.phone':
            'Phone',

        'settings.alternate_phone':
            'Alternate Phone',

        'settings.general_email':
            'General Email',

        'settings.primary_contact':
            'Primary Contact',

        'settings.contact_person':
            'Contact Person',

        'settings.contact_phone':
            'Contact Phone',

        'settings.contact_email':
            'Contact Email',

        'settings.registration':
            'Registration',

        'settings.registration_number':
            'Registration Number',

        'settings.vat_tin':
            'VAT / TIN',

        'settings.communications':
            'Communications',

        'settings.communications_description':
            'What Patrimoine sends to your tenants, owners and agents.',

        'settings.party_emails_enabled':
            'Send emails to parties',

        'settings.party_emails_help':
            'When this is off, Patrimoine sends nothing to tenants, owners or agents — no invoices, receipts, reminders, notices or vouchers — and anyone who tries to send one is told why. Individual parties can still be allowed from their own record. Emails to Patrimoine users, such as sign-in codes, invitations and password resets, are never affected.',

        'settings.language_currency':
            'Language & Currency',

        'settings.language_currency_description':
            'These settings apply to the entire Managing Organisation. Language and currency are independent.',

        'settings.language':
            'Language',

        'settings.language_help':
            'Controls normal user-facing Patrimoine content.',

        'settings.currency':
            'Currency',

        'settings.currency_help':
            'Changes presentation only. Stored monetary values are never converted.',

        'settings.financial_defaults':
            'Financial Defaults',

        'settings.financial_defaults_description':
            'Defaults apply to newly created records only. Existing leases and invoices keep their stored values.',

        'settings.default_vat_rate':
            'Default Management Fee VAT Rate %',

        'settings.vat_help_label':
            'About Default Management Fee VAT',

        'settings.vat_help_text':
            'This rate is pre-filled when creating a new Lease and applies to your management fee, not to rent. Individual Leases may still override it, including 0% where applicable. Changing this setting does not alter existing Leases or historical Invoices.',

        'settings.vat_starting_rate':
            'Used as the starting management fee VAT rate for new Leases.',

        'settings.banking_details':
            'Banking Details',

        'settings.optional':
            'Optional.',

        'settings.bank_name':
            'Bank Name',

        'settings.bank_branch':
            'Bank Branch',

        'settings.account_name':
            'Account Name',

        'settings.account_number':
            'Account Number',

        'settings.notes':
            'Notes',

        'settings.save':
            'Save Organisation',

        'settings.saving':
            'Saving…',

        'settings.saved':
            'Managing Organisation saved successfully.',

        'settings.unable_to_load':
            'Unable to load Managing Organisation.',

        'settings.unable_to_save':
            'Unable to save Managing Organisation.',

        /* ---- V1.0.9 additions ---- */

        'settings.tab_organisation':
            'Organisation',

        'settings.tab_preferences':
            'Preferences',

        'settings.tab_data':
            'Data',

        'settings.not_configured':
            'The Managing Organisation is not configured yet. Fill in the form below and save to set it up.',

        'settings.save_preferences':
            'Save Preferences',

        'settings.export_success':
            'Export downloaded.',

        'settings.export_opened':
            'PDF opened in a new tab.',

        'settings.format_csv':
            'CSV',

        'settings.format_xlsx':
            'XLSX',

        'settings.format_pdf':
            'PDF',

        'settings.run_dry_run':
            'Review Restore',

        'settings.dry_run_running':
            'Running dry run…',

        'settings.apply_restore':
            'Apply this restore',

        'settings.confirm_restore_title':
            'Confirm restore',

        'settings.confirm_restore_description':
            'Review the dry-run result before applying this restore to the Registry.',

        'settings.confirm_restore_warning':
            'Applying this restore modifies Registry data immediately. This action cannot be undone.',

        'settings.confirm_restore_apply':
            'Apply restore',

        'settings.restoring':
            'Restoring…',

        'settings.restore_success':
            'Restore applied successfully.',

        'settings.full_requires_xlsx':
            'A full restore requires an .xlsx workbook file.',

        /* ---- end V1.0.9 additions ---- */


        'parties.loading': 'Loading parties…',
        'parties.unable_to_load': 'Unable to load parties.',
        'parties.no_parties_found': 'No parties found',
        'parties.empty_description': 'Add a Party or change the current filters.',
        'parties.party': 'Party',
        'parties.person': 'Person',
        'parties.organisation': 'Organisation',
        'parties.association': 'Association',
        'parties.tenant': 'Tenant',
        'parties.owner': 'Owner',
        'parties.agent': 'Agent',
        'parties.managing_organisation': 'Managing Organisation',
        'parties.no_assigned_role': 'No assigned role',
        'parties.contact': 'Contact',
        'parties.edit': 'Edit',
        'parties.delete': 'Delete',
        'parties.page': 'Page',
        'parties.of': 'of',
        'parties.previous': 'Previous',
        'parties.next': 'Next',
        'parties.unable_to_load_party': 'Unable to load Party.',
        'parties.edit_party': 'Edit Party',
        'parties.add_party': 'Add Party',
        'parties.edit_party_description': 'Update Party identity, contact details and roles.',
        'parties.add_party_description': 'Create a person, organisation or association.',
        'parties.save_changes': 'Save Changes',
        'parties.create_party': 'Create Party',
        'parties.save': 'Save',

        'leases.financial_history': 'Financial History',

        'leases.financial_history_export_pdf': 'PDF',

        'leases.financial_history_export_excel': 'Excel',

        'leases.financial_history_export_csv': 'CSV',
        'leases.financial_history_description': 'Chronological financial activity for this Lease.',
        'leases.financial_history_loading': 'Loading financial history…',
        'leases.financial_history_unable_load': 'Unable to load financial history.',
        'leases.financial_history_empty': 'No financial history',
        'leases.financial_history_empty_description': 'No financial events have been recorded for this Lease.',
        'leases.financial_history_reference': 'Reference',
        'leases.financial_history_payment_method': 'Payment Method',
        'leases.financial_history_fund': 'Fund',
        'leases.financial_history_open_document': 'Open Document',
        'leases.financial_history_unable_open_document': 'Unable to open document.',
        'leases.financial_history_event_invoice': 'Invoice',
        'leases.financial_history_event_payment': 'Tenant Payment',
        'leases.financial_history_event_fund_deposit': 'Fund Deposit',
        'leases.financial_history_event_rent_reserve_consumption': 'Rent Reserve Applied',
        'leases.financial_history_event_advance_consumption': 'Consumable Advance Applied',
        'leases.financial_history_event_withdrawal': 'Withdrawal',
        'leases.financial_history_event_adjustment': 'Adjustment',
        'leases.financial_history_event_security_application': 'Security Deposit Applied',
        'leases.financial_history_event_security_deduction': 'Security Deposit Deduction',
        'leases.financial_history_event_security_settlement': 'Security Deposit Settlement',
        'leases.financial_history_event_security_movement': 'Security Deposit Movement',
        'leases.financial_history_event_fund_movement': 'Fund Movement',
        'leases.financial_history_fund_rent_reserve': 'Rent Reserve',
        'leases.financial_history_fund_consumable_advance': 'Consumable Advance',
        'leases.financial_history_fund_security_deposit': 'Security Deposit',
        'leases.financial_history_method_cash': 'Cash',
        'leases.financial_history_method_bank_transfer': 'Bank Transfer',
        'leases.financial_history_method_mobile_payment': 'Mobile Payment',
        'leases.financial_history_method_cheque': 'Cheque',
        'leases.unable_initialize': 'Unable to initialize Leases.',
        'leases.all_tenants': 'All Tenants',
        'leases.select_tenant': 'Select tenant…',
        'leases.no_agent': 'No Agent',
        'leases.no_matching_units': 'No matching units found.',
        'leases.no_matching_tenants': 'No matching tenants found.',
        'leases.duration': 'Duration',
        'leases.duration_3m': '3 months',
        'leases.duration_6m': '6 months',
        'leases.duration_1y': '1 year',
        'leases.duration_2y': '2 years',
        'leases.duration_3y': '3 years',
        'leases.duration_4y': '4 years',
        'leases.duration_5y': '5 years',
        'leases.duration_custom': 'Other',
        'leases.notice_1m': '1 month before end',
        'leases.notice_3m': '3 months before end',
        'leases.notice_6m': '6 months before end',
        'leases.summary_title': 'Verify this lease',
        'leases.summary_description': 'Review everything below. Nothing is saved until you confirm — the lease then goes live immediately.',
        'leases.summary_back': 'Back',
        'leases.review': 'Review',
        'leases.summary_parties': 'Parties & Unit',
        'leases.summary_rent_terms': 'Rent terms',
        'leases.summary_money_held': 'Money held',
        'leases.summary_management': 'Management & notes',
        'leases.summary_automatic': 'Automatic',
        'leases.proration': 'Proration',
        'leases.advance_received': 'Advance already received',
        'leases.duration_caption': 'Picking a duration sets the End Date automatically. Editing the End Date switches to Other.',
        'leases.notice_period': 'Notice period',
        'leases.notice_caption': 'Computes the Notice Date from the End Date.',
        'leases.summary_confirm': 'Confirm',
        'leases.summary_backdated_note': 'Activation will generate :count backdated invoice(s) totalling :total, covering the period from the start date to today.',
        'leases.no_matching_agents': 'No matching agents found.',
        'leases.tenant_search_placeholder': 'Search tenant by name, phone or email…',
        'leases.agent_search_placeholder': 'Search agent by name, phone or email…',
        'leases.clear_selected_tenant': 'Clear selected Tenant',
        'leases.clear_selected_agent': 'Clear selected Agent',
        'leases.property': 'Property',
        'leases.unit': 'Unit',
        'leases.owner': 'Owner',
        'leases.no_ownership_information': 'No ownership information available.',
        'leases.loading': 'Loading leases…',
        'leases.unable_load': 'Unable to load Leases.',
        'leases.none_found': 'No leases found',
        'leases.none_found_description': 'Create a Lease or change the current filters.',
        'leases.tenant': 'Tenant',
        'leases.agent': 'Agent',
        'leases.start': 'Start',
        'leases.end': 'End',
        'leases.vat': 'VAT',
        'leases.tenant_funds': 'Tenant Funds',
        'leases.manage_security_deposit': 'Security Deposit',
        'leases.edit': 'Edit',
        'leases.extend': 'Extend',
        'leases.terminate': 'Terminate',
        'leases.terminate_lease': 'Terminate Lease',
        'leases.termination_description': 'Record notice, define the vacate date and choose the final rental treatment.',
        'leases.lease_context': 'Lease Context',
        'leases.lease': 'Lease',
        'leases.termination_details': 'Termination Details',
        'leases.termination_date': 'Termination / Vacate Date',
        'leases.final_rent_treatment': 'Final Rental Period',
        'leases.final_rent_prorate': 'Prorate final period',
        'leases.final_rent_prorate_help': 'Charge rent only through the selected termination date.',
        'leases.final_rent_full': 'Charge full period',
        'leases.final_rent_full_help': 'Charge the full contractual billing period containing the termination date.',
        'leases.final_rent_none': 'No final rent',
        'leases.final_rent_none_help': 'Do not charge rent for the final partial billing period.',
        'leases.initiate_termination': 'Initiate Termination',
        'leases.termination_required_fields': 'Notice Date, Termination Date and final rental treatment are required.',
        'leases.termination_failed': 'Unable to initiate Lease termination.',
        'leases.termination_notice': 'Termination Notice',
        'leases.termination_notice_ready': 'The Termination Notice has been generated and is ready to open.',
        'leases.open_termination_notice': 'Open Termination Notice',
        'leases.termination_notice_unable_open': 'Unable to open the Termination Notice.',
        'leases.extend_lease': 'Extend Lease',
        'leases.extend_description': 'Create a new contractual term period while preserving the Lease and its history.',
        'leases.current_terms': 'Current Terms',
        'leases.new_terms': 'New Terms',
        'leases.effective_from': 'Effective From',
        'leases.delete': 'Delete',
        'leases.delete_lease': 'Delete Lease',
        'leases.delete_destructive_action': 'Destructive action',
        'leases.delete_context': 'Lease being deleted',
        'leases.delete_impact_title': 'Deletion impact',
        'leases.delete_impact_description': 'Patrimoine will permanently remove the Lease and its operational financial history while preserving the required accounting and audit evidence.',
        'leases.delete_impact_loading': 'Calculating deletion impact…',
        'leases.delete_impact_failed': 'Unable to calculate the Lease deletion impact.',
        'leases.delete_impact_invoices': 'Invoices',
        'leases.delete_impact_payments': 'Payments',
        'leases.delete_impact_allocations': 'Allocations',
        'leases.delete_impact_receipts': 'Withdrawal receipts',
        'leases.delete_impact_security': 'Security Deposit balance',
        'leases.delete_impact_reserve': 'Rent Reserve balance',
        'leases.delete_impact_consumable': 'Consumable Advance balance',
        'leases.delete_impact_outstanding': 'Invoice outstanding',
        'leases.delete_impact_reversals': 'Journal reversals',
        'leases.delete_impact_owner': 'Owner Lease effect',
        'leases.delete_impact_safe': 'The complete impact is classified and this Lease is eligible for controlled deletion.',
        'leases.delete_blocked': 'This Lease cannot be deleted safely.',
        'leases.delete_reason': 'Deletion reason',
        'leases.delete_confirmation_label': 'Type DELETE to confirm',
        'leases.delete_password': 'Current password',
        'leases.delete_permanently': 'Delete permanently',
        'leases.status_draft': 'Draft',
        'leases.status_active': 'Active',
        'leases.status_notice': 'Notice',
        'leases.status_terminated': 'Terminated',
        'leases.frequency_month': 'month',
        'leases.frequency_quarter': 'quarter',
        'leases.frequency_six_months': 'six months',
        'leases.frequency_year': 'year',
        'leases.page': 'Page',
        'leases.of': 'of',
        'leases.previous': 'Previous',
        'leases.next': 'Next',
        'leases.unable_load_one': 'Unable to load Lease.',
        'leases.edit_lease': 'Edit Lease',
        'leases.add_lease': 'Add Lease',
        'leases.edit_description': 'Update the tenancy agreement and contractual terms.',
        'leases.add_description': 'Create a tenancy agreement for a property unit.',
        'leases.save_changes': 'Save Changes',
        'leases.create_lease': 'Create Lease',
        'leases.save': 'Save',
        'leases.select_valid_unit': 'Select a valid Property / Unit.',
        'leases.reserve_exceeds_advance': 'Rent Reserve cannot exceed Total Advance Payment.',
        'leases.saving_changes': 'Saving Changes…',
        'leases.creating': 'Creating Lease…',
        'leases.unable_update': 'Unable to update Lease.',
        'leases.unable_create': 'Unable to create Lease.',
        'leases.this_lease': 'this Lease',
        'leases.delete_financial_history_warning': 'Deleting a Lease is permanent and may remove records that belong exclusively to that Lease. Review the impact carefully before continuing.',
        'leases.delete_reason_prompt': 'Reason for deleting this Lease:',
        'leases.delete_reason_required': 'A deletion reason is required.',
        'leases.delete_confirmation_prompt': 'Type DELETE exactly to confirm:',
        'leases.delete_confirmation_invalid': 'The confirmation has to read DELETE, exactly.',
        'leases.delete_password_prompt': 'Enter your current password:',
        'leases.delete_password_required': 'Your current password is required.',
        'leases.delete_final_confirmation': 'Permanently delete this Lease? This action cannot be undone.',
        'leases.unable_delete': 'Unable to delete Lease.',
        'leases.security_review_description': 'Review held funds, deductions and final settlement.',
        'leases.unable_load_security_deposit': 'Unable to load Security Deposit.',
        'leases.voucher': 'Voucher',
        'leases.security_available_after_termination': 'Security Deposit deductions are available during termination. Final settlement remains controlled until the Lease financial position is resolved.',
        'leases.security_available_during_termination': 'Security Deposit deductions become available once termination is in progress.',
        'leases.security_deductions_during_termination': 'Itemized deductions may be recorded while termination is in progress. Final Security Deposit settlement remains available after termination is completed.',
        'leases.termination_settlement': 'Termination Settlement',
        'leases.termination_settlement_description': 'Review the financial position and resolve every blocker before completing termination.',
        'leases.termination_settlement_loading': 'Loading settlement…',
        'leases.termination_settlement_load_failed': 'Unable to load the termination settlement.',
        'leases.termination_financial_position': 'Financial Position',
        'leases.outstanding_debt': 'Outstanding Debt',
        'leases.security_deposit_deductions': 'Security Deposit Deductions',
        'leases.other_tenant_funds': 'Other Tenant Funds',
        'leases.amount_still_owed': 'Amount Still Owed',
        'leases.final_refundable_amount': 'Potential Refundable Amount',
        'leases.termination_unresolved_items': 'Items that must be resolved',
        'leases.termination_unresolved_item': 'Unresolved settlement item',
        'leases.termination_no_blockers': 'No unresolved financial blockers remain. Termination can be completed.',
        'leases.termination_resolve_from_tenant': 'Resolve debt, held funds and refunds from the Tenant workspace. Financial operations are not duplicated on the Lease page.',
        'leases.go_to_tenant': 'Go to Tenant',
        'leases.complete_termination': 'Complete Termination',
        'leases.cancel_termination': 'Cancel Termination',
        'leases.confirm_complete_termination': 'Complete this Lease termination? The Lease will become inactive and Unit occupancy will be recalculated.',
        'leases.confirm_cancel_termination': 'Cancel this Lease termination and restore its previous operational state?',
        'leases.termination_complete_failed': 'The termination was not completed.',
        'leases.termination_cancel_failed': 'Unable to cancel termination.',
        'leases.no_deductions': 'No deductions recorded.',
        'leases.date': 'Date',
        'leases.description': 'Description',
        'leases.reference': 'Reference',
        'leases.amount': 'Amount',
        'leases.voucher_popup_blocked': 'The voucher could not be opened because the browser blocked the new tab.',
        'leases.opening': 'Opening…',
        'leases.unable_open_voucher': 'Unable to open Security Deposit voucher.',
        'leases.download_voucher': 'Download Voucher',
        'leases.adding': 'Adding…',
        'leases.unable_add_deduction': 'Unable to add Security Deposit deduction.',
        'leases.add_deduction': 'Add Deduction',
        'leases.finalize_security_confirmation': 'Finalize this Security Deposit settlement?',
        'leases.finalize_security_warning': 'This action is permanent. No further deductions can be added afterward.',
        'leases.finalizing': 'Finalizing…',
        'leases.unable_finalize_security': 'Unable to finalize Security Deposit.',
        'leases.finalize_settlement': 'Finalize Settlement',
        'leases.tenant_funds_description': 'Review actual tenant-held funds.',
        'leases.unable_load_tenant_funds': 'Unable to load Tenant Funds.',
        'leases.no_outstanding_invoice': 'No outstanding Invoice',
        'leases.select_invoice': 'Select Invoice…',
        'leases.invoice': 'Invoice',
        'leases.outstanding': 'outstanding',
        'leases.no_rent_reserve': 'No Rent Reserve balance is currently available.',
        'leases.reserve_protected': 'Rent Reserve remains protected until termination notice has been recorded.',
        'leases.reserve_available': 'Termination notice has been recorded. Available Reserve may now be applied to outstanding rent.',
        'leases.no_consumable_advance': 'No Consumable Advance balance is currently available.',
        'leases.applying': 'Applying…',
        'leases.unable_apply_reserve': 'Unable to apply Rent Reserve.',
        'leases.apply_rent_reserve': 'Apply Rent Reserve',
        'leases.unable_apply_advance': 'Unable to apply Consumable Advance.',
        'leases.apply_consumable_advance': 'Apply Consumable Advance',

        'leases.title': 'Leases — Patrimoine',
        'leases.tenancy': 'Tenancy',
        'leases.heading': 'Leases',
        'leases.page_description': 'Manage tenancy agreements, rent terms and lease lifecycle.',
        'leases.total_leases': 'Total Leases',
        'leases.in_notice': 'In Notice',
        'leases.register': 'Lease Register',
        'leases.register_description': 'Current and historical tenancy agreements.',
        'leases.lease_status': 'Lease Status',
        'leases.all_statuses': 'All Statuses',
        'leases.close': 'Close',
        'leases.property_tenant': 'Property & Tenant',
        'leases.property_tenant_description': 'Select the leased unit and parties to the agreement.',
        'leases.property_unit': 'Property / Unit',
        'leases.unit_search_placeholder': 'Search property, location, unit or owner…',
        'leases.clear_selected_unit': 'Clear selected Unit',
        'leases.selected_unit': 'Selected Unit',
        'leases.ownership': 'Ownership',
        'leases.lease_period': 'Lease Period',
        'leases.lease_period_description': 'Define when the agreement takes effect and its current lifecycle state.',
        'leases.start_date': 'Start Date',
        'leases.end_date': 'End Date',
        'leases.status': 'Status',
        'leases.notice_date': 'Notice Date',
        'leases.rent_terms': 'Rent Terms',
        'leases.rent_terms_description': 'Amounts are VAT inclusive and stored as whole currency units.',
        'leases.monthly_rent': 'Monthly Rent',
        'leases.payment_frequency': 'Payment Frequency',
        'leases.due_day': 'Due Day',
        'leases.due_day_override': 'Due Day Override',
        'leases.vat_rate': 'Management Fee VAT Rate %',
        'leases.proration_override': 'Proration Override',
        'leases.security_deposit': 'Security Deposit',
        'leases.monthly': 'Monthly',
        'leases.quarterly': 'Quarterly',
        'leases.bi_yearly': 'Bi-Yearly',
        'leases.yearly': 'Yearly',
        'leases.from_start_date': 'From start date',
        'leases.automatic': 'Automatic',
        'leases.advance_payment': 'Advance Payment',
        'leases.advance_payment_description': 'Record the contractual advance and how much should remain protected as Rent Reserve.',
        'leases.total_advance_payment': 'Total Advance Payment',
        'leases.rent_reserve': 'Rent Reserve',
        'leases.consumable_advance': 'Consumable Advance',
        'leases.advance_already_received': 'Advance already received',
        'leases.advance_received_description': 'Use this when entering an existing or backdated Lease for which the tenant already paid the advance.',
        'leases.date_received': 'Date Received',
        'leases.payment_method': 'Payment Method',
        'leases.cash_collector': 'Cashier',
        'leases.select_method': 'Select method...',
        'leases.bank_transfer': 'Bank Transfer',
        'leases.mobile_money': 'Mobile Payment',
        'leases.cheque': 'Cheque',
        'leases.cash': 'Cash',
        'leases.optional': 'Optional',
        'leases.cash_collector_placeholder': 'Automatically set to the logged-in User',
        'leases.rent_increment': 'Rent Increment',
        'leases.rent_increment_description': 'Configure the next contractual rent increase where applicable.',
        'leases.increment_type': 'Increment Type',
        'leases.increment_value': 'Increment Value',
        'leases.next_increment_date': 'Next Increment Date',
        'leases.none': 'None',
        'leases.percentage': 'Percentage',
        'leases.fixed_amount': 'Fixed Amount',
        'leases.fees_commission': 'Fees & Commission',
        'leases.fees_commission_description': 'Configure the managing organisation fee and one-time Agent commission applicable to this Lease.',
        'leases.management_fee': 'Managing Organisation Fee',
        'leases.fee_value': 'Fee Value',
        'leases.agent_commission': 'Agent Commission',
        'leases.notes': 'Notes',
        'leases.notes_placeholder': 'Optional lease notes',
        'leases.cancel': 'Cancel',

        'leases.property_unit_help_label': 'About Property and Unit',
        'leases.property_unit_help_text': 'Search for the specific leasable Unit covered by this agreement. A Unit inherits the ownership of its Building and cannot have more than one Active or Notice Lease at the same time.',
        'leases.tenant_help_label': 'About Tenant',
        'leases.tenant_help_text': 'The Party renting this unit. Patrimoine V1 supports exactly one tenant per lease. The selected Party must have the Tenant role.',
        'leases.agent_help_label': 'About Agent',
        'leases.agent_help_text': 'Optional Party that facilitated or manages this lease transaction. If an Agent Commission is greater than zero, an Agent must be selected. The selected Party must have the Agent role.',
        'leases.start_date_help_label': 'About Start Date',
        'leases.start_date_help_text': 'The date the lease begins. Unless a Due Day Override is specified, Patrimoine uses the day of this date as the recurring rent due day.',
        'leases.end_date_help_label': 'About End Date',
        'leases.end_date_help_text': 'Optional contractual end date. Leave this blank for a lease without a predetermined termination date.',
        'leases.status_help_label': 'About Lease Status',
        'leases.status_help_text': 'Draft means the lease is prepared but not yet in force. Active means the tenancy is currently running. Notice means termination notice has been recorded. Terminated means the lease has ended.',
        'leases.notice_date_help_label': 'About Notice Date',
        'leases.notice_date_help_text': 'The date termination notice was received or issued. This field becomes required when the Lease Status is Notice and will later control when Rent Reserve consumption begins.',
        'leases.monthly_rent_help_label': 'About Monthly Rent',
        'leases.monthly_rent_help_text': 'The VAT-inclusive monthly contractual rent for the Unit. Payment Frequency determines how many months are invoiced together. For example, a Monthly Rent of 5,000 with Quarterly frequency creates a 15,000 rent obligation for each quarterly billing period.',
        'leases.payment_frequency_help_label': 'About Payment Frequency',
        'leases.payment_frequency_help_text': 'Controls how often the Monthly Rent becomes due: Monthly, Quarterly, every six months, or Yearly.',
        'leases.due_day_help_label': 'About Due Day Override',
        'leases.due_day_help_text': 'Leave blank to use the day of the Lease Start Date as the rent due day. For example, a lease starting on the 15th will normally be due on the 15th. Enter another day here to override that rule.',
        'leases.vat_rate_help_label': 'About Management Fee VAT',
        'leases.vat_rate_help_text': 'VAT is charged on your management fee, never on the rent. On 100,000 rent with a 10% fee and a 20% rate, the owner is charged 10,000 fee plus 2,000 VAT and receives 88,000. Use 0% where VAT does not apply.',
        'leases.proration_help_label': 'About Proration Override',
        'leases.proration_help_text': 'Leave blank to let Patrimoine calculate the prorated amount automatically for a partial billing period. Enter 0 to deliberately charge no proration. Any other amount replaces the automatic calculation.',
        'leases.security_deposit_help_label': 'About Security Deposit',
        'leases.security_deposit_help_text': 'The contractual security deposit required from the tenant. It is held separately from rent and may later be reduced by itemized deductions before any remaining balance is refunded.',
        'leases.advance_payment_help_label': 'About Advance Payment',
        'leases.advance_payment_help_text': 'Total advance rent contractually expected from the Tenant. This records the Lease agreement only. It does not mean Patrimoine has actually received the money. Actual funds are recorded later through Payments.',
        'leases.rent_reserve_help_label': 'About Rent Reserve',
        'leases.rent_reserve_help_text': 'Portion of the contractual Advance Payment that should remain protected while the Lease is running. After termination notice, Rent Reserve may be consumed against rent according to Patrimoine’s reserve rules.',
        'leases.consumable_advance_help_label': 'About Consumable Advance',
        'leases.consumable_advance_help_text': 'The contractual portion of Advance Payment that is not reserved. Patrimoine calculates this as Total Advance Payment minus Rent Reserve. Actual available money still comes from the tenant-fund ledger.',
        'leases.advance_received_help_label': 'About Advance already received',
        'leases.advance_received_help_text': 'Select this only when the contractual Advance Payment was actually received before this Lease was entered into Patrimoine. Patrimoine will reconstruct the historical payment, protect the Rent Reserve portion, allocate the remaining advance against outstanding rent and create the corresponding owner accounting entries.',
        'leases.increment_type_help_label': 'About Rent Increment Type',
        'leases.increment_type_help_text': 'Choose how the next rent increase is defined. Percentage increases the existing Monthly Rent by a rate. Fixed Amount adds a specific monetary amount. Choose None when no increase has been agreed.',
        'leases.increment_value_help_label': 'About Rent Increment Value',
        'leases.increment_value_help_text': 'Enter the rate or amount of the next rent increase. Its meaning depends on the selected Increment Type.',
        'leases.increment_date_help_label': 'About Next Rent Increment Date',
        'leases.increment_date_help_text': 'Date on which the configured increase should first take effect. Patrimoine V1 stores this contractual date but does not infer future recurring increases beyond it.',
        'leases.management_fee_help_label': 'About Managing Organisation Fee',
        'leases.management_fee_help_text': 'Defines the fee earned by the Managing Organisation for managing rent under this Lease. Choose None, Percentage of rent, or Fixed Amount. The amount is ultimately deducted from Owner entitlement.',
        'leases.management_fee_value_help_label': 'About Managing Organisation Fee Value',
        'leases.management_fee_value_help_text': 'The meaning depends on the Managing Organisation Fee type. For Percentage, enter the percentage rate. For Fixed Amount, enter the monetary amount. When Managing Organisation Fee is None, this must remain 0.',
        'leases.agent_commission_help_label': 'About Agent Commission',
        'leases.agent_commission_help_text': 'One-time commission agreed with the Agent for this lease. Enter the total commission amount in whole currency units. A non-zero commission requires an Agent to be selected.',
        'leases.notes_help_label': 'About Lease Notes',
        'leases.notes_help_text': 'Optional internal information about the agreement that does not form part of Patrimoine’s automated financial calculations.',

        'leases.security_closeout': 'Lease Close-out',
        'leases.security_modal_description': 'Review held funds, itemized deductions and final settlement.',
        'leases.loading_security_deposit': 'Loading Security Deposit…',
        'leases.contractual_deposit': 'Contractual Deposit',
        'leases.held_balance': 'Held Balance',
        'leases.deductions': 'Deductions',
        'leases.refund': 'Refund',
        'leases.tenant_debt': 'Tenant Debt',
        'leases.itemized_deductions': 'Itemized Deductions',
        'leases.itemized_deductions_description': 'Charges retained from the tenant’s Security Deposit.',
        'leases.deduction_date': 'Deduction Date',
        'leases.deduction_description_placeholder': 'e.g. Damaged lock',
        'leases.deduction_reference_placeholder': 'Inspection / work order reference',
        'leases.optional_details': 'Optional details',
        'leases.final_settlement': 'Final Settlement',
        'leases.final_settlement_description': 'Finalize the Security Deposit and create the formal settlement voucher.',
        'leases.settlement_date': 'Settlement Date',
        'leases.closeout_notes_placeholder': 'Optional close-out notes',
        'leases.final_settlement_warning': 'Final settlement is irreversible. Once confirmed, no additional Security Deposit deductions can be added.',
        'leases.security_deposit_settled': 'Security Deposit Settled',

        'leases.tenant_money': 'Tenant Money',
        'leases.tenant_funds_modal_description': 'Review actual held balances and apply eligible funds to rent.',
        'leases.loading_tenant_funds': 'Loading tenant funds…',
        'leases.reserve_protected_short': 'Protected until termination notice.',
        'leases.consumable_advance_description': 'Available tenant advance that may be applied to rent.',
        'leases.manage_security_deposit': 'Security Deposit',
        'leases.apply_reserve_description': 'Rent Reserve becomes consumable after termination notice and may settle an outstanding Invoice.',
        'leases.outstanding_invoice': 'Outstanding Invoice',
        'leases.apply_advance_description': 'Apply available Consumable Advance against an outstanding rent Invoice.',

        'payments.unable_to_load': 'Unable to load payments.',
        'payments.no_matching_payments': 'No payments match the selected filters.',
        'payments.tenant_payment': 'Tenant Payment',
        'payments.owner_deposit': 'Owner Deposit',
        'payments.manage_funds': 'Manage Funds',
        'payments.receipt': 'Receipt',
        'payments.tenant': 'Tenant',
        'payments.owner': 'Owner',
        'payments.reference': 'Ref',
        'payments.collector': 'Collector',
        'payments.lease_number': 'Lease #{id}',
        'payments.general_owner_account': 'General Owner Account',
        'payments.pagination_single': 'Page {current} of {last} · {total} transaction',
        'payments.pagination_plural': 'Page {current} of {last} · {total} transactions',
        'payments.previous': 'Previous',
        'payments.next': 'Next',
        'payments.unable_to_open_receipt': 'Unable to open receipt.',
        'payments.searching': 'Searching…',
        'payments.unable_to_search_tenants': 'Unable to search Tenants.',
        'payments.no_matching_tenants': 'No matching Tenants found.',
        'payments.loading_leases': 'Loading Leases…',
        'payments.no_payable_lease': 'No payable Lease found',
        'payments.no_payable_lease_help': 'This Tenant has no non-draft Lease against which a payment can be recorded.',
        'payments.select_lease_property': 'Select Lease / Property',
        'payments.lease_fifo_outstanding_help': 'Payments are recorded against the applicable Lease so outstanding rent can be allocated FIFO.',
        'payments.unable_to_load_leases': 'Unable to load Leases',
        'payments.unable_to_load_tenant_leases': 'Unable to load Tenant Leases.',
        'payments.search_select_tenant_first': 'Search and select a Tenant first',
        'payments.lease_fifo_help': 'Payments are recorded against the applicable Lease so rent can be allocated FIFO.',
        'payments.unable_to_search_owners': 'Unable to search Owners.',
        'payments.no_matching_owners': 'No matching Property Owners found.',
        'payments.unable_to_load_owner': 'Unable to load Owner details.',
        'payments.no_specific_building': 'No specific Building',
        'payments.building_number': 'Building #{id}',
        'payments.no_specific_unit': 'No specific Unit',
        'payments.unit_number': 'Unit #{id}',
        'payments.select_building_first': 'Select a Building first',
        'payments.validation_amount': 'Enter a valid Payment amount greater than zero.',
        'payments.validation_date': 'Payment Date is required.',
        'payments.validation_method': 'Select a valid Payment Method.',
        'payments.validation_collector': 'The Cashier could not be determined for this cash payment.',
        'payments.unable_to_record': 'Unable to record payment.',
        'payments.select_tenant_required': 'Search for and select a Tenant.',
        'payments.select_lease_required': 'Select the Lease / Property against which the Tenant payment was received.',
        'payments.payment_receipt_unresolved': 'Payment was recorded but its receipt could not be resolved.',
        'payments.select_owner_required': 'Search for and select a Property Owner.',
        'payments.owner_receipt_unresolved': 'Owner deposit was recorded but its receipt could not be resolved.',
        'payments.recording': 'Recording…',
        'payments.record_payment': 'Record Payment',
        'payments.save_payment': 'Save',
        'payments.cash': 'Cash',
        'payments.bank_transfer': 'Bank Transfer',
        'payments.momo': 'Mobile Payment',
        'payments.cheque': 'Cheque',
        'payments.general_funding': 'General Funding',
        'payments.property_expense': 'Property Expense',
        'payments.repair_maintenance': 'Repair / Maintenance',
        'payments.other': 'Other',
        'payments.unnamed_party': 'Unnamed Party',
        'payments.property': 'Property',
        'payments.status_draft': 'Draft',
        'payments.status_active': 'Active',
        'payments.status_notice': 'Notice',
        'payments.status_terminated': 'Terminated',
        'payments.from_date': 'From {date}',
        'payments.loading': 'Loading payments…',
        'payments.unable_to_load_funds': 'Unable to load Payment funds.',
        'payments.maximum_available': 'Maximum available: {amount}',
        'payments.unable_to_classify_funds': 'Unable to classify tenant funds.',
        'payments.allocate_funds': 'Allocate Funds',
        'owners.unable_to_load': 'Unable to load Property Owners.',
        'owners.no_search_results': 'No Property Owners match your search.',
        'owners.unable_to_load_details': 'Unable to load Owner details.',
        'owners.unable_to_load_owner': 'Unable to load this Property Owner.',
        'owners.no_contact_information': 'No contact information available.',
        'owners.no_funds_available': 'This Owner has no funds available for payout.',
        'owners.property': 'property',
        'owners.properties_lower': 'properties',
        'owners.balance': 'balance',
        'owners.pagination_owner': '{total} owner',
        'owners.pagination_owners': '{total} owners',
        'owners.previous': 'Previous',
        'owners.next': 'Next',
        'owners.active': 'Active',
        'owners.unknown': 'Unknown',
        'owners.no_building_ownership': 'No Building ownership records found.',
        'owners.building': 'Building',
        'owners.units': 'Units',
        'owners.unit': 'Unit',
        'owners.no_units_created': 'No Units have been created yet.',
        'owners.no_transactions': 'No owner financial transactions have been recorded.',
        'owners.receipt': 'Receipt',
        'owners.credit': 'Credit',
        'owners.debit': 'Debit',
        'owners.reference_short': 'Ref:',
        'owners.collector_short': 'Collector:',
        'owners.invoice': 'Invoice',
        'owners.page_of': 'Page {current} of {last}',
        'owners.pagination_transaction': '{total} transaction',
        'owners.pagination_transactions': '{total} transactions',
        'owners.no_payouts': 'No payouts have been recorded for this Owner.',
        'owners.select_owner_first': 'Select a Property Owner first.',
        'owners.no_specific_building': 'No specific Building',
        'owners.select_building': 'Select Building',
        'owners.no_specific_unit': 'No specific Unit',
        'owners.select_building_first': 'Select a Building first',
        'owners.invalid_deposit_amount': 'Enter a valid deposit amount greater than zero.',
        'owners.collector_required': 'The Cashier could not be determined for this cash deposit.',
        'owners.recording': 'Recording…',
        'owners.record_deposit': 'Record Deposit',
        'owners.unable_to_record_deposit': 'Unable to record Owner deposit.',
        'owners.no_property_for_expense': 'This Owner has no Building ownership against which an expense can be recorded.',
        'owners.select_expense_building': 'Select the Building against which the expense was incurred.',
        'owners.invalid_expense_amount': 'Enter a valid expense amount greater than zero.',
        'owners.expense_description_required': 'Expense description is required.',
        'owners.record_expense': 'Record Expense',
        'owners.unable_to_record_expense': 'Unable to record Owner expense.',
        'owners.expense_sharing_warning': 'This Owner holds {percentage}% of this Building. Patrimoine will allocate the full expense across all Building owners according to their ownership percentages.',
        'owners.no_payout_funds': 'This Owner does not currently have funds available for payout.',
        'owners.invalid_payout_amount': 'Enter a valid payout amount greater than zero.',
        'owners.payout_exceeds_balance': 'Withdrawal cannot exceed the available Payout account balance of {balance}.',
        'owners.processing': 'Processing…',
        'owners.make_payout': 'Make Payout',
        'owners.unable_to_create_payout': 'Unable to create Owner payout.',
        'owners.invalid_adjustment_amount': 'Enter a valid adjustment amount greater than zero.',
        'owners.adjustment_reason_required': 'An audit reason is required for every manual adjustment.',
        'owners.record_adjustment': 'Record Adjustment',
        'owners.unable_to_record_adjustment': 'Unable to record Owner adjustment.',
        'owners.unable_to_open_document': 'Unable to open document.',
        'owners.owner_deposit': 'Owner Deposit',
        'owners.rent_collected': 'Rent Collected',
        'owners.property_expense': 'Property Expense',
        'owners.management_fee': 'Management Fee',
        'owners.agent_commission': 'Agent Commission',
        'owners.adjustment': 'Adjustment',
        'owners.owner_payout': 'Owner Withdrawal',
        'owners.transaction': 'Transaction',
        'owners.cash': 'Cash',
        'owners.bank_transfer': 'Bank Transfer',
        'owners.momo': 'Mobile Payment',
        'owners.cheque': 'Cheque',
        'owners.general_funding': 'General Funding',
        'owners.repair_maintenance': 'Repair / Maintenance',
        'owners.other': 'Other',
        'owners.unnamed_owner': 'Unnamed Owner',
        'owners.loading': 'Loading owners…',
        'owners.loading_details': 'Loading Owner details…',


        'reports.title': 'Reports — Patrimoine',
        'reports.finance': 'Finance',
        'reports.heading': 'Reports',
        'reports.page_description': 'Review financial and operational reports across owners, tenants and properties.',
        'reports.report_type': 'Report Type',
        'reports.report_type_description': 'Select the report you want to review.',
        'reports.managing_organisation': 'Managing Organisation',
        'reports.managing_organisation_summary': 'Portfolio-wide operational and financial summary.',
        'reports.owner_report_summary': 'Owner balance, credits, debits and ledger history.',
        'reports.building_report_summary': 'Billing, collections, expenses and ownership.',
        'reports.unit_report_summary': 'Lease, billing and collection history for one Unit.',
        'reports.tenant_statement_summary': 'Tenant billing, payments and held funds.',
        'reports.change': 'Change',
        'reports.period_description': 'Leave dates empty to include all available history.',
        'reports.from': 'From',
        'reports.to': 'To',
        'reports.run_report': 'Run Report',
        'reports.pdf': 'PDF',
        'reports.csv': 'CSV',
        'reports.xlsx': 'XLSX',
        'reports.initial_prompt': 'Select a report type and run the report.',
        'reports.not_tenant': 'The selected Party is not a Tenant.',
        'reports.unable_to_open_tenant_statement': 'Unable to open the Tenant Statement.',
        'reports.property_owner': 'Property Owner',
        'reports.search_owner_placeholder': 'Search owner by name, phone or email...',
        'reports.tenant': 'Tenant',
        'reports.search_tenant_placeholder': 'Search tenant by name, phone or email...',
        'reports.building': 'Building',
        'reports.search_building_placeholder': 'Search building...',
        'reports.unit': 'Unit',
        'reports.search_unit_placeholder': 'Search unit...',
        'reports.search': 'Search',
        'reports.search_placeholder': 'Search...',
        'reports.managing_organisation_report': 'Managing Organisation Report',
        'reports.managing_organisation_description': 'Portfolio-wide financial and operational report.',
        'reports.owner_report': 'Owner Report',
        'reports.owner_report_description': 'Consolidated owner financial statement and ledger.',
        'reports.building_report': 'Building Report',
        'reports.building_report_description': 'Billing, collections, expenses and ownership for one Building.',
        'reports.unit_report': 'Unit Report',
        'reports.unit_report_description': 'Lease and financial history for one Unit.',
        'reports.tenant_statement': 'Tenant Statement',
        'reports.tenant_statement_description': 'Billing, payments and held funds for one Tenant.',
        'reports.report': 'Report',
        'reports.searching': 'Searching…',
        'reports.unable_to_search': 'Unable to search.',
        'reports.subject_not_required': 'This report does not require a subject.',
        'reports.no_matching_records': 'No matching records found.',
        'reports.invalid_period': 'The report end date must be on or after the start date.',
        'reports.select_subject_first': 'Select a report subject first.',
        'reports.unable_to_generate': 'Unable to generate report.',
        'reports.buildings': 'Buildings',
        'reports.units': 'Units',
        'reports.owner_accounts': 'Owner Accounts',
        'reports.cash_received': 'Cash Received',
        'reports.billing': 'Billing',
        'reports.total_invoiced': 'Total Invoiced',
        'reports.rent_invoiced': 'Rent Invoiced',
        'reports.security_deposit_debt_invoiced': 'Security Deposit Debt Invoiced',
        'reports.settled': 'Settled',
        'reports.rent_outstanding': 'Rent Outstanding',
        'reports.security_deposit_debt_outstanding': 'Security Deposit Debt Outstanding',
        'reports.total_outstanding': 'Total Outstanding',
        'reports.owner_accounting': 'Owner Accounting',
        'reports.rent_entitlement': 'Rent Entitlement',
        'reports.management_fees': 'Management Fees',
        'reports.agent_commissions': 'Agent Commissions',
        'reports.owner_expenses': 'Owner Expenses',
        'reports.owner_payouts': 'Owner Payouts',
        'reports.owner_funds_held': 'Owner Funds Held',
        'reports.tenant_funds': 'Tenant Funds',
        'reports.rent_reserve': 'Rent Reserve',
        'reports.consumable_advance': 'Consumable Advance',
        'reports.security_deposit': 'Security Deposit',
        'reports.opening_balance': 'Opening Balance',
        'reports.credits': 'Credits',
        'reports.debits': 'Debits',
        'reports.closing_balance': 'Closing Balance',
        'reports.financial_summary': 'Financial Summary',
        'reports.rent_collected': 'Rent Collected',
        'reports.owner_deposits': 'Owner Deposits',
        'reports.property_expenses': 'Property Expenses',
        'reports.payouts': 'Payouts',
        'reports.adjustments_credit': 'Adjustments Credit',
        'reports.adjustments_debit': 'Adjustments Debit',
        'reports.transactions': 'Transactions',
        'reports.leases': 'Leases',
        'reports.security_deposit_debt': 'Security Deposit Debt',
        'reports.invoice_settled': 'Invoice Settled',
        'reports.owner_rent_entitlement': 'Owner Rent Entitlement',
        'reports.ownership': 'Ownership',
        'reports.expenses': 'Expenses',
        'reports.lease_history': 'Lease History',
        'reports.invoices': 'Invoices',
        'reports.receivables': 'Receivables',
        'reports.held_funds': 'Held Funds',
        'reports.payments': 'Payments',
        'reports.payments_report_summary': 'Historical tenant payments and receipts.',
        'reports.payments_report': 'Payments Report',
        'reports.payments_report_description': 'Read-only historical tenant payments and receipt access.',
        'reports.payment_filters': 'Payment Filters',
        'reports.payment_filters_description': 'Leave any filter blank to include all matching records.',
        'reports.all_tenants': 'All tenants',
        'reports.lease': 'Lease',
        'reports.all_leases': 'All leases',
        'reports.all_buildings': 'All Buildings',
        'reports.all_units': 'All units',
        'reports.all_payment_methods': 'All payment methods',
        'reports.payment_method_label': 'Payment Method',
        'reports.payment_method_cash': 'Cash',
        'reports.payment_method_bank': 'Bank',
        'reports.payment_method_mobile': 'Mobile Payment',
        'reports.payment_method_cheque': 'Cheque',
        'reports.cash_receiver': 'Cashier',
        'reports.cash_receiver_placeholder': 'Search cashier name',
        'reports.payment_reference': 'Payment / Reference',
        'reports.payment_reference_placeholder': 'Payment ID or reference',
        'reports.payment_count': 'Payments',
        'reports.total_received': 'Total Received',
        'reports.payment_number': 'Payment',
        'reports.property': 'Property',
        'reports.receipt': 'Receipt',
        'reports.no_payments_found': 'No payments match the selected filters.',
        'reports.unable_to_load_payment_filters': 'Unable to load Payment Report filters.',
        'reports.reporting_period': 'Reporting Period',
        'reports.reporting_period_all_history': 'Reporting Period: All available history',
        'reports.beginning': 'Beginning',
        'reports.present': 'Present',
        'reports.no_records_section': 'No records for this section.',
        'reports.date': 'Date',
        'reports.direction': 'Direction',
        'reports.category': 'Category',
        'reports.amount': 'Amount',
        'reports.invoice': 'Invoice',
        'reports.reference': 'Reference',
        'reports.owner': 'Owner',
        'reports.description': 'Description',
        'reports.start': 'Start',
        'reports.end': 'End',
        'reports.status': 'Status',
        'reports.rent': 'Rent',
        'reports.frequency': 'Frequency',
        'reports.type': 'Type',
        'reports.issue_date': 'Issue Date',
        'reports.due_date': 'Due Date',
        'reports.paid': 'Paid',
        'reports.outstanding': 'Outstanding',
        'reports.method': 'Method',
        'reports.allocated': 'Allocated',
        'reports.unallocated': 'Unallocated',
        'reports.generating': 'Generating report…',
        'reports.could_not_generate': 'The report could not be generated.',
        'reports.unable_to_open': 'Unable to open report.',
        'reports.unable_to_download': 'Unable to download report.',
        'reports.unnamed_party': 'Unnamed Party',
        'reports.building_number': 'Building #:number',
        'reports.unit_number': 'Unit #:number',

        'reports.direction.credit': 'Credit',
        'reports.direction.debit': 'Debit',

        'reports.category.rent_entitlement': 'Rent Entitlement',
        'reports.category.owner_deposit': 'Owner Deposit',
        'reports.category.management_fee': 'Management Fee',
        'reports.category.agent_commission': 'Agent Commission',
        'reports.category.expense': 'Expense',
        'reports.category.owner_expense': 'Owner Expense',
        'reports.category.payout': 'Payout',
        'reports.category.owner_payout': 'Owner Payout',
        'reports.category.adjustment': 'Adjustment',
        'reports.category.adjustment_credit': 'Adjustment Credit',
        'reports.category.adjustment_debit': 'Adjustment Debit',

        'reports.status.active': 'Active',
        'reports.status.inactive': 'Inactive',
        'reports.status.ended': 'Ended',
        'reports.status.terminated': 'Terminated',
        'reports.status.draft': 'Draft',
        'reports.status.issued': 'Issued',
        'reports.status.pending': 'Pending',
        'reports.status.partial': 'Partial',
        'reports.status.partially_paid': 'Partially Paid',
        'reports.status.paid': 'Paid',
        'reports.status.settled': 'Settled',
        'reports.status.overdue': 'Overdue',
        'reports.status.cancelled': 'Cancelled',
        'reports.status.void': 'Void',

        'reports.frequency.monthly': 'Monthly',
        'reports.frequency.quarterly': 'Quarterly',
        'reports.frequency.bi_yearly': 'Bi-yearly',
        'reports.frequency.biyearly': 'Bi-yearly',
        'reports.frequency.yearly': 'Yearly',
        'reports.frequency.annual': 'Yearly',

        'reports.invoice_type.rent': 'Rent',
        'reports.invoice_type.security_deposit_debt': 'Security Deposit Debt',

        'reports.payment_method.cash': 'Cash',
        'reports.payment_method.bank_transfer': 'Bank Transfer',
        'reports.payment_method.momo': 'Mobile Payment',
        'reports.payment_method.cheque': 'Cheque',

        'tenants.title': 'Tenants — Patrimoine',
        'tenants.finance': 'Finance',
        'tenants.heading': 'Tenants',
        'tenants.page_description': 'Review tenant identity, contact information and lease history.',
        'tenants.directory': 'Tenants',
        'tenants.search_description': 'Search by tenant name, phone or email.',
        'tenants.search': 'Search Tenants',
        'tenants.search_placeholder': 'Search tenants...',
        'tenants.select_tenant': 'Select a Tenant',
        'tenants.select_tenant_description': 'Choose a tenant to review their details and leases.',
        'tenants.no_tenant_available': 'No Tenant is available to display.',
        'tenants.unable_to_load': 'Unable to load Tenants.',
        'tenants.pagination_tenant': ':total tenant',
        'tenants.pagination_tenants': ':total tenants',
        'tenants.no_search_results': 'No Tenants match your search.',
        'tenants.not_tenant': 'The selected Party is not a Tenant.',
        'tenants.unable_to_load_details': 'Unable to load Tenant details.',
        'tenants.unable_to_load_tenant': 'Unable to load this Tenant.',
        'tenants.no_contact_information': 'No contact information available.',
        'tenants.tenant_statement': 'Tenant Statement',
        'tenants.total_leases': 'Total Leases',
        'tenants.current_leases': 'Current Leases',
        'tenants.historical_leases': 'Historical Leases',
        'tenants.tenant_details': 'Tenant Details',
        'tenants.party_type': 'Party Type',
        'tenants.party_type.person': 'Person',
        'tenants.party_type.organisation': 'Organisation',
        'tenants.party_type.organization': 'Organisation',
        'tenants.party_type.association': 'Association',

        'tenants.payment_method.cash': 'Cash',
        'tenants.payment_method.bank_transfer': 'Bank Transfer',
        'tenants.payment_method.momo': 'Mobile Payment',
        'tenants.payment_method.cheque': 'Cheque',
        'tenants.apply_security_deposit': 'Apply Security Deposit',
        'tenants.apply_security_deposit_description': 'Apply held Security Deposit against an outstanding Lease receivable.',
        'tenants.security_deposit_available': 'Security Deposit Available',
        'tenants.receivable': 'Receivable',
        'tenants.select_receivable': 'Select Receivable…',
        'tenants.receivable_outstanding': 'Receivable Outstanding',
        'tenants.resulting_security_deposit': 'Remaining Security Deposit',
        'tenants.resulting_receivable': 'Remaining Receivable',
        'tenants.security_application_not_available': 'Security Deposit cannot currently be applied for this Lease.',
        'tenants.security_application_recorded': 'Security Deposit applied successfully.',
        'tenants.security_application_exceeds_deposit': 'The amount cannot exceed the available Security Deposit balance.',
        'tenants.security_application_exceeds_receivable': 'The amount cannot exceed the selected receivable outstanding balance.',
        'tenants.invoice_type.rent': 'Rent',
        'tenants.invoice_type.security_deposit_debt': 'Security Deposit Debt',
        'tenants.payment_method.mobile_payment': 'Mobile Payment',

        'tenants.fund_type.rent_reserve': 'Rent Reserve',
        'tenants.fund_type.consumable_advance': 'Consumable Advance',
        'tenants.fund_type.security_deposit': 'Security Deposit',

        'tenants.lease_status.draft': 'Draft',
        'tenants.lease_status.active': 'Active',
        'tenants.lease_status.notice': 'Notice',
        'tenants.lease_status.terminated': 'Terminated',
        'tenants.lease_status.expired': 'Expired',
        'tenants.lease_status.cancelled': 'Cancelled',

        'tenants.direction.credit': 'Credit',
        'tenants.direction.debit': 'Debit',

        'tenants.category.reserve_funding': 'Reserve Funding',
        'tenants.category.advance_funding': 'Advance Funding',
        'tenants.category.security_deposit_funding': 'Security Deposit Funding',
        'tenants.category.rent_consumption': 'Rent Consumption',
        'tenants.category.advance_consumption': 'Advance Consumption',
        'tenants.category.security_deposit_deduction': 'Security Deposit Deduction',
        'tenants.category.security_deposit_refund': 'Security Deposit Refund',
        'tenants.phone': 'Phone',
        'tenants.alternate_phone': 'Alternate Phone',
        'tenants.email': 'Email',
        'tenants.address': 'Address',
        'tenants.id_registration': 'ID / Registration',
        'tenants.leases': 'Leases',
        'tenants.leases_description': 'Current and historical lease relationships for this Tenant.',
        'tenants.financial_position': 'Financial Position',
        'tenants.financial_position_description': 'Outstanding receivables and tenant-held funds across all leases.',
        'tenants.rent_outstanding': 'Rent Outstanding',
        'tenants.security_deposit_debt': 'Security Deposit Debt',
        'tenants.total_outstanding': 'Total Outstanding',
        'tenants.held_funds': 'Held Funds',
        'tenants.rent_reserve': 'Rent Reserve',
        'tenants.consumable_advance': 'Consumable Advance',
        'tenants.security_deposit': 'Security Deposit',
        'tenants.invoices': 'Invoices',
        'tenants.invoices_description': "Billing history across this Tenant's leases.",
        'tenants.no_invoices': 'No invoices have been recorded for this Tenant.',
        'tenants.invoice': 'Invoice',
        'tenants.type': 'Type',
        'tenants.date': 'Date',
        'tenants.due_date': 'Due Date',
        'tenants.amount': 'Amount',
        'tenants.paid': 'Paid',
        'tenants.outstanding': 'Outstanding',
        'tenants.status': 'Status',
        'tenants.actions': 'Actions',
        'tenants.resend': 'Resend',
        'tenants.opening': 'Opening…',
        'tenants.unable_to_open_invoice': 'Unable to open invoice.',
        'tenants.sending': 'Sending…',
        'tenants.sent': 'Sent',
        'tenants.unable_to_resend_invoice': 'Unable to resend invoice.',
        'tenants.payments': 'Payments',
        'tenants.payments_description': "Cash received and allocation history across this Tenant's leases.",
        'tenants.no_payments': 'No payments have been recorded for this Tenant.',
        'tenants.method': 'Method',
        'tenants.reference': 'Reference',
        'tenants.allocated': 'Allocated',
        'tenants.unallocated': 'Unallocated',
        'tenants.receipt': 'Receipt',
        'tenants.unable_to_open_receipt': 'Unable to open receipt.',
        'tenants.unable_to_resend_receipt': 'Unable to resend receipt.',
        'tenants.fund_history': 'Fund History',
        'tenants.fund_history_description': 'Transaction history for Rent Reserve, Consumable Advance and Security Deposit.',
        'tenants.no_fund_transactions': 'No tenant fund transactions have been recorded for this Tenant.',
        'tenants.fund': 'Fund',
        'tenants.direction': 'Direction',
        'tenants.category': 'Category',
        'tenants.source': 'Source',
        'tenants.payment_number': 'Payment #:number',
        'tenants.invoice_number': 'Invoice #:number',
        'tenants.ledger': 'Ledger',
        'tenants.no_leases': 'No leases have been recorded for this Tenant.',
        'tenants.building': 'Building',
        'tenants.unit': 'Unit',
        'tenants.unnamed_tenant': 'Unnamed Tenant',
        'tenants.lease_ongoing': ':start → ongoing',
        'tenants.lease_dates_unavailable': 'Lease dates unavailable',
        'tenants.previous': 'Previous',
        'tenants.next': 'Next',
        'tenants.loading': 'Loading tenants…',
        'tenants.loading_details': 'Loading Tenant details…',
        'tenants.deposit': 'Deposit',
        'tenants.deposit_description': 'Record money received from the selected Tenant.',
        'tenants.withdrawal': 'Withdrawal',
        'tenants.withdrawal_description': 'Pay available Tenant funds back to the selected Tenant.',
        'tenants.adjustment': 'Adjustment',
        'tenants.adjustment_description': 'Correct a Tenant financial account to the balance that should exist.',
        'tenants.adjustment_warning': 'Use Adjustment only for accounting corrections. Normal receipts and payouts should use Deposit or Withdrawal.',
        'tenants.transaction_context': 'Transaction Context',
        'tenants.lease': 'Lease',
        'tenants.select_lease': 'Select Lease…',
        'tenants.select_lease_first': 'Select a Lease first',
        'tenants.lease_first_help': 'Deposit and Withdrawal are recorded against a specific Lease.',
        'tenants.destination': 'Destination',
        'tenants.account': 'Account',
        'tenants.select_account': 'Select Account…',
        'tenants.current_balance': 'Current Balance',
        'tenants.transaction_amount': 'Transaction Amount',
        'tenants.payment_method_label': 'Payment Method',
        'tenants.resulting_balance': 'Resulting Balance',
        'tenants.correct_balance': 'Correct Balance',
        'tenants.calculated_adjustment': 'Calculated Adjustment',
        'tenants.payment_method': 'Payment Method',
        'tenants.cash_receiver': 'Cashier',
        'tenants.cash_receiver_automatic': 'Automatically set to the logged-in User',
        'tenants.cash_receiver_help': 'For Cash, the logged-in User is automatically recorded as the Cashier and cannot be changed.',
        'tenants.transaction_date': 'Transaction Date',
        'tenants.reference': 'Reference',
        'tenants.notes': 'Notes',
        'tenants.optional': '(Optional)',
        'tenants.reason': 'Reason',
        'tenants.adjustment_reason_placeholder': 'Explain why this account balance must be corrected…',
        'tenants.cancel': 'Cancel',
        'tenants.close': 'Close',
        'tenants.rent_payment': 'Rent Payment',
        'tenants.no_eligible_accounts': 'No eligible destinations are available for this Lease.',
        'tenants.no_withdrawable_funds': 'This Lease has no Tenant funds available for withdrawal.',
        'tenants.unable_to_load_accounts': 'Unable to load Tenant financial accounts.',
        'tenants.select_lease_context': 'Select a Lease or account to see the Building and Unit context.',
        'tenants.transaction_required_fields': 'Complete all required transaction fields.',
        'tenants.adjustment_required_fields': 'Select an account, enter the correct balance, and provide a reason.',
        'tenants.withdrawal_exceeds_balance': 'Withdrawal cannot exceed the available balance.',
        'tenants.invalid_account': 'The selected account is not valid for this transaction.',
        'tenants.transaction_failed': 'Unable to complete the transaction.',
        'tenants.rent_payment_recorded': 'Rent payment recorded successfully.',
        'tenants.deposit_recorded': 'Deposit recorded successfully.',
        'tenants.withdrawal_recorded': 'Withdrawal recorded successfully.',
        'tenants.adjustment_recorded': 'Adjustment recorded successfully.',



        'owners.title': 'Owners — Patrimoine',
        'owners.finance': 'Finance',
        'owners.heading': 'Owners',
        'owners.page_description': 'Review property ownership, owner balances, transactions, deposits and payouts.',
        'owners.property_owners': 'Property Owners',
        'owners.search_description': 'Search by owner name, phone or email.',
        'owners.search_property_owners': 'Search Property Owners',
        'owners.search_placeholder': 'Search owners...',
        'owners.select_property_owner': 'Select a Property Owner',
        'owners.select_owner_description': 'Choose an owner from the directory to review their properties, account balance and financial history.',
        'owners.deposit': 'Deposit',
        'owners.expense': 'Expense',
        'owners.payout': 'Withdrawal',
        'owners.owner_report': 'Owner Report',
        'owners.current_balance': 'Current Balance',
        'owners.account': 'Account',
        'owners.accounts_breakdown': 'Accounts',
        'owners.accounts_breakdown_description': "Every category of this owner's consolidated ledger and its effect on the balance.",
        'owners.payout_account_balance': 'Payout Account (from rent)',
        'owners.deposit_account_balance': 'Deposit / Expense Account',
        'owners.reserve_transfer': 'Account Transfer',
        'owners.transfer': 'Transfer',
        'owners.management_fee_vat': 'VAT on management fee',
        'owners.statement': 'Statement',
        'owners.statement_title': 'Owner statement',
        'owners.statement_description': 'Rent collected, expenses, fees and VAT for a period, and what is left to pay out.',
        'owners.statement_from': 'From',
        'owners.statement_to': 'To',
        'owners.statement_generate': 'Generate',
        'owners.statement_since_payout': 'Pre-filled from the day after the last payout on :date. Change the dates if you need a different period.',
        'owners.statement_no_payout': 'This owner has not been paid out yet, so the statement covers everything to date.',
        'owners.unable_to_open_statement': 'The statement could not be generated.',
        'owners.transfer_title': 'Account Transfer',
        'owners.transfer_description': "Move money between this owner's Payout account and Deposit / Expense account.",
        'owners.transfer_direction': 'Direction',
        'owners.transfer_to_expense': 'Payout account → Deposit / Expense account',
        'owners.transfer_to_payout': 'Deposit / Expense account → Payout account',
        'owners.transfer_available': 'Available in source account',
        'owners.transfer_reason': 'Reason',
        'owners.transfers': 'Account Transfers',
        'owners.review': 'Review',
        'owners.back': 'Back',
        'owners.confirm': 'Confirm',
        'owners.building': 'Building',
        'owners.select_building': 'Select a building…',
        'owners.building_required': 'Select the building this expense belongs to.',
        'owners.billing_mode': 'Billing',
        'owners.billing_mode_single': 'Bill this owner only',
        'owners.billing_mode_split': 'Split across all owners by ownership share',
        'owners.expense_review_title': 'Verify this expense',
        'owners.split_preview_title': 'Share per owner',
        'owners.expense_review_description': 'Nothing is recorded until you confirm. Each billed owner receives the itemized bill by email.',
        'owners.transfers_description': 'Movements between the Payout account and the Deposit / Expense account, with their official vouchers.',
        'owners.no_transfers': 'No account transfers recorded yet.',
        'owners.invalid_transfer_amount': 'Enter a transfer amount greater than zero.',
        'owners.unable_to_transfer': 'Unable to record the account transfer.',
        'owners.voucher': 'Voucher',
        'owners.resend': 'Resend',
        'owners.sending': 'Sending…',
        'owners.sent': 'Sent',
        'owners.unable_to_open_voucher': 'Unable to open the transfer voucher.',
        'owners.unable_to_resend_voucher': 'Unable to resend the transfer voucher.',
        'owners.total_credits': 'Total Credits',
        'owners.total_debits': 'Total Debits',
        'owners.properties': 'Properties',
        'owners.properties_description': 'Buildings owned by this Party, including vacant properties.',
        'owners.owner_ledger': 'Owner Ledger',
        'owners.ledger_description': "Complete auditable financial movements affecting the owner's consolidated account.",
        'owners.payout_history': 'Withdrawal History',
        'owners.payout_history_description': 'Funds previously withdrawn by this Property Owner.',
        'owners.record_owner_deposit': 'Record Owner Deposit',
        'owners.deposit_description': 'Record money received from this Property Owner.',
        'owners.amount': 'Amount',
        'owners.deposit_date': 'Deposit Date',
        'owners.payment_method': 'Payment Method',
        'owners.deposit_purpose': 'Deposit Purpose',
        'owners.reference': 'Reference',
        'owners.collector': 'Collector',
        'owners.notes': 'Notes',
        'owners.optional': '(Optional)',
        'owners.cancel': 'Cancel',
        'owners.close': 'Close',
        'owners.record_property_expense': 'Record Property Expense',
        'owners.expense_description': "Record an expense against one of this Owner's properties.",
        'owners.description': 'Description',
        'owners.expense_date': 'Expense Date',
        'owners.expense_description_placeholder': 'e.g. Air-conditioner repair',
        'owners.make_owner_payout': 'Make Owner Payout',
        'owners.payout_description': 'Withdraw available rent-derived funds for the selected Property Owner.',
        'owners.available_owner_balance': 'Available Owner Balance',
        'owners.payout_date': 'Withdrawal Date',
        'owners.owner_account_adjustment': 'Owner Account Adjustment',
        'owners.adjustment_description': 'Record an exceptional manual accounting correction.',
        'owners.adjustment_warning': 'Adjustments should only be used for accounting corrections. Normal owner deposits, expenses and payouts should use their dedicated actions.',
        'owners.direction': 'Direction',
        'owners.credit_increase_balance': 'Credit — Increase Owner Balance',
        'owners.debit_reduce_balance': 'Debit — Reduce Owner Balance',
        'owners.adjustment_date': 'Adjustment Date',
        'owners.reason': 'Reason',
        'owners.adjustment_reason_placeholder': 'Explain why this manual adjustment is necessary...',
        'owners.repair_maintenance_static': 'Repair & Maintenance',
        'payments.title': 'Payments — Patrimoine',
        'payments.finance': 'Finance',
        'payments.heading': 'Payments',
        'payments.page_description': 'Record and review money received from tenants and property owners.',
        'payments.received_this_month': 'Received This Month',
        'payments.tenant_payments': 'Tenant Payments',
        'payments.owner_deposits': 'Owner Deposits',
        'payments.transactions': 'Transactions',
        'payments.register': 'Payment Register',
        'payments.register_description': 'Incoming payments recorded in Patrimoine.',
        'payments.payment_source': 'Payment Source',
        'payments.all_sources': 'All Sources',
        'payments.payment_method': 'Payment Method',
        'payments.all_methods': 'All Methods',
        'payments.from_date_label': 'From Date',
        'payments.to_date': 'To Date',
        'payments.record_description': 'Record money received from a Tenant or Property Owner.',
        'payments.close': 'Close',
        'payments.source_description': 'Select who provided the money.',
        'payments.tenant_payment_description': 'Rent, arrears or other Lease-related money received from a Tenant.',
        'payments.property_owner': 'Property Owner',
        'payments.owner_payment_description': 'Funds supplied by an Owner for property expenses, repairs or general funding.',
        'payments.tenant_search_description': 'Search for the Tenant rather than selecting from a fixed list.',
        'payments.search_tenant': 'Search Tenant',
        'payments.search_party_placeholder': 'Search by name, phone or email...',
        'payments.change': 'Change',
        'payments.lease_property': 'Lease / Property',
        'payments.owner_search_description': 'Search for the Owner whose account should receive the deposit.',
        'payments.search_owner': 'Search Owner',
        'payments.current_owner_balance': 'Current Owner Balance:',
        'payments.deposit_purpose': 'Deposit Purpose',
        'payments.repair_maintenance_static': 'Repair & Maintenance',
        'payments.building': 'Building',
        'payments.unit': 'Unit',
        'payments.optional': '(Optional)',
        'payments.payment_details': 'Payment Details',
        'payments.amount': 'Amount',
        'payments.payment_date': 'Payment Date',
        'payments.reference_label': 'Reference',
        'payments.reference_placeholder': 'Transaction or deposit reference',
        'payments.collector_placeholder': 'Automatically set to the logged-in User',
        'payments.collector_help': 'Automatically set to the logged-in User for cash payments.',
        'payments.notes': 'Notes',
        'payments.cancel': 'Cancel',
        'payments.manage_funds_description': 'Classify unapplied tenant money into held funds.',
        'payments.loading_position': 'Loading Payment position…',
        'payments.received': 'Received',
        'payments.allocated_to_invoices': 'Allocated to Invoices',
        'payments.unapplied': 'Unapplied',
        'payments.classified': 'Classified',
        'payments.available': 'Available',
        'payments.no_money_remaining': 'This Payment has no money remaining to classify.',
        'payments.classify_remaining_money': 'Classify Remaining Money',
        'payments.classify_description': 'Move unapplied Payment money into a dedicated tenant-held fund.',
        'payments.fund': 'Fund',
        'payments.select_fund': 'Select fund…',
        'payments.rent_reserve': 'Rent Reserve',
        'payments.consumable_advance': 'Consumable Advance',
        'payments.security_deposit': 'Security Deposit',
        'payments.transaction_date': 'Transaction Date',
        'payments.optional_placeholder': 'Optional',
        'payments.classification_notes_placeholder': 'Optional classification notes',
        'parties.saving_changes': 'Saving Changes…',
        'parties.creating_party': 'Creating Party…',
        'parties.unable_to_update_party': 'Unable to update Party.',
        'parties.unable_to_create_party': 'Unable to create Party.',
        'parties.delete_confirmation': 'Delete "{{name}}"?',
        'parties.this_party': 'this Party',
        'parties.delete_restriction': 'Only an unreferenced Party can be deleted. Parties used by leases, ownership, agency or financial history must be retained.',
        'parties.unable_to_delete_party': 'Unable to delete Party.',

        'parties.title': 'Parties — Patrimoine',
        'parties.contacts_stakeholders': 'Contacts & Stakeholders',
        'parties.heading': 'Parties',
        'parties.page_description': 'Manage owners, tenants, agents, organisations and associations.',
        'parties.total_parties': 'Total Parties',
        'parties.people': 'People',
        'parties.organisations': 'Organisations',
        'parties.multiple_roles': 'Multiple Roles',
        'parties.directory': 'Party Directory',
        'parties.directory_description': 'People and entities participating in property operations.',
        'parties.search': 'Search Parties',
        'parties.search_placeholder': 'Search name, email, phone...',
        'parties.party_type': 'Party Type',
        'parties.party_role': 'Party Role',
        'parties.all_types': 'All Types',
        'parties.associations': 'Associations',
        'parties.all_roles': 'All Roles',
        'parties.owners': 'Owners',
        'parties.tenants': 'Tenants',
        'parties.agents': 'Agents',
        'parties.close': 'Close',
        'parties.party_type_description': 'Select the legal nature of this Party.',
        'parties.personal_details': 'Personal Details',
        'parties.organisation_details': 'Organisation Details',
        'parties.contact_identification': 'Contact & Identification',
        'parties.contact_identification_description': 'Optional secondary contact and identification information.',
        'parties.roles': 'Roles',
        'parties.roles_description': 'A Party may perform several functions at the same time.',
        'parties.banking_details': 'Banking Details',
        'parties.banking_description': 'Optional. Primarily used for Owners and Agents.',
        'parties.full_name': 'Full Name',
        'parties.phone': 'Phone',
        'parties.email': 'Email',
        'parties.legal_name': 'Legal Name',
        'parties.contact_person': 'Contact Person',
        'parties.contact_phone': 'Contact Phone',
        'parties.contact_email': 'Contact Email',
        'parties.alternate_phone': 'Alternate Phone',
        'parties.id_number': 'ID Number',
        'parties.registration_number': 'Registration Number',
        'parties.vat_tin': 'VAT / TIN',
        'parties.address': 'Address',
        'parties.bank_name': 'Bank Name',
        'parties.bank_branch': 'Bank Branch',
        'parties.account_name': 'Account Name',
        'parties.account_number': 'Account Number',
        'parties.notes': 'Notes',
        'parties.notes_placeholder': 'Optional internal notes',
        'parties.cancel': 'Cancel',
        'properties.loading':
            'Loading properties…',


        'properties.unable_to_load':
            'Unable to load properties.',

        'properties.no_address':
            'No address provided',

        'properties.unnamed_property':
            'Unnamed Property',

        'properties.unit_lower':
            'unit',

        'properties.units_lower':
            'units',

        'properties.edit':
            'Edit',

        'properties.add_unit':
            'Add Unit',

        'properties.hide_units':
            'Hide Units',

        'properties.view_units':
            'View Units',

        'properties.units':
            'Units',

        'properties.no_ownership_information':
            'No ownership information',

        'properties.owner':
            'Owner',

        'properties.no_units':
            'No units have been added to this property.',

        'properties.unnamed_unit':
            'Unnamed Unit',

        'properties.unit':
            'Unit',

        'properties.page':
            'Page',

        'properties.of':
            'of',

        'properties.previous':
            'Previous',

        'properties.next':
            'Next',

        'properties.edit_property':
            'Edit Property',

        'properties.add_property':
            'Add Property',

        'properties.edit_property_description':
            'Update the building details and ownership allocation.',

        'properties.add_property_description':
            'Create a building, define its ownership and add its units.',

        'properties.save_changes':
            'Save Changes',

        'properties.create_property':
            'Create Property',

        'properties.unable_to_load_owners':
            'Unable to load property owners.',

        'properties.unable_to_load_property':
            'Unable to load property.',

        'properties.party':
            'Party',

        'properties.create_owner_first':
            'Create an owner first…',

        'properties.select_owner':
            'Select owner…',

        'properties.create_new_owner':
            'Create a new owner',

        'properties.new':
            '+ New',

        'properties.no_owners_yet':
            'No owners yet. Create the first Owner Party.',

        'properties.ownership_percentage':
            'Ownership %',

        'properties.remove':
            'Remove',

        'properties.total':
            'Total',

        'properties.unit_name_number':
            'Unit Name / Number',

        'properties.unit_name_placeholder':
            'e.g. Apartment A1',

        'properties.description':
            'Description',

        'properties.optional_description':
            'Optional description',

        'properties.validation_owner_required':
            'A property must have at least one owner.',

        'properties.validation_select_every_owner':
            'Select an owner for every ownership row.',

        'properties.validation_duplicate_owner':
            'The same owner cannot be added more than once.',

        'properties.validation_owner_percentage':
            'Enter a valid ownership percentage for every owner.',

        'properties.validation_ownership_total':
            'Property ownership must total exactly 100%.',

        'properties.validation_unit_required':
            'A property must have at least one unit.',

        'properties.validation_every_unit_name':
            'Every unit must have a name or number.',

        'properties.validation_unique_unit_names':
            'Unit names must be unique within the property.',

        'properties.saving_changes':
            'Saving Changes…',

        'properties.creating_property':
            'Creating Property…',

        'properties.unable_to_update_property':
            'Unable to update property.',

        'properties.unable_to_create_property':
            'Unable to create property.',

        'properties.creating_owner':
            'Creating Owner…',

        'properties.unable_to_create_owner':
            'Unable to create owner.',

        'properties.create_owner':
            'Create Owner',

        'properties.person_required_fields':
            'Name, phone and email are required for a person.',

        'properties.organisation_required_fields':
            'Legal name and contact person details are required.',

        'properties.unable_to_locate_unit':
            'Unable to locate this unit.',

        'properties.property':
            'Property',

        'properties.edit_unit':
            'Edit Unit',

        'properties.edit_unit_description':
            'Update this unit\'s name or description.',

        'properties.add_unit_description':
            'Add a leasable unit to an existing property.',

        'properties.validation_valid_property':
            'A valid property must be selected.',

        'properties.validation_unit_name_required':
            'Unit name or number is required.',

        'properties.adding_unit':
            'Adding Unit…',

        'properties.unable_to_update_unit':
            'Unable to update unit.',

        'properties.unable_to_add_unit':
            'Unable to add unit.',

        'properties.title':
            'Properties — Patrimoine',

        'properties.portfolio':
            'Portfolio',

        'properties.heading':
            'Properties',

        'properties.page_description':
            'Manage buildings, ownership and individual units.',

        'properties.buildings':
            'Buildings',

        'properties.total_units':
            'Total Units',

        'properties.single_unit_properties':
            'Single-Unit Properties',

        'properties.multi_unit_properties':
            'Multi-Unit Properties',

        'properties.property_portfolio':
            'Property Portfolio',

        'properties.portfolio_description':
            'Buildings and their associated units.',

        'properties.search':
            'Search properties',

        'properties.search_placeholder':
            'Search buildings or units...',

        'properties.close':
            'Close',

        'properties.property_details':
            'Property Details',

        'properties.property_details_description':
            'Basic information identifying the building.',

        'properties.property_name':
            'Property Name',

        'properties.property_name_placeholder':
            'e.g. Airport Residential Apartments',

        'properties.location':
            'Location',

        'properties.location_placeholder':
            'e.g. Airport Residential, Accra',

        'properties.address':
            'Address',

        'properties.address_placeholder':
            'Street or property address',

        'properties.optional_property_description':
            'Optional property description',

        'properties.ownership':
            'Ownership',

        'properties.ownership_description':
            'Ownership must total exactly 100%.',

        'properties.add_owner':
            '+ Add Owner',

        'properties.units_description':
            'Every property must contain at least one leasable unit.',

        'properties.cancel':
            'Cancel',

        'properties.save':
            'Save',

        'properties.create_owner_description':
            'Create an Owner Party and assign it to this property.',

        'properties.owner_type':
            'Owner Type',

        'properties.person':
            'Person',

        'properties.organisation':
            'Organisation',

        'properties.association':
            'Association',

        'properties.full_name':
            'Full Name',

        'properties.phone':
            'Phone',

        'properties.email':
            'Email',

        'properties.legal_name':
            'Legal Name',

        'properties.contact_person':
            'Contact Person',

        'properties.contact_phone':
            'Contact Phone',

        'properties.contact_email':
            'Contact Email',

        'properties.existing_unit_name_placeholder':
            'e.g. Apartment A2',
        'properties.no_properties_found':
            'No properties found',
        'properties.no_properties_hint':
            'Add a property or change your search.',

        'properties.optional_unit_description':
            'Optional unit description',
        'core.session_expired':
            'Your session has expired. Please sign in again.',
        'core.request_failed':
            'Patrimoine could not complete that.',

        'pagination.summary':
            'Showing :from–:to of :total',

        'pagination.empty':
            'Nothing to show',

        'pagination.rows_per_page':
            'Rows per page',

        'pagination.navigation':
            'Pagination',

        'pagination.previous':
            'Previous page',

        'pagination.next':
            'Next page',

        'pagination.go_to_page':
            'Go to page :page',

        'pagination.current_page':
            'Page :page, current page',

        'profile.download_data':
            'Download my data',

        'profile.downloading':
            'Preparing…',

        'settings.everything_title':
            'Download everything',

        'settings.everything_description':
            'A complete copy of everything Patrimoine holds for this organisation, financial history included, as one JSON file. The registry export above is the portable half; this is the whole of it, for answering somebody who asks what you hold.',

        'settings.everything_action':
            'Download everything',

        'parties.export_data':
            'Data',

        'parties.exporting':
            'Preparing…',

        'parties.erase':
            'Erase',

        'parties.erasing':
            'Erasing…',

        'parties.erase_title':
            'Erase this person',

        'parties.erase_description':
            'Read what goes and what stays, then type the name and your password.',

        'parties.erase_warning':
            'Their name, e-mail address, telephone numbers, postal address, identity and registration numbers, bank details and notes are destroyed permanently. Nobody — not you, not us — can bring them back.',

        'parties.erase_kept':
            'The invoices, payments and journal entries stay, because the law that requires them kept is the same law that lets us refuse to destroy them. They will refer to this person by a reference instead of a name, so the accounts still balance and still explain themselves.',

        'parties.erase_name_label':
            'Type the name on the record',

        'parties.erase_name_hint':
            'Type :name exactly as it is written.',

        'parties.erase_password_label':
            'Your password',

        'parties.erase_confirm':
            'Erase this person',

        'settings.summary':
            'Account summary',

        'settings.summary_description':
            'What this account is, at a glance.',

        'settings.summary_account':
            'Account',

        'settings.summary_plan':
            'Plan',

        'settings.summary_users':
            'Users',

        'settings.summary_leases':
            'Active leases',

        'settings.summary_parties':
            'Parties',

        'settings.summary_created':
            'Opened on',

        'settings.summary_trial':
            'Trial ends',

        'settings.need_help':
            'Need help?',

        'settings.need_help_description':
            'Every screen in Patrimoine is explained in the guide, and every refusal carries a code you can look up there.',

        'settings.open_guide':
            'Open the guide',

        'settings.close_account':
            'Close this account',

        'settings.close_account_description':
            'Permanently delete this organisation and everything it holds. This cannot be undone.',

        'settings.close_account_action':
            'Close account',

        'settings.close_account_drawer':
            'Read what goes, then type the name and your password.',

        'settings.close_account_warning':
            'Everything below is destroyed permanently, along with the properties, leases, invoices, payments and the financial journal behind them. Nobody — not you, not us — can bring it back.',

        'settings.close_account_name_label':
            'Type the organisation name',

        'settings.close_account_name_hint':
            'Type :name exactly as it is written.',

        'settings.close_account_password_label':
            'Your password',

        'settings.close_account_confirm':
            'Delete everything',

        'settings.close_account_closing':
            'Closing the account…',

        'settings.close_account_done':
            'The account is closed. Everything it held has been deleted.',

    },

    fr: {
        /* ---- V1.0.7 additions ---- */
        'leases.record_deduction':
            'Enregistrer une retenue sur caution',
        'leases.record_deduction_description':
            'Les retenues détaillées réduisent la caution remboursable avant le règlement.',
        'leases.deduction_description':
            'Description',
        'leases.deduction_amount':
            'Montant',
        'leases.deduction_fields_required':
            'Renseignez une description, un montant entier supérieur à zéro et une date.',
        'leases.deduction_record_failed':
            'Impossible d\'enregistrer la retenue.',
        'release.summary_line':
            'Cette mise à jour apporte de nouvelles fonctionnalités et des améliorations dans tout Patrimoine 365.',
        'release.view_details':
            'Voir le journal complet des mises à jour',
        'dashboard.occupancy_rate':
            'Taux d\'occupation',
        'dashboard.occupied':
            'Occupées',
        'dashboard.vacant':
            'Vacantes',
        'dashboard.vacant_commercial':
            'Commerciaux vacants',
        'dashboard.vacant_residential':
            'Résidentiels vacants',
        'dashboard.collections_trend':
            'Tendance des encaissements',
        'dashboard.collections_trend_description':
            'Loyers encaissés au cours des six derniers mois.',
        'dashboard.funds_held':
            'Fonds détenus',
        'dashboard.funds_held_description':
            'Soldes actuellement détenus pour le compte des propriétaires et des locataires.',
        'dashboard.tenant_funds_held':
            'Fonds locataires détenus',
        'dashboard.expiring_leases':
            'Baux arrivant à échéance',
        'dashboard.expiring_leases_description':
            'Baux se terminant dans les 90 prochains jours.',
        'dashboard.upcoming_increments':
            'Augmentations de loyer à venir',
        'dashboard.upcoming_increments_description':
            'Augmentations de loyer prenant effet dans les 60 prochains jours.',
        'dashboard.ends':
            'Fin',
        'dashboard.effective':
            'Effectif le',
        'dashboard.no_expiring_leases':
            'Aucun bail n\'expire dans les 90 prochains jours.',
        'dashboard.no_increments':
            'Aucune augmentation de loyer n\'est prévue.',
        'dashboard.no_collections':
            'Aucun encaissement enregistré pour le moment.',
        /* ---- V1.0.9 additions ---- */
        'dashboard.management_fees_this_month':
            'Frais de gestion ce mois-ci',
        'dashboard.more_records':
            '+:count autres',
        'dashboard.paid_of_total':
            ':paid payé sur :total',
        'dashboard.increments_count_aria':
            'Nombre d\'augmentations de loyer prenant effet dans les 60 prochains jours',
        'dashboard.unable_to_load_section':
            'Impossible de charger cette section.',
        /* ---- end V1.0.9 additions ---- */
        'activity_metadata.format':
            'Format',

        'activity_metadata.report_type':
            'Type de rapport',

        'activity_metadata.document_type':
            'Type de document',

        'activity_metadata.delivery':
            'Mode d’envoi',

        'activity_metadata.reference':
            'Référence',

        'activity_metadata.invitation_sent':
            'Invitation envoyée',

        'activity_metadata.source':
            'Source',

        'activity_actions.auth.login':
            'Connexion',

        'activity_actions.auth.login_failed':
            'Échec de connexion',

        'activity_actions.auth.logout':
            'Déconnexion',

        'activity_actions.user.created':
            'Utilisateur créé',

        'activity_actions.user.updated':
            'Utilisateur modifié',

        'activity_actions.user.deleted':
            'Utilisateur supprimé',

        'activity_actions.user.invitation_resent':
            'Invitation utilisateur renvoyée',

        'activity_actions.user.password_reset_requested':
            'Réinitialisation du mot de passe demandée',

        'activity_actions.user.invitation_accepted':
            'Invitation utilisateur acceptée',

        'activity_actions.user.password_reset':
            'Réinitialisation du mot de passe effectuée',

        'activity_actions.user.password_changed':
            'Mot de passe modifié',

        'activity_actions.party.created':
            'Partie créée',

        'activity_actions.party.updated':
            'Partie modifiée',

        'activity_actions.party.deleted':
            'Partie supprimée',

        'activity_actions.building.created':
            'Immeuble créé',

        'activity_actions.building.updated':
            'Immeuble modifié',

        'activity_actions.building.deleted':
            'Immeuble supprimé',

        'activity_actions.unit.created':
            'Unité créée',

        'activity_actions.unit.updated':
            'Unité modifiée',

        'activity_actions.unit.deleted':
            'Unité supprimée',

        /* ---- V1.0.35: actions that were written but never named ---- */
        'activity_actions.adjustment_voucher.downloaded':
            'Bon d’ajustement téléchargé',
        'activity_actions.auth.email_verified':
            'Adresse e-mail vérifiée',
        'activity_actions.auth.invitation_accepted':
            'Invitation acceptée',
        'activity_actions.auth.password_changed':
            'Mot de passe modifié',
        'activity_actions.auth.password_reset':
            'Mot de passe réinitialisé',
        'activity_actions.expense_invoice.created':
            'Facture de dépense locataire créée',
        'activity_actions.invoice_account_payment.cancelled':
            'Paiement de facture depuis un compte annulé',
        'activity_actions.invoice_account_payment.recorded':
            'Facture réglée depuis un compte',
        'activity_actions.invoice_payment_receipt.downloaded':
            'Reçu de paiement de facture téléchargé',
        'activity_actions.lease.extended':
            'Bail prolongé',
        'activity_actions.lease.rent_increment_cancelled':
            'Augmentation de loyer annulée',
        'activity_actions.lease.rent_increment_scheduled':
            'Augmentation de loyer programmée',
        'activity_actions.lease.termination_cancelled':
            'Résiliation de bail annulée',
        'activity_actions.lease.termination_completed':
            'Résiliation de bail finalisée',
        'activity_actions.lease.termination_initiated':
            'Résiliation de bail engagée',
        'activity_actions.lease.termination_notice_downloaded':
            'Préavis de résiliation téléchargé',
        'activity_actions.organisation.closed_by_customer':
            'Compte fermé par le client',
        'activity_actions.organisation.data_exported':
            'Données de l’organisation exportées',
        'activity_actions.organisation.registered':
            'Organisation inscrite',
        'activity_actions.owner_expense_bill.downloaded':
            'Facture de dépenses téléchargée',
        'activity_actions.owner_expense_bill.recorded':
            'Facture de dépenses enregistrée',
        'activity_actions.owner_expense_bill.resent':
            'Facture de dépenses renvoyée',
        'activity_actions.owner_expense_bill_payment.cancelled':
            'Paiement de facture de dépenses annulé',
        'activity_actions.owner_expense_bill_payment.recorded':
            'Facture de dépenses réglée',
        'activity_actions.owner_expense_bill_payment_receipt.downloaded':
            'Reçu de paiement de facture de dépenses téléchargé',
        'activity_actions.owner_payout_receipt.downloaded':
            'Reçu de paiement au propriétaire téléchargé',
        'activity_actions.owner_reserve_transfer.recorded':
            'Transfert entre comptes du propriétaire',
        'activity_actions.owner_reserve_transfer_voucher.downloaded':
            'Bon de transfert propriétaire téléchargé',
        'activity_actions.owner_reserve_transfer_voucher.resent':
            'Bon de transfert propriétaire renvoyé',
        'activity_actions.party.data_exported':
            'Données d’un tiers exportées',
        'activity_actions.party.erased':
            'Tiers effacé',
        'activity_actions.registry.exported':
            'Registre exporté',
        'activity_actions.registry.imported':
            'Registre importé',
        'activity_actions.security_deposit.deduction_added':
            'Retenue sur dépôt de garantie ajoutée',
        'activity_actions.tenant_adjustment.recorded':
            'Solde locataire ajusté',
        'activity_actions.tenant_expense.recorded':
            'Dépense locataire enregistrée',
        'activity_actions.tenant_expense_voucher.downloaded':
            'Bon de dépense locataire téléchargé',
        'activity_actions.tenant_expense_voucher.resent':
            'Bon de dépense locataire renvoyé',
        'activity_actions.tenant_fund.deposit':
            'Dépôt sur un fonds locataire',
        'activity_actions.tenant_fund.transfer_recorded':
            'Transfert entre comptes locataire',
        'activity_actions.tenant_fund_transfer_voucher.downloaded':
            'Bon de transfert locataire téléchargé',
        'activity_actions.tenant_fund_transfer_voucher.resent':
            'Bon de transfert locataire renvoyé',
        'activity_actions.tenant_withdrawal.recorded':
            'Retrait locataire enregistré',
        'activity_actions.lease.created':
            'Bail créé',

        'activity_actions.lease.updated':
            'Bail modifié',

        'activity_actions.lease.deleted':
            'Bail supprimé',

        'activity_actions.managing_organisation.created':
            'Organisation gestionnaire configurée',

        'activity_actions.managing_organisation.updated':
            'Organisation gestionnaire modifiée',

        'activity_actions.payment.recorded':
            'Paiement enregistré',

        'activity_actions.tenant_fund.classified':
            'Fonds du locataire classifiés',

        'activity_actions.rent_reserve.consumed':
            'Réserve de loyer consommée',

        'activity_actions.consumable_advance.consumed':
            'Avance consommable utilisée',

        'activity_actions.security_deposit.deduction_recorded':
            'Déduction du dépôt de garantie enregistrée',

        'activity_actions.security_deposit.settled':
            'Dépôt de garantie réglé',

        'activity_actions.owner_expense.recorded':
            'Dépense propriétaire enregistrée',

        'activity_actions.owner_deposit.recorded':
            'Dépôt propriétaire enregistré',

        'activity_actions.owner_adjustment.recorded':
            'Ajustement propriétaire enregistré',

        'activity_actions.owner_payout.recorded':
            'Versement au propriétaire enregistré',

        'activity_actions.invoice.downloaded':
            'Facture téléchargée',

        'activity_actions.receipt.downloaded':
            'Reçu téléchargé',

        'activity_actions.owner_deposit_receipt.downloaded':
            'Reçu de dépôt propriétaire téléchargé',

        'activity_actions.security_deposit_voucher.downloaded':
            'Bon de règlement du dépôt de garantie téléchargé',

        'activity_actions.invoice.resent':
            'Facture renvoyée',

        'activity_actions.email.suppressed':
            'E-mail non envoyé',

        'activity_actions.receipt.resent':
            'Reçu renvoyé',

        'activity_actions.report.exported':
            'Rapport exporté',

        'activity_actions.activity_log.exported':
            'Journal d’activité exporté',

        'activity_entities.user':
            'Utilisateur',

        'activity_entities.party':
            'Partie',

        'activity_entities.building':
            'Immeuble',

        'activity_entities.unit':
            'Unité',

        'activity_entities.lease':
            'Bail',

        'activity_entities.payment':
            'Paiement',

        'activity_entities.invoice':
            'Facture',

        'activity_entities.receipt':
            'Reçu',

        'activity_entities.tenant_fund':
            'Fonds du locataire',

        'activity_entities.rent_reserve':
            'Réserve de loyer',

        'activity_entities.consumable_advance':
            'Avance consommable',

        'activity_entities.security_deposit':
            'Dépôt de garantie',

        'activity_entities.owner_expense':
            'Dépense propriétaire',

        'activity_entities.owner_account':
            'Compte propriétaire',

        'activity_entities.owner_transaction':
            'Transaction propriétaire',

        'activity_entities.owner_payout':
            'Versement au propriétaire',

        'activity_entities.managing_organisation':
            'Organisation gestionnaire',

        'activity_entities.report':
            'Rapport',

        'activity_entities.activity_log':
            'Journal d’activité',

        'shell.my_profile':
            'Mon profil',

        'shell.my_profile_description':
            'Mettre à jour mon profil',

        'shell.profile_description':
            'Mettez à jour les informations de votre compte.',

        'password.section':
            'Mot de passe',

        'password.profile_new_help':
            'Laissez vide pour conserver votre mot de passe actuel.',

        'password.profile_current_help':
            'Requis uniquement lorsque vous définissez un nouveau mot de passe.',

        'tenants.payment_method_cash':
            'Espèces',

        'tenants.payment_method_bank_transfer':
            'Virement bancaire',

        'tenants.payment_method_momo':
            'Paiement mobile',

        'tenants.payment_method_cheque':
            'Chèque',

        'owners.collector_placeholder':
            'Défini automatiquement selon l\'utilisateur connecté',

        'financial_journal.transaction_types.management_fee_vat':
            'TVA sur frais de gestion',

        'errors.heading':
            'Codes d’erreur',

        'errors.intro':
            'Chaque message affiché par Patrimoine lorsqu’une action échoue porte un code. Recherchez-le ici pour comprendre ce qui s’est passé et savoir quoi faire.',

        'errors.search_label':
            'Rechercher',

        'errors.search_placeholder':
            'Recherchez un code, ou des mots du message',

        'errors.what_happened':
            'Ce qui s’est passé',

        'errors.what_to_do':
            'Que faire',

        'errors.no_matches':
            'Aucun résultat pour cette recherche. Essayez moins de mots, ou le code lui-même.',

        'errors.severity_fix_yourself':
            'Vous pouvez le corriger',

        'errors.severity_try_again':
            'À réessayer',

        'errors.severity_ask_admin':
            'Un administrateur peut vous aider',

        'errors.severity_contact_us':
            'Celle-ci vient de nous',

        'errors.explain_code':
            'Que signifie ce message ?',

        'wizard.title':
            'Assistant de bail',

        'wizard.eyebrow':
            'Baux',

        'wizard.heading':
            'Créer un bail, étape par étape',

        'wizard.subtitle':
            'Tout ce qu’un bail exige, en une fois. Rien n’est enregistré avant la dernière page.',

        'wizard.cancel':
            'Annuler',

        'wizard.launch':
            'Assistant de bail',

        'wizard.invite_title':
            'Créez votre premier bail',

        'wizard.invite_text':
            'L’assistant crée le bien, le propriétaire, le locataire et le bail en une seule fois.',

        'wizard.step_counter':
            'Étape :current sur :total',

        'wizard.back':
            'Retour',

        'wizard.next':
            'Suivant',

        'wizard.save_draft':
            'Enregistrer en brouillon',

        'wizard.drafts_title':
            "Assistants inachevés",

        'wizard.drafts_note':
            "Commencés sans être terminés. Reprenez là où vous en étiez, ou supprimez-les.",

        'wizard.drafts_continue':
            "Reprendre",

        'wizard.drafts_discard':
            "Supprimer",

        'wizard.drafts_discard_confirm':
            "Supprimer ?",

        'wizard.drafts_discard_failed':
            "Celui-ci n’a pas pu être supprimé. Réessayez.",

        'wizard.draft_missing':
            "Cet assistant n’existe plus. Il a été terminé ou supprimé.",

        'wizard.create_activate':
            'Enregistrer et activer',

        'wizard.saving':
            'Enregistrement…',

        'wizard.load_failed':
            'Impossible de charger vos biens et vos parties.',

        'wizard.save_failed':
            'Le bail n’a pas pu être créé. Rien n’a été enregistré.',

        'wizard.step1_title':
            'Quelques mots d’abord',

        'wizard.step2_title':
            'Bien et lot',

        'wizard.step3_title':
            'À qui appartient le bien',

        'wizard.step4_title':
            'Qui loue',

        'wizard.step5_title':
            'Un agent intervient-il',

        'wizard.step6_title':
            'Durée du bail',

        'wizard.step7_title':
            'Préavis et augmentations',

        'wizard.step8_title':
            'Loyer et avance',

        'wizard.step9_title':
            'Honoraires et commission',

        'wizard.step10_title':
            'Vérifier et créer',

        'wizard.glossary_party_term':
            'Partie',

        'wizard.glossary_party_text':
            'Toute personne avec qui vous traitez : un particulier, une société ou une association.',

        'wizard.glossary_owner_term':
            'Propriétaire',

        'wizard.glossary_owner_text':
            'La partie à qui appartient le bien. Vous encaissez le loyer pour son compte.',

        'wizard.glossary_tenant_term':
            'Locataire',

        'wizard.glossary_tenant_text':
            'La partie qui occupe le lot et paie le loyer.',

        'wizard.glossary_agent_term':
            'Agent',

        'wizard.glossary_agent_text':
            'Une partie qui a présenté le locataire et perçoit une commission. Facultatif.',

        'wizard.glossary_property_term':
            'Bien',

        'wizard.glossary_property_text':
            'Un immeuble ou un terrain. Il contient un ou plusieurs lots.',

        'wizard.glossary_unit_term':
            'Lot',

        'wizard.glossary_unit_text':
            'Ce qui est réellement loué : un appartement, un magasin, un bureau.',

        'wizard.glossary_lease_term':
            'Bail',

        'wizard.glossary_lease_text':
            'Le contrat entre un locataire et un lot, avec son loyer et ses dates.',

        'wizard.step1_note':
            'Vous pouvez revenir en arrière à tout moment. Rien n’est enregistré avant la fin.',

        'wizard.property':
            'Bien',

        'wizard.use_existing_property':
            'Utiliser un bien existant',

        'wizard.add_new_property':
            'Ajouter un nouveau bien',

        'wizard.choose_property':
            'Choisir le bien',

        'wizard.property_name':
            'Nom du bien',

        'wizard.property_address':
            'Adresse',

        'wizard.unit':
            'Lot',

        'wizard.use_existing_unit':
            'Utiliser un lot existant',

        'wizard.add_new_unit':
            'Ajouter un nouveau lot',

        'wizard.choose_unit':
            'Choisir le lot',

        'wizard.vacant_units_only':
            'Seuls les lots vacants sont proposés. Un lot ne peut porter qu’un bail actif à la fois.',

        'wizard.unit_name':
            'Nom ou numéro du lot',

        'wizard.unit_commercial':
            'Il s’agit d’un lot commercial',

        'wizard.step3_note':
            'La somme des parts doit atteindre 100 %. Cette page est ignorée si le bien a déjà ses propriétaires.',

        'wizard.add_owner':
            'Ajouter un autre propriétaire',

        'wizard.owner':
            'Propriétaire',

        'wizard.share':
            'Part (%)',

        'wizard.choose_owner':
            'Choisir le propriétaire',

        'wizard.owner_total':
            'Total : :total %',

        'wizard.remove':
            'Retirer',

        'wizard.use_existing_party':
            'Choisir une partie existante',

        'wizard.add_new_party':
            'Ajouter une nouvelle partie',

        'wizard.party_type':
            'Type',

        'wizard.person':
            'Particulier',

        'wizard.organisation':
            'Société',

        'wizard.given_names':
            'Prénoms',

        'wizard.surname':
            'Nom de famille',

        'wizard.legal_name':
            'Raison sociale',

        'wizard.contact_name':
            'Personne à contacter',

        'wizard.phone':
            'Téléphone',

        'wizard.email':
            'E-mail',

        'wizard.tenant':
            'Locataire',

        'wizard.choose_tenant':
            'Choisir le locataire',

        'wizard.agent':
            'Agent',

        'wizard.no_agent':
            'Aucun agent',

        'wizard.choose_agent':
            'Choisir l’agent',

        'wizard.agent_commission':
            'Commission',

        'wizard.agent_commission_help':
            'Payée une fois, sur les fonds du propriétaire.',

        'wizard.start_date':
            'Date de début',

        'wizard.duration':
            'Durée',

        'wizard.duration_12':
            '12 mois',

        'wizard.duration_6':
            '6 mois',

        'wizard.duration_24':
            '24 mois',

        'wizard.duration_custom':
            'Choisir moi-même la date de fin',

        'wizard.duration_open':
            'Sans date de fin',

        'wizard.end_date':
            'Date de fin',

        'wizard.end_date_help':
            'La date de fin n’est pas obligatoire. Sans elle, le bail court jusqu’à sa résiliation.',

        'wizard.notice_date':
            'Date de préavis',

        'wizard.notice_date_help':
            'Date à laquelle le préavis doit être donné. Laissez vide s’il n’y en a pas.',

        'wizard.increment_type':
            'Augmentation du loyer',

        'wizard.increment_none':
            'Aucune augmentation prévue',

        'wizard.increment_percentage':
            'Un pourcentage',

        'wizard.increment_fixed':
            'Un montant fixe',

        'wizard.increment_value':
            'Augmentation',

        'wizard.increment_date':
            'Date de la prochaine augmentation',

        'wizard.rent_amount':
            'Loyer',

        'wizard.frequency':
            'Payé tous les',

        'wizard.frequency_monthly':
            'Mois',

        'wizard.frequency_quarterly':
            'Trimestre',

        'wizard.frequency_bi_yearly':
            'Six mois',

        'wizard.frequency_yearly':
            'An',

        'wizard.due_day':
            'Jour d’échéance',

        'wizard.due_day_help':
            'Laissez vide pour utiliser le jour de la date de début.',

        'wizard.proration':
            'Ajustement de la première période',

        'wizard.proration_help':
            'Laissez vide : Patrimoine le calcule.',

        'wizard.security_deposit':
            'Dépôt de garantie',

        'wizard.rent_reserve':
            'Réserve de loyer',

        'wizard.advance_amount':
            'Avance',

        'wizard.advance_received':
            'Déjà reçue',

        'wizard.advance_date':
            'Reçue le',

        'wizard.advance_method':
            'Reçue par',

        'wizard.method_cash':
            'Espèces',

        'wizard.method_bank_transfer':
            'Virement bancaire',

        'wizard.method_cheque':
            'Chèque',

        'wizard.method_mobile_money':
            'Mobile money',

        'wizard.advance_reference':
            'Référence',

        'wizard.fee_type':
            'Honoraires de gestion',

        'wizard.fee_percentage':
            'Pourcentage du loyer',

        'wizard.fee_fixed':
            'Montant fixe',

        'wizard.fee_none':
            'Aucun honoraire',

        'wizard.fee_value':
            'Honoraires',

        'wizard.fee_vat':
            'TVA sur les honoraires (%)',

        'wizard.fee_vat_help':
            'La TVA porte sur vos honoraires et est facturée au propriétaire, jamais sur le loyer.',

        'wizard.commission_echo':
            'Commission de l’agent saisie précédemment :',

        'wizard.step10_note':
            'La création du bail crée aussi tout ce qui précède. Un brouillon peut être activé plus tard depuis la page Baux.',

        'parties.email_policy':
            'Communications par e-mail',

        'parties.email_policy_description':
            'Indique si Patrimoine peut envoyer des documents et des avis à cette partie.',

        'parties.email_policy_inherit':
            'Suivre le paramètre de l’organisation',

        'parties.email_policy_always':
            'Toujours envoyer à cette partie',

        'parties.email_policy_never':
            'Ne jamais envoyer à cette partie',

        'parties.email_policy_help':
            'Factures, reçus, rappels, avis et bons. Les e-mails destinés aux utilisateurs de Patrimoine ne sont jamais concernés.',

        'parties.emails_off':
            'E-mails désactivés',

        'parties.given_names':
            'Prénoms',
        'parties.surname':
            'Nom de famille',
        'parties.has_email':
            'E-mail renseigné',
        'parties.has_email_all':
            'E-mail : Tous',
        'parties.has_email_yes':
            'Avec e-mail',
        'parties.has_email_no':
            'Sans e-mail',
        'parties.sort_by_surname':
            'Trier par nom de famille',
        'parties.sort_presentation_only':
            'Affichage uniquement — trie la page chargée',
        'parties.delete_party':
            'Supprimer la partie',
        'parties.delete_party_description':
            'Cette action est définitive et irréversible.',
        'parties.delete_party_prompt':
            'Vous êtes sur le point de supprimer :',
        'parties.deleting_party':
            'Suppression…',
        'reports.occupancy_report':
            'Occupation',
        'reports.occupancy_report_summary':
            'Occupation et vacance de l\'ensemble du portefeuille par immeuble.',
        'reports.occupancy_report_description':
            'Unités occupées et vacantes du portefeuille, réparties par classification et par immeuble.',
        'reports.arrears_report':
            'Balance âgée des impayés',
        'reports.arrears_report_summary':
            'Soldes locataires impayés par tranche d\'ancienneté.',
        'reports.arrears_report_description':
            'Factures impayées regroupées par tranches d\'ancienneté pour chaque locataire.',
        'reports.funds_report':
            'Fonds détenus',
        'reports.funds_report_summary':
            'Fonds des locataires et des propriétaires actuellement détenus.',
        'reports.funds_report_description':
            'Réserves de loyer, avances sur consommables, cautions et soldes propriétaires détenus.',
        'reports.as_of_heading':
            'Date de référence',
        'reports.as_of_description':
            'Date de situation facultative. Laissez vide pour aujourd\'hui.',
        'reports.as_of':
            'Au',
        'reports.occupied':
            'Occupées',
        'reports.vacant':
            'Vacantes',
        'reports.occupancy_rate':
            'Taux d\'occupation',
        'reports.occupancy_by_classification':
            'Commercial vs résidentiel',
        'reports.commercial':
            'Commercial',
        'reports.residential':
            'Résidentiel',
        'reports.commercial_units':
            'Unités commerciales',
        'reports.aging_current':
            'Courant',
        'reports.aging_31_60':
            '31-60 jours',
        'reports.aging_61_90':
            '61-90 jours',
        'reports.aging_over_90':
            'Plus de 90 jours',
        'reports.total_arrears':
            'Total des impayés',
        'reports.open_invoices':
            'Factures ouvertes',
        'reports.tenants_in_arrears':
            'Locataires en impayés',
        'reports.total_held':
            'Total détenu',
        'reports.owner_funds':
            'Fonds des propriétaires',
        'reports.balance':
            'Solde',
        'reports.account_count':
            ':count comptes',
        'reports.select_date':
            'Choisir une date',
        'reports.date_placeholder':
            'jj-mm-aaaa',
        'reports.reset_filters':
            'Réinitialiser les filtres',
        'reports.stale_results':
            'Les résultats ne sont plus à jour — générez à nouveau le rapport.',
        'reports.result_rows':
            ':count lignes',
        'properties.filter_units_label':
            'Filtrer les unités par type',
        'properties.occupied': 'Occupée',
        'properties.search_owner_placeholder': 'Rechercher un propriétaire par nom, téléphone ou e-mail…',
        'properties.no_matching_owners': 'Aucun propriétaire correspondant.',
        'properties.vacant': 'Vacante',
        'properties.filter_all_units':
            'Toutes les unités',
        'properties.commercial':
            'Commercial',
        'properties.residential':
            'Résidentiel',
        'properties.classification':
            'Classification',
        'properties.actions':
            'Actions',
        'properties.delete':
            'Supprimer',
        'properties.no_units_match_filter':
            'Aucune unité ne correspond au filtre actuel.',
        'properties.commercial_unit':
            'Unité commerciale',
        'properties.commercial_unit_help':
            'Marquer cette unité comme louée à usage professionnel ou commercial.',
        'properties.given_names':
            'Prénoms',
        'properties.surname':
            'Nom de famille',
        'properties.delete_property':
            'Supprimer la propriété',
        'properties.delete_property_description':
            'Supprimer définitivement cette propriété et ses enregistrements.',
        'properties.delete_property_warning':
            'Cette action est irréversible. Les propriétés avec des enregistrements liés ne peuvent pas être supprimées.',
        'properties.type_name_to_confirm':
            'Saisissez le nom de la propriété pour confirmer',
        'properties.deleting':
            'Suppression…',
        'properties.property_deleted':
            'Propriété supprimée.',
        'properties.unable_to_delete_property':
            'Impossible de supprimer la propriété.',
        'properties.delete_unit':
            'Supprimer l\'unité',
        'properties.delete_unit_description':
            'Supprimer définitivement cette unité de sa propriété.',
        'properties.delete_unit_warning':
            'Cette action est irréversible. Les unités avec des enregistrements liés ne peuvent pas être supprimées.',
        'properties.unit_deleted':
            'Unité supprimée.',
        'properties.unable_to_delete_unit':
            'Impossible de supprimer l\'unité.',
        'properties.property_created':
            'Propriété créée.',
        'properties.property_updated':
            'Propriété mise à jour.',
        'properties.unit_added':
            'Unité ajoutée.',
        'properties.unit_updated':
            'Unité mise à jour.',
        'owners.accounts':
            'Comptes',
        'owners.owner_accounts_title':
            'Comptes du propriétaire',
        'owners.owner_accounts_description':
            'Situation financière consolidée de ce propriétaire.',
        'owners.consolidated_account_note':
            'Chaque propriétaire dispose d\'un compte consolidé unique : tous ses immeubles, dépôts, dépenses et versements se règlent sur ce même solde.',
        'owners.recent_activity':
            'Activité récente',
        'owners.recent_activity_description':
            'Derniers mouvements du grand livre déjà chargés pour ce propriétaire.',
        'owners.date':
            'Date',
        'owners.type':
            'Type',
        'owners.expense_bill_title':
            'Enregistrer une facture de dépenses',
        'owners.expense_bill_description':
            'Facturez une ou plusieurs lignes de dépenses directement sur le compte consolidé de ce propriétaire.',
        'owners.property_expense_switch_hint':
            'Besoin d\'enregistrer une dépense sur un immeuble précis ?',
        'owners.bill_date':
            'Date de la facture',
        'owners.pay': 'Payer',
        'owners.cancel_payment': 'Annuler le paiement',
        'owners.expenses': 'Dépenses',
        'owners.expense_bills_description': "Les factures de dépenses restent impayées jusqu'à leur règlement depuis un compte du propriétaire via l'action Payer ; un paiement peut être annulé.",
        'owners.no_expense_bills': 'Aucune facture de dépenses enregistrée pour le moment.',
        'owners.expense_bill': 'Facture de dépenses',
        'owners.paid': 'Payé',
        'owners.outstanding': 'Restant dû',
        'owners.status': 'Statut',
        'owners.actions': 'Actions',
        'owners.bill_status_unpaid': 'Impayée',
        'owners.bill_status_partial': 'Partiellement payée',
        'owners.bill_status_paid': 'Payée',
        'owners.pay_bill_title': 'Payer la facture de dépenses',
        'owners.pay_bill_description': "Régler cette facture depuis un des comptes du propriétaire.",
        'owners.pay_source_account': 'Compte source',
        'owners.deposit_account': 'Compte Dépôts / Dépenses',
        'owners.payout_account': 'Compte Retraits',
        'owners.transaction_date': 'Date',
        'owners.pay_fields_required': 'Le compte source, le montant et la date sont obligatoires.',
        'owners.pay_exceeds_bill': 'Le paiement dépasse le montant restant dû de la facture de dépenses.',
        'owners.pay_exceeds_payout': 'Le paiement dépasse le solde disponible du compte Retraits.',
        'owners.pay_review_title': 'Vérifier le paiement',
        'owners.pay_review_description': "Confirmez le paiement exactement tel qu'il sera enregistré.",
        'owners.unable_to_pay_bill': "Impossible d'enregistrer le paiement de la facture.",
        'owners.cancel_payment_title': 'Annuler le paiement',
        'owners.cancel_payment_description': "Annule le paiement le plus récent de cette facture. L'annulation est enregistrée dans le journal et le journal d'activité.",
        'owners.cancellation_reason': "Motif d'annulation",
        'owners.cancellation_reason_required': "Un motif d'annulation est obligatoire.",
        'owners.unable_to_cancel_payment': "Impossible d'annuler le paiement de la facture.",
        'owners.unable_to_resend_bill': "Impossible de renvoyer la facture de dépenses.",
        'owners.expense_lines':
            'Lignes de dépense',
        'owners.add_line':
            'Ajouter une ligne',
        'owners.bill_total':
            'Total de la facture',
        'owners.line_description_placeholder':
            'p. ex. Réparation de plomberie',
        'owners.remove_line':
            'Supprimer la ligne',
        'owners.expense_bill_lines_required':
            'Ajoutez au moins une ligne de dépense.',
        'owners.expense_bill_line_invalid':
            'Chaque ligne doit comporter une description et un montant entier supérieur à zéro.',
        'owners.unable_to_record_bill':
            'Impossible d\'enregistrer la facture de dépenses.',
        'owners.expense_bill_recorded':
            'Facture de dépenses {number} enregistrée.',
        'owners.download_bill':
            'Télécharger la facture',
        'owners.email_to_owner':
            'Envoyer au propriétaire',
        'owners.sending_email':
            'Envoi…',
        'owners.email_sent':
            'La facture a été envoyée au propriétaire par e-mail.',
        'owners.email_failed':
            'Impossible d\'envoyer la facture par e-mail.',
        'shell.help':
            'Aide',
        'shell.update_log':
            'Journal des mises à jour',
        'notifications.loading':
            'Chargement des notifications…',
        'notifications.unable_load':
            'Impossible de charger les notifications.',
        'notifications.empty':
            'Vous êtes à jour.',
        'notifications.rent_overdue_title':
            'Loyers en retard',
        'notifications.rent_overdue_body':
            '{count} factures impayées — {amount} à recouvrer',
        'notifications.rent_overdue_body_one':
            '1 facture impayée — {amount} à recouvrer',
        'notifications.rent_due_soon_title':
            'Loyers bientôt dus',
        'notifications.rent_due_soon_body':
            '{count} factures dues sous 7 jours — {amount}',
        'notifications.rent_due_soon_body_one':
            '1 facture due sous 7 jours — {amount}',
        'notifications.expenses_unpaid_title':
            'Factures de dépenses impayées',
        'notifications.expenses_unpaid_body':
            '{count} factures de dépenses en attente de paiement — {amount}',
        'notifications.expenses_unpaid_body_one':
            '1 facture de dépenses en attente de paiement — {amount}',
        'notifications.owner_bills_unpaid_title':
            'Factures de dépenses propriétaires impayées',
        'notifications.owner_bills_unpaid_body':
            '{count} factures propriétaires en attente de paiement — {amount}',
        'notifications.owner_bills_unpaid_body_one':
            '1 facture propriétaire en attente de paiement — {amount}',
        'notifications.leases_expiring_title':
            'Baux arrivant à échéance',
        'notifications.leases_expiring_body':
            '{count} baux se terminent sous 90 jours',
        'notifications.leases_expiring_body_one':
            '1 bail se termine sous 90 jours',
        'notifications.increments_upcoming_title':
            'Augmentations de loyer à venir',
        'notifications.increments_upcoming_body':
            '{count} augmentations de loyer prennent effet sous 60 jours',
        'notifications.increments_upcoming_body_one':
            '1 augmentation de loyer prend effet sous 60 jours',
        'notifications.release_notes_title':
            'Nouveautés de Patrimoine v{release}',
        'notifications.release_notes_body':
            'Découvrez les changements de cette mise à jour.',
        'settings.about':
            'À propos',
        'settings.application_version':
            'Version de l\'application',
        'settings.view_update_log':
            'Voir le journal des mises à jour',
        'settings.backup_restore':
            'Sauvegarde et restauration des données',
        'settings.backup_restore_description':
            'Exportez le registre sous forme de fichiers de sauvegarde restaurables, ou restaurez une sauvegarde précédente.',
        'settings.backup_financial_note':
            'L\'historique financier (paiements, factures, journal et fonds) ne fait pas partie des sauvegardes. Il ne peut être ni exporté ni restauré ici.',
        'settings.export_heading':
            'Exportation',
        'settings.entity_parties':
            'Parties',
        'settings.entity_buildings':
            'Immeubles',
        'settings.entity_units':
            'Unités',
        'settings.entity_leases':
            'Baux',
        'settings.entity_full':
            'Sauvegarde complète (toutes les entités)',
        'settings.export_full':
            'Sauvegarde complète',
        'settings.exporting':
            'Exportation…',
        'settings.unable_export':
            'Impossible d\'exporter le registre.',
        'settings.import_heading':
            'Importation / restauration',
        'settings.choose_file':
            'Choisir un fichier…',
        'settings.no_file_selected':
            'Aucun fichier sélectionné',
        'settings.import_file':
            'Fichier de sauvegarde',
        'settings.import_entity':
            'Jeu de données',
        'settings.dry_run':
            'Simulation (valider sans enregistrer)',
        'settings.dry_run_help':
            'La simulation lit le fichier et présente ce qui changerait sans toucher aux données. Appliquez ensuite la restauration depuis le résultat.',
        'settings.run_import':
            'Lancer l\'importation',
        'settings.importing':
            'Importation…',
        'settings.import_select_file':
            'Choisissez d\'abord un fichier de sauvegarde.',
        'settings.import_result_heading':
            'Résultat de l\'importation',
        'settings.import_dry_run_notice':
            'Simulation — aucune donnée n\'a été modifiée.',
        'settings.import_created':
            'Créés',
        'settings.import_updated':
            'Mis à jour',
        'settings.import_unchanged':
            'Inchangés',
        'settings.import_skipped':
            'Ignorés',
        'settings.import_skipped_row':
            'Ligne {row} : {reason}',
        'settings.unable_import':
            'Impossible d\'importer la sauvegarde.',
        'users.given_names':
            'Prénoms',
        'users.surname':
            'Nom de famille',
        'activity_log.export_xlsx':
            'XLSX',
        'leases.building':
            'Immeuble',
        'leases.all_buildings':
            'Tous les immeubles',
        'leases.all_frequencies':
            'Toutes les fréquences',
        'leases.expiring_before':
            'Expire avant le',
        'leases.rent_increments':
            'Augmentations du loyer',
        'leases.rent_increments_description':
            'Consultez les augmentations de loyer programmées, appliquées et annulées pour ce bail.',
        'leases.rent_increments_loading':
            'Chargement des augmentations du loyer…',
        'leases.no_rent_increments':
            'Aucune augmentation du loyer enregistrée pour ce bail.',
        'leases.increments_unable_load':
            'Impossible de charger les augmentations du loyer.',
        'leases.schedule_increment':
            'Programmer une augmentation',
        'leases.schedule_increment_description':
            'Le nouveau loyer prend effet automatiquement à la date d\'effet.',
        'leases.effective_date':
            'Date d\'effet',
        'leases.increment_status_scheduled':
            'Programmée',
        'leases.increment_status_applied':
            'Appliquée',
        'leases.increment_status_cancelled':
            'Annulée',
        'leases.notification_sent':
            'Notification envoyée',
        'leases.applied_on':
            'Appliquée le',
        'leases.cancelled_on':
            'Annulée le',
        'leases.cancel_increment':
            'Annuler l\'augmentation',
        'leases.confirm_cancel_increment':
            'Annuler cette augmentation du loyer programmée ?',
        'leases.increment_invalid_date':
            'Saisissez une date d\'effet valide.',
        'leases.increment_schedule_failed':
            'Impossible de programmer l\'augmentation du loyer.',
        'leases.increment_cancel_failed':
            'Impossible d\'annuler l\'augmentation du loyer.',
        'tenants.deposit_title':
            'Enregistrer un dépôt du locataire',
        'tenants.withdrawal_title':
            'Enregistrer un retrait du locataire',
        'tenants.expense_title':
            'Enregistrer une dépense du locataire',
        'tenants.adjustment_title':
            'Enregistrer un ajustement de solde',
        'tenants.accounts':
            'Comptes',
        'tenants.accounts_description':
            'Tous les comptes de fonds détenus pour ce locataire sur l\'ensemble de ses baux.',
        'tenants.account_status.active':
            'Actif',
        'tenants.account_status.closed':
            'Clôturé',
        'tenants.all_leases':
            'Tous les baux',
        'tenants.any_account_help':
            'Les comptes de tous les baux de ce locataire sont proposés ; filtrez éventuellement par bail.',
        'tenants.loading_accounts':
            'Chargement des comptes…',
        'tenants.no_accounts':
            'Aucun compte de fonds n\'existe pour ce locataire.',
        'tenants.total_held_funds':
            'Total des fonds détenus',
        'tenants.transfer':
            'Transfert',
        'tenants.transfer_description':
            'Déplacer des fonds détenus entre deux comptes de ce locataire. Un motif est obligatoire.',
        'tenants.source_account':
            'Compte source',
        'tenants.destination_account':
            'Compte de destination',
        'tenants.select_source_account':
            'Sélectionnez le compte source…',
        'tenants.select_destination_account':
            'Sélectionnez le compte de destination…',
        'tenants.source_balance':
            'Solde du compte source',
        'tenants.destination_balance':
            'Solde du compte de destination',
        'tenants.resulting_source_balance':
            'Solde source résultant',
        'tenants.resulting_destination_balance':
            'Solde destination résultant',
        'tenants.transfer_reason_placeholder':
            'Expliquez pourquoi ces fonds sont déplacés…',
        'tenants.transfer_required_fields':
            'Renseignez tous les champs obligatoires du transfert, y compris le motif.',
        'tenants.transfer_same_account':
            'Les comptes source et destination doivent être différents.',
        'tenants.transfer_exceeds_balance':
            'Le montant ne peut pas dépasser le solde du compte source.',
        'tenants.no_transferable_accounts':
            'Au moins deux comptes de fonds actifs sont nécessaires pour un transfert.',
        'tenants.transfer_recorded':
            'Transfert enregistré avec succès.',
        'tenants.transfers':
            'Transferts',
        'tenants.transfers_description':
            'Transferts entre fonds avec leurs récépissés officiels.',
        'tenants.no_transfers':
            'Aucun transfert enregistré pour le moment.',
        'tenants.expense': 'Dépense',
        'tenants.expense_title': 'Enregistrer une dépense locataire',
        'tenants.expense_description_text': "Réglez une dépense liée à un bail à partir de l'un des comptes de fonds de ce locataire. Le compte ne peut jamais devenir négatif.",
        'tenants.expense_description_label': 'Description de la dépense',
        'tenants.expense_recorded': 'Dépense locataire enregistrée avec succès.',
        'tenants.pay': 'Payer',
        'tenants.cancel_payment': 'Annuler le paiement',
        'tenants.download_invoice': 'Télécharger la facture',
        'tenants.expense_invoices_description': 'Dépenses facturées à ce locataire sous forme de factures EXP. Payer les règle depuis un compte de fonds ; un paiement peut être annulé.',
        'tenants.no_expense_invoices': 'Aucune facture de dépenses enregistrée pour le moment.',
        'tenants.expense_invoice_help': "L'enregistrement crée une facture EXP impayée. L'argent ne quitte un compte de fonds que lorsque la facture est payée via l'action Payer.",
        'tenants.expense_invoice_created': 'Facture de dépenses créée avec succès.',
        'tenants.pay_invoice_title': 'Payer la facture',
        'tenants.pay_invoice_description': 'Régler cette facture depuis un des comptes de fonds du bail.',
        'tenants.pay_fields_required': 'Le compte, le montant et la date sont obligatoires.',
        'tenants.pay_exceeds_balance': 'Le paiement dépasse le solde disponible du compte.',
        'tenants.pay_exceeds_invoice': 'Le paiement dépasse le montant restant dû de la facture.',
        'tenants.pay_review_title': 'Vérifier le paiement',
        'tenants.pay_review_description': "Confirmez le paiement exactement tel qu'il sera enregistré.",
        'tenants.payment_recorded': 'Paiement de la facture enregistré avec succès.',
        'tenants.cancel_payment_title': 'Annuler le paiement',
        'tenants.cancel_payment_description': "Annule le paiement le plus récent de cette facture. L'annulation est enregistrée dans le journal et le journal d'activité.",
        'tenants.cancellation_reason': "Motif d'annulation",
        'tenants.cancellation_reason_required': "Un motif d'annulation est obligatoire.",
        'tenants.payment_cancelled': 'Paiement annulé et reversé avec succès.',
        'tenants.expenses': 'Dépenses',
        'tenants.expenses_description': "Dépenses liées aux baux réglées à partir des comptes de fonds de ce locataire, avec leurs bons officiels.",
        'tenants.no_expenses': 'Aucune dépense enregistrée pour le moment.',
        'tenants.review': 'Vérifier',
        'danger.title': 'Confirmer la suppression irréversible',
        'danger.entity_prefix': 'Vous êtes sur le point de supprimer définitivement :',
        'danger.entity_generic': 'Vous êtes sur le point de supprimer définitivement cet enregistrement.',
        'danger.acknowledgement': 'Je comprends que cette action est irréversible, que l’enregistrement et son historique ne pourront pas être récupérés, et j’en accepte le risque.',
        'danger.password_label': 'Saisissez votre mot de passe pour confirmer',
        'danger.cancel': 'Annuler',
        'danger.confirm': 'Supprimer définitivement',
        'danger.verification_failed': 'La vérification du mot de passe a échoué.',
        'tenants.back': 'Retour',
        'tenants.confirm': 'Confirmer',
        'tenants.expense_lines': 'Lignes de dépense',
        'tenants.add_line': 'Ajouter une ligne',
        'tenants.remove_line': 'Supprimer la ligne',
        'tenants.expense_total': 'Total',
        'tenants.expense_line_description_placeholder': 'À quoi correspond cette dépense ?',
        'tenants.expense_fields_required': 'Sélectionnez d’abord un compte, une date et un mode de paiement.',
        'tenants.expense_line_invalid': 'Chaque ligne nécessite une description et un montant supérieur à zéro.',
        'tenants.expense_exceeds_balance': 'Le total dépasse le solde disponible du fonds.',
        'tenants.expense_review_title': 'Vérifier cette dépense',
        'tenants.expense_review_description': 'Rien n’est enregistré avant votre confirmation. Le locataire reçoit le bon détaillé par e-mail.',
        'tenants.source_fund': 'Source',
        'tenants.description': 'Description',
        'tenants.category.expense': 'Dépense',
        'tenants.voucher':
            'Récépissé',
        'tenants.from_fund':
            'De',
        'tenants.to_fund':
            'Vers',
        'tenants.unable_to_open_voucher':
            "Impossible d'ouvrir le récépissé de transfert.",
        'tenants.unable_to_resend_voucher':
            'Impossible de renvoyer le récépissé de transfert.',
        'tenants.download_voucher':
            'Télécharger le récépissé',
        'tenants.download_receipt':
            'Télécharger le reçu',
        'tenants.unable_to_open_document':
            'Impossible d\'ouvrir le document.',
        'tenants.adjustment_no_change':
            'Le solde corrigé est déjà identique au solde actuel.',
        'tenants.adjustment_negative_balance':
            'Les soldes de fonds locataire ne peuvent pas être ajustés en dessous de zéro.',
        /* ---- end V1.0.7 additions ---- */
        'product.property_management':
            'Gestion immobilière',

        'login.title':
            'Connexion — Patrimoine 365',

        'login.hero_kicker':
            'La gestion locative, sans le drame',

        'login.hero_title':
            'Encaissez vos loyers, l\'esprit tranquille.',

        'login.hero_description':
            'Baux, quittances, propriétaires et locataires en ordre parfait — avec un journal en partie double qui veille au grain en coulisses. Vous gérez vos biens ; les écritures, c\'est notre affaire.',

        'login.hero_image_label':
            'Aperçu du tableau de bord Patrimoine 365',

        'login.product_name':
            '© 2026 Patrimoine 365. Tous droits réservés.',

        'login.switch_to_dark':
            'Passer en mode sombre',

        'login.switch_to_light':
            'Passer en mode clair',

        'login.switch_language':
            'Changer de langue',

        'login.welcome':
            'Bienvenue',

        'login.description':
            'Connectez-vous pour accéder à votre espace de gestion immobilière.',

        'login.email':
            'Adresse e-mail',

        'login.email_placeholder':
            'nom@exemple.com',

        'login.password':
            'Mot de passe',

        'login.password_placeholder':
            'Saisissez votre mot de passe',

        'login.sign_in':
            'Se connecter',

        'login.signing_in':
            'Connexion…',

        'login.unable_to_sign_in':
            'Impossible de se connecter.',

        'login.secure_access':
            'Accès sécurisé à Patrimoine 365.',

        'password.forgot_link':
            'Mot de passe oublié ?',
        'password.forgot_title':
            'Mot de passe oublié — Patrimoine',
        'password.forgot_heading':
            'Mot de passe oublié ?',
        'password.forgot_description':
            'Saisissez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.',
        'password.send_reset':
            'Envoyer le lien',
        'password.sending':
            'Envoi…',
        'password.reset_requested':
            'Si le compte est éligible, un lien de réinitialisation a été envoyé.',
        'password.back_to_login':
            'Retour à la connexion',
        'password.reset_title':
            'Réinitialiser le mot de passe — Patrimoine',
        'password.reset_heading':
            'Réinitialisez votre mot de passe',
        'password.reset_description':
            'Choisissez un nouveau mot de passe pour votre compte Patrimoine.',
        'password.new_password':
            'Nouveau mot de passe',
        'password.confirm_password':
            'Confirmer le mot de passe',
        'password.reset_action':
            'Réinitialiser le mot de passe',
        'password.resetting':
            'Réinitialisation…',
        'password.reset_complete':
            'Votre mot de passe a été réinitialisé avec succès.',
        'password.invitation_title':
            'Définir le mot de passe — Patrimoine',
        'password.invitation_heading':
            'Définissez votre mot de passe',
        'password.invitation_description':
            'Créez un mot de passe pour activer votre compte Patrimoine.',
        'password.set_password':
            'Définir le mot de passe',
        'password.setting_password':
            'Définition du mot de passe…',
        'password.invitation_complete':
            'Votre mot de passe a été défini avec succès.',
        'password.current_password':
            'Mot de passe actuel',
        'password.profile_updated':
            'Votre profil a été mis à jour.',
        'password.profile_current_required':
            'Saisissez votre mot de passe actuel pour définir un nouveau mot de passe.',

'password.change_action':
            'Modifier le mot de passe',
        'password.change_heading':
            'Modifier le mot de passe',
        'password.change_description':
            'Modifiez le mot de passe de votre compte.',
        'password.changing':
            'Modification…',
        'password.confirmation_mismatch':
            'La confirmation du mot de passe ne correspond pas.',
        'password.request_failed':
            'Cette demande de mot de passe n’a pas abouti.',
        'login.missing_api_token':
            'La connexion a réussi, mais aucun jeton API n’a été retourné.',

        'login.no_account':
            'Nouveau sur Patrimoine 365 ?',

        'navigation.license':
            'Licence',

        'license.title':
            'Licence et forfait — Patrimoine 365',

        'license.eyebrow':
            'Abonnement',

        'license.heading':
            'Licence et forfait',

        'license.description':
            'Le forfait actuel de votre organisation, son utilisation par rapport aux limites, et le contenu de chaque forfait.',

        'license.current_plan':
            'Forfait actuel',

        'license.upgrade_hint':
            'Pour souscrire, prolonger ou changer de forfait, contactez',

        'license.footnotes':
            'Toute nouvelle organisation commence par un essai Professionnel de 30 jours — sans carte bancaire. Prix en USD ; la facturation annuelle offre deux mois gratuits. Au-delà de 1 000 baux actifs, parlons-en. L\'intégrité financière et les e-mails de documents transactionnels sont identiques sur tous les forfaits, et les e-mails de connexion ne sont jamais bloqués.',

        'license.unable':
            'Impossible de charger les informations de licence.',

        'license.unlimited':
            'Illimité',

        'license.trial_until':
            'Essai Professionnel jusqu\'au',

        'license.plan_free':
            'Gratuit',

        'license.plan_standard':
            'Standard',

        'license.plan_professional':
            'Professionnel',

        'license.usage_users':
            'Utilisateurs internes',

        'license.usage_active_leases':
            'Baux actifs',

        'license.usage_parties':
            'Tiers',

        'license.usage_emails':
            'E-mails ce mois-ci',

        'login.create_organisation':
            'Cr\u00e9ez votre organisation',

        'login.mfa_heading':
            'Consultez votre e-mail',

        'login.mfa_description':
            'Nous avons envoy\u00e9 un code \u00e0 6 chiffres \u00e0',

        'login.mfa_code_label':
            'Code de v\u00e9rification',

        'login.mfa_verify':
            'V\u00e9rifier et se connecter',

        'login.mfa_verifying':
            'V\u00e9rification\u2026',

        'login.mfa_back':
            'Retour \u00e0 la connexion',

        'login.mfa_resend':
            'Renvoyer le code',

        'signup.title':
            'Cr\u00e9ez votre organisation \u2014 Patrimoine 365',

        'signup.heading':
            'Cr\u00e9ez votre organisation',

        'signup.description':
            'Commencez votre essai Professionnel de 30 jours. Aucune carte bancaire requise.',

        'signup.organisation_name':
            'Nom de l\u2019organisation',

        'signup.organisation_name_placeholder':
            'Immobilier Acme SARL',

        'signup.given_names':
            'Pr\u00e9noms',

        'signup.surname':
            'Nom',

        'signup.email':
            'Adresse e-mail',

        'signup.phone':
            'T\u00e9l\u00e9phone (facultatif)',

        'signup.password':
            'Mot de passe',

        'signup.password_help':
            'Au moins 10 caract\u00e8res, avec des lettres et des chiffres.',

        'signup.password_confirmation':
            'Confirmez le mot de passe',

        'signup.accept_prefix':
            'J\u2019accepte les',

        'signup.terms_link':
            'Conditions G\u00e9n\u00e9rales d\u2019Utilisation',

        'signup.accept_and':
            'et la',

        'signup.privacy_link':
            'Politique de Confidentialit\u00e9',

        'signup.submit':
            'Cr\u00e9er l\u2019organisation',

        'signup.submitting':
            'Cr\u00e9ation\u2026',

        'signup.unable':
            'Impossible de cr\u00e9er votre organisation.',

        'signup.have_account':
            'Vous avez d\u00e9j\u00e0 un compte ?',

        'signup.sign_in_link':
            'Se connecter',

        'signup.done_heading':
            'Consultez votre e-mail',

        'signup.done_description':
            'Nous avons envoy\u00e9 un lien de v\u00e9rification \u00e0',

        'signup.done_back_to_login':
            'Retour \u00e0 la connexion',

        'signup.resend_hint':
            'Rien re\u00e7u apr\u00e8s une minute ou deux ? V\u00e9rifiez d\'abord vos courriers ind\u00e9sirables.',

        'signup.resend_button':
            'Renvoyer l\'e-mail de v\u00e9rification',

        'login.resend_verification':
            'Renvoyer l\'e-mail de v\u00e9rification',

        'verify_email.title':
            'V\u00e9rifiez votre e-mail \u2014 Patrimoine 365',

        'verify_email.pending_heading':
            'V\u00e9rification\u2026',

        'verify_email.pending_description':
            'Un instant, nous confirmons votre adresse e-mail.',

        'verify_email.success_heading':
            'E-mail v\u00e9rifi\u00e9',

        'verify_email.success_description':
            'Votre adresse e-mail a \u00e9t\u00e9 confirm\u00e9e. Vous pouvez maintenant vous connecter \u00e0 votre organisation.',

        'verify_email.continue':
            'Continuer vers la connexion',

        'verify_email.failed_heading':
            'Lien invalide ou expir\u00e9',

        'verify_email.failed_description':
            'Ce lien de v\u00e9rification n\u2019est plus valide. Saisissez votre adresse e-mail et nous vous en enverrons un nouveau.',

        'verify_email.resend':
            'Envoyer un nouveau lien',

        'verify_email.resent':
            'Si cette adresse n\u00e9cessite une v\u00e9rification, un nouveau lien a \u00e9t\u00e9 envoy\u00e9.',

        'verify_email.resend_failed':
            'Impossible d\u2019envoyer un nouveau lien pour le moment.',

        'verify_email.back_to_login':
            'Retour \u00e0 la connexion',

        'navigation.workspace':
            'Espace de travail',

        'navigation.dashboard':
            'Tableau de bord',

        'navigation.properties':
            'Propriétés',

        'navigation.parties':
            'Parties',

        'navigation.leases':
            'Baux',

        'navigation.payments':
            'Paiements',

        'navigation.finance':
            'Finance',

        'navigation.manage':
            'Gestion',

        'shell.refresh':
            'Actualiser',

        'shell.notifications':
            'Notifications',

        'shell.whats_new':
            'Nouveautés',

        'shell.appearance':
            'Apparence',

        'shell.theme_light':
            'Clair',

        'shell.theme_dark':
            'Sombre',

        'shell.theme_system':
            'Système',

        'release.v104_heading':
            'Vous utilisez maintenant Patrimoine v1.0.4',

        'release.v104_ui':
            'Interface mise à jour pour une utilisation plus claire.',

        'release.v104_fixes':
            'Corrections d’ergonomie et de localisation.',

        'navigation.tenants':
            'Locataires',

        'navigation.owners':
            'Propriétaires',

        'navigation.accounting':
            'Comptabilité',

        'accounting.title':
            'Comptabilité',

        'accounting.subtitle':
            'Ce que votre organisation a gagné en frais de gestion, et la TVA que vous avez facturée dessus.',

        'accounting.fee_income':
            'Produits d’honoraires',

        'accounting.fee_income_hint':
            'Frais de gestion facturés aux propriétaires.',

        'accounting.vat_charged':
            'TVA facturée',

        'accounting.vat_charged_hint':
            'Collectée sur vos frais et due au Trésor.',

        'accounting.charged_to_owners':
            'Total facturé aux propriétaires',

        'accounting.charged_to_owners_hint':
            'Les frais et la TVA correspondante.',

        'accounting.from':
            'Du',

        'accounting.to':
            'Au',

        'accounting.apply':
            'Appliquer',

        'accounting.reset':
            'Réinitialiser',

        'accounting.transactions':
            'Facturations',

        'accounting.date':
            'Date',

        'accounting.type':
            'Type',

        'accounting.owner':
            'Propriétaire',

        'accounting.property':
            'Bien',

        'accounting.reference':
            'Référence',

        'accounting.amount':
            'Montant',

        'accounting.management_fee':
            'Frais de gestion',

        'accounting.management_fee_vat':
            'TVA sur frais',

        'accounting.empty':
            'Aucun frais de gestion facturé sur cette période.',

        'accounting.capped':
            'Les 200 facturations les plus récentes sont affichées. Le Journal financier conserve l’enregistrement complet.',

        'accounting.vat_note':
            'La TVA affichée ici est collectée pour le compte du Trésor. Ce n’est pas un produit.',

        'navigation.reports':
            'Rapports',

        'navigation.settings':
            'Paramètres',

        'navigation.sign_out':
            'Se déconnecter',
        'navigation.sign_out_description':
            'Quitter Patrimoine',

        'user.property_manager':
            'Gestionnaire immobilier',

        'dashboard.title':
            'Tableau de bord — Patrimoine',

        'dashboard.overview':
            'Vue d’ensemble',

        'dashboard.heading':
            'Tableau de bord',

        'dashboard.description':
            'Situation actuelle du portefeuille et des finances.',

        'dashboard.buildings':
            'Immeubles',

        'dashboard.total_units':
            'Unités totales',

        'dashboard.rent_due':
            'Loyers dus',

        'dashboard.rent_overdue':
            'Loyers en retard',

        'dashboard.collected_this_month':
            'Encaissé ce mois-ci',

        'dashboard.owner_funds_held':
            'Fonds des propriétaires détenus',

        'dashboard.overdue_rent':
            'Loyers en retard',

        'dashboard.overdue_description':
            'Obligations impayées nécessitant une attention.',

        'dashboard.upcoming_rent':
            'Loyers à venir',

        'dashboard.upcoming_description':
            'Obligations de loyer arrivant bientôt à échéance.',

        'dashboard.loading':
            'Chargement…',

        'dashboard.no_records':
            'Aucun élément à afficher.',

        'dashboard.tenant':
            'Locataire',

        'dashboard.due':
            'Échéance',

        'dashboard.unable_to_load':
            'Impossible de charger les informations du tableau de bord.',

        'language.en':
            'Anglais',

        'language.fr':
            'Français',

        'currency.GHS':
            'GHS',

        'currency.FCFA':
            'FCFA',

        'navigation.activity_log':
            'Journal d’activité',

        'navigation.financial_journal':
            'Journal financier',

        'navigation.users':
            'Utilisateurs',

        'navigation.platform_console':
            'Administration',

        'profile.photo':
            "Photographie",
        'profile.photo_hint':
            "Affichée en haut de l'écran et à côté de votre nom partout où votre compte apparaît.",
        'profile.photo_choose':
            "Choisir une image",
        'profile.photo_reframe':
            "Recadrer",
        'profile.photo_remove':
            "Retirer",
        'profile.photo_zoom':
            "Zoom",
        'profile.photo_save':
            "Utiliser ce cadrage",
        'profile.photo_cancel':
            "Annuler",
        'profile.photo_drag':
            "Faites glisser pour déplacer, molette ou curseur pour zoomer.",
        'profile.photo_unreadable':
            "Cette image n'a pas pu être ouverte. Si elle vient d'un iPhone, enregistrez-la d'abord en JPEG.",

        'phone.country':
            'Pays',
        'phone.select':
            'Pays',
        'phone.search':
            'Pays ou indicatif',
        'phone.none':
            'Aucun pays ne correspond',

        'roles.administrator':
            'Administrateur',
        'roles.property_manager':
            'Gestionnaire immobilier',
        'roles.viewer':
            'Consultation',

        'financial_journal.transaction_types.rent_invoice':
            'Facture de loyer',

        'financial_journal.transaction_types.rent_receipt':
            'Encaissement de loyer',

        'financial_journal.transaction_types.owner_deposit':
            'Dépôt propriétaire',

        'financial_journal.transaction_types.owner_payout':
            'Versement propriétaire',

        'financial_journal.transaction_types.owner_expense':
            'Charge propriétaire',

        'financial_journal.transaction_types.owner_rent_entitlement':
            'Droit du propriétaire',

        'financial_journal.transaction_types.owner_adjustment':
            'Ajustement propriétaire',

        'financial_journal.transaction_types.management_fee':
            'Frais de gestion',

        'financial_journal.transaction_types.advance_consumption':
            'Consommation d’avance',

        'financial_journal.transaction_types.rent_reserve_consumption':
            'Consommation de réserve',

        'financial_journal.transaction_types.rent_reserve_funding':
            'Alimentation de réserve',

        'financial_journal.transaction_types.consumable_advance_funding':
            'Alimentation d’avance',

        'financial_journal.transaction_types.security_deposit_funding':
            'Alimentation de caution',

        'financial_journal.transaction_types.security_deposit_settlement':
            'Règlement de caution',

        'financial_journal.transaction_types.security_deposit_refund':
            'Remboursement de caution',

        'financial_journal.transaction_types.security_deposit_debt':
            'Dette sur caution',

        'financial_journal.transaction_types.tenant_fund_funding':
            'Alimentation de fonds locataire',

        'financial_journal.transaction_types.tenant_fund_expense':
            'Dépense sur fonds locataire',

        'financial_journal.transaction_types.tenant_fund_transfer':
            'Transfert de fonds locataire',

        'financial_journal.transaction_types.tenant_expense_settlement':
            'Règlement de dépense locataire',

        'financial_journal.transaction_types.journal_reversal':
            'Contre-passation',

        'financial_journal.transaction_types.v1_0_5_opening_balance':
            'Solde d’ouverture',

        'financial_journal.title':
            'Journal financier — Patrimoine',

        'financial_journal.administration':
            'Administration',

        'financial_journal.heading':
            'Journal financier',

        'financial_journal.description':
            'Consultez le registre comptable permanent en partie double de Patrimoine.',

        'financial_journal.loading':
            'Chargement du journal financier...',

        'financial_journal.search':
            'Rechercher',

        'financial_journal.search_placeholder':
            'Numéro du journal, description, acteur, compte ou source...',

        'financial_journal.from':
            'Du',

        'financial_journal.to':
            'Au',

        'financial_journal.entry_kind':
            'Type d’écriture',

        'financial_journal.all_entry_kinds':
            'Tous les types d’écriture',

        'financial_journal.kind_financial':
            'Financière',

        'financial_journal.kind_reversal':
            'Contre-passation',

        'financial_journal.kind_informational':
            'Informationnelle',

        'financial_journal.transaction_type':
            'Type de transaction',

        'financial_journal.all_transaction_types':
            'Tous les types de transaction',

        'financial_journal.account':
            'Compte',

        'financial_journal.all_accounts':
            'Tous les comptes',

        'financial_journal.clear_filters':
            'Effacer les filtres',

        'financial_journal.export_pdf':
            'PDF',

        'financial_journal.export_csv':
            'CSV',

        'financial_journal.export_xlsx':
            'XLSX',

        'financial_journal.exporting':
            'Exportation...',

        'financial_journal.unable_export':
            'Impossible d’exporter le journal financier.',

        'financial_journal.unable_load':
            'Impossible de charger le journal financier.',

        'financial_journal.none_found':
            'Aucune écriture trouvée',

        'financial_journal.none_found_description':
            'Aucune écriture ne correspond aux filtres sélectionnés.',

        'financial_journal.view_details':
            'Voir les détails',

        'financial_journal.page_of':
            'Page :current sur :last',

        'financial_journal.previous':
            'Précédent',

        'financial_journal.next':
            'Suivant',

        'financial_journal.close':
            'Fermer',

        'financial_journal.detail_heading':
            'Écriture du journal',

        'financial_journal.detail_description':
            'Détails immuables de la transaction comptable.',

        'financial_journal.loading_detail':
            'Chargement de l’écriture...',

        'financial_journal.unable_load_detail':
            'Impossible de charger les détails de l’écriture.',

        'financial_journal.debit':
            'Débit',

        'financial_journal.credit':
            'Crédit',

        'financial_journal.actor':
            'Acteur',

        'financial_journal.source':
            'Source',

        'financial_journal.balance_status':
            'État d’équilibre',

        'financial_journal.balanced':
            'Équilibrée',

        'financial_journal.unbalanced':
            'Non équilibrée',

        'financial_journal.description_label':
            'Description',

        'financial_journal.accounting_lines':
            'Lignes comptables',

        'financial_journal.line_count':
            ':count ligne(s)',

        'financial_journal.no_lines':
            'Cette écriture informationnelle ne contient aucune ligne comptable.',

        'financial_journal.total_debit':
            'Total débit',

        'financial_journal.total_credit':
            'Total crédit',

        'financial_journal.reversal_context':
            'Informations de contre-passation',

        'financial_journal.reversal_of':
            'Contre-passation de',

        'financial_journal.reversed_by':
            'Contre-passée par',

        'financial_journal.reversal_reason':
            'Motif de contre-passation',

        'financial_journal.reversed':
            'Contre-passée',

        'financial_journal.not_available':
            'Non disponible',


        'activity_log.title':
            'Journal d’activité — Patrimoine',
        'activity_log.administration':
            'Administration',
        'activity_log.heading':
            'Journal d’activité',
        'activity_log.description':
            'Consultez les actions humaines significatives enregistrées par Patrimoine.',
        'activity_log.search':
            'Rechercher',
        'activity_log.search_placeholder':
            'Rechercher un utilisateur, une action, un enregistrement, une adresse IP, un navigateur, un appareil ou un contexte historique...',
        'activity_log.from':
            'Du',
        'activity_log.to':
            'Au',
        'activity_log.user':
            'Utilisateur',
        'activity_log.all_users':
            'Tous les utilisateurs',
        'activity_log.role':
            'Rôle',
        'activity_log.all_roles':
            'Tous les rôles',
        'activity_log.action':
            'Action',
        'activity_log.action_placeholder':
            'ex. payment.recorded',
        'activity_log.entity_type':
            'Type d’enregistrement',
        'activity_log.entity_type_placeholder':
            'ex. payment',
        'activity_log.clear_filters':
            'Effacer les filtres',
        'activity_log.export_pdf':
            'PDF',
        'activity_log.export_csv':
            'CSV',
        'activity_log.exporting':
            'Export en cours...',
        'activity_log.unable_export':
            'Impossible d’exporter le journal d’activité.',
        'activity_log.loading':
            'Chargement du journal d’activité...',
        'activity_log.none_found':
            'Aucune activité trouvée',
        'activity_log.none_found_description':
            'Aucune activité enregistrée ne correspond aux filtres actuels.',
        'activity_log.view_details':
            'Voir les détails',
        'activity_log.page_of':
            'Page :current sur :last',
        'activity_log.previous':
            'Précédent',
        'activity_log.next':
            'Suivant',
        'activity_log.detail_heading':
            'Détails de l’activité',
        'activity_log.detail_description':
            'Informations historiques immuables enregistrées pour cette action.',
        'activity_log.loading_detail':
            'Chargement des détails...',
        'activity_log.unable_load':
            'Impossible de charger le journal d’activité.',
        'activity_log.unable_load_detail':
            'Impossible de charger les détails de l’activité.',
        'activity_log.close':
            'Fermer',
        'activity_log.event':
            'Événement',
        'activity_log.timestamp':
            'Horodatage',
        'activity_log.actor':
            'Utilisateur',
        'activity_log.email':
            'E-mail',
        'activity_log.ip_address':
            'Adresse IP',
        'activity_log.browser':
            'Navigateur',
        'activity_log.platform':
            'Plateforme',
        'activity_log.device':
            'Appareil',
        'activity_log.user_agent':
            'Agent utilisateur',
        'activity_log.entity':
            'Enregistrement',
        'activity_log.before_values':
            'Avant',
        'activity_log.after_values':
            'Après',
        'activity_log.snapshot':
            'Instantané',
        'activity_log.metadata':
            'Contexte complémentaire',
        'activity_log.not_available':
            'Non disponible',
        'activity_log.unknown_actor':
            'Utilisateur inconnu',

        'users.title':
            'Utilisateurs — Patrimoine',
        'users.administration':
            'Administration',
        'users.heading':
            'Gestion des utilisateurs',
        'users.description':
            'Gérez les utilisateurs de l’application, leurs rôles et l’accès à leur compte.',
        'users.add_user':
            'Ajouter un utilisateur',
        'users.edit_user':
            'Modifier l’utilisateur',
        'users.create_user':
            'Créer l’utilisateur',
        'users.create_description':
            'Créez un utilisateur de l’application et envoyez-lui une invitation sécurisée pour définir son mot de passe.',
        'users.edit_description':
            'Modifiez l’identité, le rôle ou l’état du compte de cet utilisateur.',
        'users.name':
            'Nom',
        'users.email':
            'E-mail',
        'users.phone':
            'Téléphone',
        'users.role':
            'Rôle',
        'users.status':
            'État',
        'users.search':
            'Rechercher',
        'users.search_placeholder':
            'Rechercher par nom, e-mail ou téléphone...',
        'users.all_roles':
            'Tous les rôles',
        'users.all_statuses':
            'Tous les états',
        'users.active':
            'Actif',
        'users.inactive':
            'Inactif',
        'users.active_account':
            'Compte actif',
        'users.active_account_help':
            'Les utilisateurs inactifs ne peuvent pas se connecter.',
        'users.invitation_pending':
            'Invitation en attente',
        'users.you':
            'Vous',
        'users.loading':
            'Chargement des utilisateurs...',
        'users.none_found':
            'Aucun utilisateur trouvé',
        'users.none_found_description':
            'Créez un utilisateur ou modifiez les filtres actuels.',
        'users.edit':
            'Modifier',
        'users.delete':
            'Supprimer',
        'users.resend_invitation':
            'Renvoyer l’invitation',
        'users.send_password_reset':
            'Envoyer la réinitialisation',
        'users.cancel':
            'Annuler',

        'actions.save':
            'Enregistrer',
        'actions.cancel':
            'Annuler',
        'actions.close':
            'Fermer',
        'users.close':
            'Fermer',
        'users.save_changes':
            'Enregistrer les modifications',
        'users.saving':
            'Enregistrement...',
        'users.creating':
            'Création...',
        'users.created':
            'Utilisateur créé et invitation envoyée avec succès.',
        'users.updated':
            'Utilisateur mis à jour avec succès.',
        'users.deleted':
            'Utilisateur supprimé avec succès.',
        'users.invitation_resent':
            'Une nouvelle invitation a été envoyée. Les anciens liens d’invitation ne sont plus valides.',
        'users.reset_sent':
            'La procédure de réinitialisation du mot de passe a été lancée.',
        'users.resend_confirmation':
            'Envoyer une nouvelle invitation à :name ? Les anciens liens d’invitation cesseront de fonctionner.',
        'users.reset_confirmation':
            'Envoyer un lien de réinitialisation du mot de passe à :name ?',
        'users.delete_confirmation':
            'Supprimer :name ? Cette action est irréversible.',
        'users.unable_load':
            'Impossible de charger les utilisateurs.',
        'users.unable_create':
            'Impossible de créer l’utilisateur.',
        'users.unable_update':
            'Impossible de mettre à jour l’utilisateur.',
        'users.unable_delete':
            'Impossible de supprimer l’utilisateur.',
        'users.action_failed':
            'Cette modification de l’utilisateur n’a pas été enregistrée.',
        'users.page_of':
            'Page :current sur :last',
        'users.previous':
            'Précédent',
        'users.next':
            'Suivant',

        'settings.title':
            'Paramètres — Patrimoine',

        'settings.administration':
            'Administration',

        'settings.heading':
            'Paramètres',

        'settings.description':
            'Configurez l’organisation qui exploite cette installation de Patrimoine.',

        'settings.managing_organisation':
            'Organisation gestionnaire',

        'settings.managing_organisation_description':
            'Cette organisation représente la société ou l’entité qui gère le portefeuille immobilier dans cette installation de Patrimoine.',

        'settings.organisation_details':
            'Informations sur l’organisation',

        'settings.legal_name':
            'Raison sociale',

        'settings.legal_name_placeholder':
            'p. ex. Apotica Company Limited',

        'settings.address':
            'Adresse',

        'settings.address_placeholder':
            'Adresse de l’organisation',

        'settings.phone':
            'Téléphone',

        'settings.alternate_phone':
            'Téléphone secondaire',

        'settings.general_email':
            'E-mail général',

        'settings.primary_contact':
            'Contact principal',

        'settings.contact_person':
            'Personne de contact',

        'settings.contact_phone':
            'Téléphone du contact',

        'settings.contact_email':
            'E-mail du contact',

        'settings.registration':
            'Immatriculation',

        'settings.registration_number':
            'Numéro d’immatriculation',

        'settings.vat_tin':
            'TVA / NIF',

        'settings.communications':
            'Communications',

        'settings.communications_description':
            'Ce que Patrimoine envoie à vos locataires, propriétaires et agents.',

        'settings.party_emails_enabled':
            'Envoyer des e-mails aux parties',

        'settings.party_emails_help':
            'Lorsque ce paramètre est désactivé, Patrimoine n’envoie rien aux locataires, propriétaires ni agents — ni facture, ni reçu, ni rappel, ni avis, ni bon — et toute tentative d’envoi affiche la raison. Une partie peut malgré tout être autorisée depuis sa propre fiche. Les e-mails destinés aux utilisateurs de Patrimoine (codes de connexion, invitations, réinitialisations de mot de passe) ne sont jamais concernés.',

        'settings.language_currency':
            'Langue et devise',

        'settings.language_currency_description':
            'Ces paramètres s’appliquent à toute l’organisation gestionnaire. La langue et la devise sont indépendantes.',

        'settings.language':
            'Langue',

        'settings.language_help':
            'Contrôle le contenu de Patrimoine normalement visible par les utilisateurs.',

        'settings.currency':
            'Devise',

        'settings.currency_help':
            'Modifie uniquement la présentation. Les valeurs monétaires enregistrées ne sont jamais converties.',

        'settings.financial_defaults':
            'Paramètres financiers par défaut',

        'settings.financial_defaults_description':
            'Les valeurs par défaut s’appliquent uniquement aux nouveaux enregistrements. Les baux et factures existants conservent leurs valeurs enregistrées.',

        'settings.default_vat_rate':
            'Taux de TVA par défaut sur frais de gestion %',

        'settings.vat_help_label':
            'À propos du taux de TVA par défaut sur les frais de gestion',

        'settings.vat_help_text':
            'Ce taux est prérempli lors de la création d’un nouveau bail et s’applique à vos frais de gestion, non au loyer. Chaque bail peut toujours le remplacer, y compris par 0 % lorsque cela s’applique. La modification de ce paramètre ne change pas les baux existants ni les factures historiques.',

        'settings.vat_starting_rate':
            'Utilisé comme taux de TVA initial sur les frais de gestion pour les nouveaux baux.',

        'settings.banking_details':
            'Coordonnées bancaires',

        'settings.optional':
            'Facultatif.',

        'settings.bank_name':
            'Banque',

        'settings.bank_branch':
            'Agence bancaire',

        'settings.account_name':
            'Nom du compte',

        'settings.account_number':
            'Numéro de compte',

        'settings.notes':
            'Notes',

        'settings.save':
            'Enregistrer l’organisation',

        'settings.saving':
            'Enregistrement…',

        'settings.saved':
            'Organisation gestionnaire enregistrée avec succès.',

        'settings.unable_to_load':
            'Impossible de charger l’organisation gestionnaire.',

        'settings.unable_to_save':
            'Impossible d’enregistrer l’organisation gestionnaire.',

        /* ---- V1.0.9 additions ---- */

        'settings.tab_organisation':
            'Organisation',

        'settings.tab_preferences':
            'Préférences',

        'settings.tab_data':
            'Données',

        'settings.not_configured':
            'L’organisation gestionnaire n’est pas encore configurée. Renseignez le formulaire ci-dessous et enregistrez pour la mettre en place.',

        'settings.save_preferences':
            'Enregistrer les préférences',

        'settings.export_success':
            'Exportation téléchargée.',

        'settings.export_opened':
            'PDF ouvert dans un nouvel onglet.',

        'settings.format_csv':
            'CSV',

        'settings.format_xlsx':
            'XLSX',

        'settings.format_pdf':
            'PDF',

        'settings.run_dry_run':
            'Vérifier la restauration',

        'settings.dry_run_running':
            'Simulation en cours…',

        'settings.apply_restore':
            'Appliquer cette restauration',

        'settings.confirm_restore_title':
            'Confirmer la restauration',

        'settings.confirm_restore_description':
            'Vérifiez le résultat de la simulation avant d’appliquer cette restauration au registre.',

        'settings.confirm_restore_warning':
            'L’application de cette restauration modifie immédiatement les données du registre. Cette action est irréversible.',

        'settings.confirm_restore_apply':
            'Appliquer la restauration',

        'settings.restoring':
            'Restauration…',

        'settings.restore_success':
            'Restauration appliquée avec succès.',

        'settings.full_requires_xlsx':
            'Une restauration complète nécessite un classeur .xlsx.',

        /* ---- end V1.0.9 additions ---- */

        'properties.loading':
            'Chargement des propriétés…',

        'properties.unable_to_load':
            'Impossible de charger les propriétés.',

        'properties.no_address':
            'Aucune adresse renseignée',

        'properties.unnamed_property':
            'Propriété sans nom',

        'properties.unit_lower':
            'unité',

        'properties.units_lower':
            'unités',

        'properties.edit':
            'Modifier',

        'properties.add_unit':
            'Ajouter une unité',

        'properties.hide_units':
            'Masquer les unités',

        'properties.view_units':
            'Voir les unités',

        'properties.units':
            'Unités',

        'properties.no_ownership_information':
            'Aucune information sur la propriété',

        'properties.owner':
            'Propriétaire',

        'properties.no_units':
            'Aucune unité n’a été ajoutée à cette propriété.',

        'properties.unnamed_unit':
            'Unité sans nom',

        'properties.unit':
            'Unité',

        'properties.page':
            'Page',

        'properties.of':
            'sur',

        'properties.previous':
            'Précédent',

        'properties.next':
            'Suivant',

        'properties.edit_property':
            'Modifier la propriété',

        'properties.add_property':
            'Ajouter une propriété',

        'properties.edit_property_description':
            'Modifiez les informations de l’immeuble et la répartition de la propriété.',

        'properties.add_property_description':
            'Créez un immeuble, définissez sa propriété et ajoutez ses unités.',

        'properties.save_changes':
            'Enregistrer les modifications',

        'properties.create_property':
            'Créer la propriété',

        'properties.unable_to_load_owners':
            'Impossible de charger les propriétaires.',

        'properties.unable_to_load_property':
            'Impossible de charger la propriété.',

        'properties.party':
            'Partie',

        'properties.create_owner_first':
            'Créez d’abord un propriétaire…',

        'properties.select_owner':
            'Sélectionner un propriétaire…',

        'properties.create_new_owner':
            'Créer un nouveau propriétaire',

        'properties.new':
            '+ Nouveau',

        'properties.no_owners_yet':
            'Aucun propriétaire pour le moment. Créez la première Partie propriétaire.',

        'properties.ownership_percentage':
            'Part de propriété %',

        'properties.remove':
            'Supprimer',

        'properties.total':
            'Total',

        'properties.unit_name_number':
            'Nom / numéro de l’unité',

        'properties.unit_name_placeholder':
            'p. ex. Appartement A1',

        'properties.description':
            'Description',

        'properties.optional_description':
            'Description facultative',

        'properties.validation_owner_required':
            'Une propriété doit avoir au moins un propriétaire.',

        'properties.validation_select_every_owner':
            'Sélectionnez un propriétaire pour chaque ligne de propriété.',

        'properties.validation_duplicate_owner':
            'Le même propriétaire ne peut pas être ajouté plusieurs fois.',

        'properties.validation_owner_percentage':
            'Saisissez un pourcentage de propriété valide pour chaque propriétaire.',

        'properties.validation_ownership_total':
            'La répartition de la propriété doit totaliser exactement 100 %.',

        'properties.validation_unit_required':
            'Une propriété doit avoir au moins une unité.',

        'properties.validation_every_unit_name':
            'Chaque unité doit avoir un nom ou un numéro.',

        'properties.validation_unique_unit_names':
            'Les noms des unités doivent être uniques au sein de la propriété.',

        'properties.saving_changes':
            'Enregistrement des modifications…',

        'properties.creating_property':
            'Création de la propriété…',

        'properties.unable_to_update_property':
            'Impossible de modifier la propriété.',

        'properties.unable_to_create_property':
            'Impossible de créer la propriété.',

        'properties.creating_owner':
            'Création du propriétaire…',

        'properties.unable_to_create_owner':
            'Impossible de créer le propriétaire.',

        'properties.create_owner':
            'Créer le propriétaire',

        'properties.person_required_fields':
            'Le nom, le téléphone et l’e-mail sont obligatoires pour une personne.',

        'properties.organisation_required_fields':
            'La raison sociale et les coordonnées de la personne de contact sont obligatoires.',

        'properties.unable_to_locate_unit':
            'Impossible de trouver cette unité.',

        'properties.property':
            'Propriété',

        'properties.edit_unit':
            'Modifier l’unité',

        'properties.edit_unit_description':
            'Modifiez le nom ou la description de cette unité.',

        'properties.add_unit_description':
            'Ajoutez une unité louable à une propriété existante.',

        'properties.validation_valid_property':
            'Une propriété valide doit être sélectionnée.',

        'properties.validation_unit_name_required':
            'Le nom ou le numéro de l’unité est obligatoire.',

        'properties.adding_unit':
            'Ajout de l’unité…',

        'properties.unable_to_update_unit':
            'Impossible de modifier l’unité.',

        'properties.unable_to_add_unit':
            'Impossible d’ajouter l’unité.',

        'properties.title':
            'Propriétés — Patrimoine',

        'properties.portfolio':
            'Portefeuille',

        'properties.heading':
            'Propriétés',

        'properties.page_description':
            'Gérez les immeubles et les unités.',

        'properties.buildings':
            'Immeubles',

        'properties.total_units':
            'Unités totales',

        'properties.single_unit_properties':
            'Propriétés à une seule unité',

        'properties.multi_unit_properties':
            'Propriétés à plusieurs unités',

        'properties.property_portfolio':
            'Portefeuille immobilier',

        'properties.portfolio_description':
            'Immeubles et unités qui leur sont associées.',

        'properties.search':
            'Rechercher des propriétés',

        'properties.search_placeholder':
            'Rechercher des immeubles ou des unités...',

        'properties.close':
            'Fermer',

        'properties.property_details':
            'Informations sur la propriété',

        'properties.property_details_description':
            'Informations de base permettant d’identifier l’immeuble.',

        'properties.property_name':
            'Nom de la propriété',

        'properties.property_name_placeholder':
            'p. ex. Appartements Airport Residential',

        'properties.location':
            'Localisation',

        'properties.location_placeholder':
            'p. ex. Airport Residential, Accra',

        'properties.address':
            'Adresse',

        'properties.address_placeholder':
            'Rue ou adresse de la propriété',

        'properties.optional_property_description':
            'Description facultative de la propriété',

        'properties.ownership':
            'Propriété',

        'properties.ownership_description':
            'La répartition de la propriété doit totaliser exactement 100 %.',

        'properties.add_owner':
            '+ Ajouter un propriétaire',

        'properties.units_description':
            'Chaque propriété doit comporter au moins une unité pouvant être louée.',

        'properties.cancel':
            'Annuler',

        'properties.save':
            'Enregistrer',

        'properties.create_owner_description':
            'Créez une Partie propriétaire et affectez-la à cette propriété.',

        'properties.owner_type':
            'Type de propriétaire',

        'properties.person':
            'Personne',

        'properties.organisation':
            'Organisation',

        'properties.association':
            'Association',

        'properties.full_name':
            'Nom complet',

        'properties.phone':
            'Téléphone',

        'properties.email':
            'E-mail',

        'properties.legal_name':
            'Raison sociale',

        'properties.contact_person':
            'Personne de contact',

        'properties.contact_phone':
            'Téléphone du contact',

        'properties.contact_email':
            'E-mail du contact',

        'properties.existing_unit_name_placeholder':
            'p. ex. Appartement A2',
        'properties.no_properties_found':
            'Aucune propriété trouvée',
        'properties.no_properties_hint':
            'Ajoutez une propriété ou modifiez votre recherche.',

        'properties.optional_unit_description':
            'Description facultative de l’unité',
        'core.session_expired':
            'Votre session a expiré. Veuillez vous reconnecter.',
        'core.request_failed':
            'Patrimoine n’a pas pu terminer cette opération.',

        'pagination.summary':
            'Affichage de :from à :to sur :total',

        'pagination.empty':
            'Rien à afficher',

        'pagination.rows_per_page':
            'Lignes par page',

        'pagination.navigation':
            'Pagination',

        'pagination.previous':
            'Page précédente',

        'pagination.next':
            'Page suivante',

        'pagination.go_to_page':
            'Aller à la page :page',

        'pagination.current_page':
            'Page :page, page actuelle',

        'profile.download_data':
            'Télécharger mes données',

        'profile.downloading':
            'Préparation…',

        'settings.everything_title':
            'Tout télécharger',

        'settings.everything_description':
            'Une copie complète de tout ce que Patrimoine détient pour cette organisation, historique financier compris, en un seul fichier JSON. L\'export du registre ci-dessus en est la moitié portable ; ceci en est la totalité, pour répondre à qui demande ce que vous détenez.',

        'settings.everything_action':
            'Tout télécharger',

        'parties.export_data':
            'Données',

        'parties.exporting':
            'Préparation…',

        'parties.erase':
            'Effacer',

        'parties.erasing':
            'Effacement…',

        'parties.erase_title':
            'Effacer cette personne',

        'parties.erase_description':
            'Lisez ce qui disparaît et ce qui reste, puis saisissez le nom et votre mot de passe.',

        'parties.erase_warning':
            'Son nom, son adresse e-mail, ses numéros de téléphone, son adresse postale, ses numéros d\'identité et d\'immatriculation, ses coordonnées bancaires et les notes sont détruits définitivement. Personne — ni vous, ni nous — ne pourra les rétablir.',

        'parties.erase_kept':
            'Les factures, les paiements et les écritures du journal demeurent, car la loi qui impose de les conserver est celle-là même qui nous autorise à refuser de les détruire. Ils désigneront cette personne par une référence plutôt que par un nom : les comptes s\'équilibrent toujours et s\'expliquent toujours.',

        'parties.erase_name_label':
            'Saisissez le nom figurant sur la fiche',

        'parties.erase_name_hint':
            'Saisissez :name exactement tel qu\'il est écrit.',

        'parties.erase_password_label':
            'Votre mot de passe',

        'parties.erase_confirm':
            'Effacer cette personne',

        'settings.summary':
            'Résumé du compte',

        'settings.summary_description':
            'Ce compte en un coup d’œil.',

        'settings.summary_account':
            'Compte',

        'settings.summary_plan':
            'Formule',

        'settings.summary_users':
            'Utilisateurs',

        'settings.summary_leases':
            'Baux actifs',

        'settings.summary_parties':
            'Parties',

        'settings.summary_created':
            'Ouvert le',

        'settings.summary_trial':
            'Fin de l’essai',

        'settings.need_help':
            'Besoin d’aide ?',

        'settings.need_help_description':
            'Chaque écran de Patrimoine est expliqué dans le guide, et chaque refus porte un code que vous pouvez y rechercher.',

        'settings.open_guide':
            'Ouvrir le guide',

        'settings.close_account':
            'Fermer ce compte',

        'settings.close_account_description':
            'Supprimer définitivement cette organisation et tout ce qu’elle contient. Cette action est irréversible.',

        'settings.close_account_action':
            'Fermer le compte',

        'settings.close_account_drawer':
            'Lisez ce qui disparaît, puis saisissez le nom et votre mot de passe.',

        'settings.close_account_warning':
            'Tout ce qui suit est détruit définitivement, avec les biens, les baux, les factures, les paiements et le journal financier qui les accompagnent. Personne — ni vous, ni nous — ne pourra les rétablir.',

        'settings.close_account_name_label':
            'Saisissez le nom de l’organisation',

        'settings.close_account_name_hint':
            'Saisissez :name exactement tel qu’il est écrit.',

        'settings.close_account_password_label':
            'Votre mot de passe',

        'settings.close_account_confirm':
            'Tout supprimer',

        'settings.close_account_closing':
            'Fermeture du compte…',

        'settings.close_account_done':
            'Le compte est fermé. Tout ce qu’il contenait a été supprimé.',

'parties.loading': 'Chargement des parties…',
        'parties.unable_to_load': 'Impossible de charger les parties.',
        'parties.no_parties_found': 'Aucune partie trouvée',
        'parties.empty_description': 'Ajoutez une partie ou modifiez les filtres actuels.',
        'parties.party': 'Partie',
        'parties.person': 'Personne',
        'parties.organisation': 'Organisation',
        'parties.association': 'Association',
        'parties.tenant': 'Locataire',
        'parties.owner': 'Propriétaire',
        'parties.agent': 'Agent',
        'parties.managing_organisation': 'Organisation gestionnaire',
        'parties.no_assigned_role': 'Aucun rôle attribué',
        'parties.contact': 'Contact',
        'parties.edit': 'Modifier',
        'parties.delete': 'Supprimer',
        'parties.page': 'Page',
        'parties.of': 'sur',
        'parties.previous': 'Précédent',
        'parties.next': 'Suivant',
        'parties.unable_to_load_party': 'Impossible de charger la partie.',
        'parties.edit_party': 'Modifier la partie',
        'parties.add_party': 'Ajouter une partie',
        'parties.edit_party_description': 'Mettez à jour l’identité, les coordonnées et les rôles de la partie.',
        'parties.add_party_description': 'Créez une personne, une organisation ou une association.',
        'parties.save_changes': 'Enregistrer les modifications',
        'parties.create_party': 'Créer la partie',
        'parties.save': 'Enregistrer',

        'leases.financial_history': 'Historique financier',

        'leases.financial_history_export_pdf': 'PDF',

        'leases.financial_history_export_excel': 'Excel',

        'leases.financial_history_export_csv': 'CSV',
        'leases.financial_history_description': 'Activité financière chronologique de ce bail.',
        'leases.financial_history_loading': 'Chargement de l’historique financier…',
        'leases.financial_history_unable_load': 'Impossible de charger l’historique financier.',
        'leases.financial_history_empty': 'Aucun historique financier',
        'leases.financial_history_empty_description': 'Aucun événement financier n’a été enregistré pour ce bail.',
        'leases.financial_history_reference': 'Référence',
        'leases.financial_history_payment_method': 'Mode de paiement',
        'leases.financial_history_fund': 'Fonds',
        'leases.financial_history_open_document': 'Ouvrir le document',
        'leases.financial_history_unable_open_document': 'Impossible d’ouvrir le document.',
        'leases.financial_history_event_invoice': 'Facture',
        'leases.financial_history_event_payment': 'Paiement du locataire',
        'leases.financial_history_event_fund_deposit': 'Dépôt de fonds',
        'leases.financial_history_event_rent_reserve_consumption': 'Réserve de loyer appliquée',
        'leases.financial_history_event_advance_consumption': 'Avance consommable appliquée',
        'leases.financial_history_event_withdrawal': 'Retrait',
        'leases.financial_history_event_adjustment': 'Ajustement',
        'leases.financial_history_event_security_application': 'Dépôt de garantie appliqué',
        'leases.financial_history_event_security_deduction': 'Déduction du dépôt de garantie',
        'leases.financial_history_event_security_settlement': 'Règlement du dépôt de garantie',
        'leases.financial_history_event_security_movement': 'Mouvement du dépôt de garantie',
        'leases.financial_history_event_fund_movement': 'Mouvement de fonds',
        'leases.financial_history_fund_rent_reserve': 'Réserve de loyer',
        'leases.financial_history_fund_consumable_advance': 'Avance consommable',
        'leases.financial_history_fund_security_deposit': 'Dépôt de garantie',
        'leases.financial_history_method_cash': 'Espèces',
        'leases.financial_history_method_bank_transfer': 'Virement bancaire',
        'leases.financial_history_method_mobile_payment': 'Paiement mobile',
        'leases.financial_history_method_cheque': 'Chèque',
        'leases.unable_initialize': 'Impossible d’initialiser les baux.',
        'leases.all_tenants': 'Tous les locataires',
        'leases.select_tenant': 'Sélectionner un locataire…',
        'leases.no_agent': 'Aucun agent',
        'leases.no_matching_units': 'Aucune unité correspondante trouvée.',
        'leases.no_matching_tenants': 'Aucun locataire correspondant.',
        'leases.duration': 'Durée',
        'leases.duration_3m': '3 mois',
        'leases.duration_6m': '6 mois',
        'leases.duration_1y': '1 an',
        'leases.duration_2y': '2 ans',
        'leases.duration_3y': '3 ans',
        'leases.duration_4y': '4 ans',
        'leases.duration_5y': '5 ans',
        'leases.duration_custom': 'Autre',
        'leases.notice_1m': '1 mois avant la fin',
        'leases.notice_3m': '3 mois avant la fin',
        'leases.notice_6m': '6 mois avant la fin',
        'leases.summary_title': 'Vérifier ce bail',
        'leases.summary_description': 'Vérifiez les informations ci-dessous. Rien n’est enregistré avant votre confirmation — le bail devient ensuite immédiatement actif.',
        'leases.summary_back': 'Retour',
        'leases.review': 'Vérifier',
        'leases.summary_parties': 'Parties et unité',
        'leases.summary_rent_terms': 'Conditions de loyer',
        'leases.summary_money_held': 'Fonds détenus',
        'leases.summary_management': 'Gestion et notes',
        'leases.summary_automatic': 'Automatique',
        'leases.proration': 'Prorata',
        'leases.advance_received': 'Avance déjà reçue',
        'leases.duration_caption': 'Choisir une durée renseigne automatiquement la date de fin. Modifier la date de fin bascule sur Autre.',
        'leases.notice_period': 'Préavis',
        'leases.notice_caption': 'Calcule la date de préavis à partir de la date de fin.',
        'leases.summary_confirm': 'Confirmer',
        'leases.summary_backdated_note': 'L’activation générera :count facture(s) antérieure(s) pour un total de :total, couvrant la période de la date de début à aujourd’hui.',
        'leases.no_matching_agents': 'Aucun agent correspondant.',
        'leases.tenant_search_placeholder': 'Rechercher un locataire par nom, téléphone ou e-mail…',
        'leases.agent_search_placeholder': 'Rechercher un agent par nom, téléphone ou e-mail…',
        'leases.clear_selected_tenant': 'Effacer le locataire sélectionné',
        'leases.clear_selected_agent': 'Effacer l’agent sélectionné',
        'leases.property': 'Propriété',
        'leases.unit': 'Unité',
        'leases.owner': 'Propriétaire',
        'leases.no_ownership_information': 'Aucune information sur la propriété disponible.',
        'leases.loading': 'Chargement des baux…',
        'leases.unable_load': 'Impossible de charger les baux.',
        'leases.none_found': 'Aucun bail trouvé',
        'leases.none_found_description': 'Créez un bail ou modifiez les filtres actuels.',
        'leases.tenant': 'Locataire',
        'leases.agent': 'Agent',
        'leases.start': 'Début',
        'leases.end': 'Fin',
        'leases.vat': 'TVA',
        'leases.tenant_funds': 'Fonds du locataire',
        'leases.manage_security_deposit': 'Dépôt de garantie',
        'leases.edit': 'Modifier',
        'leases.extend': 'Prolonger',
        'leases.terminate': 'Résilier',
        'leases.terminate_lease': 'Résilier le bail',
        'leases.termination_description': 'Enregistrez le préavis, définissez la date de départ et choisissez le traitement du dernier loyer.',
        'leases.lease_context': 'Contexte du bail',
        'leases.lease': 'Bail',
        'leases.termination_details': 'Détails de la résiliation',
        'leases.termination_date': 'Date de résiliation / départ',
        'leases.final_rent_treatment': 'Dernière période de loyer',
        'leases.final_rent_prorate': 'Proratiser la dernière période',
        'leases.final_rent_prorate_help': 'Facturer le loyer uniquement jusqu’à la date de résiliation sélectionnée.',
        'leases.final_rent_full': 'Facturer la période complète',
        'leases.final_rent_full_help': 'Facturer toute la période contractuelle contenant la date de résiliation.',
        'leases.final_rent_none': 'Aucun loyer final',
        'leases.final_rent_none_help': 'Ne pas facturer de loyer pour la dernière période partielle.',
        'leases.initiate_termination': 'Initier la résiliation',
        'leases.termination_required_fields': 'La date de préavis, la date de résiliation et le traitement du dernier loyer sont obligatoires.',
        'leases.termination_failed': 'Impossible d’initier la résiliation du bail.',
        'leases.termination_notice': 'Avis de résiliation',
        'leases.termination_notice_ready': 'L’avis de résiliation a été généré et peut être ouvert.',
        'leases.open_termination_notice': 'Ouvrir l’avis de résiliation',
        'leases.termination_notice_unable_open': 'Impossible d’ouvrir l’avis de résiliation.',
        'leases.extend_lease': 'Prolonger le bail',
        'leases.extend_description': 'Créez une nouvelle période contractuelle tout en conservant le bail et son historique.',
        'leases.current_terms': 'Conditions actuelles',
        'leases.new_terms': 'Nouvelles conditions',
        'leases.effective_from': 'Prise d’effet',
        'leases.delete': 'Supprimer',
        'leases.delete_lease': 'Supprimer le bail',
        'leases.delete_destructive_action': 'Action destructive',
        'leases.delete_context': 'Bail à supprimer',
        'leases.delete_impact_title': 'Impact de la suppression',
        'leases.delete_impact_description': 'Patrimoine supprimera définitivement le bail et son historique financier opérationnel tout en conservant les éléments comptables et d’audit requis.',
        'leases.delete_impact_loading': 'Calcul de l’impact de la suppression…',
        'leases.delete_impact_failed': 'Impossible de calculer l’impact de la suppression du bail.',
        'leases.delete_impact_invoices': 'Factures',
        'leases.delete_impact_payments': 'Paiements',
        'leases.delete_impact_allocations': 'Affectations',
        'leases.delete_impact_receipts': 'Reçus de retrait',
        'leases.delete_impact_security': 'Solde du dépôt de garantie',
        'leases.delete_impact_reserve': 'Solde de la réserve de loyer',
        'leases.delete_impact_consumable': 'Solde de l’avance consommable',
        'leases.delete_impact_outstanding': 'Solde des factures impayées',
        'leases.delete_impact_reversals': 'Contre-passations du journal',
        'leases.delete_impact_owner': 'Effet du bail sur le propriétaire',
        'leases.delete_impact_safe': 'L’impact complet est classifié et ce bail est admissible à la suppression contrôlée.',
        'leases.delete_blocked': 'Ce bail ne peut pas être supprimé en toute sécurité.',
        'leases.delete_reason': 'Motif de suppression',
        'leases.delete_confirmation_label': 'Saisissez DELETE pour confirmer',
        'leases.delete_password': 'Mot de passe actuel',
        'leases.delete_permanently': 'Supprimer définitivement',
        'leases.status_draft': 'En cours',
        'leases.status_active': 'Actif',
        'leases.status_notice': 'Préavis',
        'leases.status_terminated': 'Résilié',
        'leases.frequency_month': 'mois',
        'leases.frequency_quarter': 'trimestre',
        'leases.frequency_six_months': 'six mois',
        'leases.frequency_year': 'an',
        'leases.page': 'Page',
        'leases.of': 'sur',
        'leases.previous': 'Précédent',
        'leases.next': 'Suivant',
        'leases.unable_load_one': 'Impossible de charger le bail.',
        'leases.edit_lease': 'Modifier le bail',
        'leases.add_lease': 'Ajouter un bail',
        'leases.edit_description': 'Mettre à jour le contrat de location et les conditions contractuelles.',
        'leases.add_description': 'Créer un contrat de location pour une unité.',
        'leases.save_changes': 'Enregistrer les modifications',
        'leases.create_lease': 'Créer le bail',
        'leases.save': 'Enregistrer',
        'leases.select_valid_unit': 'Sélectionnez une propriété / unité valide.',
        'leases.reserve_exceeds_advance': 'La réserve de loyer ne peut pas dépasser le paiement anticipé total.',
        'leases.saving_changes': 'Enregistrement des modifications…',
        'leases.creating': 'Création du bail…',
        'leases.unable_update': 'Impossible de mettre à jour le bail.',
        'leases.unable_create': 'Impossible de créer le bail.',
        'leases.this_lease': 'ce bail',
        'leases.delete_financial_history_warning': 'La suppression d’un bail est définitive et peut supprimer les données appartenant exclusivement à ce bail. Examinez attentivement l’impact avant de continuer.',
        'leases.delete_reason_prompt': 'Motif de la suppression de ce bail :',
        'leases.delete_reason_required': 'Un motif de suppression est obligatoire.',
        'leases.delete_confirmation_prompt': 'Saisissez exactement DELETE pour confirmer :',
        'leases.delete_confirmation_invalid': 'La confirmation doit indiquer exactement DELETE.',
        'leases.delete_password_prompt': 'Saisissez votre mot de passe actuel :',
        'leases.delete_password_required': 'Votre mot de passe actuel est obligatoire.',
        'leases.delete_final_confirmation': 'Supprimer définitivement ce bail ? Cette action est irréversible.',
        'leases.unable_delete': 'Impossible de supprimer le bail.',
        'leases.security_review_description': 'Consultez les fonds détenus, les déductions et le règlement final.',
        'leases.unable_load_security_deposit': 'Impossible de charger le dépôt de garantie.',
        'leases.voucher': 'Bon',
        'leases.security_available_after_termination': 'Les déductions du dépôt de garantie sont disponibles pendant la résiliation. Le règlement final reste contrôlé jusqu’à la résolution de la situation financière du bail.',
        'leases.security_available_during_termination': 'Les déductions du dépôt de garantie deviennent disponibles lorsque la résiliation est en cours.',
        'leases.security_deductions_during_termination': 'Les déductions détaillées peuvent être enregistrées pendant la résiliation. Le règlement final du dépôt de garantie reste disponible après la fin de la résiliation.',
        'leases.termination_settlement': 'Règlement de résiliation',
        'leases.termination_settlement_description': 'Examinez la situation financière et résolvez chaque blocage avant de terminer la résiliation.',
        'leases.termination_settlement_loading': 'Chargement du règlement…',
        'leases.termination_settlement_load_failed': 'Impossible de charger le règlement de résiliation.',
        'leases.termination_financial_position': 'Situation financière',
        'leases.outstanding_debt': 'Dette impayée',
        'leases.security_deposit_deductions': 'Déductions sur le dépôt de garantie',
        'leases.other_tenant_funds': 'Autres fonds du locataire',
        'leases.amount_still_owed': 'Montant restant dû',
        'leases.final_refundable_amount': 'Montant potentiellement remboursable',
        'leases.termination_unresolved_items': 'Éléments à résoudre',
        'leases.termination_unresolved_item': 'Élément de règlement non résolu',
        'leases.termination_no_blockers': 'Aucun blocage financier non résolu. La résiliation peut être terminée.',
        'leases.termination_resolve_from_tenant': 'Réglez les dettes, fonds détenus et remboursements depuis l’espace Locataire. Les opérations financières ne sont pas dupliquées sur la page du bail.',
        'leases.go_to_tenant': 'Voir le locataire',
        'leases.complete_termination': 'Terminer la résiliation',
        'leases.cancel_termination': 'Annuler la résiliation',
        'leases.confirm_complete_termination': 'Terminer cette résiliation de bail ? Le bail deviendra inactif et l’occupation de l’unité sera recalculée.',
        'leases.confirm_cancel_termination': 'Annuler cette résiliation et restaurer l’état opérationnel précédent du bail ?',
        'leases.termination_complete_failed': 'La résiliation n’a pas été finalisée.',
        'leases.termination_cancel_failed': 'Impossible d’annuler la résiliation.',
        'leases.no_deductions': 'Aucune déduction enregistrée.',
        'leases.date': 'Date',
        'leases.description': 'Description',
        'leases.reference': 'Référence',
        'leases.amount': 'Montant',
        'leases.voucher_popup_blocked': 'Le bon n’a pas pu être ouvert car le navigateur a bloqué le nouvel onglet.',
        'leases.opening': 'Ouverture…',
        'leases.unable_open_voucher': 'Impossible d’ouvrir le bon du dépôt de garantie.',
        'leases.download_voucher': 'Télécharger le bon',
        'leases.adding': 'Ajout…',
        'leases.unable_add_deduction': 'Impossible d’ajouter la déduction du dépôt de garantie.',
        'leases.add_deduction': 'Ajouter une déduction',
        'leases.finalize_security_confirmation': 'Finaliser ce règlement du dépôt de garantie ?',
        'leases.finalize_security_warning': 'Cette action est définitive. Aucune autre déduction ne pourra être ajoutée par la suite.',
        'leases.finalizing': 'Finalisation…',
        'leases.unable_finalize_security': 'Impossible de finaliser le dépôt de garantie.',
        'leases.finalize_settlement': 'Finaliser le règlement',
        'leases.tenant_funds_description': 'Consultez les fonds effectivement détenus pour le locataire.',
        'leases.unable_load_tenant_funds': 'Impossible de charger les fonds du locataire.',
        'leases.no_outstanding_invoice': 'Aucune facture impayée',
        'leases.select_invoice': 'Sélectionner une facture…',
        'leases.invoice': 'Facture',
        'leases.outstanding': 'impayé',
        'leases.no_rent_reserve': 'Aucun solde de réserve de loyer n’est actuellement disponible.',
        'leases.reserve_protected': 'La réserve de loyer reste protégée jusqu’à l’enregistrement du préavis de résiliation.',
        'leases.reserve_available': 'Le préavis de résiliation a été enregistré. La réserve disponible peut maintenant être affectée au loyer impayé.',
        'leases.no_consumable_advance': 'Aucun solde d’avance consommable n’est actuellement disponible.',
        'leases.applying': 'Application…',
        'leases.unable_apply_reserve': 'Impossible d’appliquer la réserve de loyer.',
        'leases.apply_rent_reserve': 'Appliquer la réserve de loyer',
        'leases.unable_apply_advance': 'Impossible d’appliquer l’avance consommable.',
        'leases.apply_consumable_advance': 'Appliquer l’avance consommable',

        'leases.title': 'Baux — Patrimoine',
        'leases.tenancy': 'Location',
        'leases.heading': 'Baux',
        'leases.page_description': 'Gérez les contrats de location, les conditions de loyer et le cycle de vie des baux.',
        'leases.total_leases': 'Total des baux',
        'leases.in_notice': 'En préavis',
        'leases.register': 'Registre des baux',
        'leases.register_description': 'Contrats de location actuels et historiques.',
        'leases.lease_status': 'Statut du bail',
        'leases.all_statuses': 'Tous les statuts',
        'leases.close': 'Fermer',
        'leases.property_tenant': 'Propriété et locataire',
        'leases.property_tenant_description': 'Sélectionnez l’unité louée et les parties au contrat.',
        'leases.property_unit': 'Propriété / Unité',
        'leases.unit_search_placeholder': 'Rechercher une propriété, un emplacement, une unité ou un propriétaire…',
        'leases.clear_selected_unit': 'Effacer l’unité sélectionnée',
        'leases.selected_unit': 'Unité sélectionnée',
        'leases.ownership': 'Propriété',
        'leases.lease_period': 'Période du bail',
        'leases.lease_period_description': 'Définissez la prise d’effet du contrat et son état actuel.',
        'leases.start_date': 'Date de début',
        'leases.end_date': 'Date de fin',
        'leases.status': 'Statut',
        'leases.notice_date': 'Date du préavis',
        'leases.rent_terms': 'Conditions de loyer',
        'leases.rent_terms_description': 'Les montants incluent la TVA et sont enregistrés en unités monétaires entières.',
        'leases.monthly_rent': 'Loyer mensuel',
        'leases.payment_frequency': 'Fréquence de paiement',
        'leases.due_day': 'Jour d’échéance',
        'leases.due_day_override': 'Jour d’échéance personnalisé',
        'leases.vat_rate': 'Taux de TVA sur frais de gestion %',
        'leases.proration_override': 'Prorata personnalisé',
        'leases.security_deposit': 'Dépôt de garantie',
        'leases.monthly': 'Mensuel',
        'leases.quarterly': 'Trimestriel',
        'leases.bi_yearly': 'Semestriel',
        'leases.yearly': 'Annuel',
        'leases.from_start_date': 'Selon la date de début',
        'leases.automatic': 'Automatique',
        'leases.advance_payment': 'Paiement anticipé',
        'leases.advance_payment_description': 'Enregistrez l’avance contractuelle et la part devant rester protégée en réserve de loyer.',
        'leases.total_advance_payment': 'Paiement anticipé total',
        'leases.rent_reserve': 'Réserve de loyer',
        'leases.consumable_advance': 'Avance consommable',
        'leases.advance_already_received': 'Avance déjà reçue',
        'leases.advance_received_description': 'Utilisez cette option lors de la saisie d’un bail existant ou antidaté pour lequel le locataire a déjà payé l’avance.',
        'leases.date_received': 'Date de réception',
        'leases.payment_method': 'Mode de paiement',
        'leases.cash_collector': 'Caissier',
        'leases.select_method': 'Sélectionner un mode...',
        'leases.bank_transfer': 'Virement bancaire',
        'leases.mobile_money': 'Paiement mobile',
        'leases.cheque': 'Chèque',
        'leases.cash': 'Espèces',
        'leases.optional': 'Facultatif',
        'leases.cash_collector_placeholder': 'Défini automatiquement selon l’utilisateur connecté',
        'leases.rent_increment': 'Augmentation du loyer',
        'leases.rent_increment_description': 'Configurez la prochaine augmentation contractuelle du loyer, le cas échéant.',
        'leases.increment_type': 'Type d’augmentation',
        'leases.increment_value': 'Valeur de l’augmentation',
        'leases.next_increment_date': 'Prochaine augmentation',
        'leases.none': 'Aucun',
        'leases.percentage': 'Pourcentage',
        'leases.fixed_amount': 'Montant fixe',
        'leases.fees_commission': 'Frais et commission',
        'leases.fees_commission_description': 'Configurez les frais de gestion et la commission unique de l’agent applicables à ce bail.',
        'leases.management_fee': 'Frais de gestion',
        'leases.fee_value': 'Valeur des frais',
        'leases.agent_commission': 'Commission de l’agent',
        'leases.notes': 'Notes',
        'leases.notes_placeholder': 'Notes facultatives sur le bail',
        'leases.cancel': 'Annuler',

        'leases.property_unit_help_label': 'À propos de la propriété et de l’unité',
        'leases.property_unit_help_text': 'Recherchez l’unité louable précise couverte par ce contrat. Une unité hérite de la propriété de son immeuble et ne peut pas avoir plus d’un bail actif ou en préavis simultanément.',
        'leases.tenant_help_label': 'À propos du locataire',
        'leases.tenant_help_text': 'La partie qui loue cette unité. Patrimoine V1 prend en charge exactement un locataire par bail. La partie sélectionnée doit avoir le rôle Locataire.',
        'leases.agent_help_label': 'À propos de l’agent',
        'leases.agent_help_text': 'Partie facultative ayant facilité ou gérant cette opération de location. Si la commission de l’agent est supérieure à zéro, un agent doit être sélectionné. La partie sélectionnée doit avoir le rôle Agent.',
        'leases.start_date_help_label': 'À propos de la date de début',
        'leases.start_date_help_text': 'Date de début du bail. Sauf si un jour d’échéance personnalisé est défini, Patrimoine utilise le jour de cette date comme jour récurrent d’échéance du loyer.',
        'leases.end_date_help_label': 'À propos de la date de fin',
        'leases.end_date_help_text': 'Date de fin contractuelle facultative. Laissez ce champ vide pour un bail sans date de fin prédéterminée.',
        'leases.status_help_label': 'À propos du statut du bail',
        'leases.status_help_text': 'En cours signifie que le bail est préparé mais pas encore en vigueur. Actif signifie que la location est en cours. Préavis signifie qu’un préavis de résiliation a été enregistré. Résilié signifie que le bail a pris fin.',
        'leases.notice_date_help_label': 'À propos de la date du préavis',
        'leases.notice_date_help_text': 'Date à laquelle le préavis de résiliation a été reçu ou émis. Ce champ devient obligatoire lorsque le statut du bail est Préavis et déterminera ensuite le début de la consommation de la réserve de loyer.',
        'leases.monthly_rent_help_label': 'À propos du loyer mensuel',
        'leases.monthly_rent_help_text': 'Loyer mensuel contractuel de l’unité, TVA incluse. La fréquence de paiement détermine combien de mois sont facturés ensemble. Par exemple, un loyer mensuel de 5 000 avec une fréquence trimestrielle crée une obligation de loyer de 15 000 pour chaque période trimestrielle.',
        'leases.payment_frequency_help_label': 'À propos de la fréquence de paiement',
        'leases.payment_frequency_help_text': 'Détermine la fréquence à laquelle le loyer mensuel devient exigible : mensuellement, trimestriellement, tous les six mois ou annuellement.',
        'leases.due_day_help_label': 'À propos du jour d’échéance personnalisé',
        'leases.due_day_help_text': 'Laissez ce champ vide pour utiliser le jour de la date de début du bail comme jour d’échéance. Par exemple, un bail commençant le 15 sera normalement exigible le 15. Saisissez un autre jour pour remplacer cette règle.',
        'leases.vat_rate_help_label': 'À propos de la TVA sur les frais de gestion',
        'leases.vat_rate_help_text': 'La TVA est calculée sur vos frais de gestion, jamais sur le loyer. Sur un loyer de 100 000 avec des frais de 10 % et un taux de 20 %, le propriétaire est débité de 10 000 de frais et de 2 000 de TVA, et perçoit 88 000. Indiquez 0 % lorsque la TVA ne s’applique pas.',
        'leases.proration_help_label': 'À propos du prorata personnalisé',
        'leases.proration_help_text': 'Laissez ce champ vide pour que Patrimoine calcule automatiquement le prorata d’une période de facturation partielle. Saisissez 0 pour ne facturer volontairement aucun prorata. Tout autre montant remplace le calcul automatique.',
        'leases.security_deposit_help_label': 'À propos du dépôt de garantie',
        'leases.security_deposit_help_text': 'Dépôt de garantie contractuel exigé du locataire. Il est détenu séparément du loyer et peut ensuite être réduit par des déductions détaillées avant le remboursement du solde restant.',
        'leases.advance_payment_help_label': 'À propos du paiement anticipé',
        'leases.advance_payment_help_text': 'Montant total du loyer anticipé contractuellement attendu du locataire. Ceci enregistre uniquement le contrat de bail et ne signifie pas que Patrimoine a effectivement reçu l’argent. Les fonds réels sont enregistrés ensuite via les paiements.',
        'leases.rent_reserve_help_label': 'À propos de la réserve de loyer',
        'leases.rent_reserve_help_text': 'Part du paiement anticipé contractuel qui doit rester protégée pendant la durée du bail. Après le préavis de résiliation, la réserve de loyer peut être utilisée contre le loyer conformément aux règles de Patrimoine.',
        'leases.consumable_advance_help_label': 'À propos de l’avance consommable',
        'leases.consumable_advance_help_text': 'Part contractuelle du paiement anticipé qui n’est pas réservée. Patrimoine la calcule comme le paiement anticipé total moins la réserve de loyer. Les fonds réellement disponibles proviennent toujours du registre des fonds du locataire.',
        'leases.advance_received_help_label': 'À propos de l’avance déjà reçue',
        'leases.advance_received_help_text': 'Sélectionnez cette option uniquement lorsque le paiement anticipé contractuel a réellement été reçu avant la saisie du bail dans Patrimoine. Patrimoine reconstruira le paiement historique, protégera la part de réserve de loyer, affectera le reste de l’avance au loyer impayé et créera les écritures comptables correspondantes du propriétaire.',
        'leases.increment_type_help_label': 'À propos du type d’augmentation',
        'leases.increment_type_help_text': 'Choisissez comment la prochaine augmentation du loyer est définie. Pourcentage augmente le loyer mensuel existant selon un taux. Montant fixe ajoute un montant monétaire précis. Choisissez Aucun lorsqu’aucune augmentation n’a été convenue.',
        'leases.increment_value_help_label': 'À propos de la valeur de l’augmentation',
        'leases.increment_value_help_text': 'Saisissez le taux ou le montant de la prochaine augmentation du loyer. Sa signification dépend du type d’augmentation sélectionné.',
        'leases.increment_date_help_label': 'À propos de la date de la prochaine augmentation',
        'leases.increment_date_help_text': 'Date à laquelle l’augmentation configurée doit prendre effet pour la première fois. Patrimoine V1 conserve cette date contractuelle mais ne déduit pas les augmentations récurrentes futures au-delà de celle-ci.',
        'leases.management_fee_help_label': 'À propos des frais de l’organisation gestionnaire',
        'leases.management_fee_help_text': 'Définit les frais gagnés par l’organisation gestionnaire pour la gestion du loyer de ce bail. Choisissez Aucun, Pourcentage du loyer ou Montant fixe. Le montant est finalement déduit du droit du propriétaire.',
        'leases.management_fee_value_help_label': 'À propos de la valeur des frais de gestion',
        'leases.management_fee_value_help_text': 'La signification dépend du type de frais de l’organisation gestionnaire. Pour Pourcentage, saisissez le taux. Pour Montant fixe, saisissez le montant monétaire. Lorsque les frais sont Aucun, cette valeur doit rester à 0.',
        'leases.agent_commission_help_label': 'À propos de la commission de l’agent',
        'leases.agent_commission_help_text': 'Commission unique convenue avec l’agent pour ce bail. Saisissez le montant total en unités monétaires entières. Une commission non nulle nécessite la sélection d’un agent.',
        'leases.notes_help_label': 'À propos des notes du bail',
        'leases.notes_help_text': 'Informations internes facultatives sur le contrat qui ne font pas partie des calculs financiers automatisés de Patrimoine.',

        'leases.security_closeout': 'Clôture du bail',
        'leases.security_modal_description': 'Consultez les fonds détenus, les déductions détaillées et le règlement final.',
        'leases.loading_security_deposit': 'Chargement du dépôt de garantie…',
        'leases.contractual_deposit': 'Dépôt contractuel',
        'leases.held_balance': 'Solde détenu',
        'leases.deductions': 'Déductions',
        'leases.refund': 'Remboursement',
        'leases.tenant_debt': 'Dette du locataire',
        'leases.itemized_deductions': 'Déductions détaillées',
        'leases.itemized_deductions_description': 'Frais retenus sur le dépôt de garantie du locataire.',
        'leases.deduction_date': 'Date de déduction',
        'leases.deduction_description_placeholder': 'ex. Serrure endommagée',
        'leases.deduction_reference_placeholder': 'Référence d’inspection / ordre de travail',
        'leases.optional_details': 'Détails facultatifs',
        'leases.final_settlement': 'Règlement final',
        'leases.final_settlement_description': 'Finalisez le dépôt de garantie et créez le bon de règlement officiel.',
        'leases.settlement_date': 'Date de règlement',
        'leases.closeout_notes_placeholder': 'Notes de clôture facultatives',
        'leases.final_settlement_warning': 'Le règlement final est irréversible. Une fois confirmé, aucune déduction supplémentaire du dépôt de garantie ne pourra être ajoutée.',
        'leases.security_deposit_settled': 'Dépôt de garantie réglé',

        'leases.tenant_money': 'Fonds du locataire',
        'leases.tenant_funds_modal_description': 'Consultez les soldes réellement détenus et appliquez les fonds admissibles au loyer.',
        'leases.loading_tenant_funds': 'Chargement des fonds du locataire…',
        'leases.reserve_protected_short': 'Protégée jusqu’au préavis de résiliation.',
        'leases.consumable_advance_description': 'Avance disponible du locataire pouvant être appliquée au loyer.',
        'leases.manage_security_deposit': 'Dépôt de garantie',
        'leases.apply_reserve_description': 'La réserve de loyer devient consommable après le préavis de résiliation et peut régler une facture impayée.',
        'leases.outstanding_invoice': 'Facture impayée',
        'leases.apply_advance_description': 'Appliquez l’avance consommable disponible à une facture de loyer impayée.',

        'payments.unable_to_load': 'Impossible de charger les paiements.',
        'payments.no_matching_payments': 'Aucun paiement ne correspond aux filtres sélectionnés.',
        'payments.tenant_payment': 'Paiement du locataire',
        'payments.owner_deposit': 'Dépôt du propriétaire',
        'payments.manage_funds': 'Gérer les fonds',
        'payments.receipt': 'Reçu',
        'payments.tenant': 'Locataire',
        'payments.owner': 'Propriétaire',
        'payments.reference': 'Réf.',
        'payments.collector': 'Caissier',
        'payments.lease_number': 'Bail n° {id}',
        'payments.general_owner_account': 'Compte général du propriétaire',
        'payments.pagination_single': 'Page {current} sur {last} · {total} opération',
        'payments.pagination_plural': 'Page {current} sur {last} · {total} opérations',
        'payments.previous': 'Précédent',
        'payments.next': 'Suivant',
        'payments.unable_to_open_receipt': 'Impossible d’ouvrir le reçu.',
        'payments.searching': 'Recherche…',
        'payments.unable_to_search_tenants': 'Impossible de rechercher les locataires.',
        'payments.no_matching_tenants': 'Aucun locataire correspondant trouvé.',
        'payments.loading_leases': 'Chargement des baux…',
        'payments.no_payable_lease': 'Aucun bail payable trouvé',
        'payments.no_payable_lease_help': 'Ce locataire n’a aucun bail non brouillon sur lequel un paiement peut être enregistré.',
        'payments.select_lease_property': 'Sélectionner le bail / la propriété',
        'payments.lease_fifo_outstanding_help': 'Les paiements sont enregistrés sur le bail applicable afin que le loyer impayé puisse être affecté selon la méthode FIFO.',
        'payments.unable_to_load_leases': 'Impossible de charger les baux',
        'payments.unable_to_load_tenant_leases': 'Impossible de charger les baux du locataire.',
        'payments.search_select_tenant_first': 'Recherchez et sélectionnez d’abord un locataire',
        'payments.lease_fifo_help': 'Les paiements sont enregistrés sur le bail applicable afin que le loyer puisse être affecté selon la méthode FIFO.',
        'payments.unable_to_search_owners': 'Impossible de rechercher les propriétaires.',
        'payments.no_matching_owners': 'Aucun propriétaire correspondant trouvé.',
        'payments.unable_to_load_owner': 'Impossible de charger les informations du propriétaire.',
        'payments.no_specific_building': 'Aucun immeuble spécifique',
        'payments.building_number': 'Immeuble n° {id}',
        'payments.no_specific_unit': 'Aucune unité spécifique',
        'payments.unit_number': 'Unité n° {id}',
        'payments.select_building_first': 'Sélectionnez d’abord un immeuble',
        'payments.validation_amount': 'Saisissez un montant de paiement valide supérieur à zéro.',
        'payments.validation_date': 'La date du paiement est obligatoire.',
        'payments.validation_method': 'Sélectionnez un mode de paiement valide.',
        'payments.validation_collector': 'Le caissier n’a pas pu être déterminé pour ce paiement.',
        'payments.unable_to_record': 'Impossible d’enregistrer le paiement.',
        'payments.select_tenant_required': 'Recherchez et sélectionnez un locataire.',
        'payments.select_lease_required': 'Sélectionnez le bail / la propriété pour lequel le paiement du locataire a été reçu.',
        'payments.payment_receipt_unresolved': 'Le paiement a été enregistré mais son reçu n’a pas pu être identifié.',
        'payments.select_owner_required': 'Recherchez et sélectionnez un propriétaire.',
        'payments.owner_receipt_unresolved': 'Le dépôt du propriétaire a été enregistré mais son reçu n’a pas pu être identifié.',
        'payments.recording': 'Enregistrement…',
        'payments.record_payment': 'Enregistrer un paiement',
        'payments.save_payment': 'Enregistrer',
        'payments.cash': 'Espèces',
        'payments.bank_transfer': 'Virement bancaire',
        'payments.momo': 'Paiement mobile',
        'payments.cheque': 'Chèque',
        'payments.general_funding': 'Financement général',
        'payments.property_expense': 'Dépense immobilière',
        'payments.repair_maintenance': 'Réparation / Entretien',
        'payments.other': 'Autre',
        'payments.unnamed_party': 'Partie sans nom',
        'payments.property': 'Propriété',
        'payments.status_draft': 'En cours',
        'payments.status_active': 'Actif',
        'payments.status_notice': 'Préavis',
        'payments.status_terminated': 'Résilié',
        'payments.from_date': 'Depuis le {date}',
        'payments.loading': 'Chargement des paiements…',
        'payments.unable_to_load_funds': 'Impossible de charger les fonds du paiement.',
        'payments.maximum_available': 'Maximum disponible : {amount}',
        'payments.unable_to_classify_funds': 'Impossible de classer les fonds du locataire.',
        'payments.allocate_funds': 'Affecter les fonds',
        'owners.unable_to_load': 'Impossible de charger les propriétaires.',
        'owners.no_search_results': 'Aucun propriétaire ne correspond à votre recherche.',
        'owners.unable_to_load_details': 'Impossible de charger les détails du propriétaire.',
        'owners.unable_to_load_owner': 'Impossible de charger ce propriétaire.',
        'owners.no_contact_information': 'Aucune coordonnée disponible.',
        'owners.no_funds_available': 'Ce propriétaire ne dispose d’aucun fonds disponible pour un versement.',
        'owners.property': 'propriété',
        'owners.properties_lower': 'propriétés',
        'owners.balance': 'solde',
        'owners.pagination_owner': '{total} propriétaire',
        'owners.pagination_owners': '{total} propriétaires',
        'owners.previous': 'Précédent',
        'owners.next': 'Suivant',
        'owners.active': 'Actif',
        'owners.unknown': 'Inconnu',
        'owners.no_building_ownership': 'Aucun immeuble détenu n’a été trouvé.',
        'owners.building': 'Immeuble',
        'owners.units': 'Unités',
        'owners.unit': 'Unité',
        'owners.no_units_created': 'Aucune unité n’a encore été créée.',
        'owners.no_transactions': 'Aucune transaction financière du propriétaire n’a été enregistrée.',
        'owners.receipt': 'Reçu',
        'owners.credit': 'Crédit',
        'owners.debit': 'Débit',
        'owners.reference_short': 'Réf. :',
        'owners.collector_short': 'Caissier :',
        'owners.invoice': 'Facture',
        'owners.page_of': 'Page {current} sur {last}',
        'owners.pagination_transaction': '{total} transaction',
        'owners.pagination_transactions': '{total} transactions',
        'owners.no_payouts': 'Aucun versement n’a été enregistré pour ce propriétaire.',
        'owners.select_owner_first': 'Sélectionnez d’abord un propriétaire.',
        'owners.no_specific_building': 'Aucun immeuble spécifique',
        'owners.select_building': 'Sélectionner un immeuble',
        'owners.no_specific_unit': 'Aucune unité spécifique',
        'owners.select_building_first': 'Sélectionnez d’abord un immeuble',
        'owners.invalid_deposit_amount': 'Saisissez un montant de dépôt valide supérieur à zéro.',
        'owners.collector_required': 'Le caissier n’a pas pu être déterminé pour ce dépôt.',
        'owners.recording': 'Enregistrement…',
        'owners.record_deposit': 'Enregistrer le dépôt',
        'owners.unable_to_record_deposit': 'Impossible d’enregistrer le dépôt du propriétaire.',
        'owners.no_property_for_expense': 'Ce propriétaire ne possède aucun immeuble auquel une dépense peut être imputée.',
        'owners.select_expense_building': 'Sélectionnez l’immeuble auquel la dépense a été imputée.',
        'owners.invalid_expense_amount': 'Saisissez un montant de dépense valide supérieur à zéro.',
        'owners.expense_description_required': 'La description de la dépense est obligatoire.',
        'owners.record_expense': 'Enregistrer la dépense',
        'owners.unable_to_record_expense': 'Impossible d’enregistrer la dépense du propriétaire.',
        'owners.expense_sharing_warning': 'Ce propriétaire détient {percentage} % de cet immeuble. Patrimoine répartira la dépense totale entre tous les propriétaires de l’immeuble selon leurs pourcentages de propriété.',
        'owners.no_payout_funds': 'Ce propriétaire ne dispose actuellement d’aucun fonds disponible pour un versement.',
        'owners.invalid_payout_amount': 'Saisissez un montant de versement valide supérieur à zéro.',
        'owners.payout_exceeds_balance': 'Le retrait ne peut pas dépasser le solde disponible du compte de retrait de {balance}.',
        'owners.processing': 'Traitement…',
        'owners.make_payout': 'Effectuer le versement',
        'owners.unable_to_create_payout': 'Impossible de créer le versement au propriétaire.',
        'owners.invalid_adjustment_amount': 'Saisissez un montant d’ajustement valide supérieur à zéro.',
        'owners.adjustment_reason_required': 'Un motif d’audit est obligatoire pour chaque ajustement manuel.',
        'owners.record_adjustment': 'Enregistrer l’ajustement',
        'owners.unable_to_record_adjustment': 'Impossible d’enregistrer l’ajustement du propriétaire.',
        'owners.unable_to_open_document': 'Impossible d’ouvrir le document.',
        'owners.owner_deposit': 'Dépôt du propriétaire',
        'owners.rent_collected': 'Loyer encaissé',
        'owners.property_expense': 'Dépense immobilière',
        'owners.management_fee': 'Frais de gestion',
        'owners.agent_commission': 'Commission de l’agent',
        'owners.adjustment': 'Ajustement',
        'owners.owner_payout': 'Retrait du propriétaire',
        'owners.transaction': 'Transaction',
        'owners.cash': 'Espèces',
        'owners.bank_transfer': 'Virement bancaire',
        'owners.momo': 'Paiement mobile',
        'owners.cheque': 'Chèque',
        'owners.general_funding': 'Financement général',
        'owners.repair_maintenance': 'Réparation / Entretien',
        'owners.other': 'Autre',
        'owners.unnamed_owner': 'Propriétaire sans nom',
        'owners.loading': 'Chargement des propriétaires…',
        'owners.loading_details': 'Chargement des détails du propriétaire…',


        'reports.title': 'Rapports — Patrimoine',
        'reports.finance': 'Finance',
        'reports.heading': 'Rapports',
        'reports.page_description': 'Consultez les rapports financiers et opérationnels relatifs aux propriétaires, locataires et propriétés.',
        'reports.report_type': 'Type de rapport',
        'reports.report_type_description': 'Sélectionnez le rapport que vous souhaitez consulter.',
        'reports.managing_organisation': 'Organisation gestionnaire',
        'reports.managing_organisation_summary': "Résumé opérationnel et financier de l'ensemble du portefeuille.",
        'reports.owner_report_summary': 'Solde, crédits, débits et historique du grand livre du propriétaire.',
        'reports.building_report_summary': 'Facturation, encaissements, dépenses et propriété.',
        'reports.unit_report_summary': "Historique des baux, de la facturation et des encaissements d'une unité.",
        'reports.tenant_statement_summary': 'Facturation, paiements et fonds détenus du locataire.',
        'reports.change': 'Modifier',
        'reports.period_description': "Laissez les dates vides pour inclure tout l'historique disponible.",
        'reports.from': 'Du',
        'reports.to': 'Au',
        'reports.run_report': 'Générer le rapport',
        'reports.pdf': 'PDF',
        'reports.csv': 'CSV',
        'reports.xlsx': 'XLSX',
        'reports.initial_prompt': 'Sélectionnez un type de rapport puis générez le rapport.',
        'reports.not_tenant': "La partie sélectionnée n'est pas un locataire.",
        'reports.unable_to_open_tenant_statement': "Impossible d'ouvrir le relevé du locataire.",
        'reports.property_owner': 'Propriétaire',
        'reports.search_owner_placeholder': 'Rechercher un propriétaire par nom, téléphone ou e-mail...',
        'reports.tenant': 'Locataire',
        'reports.search_tenant_placeholder': 'Rechercher un locataire par nom, téléphone ou e-mail...',
        'reports.building': 'Immeuble',
        'reports.search_building_placeholder': 'Rechercher un immeuble...',
        'reports.unit': 'Unité',
        'reports.search_unit_placeholder': 'Rechercher une unité...',
        'reports.search': 'Rechercher',
        'reports.search_placeholder': 'Rechercher...',
        'reports.managing_organisation_report': "Rapport de l'organisation gestionnaire",
        'reports.managing_organisation_description': "Rapport financier et opérationnel de l'ensemble du portefeuille.",
        'reports.owner_report': 'Rapport du propriétaire',
        'reports.owner_report_description': 'Situation financière consolidée et grand livre du propriétaire.',
        'reports.building_report': "Rapport de l'immeuble",
        'reports.building_report_description': "Facturation, encaissements, dépenses et propriété d'un immeuble.",
        'reports.unit_report': "Rapport de l'unité",
        'reports.unit_report_description': "Historique des baux et des finances d'une unité.",
        'reports.tenant_statement': 'Relevé du locataire',
        'reports.tenant_statement_description': "Facturation, paiements et fonds détenus pour un locataire.",
        'reports.report': 'Rapport',
        'reports.searching': 'Recherche…',
        'reports.unable_to_search': 'Impossible d’effectuer la recherche.',
        'reports.subject_not_required': "Ce rapport ne nécessite pas de sujet.",
        'reports.no_matching_records': 'Aucun enregistrement correspondant trouvé.',
        'reports.invalid_period': 'La date de fin du rapport doit être égale ou postérieure à la date de début.',
        'reports.select_subject_first': "Sélectionnez d'abord le sujet du rapport.",
        'reports.unable_to_generate': 'Impossible de générer le rapport.',
        'reports.buildings': 'Immeubles',
        'reports.units': 'Unités',
        'reports.owner_accounts': 'Comptes propriétaires',
        'reports.cash_received': 'Encaissements reçus',
        'reports.billing': 'Facturation',
        'reports.total_invoiced': 'Total facturé',
        'reports.rent_invoiced': 'Loyer facturé',
        'reports.security_deposit_debt_invoiced': 'Dette de dépôt de garantie facturée',
        'reports.settled': 'Réglé',
        'reports.rent_outstanding': 'Loyer impayé',
        'reports.security_deposit_debt_outstanding': 'Dette de dépôt de garantie impayée',
        'reports.total_outstanding': 'Total impayé',
        'reports.owner_accounting': 'Comptabilité des propriétaires',
        'reports.rent_entitlement': 'Droit au loyer',
        'reports.management_fees': 'Frais de gestion',
        'reports.agent_commissions': "Commissions d'agent",
        'reports.owner_expenses': 'Dépenses du propriétaire',
        'reports.owner_payouts': 'Versements aux propriétaires',
        'reports.owner_funds_held': 'Fonds des propriétaires détenus',
        'reports.tenant_funds': 'Fonds des locataires',
        'reports.rent_reserve': 'Réserve de loyer',
        'reports.consumable_advance': 'Avance consommable',
        'reports.security_deposit': 'Dépôt de garantie',
        'reports.opening_balance': "Solde d'ouverture",
        'reports.credits': 'Crédits',
        'reports.debits': 'Débits',
        'reports.closing_balance': 'Solde de clôture',
        'reports.financial_summary': 'Résumé financier',
        'reports.rent_collected': 'Loyer encaissé',
        'reports.owner_deposits': 'Dépôts du propriétaire',
        'reports.property_expenses': 'Dépenses immobilières',
        'reports.payouts': 'Versements',
        'reports.adjustments_credit': 'Ajustements créditeurs',
        'reports.adjustments_debit': 'Ajustements débiteurs',
        'reports.transactions': 'Transactions',
        'reports.leases': 'Baux',
        'reports.security_deposit_debt': 'Dette de dépôt de garantie',
        'reports.invoice_settled': 'Factures réglées',
        'reports.owner_rent_entitlement': 'Droit au loyer du propriétaire',
        'reports.ownership': 'Propriété',
        'reports.expenses': 'Dépenses',
        'reports.lease_history': 'Historique des baux',
        'reports.invoices': 'Factures',
        'reports.receivables': 'Créances',
        'reports.held_funds': 'Fonds détenus',
        'reports.payments': 'Paiements',
        'reports.payments_report_summary': 'Historique des paiements des locataires et des reçus.',
        'reports.payments_report': 'Rapport des paiements',
        'reports.payments_report_description': 'Historique en lecture seule des paiements des locataires avec accès aux reçus.',
        'reports.payment_filters': 'Filtres des paiements',
        'reports.payment_filters_description': 'Laissez un filtre vide pour inclure tous les enregistrements correspondants.',
        'reports.all_tenants': 'Tous les locataires',
        'reports.lease': 'Bail',
        'reports.all_leases': 'Tous les baux',
        'reports.all_buildings': 'Tous les immeubles',
        'reports.all_units': 'Toutes les unités',
        'reports.all_payment_methods': 'Tous les modes de paiement',
        'reports.payment_method_label': 'Mode de paiement',
        'reports.payment_method_cash': 'Espèces',
        'reports.payment_method_bank': 'Banque',
        'reports.payment_method_mobile': 'Paiement mobile',
        'reports.payment_method_cheque': 'Chèque',
        'reports.cash_receiver': 'Caissier',
        'reports.cash_receiver_placeholder': 'Rechercher le caissier',
        'reports.payment_reference': 'Paiement / Référence',
        'reports.payment_reference_placeholder': 'ID du paiement ou référence',
        'reports.payment_count': 'Paiements',
        'reports.total_received': 'Total reçu',
        'reports.payment_number': 'Paiement',
        'reports.property': 'Bien',
        'reports.receipt': 'Reçu',
        'reports.no_payments_found': 'Aucun paiement ne correspond aux filtres sélectionnés.',
        'reports.unable_to_load_payment_filters': 'Impossible de charger les filtres du rapport des paiements.',
        'reports.reporting_period': 'Période du rapport',
        'reports.reporting_period_all_history': "Période du rapport : tout l'historique disponible",
        'reports.beginning': 'Début',
        'reports.present': 'Présent',
        'reports.no_records_section': 'Aucun enregistrement pour cette section.',
        'reports.date': 'Date',
        'reports.direction': 'Sens',
        'reports.category': 'Catégorie',
        'reports.amount': 'Montant',
        'reports.invoice': 'Facture',
        'reports.reference': 'Référence',
        'reports.owner': 'Propriétaire',
        'reports.description': 'Description',
        'reports.start': 'Début',
        'reports.end': 'Fin',
        'reports.status': 'Statut',
        'reports.rent': 'Loyer',
        'reports.frequency': 'Fréquence',
        'reports.type': 'Type',
        'reports.issue_date': "Date d'émission",
        'reports.due_date': "Date d'échéance",
        'reports.paid': 'Payé',
        'reports.outstanding': 'Impayé',
        'reports.method': 'Mode',
        'reports.allocated': 'Affecté',
        'reports.unallocated': 'Non affecté',
        'reports.generating': 'Génération du rapport…',
        'reports.could_not_generate': "Le rapport n'a pas pu être généré.",
        'reports.unable_to_open': "Impossible d'ouvrir le rapport.",
        'reports.unable_to_download': 'Impossible de télécharger le rapport.',
        'reports.unnamed_party': 'Partie sans nom',
        'reports.building_number': 'Immeuble n° :number',
        'reports.unit_number': 'Unité n° :number',

        'reports.direction.credit': 'Crédit',
        'reports.direction.debit': 'Débit',

        'reports.category.rent_entitlement': 'Droit au loyer',
        'reports.category.owner_deposit': 'Dépôt du propriétaire',
        'reports.category.management_fee': 'Frais de gestion',
        'reports.category.agent_commission': "Commission d'agent",
        'reports.category.expense': 'Dépense',
        'reports.category.owner_expense': 'Dépense du propriétaire',
        'reports.category.payout': 'Versement',
        'reports.category.owner_payout': 'Versement au propriétaire',
        'reports.category.adjustment': 'Ajustement',
        'reports.category.adjustment_credit': 'Ajustement créditeur',
        'reports.category.adjustment_debit': 'Ajustement débiteur',

        'reports.status.active': 'Actif',
        'reports.status.inactive': 'Inactif',
        'reports.status.ended': 'Terminé',
        'reports.status.terminated': 'Résilié',
        'reports.status.draft': 'En cours',
        'reports.status.issued': 'Émise',
        'reports.status.pending': 'En attente',
        'reports.status.partial': 'Partiel',
        'reports.status.partially_paid': 'Partiellement payée',
        'reports.status.paid': 'Payée',
        'reports.status.settled': 'Réglée',
        'reports.status.overdue': 'En retard',
        'reports.status.cancelled': 'Annulée',
        'reports.status.void': 'Annulée',

        'reports.frequency.monthly': 'Mensuel',
        'reports.frequency.quarterly': 'Trimestriel',
        'reports.frequency.bi_yearly': 'Semestriel',
        'reports.frequency.biyearly': 'Semestriel',
        'reports.frequency.yearly': 'Annuel',
        'reports.frequency.annual': 'Annuel',

        'reports.invoice_type.rent': 'Loyer',
        'reports.invoice_type.security_deposit_debt': 'Dette de dépôt de garantie',

        'reports.payment_method.cash': 'Espèces',
        'reports.payment_method.bank_transfer': 'Virement bancaire',
        'reports.payment_method.momo': 'Paiement mobile',
        'reports.payment_method.cheque': 'Chèque',

        'tenants.title': 'Locataires — Patrimoine',
        'tenants.finance': 'Finance',
        'tenants.heading': 'Locataires',
        'tenants.page_description': 'Consultez l’identité du locataire, ses coordonnées et l’historique de ses baux.',
        'tenants.directory': 'Locataires',
        'tenants.search_description': 'Recherchez par nom, téléphone ou e-mail du locataire.',
        'tenants.search': 'Rechercher des locataires',
        'tenants.search_placeholder': 'Rechercher des locataires...',
        'tenants.select_tenant': 'Sélectionner un locataire',
        'tenants.select_tenant_description': 'Choisissez un locataire pour consulter ses informations et ses baux.',
        'tenants.no_tenant_available': "Aucun locataire n'est disponible à afficher.",
        'tenants.unable_to_load': 'Impossible de charger les locataires.',
        'tenants.pagination_tenant': ':total locataire',
        'tenants.pagination_tenants': ':total locataires',
        'tenants.no_search_results': 'Aucun locataire ne correspond à votre recherche.',
        'tenants.not_tenant': "La partie sélectionnée n'est pas un locataire.",
        'tenants.unable_to_load_details': 'Impossible de charger les détails du locataire.',
        'tenants.unable_to_load_tenant': 'Impossible de charger ce locataire.',
        'tenants.no_contact_information': 'Aucune coordonnée disponible.',
        'tenants.tenant_statement': 'Relevé du locataire',
        'tenants.total_leases': 'Total des baux',
        'tenants.current_leases': 'Baux en cours',
        'tenants.historical_leases': 'Baux historiques',
        'tenants.tenant_details': 'Détails du locataire',
        'tenants.party_type': 'Type de partie',
        'tenants.party_type.person': 'Personne',
        'tenants.party_type.organisation': 'Organisation',
        'tenants.party_type.organization': 'Organisation',
        'tenants.party_type.association': 'Association',

        'tenants.payment_method.cash': 'Espèces',
        'tenants.payment_method.bank_transfer': 'Virement bancaire',
        'tenants.payment_method.momo': 'Paiement mobile',
        'tenants.payment_method.cheque': 'Chèque',
        'tenants.apply_security_deposit': 'Affecter le dépôt de garantie',
        'tenants.apply_security_deposit_description': 'Affectez le dépôt de garantie détenu à une créance impayée du bail.',
        'tenants.security_deposit_available': 'Dépôt de garantie disponible',
        'tenants.receivable': 'Créance',
        'tenants.select_receivable': 'Sélectionner une créance…',
        'tenants.receivable_outstanding': 'Créance impayée',
        'tenants.resulting_security_deposit': 'Dépôt de garantie restant',
        'tenants.resulting_receivable': 'Créance restante',
        'tenants.security_application_not_available': 'Le dépôt de garantie ne peut pas être affecté pour ce bail actuellement.',
        'tenants.security_application_recorded': 'Dépôt de garantie affecté avec succès.',
        'tenants.security_application_exceeds_deposit': 'Le montant ne peut pas dépasser le solde disponible du dépôt de garantie.',
        'tenants.security_application_exceeds_receivable': 'Le montant ne peut pas dépasser le solde impayé de la créance sélectionnée.',
        'tenants.invoice_type.rent': 'Loyer',
        'tenants.invoice_type.security_deposit_debt': 'Dette de dépôt de garantie',
        'tenants.payment_method.mobile_payment': 'Paiement mobile',

        'tenants.fund_type.rent_reserve': 'Réserve de loyer',
        'tenants.fund_type.consumable_advance': 'Avance consommable',
        'tenants.fund_type.security_deposit': 'Dépôt de garantie',

        'tenants.lease_status.draft': 'En cours',
        'tenants.lease_status.active': 'Actif',
        'tenants.lease_status.notice': 'Préavis',
        'tenants.lease_status.terminated': 'Résilié',
        'tenants.lease_status.expired': 'Expiré',
        'tenants.lease_status.cancelled': 'Annulé',

        'tenants.direction.credit': 'Crédit',
        'tenants.direction.debit': 'Débit',

        'tenants.category.reserve_funding': 'Alimentation de la réserve',
        'tenants.category.advance_funding': "Alimentation de l’avance",
        'tenants.category.security_deposit_funding': 'Alimentation du dépôt de garantie',
        'tenants.category.rent_consumption': 'Consommation de la réserve de loyer',
        'tenants.category.advance_consumption': "Consommation de l’avance",
        'tenants.category.security_deposit_deduction': 'Déduction du dépôt de garantie',
        'tenants.category.security_deposit_refund': 'Remboursement du dépôt de garantie',
        'tenants.phone': 'Téléphone',
        'tenants.alternate_phone': 'Téléphone secondaire',
        'tenants.email': 'E-mail',
        'tenants.address': 'Adresse',
        'tenants.id_registration': 'Pièce d’identité / Immatriculation',
        'tenants.leases': 'Baux',
        'tenants.leases_description': 'Relations de bail actuelles et historiques de ce locataire.',
        'tenants.financial_position': 'Situation financière',
        'tenants.financial_position_description': 'Créances impayées et fonds détenus pour le locataire sur l’ensemble des baux.',
        'tenants.rent_outstanding': 'Loyer impayé',
        'tenants.security_deposit_debt': 'Dette de dépôt de garantie',
        'tenants.total_outstanding': 'Total impayé',
        'tenants.held_funds': 'Fonds détenus',
        'tenants.rent_reserve': 'Réserve de loyer',
        'tenants.consumable_advance': 'Avance consommable',
        'tenants.security_deposit': 'Dépôt de garantie',
        'tenants.invoices': 'Factures',
        'tenants.invoices_description': 'Historique de facturation pour les baux de ce locataire.',
        'tenants.no_invoices': "Aucune facture n'a été enregistrée pour ce locataire.",
        'tenants.invoice': 'Facture',
        'tenants.type': 'Type',
        'tenants.date': 'Date',
        'tenants.due_date': 'Date d’échéance',
        'tenants.amount': 'Montant',
        'tenants.paid': 'Payé',
        'tenants.outstanding': 'Impayé',
        'tenants.status': 'Statut',
        'tenants.actions': 'Actions',
        'tenants.resend': 'Renvoyer',
        'tenants.opening': 'Ouverture…',
        'tenants.unable_to_open_invoice': "Impossible d'ouvrir la facture.",
        'tenants.sending': 'Envoi…',
        'tenants.sent': 'Envoyé',
        'tenants.unable_to_resend_invoice': 'Impossible de renvoyer la facture.',
        'tenants.payments': 'Paiements',
        'tenants.payments_description': 'Historique des encaissements et des affectations pour les baux de ce locataire.',
        'tenants.no_payments': "Aucun paiement n'a été enregistré pour ce locataire.",
        'tenants.method': 'Mode',
        'tenants.reference': 'Référence',
        'tenants.allocated': 'Affecté',
        'tenants.unallocated': 'Non affecté',
        'tenants.receipt': 'Reçu',
        'tenants.unable_to_open_receipt': "Impossible d'ouvrir le reçu.",
        'tenants.unable_to_resend_receipt': 'Impossible de renvoyer le reçu.',
        'tenants.fund_history': 'Historique des fonds',
        'tenants.fund_history_description': 'Historique des mouvements de la réserve de loyer, de l’avance consommable et du dépôt de garantie.',
        'tenants.no_fund_transactions': "Aucun mouvement de fonds du locataire n'a été enregistré.",
        'tenants.fund': 'Fonds',
        'tenants.direction': 'Sens',
        'tenants.category': 'Catégorie',
        'tenants.source': 'Source',
        'tenants.payment_number': 'Paiement n° :number',
        'tenants.invoice_number': 'Facture n° :number',
        'tenants.ledger': 'Grand livre',
        'tenants.no_leases': "Aucun bail n'a été enregistré pour ce locataire.",
        'tenants.building': 'Immeuble',
        'tenants.unit': 'Unité',
        'tenants.unnamed_tenant': 'Locataire sans nom',
        'tenants.lease_ongoing': ':start → en cours',
        'tenants.lease_dates_unavailable': 'Dates du bail indisponibles',
        'tenants.previous': 'Précédent',
        'tenants.next': 'Suivant',
        'tenants.loading': 'Chargement des locataires…',
        'tenants.loading_details': 'Chargement des détails du locataire…',
        'tenants.deposit': 'Dépôt',
        'tenants.deposit_description': 'Enregistrez les fonds reçus du locataire sélectionné.',
        'tenants.withdrawal': 'Retrait',
        'tenants.withdrawal_description': 'Versez au locataire sélectionné les fonds disponibles qui lui sont dus.',
        'tenants.adjustment': 'Ajustement',
        'tenants.adjustment_description': 'Corrigez un compte financier du locataire au solde qui devrait exister.',
        'tenants.adjustment_warning': 'Utilisez l’ajustement uniquement pour les corrections comptables. Les encaissements et versements normaux doivent utiliser Dépôt ou Retrait.',
        'tenants.transaction_context': 'Contexte de la transaction',
        'tenants.lease': 'Bail',
        'tenants.select_lease': 'Sélectionner un bail…',
        'tenants.select_lease_first': 'Sélectionnez d’abord un bail',
        'tenants.lease_first_help': 'Le dépôt et le retrait sont enregistrés pour un bail spécifique.',
        'tenants.destination': 'Destination',
        'tenants.account': 'Compte',
        'tenants.select_account': 'Sélectionner un compte…',
        'tenants.current_balance': 'Solde actuel',
        'tenants.transaction_amount': 'Montant de la transaction',
        'tenants.payment_method_label': 'Mode de paiement',
        'tenants.resulting_balance': 'Solde résultant',
        'tenants.correct_balance': 'Solde correct',
        'tenants.calculated_adjustment': 'Ajustement calculé',
        'tenants.payment_method': 'Mode de paiement',
        'tenants.cash_receiver': 'Caissier',
        'tenants.cash_receiver_automatic': 'Défini automatiquement sur l’utilisateur connecté',
        'tenants.cash_receiver_help': 'Pour les espèces, l’utilisateur connecté est automatiquement enregistré comme caissier et ne peut pas être modifié.',
        'tenants.transaction_date': 'Date de la transaction',
        'tenants.reference': 'Référence',
        'tenants.notes': 'Notes',
        'tenants.optional': '(Facultatif)',
        'tenants.reason': 'Motif',
        'tenants.adjustment_reason_placeholder': 'Expliquez pourquoi le solde de ce compte doit être corrigé…',
        'tenants.cancel': 'Annuler',
        'tenants.close': 'Fermer',
        'tenants.rent_payment': 'Paiement du loyer',
        'tenants.no_eligible_accounts': 'Aucune destination admissible n’est disponible pour ce bail.',
        'tenants.no_withdrawable_funds': 'Ce bail ne dispose d’aucun fonds locataire pouvant être retiré.',
        'tenants.unable_to_load_accounts': 'Impossible de charger les comptes financiers du locataire.',
        'tenants.select_lease_context': 'Sélectionnez un bail ou un compte pour afficher le contexte immeuble et unité.',
        'tenants.transaction_required_fields': 'Renseignez tous les champs obligatoires de la transaction.',
        'tenants.adjustment_required_fields': 'Sélectionnez un compte, saisissez le solde correct et indiquez un motif.',
        'tenants.withdrawal_exceeds_balance': 'Le retrait ne peut pas dépasser le solde disponible.',
        'tenants.invalid_account': 'Le compte sélectionné n’est pas valide pour cette transaction.',
        'tenants.transaction_failed': 'Impossible d’effectuer la transaction.',
        'tenants.rent_payment_recorded': 'Paiement du loyer enregistré avec succès.',
        'tenants.deposit_recorded': 'Dépôt enregistré avec succès.',
        'tenants.withdrawal_recorded': 'Retrait enregistré avec succès.',
        'tenants.adjustment_recorded': 'Ajustement enregistré avec succès.',



        'owners.title': 'Propriétaires — Patrimoine',
        'owners.finance': 'Finance',
        'owners.heading': 'Propriétaires',
        'owners.page_description': 'Consultez la propriété des biens, les soldes des propriétaires, les transactions, les dépôts et les versements.',
        'owners.property_owners': 'Propriétaires',
        'owners.search_description': 'Recherchez par nom, téléphone ou e-mail du propriétaire.',
        'owners.search_property_owners': 'Rechercher des propriétaires',
        'owners.search_placeholder': 'Rechercher des propriétaires...',
        'owners.select_property_owner': 'Sélectionner un propriétaire',
        'owners.select_owner_description': 'Choisissez un propriétaire dans le répertoire pour consulter ses propriétés, son solde et son historique financier.',
        'owners.deposit': 'Dépôt',
        'owners.expense': 'Dépense',
        'owners.payout': 'Retrait',
        'owners.owner_report': 'Rapport du propriétaire',
        'owners.current_balance': 'Solde actuel',
        'owners.account': 'Compte',
        'owners.accounts_breakdown': 'Comptes',
        'owners.accounts_breakdown_description': 'Chaque catégorie du grand livre consolidé de ce propriétaire et son effet sur le solde.',
        'owners.payout_account_balance': 'Compte de retrait (loyers)',
        'owners.deposit_account_balance': 'Compte de dépôt / dépenses',
        'owners.reserve_transfer': 'Transfert entre comptes',
        'owners.transfer': 'Transférer',
        'owners.management_fee_vat': 'TVA sur frais de gestion',
        'owners.statement': 'Relevé',
        'owners.statement_title': 'Relevé du propriétaire',
        'owners.statement_description': 'Loyers encaissés, dépenses, frais et TVA sur une période, et le solde à reverser.',
        'owners.statement_from': 'Du',
        'owners.statement_to': 'Au',
        'owners.statement_generate': 'Générer',
        'owners.statement_since_payout': 'Prérempli à partir du lendemain du dernier reversement du :date. Modifiez les dates si vous souhaitez une autre période.',
        'owners.statement_no_payout': 'Ce propriétaire n\'a encore reçu aucun reversement : le relevé couvre donc tout l\'historique.',
        'owners.unable_to_open_statement': 'Le relevé n\'a pas pu être généré.',
        'owners.transfer_title': 'Transfert entre comptes',
        'owners.transfer_description': 'Déplacez des fonds entre le compte de retrait et le compte de dépôt / dépenses de ce propriétaire.',
        'owners.transfer_direction': 'Sens',
        'owners.transfer_to_expense': 'Compte de retrait → Compte de dépôt / dépenses',
        'owners.transfer_to_payout': 'Compte de dépôt / dépenses → Compte de retrait',
        'owners.transfer_available': 'Disponible sur le compte source',
        'owners.transfer_reason': 'Motif',
        'owners.transfers': 'Transferts entre comptes',
        'owners.review': 'Vérifier',
        'owners.back': 'Retour',
        'owners.confirm': 'Confirmer',
        'owners.building': 'Immeuble',
        'owners.select_building': 'Sélectionner un immeuble…',
        'owners.building_required': 'Sélectionnez l’immeuble auquel cette dépense se rapporte.',
        'owners.billing_mode': 'Facturation',
        'owners.billing_mode_single': 'Facturer uniquement ce propriétaire',
        'owners.billing_mode_split': 'Répartir entre tous les propriétaires selon leurs parts',
        'owners.expense_review_title': 'Vérifier cette dépense',
        'owners.split_preview_title': 'Part par propriétaire',
        'owners.expense_review_description': 'Rien n’est enregistré avant votre confirmation. Chaque propriétaire facturé reçoit la facture détaillée par e-mail.',
        'owners.transfers_description': 'Mouvements entre le compte de retrait et le compte de dépôt / dépenses, avec leurs bons officiels.',
        'owners.no_transfers': 'Aucun transfert entre comptes pour le moment.',
        'owners.invalid_transfer_amount': 'Saisissez un montant de transfert supérieur à zéro.',
        'owners.unable_to_transfer': "Impossible d'enregistrer le transfert entre comptes.",
        'owners.voucher': 'Bon',
        'owners.resend': 'Renvoyer',
        'owners.sending': 'Envoi…',
        'owners.sent': 'Envoyé',
        'owners.unable_to_open_voucher': "Impossible d'ouvrir le bon de transfert.",
        'owners.unable_to_resend_voucher': 'Impossible de renvoyer le bon de transfert.',
        'owners.total_credits': 'Total des crédits',
        'owners.total_debits': 'Total des débits',
        'owners.properties': 'Propriétés',
        'owners.properties_description': 'Immeubles détenus par cette partie, y compris les propriétés vacantes.',
        'owners.owner_ledger': 'Grand livre du propriétaire',
        'owners.ledger_description': 'Ensemble des mouvements financiers auditables affectant le compte consolidé du propriétaire.',
        'owners.payout_history': 'Historique des retraits',
        'owners.payout_history_description': 'Fonds précédemment retirés par ce propriétaire.',
        'owners.record_owner_deposit': 'Enregistrer un dépôt du propriétaire',
        'owners.deposit_description': 'Enregistrez les fonds reçus de ce propriétaire.',
        'owners.amount': 'Montant',
        'owners.deposit_date': 'Date du dépôt',
        'owners.payment_method': 'Mode de paiement',
        'owners.deposit_purpose': 'Objet du dépôt',
        'owners.reference': 'Référence',
        'owners.collector': 'Caissier',
        'owners.notes': 'Notes',
        'owners.optional': '(Facultatif)',
        'owners.cancel': 'Annuler',
        'owners.close': 'Fermer',
        'owners.record_property_expense': 'Enregistrer une dépense immobilière',
        'owners.expense_description': 'Enregistrez une dépense sur l’une des propriétés de ce propriétaire.',
        'owners.description': 'Description',
        'owners.expense_date': 'Date de la dépense',
        'owners.expense_description_placeholder': 'ex. réparation de climatiseur',
        'owners.make_owner_payout': 'Effectuer un versement au propriétaire',
        'owners.payout_description': 'Retirez les fonds disponibles issus des loyers pour le propriétaire sélectionné.',
        'owners.available_owner_balance': 'Solde disponible du propriétaire',
        'owners.payout_date': 'Date du retrait',
        'owners.owner_account_adjustment': 'Ajustement du compte propriétaire',
        'owners.adjustment_description': 'Enregistrez une correction comptable manuelle exceptionnelle.',
        'owners.adjustment_warning': 'Les ajustements doivent uniquement servir aux corrections comptables. Les dépôts, dépenses et versements normaux doivent utiliser leurs actions dédiées.',
        'owners.direction': 'Sens',
        'owners.credit_increase_balance': 'Crédit — Augmenter le solde du propriétaire',
        'owners.debit_reduce_balance': 'Débit — Réduire le solde du propriétaire',
        'owners.adjustment_date': 'Date de l’ajustement',
        'owners.reason': 'Motif',
        'owners.adjustment_reason_placeholder': 'Expliquez pourquoi cet ajustement manuel est nécessaire...',
        'owners.repair_maintenance_static': 'Réparation et entretien',
        'payments.title': 'Paiements — Patrimoine',
        'payments.finance': 'Finance',
        'payments.heading': 'Paiements',
        'payments.page_description': 'Enregistrez et consultez les sommes reçues des locataires et des propriétaires.',
        'payments.received_this_month': 'Reçu ce mois-ci',
        'payments.tenant_payments': 'Paiements des locataires',
        'payments.owner_deposits': 'Dépôts des propriétaires',
        'payments.transactions': 'Opérations',
        'payments.register': 'Registre des paiements',
        'payments.register_description': 'Paiements entrants enregistrés dans Patrimoine.',
        'payments.payment_source': 'Source du paiement',
        'payments.all_sources': 'Toutes les sources',
        'payments.payment_method': 'Mode de paiement',
        'payments.all_methods': 'Tous les modes',
        'payments.from_date_label': 'Date de début',
        'payments.to_date': 'Date de fin',
        'payments.record_description': 'Enregistrez une somme reçue d’un locataire ou d’un propriétaire.',
        'payments.close': 'Fermer',
        'payments.source_description': 'Sélectionnez la personne ayant fourni les fonds.',
        'payments.tenant_payment_description': 'Loyer, arriérés ou autres sommes liées au bail reçues d’un locataire.',
        'payments.property_owner': 'Propriétaire',
        'payments.owner_payment_description': 'Fonds fournis par un propriétaire pour des dépenses immobilières, des réparations ou un financement général.',
        'payments.tenant_search_description': 'Recherchez le locataire plutôt que de le sélectionner dans une liste fixe.',
        'payments.search_tenant': 'Rechercher un locataire',
        'payments.search_party_placeholder': 'Rechercher par nom, téléphone ou e-mail...',
        'payments.change': 'Modifier',
        'payments.lease_property': 'Bail / Propriété',
        'payments.owner_search_description': 'Recherchez le propriétaire dont le compte doit recevoir le dépôt.',
        'payments.search_owner': 'Rechercher un propriétaire',
        'payments.current_owner_balance': 'Solde actuel du propriétaire :',
        'payments.deposit_purpose': 'Objet du dépôt',
        'payments.repair_maintenance_static': 'Réparation et entretien',
        'payments.building': 'Immeuble',
        'payments.unit': 'Unité',
        'payments.optional': '(Facultatif)',
        'payments.payment_details': 'Détails du paiement',
        'payments.amount': 'Montant',
        'payments.payment_date': 'Date du paiement',
        'payments.reference_label': 'Référence',
        'payments.reference_placeholder': 'Référence de l’opération ou du dépôt',
        'payments.collector_placeholder': 'Défini automatiquement selon l’utilisateur connecté',
        'payments.collector_help': 'Défini automatiquement selon l’utilisateur connecté pour les paiements en espèces.',
        'payments.notes': 'Notes',
        'payments.cancel': 'Annuler',
        'payments.manage_funds_description': 'Classez les fonds non affectés du locataire dans les fonds détenus.',
        'payments.loading_position': 'Chargement de la situation du paiement…',
        'payments.received': 'Reçu',
        'payments.allocated_to_invoices': 'Affecté aux factures',
        'payments.unapplied': 'Non affecté',
        'payments.classified': 'Classé',
        'payments.available': 'Disponible',
        'payments.no_money_remaining': 'Ce paiement ne contient plus de fonds à classer.',
        'payments.classify_remaining_money': 'Classer les fonds restants',
        'payments.classify_description': 'Transférez les fonds non affectés du paiement vers un fonds dédié détenu pour le locataire.',
        'payments.fund': 'Fonds',
        'payments.select_fund': 'Sélectionner un fonds…',
        'payments.rent_reserve': 'Réserve de loyer',
        'payments.consumable_advance': 'Avance consommable',
        'payments.security_deposit': 'Dépôt de garantie',
        'payments.transaction_date': 'Date de l’opération',
        'payments.optional_placeholder': 'Facultatif',
        'payments.classification_notes_placeholder': 'Notes facultatives sur le classement',
        'parties.saving_changes': 'Enregistrement des modifications…',
        'parties.creating_party': 'Création de la partie…',
        'parties.unable_to_update_party': 'Impossible de mettre à jour la partie.',
        'parties.unable_to_create_party': 'Impossible de créer la partie.',
        'parties.delete_confirmation': 'Supprimer « {{name}} » ?',
        'parties.this_party': 'cette partie',
        'parties.delete_restriction': 'Seule une partie sans référence peut être supprimée. Les parties utilisées par des baux, des propriétés, des mandats d’agence ou un historique financier doivent être conservées.',
        'parties.unable_to_delete_party': 'Impossible de supprimer la partie.',

        'parties.title': 'Parties — Patrimoine',
        'parties.contacts_stakeholders': 'Contacts et parties prenantes',
        'parties.heading': 'Parties',
        'parties.page_description': 'Gérez les propriétaires, locataires, agents, organisations et associations.',
        'parties.total_parties': 'Total des parties',
        'parties.people': 'Personnes',
        'parties.organisations': 'Organisations',
        'parties.multiple_roles': 'Rôles multiples',
        'parties.directory': 'Répertoire des parties',
        'parties.directory_description': 'Personnes et entités participant aux opérations immobilières.',
        'parties.search': 'Rechercher des parties',
        'parties.search_placeholder': 'Rechercher par nom, e-mail, téléphone...',
        'parties.party_type': 'Type de partie',
        'parties.party_role': 'Rôle de la partie',
        'parties.all_types': 'Tous les types',
        'parties.associations': 'Associations',
        'parties.all_roles': 'Tous les rôles',
        'parties.owners': 'Propriétaires',
        'parties.tenants': 'Locataires',
        'parties.agents': 'Agents',
        'parties.close': 'Fermer',
        'parties.party_type_description': 'Sélectionnez la nature juridique de cette partie.',
        'parties.personal_details': 'Informations personnelles',
        'parties.organisation_details': 'Informations sur l’organisation',
        'parties.contact_identification': 'Contact et identification',
        'parties.contact_identification_description': 'Coordonnées secondaires et informations d’identification facultatives.',
        'parties.roles': 'Rôles',
        'parties.roles_description': 'Une partie peut remplir plusieurs fonctions simultanément.',
        'parties.banking_details': 'Coordonnées bancaires',
        'parties.banking_description': 'Facultatif. Principalement utilisé pour les propriétaires et les agents.',
        'parties.full_name': 'Nom complet',
        'parties.phone': 'Téléphone',
        'parties.email': 'E-mail',
        'parties.legal_name': 'Raison sociale',
        'parties.contact_person': 'Personne de contact',
        'parties.contact_phone': 'Téléphone du contact',
        'parties.contact_email': 'E-mail du contact',
        'parties.alternate_phone': 'Téléphone secondaire',
        'parties.id_number': 'Numéro d’identification',
        'parties.registration_number': 'Numéro d’enregistrement',
        'parties.vat_tin': 'TVA / NIF',
        'parties.address': 'Adresse',
        'parties.bank_name': 'Nom de la banque',
        'parties.bank_branch': 'Agence bancaire',
        'parties.account_name': 'Nom du compte',
        'parties.account_number': 'Numéro de compte',
        'parties.notes': 'Notes',
        'parties.notes_placeholder': 'Notes internes facultatives',
        'parties.cancel': 'Annuler',
    },
};

/**
 * Resolve a translation from the requested language with English fallback.
 *
 * @param {string} language
 * @param {string} key
 * @returns {string}
 */
export function translationFor(
    language,
    key,
    replacements = {}
) {
    const english =
        translations.en
        || {};

    const catalogue =
        translations[language]
        || english;

    const template =
        catalogue[key]
        ?? english[key]
        ?? key;

    /*
     * Longest name first.
     *
     * ':to' is a prefix of ':total', so replacing in the order the caller
     * happened to write them turned "Showing :from–:to of :total" into
     * "Showing 1–25 of 25tal". Laravel's own translator sorts for the
     * same reason; this is that rule, on this side of the wire.
     */
    return Object
        .entries(
            replacements
            || {}
        )
        .sort(
            (
                [first],
                [second]
            ) => second.length - first.length
        )
        .reduce(
            (
                translated,
                [
                    name,
                    value,
                ]
            ) => {
                const replacement =
                    String(
                        value
                        ?? ''
                    );

                return translated
                    .replaceAll(
                        `{${name}}`,
                        replacement
                    )
                    .replaceAll(
                        `:${name}`,
                        replacement
                    );
            },
            String(
                template
            )
        );
}

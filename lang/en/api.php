<?php

return [
    'documents' => [
        'not_signable' => 'This document cannot be opened through a signed link.',
        'link_invalid' => 'This document link is invalid or has expired. Please open the document again.',
    ],

    'not_found' => 'That record could not be found. It may have been deleted, or it may belong to another organisation.',

    'auth' => [
        'password_confirmation_failed' => 'The password is incorrect.',
        'account_disabled' => 'This account has been disabled.',
        'setup_required' => 'Complete your account setup before signing in.',
        'invalid_credentials' => 'The provided credentials are incorrect.',
        'logged_out' => 'Logged out successfully.',

        'unauthenticated' => 'Unauthenticated.',
        'forbidden' => 'You are not authorized to perform this action.',

        'verification_required' => 'Verify your email address before signing in. Check your inbox for the verification link.',
        'organisation_suspended' => 'This organisation is currently suspended. Contact support@patrimoine365.com.',
        'mfa_challenge_expired' => 'This sign-in attempt has expired. Sign in again to receive a new code.',
        'mfa_code_invalid' => 'The verification code is incorrect.',
        'mfa_code_resent' => 'A new verification code has been sent to your email address.',
    ],

    'registration' => [
        'created' => 'Your organisation has been created. Check your email to verify your address.',
        'verified' => 'Your email address has been verified. You can now sign in.',
        'verification_invalid' => 'This verification link is invalid or has expired. Request a new one from the sign-in page.',
        'verification_sent' => 'If that address needs verification, a new link has been sent.',
        'platform_domain_blocked' => 'This email domain is reserved. Contact support@patrimoine365.com.',
    ],

    'profile' => [
        'photo_invalid' => 'That file could not be read as an image. Use a JPG, PNG, WEBP or GIF.',
        'photo_updated' => 'Profile photo updated.',
        'photo_removed' => 'Profile photo removed.',
    ],

    'license' => [
        'user_limit_reached' => 'Your plan\'s user limit has been reached. Upgrade your plan to add more users.',
        'lease_limit_reached' => 'Your plan\'s active lease limit has been reached. Upgrade your plan to add more active leases.',
        'party_limit_reached' => 'Your plan\'s party limit has been reached. Upgrade your plan to add more parties.',
        'feature_unavailable' => 'This feature is not included in your current plan.',
        'email_cap_reached' => 'Your plan\'s monthly email allowance has been used up.',
    ],

    'user_management' => [
        'platform_domain_required' => 'Platform staff accounts must use an @patrimoine365.com email address.',
        'platform_domain_reserved' => 'This email domain is reserved for platform staff.',
        'cannot_change_own_role' => 'An administrator’s own role has to be changed by another administrator.',
        'cannot_disable_self' => 'An account cannot switch itself off. Another administrator can do it.',
        'cannot_delete_self' => 'An account cannot delete itself. Another administrator can do it.',
        'last_active_administrator' => 'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
    ],

    'personal_data' => [
        'erased' => 'That person has been erased. The records they appear in now read :reference.',
        'already_erased' => 'That person has already been erased.',
        'cannot_erase_managing' => 'Your own organisation cannot be erased. It is the company every document names as the producer.',
        'name_confirmation_mismatch' => 'That is not the name on the record. Type it exactly as it is shown.',
    ],

    'organisation' => [
        'closed' => 'Your organisation and everything in it has been permanently deleted.',
        'name_confirmation_mismatch' => 'That is not the name of your organisation. Type it exactly as it is shown.',
        'platform_cannot_close' => 'The organisation that operates Patrimoine cannot be closed from here.',
    ],

    'managing_organisation' => [
        'not_configured' => 'Managing organisation has not been configured.',
        'cannot_remove_role' => 'Your own company has to stay the managing organisation. Name another party as the managing organisation first.',
        'cannot_delete' => 'The configured managing organisation cannot be deleted.',
    ],

    'email' => [
        'invoice_sent' => 'Invoice email sent successfully.',
        'receipt_sent' => 'Receipt email sent successfully.',
        'transfer_voucher_sent' => 'Transfer receipt email sent successfully.',
        'owner_reserve_transfer_sent' => 'Account transfer voucher email sent successfully.',
        'tenant_expense_voucher_sent' => 'Tenant expense voucher email sent successfully.',
    ],

    'lease_wizard' => [
        'draft_saved' => 'Your progress is saved. Continue it from the Leases page.',
        'draft_discarded' => 'That unfinished assistant has been discarded.',
    ],

    'validation' => [
        'building_ownership_total' => 'Building ownership percentages must total 100%.',

        'wizard_owners_required' => 'This property has no recorded owner yet, so the wizard needs at least one.',

        'wizard_unit_building' => 'The selected unit does not belong to the selected property.',

        'building_required_for_unit' => 'A Building is required when a Unit is selected.',

        'unit_not_in_building' => 'Selected Unit does not belong to the selected Building.',

        'payment_draft_lease' => 'Payments cannot be recorded against a draft Lease.',

        'tenant_role_required' => 'Selected Party must have the tenant role.',

        'agent_role_required' => 'Selected Party must have the agent role.',

        'notice_date_required' => 'Termination notice date is required when Lease status is notice.',

        'management_fee_none_zero' => 'Management fee value must be zero when management fee type is none.',

        'management_fee_percentage_max' => 'Percentage management fee cannot exceed 100%.',

        'agent_required_for_commission' => 'An Agent is required when an agent commission is configured.',

        'unit_active_lease' => 'This Unit already has an active Lease.',

        'rent_reserve_exceeds_advance' => 'Rent Reserve cannot exceed the total Advance Payment.',

        'rent_increment_none_zero' => 'Rent increment value must be zero when no rent increment is configured.',

        'rent_increment_none_date' => 'Next rent increment date must be empty when no rent increment is configured.',

        'rent_increment_value_required' => 'Enter a rent increment value when a rent increment is configured.',

        'rent_increment_date_required' => 'Next rent increment date is required when a rent increment is configured.',

        'rent_increment_percentage_max' => 'Percentage rent increment cannot exceed 100%.',

        'advance_received_positive' => 'Advance Payment must be greater than zero when Advance already received is selected.',

        'advance_received_before_lease' => 'Advance received date cannot be before the Lease start date.',

        'telephone_country_required' => 'Choose the country this telephone number belongs to.',

        'telephone_number_invalid' => 'This telephone number does not look right. Check the digits and the country.',
    ],

    'user_invitation' => [
        'accepted' => 'Your account setup is complete. You can now sign in.',
        'resent' => 'A new invitation has been sent.',
        'inactive_user' => 'An invitation cannot be sent to an inactive user.',
        'invalid' => 'This invitation link is invalid or has expired.',
    ],

    'password' => [
        'reset_requested' => 'If the account is eligible, a password reset link has been sent.',
        'administrator_reset_requested' => 'The password reset workflow has been initiated.',
        'reset_complete' => 'Your password has been reset successfully.',
        'changed' => 'Your password has been changed successfully.',
        'invalid_reset' => 'This password reset link is invalid or has expired.',
        'current_incorrect' => 'The current password is incorrect.',
        'account_disabled' => 'This account has been disabled.',
    ],

    'deletion' => [
        'lease_confirmation_invalid' => 'Type DELETE exactly to confirm Lease deletion.',
        'lease_cannot_delete' => 'This lease cannot be deleted safely.',
        'party_managing_organisation' => 'The configured Managing Organisation cannot be deleted. Change the Managing Organisation configuration instead.',
        'party_referenced' => 'This Party cannot be deleted because it is referenced by Lease, ownership, agency or financial history. Keep the Party so historical records remain understandable.',
        'building_has_units' => 'This Building cannot be deleted while it still contains Units. Delete only unreferenced Units first; Units with Lease or financial history must be retained.',
        'building_referenced' => 'This Building cannot be deleted because financial or historical records reference it. Keep the Building for historical accountability.',
        'unit_referenced' => 'This Unit cannot be deleted because Lease or financial history references it. Keep the Unit and terminate the Lease instead where applicable.',
        'lease_not_draft' => 'Only an unused draft lease can be deleted. An active lease, or one under notice, is ended by terminating it — and a terminated lease is kept as history.',
        'lease_referenced' => 'This draft Lease cannot be deleted because contractual or financial history references it. Keep the Lease record.',
    ],

];

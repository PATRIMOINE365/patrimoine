<?php

return [
    'auth' => [
        'invalid_credentials' =>
            'The provided credentials are incorrect.',
        'logged_out' =>
            'Logged out successfully.',
    ],

    'managing_organisation' => [
        'not_configured' =>
            'Managing organisation has not been configured.',
        'cannot_remove_role' =>
            'The configured managing organisation cannot lose the managing_organisation role.',
        'cannot_delete' =>
            'The configured managing organisation cannot be deleted.',
    ],

    'email' => [
        'invoice_sent' =>
            'Invoice email sent successfully.',
        'receipt_sent' =>
            'Receipt email sent successfully.',
    ],

    'validation' => [
        'building_ownership_total' =>
            'Building ownership percentages must total 100%.',

        'building_required_for_unit' =>
            'A Building is required when a Unit is selected.',

        'unit_not_in_building' =>
            'Selected Unit does not belong to the selected Building.',

        'payment_draft_lease' =>
            'Payments cannot be recorded against a draft Lease.',

        'tenant_role_required' =>
            'Selected Party must have the tenant role.',

        'agent_role_required' =>
            'Selected Party must have the agent role.',

        'notice_date_required' =>
            'Termination notice date is required when Lease status is notice.',

        'management_fee_none_zero' =>
            'Management fee value must be zero when management fee type is none.',

        'management_fee_percentage_max' =>
            'Percentage management fee cannot exceed 100%.',

        'agent_required_for_commission' =>
            'An Agent is required when an agent commission is configured.',

        'unit_active_lease' =>
            'This Unit already has an active Lease.',

        'rent_reserve_exceeds_advance' =>
            'Rent Reserve cannot exceed the total Advance Payment.',

        'rent_increment_none_zero' =>
            'Rent increment value must be zero when no rent increment is configured.',

        'rent_increment_none_date' =>
            'Next rent increment date must be empty when no rent increment is configured.',

        'rent_increment_value_required' =>
            'Enter a rent increment value when a rent increment is configured.',

        'rent_increment_date_required' =>
            'Next rent increment date is required when a rent increment is configured.',

        'rent_increment_percentage_max' =>
            'Percentage rent increment cannot exceed 100%.',

        'advance_received_positive' =>
            'Advance Payment must be greater than zero when Advance already received is selected.',

        'advance_received_before_lease' =>
            'Advance received date cannot be before the Lease start date.',
    ],
];

<?php

/*
|--------------------------------------------------------------------------
| Error codes
|--------------------------------------------------------------------------
|
| Every failure Patrimoine can show a person carries a code, so that what
| somebody reads on screen, what support hears on the telephone and what
| the Error codes page explains are the same thing.
|
| A code is PM-Fnnn: F is the family, nnn the sequence inside it.
|
|   1  access      signing in, invitations, passwords, who may do what
|   2  input       something typed into a form
|   3  property    properties, units, parties, leases
|   4  money       invoices, payments, funds, owner accounting
|   5  documents   documents, reports and exports
|   6  messaging   e-mail Patrimoine sends to parties
|   7  licence     plan limits and billing
|   8  console     the platform administration console
|   9  system      the application itself, or the network under it
|
| 'severity' says who can act:
|
|   fix_yourself  the person reading it can put it right
|   try_again     a request did not come back; retry, then tell us
|   ask_admin     an administrator of the organisation must act
|   contact_us    ours to fix — the page shows how to reach us
|
| 'keys' are the translation keys that produce this message. A message
| shown by both the server and the browser lists both, which is what lets
| one code be attached wherever the message is raised.
|
| This file is the source for the in-app Error codes page, the public one
| at patrimoine365.com/errors, and the code attached to API responses.
|
*/

return [

    'families' => [
        1 => 'access',
        2 => 'input',
        3 => 'property',
        4 => 'money',
        5 => 'documents',
        6 => 'messaging',
        7 => 'licence',
        8 => 'console',
        9 => 'system',
    ],

    'codes' => [

        /* ---- 1xxx access ---- */

        'PM-1001' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.account_disabled', 'api.password.account_disabled'],
        ],
        'PM-1002' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.forbidden'],
        ],
        'PM-1003' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.invalid_credentials'],
        ],
        'PM-1004' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.mfa_challenge_expired'],
        ],
        'PM-1005' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.mfa_code_invalid'],
        ],
        'PM-1006' => [
            'family' => 1,
            'severity' => 'ask_admin',
            'keys' => ['api.auth.organisation_suspended'],
        ],
        'PM-1007' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            /*
             * Raised two ways: the controller checks the password
             * itself before a destructive action, and the validator
             * checks it through the current_password rule. Same
             * refusal to the person reading it, so one code.
             */
            'keys' => [
                'api.auth.password_confirmation_failed',
                'validation.current_password',
            ],
        ],
        'PM-1008' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.setup_required'],
        ],
        'PM-1009' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.unauthenticated'],
        ],
        'PM-1010' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.auth.verification_required'],
        ],
        'PM-1011' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.login.unable_to_sign_in', 'login.unable_to_sign_in'],
        ],
        'PM-1012' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.password.confirmation_mismatch', 'password.confirmation_mismatch'],
        ],
        'PM-1013' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.password.current_incorrect'],
        ],
        'PM-1014' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.password.invalid_reset'],
        ],
        'PM-1015' => [
            'family' => 1,
            'severity' => 'try_again',
            'keys' => ['ui.password.request_failed', 'password.request_failed'],
        ],
        'PM-1016' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            /*
             * The browser refuses a picture it cannot decode before the
             * server ever sees it — an HEIC outside Safari, most often —
             * so the same failure has to be recognisable from either side.
             */
            'keys' => [
                'api.profile.photo_invalid',
                'ui.profile.photo_unreadable',
                'profile.photo_unreadable',
            ],
        ],
        'PM-1017' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.profile.photo_removed'],
        ],
        'PM-1018' => [
            'family' => 1,
            'severity' => 'ask_admin',
            'keys' => ['api.registration.platform_domain_blocked'],
        ],
        'PM-1019' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.registration.verification_invalid'],
        ],
        'PM-1020' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.signup.description', 'signup.description'],
        ],
        'PM-1021' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.signup.unable', 'signup.unable'],
        ],
        'PM-1022' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_invitation.accepted'],
        ],
        'PM-1023' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_invitation.inactive_user'],
        ],
        'PM-1024' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_invitation.invalid'],
        ],
        'PM-1025' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_management.cannot_change_own_role'],
        ],
        'PM-1026' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_management.cannot_delete_self'],
        ],
        'PM-1027' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_management.cannot_disable_self'],
        ],
        'PM-1028' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_management.last_active_administrator'],
        ],
        'PM-1029' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.user_management.platform_domain_required'],
        ],
        'PM-1030' => [
            'family' => 1,
            'severity' => 'ask_admin',
            'keys' => ['api.user_management.platform_domain_reserved'],
        ],
        'PM-1031' => [
            'family' => 1,
            'severity' => 'try_again',
            'keys' => ['ui.users.action_failed', 'users.action_failed'],
        ],
        'PM-1032' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.users.unable_create', 'users.unable_create'],
        ],
        'PM-1033' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.users.unable_delete', 'users.unable_delete'],
        ],
        'PM-1034' => [
            'family' => 1,
            'severity' => 'try_again',
            'keys' => ['ui.users.unable_load', 'users.unable_load'],
        ],
        'PM-1035' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.users.unable_update', 'users.unable_update'],
        ],
        'PM-1036' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['ui.verify_email.failed_heading', 'verify_email.failed_heading'],
        ],
        'PM-1037' => [
            'family' => 1,
            'severity' => 'try_again',
            'keys' => ['ui.verify_email.resend_failed', 'verify_email.resend_failed'],
        ],
        'PM-1038' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.organisation.name_confirmation_mismatch'],
        ],
        'PM-1039' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.organisation.platform_cannot_close'],
        ],
        'PM-1040' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.personal_data.already_erased'],
        ],
        'PM-1041' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.personal_data.cannot_erase_managing'],
        ],
        'PM-1042' => [
            'family' => 1,
            'severity' => 'fix_yourself',
            'keys' => ['api.personal_data.name_confirmation_mismatch'],
        ],

        /* ---- 2xxx input ---- */

        'PM-2001' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.accepted'],
        ],
        'PM-2002' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.accepted_if'],
        ],
        'PM-2003' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.active_url', 'validation.url'],
        ],
        'PM-2004' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.after'],
        ],
        'PM-2005' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.after_or_equal'],
        ],
        'PM-2006' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.alpha'],
        ],
        'PM-2007' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.alpha_dash'],
        ],
        'PM-2008' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.alpha_num'],
        ],
        'PM-2009' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.array'],
        ],
        'PM-2010' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.before'],
        ],
        'PM-2011' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.before_or_equal'],
        ],
        'PM-2012' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.between.array'],
        ],
        'PM-2013' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.between.file'],
        ],
        'PM-2014' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.between.numeric'],
        ],
        'PM-2015' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.between.string'],
        ],
        'PM-2016' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.boolean'],
        ],
        'PM-2017' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.confirmed'],
        ],
        'PM-2018' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.date'],
        ],
        'PM-2019' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.date_equals'],
        ],
        'PM-2020' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.date_format'],
        ],
        'PM-2021' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.decimal'],
        ],
        'PM-2022' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.different'],
        ],
        'PM-2023' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.digits'],
        ],
        'PM-2024' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.digits_between'],
        ],
        'PM-2025' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.email'],
        ],
        'PM-2026' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.ends_with'],
        ],
        'PM-2027' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.exists', 'validation.in', 'validation.not_in'],
        ],
        'PM-2028' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.integer'],
        ],
        'PM-2029' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.max.array'],
        ],
        'PM-2030' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.max.file'],
        ],
        'PM-2031' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.max.numeric'],
        ],
        'PM-2032' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.max.string'],
        ],
        'PM-2033' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.min.array'],
        ],
        'PM-2034' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.min.file'],
        ],
        'PM-2035' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.min.numeric'],
        ],
        'PM-2036' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.min.string'],
        ],
        'PM-2038' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.numeric'],
        ],
        'PM-2039' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.regex'],
        ],
        'PM-2040' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.required'],
        ],
        'PM-2041' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.required_if'],
        ],
        'PM-2042' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.required_unless'],
        ],
        'PM-2043' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.required_with'],
        ],
        'PM-2044' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.required_without'],
        ],
        'PM-2045' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.same'],
        ],
        'PM-2046' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.size.array'],
        ],
        'PM-2047' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.size.file'],
        ],
        'PM-2048' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.size.numeric'],
        ],
        'PM-2049' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.size.string'],
        ],
        'PM-2050' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.string'],
        ],
        'PM-2051' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.unique'],
        ],
        'PM-2052' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.advance_received_before_lease'],
        ],
        'PM-2053' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.advance_received_positive'],
        ],
        'PM-2054' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.agent_required_for_commission'],
        ],
        'PM-2055' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.agent_role_required'],
        ],
        'PM-2056' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.building_required_for_unit'],
        ],
        'PM-2057' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.management_fee_none_zero'],
        ],
        'PM-2058' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.management_fee_percentage_max'],
        ],
        'PM-2059' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.notice_date_required'],
        ],
        'PM-2060' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.payment_draft_lease'],
        ],
        'PM-2061' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_increment_date_required'],
        ],
        'PM-2062' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_increment_none_date'],
        ],
        'PM-2063' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_increment_none_zero'],
        ],
        'PM-2064' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_increment_percentage_max'],
        ],
        'PM-2065' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_increment_value_required'],
        ],
        'PM-2066' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.rent_reserve_exceeds_advance'],
        ],
        'PM-2067' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.tenant_role_required'],
        ],
        'PM-2068' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.unit_active_lease'],
        ],
        'PM-2069' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.unit_not_in_building'],
        ],
        'PM-2070' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.wizard_owners_required'],
        ],
        'PM-2071' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.wizard_unit_building'],
        ],
        'PM-2072' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.telephone_country_required'],
        ],
        'PM-2073' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['api.validation.telephone_number_invalid'],
        ],


        /*
         * V1.0.35: eight validation rules the application uses were in
         * no language file, so they fell back to the framework's own
         * English AND belonged to no code — which is worse than having
         * none, because forMessage() then matched them against whatever
         * broader pattern happened to fit. A zero amount answered
         * PM-2048, the code for a SIZE rule, and read "The montant field
         * must be greater than 0." to a French organisation.
         */
        'PM-2074' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.gt.numeric'],
        ],

        'PM-2078' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.lte.numeric'],
        ],

        'PM-2083' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.distinct'],
        ],

        'PM-2084' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.file'],
        ],

        'PM-2085' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.mimes'],
        ],

        'PM-2086' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.ip'],
        ],

        'PM-2087' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.password.letters'],
        ],

        'PM-2088' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.password.mixed'],
        ],

        'PM-2089' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.password.numbers'],
        ],

        'PM-2090' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.password.symbols'],
        ],

        'PM-2091' => [
            'family' => 2,
            'severity' => 'fix_yourself',
            'keys' => ['validation.password.uncompromised'],
        ],
        /* ---- 3xxx property ---- */

        'PM-3001' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.building_has_units'],
        ],
        'PM-3002' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.building_referenced'],
        ],
        'PM-3003' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.lease_cannot_delete'],
        ],
        'PM-3004' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.lease_confirmation_invalid'],
        ],
        'PM-3005' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.lease_not_draft'],
        ],
        'PM-3006' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.lease_referenced'],
        ],
        'PM-3007' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.party_managing_organisation'],
        ],
        'PM-3008' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.party_referenced'],
        ],
        'PM-3009' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.deletion.unit_referenced'],
        ],
        'PM-3010' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.deduction_record_failed', 'leases.deduction_record_failed'],
        ],
        'PM-3011' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.delete_blocked'],
        ],
        'PM-3012' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.delete_confirmation_invalid'],
        ],
        'PM-3013' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['leases.delete_impact_failed'],
        ],
        'PM-3014' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.delete_password_required'],
        ],
        'PM-3015' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.delete_reason_required'],
        ],
        'PM-3016' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.financial_history_unable_load', 'leases.financial_history_unable_load'],
        ],
        'PM-3017' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.financial_history_unable_open_document', 'leases.financial_history_unable_open_document', 'ui.owners.unable_to_open_document', 'owners.unable_to_open_document', 'ui.tenants.unable_to_open_document', 'tenants.unable_to_open_document'],
        ],
        'PM-3018' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.increment_cancel_failed', 'leases.increment_cancel_failed'],
        ],
        'PM-3019' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.increment_schedule_failed', 'leases.increment_schedule_failed'],
        ],
        'PM-3020' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.increments_unable_load', 'leases.increments_unable_load'],
        ],
        'PM-3021' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.reserve_exceeds_advance', 'leases.reserve_exceeds_advance'],
        ],
        'PM-3022' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.termination_cancel_failed'],
        ],
        'PM-3023' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['leases.termination_complete_failed'],
        ],
        'PM-3024' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.termination_failed'],
        ],
        'PM-3025' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['leases.termination_notice_unable_open'],
        ],
        'PM-3026' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.termination_required_fields'],
        ],
        'PM-3027' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['leases.termination_settlement_load_failed'],
        ],
        'PM-3028' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['leases.termination_unresolved_items'],
        ],
        'PM-3029' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_add_deduction', 'leases.unable_add_deduction'],
        ],
        'PM-3030' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_apply_advance', 'leases.unable_apply_advance'],
        ],
        'PM-3031' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_apply_reserve', 'leases.unable_apply_reserve'],
        ],
        'PM-3032' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_create', 'leases.unable_create'],
        ],
        'PM-3033' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_delete', 'leases.unable_delete'],
        ],
        'PM-3034' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_finalize_security', 'leases.unable_finalize_security'],
        ],
        'PM-3035' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_initialize', 'leases.unable_initialize'],
        ],
        'PM-3036' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.unable_load', 'leases.unable_load'],
        ],
        'PM-3037' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.unable_load_one', 'leases.unable_load_one'],
        ],
        'PM-3038' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.unable_load_security_deposit', 'leases.unable_load_security_deposit'],
        ],
        'PM-3039' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.unable_load_tenant_funds', 'leases.unable_load_tenant_funds'],
        ],
        'PM-3040' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.unable_open_voucher', 'leases.unable_open_voucher'],
        ],
        'PM-3041' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.leases.unable_update', 'leases.unable_update'],
        ],
        'PM-3042' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.leases.voucher_popup_blocked', 'leases.voucher_popup_blocked'],
        ],
        'PM-3043' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.parties.delete_restriction', 'parties.delete_restriction'],
        ],
        'PM-3044' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.parties.unable_to_create_party', 'parties.unable_to_create_party'],
        ],
        'PM-3045' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.parties.unable_to_delete_party', 'parties.unable_to_delete_party'],
        ],
        'PM-3046' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.parties.unable_to_load', 'parties.unable_to_load'],
        ],
        'PM-3047' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.parties.unable_to_load_party', 'parties.unable_to_load_party'],
        ],
        'PM-3048' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.parties.unable_to_update_party', 'parties.unable_to_update_party'],
        ],
        'PM-3049' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.organisation_required_fields', 'properties.organisation_required_fields'],
        ],
        'PM-3050' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.person_required_fields', 'properties.person_required_fields'],
        ],
        'PM-3051' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_add_unit', 'properties.unable_to_add_unit'],
        ],
        'PM-3052' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_create_owner', 'properties.unable_to_create_owner'],
        ],
        'PM-3053' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_create_property', 'properties.unable_to_create_property'],
        ],
        'PM-3054' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_delete_property', 'properties.unable_to_delete_property'],
        ],
        'PM-3055' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_delete_unit', 'properties.unable_to_delete_unit'],
        ],
        'PM-3056' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.properties.unable_to_load', 'properties.unable_to_load'],
        ],
        'PM-3057' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.properties.unable_to_load_property', 'properties.unable_to_load_property'],
        ],
        'PM-3058' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_locate_unit', 'properties.unable_to_locate_unit'],
        ],
        'PM-3059' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_update_property', 'properties.unable_to_update_property'],
        ],
        'PM-3060' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.unable_to_update_unit', 'properties.unable_to_update_unit'],
        ],
        'PM-3061' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_duplicate_owner', 'properties.validation_duplicate_owner'],
        ],
        'PM-3062' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_every_unit_name', 'properties.validation_every_unit_name'],
        ],
        'PM-3063' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_owner_required', 'properties.validation_owner_required'],
        ],
        'PM-3064' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_ownership_total', 'properties.validation_ownership_total'],
        ],
        'PM-3065' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_unique_unit_names', 'properties.validation_unique_unit_names'],
        ],
        'PM-3066' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_unit_name_required', 'properties.validation_unit_name_required'],
        ],
        'PM-3067' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_unit_required', 'properties.validation_unit_required'],
        ],
        'PM-3068' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.properties.validation_valid_property', 'properties.validation_valid_property'],
        ],
        'PM-3069' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.adjustment_negative_balance', 'tenants.adjustment_negative_balance'],
        ],
        'PM-3070' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.adjustment_no_change', 'tenants.adjustment_no_change'],
        ],
        'PM-3071' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.expense_exceeds_balance', 'tenants.expense_exceeds_balance'],
        ],
        'PM-3072' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.not_tenant', 'tenants.not_tenant'],
        ],
        'PM-3073' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.pay_exceeds_balance', 'tenants.pay_exceeds_balance'],
        ],
        'PM-3074' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.pay_fields_required', 'tenants.pay_fields_required'],
        ],
        'PM-3075' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.security_application_exceeds_deposit', 'tenants.security_application_exceeds_deposit'],
        ],
        'PM-3076' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.security_application_exceeds_receivable', 'tenants.security_application_exceeds_receivable'],
        ],
        'PM-3077' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.transfer_exceeds_balance', 'tenants.transfer_exceeds_balance'],
        ],
        'PM-3078' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.transfer_required_fields', 'tenants.transfer_required_fields'],
        ],
        'PM-3079' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.transfer_same_account', 'tenants.transfer_same_account'],
        ],
        'PM-3080' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.tenants.unable_to_load', 'tenants.unable_to_load'],
        ],
        'PM-3081' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.tenants.unable_to_load_details', 'tenants.unable_to_load_details'],
        ],
        'PM-3082' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.tenants.unable_to_load_tenant', 'tenants.unable_to_load_tenant'],
        ],
        'PM-3083' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.tenants.unable_to_open_invoice', 'tenants.unable_to_open_invoice'],
        ],
        'PM-3084' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.tenants.unable_to_open_voucher', 'tenants.unable_to_open_voucher'],
        ],
        'PM-3085' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.unable_to_resend_invoice', 'tenants.unable_to_resend_invoice'],
        ],
        'PM-3086' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.unable_to_resend_receipt', 'tenants.unable_to_resend_receipt'],
        ],
        'PM-3087' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.tenants.unable_to_resend_voucher', 'tenants.unable_to_resend_voucher'],
        ],
        'PM-3088' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            /*
             * The browser stops this before the request is sent; the
             * service stops it again under a lock, because the balance can
             * move between the two. Both are the same refusal to the
             * person reading it, so both carry this code.
             */
            'keys' => [
                'tenants.withdrawal_exceeds_balance',
                'business.tenant_fund_withdrawal.exceeds_balance',
            ],
        ],
        'PM-3089' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.wizard.load_failed', 'wizard.load_failed'],
        ],
        'PM-3090' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.wizard.save_failed', 'wizard.save_failed'],
        ],
        'PM-3091' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['ui.wizard.draft_missing', 'wizard.draft_missing'],
        ],
        'PM-3092' => [
            'family' => 3,
            'severity' => 'try_again',
            'keys' => ['ui.wizard.drafts_discard_failed', 'wizard.drafts_discard_failed'],
        ],

        /*
         * V1.0.35: the withdrawal refusals. These were English literals
         * thrown out of TenantFundWithdrawalService, so a French
         * organisation read them in English and no code was attached to
         * any of them — the over-withdrawal already had PM-3088 for the
         * browser's copy of the same refusal, and the server's copy
         * carried nothing.
         */
        'PM-3093' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant_fund_withdrawal.account_inactive'],
        ],

        'PM-3094' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant_fund_withdrawal.ineligible_fund'],
        ],

        'PM-3095' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant_fund_withdrawal.amount_positive'],
        ],
        'PM-3096' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.archive.not_archivable'],
        ],
        'PM-3097' => [
            'family' => 3,
            'severity' => 'fix_yourself',
            'keys' => ['api.archive.lease_not_terminated'],
        ],

        /* ---- 4xxx money ---- */

        'PM-4001' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.account_closed'],
        ],
        'PM-4002' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.amount_positive'],
        ],
        'PM-4003' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.draft_lease'],
        ],
        'PM-4004' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.exceeds_invoice'],
        ],
        'PM-4005' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.insufficient_balance'],
        ],
        'PM-4006' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.rent_only'],
        ],
        'PM-4007' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.wrong_account_type'],
        ],
        'PM-4008' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.consumable_advance.wrong_invoice_lease'],
        ],
        'PM-4009' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['financial_journal.unable_export', 'activity_log.unable_export'],
        ],
        'PM-4010' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['financial_journal.unable_load', 'activity_log.unable_load'],
        ],
        'PM-4011' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['financial_journal.unable_load_detail', 'activity_log.unable_load_detail'],
        ],
        'PM-4012' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.fund_accounts.not_consumable_advance'],
        ],
        'PM-4013' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.fund_accounts.not_rent_reserve'],
        ],
        'PM-4014' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.account_cannot_pay_rent'],
        ],
        'PM-4015' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.account_closed'],
        ],
        'PM-4016' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.already_cancelled'],
        ],
        'PM-4017' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.amount_positive'],
        ],
        'PM-4018' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.exceeds_invoice', 'ui.tenants.pay_exceeds_invoice', 'tenants.pay_exceeds_invoice'],
        ],
        'PM-4019' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.historical_payment'],
        ],
        'PM-4020' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.insufficient_balance'],
        ],
        'PM-4021' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.not_a_payment'],
        ],
        'PM-4022' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.receipt_unpaid'],
        ],
        'PM-4023' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.unsupported_invoice'],
        ],
        'PM-4024' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.invoice_payment.wrong_lease'],
        ],
        'PM-4025' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.adjustment_direction'],
        ],
        'PM-4026' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.adjustment_positive'],
        ],
        'PM-4027' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.adjustment_reason'],
        ],
        'PM-4028' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_already_cancelled'],
        ],
        'PM-4029' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_exceeds_bill'],
        ],
        'PM-4030' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_insufficient_payout'],
        ],
        'PM-4031' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_positive'],
        ],
        'PM-4032' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_reason_required', 'business.invoice_payment.reason_required', 'ui.owners.cancellation_reason_required', 'owners.cancellation_reason_required', 'ui.tenants.cancellation_reason_required', 'tenants.cancellation_reason_required'],
        ],
        'PM-4033' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_receipt_unpaid'],
        ],
        'PM-4034' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.bill_payment_source'],
        ],
        'PM-4035' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.cash_collector_required'],
        ],
        'PM-4036' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.deposit_payment_method'],
        ],
        'PM-4037' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.deposit_positive'],
        ],
        'PM-4038' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.deposit_purpose'],
        ],
        'PM-4039' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.deposit_receipt_only'],
        ],
        'PM-4040' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.historical_bill_payment'],
        ],
        'PM-4041' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.not_a_bill_payment'],
        ],
        'PM-4042' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.payout_allocation_failed'],
        ],
        'PM-4043' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.payout_exceeds_balance'],
        ],
        'PM-4044' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.payout_no_funds'],
        ],
        'PM-4045' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.payout_positive'],
        ],
        'PM-4046' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.reserve_transfer_exceeds_source'],
        ],
        'PM-4047' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.reserve_transfer_positive'],
        ],
        'PM-4048' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.owner.reserve_transfer_reason_required'],
        ],
        'PM-4049' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.adjustment_reason_required', 'owners.adjustment_reason_required'],
        ],
        'PM-4050' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.collector_required', 'owners.collector_required'],
        ],
        'PM-4051' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.email_failed', 'owners.email_failed'],
        ],
        'PM-4052' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.expense_description_required', 'owners.expense_description_required'],
        ],
        'PM-4053' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.pay_exceeds_bill', 'owners.pay_exceeds_bill'],
        ],
        'PM-4054' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.pay_exceeds_payout', 'owners.pay_exceeds_payout'],
        ],
        'PM-4055' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.pay_fields_required', 'owners.pay_fields_required'],
        ],
        'PM-4056' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.payout_exceeds_balance', 'owners.payout_exceeds_balance'],
        ],
        'PM-4057' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.unable_to_cancel_payment', 'owners.unable_to_cancel_payment'],
        ],
        'PM-4058' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.unable_to_create_payout', 'owners.unable_to_create_payout'],
        ],
        'PM-4059' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_load', 'owners.unable_to_load', 'ui.properties.unable_to_load_owners', 'properties.unable_to_load_owners'],
        ],
        'PM-4060' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_load_owner', 'owners.unable_to_load_owner'],
        ],
        'PM-4061' => [
            'family' => 4,
            'severity' => 'contact_us',
            'keys' => ['ui.owners.unable_to_open_statement', 'owners.unable_to_open_statement'],
        ],
        'PM-4062' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_open_voucher', 'owners.unable_to_open_voucher'],
        ],
        'PM-4063' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_pay_bill', 'owners.unable_to_pay_bill'],
        ],
        'PM-4064' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_record_adjustment', 'owners.unable_to_record_adjustment'],
        ],
        'PM-4065' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_record_bill', 'owners.unable_to_record_bill'],
        ],
        'PM-4066' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_record_deposit', 'owners.unable_to_record_deposit'],
        ],
        'PM-4067' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_record_expense', 'owners.unable_to_record_expense'],
        ],
        'PM-4068' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.unable_to_resend_bill', 'owners.unable_to_resend_bill'],
        ],
        'PM-4069' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.owners.unable_to_resend_voucher', 'owners.unable_to_resend_voucher'],
        ],
        'PM-4070' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.owners.unable_to_transfer', 'owners.unable_to_transfer'],
        ],
        'PM-4071' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.payments.owner_receipt_unresolved', 'payments.owner_receipt_unresolved'],
        ],
        'PM-4072' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.payments.payment_receipt_unresolved', 'payments.payment_receipt_unresolved'],
        ],
        'PM-4073' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.payments.unable_to_classify_funds', 'payments.unable_to_classify_funds'],
        ],
        'PM-4074' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_load', 'payments.unable_to_load'],
        ],
        'PM-4075' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_load_funds', 'payments.unable_to_load_funds'],
        ],
        'PM-4076' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_load_leases', 'payments.unable_to_load_leases'],
        ],
        'PM-4077' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_load_owner', 'payments.unable_to_load_owner', 'ui.owners.unable_to_load_details', 'owners.unable_to_load_details'],
        ],
        'PM-4078' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_load_tenant_leases', 'payments.unable_to_load_tenant_leases'],
        ],
        'PM-4079' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_open_receipt', 'payments.unable_to_open_receipt', 'ui.tenants.unable_to_open_receipt', 'tenants.unable_to_open_receipt'],
        ],
        'PM-4080' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_record', 'payments.unable_to_record'],
        ],
        'PM-4081' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_search_owners', 'payments.unable_to_search_owners'],
        ],
        'PM-4082' => [
            'family' => 4,
            'severity' => 'try_again',
            'keys' => ['ui.payments.unable_to_search_tenants', 'payments.unable_to_search_tenants'],
        ],
        'PM-4083' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.payments.validation_collector', 'payments.validation_collector'],
        ],
        'PM-4084' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['ui.payments.validation_date', 'payments.validation_date'],
        ],
        'PM-4085' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.account_closed'],
        ],
        'PM-4086' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.amount_positive'],
        ],
        'PM-4087' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.before_notice'],
        ],
        'PM-4088' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.exceeds_invoice'],
        ],
        'PM-4089' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.insufficient_balance'],
        ],
        'PM-4090' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.no_ownership', 'business.consumable_advance.no_ownership', 'business.owner.no_ownership'],
        ],
        'PM-4091' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.ownership_total', 'business.consumable_advance.ownership_total', 'business.owner.ownership_total', 'api.validation.building_ownership_total'],
        ],
        'PM-4092' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.rent_only'],
        ],
        'PM-4093' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.wrong_account_type'],
        ],
        'PM-4094' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.rent_reserve.wrong_invoice_lease'],
        ],
        'PM-4095' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.security_deposit.account_missing'],
        ],
        'PM-4096' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.security_deposit.already_settled'],
        ],
        'PM-4097' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.security_deposit.deductions_after_settlement'],
        ],
        'PM-4098' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.security_deposit.deductions_terminated_only'],
        ],
        'PM-4099' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.security_deposit.negative_balance'],
        ],
        'PM-4100' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_account_inactive'],
        ],
        'PM-4101' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_description_required'],
        ],
        'PM-4102' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_draft_lease'],
        ],
        'PM-4103' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_exceeds_balance'],
        ],
        'PM-4104' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_lines_required'],
        ],
        'PM-4105' => [
            'family' => 4,
            'severity' => 'fix_yourself',
            'keys' => ['business.tenant.expense_positive'],
        ],

        /* ---- 5xxx documents ---- */

        'PM-5001' => [
            'family' => 5,
            'severity' => 'fix_yourself',
            'keys' => ['ui.activity_log.invalid_account', 'tenants.invalid_account'],
        ],
        'PM-5002' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.activity_log.transaction_failed', 'tenants.transaction_failed'],
        ],
        'PM-5003' => [
            'family' => 5,
            'severity' => 'fix_yourself',
            'keys' => ['ui.activity_log.transaction_required_fields', 'tenants.transaction_required_fields'],
        ],
        'PM-5004' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.activity_log.unable_export'],
        ],
        'PM-5005' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.activity_log.unable_load'],
        ],
        'PM-5006' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.activity_log.unable_load_detail'],
        ],
        'PM-5007' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.activity_log.unable_to_load_accounts', 'tenants.unable_to_load_accounts'],
        ],
        'PM-5008' => [
            'family' => 5,
            'severity' => 'fix_yourself',
            'keys' => ['api.documents.link_invalid'],
        ],
        'PM-5009' => [
            'family' => 5,
            'severity' => 'fix_yourself',
            'keys' => ['api.documents.not_signable'],
        ],
        'PM-5010' => [
            'family' => 5,
            'severity' => 'fix_yourself',
            'keys' => ['ui.reports.invalid_period', 'reports.invalid_period'],
        ],
        'PM-5011' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_download', 'reports.unable_to_download'],
        ],
        'PM-5012' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_generate', 'reports.unable_to_generate'],
        ],
        'PM-5013' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_load_payment_filters', 'reports.unable_to_load_payment_filters'],
        ],
        'PM-5014' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_open', 'reports.unable_to_open'],
        ],
        'PM-5015' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_open_tenant_statement', 'reports.unable_to_open_tenant_statement'],
        ],
        'PM-5016' => [
            'family' => 5,
            'severity' => 'try_again',
            'keys' => ['ui.reports.unable_to_search', 'reports.unable_to_search'],
        ],

        /* ---- 6xxx messaging ---- */

        'PM-6001' => [
            'family' => 6,
            'severity' => 'fix_yourself',
            'keys' => ['business.email.owner_email_missing'],
        ],
        'PM-6002' => [
            'family' => 6,
            'severity' => 'fix_yourself',
            'keys' => ['business.email.suppressed_by_organisation'],
        ],
        'PM-6003' => [
            'family' => 6,
            'severity' => 'fix_yourself',
            'keys' => ['business.email.suppressed_for_party'],
        ],
        'PM-6004' => [
            'family' => 6,
            'severity' => 'fix_yourself',
            'keys' => ['business.email.tenant_email_missing'],
        ],
        'PM-6005' => [
            'family' => 6,
            'severity' => 'try_again',
            'keys' => ['ui.notifications.unable_load', 'notifications.unable_load'],
        ],

        /* ---- V1.0.36: writing to support from inside the application ---- */
        'PM-6006' => [
            'family' => 6,
            'severity' => 'try_again',
            'keys' => ['api.support.send_failed'],
        ],

        /* ---- 7xxx licence ---- */

        'PM-7001' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['api.license.email_cap_reached'],
        ],
        'PM-7002' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['api.license.feature_unavailable'],
        ],
        'PM-7004' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['api.license.lease_limit_reached'],
        ],
        'PM-7005' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['api.license.party_limit_reached'],
        ],
        'PM-7006' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['ui.license.unable', 'license.unable'],
        ],
        'PM-7007' => [
            'family' => 7,
            'severity' => 'ask_admin',
            'keys' => ['api.license.user_limit_reached'],
        ],

        /* ---- 8xxx console ---- */

        'PM-8001' => [
            'family' => 8,
            'severity' => 'fix_yourself',
            'keys' => ['api.managing_organisation.cannot_delete'],
        ],
        'PM-8002' => [
            'family' => 8,
            'severity' => 'fix_yourself',
            'keys' => ['api.managing_organisation.cannot_remove_role'],
        ],
        'PM-8003' => [
            'family' => 8,
            'severity' => 'fix_yourself',
            'keys' => ['api.managing_organisation.not_configured'],
        ],

        /* ---- 9xxx system ---- */

        'PM-9001' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => ['ui.core.request_failed', 'core.request_failed'],
        ],
        'PM-9002' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => ['ui.core.session_expired', 'core.session_expired'],
        ],
        'PM-9003' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => ['ui.dashboard.unable_to_load', 'dashboard.unable_to_load'],
        ],
        'PM-9004' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => ['ui.dashboard.unable_to_load_section', 'dashboard.unable_to_load_section'],
        ],
        'PM-9005' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => ['ui.settings.not_configured', 'settings.not_configured'],
        ],
        'PM-9006' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => ['ui.settings.unable_export', 'settings.unable_export'],
        ],
        'PM-9007' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => ['ui.settings.unable_import', 'settings.unable_import'],
        ],
        'PM-9008' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => ['ui.settings.unable_to_load', 'settings.unable_to_load'],
        ],
        'PM-9009' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => ['ui.settings.unable_to_save', 'settings.unable_to_save'],
        ],

        /* ---- 99xx the system itself, which raises no message of its own ---- */

        'PM-9901' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            /*
             * Reached two ways: an address that does not exist, which has
             * only its status, and a record that cannot be found, which
             * now says so in a sentence of ours rather than the ORM's.
             */
            'keys' => ['api.not_found'],
        ],
        'PM-9902' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => [],
        ],
        'PM-9903' => [
            'family' => 9,
            'severity' => 'fix_yourself',
            'keys' => [],
        ],
        'PM-9904' => [
            'family' => 9,
            'severity' => 'contact_us',
            'keys' => [],
        ],
        'PM-9905' => [
            'family' => 9,
            'severity' => 'contact_us',
            'keys' => [],
        ],
        'PM-9906' => [
            'family' => 9,
            'severity' => 'try_again',
            'keys' => [],
        ],
    ],
];

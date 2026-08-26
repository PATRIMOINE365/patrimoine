<?php

/*
|--------------------------------------------------------------------------
| Patrimoine 365 Licensing Plans
|--------------------------------------------------------------------------
|
| Source of truth: "Patrimoine 365 — Licensing Plans" (26 August 2026).
|
| Active leases are the only licensing metric; history, buildings and
| units never force an upgrade. The parties cap is purely an anti-abuse
| ceiling at 5x the lease quota. Financial integrity features are
| identical on every plan. Email hard caps stop automated mail first;
| transactional document mail keeps flowing; sign-in/MFA email is never
| blocked or counted. Over-quota blocks creating new records of the
| limited kind only — existing data is never locked or degraded.
|
| A null limit means unlimited.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Introductory trial
    |----------------------------------------------------------------------
    |
    | Every new organisation starts a 30-day Professional trial, no card
    | required, then continues on Free unless subscribed.
    |
    */

    'trial_days' => 30,

    'trial_plan' => 'professional',

    'fallback_plan' => 'free',

    'plans' => [

        'free' => [
            'label' => 'Free',
            'price_monthly_usd' => 0,
            'price_annual_usd' => 0,
            'limits' => [
                'users' => 1,
                'active_leases' => 5,
                'parties' => 25,
                'emails_per_month' => 200,
                'sms_per_month' => 0,
            ],
            'features' => [
                'reports' => false,
                'exports' => false,
                'automated_reminders' => false,
                'sms' => false,
                'party_portal' => false,
                'api_access' => false,
            ],
        ],

        'standard' => [
            'label' => 'Standard',
            'price_monthly_usd' => 19.99,
            'price_annual_usd' => 199.99,
            'limits' => [
                'users' => 3,
                'active_leases' => 50,
                'parties' => 250,
                'emails_per_month' => 2000,
                'sms_per_month' => 0,
            ],
            'features' => [
                'reports' => true,
                'exports' => true,
                'automated_reminders' => false,
                'sms' => false,
                'party_portal' => false,
                'api_access' => false,
            ],
        ],

        'professional' => [
            'label' => 'Professional',
            'price_monthly_usd' => 99.99,
            'price_annual_usd' => 999.99,
            'limits' => [
                'users' => 20,
                'active_leases' => 1000,
                'parties' => 5000,
                'emails_per_month' => 20000,
                'sms_per_month' => 500,
            ],
            'features' => [
                'reports' => true,
                'exports' => true,
                'automated_reminders' => true,
                'sms' => true,
                'party_portal' => true,
                'api_access' => true,
            ],
        ],

    ],
];

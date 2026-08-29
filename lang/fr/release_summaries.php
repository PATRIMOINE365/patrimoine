<?php

/*
|--------------------------------------------------------------------------
| Update log, as a customer reads it (French)
|--------------------------------------------------------------------------
|
| See the English file for why the log is written in fives. `through` is
| the last version an entry covers; the newest entry is the one still
| being filled.
|
| Written as French, not translated from the English.
|
| Newest first.
|
*/

return [
    'title' => 'Journal des mises à jour',
    'current_version' => 'Vous utilisez la version :version.',

    'entries' => [
        [
            'through' => '1.0.35',
            'date' => '2026-08-29',
            'summary' => 'Votre photo apparaît désormais en haut de l\'écran et à côté de votre nom sur la page Utilisateurs, ce qui permet de reconnaître un compte d\'un coup d\'œil. L\'assistant de bail ne propose plus les champs d\'un bien que vous ne créez pas, et le journal que vous lisez a été ramené à l\'essentiel.',
        ],
        [
            'through' => '1.0.30',
            'date' => '2026-08-29',
            'summary' => 'Chaque message affiché lorsqu\'une opération n\'aboutit pas porte maintenant un code et renvoie à une page qui explique quoi faire, et les numéros de téléphone sont enregistrés avec leur pays. Les relevés de propriétaire sont arrivés, un assistant crée une location complète en une seule fois, et vous pouvez couper tout ce que Patrimoine envoie à une partie — ou à toutes.',
        ],
        [
            'through' => '1.0.25',
            'date' => '2026-08-28',
            'summary' => 'La TVA s\'applique désormais aux honoraires de gestion, là où un comptable s\'attend à la trouver, et le chèque a rejoint les modes de règlement. L\'interface ne repasse plus en anglais pour les organisations qui travaillent en français.',
        ],
        [
            'through' => '1.0.20',
            'date' => '2026-08-26',
            'summary' => 'Le journal financier, les e-mails envoyés par Patrimoine et les pages de connexion ont été achevés dans les deux langues et habillés de l\'identité Patrimoine 365. Les e-mails de vérification ne laissent plus personne bloqué : on peut en redemander un depuis la page elle-même.',
        ],
        [
            'through' => '1.0.15',
            'date' => '2026-08-26',
            'summary' => 'Patrimoine est devenu l\'espace sécurisé propre à votre organisation : vous créez votre compte, vérifiez votre adresse et confirmez chaque connexion par un code qui vous est envoyé. Les forfaits, les confirmations de licence et les rappels d\'échéance sont arrivés avec.',
        ],
        [
            'through' => '1.0.10',
            'date' => '2026-08-26',
            'summary' => 'Les comptes propriétaires, les dépenses à payer et les reversements ont complété le volet financier, et les Paramètres, les Rapports et le Tableau de bord ont été reconstruits autour d\'eux. Les documents sont désormais accessibles là où se trouve la fiche à laquelle ils appartiennent.',
        ],
        [
            'through' => '1.0.5',
            'date' => '2026-08-20',
            'summary' => 'Les premières versions ont bâti Patrimoine : biens, lots, parties, baux, factures et quittances, sur un vrai journal en partie double qui conserve définitivement chaque événement financier. Le français et l\'anglais, deux devises, des rôles fixes et une traçabilité des actions sont arrivés avec elles.',
        ],
    ],
];

<?php

/*
|--------------------------------------------------------------------------
| Historique des versions Patrimoine (français)
|--------------------------------------------------------------------------
|
| Affiché dans le journal des mises à jour. Ordre : plus récent d'abord.
|
*/

return [
    'title' => 'Journal des mises à jour',
    'current_version' => 'Vous utilisez la version :version.',

    'entries' => [
        [
            'version' => '1.0.7',
            'date' => '2026-08-22',
            'title' => 'Comptes, documents partout et restauration des données',
            'changes' => [
                'Les gestionnaires peuvent désormais tout faire sauf l\'administration (Journal d\'activité, Journal financier, Utilisateurs et Paramètres restent réservés à l\'administrateur).',
                'Les personnes disposent de champs séparés Prénoms et Nom de famille pour les locataires, propriétaires, agents et utilisateurs.',
                'Les unités peuvent être marquées comme commerciales et filtrées en conséquence.',
                'Vue Comptes du locataire avec virements entre comptes (motif obligatoire et bon imprimable), dépôts, retraits et ajustements sur tout compte.',
                'Propriétaires : factures de dépenses multi-lignes imputées directement au propriétaire — la facture détaillée est envoyée par e-mail et téléchargeable ; reçus de versement ajoutés.',
                'Chaque téléchargement de liste est disponible en PDF, XLSX et CSV, y compris le Journal d\'activité.',
                'Sauvegarde du registre : exportez propriétés, unités, locataires et baux, puis restaurez-les plus tard grâce à un import idempotent et sûr.',
                'Nouveaux rapports : Occupation, Balance âgée des impayés et Fonds détenus.',
                'Tableau de bord repensé : taux d\'occupation, tendance des encaissements, baux arrivant à échéance et augmentations de loyer à venir.',
                'La cloche de notification affiche les loyers en retard, les échéances proches, les baux expirant et les augmentations programmées.',
                'Nouvelle page d\'aide intégrée et ce journal des mises à jour ; couleurs actualisées en modes clair et sombre.',
            ],
        ],
        [
            'version' => '1.0.6',
            'date' => '2026-08-21',
            'title' => 'Standardisation du design',
            'changes' => [
                'Un système de design unique : chaque écran suit les mêmes couleurs, panneaux, boutons et formulaires en modes clair et sombre.',
                'Exactement deux tailles de panneaux avec des pieds Annuler/Enregistrer cohérents.',
                'Les augmentations de loyer peuvent être programmées et annulées via l\'API.',
                'Les rapports fonctionnent correctement sur téléphones et tablettes.',
            ],
        ],
        [
            'version' => '1.0.5',
            'date' => '2026-08-20',
            'title' => 'Journal financier et cycle de vie des baux',
            'changes' => [
                'Journal financier immuable en partie double avec plan comptable et bascule des soldes d\'ouverture.',
                'Cycle de vie complet des baux : prolongation, résiliation avec règlement et suppression contrôlée avec aperçu d\'impact.',
                'Dépôts, retraits et ajustements des fonds locataires avec reçus et bons imprimables.',
                'Rapport des paiements avec exports PDF, XLSX et CSV.',
            ],
        ],
        [
            'version' => '1.0.4',
            'date' => '2026-08-19',
            'title' => 'Interface modernisée',
            'changes' => [
                'Coque applicative, panneaux et mode sombre actualisés.',
                'Annonces de version dans la cloche de notification.',
            ],
        ],
        [
            'version' => '1.0.3',
            'date' => '2026-08-17',
            'title' => 'Rôles et piste d\'audit',
            'changes' => [
                'Rôles Administrateur, Gestionnaire et Lecteur avec invitations et flux de mots de passe.',
                'Journal d\'activité infalsifiable avec filtres et exports.',
            ],
        ],
        [
            'version' => '1.0.2',
            'date' => '2026-08-15',
            'title' => 'Deux langues, deux devises',
            'changes' => [
                'Interface, documents et e-mails entièrement en anglais et en français.',
                'Présentation des devises GHS et FCFA.',
            ],
        ],
        [
            'version' => '1.0.1',
            'date' => '2026-08-13',
            'title' => 'Fondation',
            'changes' => [
                'Propriétés, unités, locataires, propriétaires et baux.',
                'Facturation automatique des loyers, allocation FIFO des paiements, fonds locataires et comptabilité propriétaires.',
                'Factures, reçus et rapports PDF avec envoi par e-mail.',
            ],
        ],
    ],
];

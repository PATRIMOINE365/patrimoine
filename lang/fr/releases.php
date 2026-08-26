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
            'version' => '1.0.20',
            'date' => '2026-08-26',
            'title' => 'Un Journal financier qui parle votre langue',
            'changes' => [
                'Les libellés des écritures du journal sont désormais rédigés dans la langue de votre organisation. Les organisations francophones les voyaient jusqu\'ici en anglais ; les nouvelles écritures sont correctement enregistrées, tandis que les écritures existantes conservent le libellé sous lequel elles ont été comptabilisées.',
                'Sur le tableau de bord, les montants s\'alignent proprement même lorsque l\'intitulé d\'une tuile passe à la ligne.',
            ],
        ],
        [
            'version' => '1.0.19',
            'date' => '2026-08-26',
            'title' => 'Un panneau d\'accueil plus vivant',
            'changes' => [
                'L\'aperçu des pages de connexion et d\'inscription montre désormais un véritable espace de travail dans votre langue, et dans le thème opposé à celui de la page — de quoi découvrir Patrimoine 365 dans le mode que vous n\'utilisez pas.',
            ],
        ],
        [
            'version' => '1.0.18',
            'date' => '2026-08-26',
            'title' => 'Finition des e-mails',
            'changes' => [
                'La version texte de chaque e-mail a été nettoyée : le nom Patrimoine 365 n\'y apparaît plus qu\'une seule fois.',
            ],
        ],
        [
            'version' => '1.0.17',
            'date' => '2026-08-26',
            'title' => 'Une meilleure délivrabilité des e-mails et une page Licence simplifiée',
            'changes' => [
                'Chaque e-mail est désormais envoyé à la fois en version enrichie et en texte simple, ce qui l\'aide à atteindre la boîte de réception plutôt que les indésirables ou la quarantaine.',
                'L\'e-mail de vérification explique la suite des opérations et affiche son lien en entier : il se lit comme le message de routine qu\'il est.',
                'La page Licence se concentre désormais sur l\'essentiel au quotidien : votre forfait et votre utilisation par rapport à ses limites. Le comparatif complet des forfaits se trouve sur patrimoine365.com/pricing.',
                'Sur la page Aide, les onglets Guide et Journal des mises à jour s\'alignent maintenant proprement avec le champ de recherche.',
            ],
        ],
        [
            'version' => '1.0.16',
            'date' => '2026-08-26',
            'title' => 'Finitions : boutons de renvoi et e-mails à la nouvelle image',
            'changes' => [
                'Les actions de renvoi de l\'e-mail de vérification sur les pages de connexion et d\'inscription sont désormais de vrais boutons — impossibles à manquer — et un problème de renvoi à la connexion a été corrigé.',
                'Chaque e-mail s\'ouvre maintenant avec le logo Patrimoine 365.',
            ],
        ],
        [
            'version' => '1.0.15',
            'date' => '2026-08-26',
            'title' => 'Des e-mails de vérification qui ne vous laissent jamais bloqué',
            'changes' => [
                'L\'écran de confirmation d\'inscription propose désormais de renvoyer l\'e-mail de vérification — avec un rappel de vérifier les courriers indésirables.',
                'Connexion avant vérification ? L\'erreur s\'accompagne maintenant d\'une action « Renvoyer l\'e-mail de vérification » en un clic.',
                'Les messages d\'erreur de connexion et d\'inscription suivent désormais la langue dans laquelle vous lisez la page — français compris.',
            ],
        ],
        [
            'version' => '1.0.14',
            'date' => '2026-08-26',
            'title' => 'Une porte d\'entrée achevée',
            'changes' => [
                'Les pages de connexion et d\'inscription affichent désormais un aperçu réel du tableau de bord Patrimoine 365, dans votre langue.',
                'Cliquer sur le logo Patrimoine 365 depuis la connexion ou l\'inscription mène à patrimoine365.com.',
            ],
        ],
        [
            'version' => '1.0.13',
            'date' => '2026-08-26',
            'title' => 'Une seule identité partout : le nouveau visage de Patrimoine 365',
            'changes' => [
                'Patrimoine 365 a une nouvelle identité visuelle — le logo maison vert et le nom Patrimoine 365 apparaissent désormais de façon cohérente dans l\'application, sur les pages de connexion, dans les e-mails et sur les documents PDF.',
                'Nouvelle icône : enregistrer Patrimoine 365 sur l\'écran d\'accueil de votre téléphone (Android ou iPhone) ou épingler l\'onglet affiche maintenant le nouveau logo.',
                'Et une devise assortie : « La gestion locative, sans le drame. »',
            ],
        ],
        [
            'version' => '1.0.12',
            'date' => '2026-08-26',
            'title' => 'Un accueil plus chaleureux : thème et langue, à votre goût',
            'changes' => [
                'Les pages de connexion et d\'inscription disposent désormais de leur propre sélecteur clair/sombre et d\'un sélecteur de langue français/anglais, directement sur la page.',
                'Vous arrivez depuis patrimoine365.com ? La langue et le thème choisis sur le site vous suivent maintenant automatiquement jusqu\'aux pages de connexion et d\'inscription.',
                'Le panneau de bienvenue des pages de connexion a été rafraîchi pour s\'accorder au nouveau site patrimoine365.com.',
            ],
        ],
        [
            'version' => '1.0.11',
            'date' => '2026-08-26',
            'title' => 'Rappels de forfait, confirmations de licence et une plateforme renforcée',
            'changes' => [
                'Vos administrateurs reçoivent désormais un e-mail 7 jours et 1 jour avant la fin de votre essai ou de votre licence, expliquant exactement ce qui change sur le forfait Gratuit et comment renouveler.',
                'À chaque attribution ou prolongation de licence pour votre organisation, vos administrateurs reçoivent un e-mail confirmant le nouveau forfait, sa date de début et sa validité.',
                'Patrimoine 365 est désormais exploité via une console d\'administration dédiée : licences, santé des comptes et demandes d\'assistance sont traitées plus rapidement, et chaque action sur votre organisation est enregistrée dans votre propre Journal d\'activité.',
                'Les adresses e-mail des comptes sont mieux protégées : un compte ne peut jamais être déplacé vers une adresse réservée à la plateforme.',
                'Améliorations de sécurité et de fiabilité de la connexion, du traitement des images et de l\'infrastructure de la plateforme.',
            ],
        ],
        [
            'version' => '1.0.10',
            'date' => '2026-08-26',
            'title' => 'Patrimoine 365 : votre organisation, votre espace sécurisé',
            'changes' => [
                'Patrimoine devient Patrimoine 365, une plateforme multi-organisations : chaque organisation travaille dans un espace totalement isolé — ses données ne peuvent jamais être vues, mélangées ou référencées par une autre organisation.',
                'Les nouvelles organisations s\'inscrivent elles-mêmes : la page « Créez votre organisation » provisionne tout en une étape et démarre un essai Professionnel de 30 jours, sans carte bancaire.',
                'Les adresses e-mail sont vérifiées à l\'inscription : un lien de confirmation doit être cliqué avant la première connexion.',
                'Chaque connexion est désormais protégée par une seconde étape : un code de sécurité à 6 chiffres vous est envoyé par e-mail et se saisit dans la même fenêtre de connexion, avec option de renvoi.',
                'Une nouvelle page Licence et forfait (dans Gérer) affiche votre forfait actuel, votre utilisation par rapport à ses limites et une comparaison complète des forfaits Gratuit, Standard et Professionnel.',
                'Les forfaits suivent une règle simple : les baux actifs sont la seule vraie limite — l\'historique, les immeubles et les unités n\'imposent jamais de mise à niveau, et dépasser une limite ne bloque que la création de nouveaux enregistrements, jamais vos données existantes.',
                'Les rappels de loyer et avis d\'augmentation automatiques sont une fonctionnalité Professionnelle, avec un quota mensuel d\'e-mails ; les factures, reçus et justificatifs continuent toujours d\'être envoyés sur tous les forfaits.',
                'De nouvelles pages publiques Conditions Générales d\'Utilisation et Politique de Confidentialité (en français et en anglais) sont acceptées à l\'inscription et liées depuis chaque e-mail.',
                'Chaque e-mail envoyé par Patrimoine a été repensé sur une mise en page professionnelle unique, envoyé au nom de votre organisation avec un pied de page légal complet.',
                'Chaque document PDF — factures, reçus, notes et justificatifs — s\'ouvre désormais avec l\'en-tête de votre organisation et un style cohérent et aligné sur la marque.',
                'Le Journal financier est actif dès le premier jour pour les organisations nouvellement créées.',
                'Les tentatives de connexion échouées, y compris les codes de sécurité erronés, sont enregistrées dans le Journal d\'activité avec les détails de l\'appareil et du navigateur.',
            ],
        ],
        [
            'version' => '1.0.9',
            'date' => '2026-08-26',
            'title' => 'Réglages repensés, Rapports affinés et tableau de bord entièrement câblé',
            'changes' => [
                'Les Réglages sont désormais un espace à onglets — Organisation, Préférences, Données et À propos — avec des liens directs vers chaque onglet.',
                'Sauvegarde des données : chaque registre (Parties, Immeubles, Unités, Baux) s\'exporte en PDF, XLSX ou CSV depuis un seul endroit, à côté de la Sauvegarde complète en un clic.',
                'La restauration d\'une sauvegarde est plus sûre : Vérifier la restauration analyse toujours le fichier d\'abord et montre exactement ce qui changerait avant que vous confirmiez la restauration.',
                'Les Rapports ne gardent plus de résultats périmés : modifier un filtre ou une date après une exécution estompe les résultats et désactive les exports jusqu\'à la prochaine exécution.',
                'Les résultats affichent leur date de référence et le nombre de lignes ; chaque rapport PDF porte désormais l\'en-tête de l\'organisation, et les exports CSV/XLSX incluent les totaux récapitulatifs déjà présents dans les PDF.',
                'Les colonnes des rapports à l\'écran et dans les exports correspondent désormais (Arriérés et Fonds détenus ont gagné la colonne Bail), et les montants sont alignés à droite partout.',
                'Rapports sur téléphone : la liste des rapports devient un sélecteur compact, et les champs de date ouvrent le même calendrier Patrimoine qu\'ailleurs.',
                'Tableau de bord : Encaissé ce mois-ci et la tendance des encaissements ne comptent plus que l\'argent des loyers — les règlements de dépenses et les approvisionnements de fonds ne les gonflent plus.',
                'Les lignes du tableau de bord mènent directement au locataire, affichent les numéros de facture et l\'avancement des paiements partiels, et de nouvelles tuiles présentent les frais de gestion et les prochaines augmentations de loyer.',
                'Le journal d\'activité enregistre l\'appareil, le navigateur, la plateforme et l\'adresse IP de chaque action, visibles sur chaque événement et dans tous les exports.',
                'Les volets d\'argent du locataire portent des titres cohérents avec ceux du propriétaire : Enregistrer un dépôt, un retrait, une dépense ou un ajustement de solde.',
                'Une passe visuelle sur chaque page et chaque volet : boutons, libellés, badges et couleurs du mode sombre cohérents dans toute l\'application.',
            ],
        ],
        [
            'version' => '1.0.8',
            'date' => '2026-08-26',
            'title' => 'Dépenses payables, comptes propriétaires et documents plus rapides',
            'changes' => [
                'L\'argent du propriétaire est désormais réparti sur deux comptes : le compte Retraits (issu des loyers, retirable) et le compte Dépôts/Dépenses, avec des transferts entre eux et des récépissés imprimables.',
                'Les dépenses ne se règlent plus toutes seules : une dépense locataire crée une facture EXP détaillée et une facture de dépenses propriétaire reste impayée jusqu\'à son paiement explicite.',
                'Nouvelles sections Dépenses sur les pages Locataires et Propriétaires, avec l\'état de paiement de chaque document.',
                'Bouton Payer sur les factures : réglez tout montant depuis le compte de votre choix (comptes de fonds du locataire, ou compte Dépôts/Retraits du propriétaire) avec une étape de vérification avant enregistrement.',
                'Les paiements ainsi enregistrés peuvent être annulés : l\'annulation reverse l\'argent, passe une contre-écriture immuable au journal et est entièrement tracée.',
                'Reçus de paiement : les factures payées offrent un reçu téléchargeable listant chaque paiement.',
                'Tiroir de dépenses propriétaire : facturez un propriétaire directement ou répartissez entre tous les propriétaires d\'un immeuble selon leur pourcentage ; chaque copropriétaire reçoit sa propre facture par e-mail.',
                'Les dépôts propriétaires enregistrent leur objet, et les reçus de dépôt s\'ouvrent de manière fiable.',
                'La création de bail gagne une étape de vérification, des préréglages de durée et de préavis, et des sélecteurs de locataire/agent avec recherche ; les trois comptes de fonds du locataire sont créés avec le bail.',
                'Les suppressions irréversibles exigent désormais une confirmation saisie et votre mot de passe.',
                'Documents nettement plus rapides : des PDF jusqu\'à 36 fois plus légers, ouverts nativement dans un onglet du navigateur.',
                'La cloche de notifications affiche aussi les factures de dépenses impayées, côté locataires comme côté propriétaires.',
            ],
        ],
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

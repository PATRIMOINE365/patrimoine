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
            'through' => '1.0.45',
            'date' => '2026-08-31',
            'summary' => 'Le reçu de versement d\'un propriétaire détaille chaque mouvement derrière le montant — les loyers par lot et par période, les honoraires et la taxe correspondante, et chaque charge — de sorte qu\'il puisse les additionner et retrouver ce qui lui a été versé. Chaque bail dispose d\'un bouton Consulter qui ouvre la location en entier sur un seul écran. Un enregistrement que Patrimoine refuse de supprimer peut désormais être archivé : il quitte les listes et les menus déroulants, conserve chaque document et chaque écriture qui le nomme, et revient depuis la nouvelle page Archives quand vous le souhaitez ; les listes elles-mêmes sont environ deux fois moins hautes, et le type et les rôles d\'un tiers portent leur propre couleur.',
        ],
        [
            'through' => '1.0.40',
            'date' => '2026-08-29',
            'summary' => 'L\'Aide est devenue le Support : une entrée de votre menu ouvre une page où vous pouvez nous écrire, avec le guide, les codes d\'erreur et le journal des mises à jour à côté. Le guide se lit un guide à la fois, trouvé par recherche plutôt qu\'en faisant défiler tout le reste, et ses images n\'écrasent plus le texte qu\'elles illustrent. La Comptabilité lit les dates comme le reste de Patrimoine, chaque numéro de document porte son année, et la plupart des messages que Patrimoine envoie partent après votre demande plutôt que pendant. Patrimoine a par ailleurs été redessiné : l\'application, la console, le site, les courriels et les documents partagent désormais une seule conception, une typographie servie par Patrimoine lui-même et non par Google, une seule marque et une seule famille d\'icônes, et le Journal d\'activité et le Journal financier sont devenus deux onglets d\'une seule entrée appelée Audit, l\'assistant de bail présente chaque montant groupé et dans votre devise et dit quel champ il refuse, votre navigateur ne remplit plus les formulaires ailleurs que sur les pages de connexion et d\'inscription, et le reçu de versement d\'un propriétaire montre désormais ce qui est entré et ce qui a été déduit pour arriver au montant versé',
        ],
        [
            'through' => '1.0.35',
            'date' => '2026-08-29',
        'summary' => 'Votre photographie apparaît désormais en haut de l’écran, l’assistant de bail peut être laissé en plan puis repris, les longues listes se lisent page par page, et Utilisateurs et Licence sont devenus des onglets de Paramètres, qui résume aussi votre compte et permet à un administrateur de le fermer définitivement. Chacun peut télécharger tout ce qui le concerne, un administrateur peut produire ou effacer les données d’une personne, la politique de confidentialité dit clairement ce qui est conservé et combien de temps, et le guide couvre chaque tâche pas à pas avec des images des écrans — il est aussi public sur patrimoine365.com. Les organisations travaillant en français lisent désormais le français partout, y compris dans le journal d’activité et dans les messages de votre propre navigateur, chaque refus renvoie à la bonne explication, la cloche signale les sommes reçues qui n’ont pas encore été affectées au compte d’un locataire, et le journal d’activité comme le journal financier s’exportent en XLSX ou en CSV plutôt qu’en PDF',
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

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
            'through' => '1.0.55',
            'date' => '2026-09-05',
            'summary' => 'La console d\'administration a été renforcée après un audit complet : un e-mail reçu ne peut plus rien y exécuter, chaque page porte des en-têtes de protection du navigateur émis par l\'application elle-même sur chaque hôte, et une seule règle de mot de passe — douze caractères, majuscules et minuscules, un chiffre — s\'applique partout. La console redemande votre propre mot de passe avant de suspendre, révoquer, désactiver ou déplacer une adresse, compte et refuse les mots de passe erronés répétés partout où l\'un est demandé, confirme chaque action visible par le client avec une raison conservée dans le journal, propose les agents de l\'organisation elle-même pour corriger un bail, liste chaque lecture et correction des données d\'un client dans son propre journal, termine une session du personnel après une heure d\'inactivité, et prévient par écrit les administrateurs d\'un client lorsque leur organisation est supprimée.',
        ],
        [
            'through' => '1.0.50',
            'date' => '2026-09-05',
            'summary' => 'Il n\'y a plus qu\'une façon de créer une location : Ajouter un bail ouvre l\'assistant, et l\'ancien formulaire est retiré ; l\'assistant trouve un bien, un lot, un locataire, un agent ou un propriétaire en tapant plutôt qu\'en faisant défiler une liste qui ne contenait que les cent premiers, une personne ajoutée en chemin peut recevoir aussitôt son autorisation d\'e-mail, et la TVA sur vos honoraires démarre au taux défini par votre organisation. Le reçu de versement à un propriétaire est lui aussi devenu un document historique : il indique ce que contenait le compte au moment du versement, un paiement enregistré ensuite avec une date antérieure appartient au versement suivant plutôt que de réécrire un versement déjà effectué, et les deux comptes d\'un propriétaire sont comptés séparément de bout en bout — une dette côté dépôt ne bloque plus un retrait valable, un transfert de réserve reste sur sa propre ligne sans gonfler un relevé, un versement peut dire que son argent venait d\'un dépôt restitué, et un montant partagé ne peut plus arrondir personne en dessous de zéro. Votre adresse e-mail de connexion se change désormais par trois étapes vérifiées qui lui sont propres — votre mot de passe, un code à votre adresse actuelle, un code à la nouvelle — et personne d\'autre ne peut plus la réécrire à votre place ; et un audit complet de la 1.0.49 a trouvé sa réponse dans la 1.0.50 : rien de votre organisation n\'apparaît sur l\'écran de connexion ni dans un e-mail de réinitialisation avant votre connexion, une résiliation s\'achève après un dépôt réglé avec des retenues, un brouillon impayé peut être supprimé, chaque limite de connexion et de codes compte pour elle-même, les reçus sont numérotés par votre organisation comme tout autre document, l\'assistant ne démarre plus avec une avance marquée reçue, un propriétaire doit être enregistré comme tel, et fermer votre organisation vous envoie une confirmation écrite.',
        ],
        [
            'through' => '1.0.45',
            'date' => '2026-08-31',
            'summary' => 'Le reçu de versement d\'un propriétaire détaille chaque mouvement derrière le montant — les loyers par lot et par période, les honoraires et la taxe correspondante, et chaque charge — et chaque bail dispose d\'un bouton Consulter qui ouvre la location en entier sur un seul écran. Le dépôt de garantie saisi sur un bail est désormais réellement encaissé sur le compte de dépôt du locataire, l\'argent reçu avant le début de la location est accepté à sa vraie date, une location commencée le 31 conserve le 31 pendant toute sa durée, et l\'assistant demande exactement ce que demande le formulaire de bail, une page par section. Un enregistrement que Patrimoine refuse de supprimer peut désormais être archivé — l\'archivage explique ce qu\'il fait et demande pourquoi, l\'enregistrement quitte les listes et les menus déroulants tandis que chaque document et chaque écriture continue de le nommer, et il revient depuis la page Archives quand vous le souhaitez ; les listes elles-mêmes sont environ deux fois moins hautes, et le type et les rôles d\'un tiers portent leur propre couleur ; les Paramètres listent désormais tous les appareils sur lesquels votre compte est connecté et permettent d’en déconnecter n’importe lequel sur-le-champ, et une session inutilisée prend fin d’elle-même au lieu de durer indéfiniment ; l’assistant de bail demande l’agent à côté de la commission qui lui revient et s’ouvre toujours sur sa première page, Payé tous les s’écrit en mois plutôt qu’en noms de périodes, les tiroirs Prolonger et Résilier retrouvent leurs sélecteurs de date, et l’historique financier d’une location est désormais un tableau paginé dans le grand tiroir.',
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

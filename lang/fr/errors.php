<?php

/*
|--------------------------------------------------------------------------
| Error codes — French
|--------------------------------------------------------------------------
|
| One entry per code: what the person saw, why it happened, and what to
| do about it. The codes themselves, their families and who can act on
| them live in config/error_codes.php.
|
| These words are read by people who are already stuck, so they say what
| happened in plain terms and then what to do. They do not blame the
| reader, and they never end without a next step.
|
*/

return [

    'PM-9901' => [
        'title' => 'Page introuvable',
        'what' => 'L’adresse demandée n’existe pas dans Patrimoine. En général, un lien est périmé, une adresse a été mal saisie, ou l’enregistrement visé a été supprimé depuis.',
        'fix' => 'Utilisez le menu pour rejoindre votre destination. Si un lien interne à Patrimoine vous a mené ici, dites-nous lequel : un lien qui ne mène nulle part est notre erreur, pas la vôtre.',
    ],

    'PM-9902' => [
        'title' => 'Votre session a expiré',
        'what' => 'Patrimoine vous déconnecte après une période d’inactivité, afin qu’un écran laissé sans surveillance ne serve pas à quelqu’un d’autre. La page restait ouverte, mais la session derrière elle était déjà fermée.',
        'fix' => 'Reconnectez-vous. Tout ce qui était enregistré est intact ; ce qui était saisi dans un formulaire sans être enregistré devra l’être de nouveau.',
    ],

    'PM-9903' => [
        'title' => 'Trop de tentatives',
        'what' => 'Patrimoine limite la fréquence de répétition d’une même action. Cela protège les comptes contre les tentatives de devinette et garde le service réactif pour tout le monde.',
        'fix' => 'Patientez une minute, puis réessayez. Si vous tentiez de vous connecter sans vous souvenir du mot de passe, utilisez « Mot de passe oublié » plutôt que de deviner à nouveau.',
    ],

    'PM-9904' => [
        'title' => 'Un problème est survenu de notre côté',
        'what' => 'Patrimoine a rencontré une erreur imprévue en traitant la demande. Rien de ce que vous enregistriez n’a été perdu : une action qui échoue ainsi n’est pas à moitié enregistrée, elle n’a simplement pas lieu.',
        'fix' => 'Réessayez une fois, au cas où l’incident serait passager. S’il se reproduit, contactez-nous avec ce code et la description de votre action. Chacune de ces erreurs est enregistrée chez nous avec assez de détails pour la retrouver.',
    ],

    'PM-9905' => [
        'title' => 'Patrimoine est momentanément indisponible',
        'what' => 'Le service est en cours de mise à jour ou de redémarrage. Cela dure normalement quelques secondes, sans aucun risque pour les données.',
        'fix' => 'Patientez un instant et rechargez la page. Si cela dure plus de quelques minutes, contactez-nous : il ne s’agit alors plus d’une mise à jour de routine.',
    ],

    'PM-9906' => [
        'title' => 'Patrimoine est injoignable',
        'what' => 'Le navigateur n’a pas pu joindre le service. Soit cet appareil est hors ligne, soit un élément entre lui et Patrimoine — réseau, pare-feu, connexion mobile — bloque le passage.',
        'fix' => 'Vérifiez que d’autres sites s’ouvrent. Si c’est le cas et que Patrimoine reste injoignable, dites-le-nous : le problème est alors de notre ressort.',
    ],

    /* ---- 1xxx ---- */

    'PM-1001' => [
        'title' => 'Ce compte a été désactivé.',
        'what' => 'Ce compte a été désactivé par un administrateur de votre organisation. Il existe toujours, avec tout son historique — il ne peut simplement plus se connecter.',
        'fix' => 'Demandez à un administrateur de le réactiver depuis la page Utilisateurs. Si personne ne se souvient de l’avoir désactivé, le journal d’activité indique qui l’a fait et quand.',
    ],

    'PM-1002' => [
        'title' => 'Vous n’êtes pas autorisé à effectuer cette action.',
        'what' => 'Votre rôle ne comprend pas cette action. Patrimoine a trois rôles, chacun avec sa portée : l’administrateur gère tout, le gestionnaire assure le quotidien, et le lecteur consulte sans modifier.',
        'fix' => 'Demandez à un administrateur de votre organisation de modifier votre rôle depuis la page Utilisateurs, ou d’effectuer cette action à votre place.',
    ],

    'PM-1003' => [
        'title' => 'Les identifiants fournis sont incorrects.',
        'what' => 'L’adresse e-mail et le mot de passe ne correspondent pas ensemble à un compte actif. Par sécurité, Patrimoine ne précise pas lequel des deux est en cause.',
        'fix' => 'Vérifiez l’adresse e-mail et ressaisissez le mot de passe. Si vous l’avez oublié, utilisez « Mot de passe oublié » sur la page de connexion. Si le compte vient d’être désactivé, un administrateur de votre organisation peut le réactiver.',
    ],

    'PM-1004' => [
        'title' => 'Cette tentative de connexion a expiré. Reconnectez-vous pour recevoir un nouveau code.',
        'what' => 'Les codes de connexion sont valables peu de temps. Trop de temps s’est écoulé entre la demande du code et sa saisie ; il n’est plus accepté.',
        'fix' => 'Reconnectez-vous avec votre adresse e-mail et votre mot de passe. Un nouveau code part immédiatement : c’est celui à utiliser.',
    ],

    'PM-1005' => [
        'title' => 'Le code de vérification est incorrect.',
        'what' => 'Les six chiffres saisis ne correspondent pas au code envoyé par Patrimoine pour cette connexion.',
        'fix' => 'Vérifiez l’e-mail le plus récent — un ancien code d’une tentative précédente ne fonctionne pas. Saisissez les six chiffres sans espaces. Si aucun e-mail n’est arrivé, regardez dans les indésirables, puis demandez un nouveau code.',
    ],

    'PM-1006' => [
        'title' => 'Cette organisation est actuellement suspendue. Contactez support@patrimoine365.com.',
        'what' => 'Cette organisation est suspendue : plus personne ne peut s’y connecter. La suspension est décidée par Patrimoine, généralement pour un abonnement impayé ou à la demande de l’organisation elle-même.',
        'fix' => 'Vos données sont intactes et reviennent telles quelles dès la levée de la suspension. Contactez-nous pour en connaître la raison et les conditions de rétablissement.',
    ],

    'PM-1007' => [
        'title' => 'Le mot de passe est incorrect.',
        'what' => 'Le mot de passe saisi pour confirmer cette action ne correspond pas à celui de votre connexion.',
        'fix' => 'Ressaisissez votre mot de passe de connexion. Si vous l’avez oublié, déconnectez-vous, utilisez « Mot de passe oublié », puis revenez à cette action.',
    ],

    'PM-1008' => [
        'title' => 'Terminez la configuration de votre compte avant de vous connecter.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-1009' => [
        'title' => 'Vous devez être authentifié pour effectuer cette action.',
        'what' => 'Patrimoine n’a pas reconnu la session à l’origine de cette demande. En général, elle s’était simplement terminée pendant que la page restait ouverte.',
        'fix' => 'Reconnectez-vous. Si vous étiez au milieu d’un formulaire, copiez ce que vous aviez saisi avant de le faire : une session terminée ne se reprend pas.',
    ],

    'PM-1010' => [
        'title' => 'Vérifiez votre adresse e-mail avant de vous connecter. Consultez votre boîte de réception pour trouver le lien de vérification.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-1011' => [
        'title' => 'Impossible de se connecter.',
        'what' => 'La demande de connexion n’a pas abouti. Il ne s’agit pas d’un mot de passe erroné : Patrimoine n’est pas allé jusqu’à le vérifier.',
        'fix' => 'Vérifiez que d’autres sites s’ouvrent, puis réessayez. Si votre connexion fonctionne et que le problème persiste, signalez-le-nous : une connexion qui échoue relève de nous.',
    ],

    'PM-1012' => [
        'title' => 'La confirmation du mot de passe ne correspond pas.',
        'what' => 'Les deux cases ne contiennent pas la même chose. Patrimoine demande deux fois précisément pour qu’une faute de frappe ne vous bloque pas plus tard.',
        'fix' => 'Ressaisissez les deux cases, lentement. Si votre navigateur en a rempli une automatiquement, videz-les d’abord toutes les deux.',
    ],

    'PM-1013' => [
        'title' => 'Le mot de passe actuel est incorrect.',
        'what' => 'Patrimoine demande votre propre mot de passe avant une action irréversible, afin qu’un écran laissé sans surveillance ne serve pas à détruire des enregistrements.',
        'fix' => 'Saisissez le mot de passe avec lequel vous vous connectez. Si vous l’avez oublié, déconnectez-vous, utilisez « Mot de passe oublié », puis revenez.',
    ],

    'PM-1014' => [
        'title' => 'Ce lien de réinitialisation du mot de passe est invalide ou a expiré.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-1015' => [
        'title' => 'Cette demande de mot de passe n’a pas abouti.',
        'what' => 'Une requête n’a pas abouti. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si le problème persiste, dites-nous ce que vous faisiez.',
    ],

    'PM-1016' => [
        'title' => 'Ce fichier n\'a pas pu être lu comme une image. Utilisez un JPG, PNG, WEBP ou GIF.',
        'what' => 'Le fichier n’a pas pu être lu comme une image. Soit ce n’est pas une image, soit le format n’est pas décodable par ce navigateur.',
        'fix' => 'Utilisez un fichier JPG, PNG ou WEBP. Les photos HEIC d’iPhone ne fonctionnent que dans Safari : sur les autres navigateurs, exportez d’abord la photo en JPG.',
    ],

    'PM-1017' => [
        'title' => 'Photo de profil supprimée.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-1018' => [
        'title' => 'Ce domaine e-mail est réservé. Contactez support@patrimoine365.com.',
        'what' => 'Cette action requiert un niveau d’accès, ou un forfait, dont le compte ne dispose pas actuellement.',
        'fix' => 'Adressez-vous à un administrateur de votre organisation. Il peut modifier les rôles depuis la page Utilisateurs et consulter le forfait et ses limites sur la page Licence.',
    ],

    'PM-1019' => [
        'title' => 'Ce lien de vérification est invalide ou a expiré. Demandez-en un nouveau depuis la page de connexion.',
        'what' => 'Un lien de vérification ne sert qu’une fois et expire. Celui-ci a déjà été utilisé ou a dépassé sa validité.',
        'fix' => 'Saisissez votre adresse e-mail sur la page de vérification et demandez un nouveau lien. Ouvrez l’e-mail le plus récent : les anciens liens restent inactifs.',
    ],

    'PM-1020' => [
        'title' => 'Commencez votre essai Professionnel de 30 jours. Aucune carte bancaire requise.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-1021' => [
        'title' => 'Impossible de créer votre organisation.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-1022' => [
        'title' => 'La configuration de votre compte est terminée. Vous pouvez maintenant vous connecter.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-1023' => [
        'title' => 'Une invitation ne peut pas être envoyée à un utilisateur inactif.',
        'what' => 'Le compte est désactivé, et Patrimoine n’invite personne à un compte inutilisable.',
        'fix' => 'Activez d’abord le compte depuis la page Utilisateurs. L’invitation part d’elle-même dès l’activation.',
    ],

    'PM-1024' => [
        'title' => 'Ce lien d’invitation est invalide ou a expiré.',
        'what' => 'Ce lien d’invitation a expiré, a déjà été accepté, ou a été remplacé par une invitation plus récente envoyée à la même personne.',
        'fix' => 'Demandez à un administrateur de votre organisation de renvoyer l’invitation depuis la page Utilisateurs, puis ouvrez l’e-mail le plus récent.',
    ],

    'PM-1025' => [
        'title' => 'Le rôle d’un administrateur doit être modifié par un autre administrateur.',
        'what' => 'Un administrateur ne peut pas réduire son propre rôle : l’organisation risquerait de n’avoir plus personne pour le rétablir.',
        'fix' => 'Demandez à un autre administrateur de modifier votre rôle.',
    ],

    'PM-1026' => [
        'title' => 'Un compte ne peut pas se supprimer lui-même. Un autre administrateur peut le faire.',
        'what' => 'Vous ne pouvez pas supprimer le compte avec lequel vous êtes connecté.',
        'fix' => 'Demandez à un autre administrateur de le supprimer une fois que vous serez connecté autrement.',
    ],

    'PM-1027' => [
        'title' => 'Un compte ne peut pas se désactiver lui-même. Un autre administrateur peut le faire.',
        'what' => 'Vous êtes connecté avec ce compte : le désactiver vous déconnecterait en pleine action.',
        'fix' => 'Demandez à un autre administrateur de le faire, ou connectez-vous d’abord avec un autre compte administrateur.',
    ],

    'PM-1028' => [
        'title' => 'Cette action ne peut pas être effectuée, car Patrimoine doit conserver au moins un administrateur actif.',
        'what' => 'Chaque organisation doit conserver au moins un administrateur actif, sans quoi plus personne ne pourrait gérer les utilisateurs, les paramètres ni les licences.',
        'fix' => 'Nommez d’abord une autre personne administrateur, puis recommencez.',
    ],

    'PM-1029' => [
        'title' => 'Les comptes du personnel de la plateforme doivent utiliser une adresse @patrimoine365.com.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-1030' => [
        'title' => 'Ce domaine e-mail est réservé au personnel de la plateforme.',
        'what' => 'Cette action requiert un niveau d’accès, ou un forfait, dont le compte ne dispose pas actuellement.',
        'fix' => 'Adressez-vous à un administrateur de votre organisation. Il peut modifier les rôles depuis la page Utilisateurs et consulter le forfait et ses limites sur la page Licence.',
    ],

    'PM-1031' => [
        'title' => 'Cette modification de l’utilisateur n’a pas été enregistrée.',
        'what' => 'Une requête n’a pas abouti. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si le problème persiste, dites-nous ce que vous faisiez.',
    ],

    'PM-1032' => [
        'title' => 'Impossible de créer l’utilisateur.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-1033' => [
        'title' => 'Impossible de supprimer l’utilisateur.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-1034' => [
        'title' => 'Impossible de charger les utilisateurs.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-1035' => [
        'title' => 'Impossible de mettre à jour l’utilisateur.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-1036' => [
        'title' => 'Lien invalide ou expiré',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-1037' => [
        'title' => 'Impossible d\'envoyer un nouveau lien pour le moment.',
        'what' => 'Le message n’a pas pu être remis au service d’envoi. Le document, lui, a bien été créé et reste disponible.',
        'fix' => 'Vérifiez que la partie a une adresse e-mail valide, puis renvoyez. Si l’adresse est correcte et que l’échec persiste, téléchargez le document et envoyez-le vous-même le temps que nous examinions la cause.',
    ],

    'PM-1038' => [
        'title' => "Le nom saisi n'est pas celui de votre organisation.",
        'what' => 'Fermer un compte détruit tout ce qu’il contient : le nom doit donc être resaisi à l’identique avant que l’opération ne s’exécute. Ce qui a été saisi ne correspond pas.',
        'fix' => 'Recopiez le nom exactement tel que les Paramètres l’affichent, majuscules, espaces et ponctuation comprises. Si vous ne vouliez pas fermer le compte, fermez plutôt ce panneau.',
    ],

    'PM-1039' => [
        'title' => 'Ce compte ne peut pas être fermé depuis cet écran.',
        'what' => 'Vous êtes connecté à l’organisation qui exploite Patrimoine. La fermer emporterait la plateforme avec elle : l’écran refuse donc.',
        'fix' => "Rien à corriger. Si un compte doit réellement être fermé, faites-le depuis la console d'administration, sur l'organisation cliente concernée.",
    ],

    'PM-1040' => [
        'title' => 'Cette personne a déjà été effacée.',
        'what' => "L'effacement ne s'exécute qu'une fois. La fiche que vous consultez porte une référence et non un nom parce que quelqu'un l'a déjà demandé, et il ne reste plus rien d'identifiant à retirer.",
        'fix' => "Rien à faire. Si vous vous attendiez à voir un nom, vous consultez une fiche effacée sur demande : le journal d'activité indique quand, et qui l'a autorisé.",
    ],

    'PM-1041' => [
        'title' => 'Votre propre organisation ne peut pas être effacée.',
        'what' => "La partie choisie est l'organisation gestionnaire — votre propre société. Chaque facture, reçu et relevé la désigne comme émettrice : l'effacer empêcherait ces documents de dire qui les a produits.",
        'fix' => "Si les coordonnées de votre société ont changé, modifiez-les. Si vous cessez l'activité, fermez le compte depuis le bas des Paramètres plutôt que d'effacer la partie.",
    ],

    'PM-1042' => [
        'title' => 'Le nom saisi ne correspond pas à celui de la fiche.',
        'what' => "Effacer une personne détruit définitivement son nom, ses coordonnées et tout ce qui l'identifie : le nom doit donc être resaisi à l'identique avant exécution.",
        'fix' => "Recopiez le nom exactement tel que la fiche l'affiche, majuscules, espaces et ponctuation comprises. Si vous ne vouliez effacer personne, fermez plutôt ce panneau.",
    ],

    /* ---- 2xxx ---- */

    'PM-2001' => [
        'title' => 'Le champ ce champ doit être accepté.',
        'what' => 'Une case obligatoire est restée décochée — en général l’acceptation des conditions.',
        'fix' => 'Cochez la case pour continuer. Si vous préférez lire le document d’abord, le lien à côté l’ouvre dans un nouvel onglet.',
    ],

    'PM-2002' => [
        'title' => 'Le champ ce champ doit être accepté lorsque :other vaut :value.',
        'what' => 'Une case obligatoire est restée décochée — en général l’acceptation des conditions.',
        'fix' => 'Cochez la case pour continuer. Si vous préférez lire le document d’abord, le lien à côté l’ouvre dans un nouvel onglet.',
    ],

    'PM-2003' => [
        'title' => 'Le champ ce champ doit être une URL valide.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2004' => [
        'title' => 'Le champ ce champ doit être une date postérieure au :date.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2005' => [
        'title' => 'Le champ ce champ doit être une date postérieure ou égale au :date.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2006' => [
        'title' => 'Le champ ce champ ne doit contenir que des lettres.',
        'what' => 'Ce champ n’accepte qu’un jeu de caractères limité, et la saisie comporte autre chose.',
        'fix' => 'Retirez la ponctuation, les accents ou les symboles que le champ n’accepte pas. Si un nom en comporte réellement, saisissez ici l’orthographe la plus simple et notez la version complète dans les remarques.',
    ],

    'PM-2007' => [
        'title' => 'Le champ ce champ ne doit contenir que des lettres, des chiffres, des tirets et des underscores.',
        'what' => 'Ce champ n’accepte qu’un jeu de caractères limité, et la saisie comporte autre chose.',
        'fix' => 'Retirez la ponctuation, les accents ou les symboles que le champ n’accepte pas. Si un nom en comporte réellement, saisissez ici l’orthographe la plus simple et notez la version complète dans les remarques.',
    ],

    'PM-2008' => [
        'title' => 'Le champ ce champ ne doit contenir que des lettres et des chiffres.',
        'what' => 'Ce champ n’accepte qu’un jeu de caractères limité, et la saisie comporte autre chose.',
        'fix' => 'Retirez la ponctuation, les accents ou les symboles que le champ n’accepte pas. Si un nom en comporte réellement, saisissez ici l’orthographe la plus simple et notez la version complète dans les remarques.',
    ],

    'PM-2009' => [
        'title' => 'Le champ ce champ doit être un tableau.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2010' => [
        'title' => 'Le champ ce champ doit être une date antérieure au :date.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2011' => [
        'title' => 'Le champ ce champ doit être une date antérieure ou égale au :date.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2012' => [
        'title' => 'Le champ ce champ doit contenir entre :min et :max éléments.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2013' => [
        'title' => 'Le champ ce champ doit avoir une taille comprise entre :min et :max kilo-octets.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2014' => [
        'title' => 'Le champ ce champ doit être compris entre :min et :max.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2015' => [
        'title' => 'Le champ ce champ doit contenir entre :min et :max caractères.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2016' => [
        'title' => 'Le champ ce champ doit être vrai ou faux.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2017' => [
        'title' => 'La confirmation du champ ce champ ne correspond pas.',
        'what' => 'Les deux cases ne contiennent pas la même chose. Patrimoine demande deux fois précisément pour qu’une faute de frappe ne vous bloque pas plus tard.',
        'fix' => 'Ressaisissez les deux cases, lentement. Si votre navigateur en a rempli une automatiquement, videz-les d’abord toutes les deux.',
    ],

    'PM-2018' => [
        'title' => 'Le champ ce champ doit être une date valide.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2019' => [
        'title' => 'Le champ ce champ doit être une date égale au :date.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2020' => [
        'title' => 'Le champ ce champ doit respecter le format :format.',
        'what' => 'La date n’a pas été comprise sous la forme saisie.',
        'fix' => 'Utilisez le sélecteur de date plutôt que la saisie manuelle : le format est alors garanti. Si vous saisissez à la main, suivez l’exemple indiqué à côté du champ.',
    ],

    'PM-2021' => [
        'title' => 'Le champ ce champ doit comporter :decimal décimales.',
        'what' => 'Ce champ attend un nombre, et la valeur saisie n’en est pas un que Patrimoine puisse lire.',
        'fix' => 'Saisissez uniquement des chiffres — sans symbole monétaire ni lettres. Les montants dans Patrimoine sont exprimés en unités entières de votre devise : n’indiquez pas de décimales.',
    ],

    'PM-2022' => [
        'title' => 'Les champs ce champ et :other doivent être différents.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2023' => [
        'title' => 'Le champ ce champ doit comporter :digits chiffres.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2024' => [
        'title' => 'Le champ ce champ doit comporter entre :min et :max chiffres.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2025' => [
        'title' => 'Le champ ce champ doit être une adresse e-mail valide.',
        'what' => 'L’adresse ne ressemble pas à une adresse e-mail : Patrimoine ne l’enregistre pas — une adresse inutilisable est pire que rien.',
        'fix' => 'Vérifiez l’absence de @, un espace parasite ou une faute dans le domaine. Laissez le champ vide si la personne n’a pas d’e-mail : Patrimoine ne lui enverra simplement rien.',
    ],

    'PM-2026' => [
        'title' => 'Le champ ce champ doit se terminer par l’une des valeurs suivantes : :values.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-2027' => [
        'title' => 'La valeur sélectionnée pour ce champ est invalide.',
        'what' => 'L’enregistrement choisi n’existe plus, ou n’appartient pas à votre organisation. Il a peut-être été supprimé pendant que cette page était ouverte.',
        'fix' => 'Rechargez la page pour actualiser les listes, puis refaites votre choix.',
    ],

    'PM-2028' => [
        'title' => 'Le champ ce champ doit être un entier.',
        'what' => 'Ce champ attend un nombre, et la valeur saisie n’en est pas un que Patrimoine puisse lire.',
        'fix' => 'Saisissez uniquement des chiffres — sans symbole monétaire ni lettres. Les montants dans Patrimoine sont exprimés en unités entières de votre devise : n’indiquez pas de décimales.',
    ],

    'PM-2029' => [
        'title' => 'Le champ ce champ ne doit pas contenir plus de :max éléments.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-2030' => [
        'title' => 'Le champ ce champ ne doit pas dépasser :max kilo-octets.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2031' => [
        'title' => 'Le champ ce champ ne doit pas être supérieur à :max.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2032' => [
        'title' => 'Le champ ce champ ne doit pas dépasser :max caractères.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2033' => [
        'title' => 'Le champ ce champ doit contenir au moins :min éléments.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-2034' => [
        'title' => 'Le champ ce champ doit avoir une taille d’au moins :min kilo-octets.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2035' => [
        'title' => 'Le champ ce champ doit être au moins égal à :min.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2036' => [
        'title' => 'Le champ ce champ doit contenir au moins :min caractères.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2038' => [
        'title' => 'Le champ ce champ doit être un nombre.',
        'what' => 'Ce champ attend un nombre, et la valeur saisie n’en est pas un que Patrimoine puisse lire.',
        'fix' => 'Saisissez uniquement des chiffres — sans symbole monétaire ni lettres. Les montants dans Patrimoine sont exprimés en unités entières de votre devise : n’indiquez pas de décimales.',
    ],

    'PM-2039' => [
        'title' => 'Le format du champ ce champ est invalide.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2040' => [
        'title' => 'Le champ ce champ est obligatoire.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2041' => [
        'title' => 'Le champ ce champ est obligatoire lorsque :other vaut :value.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2042' => [
        'title' => 'Le champ ce champ est obligatoire sauf si :other est dans :values.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2043' => [
        'title' => 'Le champ ce champ est obligatoire lorsque :values est présent.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2044' => [
        'title' => 'Le champ ce champ est obligatoire lorsque :values n’est pas présent.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2045' => [
        'title' => 'Le champ ce champ doit correspondre à :other.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-2046' => [
        'title' => 'Le champ ce champ doit contenir :size éléments.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-2047' => [
        'title' => 'Le champ ce champ doit avoir une taille de :size kilo-octets.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2048' => [
        'title' => 'Le champ ce champ doit être égal à :size.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2049' => [
        'title' => 'Le champ ce champ doit contenir :size caractères.',
        'what' => 'La saisie est trop longue ou trop courte pour ce champ.',
        'fix' => 'Le message indique la limite. Raccourcissez ou complétez la saisie en conséquence. Pour une explication longue, utilisez le champ Remarques, prévu pour cela.',
    ],

    'PM-2050' => [
        'title' => 'Le champ ce champ doit être une chaîne de caractères.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2051' => [
        'title' => 'La valeur du champ ce champ est déjà utilisée.',
        'what' => 'Un autre enregistrement possède déjà cette valeur, et Patrimoine la garde unique pour éviter toute confusion ultérieure.',
        'fix' => 'Recherchez d’abord l’enregistrement existant : c’est généralement celui que vous vouliez. Si les deux existent réellement, distinguez-les, par exemple par un second prénom ou un numéro de lot.',
    ],

    'PM-2052' => [
        'title' => 'La date de réception de l’avance ne peut pas être antérieure à la date de début du bail.',
        'what' => 'La valeur sort de la plage autorisée pour ce champ — un pourcentage supérieur au total, ou une date antérieure au début du bail.',
        'fix' => 'Ramenez la valeur dans la plage indiquée par le message. Une date ne peut pas précéder le début du bail, et un pourcentage ne peut pas dépasser la totalité.',
    ],

    'PM-2053' => [
        'title' => 'L’avance doit être supérieure à zéro lorsque l’option indiquant qu’elle a déjà été reçue est sélectionnée.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-2054' => [
        'title' => 'Un agent est obligatoire lorsqu’une commission d’agent est configurée.',
        'what' => 'Une commission est saisie alors qu’aucun agent ne figure sur le bail. Une commission se verse à quelqu’un : Patrimoine doit savoir à qui.',
        'fix' => 'Indiquez l’agent sur le bail, ou remettez la commission à zéro.',
    ],

    'PM-2055' => [
        'title' => 'La partie sélectionnée doit avoir le rôle d’agent.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2056' => [
        'title' => 'Un immeuble doit être sélectionné lorsqu’une unité est sélectionnée.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2057' => [
        'title' => 'Les frais de gestion doivent être nuls lorsque le type de frais de gestion est aucun.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2058' => [
        'title' => 'Le pourcentage des frais de gestion ne peut pas dépasser 100 %.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-2059' => [
        'title' => 'La date de préavis de résiliation est obligatoire lorsque le bail est en préavis.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2060' => [
        'title' => 'Aucun paiement ne peut être enregistré sur un bail en brouillon.',
        'what' => 'Le bail est encore un brouillon. Un brouillon consigne l’accord mais n’a pas encore de vie financière : aucun mouvement d’argent n’y est possible.',
        'fix' => 'Activez le bail depuis la page Baux. L’activation génère les factures échues et ouvre ses comptes de fonds.',
    ],

    'PM-2061' => [
        'title' => 'La prochaine date d’augmentation de loyer est obligatoire lorsqu’une augmentation est configurée.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2062' => [
        'title' => 'La prochaine date d’augmentation de loyer doit être vide lorsqu’aucune augmentation n’est configurée.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2063' => [
        'title' => 'La valeur de l’augmentation de loyer doit être nulle lorsqu’aucune augmentation n’est configurée.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-2064' => [
        'title' => 'Le pourcentage d’augmentation du loyer ne peut pas dépasser 100 %.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-2065' => [
        'title' => 'Saisissez une valeur d’augmentation de loyer lorsqu’une augmentation est configurée.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2066' => [
        'title' => 'La réserve de loyer ne peut pas dépasser le montant total de l’avance.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-2067' => [
        'title' => 'La partie sélectionnée doit avoir le rôle de locataire.',
        'what' => 'La partie choisie comme locataire n’a pas le rôle Locataire ; Patrimoine ne la placera pas sur un bail.',
        'fix' => 'Ouvrez la fiche de la partie, cochez Locataire parmi ses rôles, puis réessayez. L’assistant de bail s’en charge pour vous.',
    ],

    'PM-2068' => [
        'title' => 'Cette unité possède déjà un bail actif.',
        'what' => 'Un lot ne peut porter qu’un seul bail en cours ; celui-ci a déjà un bail actif ou en préavis.',
        'fix' => 'Résiliez d’abord le bail existant, ou choisissez un lot vacant. Si le précédent locataire est déjà parti, finalisez la résiliation pour libérer le lot.',
    ],

    'PM-2069' => [
        'title' => 'L’unité sélectionnée n’appartient pas à l’immeuble sélectionné.',
        'what' => 'Le compte et la facture appartiennent à des baux différents. L’argent détenu pour une location ne peut pas régler la facture d’une autre.',
        'fix' => 'Choisissez un compte rattaché au même bail que la facture. Si l’argent doit réellement passer d’un bail à l’autre, utilisez un transfert, qui est enregistré comme tel.',
    ],

    'PM-2070' => [
        'title' => 'Ce bien n’a encore aucun propriétaire enregistré ; l’assistant en demande au moins un.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-2071' => [
        'title' => 'Le lot sélectionné n’appartient pas au bien sélectionné.',
        'what' => 'Le compte et la facture appartiennent à des baux différents. L’argent détenu pour une location ne peut pas régler la facture d’une autre.',
        'fix' => 'Choisissez un compte rattaché au même bail que la facture. Si l’argent doit réellement passer d’un bail à l’autre, utilisez un transfert, qui est enregistré comme tel.',
    ],

    'PM-2072' => [
        'title' => 'Choisissez le pays auquel ce numéro de téléphone appartient.',
        'what' => 'Un numéro de téléphone ne peut pas être composé sans savoir dans quel pays il se trouve. Les mêmes chiffres désignent des personnes différentes selon le pays.',
        'fix' => 'Ouvrez la liste des pays à côté du numéro et choisissez le pays. Vous pouvez saisir le nom du pays ou son indicatif pour le trouver rapidement.',
    ],

    'PM-2073' => [
        'title' => 'Ce numéro de téléphone semble incorrect.',
        'what' => 'Les chiffres et le pays choisi ne forment pas un numéro que l’on pourrait composer. Il manque généralement un ou deux chiffres, ou le pays n’est pas celui auquel le numéro appartient.',
        'fix' => 'Vérifiez les chiffres par rapport au numéro tel qu’il est écrit, ainsi que le pays indiqué à côté. N’indiquez pas le zéro initial : il ne sert qu’à l’intérieur du pays.',
    ],

    /* ---- 3xxx ---- */

    'PM-3001' => [
        'title' => 'Cet immeuble ne peut pas être supprimé tant qu’il contient des unités. Supprimez d’abord uniquement les unités sans référence ; les unités ayant un bail ou un historique financier doivent être conservées.',
        'what' => 'Le bien contient encore des lots ; le supprimer les emporterait avec lui.',
        'fix' => 'Supprimez d’abord les lots un par un, puis le bien. Un lot portant des baux ou un historique financier ne peut pas non plus être supprimé : c’est souvent la vraie raison du blocage.',
    ],

    'PM-3002' => [
        'title' => 'Cet immeuble ne peut pas être supprimé car des données financières ou historiques y font référence. Conservez-le pour préserver la traçabilité.',
        'what' => 'D’autres enregistrements dépendent de celui-ci. Le supprimer romprait l’historique qui explique d’où vient l’argent et où il est allé.',
        'fix' => 'Ouvrez l’enregistrement pour voir ce qui s’y rattache et traitez-le d’abord. Souvent, mieux vaut le conserver : l’historique reste lisible et rien n’est facturé pour des enregistrements inutilisés.',
    ],

    'PM-3003' => [
        'title' => 'Ce bail ne peut pas être supprimé en toute sécurité.',
        'what' => 'D’autres enregistrements dépendent de celui-ci. Le supprimer romprait l’historique qui explique d’où vient l’argent et où il est allé.',
        'fix' => 'Ouvrez l’enregistrement pour voir ce qui s’y rattache et traitez-le d’abord. Souvent, mieux vaut le conserver : l’historique reste lisible et rien n’est facturé pour des enregistrements inutilisés.',
    ],

    'PM-3004' => [
        'title' => 'Saisissez exactement DELETE pour confirmer la suppression du bail.',
        'what' => 'Le texte de confirmation ne correspond pas à ce qui est demandé. Cette contrainte est volontaire : c’est la dernière barrière entre un geste malheureux et une suppression définitive.',
        'fix' => 'Saisissez-le exactement comme indiqué, avec les mêmes majuscules et sans espace superflu.',
    ],

    'PM-3005' => [
        'title' => 'Seul un bail en brouillon et inutilisé peut être supprimé. Un bail actif, ou en préavis, prend fin par sa résiliation — et un bail résilié est conservé comme historique.',
        'what' => 'Le bail est encore un brouillon. Un brouillon consigne l’accord mais n’a pas encore de vie financière : aucun mouvement d’argent n’y est possible.',
        'fix' => 'Activez le bail depuis la page Baux. L’activation génère les factures échues et ouvre ses comptes de fonds.',
    ],

    'PM-3006' => [
        'title' => 'Ce bail brouillon ne peut pas être supprimé car des données contractuelles ou financières y font référence. Conservez le bail.',
        'what' => 'Le bail est encore un brouillon. Un brouillon consigne l’accord mais n’a pas encore de vie financière : aucun mouvement d’argent n’y est possible.',
        'fix' => 'Activez le bail depuis la page Baux. L’activation génère les factures échues et ouvre ses comptes de fonds.',
    ],

    'PM-3007' => [
        'title' => 'L’organisation gestionnaire configurée ne peut pas être supprimée. Modifiez plutôt la configuration de l’organisation gestionnaire.',
        'what' => 'Cette partie est votre propre société — celle dont le nom figure sur les factures, reçus et relevés. Patrimoine ne peut pas la supprimer tant qu’elle occupe cette fonction.',
        'fix' => 'Si les informations de votre société sont erronées, modifiez-les dans les Paramètres. Pour transférer la fonction à une autre partie, désignez d’abord celle-ci comme organisation gestionnaire.',
    ],

    'PM-3008' => [
        'title' => 'Cette partie ne peut pas être supprimée car elle est référencée par un bail, une propriété, un mandat d’agence ou un historique financier. Conservez-la afin que les données historiques restent compréhensibles.',
        'what' => 'Cette partie figure dans des baux, des propriétés, des mandats ou l’historique financier. La supprimer laisserait ces enregistrements sans référence : Patrimoine la conserve.',
        'fix' => 'Conservez la partie : c’est elle qui rend les anciens enregistrements lisibles. Si vous ne traitez plus avec elle, retirez plutôt ses rôles, ou laissez-la simplement ; une partie sans bail actif ne coûte rien.',
    ],

    'PM-3009' => [
        'title' => 'Cette unité ne peut pas être supprimée car un bail ou un historique financier y fait référence. Conservez l’unité et résiliez plutôt le bail lorsque cela s’applique.',
        'what' => 'D’autres enregistrements dépendent de celui-ci. Le supprimer romprait l’historique qui explique d’où vient l’argent et où il est allé.',
        'fix' => 'Ouvrez l’enregistrement pour voir ce qui s’y rattache et traitez-le d’abord. Souvent, mieux vaut le conserver : l’historique reste lisible et rien n’est facturé pour des enregistrements inutilisés.',
    ],

    'PM-3010' => [
        'title' => 'Impossible d\'enregistrer la retenue.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3011' => [
        'title' => 'Ce bail ne peut pas être supprimé en toute sécurité.',
        'what' => 'D’autres enregistrements dépendent de celui-ci. Le supprimer romprait l’historique qui explique d’où vient l’argent et où il est allé.',
        'fix' => 'Ouvrez l’enregistrement pour voir ce qui s’y rattache et traitez-le d’abord. Souvent, mieux vaut le conserver : l’historique reste lisible et rien n’est facturé pour des enregistrements inutilisés.',
    ],

    'PM-3012' => [
        'title' => 'Vous devez saisir exactement DELETE.',
        'what' => 'Le texte de confirmation ne correspond pas à ce qui est demandé. Cette contrainte est volontaire : c’est la dernière barrière entre un geste malheureux et une suppression définitive.',
        'fix' => 'Saisissez-le exactement comme indiqué, avec les mêmes majuscules et sans espace superflu.',
    ],

    'PM-3013' => [
        'title' => 'Impossible de calculer l’impact de la suppression du bail.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3014' => [
        'title' => 'Votre mot de passe actuel est obligatoire.',
        'what' => 'Patrimoine demande votre propre mot de passe avant une action irréversible, afin qu’un écran laissé sans surveillance ne serve pas à détruire des enregistrements.',
        'fix' => 'Saisissez le mot de passe avec lequel vous vous connectez. Si vous l’avez oublié, déconnectez-vous, utilisez « Mot de passe oublié », puis revenez.',
    ],

    'PM-3015' => [
        'title' => 'Un motif de suppression est obligatoire.',
        'what' => 'Annuler ou corriger une opération déjà enregistrée exige un motif : c’est lui qui rend l’écriture compréhensible pour qui relira les comptes plus tard.',
        'fix' => 'Indiquez brièvement la raison — « payé deux fois par le locataire », « mauvais compte choisi » — puis enregistrez de nouveau.',
    ],

    'PM-3016' => [
        'title' => 'Impossible de charger l’historique financier.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3017' => [
        'title' => 'Impossible d\'ouvrir le document.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-3018' => [
        'title' => 'Impossible d\'annuler l\'augmentation du loyer.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3019' => [
        'title' => 'Impossible de programmer l\'augmentation du loyer.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3020' => [
        'title' => 'Impossible de charger les augmentations du loyer.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3021' => [
        'title' => 'La réserve de loyer ne peut pas dépasser le paiement anticipé total.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3022' => [
        'title' => 'Impossible d’annuler la résiliation.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3023' => [
        'title' => 'Impossible de terminer la résiliation.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3024' => [
        'title' => 'Impossible d’initier la résiliation du bail.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3025' => [
        'title' => 'Impossible d’ouvrir l’avis de résiliation.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-3026' => [
        'title' => 'La date de préavis, la date de résiliation et le traitement du dernier loyer sont obligatoires.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3027' => [
        'title' => 'Impossible de charger le règlement de résiliation.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3028' => [
        'title' => 'Éléments à résoudre',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-3029' => [
        'title' => 'Impossible d’ajouter la déduction du dépôt de garantie.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3030' => [
        'title' => 'Impossible d’appliquer l’avance consommable.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3031' => [
        'title' => 'Impossible d’appliquer la réserve de loyer.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3032' => [
        'title' => 'Impossible de créer le bail.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3033' => [
        'title' => 'Impossible de supprimer le bail.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3034' => [
        'title' => 'Impossible de finaliser le dépôt de garantie.',
        'what' => 'L’étape ne s’est pas terminée : elle n’a donc rien modifié.',
        'fix' => 'Rechargez la page et regardez l’enregistrement avant de recommencer : vous verrez si une partie a malgré tout abouti. En cas de second échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3035' => [
        'title' => 'Impossible d’initialiser les baux.',
        'what' => 'L’étape ne s’est pas terminée : elle n’a donc rien modifié.',
        'fix' => 'Rechargez la page et regardez l’enregistrement avant de recommencer : vous verrez si une partie a malgré tout abouti. En cas de second échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3036' => [
        'title' => 'Impossible de charger les baux.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3037' => [
        'title' => 'Impossible de charger le bail.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3038' => [
        'title' => 'Impossible de charger le dépôt de garantie.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3039' => [
        'title' => 'Impossible de charger les fonds du locataire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3040' => [
        'title' => 'Impossible d’ouvrir le bon du dépôt de garantie.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-3041' => [
        'title' => 'Impossible de mettre à jour le bail.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3042' => [
        'title' => 'Le bon n’a pas pu être ouvert car le navigateur a bloqué le nouvel onglet.',
        'what' => 'Le document s’ouvre dans un nouvel onglet, et ce navigateur l’a bloqué. Les navigateurs bloquent les onglets qu’une page ouvre d’elle-même, sauf autorisation.',
        'fix' => 'Cherchez l’avertissement de fenêtre bloquée dans la barre d’adresse et autorisez-la pour Patrimoine. Le document a bien été créé et reste téléchargeable depuis l’enregistrement concerné.',
    ],

    'PM-3043' => [
        'title' => 'Seule une partie sans référence peut être supprimée. Les parties utilisées par des baux, des propriétés, des mandats d’agence ou un historique financier doivent être conservées.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-3044' => [
        'title' => 'Impossible de créer la partie.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3045' => [
        'title' => 'Impossible de supprimer la partie.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3046' => [
        'title' => 'Impossible de charger les parties.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3047' => [
        'title' => 'Impossible de charger la partie.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3048' => [
        'title' => 'Impossible de mettre à jour la partie.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3049' => [
        'title' => 'La raison sociale et les coordonnées de la personne de contact sont obligatoires.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3050' => [
        'title' => 'Le nom, le téléphone et l’e-mail sont obligatoires pour une personne.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3051' => [
        'title' => 'Impossible d’ajouter l’unité.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3052' => [
        'title' => 'Impossible de créer le propriétaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3053' => [
        'title' => 'Impossible de créer la propriété.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3054' => [
        'title' => 'Impossible de supprimer la propriété.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3055' => [
        'title' => 'Impossible de supprimer l\'unité.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3056' => [
        'title' => 'Impossible de charger les propriétés.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3057' => [
        'title' => 'Impossible de charger la propriété.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3058' => [
        'title' => 'Impossible de trouver cette unité.',
        'what' => 'L’étape ne s’est pas terminée : elle n’a donc rien modifié.',
        'fix' => 'Rechargez la page et regardez l’enregistrement avant de recommencer : vous verrez si une partie a malgré tout abouti. En cas de second échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3059' => [
        'title' => 'Impossible de modifier la propriété.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3060' => [
        'title' => 'Impossible de modifier l’unité.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-3061' => [
        'title' => 'Le même propriétaire ne peut pas être ajouté plusieurs fois.',
        'what' => 'La même partie figure deux fois parmi les propriétaires de ce bien.',
        'fix' => 'Supprimez le doublon et attribuez la part totale à l’entrée unique. Deux personnes portant le même nom sont deux parties : vérifiez que vous n’avez pas choisi deux fois la même.',
    ],

    'PM-3062' => [
        'title' => 'Chaque unité doit avoir un nom ou un numéro.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-3063' => [
        'title' => 'Une propriété doit avoir au moins un propriétaire.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-3064' => [
        'title' => 'La répartition de la propriété doit totaliser exactement 100 %.',
        'what' => 'Un bien est détenu exactement une fois : les parts doivent totaliser 100 %.',
        'fix' => 'Ajustez les pourcentages pour atteindre exactement 100 — un propriétaire prend 100, deux propriétaires à parts égales prennent 50 et 50.',
    ],

    'PM-3065' => [
        'title' => 'Les noms des unités doivent être uniques au sein de la propriété.',
        'what' => 'Un autre enregistrement possède déjà cette valeur, et Patrimoine la garde unique pour éviter toute confusion ultérieure.',
        'fix' => 'Recherchez d’abord l’enregistrement existant : c’est généralement celui que vous vouliez. Si les deux existent réellement, distinguez-les, par exemple par un second prénom ou un numéro de lot.',
    ],

    'PM-3066' => [
        'title' => 'Le nom ou le numéro de l’unité est obligatoire.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3067' => [
        'title' => 'Une propriété doit avoir au moins une unité.',
        'what' => 'Le formulaire attend un nombre précis, ou une forme précise, et la saisie n’y correspond pas.',
        'fix' => 'Le message indique la contrainte. Ajoutez, retirez ou corrigez les éléments jusqu’à la respecter : le champ acceptera la saisie dès ce moment.',
    ],

    'PM-3068' => [
        'title' => 'Une propriété valide doit être sélectionnée.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-3069' => [
        'title' => 'Les soldes de fonds locataire ne peuvent pas être ajustés en dessous de zéro.',
        'what' => 'Un ajustement ne peut pas rendre un fonds locataire négatif : cela reviendrait à détenir de l’argent jamais encaissé.',
        'fix' => 'Ajustez à zéro ou au-dessus. Si de l’argent est réellement sorti du compte, enregistrez plutôt un retrait ou une dépense : l’un et l’autre laissent une trace de sa destination.',
    ],

    'PM-3070' => [
        'title' => 'Le solde corrigé est déjà identique au solde actuel.',
        'what' => 'Le solde corrigé que vous avez saisi est déjà le solde enregistré : il n’y a rien à ajuster.',
        'fix' => 'Vérifiez le montant. Si le solde est réellement erroné, saisissez ce qu’il devrait être plutôt que ce qu’il est.',
    ],

    'PM-3071' => [
        'title' => 'Le total dépasse le solde disponible du fonds.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3072' => [
        'title' => 'La partie sélectionnée n\'est pas un locataire.',
        'what' => 'L’enregistrement choisi n’est pas du type sur lequel cette action s’applique.',
        'fix' => 'Revenez en arrière et choisissez dans la liste proposée pour cette action plutôt que de chercher l’enregistrement vous-même : cette liste ne contient que ce que l’action accepte.',
    ],

    'PM-3073' => [
        'title' => 'Le paiement dépasse le solde disponible du compte.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3074' => [
        'title' => 'Le compte, le montant et la date sont obligatoires.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3075' => [
        'title' => 'Le montant ne peut pas dépasser le solde disponible du dépôt de garantie.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3076' => [
        'title' => 'Le montant ne peut pas dépasser le solde impayé de la créance sélectionnée.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3077' => [
        'title' => 'Le montant ne peut pas dépasser le solde du compte source.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3078' => [
        'title' => 'Renseignez tous les champs obligatoires du transfert, y compris le motif.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-3079' => [
        'title' => 'Les comptes source et destination doivent être différents.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-3080' => [
        'title' => 'Impossible de charger les locataires.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3081' => [
        'title' => 'Impossible de charger les détails du locataire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3082' => [
        'title' => 'Impossible de charger ce locataire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3083' => [
        'title' => 'Impossible d\'ouvrir la facture.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-3084' => [
        'title' => 'Impossible d\'ouvrir le récépissé de transfert.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-3085' => [
        'title' => 'Impossible de renvoyer la facture.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3086' => [
        'title' => 'Impossible de renvoyer le reçu.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3087' => [
        'title' => 'Impossible de renvoyer le récépissé de transfert.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-3088' => [
        'title' => 'Le retrait ne peut pas dépasser le solde disponible.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-3089' => [
        'title' => 'Impossible de charger vos biens et vos parties.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-3090' => [
        'title' => 'Le bail n’a pas pu être créé. Rien n’a été enregistré.',
        'what' => 'Une requête n’a pas abouti. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si le problème persiste, dites-nous ce que vous faisiez.',
    ],

    'PM-3091' => [
        'title' => 'Cet assistant n’existe plus.',
        'what' => 'L’assistant inachevé que vous repreniez a été terminé par quelqu’un ou supprimé depuis. Il n’en existait qu’un exemplaire.',
        'fix' => 'Revenez aux Baux. Si la location a été finalisée, elle figure dans la liste ; si l’assistant a été supprimé, recommencez-en un.',
    ],

    'PM-3092' => [
        'title' => 'Celui-ci n’a pas pu être supprimé.',
        'what' => 'La demande de suppression de l’assistant inachevé n’a pas abouti. Rien n’a été modifié.',
        'fix' => 'Réessayez. Si cela persiste, l’assistant ne gêne en rien là où il est : signalez-le-nous et poursuivez.',
    ],

    'PM-3093' => [
        'title' => 'Ce compte de fonds est clos.',
        'what' => 'Aucun retrait n’est possible depuis un compte clos. Il reste visible parce que les sommes qui y ont transité font partie du registre.',
        'fix' => 'Ouvrez le locataire et vérifiez lequel de ses comptes présente encore un solde. Si le compte a été clos par erreur, un administrateur peut nous le signaler et nous l’examinerons avec vous.',
    ],

    'PM-3094' => [
        'title' => 'Ce fonds ne peut pas faire l’objet d’un retrait direct.',
        'what' => 'Un dépôt de garantie ne se rembourse pas depuis l’écran de retrait. Il se libère au règlement du bail, afin que les retenues et le remboursement soient calculés ensemble et inscrits en comptabilité comme un seul règlement.',
        'fix' => 'Résiliez le bail et utilisez le règlement, qui produit le remboursement et son bon. La réserve de loyer et l’avance consommable se retirent ici normalement.',
    ],

    'PM-3095' => [
        'title' => 'Le montant du retrait doit être supérieur à zéro.',
        'what' => 'Un retrait nul créerait une écriture indiquant qu’il ne s’est rien passé ; Patrimoine ne l’enregistre donc pas.',
        'fix' => 'Saisissez le montant réellement versé. Pour corriger un retrait antérieur, utilisez un ajustement plutôt qu’un zéro.',
    ],

    /* ---- 4xxx ---- */

    'PM-4001' => [
        'title' => 'Le compte d’avance consommable est fermé.',
        'what' => 'Le compte de fonds est clôturé ; un compte clôturé ne reçoit ni ne verse plus rien.',
        'fix' => 'Utilisez un compte ouvert pour cette opération. Les comptes se clôturent à la fin d’un bail : si le bail est toujours en cours, vérifiez que vous avez choisi le bon compte.',
    ],

    'PM-4002' => [
        'title' => 'Le montant de l’avance consommable doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4003' => [
        'title' => 'L’avance consommable ne peut pas être utilisée pour un bail en brouillon.',
        'what' => 'Le bail est encore un brouillon. Un brouillon consigne l’accord mais n’a pas encore de vie financière : aucun mouvement d’argent n’y est possible.',
        'fix' => 'Activez le bail depuis la page Baux. L’activation génère les factures échues et ouvre ses comptes de fonds.',
    ],

    'PM-4004' => [
        'title' => 'L’avance consommable dépasse le solde restant dû de la facture.',
        'what' => 'Le montant dépasse ce qui reste dû. Payer plus que le solde restant rendrait la facture excédentaire.',
        'fix' => 'Saisissez le montant restant dû ou moins. Si le locataire a réellement versé davantage, enregistrez l’excédent séparément — en dépôt sur un compte de fonds, pas sur cette facture.',
    ],

    'PM-4005' => [
        'title' => 'Le solde de l’avance consommable est insuffisant.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4006' => [
        'title' => 'L’avance consommable ne peut régler que des factures de loyer.',
        'what' => 'Chaque type de compte de fonds a une destination, et celui-ci n’est pas autorisé à régler ce type de facture.',
        'fix' => 'Utilisez le compte prévu : une réserve de loyer ou une avance consommable règle le loyer ; un dépôt de garantie se solde en fin de location, non contre des factures.',
    ],

    'PM-4007' => [
        'title' => 'Seul un compte d’avance consommable peut être utilisé par ce service.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4008' => [
        'title' => 'La facture n’appartient pas au bail associé à cette avance consommable.',
        'what' => 'Le compte et la facture appartiennent à des baux différents. L’argent détenu pour une location ne peut pas régler la facture d’une autre.',
        'fix' => 'Choisissez un compte rattaché au même bail que la facture. Si l’argent doit réellement passer d’un bail à l’autre, utilisez un transfert, qui est enregistré comme tel.',
    ],

    'PM-4009' => [
        'title' => 'Impossible d’exporter le journal financier.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-4010' => [
        'title' => 'Impossible de charger le journal financier.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4011' => [
        'title' => 'Impossible de charger les détails de l’écriture.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4012' => [
        'title' => 'Le compte de fonds du locataire sélectionné n’est pas un compte d’avance consommable.',
        'what' => 'Le compte choisi n’est pas une avance consommable, et cette opération ne s’applique qu’à celle-ci.',
        'fix' => 'Sélectionnez l’avance consommable du bail dans la liste. Chaque bail dispose de trois comptes de fonds, chacun ayant un rôle distinct.',
    ],

    'PM-4013' => [
        'title' => 'Le compte de fonds du locataire sélectionné n’est pas un compte de réserve de loyer.',
        'what' => 'Le compte choisi n’est pas une réserve de loyer, et cette opération ne s’applique qu’à celle-ci.',
        'fix' => 'Sélectionnez la réserve de loyer du bail dans la liste. Chaque bail en a trois — réserve de loyer, avance consommable et dépôt de garantie — et chacune a un rôle distinct.',
    ],

    'PM-4014' => [
        'title' => 'Ce compte de fonds ne peut pas payer les factures de loyer.',
        'what' => 'Chaque type de compte de fonds a une destination, et celui-ci n’est pas autorisé à régler ce type de facture.',
        'fix' => 'Utilisez le compte prévu : une réserve de loyer ou une avance consommable règle le loyer ; un dépôt de garantie se solde en fin de location, non contre des factures.',
    ],

    'PM-4015' => [
        'title' => 'Le compte de fonds n’est pas actif.',
        'what' => 'Le compte de fonds est clôturé ; un compte clôturé ne reçoit ni ne verse plus rien.',
        'fix' => 'Utilisez un compte ouvert pour cette opération. Les comptes se clôturent à la fin d’un bail : si le bail est toujours en cours, vérifiez que vous avez choisi le bon compte.',
    ],

    'PM-4016' => [
        'title' => 'Ce paiement a déjà été annulé.',
        'what' => 'Cette écriture a déjà été annulée. Patrimoine ne l’annule pas deux fois, ce qui reverserait l’argent une seconde fois.',
        'fix' => 'Ouvrez l’enregistrement pour voir l’annulation et son motif. Si une correction supplémentaire s’impose, passez une nouvelle écriture plutôt qu’une seconde annulation.',
    ],

    'PM-4017' => [
        'title' => 'Le montant du paiement doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4018' => [
        'title' => 'Le paiement dépasse le montant restant dû de la facture.',
        'what' => 'Le montant dépasse ce qui reste dû. Payer plus que le solde restant rendrait la facture excédentaire.',
        'fix' => 'Saisissez le montant restant dû ou moins. Si le locataire a réellement versé davantage, enregistrez l’excédent séparément — en dépôt sur un compte de fonds, pas sur cette facture.',
    ],

    'PM-4019' => [
        'title' => 'Les consommations historiques ne peuvent pas être annulées.',
        'what' => 'Cette écriture fait partie des montants de départ enregistrés à l’ouverture de vos comptes. Ils ne s’annulent pas : il n’existe pas d’état antérieur où revenir.',
        'fix' => 'Passez plutôt une écriture de correction datée d’aujourd’hui. L’historique reste lisible et la correction se voit pour ce qu’elle est.',
    ],

    'PM-4020' => [
        'title' => 'Le paiement dépasse le solde disponible du compte de fonds.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4021' => [
        'title' => 'Cette transaction n’est pas un paiement de facture depuis un compte.',
        'what' => 'L’enregistrement choisi n’est pas du type sur lequel cette action s’applique.',
        'fix' => 'Revenez en arrière et choisissez dans la liste proposée pour cette action plutôt que de chercher l’enregistrement vous-même : cette liste ne contient que ce que l’action accepte.',
    ],

    'PM-4022' => [
        'title' => 'Cette facture n’a encore aucun paiement par compte à recevoir en reçu.',
        'what' => 'Un reçu atteste d’un encaissement, et rien n’a encore été payé sur cette facture depuis un compte de fonds.',
        'fix' => 'Enregistrez d’abord le paiement : le reçu devient alors disponible. Si le locataire a payé en espèces ou par virement plutôt que depuis un fonds, enregistrez-le comme paiement ordinaire.',
    ],

    'PM-4023' => [
        'title' => 'Cette facture ne peut pas être payée depuis un compte de fonds.',
        'what' => 'L’option transmise ne fait pas partie de celles que Patrimoine reconnaît pour ce type d’enregistrement.',
        'fix' => 'Choisissez une des options proposées dans la liste plutôt que de saisir une valeur. Si vous arrivez d’un lien enregistré ou d’un onglet ancien, rechargez la page et réessayez.',
    ],

    'PM-4024' => [
        'title' => 'Le compte de fonds n’appartient pas au bail de la facture.',
        'what' => 'Le compte et la facture appartiennent à des baux différents. L’argent détenu pour une location ne peut pas régler la facture d’une autre.',
        'fix' => 'Choisissez un compte rattaché au même bail que la facture. Si l’argent doit réellement passer d’un bail à l’autre, utilisez un transfert, qui est enregistré comme tel.',
    ],

    'PM-4025' => [
        'title' => 'Le sens de l’ajustement du propriétaire doit être crédit ou débit.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-4026' => [
        'title' => 'Le montant de l’ajustement du propriétaire doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4027' => [
        'title' => 'Le motif de l’ajustement du propriétaire est obligatoire.',
        'what' => 'Annuler ou corriger une opération déjà enregistrée exige un motif : c’est lui qui rend l’écriture compréhensible pour qui relira les comptes plus tard.',
        'fix' => 'Indiquez brièvement la raison — « payé deux fois par le locataire », « mauvais compte choisi » — puis enregistrez de nouveau.',
    ],

    'PM-4028' => [
        'title' => 'Ce paiement de facture de dépenses a déjà été annulé.',
        'what' => 'Cette écriture a déjà été annulée. Patrimoine ne l’annule pas deux fois, ce qui reverserait l’argent une seconde fois.',
        'fix' => 'Ouvrez l’enregistrement pour voir l’annulation et son motif. Si une correction supplémentaire s’impose, passez une nouvelle écriture plutôt qu’une seconde annulation.',
    ],

    'PM-4029' => [
        'title' => 'Le paiement dépasse le montant restant dû de la facture de dépenses.',
        'what' => 'Le montant dépasse ce qui reste dû. Payer plus que le solde restant rendrait la facture excédentaire.',
        'fix' => 'Saisissez le montant restant dû ou moins. Si le locataire a réellement versé davantage, enregistrez l’excédent séparément — en dépôt sur un compte de fonds, pas sur cette facture.',
    ],

    'PM-4030' => [
        'title' => 'Le paiement dépasse le solde disponible du compte Retraits.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4031' => [
        'title' => 'Le montant du paiement de la facture de dépenses doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4032' => [
        'title' => 'Un motif d\'annulation est obligatoire.',
        'what' => 'Annuler ou corriger une opération déjà enregistrée exige un motif : c’est lui qui rend l’écriture compréhensible pour qui relira les comptes plus tard.',
        'fix' => 'Indiquez brièvement la raison — « payé deux fois par le locataire », « mauvais compte choisi » — puis enregistrez de nouveau.',
    ],

    'PM-4033' => [
        'title' => 'Cette facture de dépenses n’a encore aucun paiement à recevoir en reçu.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4034' => [
        'title' => 'Ce compte ne peut pas régler une facture de dépenses.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4035' => [
        'title' => 'Le réceptionnaire des espèces n’a pas pu être déterminé pour ce dépôt de propriétaire.',
        'what' => 'Des espèces sont forcément reçues par quelqu’un, et Patrimoine enregistre qui. Il n’a pas pu le déterminer pour cette écriture.',
        'fix' => 'Assurez-vous d’être connecté avec le compte de la personne qui a encaissé : Patrimoine désigne l’utilisateur connecté comme caissier. Si quelqu’un d’autre a reçu l’argent, laissez-le l’enregistrer.',
    ],

    'PM-4036' => [
        'title' => 'Un dépôt de propriétaire ne peut pas être enregistré avec ce mode de paiement.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4037' => [
        'title' => 'Le montant du dépôt du propriétaire doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4038' => [
        'title' => 'Un dépôt de propriétaire ne peut pas être enregistré pour cet objet.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4039' => [
        'title' => 'Seuls les dépôts de propriétaire peuvent générer un reçu de dépôt de propriétaire.',
        'what' => 'L’enregistrement choisi n’est pas du type sur lequel cette action s’applique.',
        'fix' => 'Revenez en arrière et choisissez dans la liste proposée pour cette action plutôt que de chercher l’enregistrement vous-même : cette liste ne contient que ce que l’action accepte.',
    ],

    'PM-4040' => [
        'title' => 'Les règlements historiques de factures de dépenses ne peuvent pas être annulés.',
        'what' => 'Cette écriture fait partie des montants de départ enregistrés à l’ouverture de vos comptes. Ils ne s’annulent pas : il n’existe pas d’état antérieur où revenir.',
        'fix' => 'Passez plutôt une écriture de correction datée d’aujourd’hui. L’historique reste lisible et la correction se voit pour ce qu’elle est.',
    ],

    'PM-4041' => [
        'title' => 'Cette transaction n’est pas un paiement de facture de dépenses.',
        'what' => 'L’enregistrement choisi n’est pas du type sur lequel cette action s’applique.',
        'fix' => 'Revenez en arrière et choisissez dans la liste proposée pour cette action plutôt que de chercher l’enregistrement vous-même : cette liste ne contient que ce que l’action accepte.',
    ],

    'PM-4042' => [
        'title' => 'Le paiement au propriétaire ne peut pas être entièrement affecté aux crédits nets disponibles.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4043' => [
        'title' => 'Le paiement au propriétaire dépasse le solde disponible.',
        'what' => 'Le montant demandé dépasse ce dont dispose actuellement le propriétaire, après honoraires, TVA et dépenses.',
        'fix' => 'Vérifiez le solde du compte propriétaire et versez ce montant ou moins. Si le solde vous semble erroné, consultez le relevé pour voir les écritures qui le composent.',
    ],

    'PM-4044' => [
        'title' => 'Aucun fonds n’est disponible pour un paiement à ce propriétaire.',
        'what' => 'Il n’y a rien à reverser. Un propriétaire ne peut recevoir que l’argent réellement encaissé auprès des locataires ; un loyer encore dû ne lui appartient pas.',
        'fix' => 'Enregistrez d’abord les paiements des locataires. Le solde du propriétaire augmente à mesure des encaissements, et le reversement devient possible.',
    ],

    'PM-4045' => [
        'title' => 'Le montant du paiement au propriétaire doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4046' => [
        'title' => 'Le transfert dépasse le solde disponible du compte source.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4047' => [
        'title' => 'Le montant du transfert entre comptes doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4048' => [
        'title' => 'Le motif du transfert entre comptes est obligatoire.',
        'what' => 'Annuler ou corriger une opération déjà enregistrée exige un motif : c’est lui qui rend l’écriture compréhensible pour qui relira les comptes plus tard.',
        'fix' => 'Indiquez brièvement la raison — « payé deux fois par le locataire », « mauvais compte choisi » — puis enregistrez de nouveau.',
    ],

    'PM-4049' => [
        'title' => 'Un motif d’audit est obligatoire pour chaque ajustement manuel.',
        'what' => 'Annuler ou corriger une opération déjà enregistrée exige un motif : c’est lui qui rend l’écriture compréhensible pour qui relira les comptes plus tard.',
        'fix' => 'Indiquez brièvement la raison — « payé deux fois par le locataire », « mauvais compte choisi » — puis enregistrez de nouveau.',
    ],

    'PM-4050' => [
        'title' => 'Le caissier n’a pas pu être déterminé pour ce dépôt.',
        'what' => 'Des espèces sont forcément reçues par quelqu’un, et Patrimoine enregistre qui. Il n’a pas pu le déterminer pour cette écriture.',
        'fix' => 'Assurez-vous d’être connecté avec le compte de la personne qui a encaissé : Patrimoine désigne l’utilisateur connecté comme caissier. Si quelqu’un d’autre a reçu l’argent, laissez-le l’enregistrer.',
    ],

    'PM-4051' => [
        'title' => 'Impossible d\'envoyer la facture par e-mail.',
        'what' => 'Le message n’a pas pu être remis au service d’envoi. Le document, lui, a bien été créé et reste disponible.',
        'fix' => 'Vérifiez que la partie a une adresse e-mail valide, puis renvoyez. Si l’adresse est correcte et que l’échec persiste, téléchargez le document et envoyez-le vous-même le temps que nous examinions la cause.',
    ],

    'PM-4052' => [
        'title' => 'La description de la dépense est obligatoire.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-4053' => [
        'title' => 'Le paiement dépasse le montant restant dû de la facture.',
        'what' => 'Le montant dépasse ce qui reste dû. Payer plus que le solde restant rendrait la facture excédentaire.',
        'fix' => 'Saisissez le montant restant dû ou moins. Si le locataire a réellement versé davantage, enregistrez l’excédent séparément — en dépôt sur un compte de fonds, pas sur cette facture.',
    ],

    'PM-4054' => [
        'title' => 'Le paiement dépasse le solde disponible du compte Retraits.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4055' => [
        'title' => 'Le compte source, le montant et la date sont obligatoires.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-4056' => [
        'title' => 'Le retrait ne peut pas dépasser le solde disponible du compte de retrait de {balance}.',
        'what' => 'Le montant saisi dépasse le disponible, et Patrimoine n’enregistre pas d’argent qui n’existe pas.',
        'fix' => 'Vérifiez le solde affiché à côté du champ et saisissez ce montant ou moins. Si le solde semble erroné, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4057' => [
        'title' => 'Impossible d\'annuler le paiement de la facture.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-4058' => [
        'title' => 'Impossible de créer le versement au propriétaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4059' => [
        'title' => 'Impossible de charger les propriétaires.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4060' => [
        'title' => 'Impossible de charger ce propriétaire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4061' => [
        'title' => 'Le relevé n\'a pas pu être généré.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-4062' => [
        'title' => 'Impossible d\'ouvrir le bon de transfert.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-4063' => [
        'title' => 'Impossible d\'enregistrer le paiement de la facture.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4064' => [
        'title' => 'Impossible d’enregistrer l’ajustement du propriétaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4065' => [
        'title' => 'Impossible d\'enregistrer la facture de dépenses.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4066' => [
        'title' => 'Impossible d’enregistrer le dépôt du propriétaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4067' => [
        'title' => 'Impossible d’enregistrer la dépense du propriétaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4068' => [
        'title' => 'Impossible de renvoyer la facture de dépenses.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-4069' => [
        'title' => 'Impossible de renvoyer le bon de transfert.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été modifié.',
        'fix' => 'Rechargez la page et vérifiez si l’opération a malgré tout abouti avant de recommencer. En cas de nouvel échec, dites-nous ce que vous faisiez.',
    ],

    'PM-4070' => [
        'title' => 'Impossible d\'enregistrer le transfert entre comptes.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4071' => [
        'title' => 'Le dépôt du propriétaire a été enregistré mais son reçu n’a pas pu être identifié.',
        'what' => 'Le dépôt a bien été enregistré — l’argent est sur le compte — mais Patrimoine n’a pas pu produire son reçu à cet instant.',
        'fix' => 'Ouvrez le compte du propriétaire et imprimez le reçu depuis la ligne du dépôt. Si l’ouverture échoue encore, dites-le-nous : l’argent est en sécurité dans tous les cas, et la correction nous revient.',
    ],

    'PM-4072' => [
        'title' => 'Le paiement a été enregistré mais son reçu n’a pas pu être identifié.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4073' => [
        'title' => 'Impossible de classer les fonds du locataire.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4074' => [
        'title' => 'Impossible de charger les paiements.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4075' => [
        'title' => 'Impossible de charger les fonds du paiement.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4076' => [
        'title' => 'Impossible de charger les baux',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4077' => [
        'title' => 'Impossible de charger les détails du propriétaire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4078' => [
        'title' => 'Impossible de charger les baux du locataire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4079' => [
        'title' => 'Impossible d\'ouvrir le reçu.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-4080' => [
        'title' => 'Impossible d’enregistrer le paiement.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],

    'PM-4081' => [
        'title' => 'Impossible de rechercher les propriétaires.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4082' => [
        'title' => 'Impossible de rechercher les locataires.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-4083' => [
        'title' => 'Le caissier n’a pas pu être déterminé pour ce paiement.',
        'what' => 'Des espèces sont forcément reçues par quelqu’un, et Patrimoine enregistre qui. Il n’a pas pu le déterminer pour cette écriture.',
        'fix' => 'Assurez-vous d’être connecté avec le compte de la personne qui a encaissé : Patrimoine désigne l’utilisateur connecté comme caissier. Si quelqu’un d’autre a reçu l’argent, laissez-le l’enregistrer.',
    ],

    'PM-4084' => [
        'title' => 'La date du paiement est obligatoire.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-4085' => [
        'title' => 'Le compte de réserve de loyer est fermé.',
        'what' => 'Le compte de fonds est clôturé ; un compte clôturé ne reçoit ni ne verse plus rien.',
        'fix' => 'Utilisez un compte ouvert pour cette opération. Les comptes se clôturent à la fin d’un bail : si le bail est toujours en cours, vérifiez que vous avez choisi le bon compte.',
    ],

    'PM-4086' => [
        'title' => 'Le montant utilisé depuis la réserve de loyer doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    'PM-4087' => [
        'title' => 'La réserve de loyer ne peut pas être utilisée avant le préavis de résiliation.',
        'what' => 'Une réserve de loyer est conservée pour la fin de la location : Patrimoine ne l’utilise pas pour régler un loyer tant que le bail suit son cours normal.',
        'fix' => 'Donnez d’abord congé sur le bail. Une fois la location en préavis, la réserve peut servir à régler le loyer restant.',
    ],

    'PM-4088' => [
        'title' => 'Le montant utilisé depuis la réserve de loyer dépasse le solde restant dû de la facture.',
        'what' => 'Le montant dépasse ce qui reste dû. Payer plus que le solde restant rendrait la facture excédentaire.',
        'fix' => 'Saisissez le montant restant dû ou moins. Si le locataire a réellement versé davantage, enregistrez l’excédent séparément — en dépôt sur un compte de fonds, pas sur cette facture.',
    ],

    'PM-4089' => [
        'title' => 'Le solde de la réserve de loyer est insuffisant.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4090' => [
        'title' => 'Aucune répartition de propriété n’est définie pour l’immeuble.',
        'what' => 'Le bien n’a aucun propriétaire enregistré : Patrimoine ne peut pas déterminer à qui appartient cet argent.',
        'fix' => 'Ouvrez le bien et ajoutez ses propriétaires avec leurs parts, pour un total de 100 %. Un loyer encaissé avant cela n’a nulle part où aller.',
    ],

    'PM-4091' => [
        'title' => 'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',
        'what' => 'Un bien est détenu exactement une fois. Les parts saisies totalisent plus ou moins de 100 %.',
        'fix' => 'Ajustez les pourcentages jusqu’à obtenir exactement 100. Un propriétaire unique prend 100 ; deux propriétaires à parts égales prennent 50 et 50.',
    ],

    'PM-4092' => [
        'title' => 'La réserve de loyer ne peut régler que des factures de loyer.',
        'what' => 'Chaque type de compte de fonds a une destination, et celui-ci n’est pas autorisé à régler ce type de facture.',
        'fix' => 'Utilisez le compte prévu : une réserve de loyer ou une avance consommable règle le loyer ; un dépôt de garantie se solde en fin de location, non contre des factures.',
    ],

    'PM-4093' => [
        'title' => 'Seul un compte de réserve de loyer peut être utilisé par ce service.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4094' => [
        'title' => 'La facture n’appartient pas au bail associé à cette réserve de loyer.',
        'what' => 'Le compte et la facture appartiennent à des baux différents. L’argent détenu pour une location ne peut pas régler la facture d’une autre.',
        'fix' => 'Choisissez un compte rattaché au même bail que la facture. Si l’argent doit réellement passer d’un bail à l’autre, utilisez un transfert, qui est enregistré comme tel.',
    ],

    'PM-4095' => [
        'title' => 'Aucun compte de dépôt de garantie n’existe pour ce bail.',
        'what' => 'Ce bail n’a pas de compte de dépôt de garantie : il n’y a donc aucun dépôt à traiter. Soit aucun n’a été convenu, soit le bail a été créé avant l’enregistrement du dépôt.',
        'fix' => 'Si un dépôt a été perçu, enregistrez-le d’abord sur le bail. Si aucun ne l’a été, il n’y a rien à solder : finalisez la résiliation sans étape de dépôt.',
    ],

    'PM-4096' => [
        'title' => 'Le dépôt de garantie a déjà fait l’objet d’un règlement pour ce bail.',
        'what' => 'Ce dépôt a déjà été soldé, et un décompte est définitif : les montants ne bougent plus ensuite.',
        'fix' => 'Consultez le bon de décompte pour voir ce qui a été retenu et remboursé. Si le décompte est réellement erroné, passez une écriture de correction plutôt que de modifier l’historique.',
    ],

    'PM-4097' => [
        'title' => 'Les retenues sur le dépôt de garantie ne peuvent plus être modifiées après le règlement final.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-4098' => [
        'title' => 'Les retenues sur le dépôt de garantie ne peuvent être enregistrées que pour un bail résilié.',
        'what' => 'Les retenues sont prélevées sur le dépôt à la fin de la location : elles ne peuvent être saisies qu’une fois le bail résilié.',
        'fix' => 'Finalisez d’abord la résiliation. Les retenues se saisissent ensuite dans le décompte, où dépôt, retenues et remboursement sont calculés ensemble.',
    ],

    'PM-4099' => [
        'title' => 'Le compte de dépôt de garantie présente un solde négatif invalide.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-4100' => [
        'title' => 'Le compte de fonds du locataire n’est pas actif.',
        'what' => 'Le compte de fonds est clôturé ; un compte clôturé ne reçoit ni ne verse plus rien.',
        'fix' => 'Utilisez un compte ouvert pour cette opération. Les comptes se clôturent à la fin d’un bail : si le bail est toujours en cours, vérifiez que vous avez choisi le bon compte.',
    ],

    'PM-4101' => [
        'title' => 'La description de la dépense est obligatoire.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-4102' => [
        'title' => 'Aucune dépense ne peut être enregistrée pour un bail en brouillon.',
        'what' => 'Le bail est encore un brouillon. Un brouillon consigne l’accord mais n’a pas encore de vie financière : aucun mouvement d’argent n’y est possible.',
        'fix' => 'Activez le bail depuis la page Baux. L’activation génère les factures échues et ouvre ses comptes de fonds.',
    ],

    'PM-4103' => [
        'title' => 'La dépense dépasse le solde disponible du fonds.',
        'what' => 'Le montant demandé dépasse le solde du compte. Patrimoine n’autorise pas un solde négatif : cet argent n’existe pas.',
        'fix' => 'Vérifiez le solde affiché et saisissez ce montant ou moins. Si le solde est plus bas que prévu, ouvrez l’historique du compte pour voir ce qui en est déjà sorti.',
    ],

    'PM-4104' => [
        'title' => 'Une dépense locataire nécessite au moins une ligne.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-4105' => [
        'title' => 'Le montant de la dépense doit être supérieur à zéro.',
        'what' => 'Patrimoine n’enregistre pas d’opération nulle ou négative : elle ne déplacerait aucun argent tout en figurant dans les comptes.',
        'fix' => 'Saisissez un montant supérieur à zéro. Pour annuler une opération déjà enregistrée, annulez l’écriture d’origine plutôt que d’en saisir une négative.',
    ],

    /* ---- 5xxx ---- */

    'PM-5001' => [
        'title' => 'Le compte sélectionné n’est pas valide pour cette transaction.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-5002' => [
        'title' => 'L’opération n’a pas été enregistrée.',
        'what' => 'Une requête n’a pas abouti. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si le problème persiste, dites-nous ce que vous faisiez.',
    ],

    'PM-5003' => [
        'title' => 'Renseignez tous les champs obligatoires de la transaction.',
        'what' => 'Un élément nécessaire au formulaire est resté vide.',
        'fix' => 'Renseignez le champ indiqué dans le message et enregistrez de nouveau. Les champs marqués d’un astérisque rouge sont ceux dont Patrimoine ne peut pas se passer.',
    ],

    'PM-5004' => [
        'title' => 'Impossible d’exporter le journal d’activité.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-5005' => [
        'title' => 'Impossible de charger le journal d’activité.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-5006' => [
        'title' => 'Impossible de charger les détails de l’activité.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-5007' => [
        'title' => 'Impossible de charger les comptes financiers du locataire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-5008' => [
        'title' => 'Ce lien de document est invalide ou a expiré. Veuillez ouvrir le document à nouveau.',
        'what' => 'Les liens de document sont signés et de courte durée, afin qu’un lien copié ne serve pas plus tard à quelqu’un d’autre. Celui-ci a expiré ou a été modifié.',
        'fix' => 'Revenez dans Patrimoine et rouvrez le document depuis son emplacement — la facture, le reçu ou le rapport. Un nouveau lien est créé à chaque fois.',
    ],

    'PM-5009' => [
        'title' => 'Ce document ne peut pas être ouvert via un lien signé.',
        'what' => 'Ce document ne fait pas partie de ceux que Patrimoine ouvre par lien temporaire. Le lien utilisé désigne un élément qui ne peut pas être servi ainsi.',
        'fix' => 'Ouvrez le document depuis son emplacement dans Patrimoine — la facture, le reçu, le rapport — plutôt que depuis un lien enregistré ou partagé.',
    ],

    'PM-5010' => [
        'title' => 'La date de fin du rapport doit être égale ou postérieure à la date de début.',
        'what' => 'La valeur saisie n’a pas la forme attendue par ce champ.',
        'fix' => 'Lisez le message : il nomme le champ et la forme attendue. Corrigez ce champ, puis enregistrez de nouveau.',
    ],

    'PM-5011' => [
        'title' => 'Impossible de télécharger le rapport.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-5012' => [
        'title' => 'Impossible de générer le rapport.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-5013' => [
        'title' => 'Impossible de charger les filtres du rapport des paiements.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-5014' => [
        'title' => 'Impossible d\'ouvrir le rapport.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-5015' => [
        'title' => 'Impossible d\'ouvrir le relevé du locataire.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-5016' => [
        'title' => 'Impossible d’effectuer la recherche.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    /* ---- 6xxx ---- */

    'PM-6001' => [
        'title' => 'Le propriétaire ne possède pas d’adresse e-mail.',
        'what' => 'Patrimoine n’a pas d’adresse e-mail pour ce propriétaire : il n’y a nulle part où envoyer le document.',
        'fix' => 'Ouvrez le propriétaire dans Parties et ajoutez son adresse e-mail, puis renvoyez. S’il n’en a pas, téléchargez le document et remettez-le autrement.',
    ],

    'PM-6002' => [
        'title' => 'Les e-mails aux parties sont désactivés dans les paramètres de votre organisation ; rien n’a été envoyé.',
        'what' => 'Votre organisation a désactivé les e-mails aux parties dans les Paramètres : Patrimoine n’a rien envoyé. Le reste est intact — la facture, le reçu ou le bon existe et reste téléchargeable.',
        'fix' => 'Un administrateur peut réactiver les e-mails dans Paramètres, section Communications. Pour écrire à une seule partie sans rétablir le reste, réglez sa fiche sur « Toujours envoyer ».',
    ],

    'PM-6003' => [
        'title' => 'Cette partie est exclue des e-mails de Patrimoine ; rien n’a été envoyé.',
        'what' => 'Cette partie est réglée pour ne jamais recevoir d’e-mail de Patrimoine : rien n’a été envoyé. Le document lui-même est intact et reste téléchargeable ou imprimable.',
        'fix' => 'Ouvrez la fiche de la partie et choisissez « Suivre le paramètre de l’organisation » ou « Toujours envoyer à cette partie ».',
    ],

    'PM-6004' => [
        'title' => 'Le locataire ne possède pas d’adresse e-mail.',
        'what' => 'Patrimoine n’a pas d’adresse e-mail pour ce locataire : il n’y a nulle part où envoyer le document.',
        'fix' => 'Ouvrez le locataire dans Parties et ajoutez son adresse e-mail, puis renvoyez. S’il n’en a pas, téléchargez le document et remettez-le autrement.',
    ],

    'PM-6005' => [
        'title' => 'Impossible de charger les notifications.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    /* ---- 7xxx ---- */

    'PM-7001' => [
        'title' => 'Le quota mensuel d’e-mails de votre forfait est épuisé.',
        'what' => 'Votre forfait en comprend un nombre déterminé, et ce nombre est atteint. Rien de ce qui est déjà enregistré n’est affecté : seul l’ajout est suspendu.',
        'fix' => 'Un administrateur peut consulter l’utilisation de chaque limite sur la page Licence et y changer de forfait. Supprimer des enregistrements devenus inutiles libère également de la place.',
    ],

    'PM-7002' => [
        'title' => 'Cette fonctionnalité n’est pas incluse dans votre forfait actuel.',
        'what' => 'Cette partie de Patrimoine appartient à un forfait supérieur à celui de votre organisation.',
        'fix' => 'Un administrateur peut consulter le contenu de chaque forfait sur la page Licence et en changer depuis cet endroit. Ce que vous avez déjà enregistré n’est pas affecté par le forfait en cours.',
    ],

    'PM-7004' => [
        'title' => 'La limite de baux actifs de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des baux actifs.',
        'what' => 'Votre forfait en comprend un nombre déterminé, et ce nombre est atteint. Rien de ce qui est déjà enregistré n’est affecté : seul l’ajout est suspendu.',
        'fix' => 'Un administrateur peut consulter l’utilisation de chaque limite sur la page Licence et y changer de forfait. Supprimer des enregistrements devenus inutiles libère également de la place.',
    ],

    'PM-7005' => [
        'title' => 'La limite de tiers de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des tiers.',
        'what' => 'Votre forfait en comprend un nombre déterminé, et ce nombre est atteint. Rien de ce qui est déjà enregistré n’est affecté : seul l’ajout est suspendu.',
        'fix' => 'Un administrateur peut consulter l’utilisation de chaque limite sur la page Licence et y changer de forfait. Supprimer des enregistrements devenus inutiles libère également de la place.',
    ],

    'PM-7006' => [
        'title' => 'Impossible de charger les informations de licence.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-7007' => [
        'title' => 'La limite d’utilisateurs de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des utilisateurs.',
        'what' => 'Votre forfait en comprend un nombre déterminé, et ce nombre est atteint. Rien de ce qui est déjà enregistré n’est affecté : seul l’ajout est suspendu.',
        'fix' => 'Un administrateur peut consulter l’utilisation de chaque limite sur la page Licence et y changer de forfait. Supprimer des enregistrements devenus inutiles libère également de la place.',
    ],

    /* ---- 8xxx ---- */

    'PM-8001' => [
        'title' => 'L’organisation gestionnaire configurée ne peut pas être supprimée.',
        'what' => 'D’autres enregistrements dépendent de celui-ci. Le supprimer romprait l’historique qui explique d’où vient l’argent et où il est allé.',
        'fix' => 'Ouvrez l’enregistrement pour voir ce qui s’y rattache et traitez-le d’abord. Souvent, mieux vaut le conserver : l’historique reste lisible et rien n’est facturé pour des enregistrements inutilisés.',
    ],

    'PM-8002' => [
        'title' => 'Votre propre société doit rester l’organisation gestionnaire. Désignez d’abord une autre partie comme organisation gestionnaire.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-8003' => [
        'title' => 'L’organisation gestionnaire n’a pas encore été configurée.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    /* ---- 9xxx ---- */

    'PM-9001' => [
        'title' => 'Patrimoine n’a pas pu terminer cette opération.',
        'what' => 'La requête n’a pas abouti. À ce stade, Patrimoine ne peut dire si la cause est le réseau, la session ou le service lui-même.',
        'fix' => 'Réessayez. En cas de second échec, rechargez la page — cela renouvelle aussi la session. Si l’échec persiste, dites-nous ce que vous faisiez : nous examinerons notre côté.',
    ],

    'PM-9002' => [
        'title' => 'Votre session a expiré. Veuillez vous reconnecter.',
        'what' => 'Patrimoine vous déconnecte après une période d’inactivité, afin qu’un écran resté ouvert ne serve pas à un passant. La page est restée ouverte ; la session derrière elle, non.',
        'fix' => 'Reconnectez-vous. Le travail enregistré est intact. Ce qui a été saisi sans être enregistré devra l’être de nouveau : copiez-le hors du formulaire avant de vous reconnecter.',
    ],

    'PM-9003' => [
        'title' => 'Impossible de charger les informations du tableau de bord.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-9004' => [
        'title' => 'Impossible de charger cette section.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-9005' => [
        'title' => 'L\'organisation gestionnaire n\'est pas encore configurée. Renseignez le formulaire ci-dessous et enregistrez pour la mettre en place.',
        'what' => 'Votre propre société n’est pas encore renseignée. Patrimoine inscrit ce nom sur les factures, reçus et relevés : il lui est nécessaire avant de produire ces documents.',
        'fix' => 'Complétez le formulaire de l’organisation dans les Paramètres et enregistrez. Le nom et les coordonnées suffisent pour commencer ; le reste peut suivre.',
    ],

    'PM-9006' => [
        'title' => 'Impossible d\'exporter le registre.',
        'what' => 'Le document n’a pas pu être produit ou transmis. Les données sous-jacentes ne risquent rien : il s’agit de la fabrication du fichier, pas de son contenu.',
        'fix' => 'Réessayez, et rechargez la page en cas de second échec. Si un document précis échoue systématiquement alors que les autres fonctionnent, dites-nous lequel : cela désigne un problème précis que nous pouvons corriger.',
    ],

    'PM-9007' => [
        'title' => 'Impossible d\'importer la sauvegarde.',
        'what' => 'Patrimoine a interrompu l’action parce qu’une règle de l’application n’était pas respectée. Rien n’a été enregistré.',
        'fix' => 'Lisez le message affiché : il indique ce qui ne va pas. Corrigez, puis réessayez.',
    ],

    'PM-9008' => [
        'title' => 'Impossible de charger l’organisation gestionnaire.',
        'what' => 'Patrimoine a demandé ces informations au serveur et n’a reçu aucune réponse. En général, la connexion a été interrompue ou la session a expiré pendant que la page était ouverte.',
        'fix' => 'Réessayez. Si cela se reproduit, rechargez la page — cela renouvelle aussi une session expirée. Si le problème persiste, la faute revient à votre connexion ou à notre service : signalez-le-nous.',
    ],

    'PM-9009' => [
        'title' => 'Impossible d’enregistrer l’organisation gestionnaire.',
        'what' => 'La demande est parvenue à Patrimoine sans aboutir : rien n’a été enregistré. Ce que vous saisissiez reste inchangé.',
        'fix' => 'Vérifiez si un champ est signalé en rouge, puis réessayez. Si rien n’est signalé et que l’échec persiste, dites-nous ce que vous tentiez d’enregistrer.',
    ],
];

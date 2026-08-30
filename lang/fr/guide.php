<?php

/*
|--------------------------------------------------------------------------
| Le guide pratique (français)
|--------------------------------------------------------------------------
|
| Un seul manuel, lu dans l'onglet Guide de Patrimoine et sur les pages
| publiques de patrimoine365.com/documentation/guides.
|
| L'anglais fait autorité sur la STRUCTURE : mêmes catégories, mêmes
| tâches, mêmes étapes, mêmes captures d'écran. GuideTest échoue si ce
| fichier s'en écarte — un manuel qui aurait une étape dans une langue et
| pas dans l'autre serait pire qu'un manuel simplement non traduit.
|
| Les captures d'écran sont prises sur l'application en français par
| scripts/capture-guide.mjs : un lecteur francophone voit ses propres
| écrans, pas ceux de quelqu'un d'autre.
|
*/

return [

    'title' => 'Guide pratique',
    'description' => 'Chaque tâche de Patrimoine, étape par étape, avec les images des écrans que vous aurez sous les yeux.',

    'categories' => [

        /*
        |----------------------------------------------------------------------
        | Pour commencer
        |----------------------------------------------------------------------
        */

        'getting_started' => [
            'title' => 'Pour commencer',
            'summary' => 'Se connecter, s\'orienter, et régler Patrimoine à votre façon de lire.',

            'tasks' => [

                'signing_in' => [
                    'title' => 'Se connecter',
                    'intro' => 'Chaque connexion demande deux choses : votre mot de passe, et un code à six chiffres envoyé à votre adresse e-mail. Le code est demandé à chaque fois, pas seulement sur un nouvel appareil.',
                    'steps' => [
                        ['text' => 'Rendez-vous sur app.patrimoine365.com. Saisissez votre adresse e-mail et votre mot de passe, puis appuyez sur Se connecter.', 'shot' => 'login'],
                        ['text' => 'Patrimoine vous envoie un code à six chiffres par e-mail. Ouvrez votre boîte de réception et copiez-le.'],
                        ['text' => 'Saisissez le code et appuyez sur Vérifier. Le code est valable quelques minutes et pour une seule connexion.', 'shot' => 'mfa'],
                        ['text' => 'Si le code n\'est pas arrivé au bout d\'une minute, appuyez sur Envoyer un nouveau code. Le précédent cesse de fonctionner dès qu\'un nouveau est envoyé.', 'note' => 'Vérifiez le dossier indésirables avant de demander un troisième code — les demandes répétées sont limitées.'],
                    ],
                    'after' => 'Vous arrivez sur le Tableau de bord. Votre session dure jusqu\'à ce que vous vous déconnectiez ou fermiez le navigateur.',
                ],

                'finding_your_way' => [
                    'title' => 'S\'orienter',
                    'intro' => 'Tout est accessible depuis la barre latérale de gauche, organisée selon la nature du travail plutôt que selon les parties du logiciel.',
                    'steps' => [
                        ['text' => 'Espace de travail contient le quotidien : Tableau de bord, Biens, Parties et Baux.', 'shot' => 'sidebar'],
                        ['text' => 'Finance contient l\'argent : Locataires, Propriétaires, Comptabilité et Rapports.'],
                        ['text' => 'Administration contient le reste : Paramètres, Journal d\'activité et Journal financier. Ce groupe n\'est visible que des administrateurs.'],
                        ['text' => 'La barre supérieure affiche l\'organisation à laquelle vous êtes connecté, la date du jour, la cloche de notifications et votre photographie. Appuyez sur la photographie pour votre profil et pour vous déconnecter.', 'shot' => 'topbar'],
                    ],
                ],

                'language_currency' => [
                    'title' => 'Choisir la langue et la devise',
                    'intro' => 'Les deux se règlent pour toute l\'organisation, et non par personne, afin qu\'un document produit par un collègue se lise comme celui produit par un autre.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres depuis le groupe Administration de la barre latérale.'],
                        ['text' => 'Choisissez l\'onglet Préférences.', 'shot' => 'settings-preferences'],
                        ['text' => 'Choisissez la langue. L\'anglais et le français sont complets l\'un comme l\'autre : chaque écran, chaque document et chaque e-mail la suivent.'],
                        ['text' => 'Choisissez la devise et le taux de TVA par défaut. La devise décide de l\'écriture de chaque montant, séparateur des milliers compris.'],
                        ['text' => 'Appuyez sur Enregistrer. L\'écran change de langue immédiatement ; les documents suivent à partir du prochain produit.'],
                    ],
                ],

                'appearance' => [
                    'title' => 'Passer du clair au sombre',
                    'intro' => 'L\'apparence n\'appartient qu\'à vous et reste dans votre navigateur. Elle n\'affecte ni vos collègues ni les documents.',
                    'steps' => [
                        ['text' => 'Appuyez sur votre photographie en haut à droite.'],
                        ['text' => 'Choisissez le réglage clair ou sombre dans le panneau de profil.', 'shot' => 'profile-drawer'],
                        ['text' => 'Le changement est immédiat et retenu sur ce navigateur.'],
                    ],
                ],

                'profile_photo' => [
                    'title' => 'Ajouter votre photographie',
                    'intro' => 'Votre photographie se trouve en haut à droite de chaque écran et à côté de votre nom sur la page Utilisateurs, ce qui permet à un administrateur de distinguer les comptes d\'un coup d\'œil. Tant que vous n\'en ajoutez pas, vos initiales apparaissent sur une couleur qui vous est propre.',
                    'steps' => [
                        ['text' => 'Appuyez sur votre photographie ou vos initiales en haut à droite, puis sur Modifier le profil.'],
                        ['text' => 'Appuyez sur Choisir une image et sélectionnez un fichier. JPG, PNG, WEBP et GIF fonctionnent tous, et une photographie HEIC d\'iPhone fonctionne dans Safari.', 'shot' => 'profile-photo'],
                        ['text' => 'Faites glisser l\'image dans la fenêtre ronde et zoomez avec le curseur ou la molette jusqu\'au cadrage voulu.'],
                        ['text' => 'Appuyez sur Enregistrer. Pour modifier le cadrage plus tard, appuyez sur Recadrer : l\'image rouvre exactement là où vous l\'aviez laissée, sans avoir à retrouver le fichier.'],
                    ],
                ],

                'paging_lists' => [
                    'title' => 'Lire une longue liste',
                    'intro' => 'Toute liste de plus de 25 lignes s\'affiche page par page.',
                    'steps' => [
                        ['text' => 'La ligne au pied de la liste indique combien de lignes sont affichées sur le total existant.', 'shot' => 'pagination'],
                        ['text' => 'Appuyez sur un numéro de page pour y aller directement. La page que vous lisez est celle qui est remplie.'],
                        ['text' => 'Réglez Lignes par page sur 25, 50 ou 100. Votre choix est retenu pour cette liste sur ce navigateur.'],
                        ['text' => 'Une recherche ou un filtre vous ramène à la première page, afin qu\'un résultat plus loin ne se cache pas derrière un numéro de page.'],
                    ],
                ],

                'ask_for_help' => [
                    'title' => 'Nous demander de l\'aide',
                    'intro' => 'Le support, le guide que vous lisez, les codes d\'erreur et le journal des mises à jour sont sur une seule page, accessible depuis votre photographie en haut à droite.',
                    'steps' => [
                        ['text' => 'Appuyez sur votre photographie en haut à droite et choisissez Support.'],
                        ['text' => 'La page s\'ouvre sur Contacter le support. Écrivez ce que vous tentiez de faire et ce qui s\'est produit à la place, puis appuyez sur Envoyer au support.'],
                        ['text' => 'Si un message portait un code commençant par PM-, indiquez-le. Il nous dit exactement quel refus vous avez rencontré, sans description de l\'écran.'],
                        ['text' => 'Votre nom, votre organisation et l\'adresse à laquelle nous répondons proviennent de votre compte : il n\'y a rien d\'autre à remplir. Nous répondons par e-mail.'],
                        ['text' => 'Les autres onglets de la même page sont le guide, la liste complète des codes d\'erreur, et ce qui a changé à chaque version.'],
                    ],
                    'after' => 'Tout le monde peut nous écrire : un Lecteur qui ne peut pas faire son travail doit pouvoir le dire autant qu\'un administrateur.',
                ],

                'roles' => [
                    'title' => 'Comprendre qui peut faire quoi',
                    'intro' => 'Patrimoine compte trois rôles. Ils sont fixes : ce que chacun peut faire est identique dans toutes les organisations.',
                    'steps' => [
                        ['text' => 'Un Administrateur peut tout faire, y compris les Paramètres, les Utilisateurs, la Licence, le Journal d\'activité et le Journal financier.'],
                        ['text' => 'Un Gestionnaire immobilier fait tout le travail quotidien — biens, parties, baux, paiements, propriétaires et rapports — et peut supprimer des enregistrements, mais n\'atteint pas le groupe Administration.'],
                        ['text' => 'Un Lecteur peut consulter et exporter des rapports, et ne peut rien modifier.'],
                        ['text' => 'Les commandes qu\'un rôle ne peut pas utiliser ne sont pas seulement désactivées : elles ne sont pas affichées. Le serveur applique la même règle, si bien qu\'une commande masquée ne peut pas être atteinte autrement.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Biens
        |----------------------------------------------------------------------
        */

        'properties' => [
            'title' => 'Biens et unités',
            'summary' => 'Les immeubles, à qui ils appartiennent, et les unités qu\'ils contiennent.',

            'tasks' => [

                'add_property' => [
                    'title' => 'Ajouter un bien',
                    'intro' => 'Un bien est un immeuble. Même une maison individuelle est un immeuble d\'une seule unité, car c\'est ce qui permet de la louer, de la facturer et de la reporter comme tout le reste.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Biens depuis la barre latérale et appuyez sur Ajouter un bien.', 'shot' => 'properties-list'],
                        ['text' => 'Donnez un nom et une adresse à l\'immeuble. Le nom est celui qui figure sur les documents : utilisez celui par lequel on le désigne.', 'shot' => 'property-drawer'],
                        ['text' => 'Appuyez sur Enregistrer. L\'immeuble apparaît dans le portefeuille, encore sans unité ni propriétaire.'],
                    ],
                    'after' => 'Enregistrez ensuite qui le possède, puis ajoutez ses unités.',
                ],

                'record_ownership' => [
                    'title' => 'Enregistrer qui possède un bien',
                    'intro' => 'La propriété commande tout le volet propriétaire des comptes : qui a droit aux loyers, qui supporte les dépenses, et ce que dit chaque relevé. Les quotes-parts doivent totaliser 100 %.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Sur la page Biens, appuyez sur Modifier sur l\'immeuble, puis ouvrez sa section de propriété.'],
                        ['text' => 'Appuyez sur Ajouter un propriétaire et choisissez une partie. Toute personne nommée ici doit déjà exister comme partie ayant le rôle propriétaire.', 'shot' => 'property-owners'],
                        ['text' => 'Saisissez le pourcentage détenu par ce propriétaire.'],
                        ['text' => 'Ajoutez les autres propriétaires jusqu\'à ce que les quotes-parts totalisent exactement 100 %. Patrimoine refuse d\'enregistrer autre chose.'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'add_units' => [
                    'title' => 'Ajouter des unités à un bien',
                    'intro' => 'Une unité est ce qui se loue réellement : un appartement, un local, un bureau, ou la maison entière. Un bail porte toujours sur une unité, jamais sur l\'immeuble.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Sur la page Biens, appuyez sur Ajouter une unité sur l\'immeuble.'],
                        ['text' => 'Donnez un nom ou un numéro à l\'unité, et une description si elle aide à la reconnaître.', 'shot' => 'unit-drawer'],
                        ['text' => 'Appuyez sur Enregistrer, et recommencez pour chaque unité.'],
                        ['text' => 'Appuyez sur Voir les unités sur l\'immeuble pour toutes les voir et connaître leur état de location.'],
                    ],
                ],

                'edit_delete_property' => [
                    'title' => 'Modifier ou supprimer un bien',
                    'intro' => 'La modification est libre. La suppression ne l\'est pas : ce qui porte un historique financier est conservé, car le supprimer laisserait les comptes incapables de s\'expliquer.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Appuyez sur Modifier sur l\'immeuble pour changer son nom, son adresse ou sa propriété.'],
                        ['text' => 'Appuyez sur Supprimer pour le retirer. Patrimoine vous dit exactement ce qui disparaîtrait avec lui avant que rien ne se produise.', 'shot' => 'property-delete'],
                        ['text' => 'Un immeuble dont des unités ont déjà été louées ne peut pas être supprimé. Ni une unité derrière laquelle il y a un bail.'],
                        ['text' => 'Confirmez pour supprimer. Il n\'y a pas de retour en arrière.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Parties
        |----------------------------------------------------------------------
        */

        'parties' => [
            'title' => 'Parties',
            'summary' => 'Tous ceux avec qui Patrimoine traite : locataires, propriétaires, agents et votre propre organisation.',

            'tasks' => [

                'add_party' => [
                    'title' => 'Ajouter une partie',
                    'intro' => 'Une partie est une personne ou une société. Un seul enregistrement porte tous les rôles que cette personne joue : quelqu\'un qui loue un appartement et en possède un autre est une seule partie, pas deux.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Parties depuis la barre latérale et appuyez sur Ajouter une partie.', 'shot' => 'parties-list'],
                        ['text' => 'Choisissez Personne ou Société. Une société a aussi une personne de contact, avec son propre numéro de téléphone.', 'shot' => 'party-drawer'],
                        ['text' => 'Saisissez le nom, l\'adresse e-mail et le numéro de téléphone. Choisissez le pays à côté du numéro : un numéro ne peut pas être composé sans savoir à quel pays il appartient.'],
                        ['text' => 'Cochez chaque rôle joué par cette partie : propriétaire, locataire, agent, ou votre organisation gestionnaire.'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'party_roles' => [
                    'title' => 'Donner plusieurs rôles à une partie',
                    'intro' => 'Les rôles ne s\'excluent pas. Le même enregistrement peut être propriétaire d\'un immeuble et locataire d\'un autre, et Patrimoine tient les deux volets de son compte entièrement séparés.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Appuyez sur Modifier sur la partie.'],
                        ['text' => 'Cochez le rôle supplémentaire et appuyez sur Enregistrer.'],
                        ['text' => 'La partie apparaît désormais dans la liste de chacun de ses rôles — sous Locataires, sous Propriétaires, ou les deux.'],
                        ['text' => 'Votre propre société doit conserver le rôle d\'organisation gestionnaire. Patrimoine refuse de le retirer tant qu\'une autre partie n\'a pas été désignée à sa place.'],
                    ],
                ],

                'party_emails' => [
                    'title' => 'Contrôler ce qui est envoyé à une partie',
                    'intro' => 'Patrimoine envoie aux parties des factures, des reçus, des rappels, des avis et des bons. Vous pouvez couper cela pour une partie ou pour toutes.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Pour une partie : appuyez sur Modifier et désactivez les e-mails sur sa fiche. Elle ne reçoit alors plus rien, et quiconque essaie d\'envoyer apprend pourquoi.', 'shot' => 'party-email-control'],
                        ['text' => 'Pour tout le monde : ouvrez Paramètres et, sous Communications, désactivez Envoyer des e-mails aux parties.'],
                        ['text' => 'Une fois désactivé, une partie précise peut tout de même être réautorisée depuis sa propre fiche.'],
                        ['text' => 'Les e-mails destinés aux utilisateurs de Patrimoine — codes de connexion, invitations, réinitialisations — ne sont jamais affectés par l\'un ou l\'autre de ces réglages.'],
                    ],
                ],

                'party_data' => [
                    'title' => 'Produire tout ce qui est détenu sur une partie',
                    'intro' => 'Un locataire ou un propriétaire peut demander ce que vous détenez sur lui, et c’est à vous d’y répondre : ses données relèvent de vous, pas de nous. Ceci en produit la totalité en un seul fichier.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Parties et trouvez la personne.'],
                        ['text' => 'Appuyez sur Données sur sa ligne. Le fichier se télécharge immédiatement.', 'shot' => 'party-data'],
                        ['text' => 'Il contient sa fiche, ses rôles, ses baux, ses factures et paiements, ce qui est détenu pour elle, et chaque entrée du journal d’activité la concernant.'],
                        ['text' => 'Le fichier est au format JSON, celui qu’attend une demande de portabilité. Envoyez-le tel quel, ou ouvrez-le dans un tableur si la personne préfère un tableau.'],
                    ],
                    'after' => 'La production est elle-même inscrite au journal d’activité : vous pouvez montrer quand une demande a reçu réponse.',
                ],

                'party_erasure' => [
                    'title' => 'Effacer une personne qui demande l’oubli',
                    'intro' => 'L’effacement détruit la personne, pas les comptes. Tout ce qui identifie disparaît définitivement ; les factures et les écritures demeurent et la désignent par une référence plutôt que par un nom.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Parties, trouvez la personne et appuyez sur Effacer.', 'shot' => 'party-erase'],
                        ['text' => 'Lisez ce qui disparaît : nom, adresse e-mail, numéros de téléphone, adresse postale, numéros d’identité et d’immatriculation, coordonnées bancaires et notes. Tout cela, définitivement.'],
                        ['text' => 'Lisez ce qui reste : les factures, les paiements et les écritures du journal. La loi qui impose de les conserver est celle-là même qui vous autorise à refuser de les détruire : ils demeurent, ne désignant la personne que par « Erased party #248 ».'],
                        ['text' => 'Saisissez le nom exactement tel que la fiche l’affiche, puis votre propre mot de passe.'],
                        ['text' => 'Appuyez sur Effacer cette personne. L’opération est irréversible, et Patrimoine ne lui écrira plus jamais.'],
                    ],
                    'after' => 'Pensez à produire ses données d’abord si elle a demandé une copie en plus de l’effacement. Ensuite, il n’y aura plus rien à produire.',
                ],

                'delete_party' => [
                    'title' => 'Supprimer une partie',
                    'intro' => 'Une partie sans historique peut être supprimée purement et simplement. Une partie qui en a ne le peut pas, pour la même raison qu\'un bien.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Appuyez sur Supprimer sur la partie.'],
                        ['text' => 'Patrimoine énumère ce qui dépend d\'elle — baux, paiements, comptes propriétaires — avant que rien ne se produise.'],
                        ['text' => 'Si rien n\'en dépend, confirmez et elle disparaît. Si quelque chose en dépend, la suppression est refusée et la raison est nommée.'],
                    ],
                ],

            ],
        ],


        /*
        |----------------------------------------------------------------------
        | Baux
        |----------------------------------------------------------------------
        */

        'leases' => [
            'title' => 'Baux',
            'summary' => 'Créer une location, la modifier en cours de route, et y mettre fin correctement.',

            'tasks' => [

                'lease_wizard' => [
                    'title' => 'Créer un bail avec l\'assistant',
                    'intro' => 'L\'assistant construit une location complète en une seule fois — le bien, l\'unité, le propriétaire, le locataire et le bail lui-même — en demandant une chose à la fois. C\'est la manière recommandée de créer votre premier bail.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Baux et appuyez sur Assistant de bail.', 'shot' => 'leases-list'],
                        ['text' => 'La première page explique les mots employés par l\'assistant — partie, propriétaire, unité — pour que rien n\'ait à être deviné ensuite. Lisez-la une fois et appuyez sur Suivant.', 'shot' => 'wizard-intro'],
                        ['text' => 'Choisissez le bien et l\'unité louée, ou créez l\'un ou l\'autre. Un nouveau bien se voit demander ici son nom et son adresse.', 'shot' => 'wizard-property'],
                        ['text' => 'Enregistrez qui le possède, et les quotes-parts. L\'assistant saute cette page lorsque la propriété du bien est déjà enregistrée.', 'shot' => 'wizard-owners'],
                        ['text' => 'Choisissez le locataire, ou créez-en un. Un nouveau locataire exige un nom, une adresse e-mail et un numéro de téléphone avec son pays.', 'shot' => 'wizard-tenant'],
                        ['text' => 'Désignez l\'agent immobilier, s\'il y en a un. Laissez « aucun » dans le cas contraire.'],
                        ['text' => 'Indiquez la date de début et soit une durée, soit une date de fin. Les dates se saisissent au format de votre organisation, avec le calendrier Patrimoine à côté du champ.', 'shot' => 'wizard-dates'],
                        ['text' => 'Indiquez le préavis, si la location en prévoit un.'],
                        ['text' => 'Saisissez le loyer, sa périodicité, et le dépôt de garantie s\'il y en a un.', 'shot' => 'wizard-rent'],
                        ['text' => 'Indiquez les honoraires de gestion, la commission d\'agent et toute augmentation de loyer programmée.'],
                        ['text' => 'Lisez la page de révision. Elle présente en une seule liste tout ce qui va être créé.', 'shot' => 'wizard-review'],
                        ['text' => 'Appuyez sur Enregistrer et activer. Tout est créé ensemble ; rien n\'est enregistré avant ce moment.'],
                    ],
                    'after' => 'Le bail est actif et sa première facture suit selon la périodicité choisie.',
                ],

                'lease_drafts' => [
                    'title' => 'Laisser l\'assistant inachevé et y revenir',
                    'intro' => 'Enregistrer comme brouillon conserve l\'assistant exactement tel que vous l\'avez laissé, si peu que vous ayez rempli. Ce n\'est pas un bail à moitié fait : aucun enregistrement n\'est créé tant que vous ne l\'activez pas.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Appuyez sur Enregistrer comme brouillon à tout moment avant la page de révision.'],
                        ['text' => 'Le brouillon apparaît sous Assistants inachevés en haut de la page Baux, au nom de la personne qui l\'a commencé et du jour où elle l\'a fait.', 'shot' => 'lease-drafts'],
                        ['text' => 'Appuyez sur Continuer pour le reprendre à la page où vous l\'aviez laissé.'],
                        ['text' => 'Appuyez sur Supprimer pour le jeter. Supprimer un assistant inachevé n\'efface rien d\'autre — il n\'y a jamais eu de bail.'],
                    ],
                ],

                'create_lease_directly' => [
                    'title' => 'Créer un bail directement',
                    'intro' => 'Lorsque le bien, l\'unité et le locataire existent déjà, le formulaire direct est plus rapide que l\'assistant.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Baux et appuyez sur Ajouter un bail.'],
                        ['text' => 'Choisissez l\'unité et le locataire. Les deux doivent déjà exister.', 'shot' => 'lease-drawer'],
                        ['text' => 'Indiquez la date de début, la date de fin ou la durée, le loyer et sa périodicité.'],
                        ['text' => 'Ajoutez le dépôt de garantie, les honoraires de gestion et la commission d\'agent s\'ils s\'appliquent.'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'extend_lease' => [
                    'title' => 'Prolonger un bail',
                    'intro' => 'Prolonger inscrit un nouveau terme sur le même bail, de sorte que l\'historique de ce qui a été convenu et quand reste lisible.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez le bail et appuyez sur Prolonger.'],
                        ['text' => 'Indiquez la nouvelle date de fin, et le nouveau loyer s\'il change.', 'shot' => 'lease-extend'],
                        ['text' => 'Appuyez sur Enregistrer. Le bail conserve son numéro et son historique ; le nouveau terme se place à côté de l\'ancien.'],
                    ],
                ],

                'rent_increment' => [
                    'title' => 'Programmer une augmentation de loyer',
                    'intro' => 'Une augmentation est datée à l\'avance et appliquée par Patrimoine le jour venu, sans que personne ait à y penser.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez le bail et trouvez ses augmentations de loyer.'],
                        ['text' => 'Saisissez le nouveau loyer et la date de prise d\'effet.', 'shot' => 'rent-increment'],
                        ['text' => 'Appuyez sur Enregistrer. Rien ne change encore.'],
                        ['text' => 'À la date d\'effet, le traitement nocturne l\'applique, et les factures suivantes portent le nouveau loyer.'],
                        ['text' => 'Une augmentation programmée qui n\'a pas encore pris effet peut être annulée.'],
                    ],
                ],

                'terminate_lease' => [
                    'title' => 'Résilier un bail et solder',
                    'intro' => 'La résiliation clôt la location et solde le dépôt de garantie dans le même geste : ce qui est dû est déduit, et le reliquat est restitué. C\'est irréversible.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez le bail et appuyez sur Résilier.'],
                        ['text' => 'Indiquez la date de résiliation.', 'shot' => 'lease-terminate'],
                        ['text' => 'Saisissez les retenues sur le dépôt, avec un motif pour chacune. Les loyers impayés et les dégradations sont les cas habituels.'],
                        ['text' => 'Lisez le montant du solde. C\'est le dépôt moins les retenues, et c\'est ce qui revient au locataire.'],
                        ['text' => 'Confirmez. Patrimoine écrit le décompte, produit le bon de restitution et clôt le bail. Le solde final est irréversible.'],
                    ],
                ],

                'lease_history' => [
                    'title' => 'Consulter l\'historique financier d\'un bail',
                    'intro' => 'Tout ce qui s\'est passé sur une location, au même endroit : factures émises, paiements reçus, dépôts détenus, bons produits.',
                    'steps' => [
                        ['text' => 'Ouvrez le bail et appuyez sur Historique financier.'],
                        ['text' => 'La liste va du plus récent au plus ancien et se pagine comme toutes les autres.', 'shot' => 'lease-history'],
                        ['text' => 'Appuyez sur une ligne pour ouvrir le document correspondant.'],
                    ],
                ],

                'delete_lease' => [
                    'title' => 'Supprimer un bail',
                    'intro' => 'Un bail sans mouvement d\'argent peut être supprimé. Un bail portant des factures ou des paiements ne le peut pas, et Patrimoine le dit avant que vous ne vous engagiez.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez le bail et repérez la zone de danger au bas de la page.'],
                        ['text' => 'Appuyez sur Supprimer. Patrimoine énumère exactement ce que la suppression emporterait.', 'shot' => 'lease-delete'],
                        ['text' => 'Confirmez si la liste est vide. Si elle ne l\'est pas, le bail reste et la raison est nommée.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Encaissements
        |----------------------------------------------------------------------
        */

        'money_in' => [
            'title' => 'Encaissements',
            'summary' => 'Encaisser les loyers, détenir les dépôts, et tout ce qui touche au solde d\'un locataire.',

            'tasks' => [

                'record_payment' => [
                    'title' => 'Enregistrer un paiement de loyer',
                    'intro' => 'Enregistrer un paiement est de loin l\'opération la plus fréquente dans Patrimoine. Elle produit le reçu, impute la somme sur ce qui est dû et passe les écritures comptables, en un seul geste.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires et sélectionnez le locataire.', 'shot' => 'tenants-list'],
                        ['text' => 'Appuyez sur Enregistrer un dépôt — c\'est ici que se saisit l\'argent reçu d\'un locataire, quel qu\'en soit l\'objet.', 'shot' => 'tenant-deposit'],
                        ['text' => 'Saisissez le montant, la date de réception et le mode de paiement : espèces, chèque, virement ou mobile money.'],
                        ['text' => 'Indiquez qui a encaissé et ajoutez la référence, s\'il y en a une.'],
                        ['text' => 'Appuyez sur Enregistrer. Le reçu est produit immédiatement et peut être envoyé au locataire depuis le même écran.'],
                    ],
                    'after' => 'La somme est imputée sur les factures ouvertes, de la plus ancienne à la plus récente. La tâche suivante explique exactement comment.',
                ],

                'fifo' => [
                    'title' => 'Comprendre l\'imputation d\'un paiement',
                    'intro' => 'Patrimoine ne demande jamais à quelle facture se rapporte un paiement. Il impute la somme sur la facture impayée la plus ancienne, puis sur la suivante, et ainsi de suite — la règle qu\'attendent les comptables et celle qui rend les arriérés honnêtes.',
                    'steps' => [
                        ['text' => 'Un paiement est imputé sur la facture ouverte la plus ancienne jusqu\'à ce qu\'elle soit soldée.'],
                        ['text' => 'Le reliquat passe à la suivante, et ainsi de suite.'],
                        ['text' => 'Ce qui reste une fois toutes les factures soldées demeure sur le paiement, en attente d’affectation à l’un des comptes du locataire. Ce montant n’est PAS imputé de lui-même sur la facture suivante.'],
                        ['text' => 'Affectez-le depuis le paiement lui-même, en choisissant la réserve de loyer, l’avance consommable ou le dépôt de garantie. Tant que vous ne le faites pas, la somme ne figure sur aucun solde ni sur aucun compte.'],
                        ['text' => 'La cloche signale qu’une somme a été reçue sans être affectée, avec son montant, afin que rien ne reste oublié.'],
                        ['text' => 'L\'imputation est indiquée sur le paiement lui-même : vous voyez toujours à quelles factures un paiement a répondu.', 'shot' => 'payment-allocation'],
                    ],
                ],

                'tenant_withdrawal' => [
                    'title' => 'Effectuer un retrait sur un compte locataire',
                    'intro' => 'L\'argent détenu pour un locataire — un dépôt, ou un solde créditeur — peut lui être restitué. Le retrait produit son propre reçu numéroté.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires, sélectionnez le locataire et appuyez sur Retirer.'],
                        ['text' => 'Choisissez le compte débité et saisissez le montant.', 'shot' => 'tenant-withdrawal'],
                        ['text' => 'Patrimoine ne vous laissera pas retirer plus que ce que le compte contient.'],
                        ['text' => 'Appuyez sur Enregistrer. Le reçu de retrait est produit et peut être envoyé par e-mail.'],
                    ],
                ],

                'tenant_expense' => [
                    'title' => 'Enregistrer une dépense locataire',
                    'intro' => 'Une somme réglée pour le compte du locataire — une réparation à sa charge, une facture de service — imputée sur son compte.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires, sélectionnez le locataire et appuyez sur Enregistrer une dépense.'],
                        ['text' => 'Saisissez le montant, la date et la description de la dépense.', 'shot' => 'tenant-expense'],
                        ['text' => 'Appuyez sur Enregistrer. La dépense est imputée au locataire et figure sur son relevé.'],
                    ],
                ],

                'tenant_transfer' => [
                    'title' => 'Transférer entre comptes d\'un locataire',
                    'intro' => 'Déplacer une somme que le locataire détient déjà d\'un de ses comptes vers un autre — un solde créditeur vers un dépôt, par exemple. Aucun argent n\'entre ni ne sort.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires, sélectionnez le locataire et appuyez sur Transférer.'],
                        ['text' => 'Choisissez le compte d\'origine et le compte de destination.', 'shot' => 'tenant-transfer'],
                        ['text' => 'Saisissez le montant et la date, et indiquez le motif.'],
                        ['text' => 'Appuyez sur Enregistrer. Un bon de transfert est produit et peut être envoyé comme tout autre document.'],
                    ],
                ],

                'tenant_adjustment' => [
                    'title' => 'Ajuster le solde d\'un locataire',
                    'intro' => 'Une correction, et rien d\'autre. Les dépôts, dépenses et retraits ont chacun leur propre action ; utiliser un ajustement à leur place masque ce qui s\'est réellement passé.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires, sélectionnez le locataire et appuyez sur Ajuster.'],
                        ['text' => 'Choisissez le compte et saisissez le solde qu\'il devrait présenter.', 'shot' => 'tenant-adjustment'],
                        ['text' => 'Patrimoine affiche le solde actuel, le solde corrigé et la différence avant que vous n\'enregistriez.'],
                        ['text' => 'Expliquez pourquoi la correction est nécessaire. Le motif est conservé définitivement avec l\'écriture.'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'tenant_accounts' => [
                    'title' => 'Voir ce qu\'un locataire détient et doit',
                    'intro' => 'Un seul écran pour toute la relation : chaque compte, son solde, et chaque mouvement qui l\'a traversé.',
                    'steps' => [
                        ['text' => 'Ouvrez Locataires et sélectionnez le locataire.'],
                        ['text' => 'Appuyez sur Voir les comptes.', 'shot' => 'tenant-accounts'],
                        ['text' => 'Chaque compte affiche son solde et ses mouvements, du plus récent au plus ancien.'],
                        ['text' => 'Appuyez sur un mouvement pour ouvrir le document correspondant.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Propriétaires
        |----------------------------------------------------------------------
        */

        'owners' => [
            'title' => 'Propriétaires',
            'summary' => 'Ce à quoi chaque propriétaire a droit, ce qui lui a été imputé, et son règlement.',

            'tasks' => [

                'owner_entitlement' => [
                    'title' => 'Voir les droits d\'un propriétaire',
                    'intro' => 'Un propriétaire perçoit sur les loyers réellement encaissés, non sur les loyers facturés. Les honoraires de gestion et l\'éventuelle commission d\'agent sont prélevés à mesure que les loyers rentrent.',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires et sélectionnez le propriétaire.', 'shot' => 'owners-list'],
                        ['text' => 'Le compte indique ce qui a été perçu, ce qui a été imputé, ce qui a déjà été reversé et ce qui reste disponible.', 'shot' => 'owner-account'],
                        ['text' => 'Un propriétaire détenant des parts dans plusieurs immeubles a un compte consolidé unique, avec les biens listés en dessous.'],
                    ],
                ],

                'owner_deposit' => [
                    'title' => 'Enregistrer une somme reçue d\'un propriétaire',
                    'intro' => 'Un propriétaire verse parfois des fonds — pour couvrir une réparation, ou pour apurer un solde négatif.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires, sélectionnez le propriétaire et appuyez sur Enregistrer un dépôt.'],
                        ['text' => 'Saisissez le montant, la date, le mode de paiement et l\'objet.', 'shot' => 'owner-deposit'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'owner_expense' => [
                    'title' => 'Enregistrer une dépense sur un bien',
                    'intro' => 'Un coût imputé à l\'un des biens du propriétaire — une réparation, un entretien, une charge. Il réduit ce à quoi le propriétaire a droit.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires, sélectionnez le propriétaire et appuyez sur Enregistrer une dépense.'],
                        ['text' => 'Choisissez le bien auquel la dépense se rapporte.', 'shot' => 'owner-expense'],
                        ['text' => 'Saisissez le montant, la date et une description — « réparation de climatiseur » plutôt que « réparation ».'],
                        ['text' => 'Appuyez sur Enregistrer. Lorsqu\'un immeuble a plusieurs propriétaires, le coût est réparti au prorata des quotes-parts.'],
                    ],
                ],

                'owner_payout' => [
                    'title' => 'Régler un propriétaire',
                    'intro' => 'Reverser ce qu\'un propriétaire a perçu. Patrimoine ne vous laissera pas reverser plus que ce qui est réellement disponible.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires, sélectionnez le propriétaire et appuyez sur Effectuer un versement.'],
                        ['text' => 'Le solde disponible est affiché. Saisissez le montant et la date.', 'shot' => 'owner-payout'],
                        ['text' => 'Choisissez le mode de règlement et indiquez qui l\'a autorisé.'],
                        ['text' => 'Appuyez sur Enregistrer. Le versement est rapproché des produits qu\'il solde, du plus ancien au plus récent, et un bon numéroté est produit.'],
                    ],
                ],

                'owner_adjustment' => [
                    'title' => 'Ajuster un compte propriétaire',
                    'intro' => 'Pour les corrections comptables uniquement. Les dépôts, dépenses et versements ont chacun leur propre action.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires, sélectionnez le propriétaire et appuyez sur Ajuster.'],
                        ['text' => 'Choisissez le sens : un crédit augmente le solde du propriétaire, un débit le réduit.', 'shot' => 'owner-adjustment'],
                        ['text' => 'Saisissez le montant et la date, et expliquez pourquoi la correction est nécessaire.'],
                        ['text' => 'Appuyez sur Enregistrer.'],
                    ],
                ],

                'owner_statement' => [
                    'title' => 'Produire un relevé de propriétaire',
                    'intro' => 'Le document que le propriétaire attend réellement : ce que ses biens ont rapporté, ce qui a été dépensé, ce qui a été prélevé et ce qui lui a été versé, sur une période vérifiable.',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires et sélectionnez le propriétaire.'],
                        ['text' => 'Appuyez sur Relevé et choisissez la période.', 'shot' => 'owner-statement'],
                        ['text' => 'Produisez-le en PDF pour l\'envoyer, ou en XLSX ou CSV pour le retravailler.'],
                        ['text' => 'Il peut être envoyé directement au propriétaire, à condition que sa fiche autorise les e-mails.'],
                    ],
                ],

                'owner_bills' => [
                    'title' => 'Facturer un propriétaire et encaisser',
                    'intro' => 'Lorsqu\'un propriétaire doit être facturé plutôt que prélevé — des honoraires de gestion facturés à part, par exemple — Patrimoine émet une facture numérotée et suit son règlement.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez Propriétaires, sélectionnez le propriétaire et trouvez ses factures de dépenses.'],
                        ['text' => 'Émettez la facture avec son montant, sa date et sa description.', 'shot' => 'owner-bill'],
                        ['text' => 'Lorsqu\'elle est réglée, appuyez sur Payer sur la facture et enregistrez le mode de règlement.'],
                        ['text' => 'Un règlement enregistré par erreur peut être annulé au même endroit, ce qui rouvre la facture.'],
                    ],
                ],

            ],
        ],


        /*
        |----------------------------------------------------------------------
        | Facturation et automatisation
        |----------------------------------------------------------------------
        */

        'invoicing' => [
            'title' => 'Facturation et automatisation',
            'summary' => 'Les factures de loyer, ce que Patrimoine fait seul chaque nuit, et le règlement d\'une facture.',

            'tasks' => [

                'how_invoices_are_raised' => [
                    'title' => 'Comprendre l\'émission des factures de loyer',
                    'intro' => 'Vous n\'émettez pas les factures de loyer à la main. Patrimoine les émet à partir du bail, selon la périodicité qu\'il fixe, afin qu\'une location ne puisse pas être oubliée.',
                    'steps' => [
                        ['text' => 'Chaque bail actif indique le loyer et sa périodicité.'],
                        ['text' => 'Le traitement nocturne émet la facture suivante dès qu\'une période commence, numérotée dans l\'ordre.', 'shot' => 'invoices-list'],
                        ['text' => 'Une facture qui existe déjà pour une période n\'est jamais émise deux fois, quel que soit le nombre de traitements.'],
                        ['text' => 'La facture est envoyée par e-mail au locataire, sauf si les e-mails sont désactivés pour lui ou pour l\'organisation.'],
                    ],
                ],

                'nightly_run' => [
                    'title' => 'Savoir ce qui se passe chaque nuit',
                    'intro' => 'Six traitements s\'exécutent automatiquement. Ensemble, ils font avancer les comptes sans que personne se connecte.',
                    'steps' => [
                        ['text' => 'Les factures de loyer sont émises pour tout bail dont la période suivante a commencé.'],
                        ['text' => 'Les augmentations de loyer arrivées à leur date d\'effet sont appliquées.'],
                        ['text' => 'Des rappels partent pour les factures échues ou en retard.'],
                        ['text' => 'Les licences proches de l\'expiration produisent leurs avis.'],
                        ['text' => 'Chaque exécution est enregistrée, de sorte qu\'on puisse établir après coup qu\'elle a eu lieu et ce qu\'elle a fait.'],
                    ],
                ],

                'pay_invoice' => [
                    'title' => 'Solder une facture',
                    'intro' => 'Le loyer se règle normalement en enregistrant un paiement du locataire, imputé de la facture la plus ancienne à la plus récente. Une facture peut aussi être réglée directement lorsque c\'est plus clair.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez la facture depuis le locataire, le bail ou la liste des factures.'],
                        ['text' => 'Appuyez sur Payer.', 'shot' => 'invoice-pay'],
                        ['text' => 'Saisissez le montant, la date et le mode de règlement.'],
                        ['text' => 'Appuyez sur Enregistrer. Le reçu est produit et la facture indique ce qui reste dû, le cas échéant.'],
                    ],
                ],

                'cancel_invoice_payment' => [
                    'title' => 'Annuler un règlement saisi par erreur',
                    'intro' => 'Un règlement imputé sur la mauvaise facture, ou saisi deux fois, s\'annule plutôt qu\'il ne se supprime : la correction reste visible dans les comptes.',
                    'who' => 'Administrateurs et Gestionnaires immobiliers',
                    'steps' => [
                        ['text' => 'Ouvrez la facture et repérez le règlement.'],
                        ['text' => 'Appuyez sur Annuler le règlement et indiquez le motif.'],
                        ['text' => 'Confirmez. La facture se rouvre à hauteur du montant concerné, et la contre-passation est écrite au journal plutôt que l\'écriture d\'origine effacée.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Rapports
        |----------------------------------------------------------------------
        */

        'reports' => [
            'title' => 'Rapports',
            'summary' => 'Sortir des chiffres de Patrimoine, à l\'écran ou sous forme de fichier.',

            'tasks' => [

                'run_report' => [
                    'title' => 'Exécuter un rapport',
                    'intro' => 'Neuf rapports couvrent le portefeuille, l\'argent et les arriérés. Tous s\'exécutent de la même façon.',
                    'steps' => [
                        ['text' => 'Ouvrez Rapports depuis la barre latérale et choisissez le rapport voulu.', 'shot' => 'reports-list'],
                        ['text' => 'Indiquez la période et les filtres éventuels — un immeuble, un propriétaire, un locataire.', 'shot' => 'report-filters'],
                        ['text' => 'Appuyez sur Exécuter le rapport. Le résultat s\'affiche à l\'écran.', 'shot' => 'report-result'],
                        ['text' => 'Modifiez un filtre et relancez ; rien n\'est enregistré tant que vous n\'exportez pas.'],
                    ],
                ],

                'export_report' => [
                    'title' => 'Exporter un rapport',
                    'intro' => 'Chaque rapport peut sortir de Patrimoine sous trois formes : PDF pour l\'envoyer, XLSX pour le retravailler, CSV pour le charger ailleurs.',
                    'steps' => [
                        ['text' => 'Exécutez d\'abord le rapport. Les exports reprennent exactement ce qui est à l\'écran, filtres compris.'],
                        ['text' => 'Appuyez sur PDF, XLSX ou CSV.', 'shot' => 'report-export'],
                        ['text' => 'Le PDF s\'ouvre dans un nouvel onglet ; le XLSX et le CSV se téléchargent.'],
                        ['text' => 'Tous les rôles peuvent exporter, y compris les Lecteurs.'],
                    ],
                ],

                'dashboard' => [
                    'title' => 'Lire le tableau de bord',
                    'intro' => 'Le tableau de bord donne l\'état de l\'activité en un écran, et chaque chiffre renvoie aux enregistrements qui le composent.',
                    'steps' => [
                        ['text' => 'Ouvrez Tableau de bord depuis la barre latérale.', 'shot' => 'dashboard'],
                        ['text' => 'Les cartes indiquent ce qui a été encaissé, ce qui reste dû, et ce qui se passe ce mois-ci.'],
                        ['text' => 'La cloche en haut à droite porte ce qui appelle une attention : factures impayées, dépenses impayées, baux qui se terminent.', 'shot' => 'notifications'],
                        ['text' => 'Appuyez sur un chiffre pour ouvrir la liste à partir de laquelle il a été calculé.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Les journaux
        |----------------------------------------------------------------------
        */

        'journal' => [
            'title' => 'Les journaux',
            'summary' => 'Les deux registres que Patrimoine tient de lui-même : ce qu\'a fait l\'argent, et ce qu\'ont fait les personnes.',

            'tasks' => [

                'financial_journal' => [
                    'title' => 'Lire le journal financier',
                    'intro' => 'Chaque mouvement d\'argent dans Patrimoine repose sur une comptabilité en partie double. Le journal, c\'est cette comptabilité, lisible, avec le document à l\'origine de chaque écriture à un clic.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Journal financier depuis le groupe Administration.', 'shot' => 'journal-list'],
                        ['text' => 'Les écritures vont de la plus récente à la plus ancienne, chacune avec son numéro, sa date et son total.'],
                        ['text' => 'Filtrez par date, par type d\'opération ou par compte.'],
                        ['text' => 'Appuyez sur une écriture pour voir ses lignes — ce qui a été débité, ce qui a été crédité, et quel document l\'a produite.', 'shot' => 'journal-entry'],
                    ],
                ],

                'opening_balances' => [
                    'title' => 'Comprendre les soldes d\'ouverture',
                    'intro' => 'Une organisation qui avait une vie avant Patrimoine commence avec des soldes plutôt qu\'à zéro. Une fois la position d\'ouverture arrêtée et la porte fermée, elle ne peut plus être déplacée en silence.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Les soldes d\'ouverture sont importés une seule fois, au moment où l\'organisation commence à utiliser Patrimoine.'],
                        ['text' => 'Ils sont rapprochés : ce qui a été importé doit concorder avec ce que dit le journal.'],
                        ['text' => 'Après la bascule, la porte se ferme, et les écritures suivantes doivent passer par des opérations ordinaires.'],
                        ['text' => 'Le rapprochement peut être relancé à tout moment pour prouver que la position s\'équilibre toujours.'],
                    ],
                ],

                'activity_log' => [
                    'title' => 'Lire le journal d\'activité',
                    'intro' => 'Qui a fait quoi, quand, et depuis où. Le journal ne fait que s\'ajouter : rien n\'y peut être modifié ni supprimé, par personne, nous compris.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Journal d\'activité depuis le groupe Administration.', 'shot' => 'activity-list'],
                        ['text' => 'Chaque événement enregistre la personne, l\'action, l\'enregistrement touché et l\'heure.'],
                        ['text' => 'Il enregistre aussi l\'adresse, le navigateur et l\'appareil d\'où l\'action est partie.'],
                        ['text' => 'Filtrez par personne, par action ou par date, puis appuyez sur un événement pour le voir en entier.', 'shot' => 'activity-entry'],
                        ['text' => 'Exportez le journal en XLSX ou en CSV, pour un auditeur qui le veut hors de Patrimoine. Il n’y a pas de PDF : un journal conservé indéfiniment dépasse vite ce qu’une page peut contenir.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Administration
        |----------------------------------------------------------------------
        */

        'admin' => [
            'title' => 'Administration',
            'summary' => 'Votre organisation, les personnes qui y travaillent, votre formule et vos données.',

            'tasks' => [

                'settings_home' => [
                    'title' => 'S\'orienter dans les Paramètres',
                    'intro' => 'Les Paramètres contiennent tout le compte : l\'organisation elle-même, les personnes qui peuvent se connecter, et votre formule.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres depuis le groupe Administration de la barre latérale.', 'shot' => 'settings-account'],
                        ['text' => 'Organisation contient vos propres coordonnées, qui figurent sur chaque document que vous produisez.'],
                        ['text' => 'Utilisateurs, Licence, Préférences, Données et À propos sont les autres onglets. Les anciennes adresses /users et /license fonctionnent toujours et ouvrent le bon onglet.'],
                        ['text' => 'Le panneau à côté du formulaire résume le compte : votre formule, les personnes, les baux, les parties, et la date d\'ouverture du compte.'],
                    ],
                ],

                'organisation_details' => [
                    'title' => 'Renseigner les coordonnées de votre organisation',
                    'intro' => 'C\'est ce qu\'un locataire ou un propriétaire voit sur une facture, un reçu ou un relevé : cela vaut la peine d\'être renseigné correctement une fois pour toutes.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres et restez sur l\'onglet Organisation.'],
                        ['text' => 'Renseignez le nom, l\'adresse, le numéro de téléphone avec son pays, et l\'adresse e-mail.', 'shot' => 'settings-organisation'],
                        ['text' => 'Ajoutez les numéros d\'immatriculation et fiscaux si vos documents doivent les porter.'],
                        ['text' => 'Ajoutez les coordonnées bancaires devant figurer sur les factures.'],
                        ['text' => 'Appuyez sur Enregistrer. Les nouveaux documents portent les nouvelles coordonnées ; ceux déjà produits ne changent pas.'],
                    ],
                ],

                'invite_user' => [
                    'title' => 'Inviter un collègue',
                    'intro' => 'Les personnes sont invitées par e-mail et définissent elles-mêmes leur mot de passe. Personne d\'autre ne le connaît jamais, vous compris.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres et choisissez l\'onglet Utilisateurs.', 'shot' => 'users-list'],
                        ['text' => 'Appuyez sur Ajouter un utilisateur.'],
                        ['text' => 'Saisissez son nom et son adresse e-mail, et choisissez le rôle à lui attribuer.', 'shot' => 'user-drawer'],
                        ['text' => 'Appuyez sur Enregistrer. Une invitation lui est envoyée par e-mail.'],
                        ['text' => 'Elle suit le lien, définit un mot de passe, puis se connecte avec un code comme tout le monde.'],
                    ],
                ],

                'change_role' => [
                    'title' => 'Changer un rôle ou désactiver quelqu\'un',
                    'intro' => 'Un rôle peut être modifié à tout moment et prend effet au chargement d\'écran suivant. Une personne partie est désactivée plutôt que supprimée, afin que l\'historique de ce qu\'elle a fait reste lisible.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres, choisissez Utilisateurs et appuyez sur Modifier sur la personne.'],
                        ['text' => 'Changez le rôle et appuyez sur Enregistrer.'],
                        ['text' => 'Pour empêcher quelqu\'un de se connecter, passez son compte en inactif. Ses sessions prennent fin et ses jetons cessent de fonctionner immédiatement.'],
                        ['text' => 'Son nom reste sur tout ce qu\'elle a fait. Le journal d\'activité n\'est pas réécrit.'],
                    ],
                ],

                'licence' => [
                    'title' => 'Consulter votre formule et ce qu\'elle permet',
                    'intro' => 'L\'onglet Licence indique la formule souscrite, ce qu\'elle comprend, et la part que vous en utilisez.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres et choisissez l\'onglet Licence.', 'shot' => 'settings-licence'],
                        ['text' => 'La formule en cours est affichée avec votre consommation par rapport à chacune de ses limites.'],
                        ['text' => 'Le tableau en dessous compare les formules.'],
                        ['text' => 'Pour souscrire, prolonger ou changer de formule, contactez-nous : les licences sont délivrées et non achetées depuis cet écran.'],
                    ],
                ],

                'backup_restore' => [
                    'title' => 'Sauvegarder et restaurer votre registre',
                    'intro' => 'Votre registre — parties, immeubles et leur propriété, unités et baux — peut être exporté en entier et rechargé. L\'historique financier n\'est délibérément pas importable : il est immuable, et un moyen de l\'écrire depuis un fichier serait un moyen de réécrire les comptes.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres et choisissez l\'onglet Données.', 'shot' => 'settings-data'],
                        ['text' => 'Appuyez sur Exporter pour télécharger le registre sous forme de classeur. Faites-le avant toute modification importante.'],
                        ['text' => 'Pour restaurer, choisissez le fichier et appuyez sur Examiner la restauration. Rien n\'est encore écrit.'],
                        ['text' => 'Lisez la simulation : elle indique exactement ce qui serait créé et ce qui serait rapproché d\'enregistrements existants.', 'shot' => 'settings-restore'],
                        ['text' => 'N\'appuyez sur Appliquer cette restauration que si la simulation dit ce que vous attendez. Les enregistrements sont rapprochés plutôt que dupliqués : restaurer deux fois ne double pas votre registre.'],
                    ],
                ],

                'my_data' => [
                    'title' => 'Télécharger vos propres données',
                    'intro' => 'Toute personne disposant d’un compte peut obtenir copie de tout ce que Patrimoine détient sur elle, sans rien demander à personne. Aucun rôle n’est requis et aucun administrateur n’intervient.',
                    'steps' => [
                        ['text' => 'Appuyez sur votre photographie en haut à droite, puis sur Modifier le profil.'],
                        ['text' => 'Appuyez sur Télécharger mes données.', 'shot' => 'my-data'],
                        ['text' => 'Le fichier contient les détails de votre compte, les jetons avec lesquels il peut être utilisé, et chacune de vos actions — y compris l’adresse, le navigateur et l’appareil d’où elle est partie.'],
                        ['text' => 'Votre mot de passe n’y figure jamais. Il n’est stocké que sous forme de hachage et ne peut être reconverti en quoi que ce soit.'],
                    ],
                ],

                'organisation_data' => [
                    'title' => 'Télécharger tout ce que détient l’organisation',
                    'intro' => 'Une copie complète de toute l’organisation, historique financier compris, en un seul fichier structuré. Plus large que l’export du registre voisin : celui-là est le registre portable, celui-ci est la totalité.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres et choisissez l’onglet Données.'],
                        ['text' => 'Appuyez sur Tout télécharger.', 'shot' => 'organisation-data'],
                        ['text' => 'Les empreintes de mots de passe sont retirées avant l’écriture du fichier. Tout le reste s’y trouve.'],
                        ['text' => 'Servez-vous-en pour répondre à qui demande ce que vous détenez, ou pour conserver votre propre copie hors de Patrimoine.'],
                    ],
                ],

                'close_account' => [
                    'title' => 'Fermer le compte',
                    'intro' => 'Supprimer définitivement l\'organisation et tout ce qu\'elle contient. L\'opération est irréversible, pour vous comme pour nous : il n\'y a ni corbeille ni copie conservée.',
                    'who' => 'Administrateurs',
                    'steps' => [
                        ['text' => 'Ouvrez Paramètres, restez sur l\'onglet Organisation et descendez au bas de la page.', 'shot' => 'settings-danger'],
                        ['text' => 'Appuyez sur Fermer le compte. Le panneau dénombre ce qui va disparaître : les personnes, les baux, les parties.', 'shot' => 'close-account-drawer'],
                        ['text' => 'Ressaisissez le nom de l\'organisation, exactement tel qu\'il est affiché.'],
                        ['text' => 'Saisissez votre propre mot de passe.'],
                        ['text' => 'Appuyez sur Tout supprimer. Les biens, les baux, les factures, les paiements et le journal financier disparaissent avec, et vous êtes déconnecté.'],
                    ],
                    'after' => 'Si vous souhaitez seulement cesser de payer, ou mettre Patrimoine de côté quelque temps, écrivez plutôt à l\'assistance : cela se discute, ceci non.',
                ],

            ],
        ],

    ],

];

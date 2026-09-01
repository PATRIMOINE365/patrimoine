<?php

return [
    'common' => [
        'property_management' => 'Gestion immobilière',
        'dear' => 'Cher/Chère',
        'regards' => 'Cordialement',
        'generated_by' => 'Ce message a été généré par Patrimoine.',
        'balance_due' => 'Solde à payer',
        'due_date' => 'Date d’échéance',
        'tenant' => 'Locataire',
    ],

    'invoice' => [
        'title' => 'Facture :number',
        'subject' => 'Facture :number - :organisation',
        'intro_before_number' => 'Veuillez trouver ci-joint votre facture de loyer',
        'intro_for' => 'pour',
        'invoice_amount' => 'Montant de la facture',
        'amount_paid' => 'Montant payé',
        'pdf_attached' => 'La facture complète est jointe au format PDF.',
    ],

    'receipt' => [
        'title' => 'Reçu de paiement',
        'subject' => 'Reçu de paiement :number - :organisation',
        'confirm_before_property' => 'Nous confirmons la réception de votre paiement pour',
        'amount_received' => 'Montant reçu',
        'receipt' => 'Reçu',
        'payment_date' => 'Date de paiement',
        'payment_method' => 'Mode de paiement',
        'reference' => 'Référence',
        'pdf_attached' => 'Votre reçu officiel est joint au format PDF.',
    ],

    'reminder' => [
        'title' => 'Rappel de loyer',
        'subject' => 'Rappel de loyer - Facture :number - :organisation',
        'intro_before_number' => 'Ceci est un rappel concernant la facture de loyer',
        'intro_for' => 'pour',
        'overdue' => 'Selon nos registres, cette facture est actuellement en retard de paiement.',
        'pay_by_due_date' => 'Veuillez effectuer le paiement au plus tard à la date d’échéance.',
        'invoice_attached' => 'Une copie de la facture est jointe pour référence.',
    ],

    'rent_increment' => [
        'title' => 'Avis d’augmentation de loyer',
        'subject' => 'Avis d’augmentation de loyer - :organisation',
        'intro_before_property' => 'Nous vous informons officiellement que le loyer mensuel applicable à votre location',
        'at' => 'à',
        'intro_before_date' => 'sera modifié à compter du',
        'current_rent' => 'Loyer mensuel actuel',
        'increment' => 'Augmentation du loyer',
        'new_rent' => 'Nouveau loyer mensuel',
        'effective_date' => 'Date d’effet',
        'unchanged_until' => 'Votre loyer actuel restera inchangé jusqu’à la date d’effet indiquée ci-dessus.',
        'contact_before' => 'Veuillez contacter',
        'contact_after' => 'pour toute demande de clarification concernant cet avis.',
    ],

    'owner_expense_bill' => [
        'title' => 'Facture de dépenses du propriétaire',
        'subject' => 'Facture de dépenses du propriétaire :number - :organisation',
        'intro' => 'Veuillez trouver ci-joint la facture détaillée des dépenses imputées à votre compte de propriétaire.',
        'bill' => 'Facture',
        'bill_date' => 'Date de facturation',
        'line_count' => 'Lignes de dépenses',
        'total_billed' => 'Total facturé',
        'pdf_attached' => 'La facture détaillée complète est jointe au format PDF.',
        'sent' => 'L’e-mail de la facture de dépenses du propriétaire a été envoyé avec succès.',
        'owner_email_missing' => 'Le propriétaire ne possède pas d’adresse e-mail.',
    ],

    'payment_methods' => [
        'cash' => 'Espèces',
        'bank_transfer' => 'Virement bancaire',
        'momo' => 'Paiement mobile',
        'mobile_money' => 'Paiement mobile',
        'cheque' => 'Chèque',
        'check' => 'Chèque',
    ],

    'user_invitation' => [
        'subject' => 'Configurez votre compte :organisation',
        'title' => 'Configurez votre compte',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Vous avez été invité à utiliser :organisation.',
        'action' => 'Définir mon mot de passe',
        'expiry' => 'Ce lien expire dans 24 heures.',
        'ignore' => 'Si vous n’attendiez pas cette invitation, vous pouvez ignorer cet e-mail.',
    ],

    'password_reset' => [
        'subject' => 'Réinitialisez votre mot de passe :organisation',
        'title' => 'Réinitialisez votre mot de passe',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Une réinitialisation du mot de passe a été demandée pour votre compte :organisation.',
        'action' => 'Réinitialiser mon mot de passe',
        'expiry' => 'Ce lien expire dans 24 heures.',
        'ignore' => 'Si vous n’avez pas demandé cette réinitialisation, vous pouvez ignorer cet e-mail.',
    ],
    'transfer_voucher' => [
        'title' => 'Récépissé de transfert',
        'subject' => 'Récépissé de transfert :number - :organisation',
        'intro' => 'Veuillez trouver ci-joint le récépissé relatif à un transfert entre les fonds que nous détenons pour votre compte.',
        'amount_moved' => 'Montant transféré',
        'voucher' => 'Récépissé',
        'transfer_date' => 'Date du transfert',
        'from_fund' => 'De',
        'to_fund' => 'Vers',
        'reason' => 'Motif',
        'fund_rent_reserve' => 'Réserve de loyer',
        'fund_consumable_advance' => 'Avance consommable',
        'fund_security_deposit' => 'Caution',
        'pdf_attached' => 'Votre récépissé de transfert officiel est joint au format PDF.',
    ],

    'owner_reserve_transfer' => [
        'title' => 'Transfert entre comptes propriétaire',
        'subject' => 'Bon de transfert entre comptes :number - :organisation',
        'intro' => 'Veuillez trouver ci-joint le bon relatif à un transfert entre vos comptes tenus chez nous.',
        'amount_moved' => 'Montant transféré',
        'voucher' => 'Bon',
        'transfer_date' => 'Date du transfert',
        'from_account' => 'De',
        'to_account' => 'Vers',
        'payout_account' => 'Compte de retrait (loyers)',
        'deposit_account' => 'Compte de dépôt / dépenses',
        'reason' => 'Motif',
        'pdf_attached' => 'Votre bon de transfert officiel est joint au format PDF.',
    ],

    'tenant_fund_expense' => [
        'title' => 'Bon de dépense locataire',
        'subject' => 'Bon de dépense :number - :organisation',
        'intro' => 'Veuillez trouver ci-joint le bon relatif à une dépense réglée à partir des fonds que nous détenons pour votre compte.',
        'amount_moved' => 'Montant',
        'voucher' => 'Bon',
        'transfer_date' => 'Date',
        'source_fund' => 'Fonds source',
        'description' => 'Description',
        'fund_rent_reserve' => 'Réserve de loyer',
        'fund_consumable_advance' => 'Avance consommable',
        'fund_security_deposit' => 'Caution',
        'pdf_attached' => 'Votre bon de dépense officiel est joint au format PDF.',
    ],

    /*
    |----------------------------------------------------------------------
    | V1.0.10 shared email layout
    |----------------------------------------------------------------------
    */
    'layout' => [
        'on_behalf_of' => 'Envoyé pour le compte de :organisation',
        'sent_by_product' => 'Ce message a été envoyé par Patrimoine 365.',
        'tagline' => 'La gestion locative, sans le drame.',
        'questions' => 'Des questions ?',
    ],

    'email_verification' => [
        'subject' => 'Vérifiez votre adresse e-mail — Patrimoine 365',
        'title' => 'Vérifiez votre adresse e-mail',
        'preheader' => 'Un clic et votre organisation Patrimoine 365 est prête.',
        'heading' => 'Vérifiez votre adresse e-mail',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Bienvenue sur Patrimoine 365 ! Votre organisation « :organisation » a été créée. Confirmez votre adresse e-mail pour activer votre compte et vous connecter.',
        'action' => 'Vérifier l\'adresse e-mail',
        'link_fallback' => 'Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :',
        'expiry' => 'Ce lien est valable pendant 48 heures.',
        'next_steps' => 'Une fois votre adresse vérifiée, vous pourrez vous connecter et commencer à enregistrer vos immeubles, vos locataires et vos baux. Chaque connexion est protégée par un code à six chiffres envoyé à cette adresse.',
        'ignore' => 'Si vous n\'avez pas créé ce compte, vous pouvez ignorer cet e-mail en toute sécurité — rien ne sera activé sans cette vérification.',
        'sent_to' => 'Ce message a été envoyé à :email car cette adresse a été utilisée pour créer une organisation sur patrimoine365.com.',
    ],

    'mfa_code' => [
        'subject' => ':code est votre code de connexion Patrimoine 365',
        'title' => 'Votre code de connexion',
        'preheader' => 'Utilisez ce code pour terminer votre connexion.',
        'heading' => 'Votre code de connexion',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Utilisez le code ci-dessous pour terminer votre connexion à Patrimoine 365 :',
        'expiry' => 'Ce code expire dans :minutes minutes et ne peut être utilisé qu\'une seule fois.',
        'ignore' => 'Si vous n\'avez pas tenté de vous connecter, nous vous recommandons de changer votre mot de passe immédiatement.',
    ],

    /*
     * V1.0.48 : le changement d'adresse e-mail en trois étapes. La
     * boîte actuelle autorise le changement ; la nouvelle prouve
     * qu'elle existe ; l'ancienne est prévenue quand c'est terminé.
     */
    'email_change_current' => [
        'subject' => ':code est votre code de changement d\'adresse Patrimoine 365',
        'title' => 'Confirmez votre changement d\'adresse',
        'preheader' => 'Un changement de votre adresse de connexion a été demandé.',
        'heading' => 'Confirmez votre changement d\'adresse',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Une demande a été faite pour remplacer votre adresse de connexion Patrimoine 365 par :proposed. Utilisez le code ci-dessous pour confirmer que vous autorisez ce changement.',
        'unchanged' => 'Votre adresse actuelle reste active tant que toutes les étapes du changement ne sont pas terminées.',
        'expiry' => 'Ce code expire dans :minutes minutes et ne peut être utilisé qu\'une seule fois.',
        'not_you' => 'Si vous n\'avez pas demandé ce changement, ne partagez ce code avec personne. Changez votre mot de passe immédiatement et écrivez à :support.',
    ],

    'email_change_proposed' => [
        'subject' => ':code est votre code de confirmation d\'adresse Patrimoine 365',
        'title' => 'Confirmez votre nouvelle adresse e-mail',
        'preheader' => 'Confirmez cette boîte pour terminer le changement d\'adresse.',
        'heading' => 'Confirmez votre nouvelle adresse e-mail',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Cette boîte, :proposed, a été proposée comme nouvelle adresse de connexion d\'un compte Patrimoine 365. Saisissez le code ci-dessous pour confirmer que vous recevez bien les e-mails ici et terminer le changement.',
        'expiry' => 'Ce code expire dans :minutes minutes et ne peut être utilisé qu\'une seule fois.',
        'ignore' => 'Si vous n\'attendiez pas cet e-mail, vous pouvez l\'ignorer en toute sécurité — rien ne change sans ce code.',
    ],

    'email_change_completed' => [
        'subject' => 'Votre adresse de connexion Patrimoine 365 a changé',
        'title' => 'Votre adresse de connexion a changé',
        'preheader' => 'L\'adresse e-mail de votre compte a été mise à jour.',
        'heading' => 'Votre adresse de connexion a changé',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'L\'adresse de connexion de votre compte Patrimoine 365 est passée de :previous à :new.',
        'sign_in' => 'Toutes les sessions ouvertes ont été déconnectées. Utilisez la nouvelle adresse lors de votre prochaine connexion ; les codes et les réinitialisations de mot de passe y sont désormais envoyés.',
        'not_you' => 'Si vous n\'êtes pas à l\'origine de ce changement, écrivez immédiatement à :support depuis cette adresse afin que nous puissions sécuriser le compte.',
    ],

    'plans' => [
        'free' => 'Gratuit',
        'standard' => 'Standard',
        'professional' => 'Professionnel',
    ],

    'license_issued' => [
        'subject' => 'Votre forfait Patrimoine 365 : :plan',
        'title' => 'Votre forfait a été mis à jour',
        'preheader' => 'Votre organisation est désormais sur le forfait :plan.',
        'heading' => 'Votre forfait a été mis à jour',
        'greeting' => 'Bonjour :name,',
        'introduction' => 'Une nouvelle licence a été activée pour « :organisation ». Voici ce qui s\'applique désormais :',
        'plan' => 'Forfait',
        'starts_on' => 'Début',
        'expires_on' => 'Valable jusqu\'au',
        'no_expiry' => 'Sans expiration',
        'questions' => 'Des questions sur votre forfait ou votre facture ?',
    ],

    'plan_expiry' => [
        'subject' => 'Votre forfait Patrimoine 365 se termine dans :days jour(s)',
        'title' => 'Votre forfait se termine bientôt',
        'heading' => 'Votre forfait se termine dans :days jour(s)',
        'greeting' => 'Bonjour :name,',
        'introduction_trial' => 'L\'essai Professionnel de « :organisation » se termine le :date.',
        'introduction_license' => 'La licence :plan de « :organisation » se termine le :date.',
        'what_changes' => 'Ensuite, votre organisation continue sur le forfait Gratuit : toutes vos données restent exactement en l\'état, mais les rapports, exports et rappels automatiques sont suspendus, et les limites du forfait Gratuit s\'appliquent à la création de nouveaux enregistrements.',
        'renew' => 'Pour renouveler ou changer de forfait, contactez',
    ],
];

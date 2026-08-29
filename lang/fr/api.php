<?php

return [
    'documents' => [
        'not_signable' => 'Ce document ne peut pas être ouvert via un lien signé.',
        'link_invalid' => 'Ce lien de document est invalide ou a expiré. Veuillez ouvrir le document à nouveau.',
    ],

    'auth' => [
        'password_confirmation_failed' => 'Le mot de passe est incorrect.',
        'account_disabled' => 'Ce compte a été désactivé.',
        'setup_required' => 'Terminez la configuration de votre compte avant de vous connecter.',
        'invalid_credentials' => 'Les identifiants fournis sont incorrects.',
        'logged_out' => 'Déconnexion effectuée avec succès.',

        'unauthenticated' => 'Vous devez être authentifié pour effectuer cette action.',
        'forbidden' => 'Vous n’êtes pas autorisé à effectuer cette action.',

        'verification_required' => 'Vérifiez votre adresse e-mail avant de vous connecter. Consultez votre boîte de réception pour trouver le lien de vérification.',
        'organisation_suspended' => 'Cette organisation est actuellement suspendue. Contactez support@patrimoine365.com.',
        'mfa_challenge_expired' => 'Cette tentative de connexion a expiré. Reconnectez-vous pour recevoir un nouveau code.',
        'mfa_code_invalid' => 'Le code de vérification est incorrect.',
        'mfa_code_resent' => 'Un nouveau code de vérification a été envoyé à votre adresse e-mail.',
    ],

    'registration' => [
        'created' => 'Votre organisation a été créée. Consultez votre e-mail pour vérifier votre adresse.',
        'verified' => 'Votre adresse e-mail a été vérifiée. Vous pouvez maintenant vous connecter.',
        'verification_invalid' => 'Ce lien de vérification est invalide ou a expiré. Demandez-en un nouveau depuis la page de connexion.',
        'verification_sent' => 'Si cette adresse nécessite une vérification, un nouveau lien a été envoyé.',
        'platform_domain_blocked' => 'Ce domaine e-mail est réservé. Contactez support@patrimoine365.com.',
    ],

    'profile' => [
        'photo_invalid' => 'Ce fichier n\'a pas pu être lu comme une image. Utilisez un JPG, PNG, WEBP ou GIF.',
        'photo_updated' => 'Photo de profil mise à jour.',
        'photo_removed' => 'Photo de profil supprimée.',
    ],

    'license' => [
        'user_limit_reached' => 'La limite d’utilisateurs de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des utilisateurs.',
        'lease_limit_reached' => 'La limite de baux actifs de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des baux actifs.',
        'party_limit_reached' => 'La limite de tiers de votre forfait est atteinte. Passez à un forfait supérieur pour ajouter des tiers.',
        'feature_unavailable' => 'Cette fonctionnalité n’est pas incluse dans votre forfait actuel.',
        'email_cap_reached' => 'Le quota mensuel d’e-mails de votre forfait est épuisé.',
    ],

    'user_management' => [
        'platform_domain_required' => 'Les comptes du personnel de la plateforme doivent utiliser une adresse @patrimoine365.com.',
        'platform_domain_reserved' => 'Ce domaine e-mail est réservé au personnel de la plateforme.',
        'cannot_change_own_role' => 'Le rôle d’un administrateur doit être modifié par un autre administrateur.',
        'cannot_disable_self' => 'Un compte ne peut pas se désactiver lui-même. Un autre administrateur peut le faire.',
        'cannot_delete_self' => 'Un compte ne peut pas se supprimer lui-même. Un autre administrateur peut le faire.',
        'last_active_administrator' => 'Cette action ne peut pas être effectuée, car Patrimoine doit conserver au moins un administrateur actif.',
        'created' => 'Utilisateur créé avec succès.',
        'updated' => 'Utilisateur mis à jour avec succès.',
        'deleted' => 'Utilisateur supprimé avec succès.',
    ],

    'managing_organisation' => [
        'not_configured' => 'L’organisation gestionnaire n’a pas encore été configurée.',
        'cannot_remove_role' => 'Votre propre société doit rester l’organisation gestionnaire. Désignez d’abord une autre partie comme organisation gestionnaire.',
        'cannot_delete' => 'L’organisation gestionnaire configurée ne peut pas être supprimée.',
    ],

    'email' => [
        'invoice_sent' => 'L’e-mail de la facture a été envoyé avec succès.',
        'receipt_sent' => 'L’e-mail du reçu a été envoyé avec succès.',
        'transfer_voucher_sent' => 'L’e-mail du récépissé de transfert a été envoyé avec succès.',
        'owner_reserve_transfer_sent' => 'L’e-mail du bon de transfert entre comptes a été envoyé avec succès.',
        'tenant_expense_voucher_sent' => 'L’e-mail du bon de dépense a été envoyé avec succès.',
    ],

    'validation' => [
        'building_ownership_total' => 'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',

        'wizard_owners_required' => 'Ce bien n’a encore aucun propriétaire enregistré ; l’assistant en demande au moins un.',

        'wizard_unit_building' => 'Le lot sélectionné n’appartient pas au bien sélectionné.',

        'building_required_for_unit' => 'Un immeuble doit être sélectionné lorsqu’une unité est sélectionnée.',

        'unit_not_in_building' => 'L’unité sélectionnée n’appartient pas à l’immeuble sélectionné.',

        'payment_draft_lease' => 'Aucun paiement ne peut être enregistré sur un bail en brouillon.',

        'tenant_role_required' => 'La partie sélectionnée doit avoir le rôle de locataire.',

        'agent_role_required' => 'La partie sélectionnée doit avoir le rôle d’agent.',

        'notice_date_required' => 'La date de préavis de résiliation est obligatoire lorsque le bail est en préavis.',

        'management_fee_none_zero' => 'Les frais de gestion doivent être nuls lorsque le type de frais de gestion est aucun.',

        'management_fee_percentage_max' => 'Le pourcentage des frais de gestion ne peut pas dépasser 100 %.',

        'agent_required_for_commission' => 'Un agent est obligatoire lorsqu’une commission d’agent est configurée.',

        'unit_active_lease' => 'Cette unité possède déjà un bail actif.',

        'rent_reserve_exceeds_advance' => 'La réserve de loyer ne peut pas dépasser le montant total de l’avance.',

        'rent_increment_none_zero' => 'La valeur de l’augmentation de loyer doit être nulle lorsqu’aucune augmentation n’est configurée.',

        'rent_increment_none_date' => 'La prochaine date d’augmentation de loyer doit être vide lorsqu’aucune augmentation n’est configurée.',

        'rent_increment_value_required' => 'Saisissez une valeur d’augmentation de loyer lorsqu’une augmentation est configurée.',

        'rent_increment_date_required' => 'La prochaine date d’augmentation de loyer est obligatoire lorsqu’une augmentation est configurée.',

        'rent_increment_percentage_max' => 'Le pourcentage d’augmentation du loyer ne peut pas dépasser 100 %.',

        'advance_received_positive' => 'L’avance doit être supérieure à zéro lorsque l’option indiquant qu’elle a déjà été reçue est sélectionnée.',

        'advance_received_before_lease' => 'La date de réception de l’avance ne peut pas être antérieure à la date de début du bail.',

        'telephone_country_required' => 'Choisissez le pays auquel ce numéro de téléphone appartient.',

        'telephone_number_invalid' => 'Ce numéro de téléphone semble incorrect. Vérifiez les chiffres et le pays.',
    ],

    'user_invitation' => [
        'accepted' => 'La configuration de votre compte est terminée. Vous pouvez maintenant vous connecter.',
        'resent' => 'Une nouvelle invitation a été envoyée.',
        'inactive_user' => 'Une invitation ne peut pas être envoyée à un utilisateur inactif.',
        'invalid' => 'Ce lien d’invitation est invalide ou a expiré.',
    ],

    'password' => [
        'reset_requested' => 'Si le compte est admissible, un lien de réinitialisation du mot de passe a été envoyé.',
        'administrator_reset_requested' => 'La procédure de réinitialisation du mot de passe a été lancée.',
        'reset_complete' => 'Votre mot de passe a été réinitialisé avec succès.',
        'changed' => 'Votre mot de passe a été modifié avec succès.',
        'invalid_reset' => 'Ce lien de réinitialisation du mot de passe est invalide ou a expiré.',
        'current_incorrect' => 'Le mot de passe actuel est incorrect.',
        'account_disabled' => 'Ce compte a été désactivé.',
    ],

    'deletion' => [
        'lease_confirmation_invalid' => 'Saisissez exactement DELETE pour confirmer la suppression du bail.',
        'lease_cannot_delete' => 'Ce bail ne peut pas être supprimé en toute sécurité.',
        'party_managing_organisation' => 'L’organisation gestionnaire configurée ne peut pas être supprimée. Modifiez plutôt la configuration de l’organisation gestionnaire.',
        'party_referenced' => 'Cette partie ne peut pas être supprimée car elle est référencée par un bail, une propriété, un mandat d’agence ou un historique financier. Conservez-la afin que les données historiques restent compréhensibles.',
        'building_has_units' => 'Cet immeuble ne peut pas être supprimé tant qu’il contient des unités. Supprimez d’abord uniquement les unités sans référence ; les unités ayant un bail ou un historique financier doivent être conservées.',
        'building_referenced' => 'Cet immeuble ne peut pas être supprimé car des données financières ou historiques y font référence. Conservez-le pour préserver la traçabilité.',
        'unit_referenced' => 'Cette unité ne peut pas être supprimée car un bail ou un historique financier y fait référence. Conservez l’unité et résiliez plutôt le bail lorsque cela s’applique.',
        'lease_not_draft' => 'Seul un bail en brouillon et inutilisé peut être supprimé. Un bail actif, ou en préavis, prend fin par sa résiliation — et un bail résilié est conservé comme historique.',
        'lease_referenced' => 'Ce bail brouillon ne peut pas être supprimé car des données contractuelles ou financières y font référence. Conservez le bail.',
    ],

];

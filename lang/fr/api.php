<?php

return [
    'auth' => [
        'invalid_credentials' =>
            'Les identifiants fournis sont incorrects.',
        'logged_out' =>
            'Déconnexion effectuée avec succès.',

        'unauthenticated' => 'Vous devez être authentifié pour effectuer cette action.',
        'forbidden' => 'Vous n’êtes pas autorisé à effectuer cette action.',
    ],

    'user_management' => [
        'cannot_change_own_role' =>
            'Vous ne pouvez pas modifier votre propre rôle d’administrateur.',
        'cannot_disable_self' =>
            'Vous ne pouvez pas désactiver votre propre compte.',
        'cannot_delete_self' =>
            'Vous ne pouvez pas supprimer votre propre compte.',
        'last_active_administrator' =>
            'Cette action ne peut pas être effectuée, car Patrimoine doit conserver au moins un administrateur actif.',
    ],

    'managing_organisation' => [
        'not_configured' =>
            'L’organisation gestionnaire n’a pas encore été configurée.',
        'cannot_remove_role' =>
            'Le rôle managing_organisation ne peut pas être retiré à l’organisation gestionnaire configurée.',
        'cannot_delete' =>
            'L’organisation gestionnaire configurée ne peut pas être supprimée.',
    ],

    'email' => [
        'invoice_sent' =>
            'L’e-mail de la facture a été envoyé avec succès.',
        'receipt_sent' =>
            'L’e-mail du reçu a été envoyé avec succès.',
    ],

    'validation' => [
        'building_ownership_total' =>
            'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',

        'building_required_for_unit' =>
            'Un immeuble doit être sélectionné lorsqu’une unité est sélectionnée.',

        'unit_not_in_building' =>
            'L’unité sélectionnée n’appartient pas à l’immeuble sélectionné.',

        'payment_draft_lease' =>
            'Aucun paiement ne peut être enregistré sur un bail en brouillon.',

        'tenant_role_required' =>
            'La partie sélectionnée doit avoir le rôle de locataire.',

        'agent_role_required' =>
            'La partie sélectionnée doit avoir le rôle d’agent.',

        'notice_date_required' =>
            'La date de préavis de résiliation est obligatoire lorsque le bail est en préavis.',

        'management_fee_none_zero' =>
            'Les frais de gestion doivent être nuls lorsque le type de frais de gestion est aucun.',

        'management_fee_percentage_max' =>
            'Le pourcentage des frais de gestion ne peut pas dépasser 100 %.',

        'agent_required_for_commission' =>
            'Un agent est obligatoire lorsqu’une commission d’agent est configurée.',

        'unit_active_lease' =>
            'Cette unité possède déjà un bail actif.',

        'rent_reserve_exceeds_advance' =>
            'La réserve de loyer ne peut pas dépasser le montant total de l’avance.',

        'rent_increment_none_zero' =>
            'La valeur de l’augmentation de loyer doit être nulle lorsqu’aucune augmentation n’est configurée.',

        'rent_increment_none_date' =>
            'La prochaine date d’augmentation de loyer doit être vide lorsqu’aucune augmentation n’est configurée.',

        'rent_increment_value_required' =>
            'Saisissez une valeur d’augmentation de loyer lorsqu’une augmentation est configurée.',

        'rent_increment_date_required' =>
            'La prochaine date d’augmentation de loyer est obligatoire lorsqu’une augmentation est configurée.',

        'rent_increment_percentage_max' =>
            'Le pourcentage d’augmentation du loyer ne peut pas dépasser 100 %.',

        'advance_received_positive' =>
            'L’avance doit être supérieure à zéro lorsque l’option indiquant qu’elle a déjà été reçue est sélectionnée.',

        'advance_received_before_lease' =>
            'La date de réception de l’avance ne peut pas être antérieure à la date de début du bail.',
    ],
];

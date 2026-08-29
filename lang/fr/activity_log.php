<?php

return [
    'export_title' => 'Journal d’activité',
    'no_export_records' => 'Aucun enregistrement du journal d’activité ne correspond aux filtres sélectionnés.',
    'export_read_only_notice' => 'Cet export constitue un historique en lecture seule des actions humaines significatives enregistrées par Patrimoine.',
    /*
    |--------------------------------------------------------------------------
    | Présentation des exports du journal d’activité
    |--------------------------------------------------------------------------
    |
    | Ces traductions sont utilisées par les exports générés côté serveur.
    |
    | Les identifiants internes d’action, les types d’enregistrement, les
    | rôles et les noms de champs restent des valeurs stables de l’application.
    | Seule leur présentation destinée aux utilisateurs est traduite.
    |
    */

    'title' => 'Journal d’activité',
    'subtitle' => 'Piste d’audit des actions humaines',
    'description' => 'Historique immuable des actions humaines significatives enregistrées par Patrimoine.',

    'property_management' => 'Gestion immobilière',

    'generated_at' => 'Généré le',
    'generated_by' => 'Généré par',
    'generated_by_patrimoine_for' => 'Généré par Patrimoine pour',
    'this_installation' => 'cette installation',

    'filters' => 'Filtres',
    'all_records' => 'Tous les enregistrements du journal d’activité',
    'no_filters' => 'Aucun filtre appliqué',
    'from' => 'Du',
    'to' => 'Au',
    'user' => 'Utilisateur',
    'role' => 'Rôle',
    'action' => 'Action',
    'record_type' => 'Type d’enregistrement',
    'search' => 'Recherche',

    'no_records' => 'Aucun enregistrement du journal d’activité ne correspond aux critères sélectionnés.',

    /*
    |--------------------------------------------------------------------------
    | Colonnes d’export
    |--------------------------------------------------------------------------
    */

    'columns' => [
        'id' => 'ID de l’événement',
        'timestamp' => 'Horodatage',
        'actor_name' => 'Auteur',
        'actor_email' => 'E-mail de l’auteur',
        'actor_role' => 'Rôle',
        'action' => 'Action',
        'entity_type' => 'Type d’enregistrement',
        'entity_id' => 'ID de l’enregistrement',
        'entity_label' => 'Enregistrement',
        'ip_address' => 'Adresse IP',
        'before_values' => 'Valeurs avant',
        'after_values' => 'Valeurs après',
        'snapshot' => 'Instantané',
        'metadata' => 'Métadonnées',
    ],

    /*
    |--------------------------------------------------------------------------
    | Détails / contexte historique structuré
    |--------------------------------------------------------------------------
    */

    'event' => 'Événement',
    'actor' => 'Auteur',
    'entity' => 'Enregistrement',
    'before_values' => 'Valeurs avant',
    'after_values' => 'Valeurs après',
    'snapshot' => 'Instantané',
    'metadata' => 'Métadonnées',
    'not_available' => 'Non disponible',
    'unknown_actor' => 'Auteur inconnu',
    'yes' => 'Oui',
    'no' => 'Non',

    /*
    |--------------------------------------------------------------------------
    | Rôles
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'administrator' => 'Administrateur',
        'property_manager' => 'Gestionnaire immobilier',
        'viewer' => 'Consultation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions du journal
    |--------------------------------------------------------------------------
    */

    'actions' => [

        /* ---- V1.0.35: actions that were written but never named ---- */
        'adjustment_voucher.downloaded' => 'Bon d’ajustement téléchargé',
        'auth.email_verified' => 'Adresse e-mail vérifiée',
        'auth.invitation_accepted' => 'Invitation acceptée',
        'auth.password_changed' => 'Mot de passe modifié',
        'auth.password_reset' => 'Mot de passe réinitialisé',
        'expense_invoice.created' => 'Facture de dépense locataire créée',
        'invoice_account_payment.cancelled' => 'Paiement de facture depuis un compte annulé',
        'invoice_account_payment.recorded' => 'Facture réglée depuis un compte',
        'invoice_payment_receipt.downloaded' => 'Reçu de paiement de facture téléchargé',
        'lease.extended' => 'Bail prolongé',
        'lease.rent_increment_cancelled' => 'Augmentation de loyer annulée',
        'lease.rent_increment_scheduled' => 'Augmentation de loyer programmée',
        'lease.termination_cancelled' => 'Résiliation de bail annulée',
        'lease.termination_completed' => 'Résiliation de bail finalisée',
        'lease.termination_initiated' => 'Résiliation de bail engagée',
        'lease.termination_notice_downloaded' => 'Préavis de résiliation téléchargé',
        'organisation.closed_by_customer' => 'Compte fermé par le client',
        'organisation.data_exported' => 'Données de l’organisation exportées',
        'organisation.registered' => 'Organisation inscrite',
        'owner_expense_bill.downloaded' => 'Facture de dépenses téléchargée',
        'owner_expense_bill.recorded' => 'Facture de dépenses enregistrée',
        'owner_expense_bill.resent' => 'Facture de dépenses renvoyée',
        'owner_expense_bill_payment.cancelled' => 'Paiement de facture de dépenses annulé',
        'owner_expense_bill_payment.recorded' => 'Facture de dépenses réglée',
        'owner_expense_bill_payment_receipt.downloaded' => 'Reçu de paiement de facture de dépenses téléchargé',
        'owner_payout_receipt.downloaded' => 'Reçu de paiement au propriétaire téléchargé',
        'owner_reserve_transfer.recorded' => 'Transfert entre comptes du propriétaire',
        'owner_reserve_transfer_voucher.downloaded' => 'Bon de transfert propriétaire téléchargé',
        'owner_reserve_transfer_voucher.resent' => 'Bon de transfert propriétaire renvoyé',
        'party.data_exported' => 'Données d’un tiers exportées',
        'party.erased' => 'Tiers effacé',
        'registry.exported' => 'Registre exporté',
        'registry.imported' => 'Registre importé',
        'security_deposit.deduction_added' => 'Retenue sur dépôt de garantie ajoutée',
        'tenant_adjustment.recorded' => 'Solde locataire ajusté',
        'tenant_expense.recorded' => 'Dépense locataire enregistrée',
        'tenant_expense_voucher.downloaded' => 'Bon de dépense locataire téléchargé',
        'tenant_expense_voucher.resent' => 'Bon de dépense locataire renvoyé',
        'tenant_fund.deposit' => 'Dépôt sur un fonds locataire',
        'tenant_fund.transfer_recorded' => 'Transfert entre comptes locataire',
        'tenant_fund_transfer_voucher.downloaded' => 'Bon de transfert locataire téléchargé',
        'tenant_fund_transfer_voucher.resent' => 'Bon de transfert locataire renvoyé',
        'tenant_withdrawal.recorded' => 'Retrait locataire enregistré',
        'auth.login' => 'Connexion',
        'auth.login_failed' => 'Échec de connexion',
        'auth.logout' => 'Déconnexion',

        'user.created' => 'Utilisateur créé',
        'user.updated' => 'Utilisateur modifié',
        'user.deleted' => 'Utilisateur supprimé',
        'user.invitation_resent' => 'Invitation utilisateur renvoyée',
        'user.password_reset_requested' => 'Réinitialisation du mot de passe demandée',

        'user.invitation_accepted' => 'Invitation utilisateur acceptée',

        'user.password_reset' => 'Réinitialisation du mot de passe effectuée',

        'user.password_changed' => 'Mot de passe modifié',

        'party.created' => 'Partie créée',
        'party.updated' => 'Partie modifiée',
        'party.deleted' => 'Partie supprimée',

        'building.created' => 'Immeuble créé',
        'building.updated' => 'Immeuble modifié',
        'building.deleted' => 'Immeuble supprimé',

        'unit.created' => 'Unité créée',
        'unit.updated' => 'Unité modifiée',
        'unit.deleted' => 'Unité supprimée',

        'lease.created' => 'Bail créé',
        'lease.updated' => 'Bail modifié',
        'lease.deleted' => 'Bail supprimé',

        'managing_organisation.created' => 'Organisation gestionnaire configurée',

        'managing_organisation.updated' => 'Organisation gestionnaire modifiée',

        'payment.recorded' => 'Paiement enregistré',

        'tenant_fund.classified' => 'Fonds du locataire classifiés',

        'rent_reserve.consumed' => 'Réserve de loyer consommée',

        'consumable_advance.consumed' => 'Avance consommable utilisée',

        'security_deposit.deduction_recorded' => 'Déduction du dépôt de garantie enregistrée',

        'security_deposit.settled' => 'Dépôt de garantie réglé',

        'owner_expense.recorded' => 'Dépense propriétaire enregistrée',

        'owner_deposit.recorded' => 'Dépôt propriétaire enregistré',

        'owner_adjustment.recorded' => 'Ajustement propriétaire enregistré',

        'owner_payout.recorded' => 'Versement au propriétaire enregistré',

        'invoice.downloaded' => 'Facture téléchargée',

        'receipt.downloaded' => 'Reçu téléchargé',

        'owner_deposit_receipt.downloaded' => 'Reçu de dépôt propriétaire téléchargé',

        'security_deposit_voucher.downloaded' => 'Bon de règlement du dépôt de garantie téléchargé',

        'invoice.resent' => 'Facture renvoyée',

        'email.suppressed' => 'E-mail non envoyé',

        'receipt.resent' => 'Reçu renvoyé',

        'report.exported' => 'Rapport exporté',

        'activity_log.exported' => 'Journal d’activité exporté',
    ],

    /*
    |--------------------------------------------------------------------------
    | Types d’enregistrement
    |--------------------------------------------------------------------------
    */

    'entities' => [
        'user' => 'Utilisateur',
        'party' => 'Partie',
        'building' => 'Immeuble',
        'unit' => 'Unité',
        'lease' => 'Bail',
        'payment' => 'Paiement',
        'invoice' => 'Facture',
        'receipt' => 'Reçu',
        'tenant_fund' => 'Fonds du locataire',
        'rent_reserve' => 'Réserve de loyer',
        'consumable_advance' => 'Avance consommable',
        'security_deposit' => 'Dépôt de garantie',
        'owner_expense' => 'Dépense propriétaire',
        'owner_account' => 'Compte propriétaire',
        'owner_transaction' => 'Transaction propriétaire',
        'owner_payout' => 'Versement au propriétaire',
        'managing_organisation' => 'Organisation gestionnaire',
        'report' => 'Rapport',
        'activity_log' => 'Journal d’activité',
    ],

    /*
    |--------------------------------------------------------------------------
    | Formats / métadonnées d’export
    |--------------------------------------------------------------------------
    */

    'formats' => [
        'pdf' => 'PDF',
        'csv' => 'CSV',
    ],

    'metadata_labels' => [
        'format' => 'Format',
        'report_type' => 'Type de rapport',
        'document_type' => 'Type de document',
        'delivery' => 'Mode d’envoi',
        'reference' => 'Référence',
        'invitation_sent' => 'Invitation envoyée',
        'source' => 'Source',
    ],
];

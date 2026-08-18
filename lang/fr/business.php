<?php

return [
    'fund_accounts' => [
        'not_rent_reserve' => 'Le compte de fonds du locataire sélectionné n’est pas un compte de réserve de loyer.',
        'not_consumable_advance' => 'Le compte de fonds du locataire sélectionné n’est pas un compte d’avance consommable.',
    ],

    'rent_reserve' => [
        'wrong_account_type' => 'Seul un compte de réserve de loyer peut être utilisé par ce service.',
        'account_closed' => 'Le compte de réserve de loyer est fermé.',
        'before_notice' => 'La réserve de loyer ne peut pas être utilisée avant le préavis de résiliation.',
        'wrong_invoice_lease' => 'La facture n’appartient pas au bail associé à cette réserve de loyer.',
        'rent_only' => 'La réserve de loyer ne peut régler que des factures de loyer.',
        'amount_positive' => 'Le montant utilisé depuis la réserve de loyer doit être supérieur à zéro.',
        'insufficient_balance' => 'Le solde de la réserve de loyer est insuffisant.',
        'exceeds_invoice' => 'Le montant utilisé depuis la réserve de loyer dépasse le solde restant dû de la facture.',
        'no_ownership' => 'Aucune répartition de propriété n’est définie pour l’immeuble.',
        'ownership_total' => 'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',
    ],

    'consumable_advance' => [
        'wrong_account_type' => 'Seul un compte d’avance consommable peut être utilisé par ce service.',
        'account_closed' => 'Le compte d’avance consommable est fermé.',
        'draft_lease' => 'L’avance consommable ne peut pas être utilisée pour un bail en brouillon.',
        'wrong_invoice_lease' => 'La facture n’appartient pas au bail associé à cette avance consommable.',
        'rent_only' => 'L’avance consommable ne peut régler que des factures de loyer.',
        'amount_positive' => 'Le montant de l’avance consommable doit être supérieur à zéro.',
        'insufficient_balance' => 'Le solde de l’avance consommable est insuffisant.',
        'exceeds_invoice' => 'L’avance consommable dépasse le solde restant dû de la facture.',
        'no_ownership' => 'Aucune répartition de propriété n’est définie pour l’immeuble.',
        'ownership_total' => 'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',
    ],

    'owner' => [
        'deposit_positive' => 'Le montant du dépôt du propriétaire doit être supérieur à zéro.',
        'deposit_payment_method' => 'Le mode de paiement du dépôt du propriétaire n’est pas pris en charge.',
        'deposit_purpose' => 'L’objet du dépôt du propriétaire n’est pas pris en charge.',
        'cash_collector_required' => 'Un encaisseur est obligatoire pour les dépôts de propriétaire en espèces.',
        'adjustment_direction' => 'Le sens de l’ajustement du propriétaire doit être crédit ou débit.',
        'adjustment_positive' => 'Le montant de l’ajustement du propriétaire doit être supérieur à zéro.',
        'adjustment_reason' => 'Le motif de l’ajustement du propriétaire est obligatoire.',
        'no_ownership' => 'Aucune répartition de propriété n’est définie pour l’immeuble.',
        'ownership_total' => 'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.',
        'payout_positive' => 'Le montant du paiement au propriétaire doit être supérieur à zéro.',
        'payout_no_funds' => 'Aucun fonds n’est disponible pour un paiement à ce propriétaire.',
        'payout_exceeds_balance' => 'Le paiement au propriétaire dépasse le solde disponible.',
        'payout_allocation_failed' => 'Le paiement au propriétaire ne peut pas être entièrement affecté aux crédits nets disponibles.',
        'deposit_receipt_only' => 'Seuls les dépôts de propriétaire peuvent générer un reçu de dépôt de propriétaire.',
    ],

    'security_deposit' => [
        'deductions_after_settlement' => 'Les retenues sur le dépôt de garantie ne peuvent plus être modifiées après le règlement final.',
        'deductions_terminated_only' => 'Les retenues sur le dépôt de garantie ne peuvent être enregistrées que pour un bail résilié.',
        'account_missing' => 'Aucun compte de dépôt de garantie n’existe pour ce bail.',
        'already_settled' => 'Le dépôt de garantie a déjà fait l’objet d’un règlement pour ce bail.',
        'account_missing' => 'Aucun compte de dépôt de garantie n’existe pour ce bail.',
        'negative_balance' => 'Le compte de dépôt de garantie présente un solde négatif invalide.',
    ],

    'email' => [
        'tenant_email_missing' => 'Le locataire ne possède pas d’adresse e-mail.',
    ],
];

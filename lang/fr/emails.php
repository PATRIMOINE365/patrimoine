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

    'payment_methods' => [
        'cash' => 'Espèces',
        'bank_transfer' => 'Virement bancaire',
        'momo' => 'MoMo',
        'mobile_money' => 'Mobile Money',
        'cheque' => 'Chèque',
        'check' => 'Chèque',
    ],
];

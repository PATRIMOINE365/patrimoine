<?php

return [
    'title' => 'Journal financier',
    'generated_at' => 'Généré le',
    'filters' => 'Filtres',

    'entry_kinds' => [
        'financial' => 'Financière',
        'reversal' => 'Contre-passation',
        'informational' => 'Informative',
    ],

    'columns' => [
        'journal_number' => 'Numéro de journal',
        'journal_date' => 'Date du journal',
        'posted_at' => 'Horodatage',
        'entry_kind' => 'Type d’écriture',
        'transaction_type' => 'Type de transaction',
        'description' => 'Description',
        'actor' => 'Utilisateur',
        'source_type' => 'Type de source',
        'source_id' => 'ID source',
        'reversal_reference' => 'Référence de contre-passation',
        'account_code' => 'Code du compte',
        'account_name' => 'Nom du compte',
        'account_type' => 'Type de compte',
        'debit' => 'Débit',
        'credit' => 'Crédit',
        'memo' => 'Libellé',
    ],
];

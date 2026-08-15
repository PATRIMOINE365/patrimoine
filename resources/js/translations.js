/*
|--------------------------------------------------------------------------
| Patrimoine Browser Translations
|--------------------------------------------------------------------------
|
| User-facing browser text is translated from stable keys.
|
| English is the compatibility fallback language. A missing key in another
| language therefore falls back to English rather than exposing the key or
| preventing the application from rendering.
|
| Translation belongs to presentation only. Business values, API field
| names and persisted domain values must never be translated here.
|
*/

export const translations = {
    en: {
        'product.property_management':
            'Property Management',

        'login.title':
            'Sign in — Patrimoine',

        'login.hero_title':
            'Your property portfolio, finances and tenants in one place.',

        'login.hero_description':
            'Manage buildings, leases, rent collections, owner funds and financial reporting from a single workspace.',

        'login.product_name':
            'Patrimoine Property Management',

        'login.welcome':
            'Welcome back',

        'login.description':
            'Sign in to access the property management workspace.',

        'login.email':
            'Email address',

        'login.email_placeholder':
            'name@example.com',

        'login.password':
            'Password',

        'login.password_placeholder':
            'Enter your password',

        'login.sign_in':
            'Sign in',

        'login.signing_in':
            'Signing in…',

        'login.unable_to_sign_in':
            'Unable to sign in.',

        'login.secure_access':
            'Secure access to Patrimoine Property Management.',

        'navigation.workspace':
            'Workspace',

        'navigation.dashboard':
            'Dashboard',

        'navigation.properties':
            'Properties',

        'navigation.parties':
            'Parties',

        'navigation.leases':
            'Leases',

        'navigation.payments':
            'Payments',

        'navigation.finance':
            'Finance',

        'navigation.tenants':
            'Tenants',

        'navigation.owners':
            'Owners',

        'navigation.reports':
            'Reports',

        'navigation.settings':
            'Settings',

        'navigation.sign_out':
            'Sign out',

        'user.property_manager':
            'Property Manager',

        'dashboard.title':
            'Dashboard — Patrimoine',

        'dashboard.overview':
            'Overview',

        'dashboard.heading':
            'Dashboard',

        'dashboard.description':
            'Current portfolio and financial position.',

        'dashboard.buildings':
            'Buildings',

        'dashboard.total_units':
            'Total Units',

        'dashboard.occupied_units':
            'Occupied Units',

        'dashboard.vacant_units':
            'Vacant Units',

        'dashboard.rent_due':
            'Rent Due',

        'dashboard.rent_overdue':
            'Rent Overdue',

        'dashboard.collected_this_month':
            'Collected This Month',

        'dashboard.owner_funds_held':
            'Owner Funds Held',

        'dashboard.overdue_rent':
            'Overdue Rent',

        'dashboard.overdue_description':
            'Outstanding obligations requiring attention.',

        'dashboard.upcoming_rent':
            'Upcoming Rent',

        'dashboard.upcoming_description':
            'Rent obligations becoming due soon.',

        'dashboard.loading':
            'Loading…',

        'dashboard.no_records':
            'No records to display.',

        'dashboard.tenant':
            'Tenant',

        'dashboard.due':
            'Due',

        'dashboard.unable_to_load':
            'Unable to load dashboard information.',

        'language.en':
            'English',

        'language.fr':
            'French',

        'settings.title':
            'Settings — Patrimoine',

        'settings.administration':
            'Administration',

        'settings.heading':
            'Settings',

        'settings.description':
            'Configure the organisation operating this Patrimoine installation.',

        'settings.managing_organisation':
            'Managing Organisation',

        'settings.managing_organisation_description':
            'This organisation represents the company or entity managing the property portfolio in this Patrimoine installation.',

        'settings.organisation_details':
            'Organisation Details',

        'settings.legal_name':
            'Legal Name',

        'settings.legal_name_placeholder':
            'e.g. Apotica Company Limited',

        'settings.address':
            'Address',

        'settings.address_placeholder':
            'Organisation address',

        'settings.phone':
            'Phone',

        'settings.alternate_phone':
            'Alternate Phone',

        'settings.general_email':
            'General Email',

        'settings.primary_contact':
            'Primary Contact',

        'settings.contact_person':
            'Contact Person',

        'settings.contact_phone':
            'Contact Phone',

        'settings.contact_email':
            'Contact Email',

        'settings.registration':
            'Registration',

        'settings.registration_number':
            'Registration Number',

        'settings.vat_tin':
            'VAT / TIN',

        'settings.language_currency':
            'Language & Currency',

        'settings.language_currency_description':
            'These settings apply to the entire Managing Organisation. Language and currency are independent.',

        'settings.language':
            'Language',

        'settings.language_help':
            'Controls normal user-facing Patrimoine content.',

        'settings.currency':
            'Currency',

        'settings.currency_help':
            'Changes presentation only. Stored monetary values are never converted.',

        'settings.financial_defaults':
            'Financial Defaults',

        'settings.financial_defaults_description':
            'Defaults apply to newly created records only. Existing leases and invoices keep their stored values.',

        'settings.default_vat_rate':
            'Default VAT Rate %',

        'settings.vat_help_label':
            'About Default VAT Rate',

        'settings.vat_help_text':
            'This rate is pre-filled when creating a new Lease. Individual Leases may still override the value, including using 0% where applicable. Changing this setting does not alter existing Leases or historical Invoices.',

        'settings.vat_starting_rate':
            'Used as the starting VAT rate for new Leases.',

        'settings.banking_details':
            'Banking Details',

        'settings.optional':
            'Optional.',

        'settings.bank_name':
            'Bank Name',

        'settings.bank_branch':
            'Bank Branch',

        'settings.account_name':
            'Account Name',

        'settings.account_number':
            'Account Number',

        'settings.notes':
            'Notes',

        'settings.save':
            'Save Organisation',

        'settings.saving':
            'Saving…',

        'settings.saved':
            'Managing Organisation saved successfully.',

        'settings.unable_to_load':
            'Unable to load Managing Organisation.',

        'settings.unable_to_save':
            'Unable to save Managing Organisation.',


        'parties.loading': 'Loading parties…',
        'parties.unable_to_load': 'Unable to load parties.',
        'parties.no_parties_found': 'No parties found',
        'parties.empty_description': 'Add a Party or change the current filters.',
        'parties.party': 'Party',
        'parties.person': 'Person',
        'parties.organisation': 'Organisation',
        'parties.association': 'Association',
        'parties.tenant': 'Tenant',
        'parties.owner': 'Owner',
        'parties.agent': 'Agent',
        'parties.managing_organisation': 'Managing Organisation',
        'parties.no_assigned_role': 'No assigned role',
        'parties.contact': 'Contact',
        'parties.edit': 'Edit',
        'parties.delete': 'Delete',
        'parties.page': 'Page',
        'parties.of': 'of',
        'parties.previous': 'Previous',
        'parties.next': 'Next',
        'parties.unable_to_load_party': 'Unable to load Party.',
        'parties.edit_party': 'Edit Party',
        'parties.add_party': 'Add Party',
        'parties.edit_party_description': 'Update Party identity, contact details and roles.',
        'parties.add_party_description': 'Create a person, organisation or association.',
        'parties.save_changes': 'Save Changes',
        'parties.create_party': 'Create Party',
        'parties.saving_changes': 'Saving Changes…',
        'parties.creating_party': 'Creating Party…',
        'parties.unable_to_update_party': 'Unable to update Party.',
        'parties.unable_to_create_party': 'Unable to create Party.',
        'parties.delete_confirmation': 'Delete "{{name}}"?',
        'parties.this_party': 'this Party',
        'parties.delete_restriction': 'A Party referenced by leases, ownership or financial records cannot be deleted.',
        'parties.unable_to_delete_party': 'Unable to delete Party.',

        'parties.title': 'Parties — Patrimoine',
        'parties.contacts_stakeholders': 'Contacts & Stakeholders',
        'parties.heading': 'Parties',
        'parties.page_description': 'Manage owners, tenants, agents, organisations and associations.',
        'parties.total_parties': 'Total Parties',
        'parties.people': 'People',
        'parties.organisations': 'Organisations',
        'parties.multiple_roles': 'Multiple Roles',
        'parties.directory': 'Party Directory',
        'parties.directory_description': 'People and entities participating in property operations.',
        'parties.search': 'Search Parties',
        'parties.search_placeholder': 'Search name, email, phone...',
        'parties.party_type': 'Party Type',
        'parties.party_role': 'Party Role',
        'parties.all_types': 'All Types',
        'parties.associations': 'Associations',
        'parties.all_roles': 'All Roles',
        'parties.owners': 'Owners',
        'parties.tenants': 'Tenants',
        'parties.agents': 'Agents',
        'parties.close': 'Close',
        'parties.party_type_description': 'Select the legal nature of this Party.',
        'parties.personal_details': 'Personal Details',
        'parties.organisation_details': 'Organisation Details',
        'parties.contact_identification': 'Contact & Identification',
        'parties.contact_identification_description': 'Optional secondary contact and identification information.',
        'parties.roles': 'Roles',
        'parties.roles_description': 'A Party may perform several functions at the same time.',
        'parties.banking_details': 'Banking Details',
        'parties.banking_description': 'Optional. Primarily used for Owners and Agents.',
        'parties.full_name': 'Full Name',
        'parties.phone': 'Phone',
        'parties.email': 'Email',
        'parties.legal_name': 'Legal Name',
        'parties.contact_person': 'Contact Person',
        'parties.contact_phone': 'Contact Phone',
        'parties.contact_email': 'Contact Email',
        'parties.alternate_phone': 'Alternate Phone',
        'parties.id_number': 'ID Number',
        'parties.registration_number': 'Registration Number',
        'parties.vat_tin': 'VAT / TIN',
        'parties.address': 'Address',
        'parties.bank_name': 'Bank Name',
        'parties.bank_branch': 'Bank Branch',
        'parties.account_name': 'Account Name',
        'parties.account_number': 'Account Number',
        'parties.notes': 'Notes',
        'parties.notes_placeholder': 'Optional internal notes',
        'parties.cancel': 'Cancel',
        'properties.loading':
            'Loading properties…',


        'parties.loading': 'Chargement des parties…',
        'parties.unable_to_load': 'Impossible de charger les parties.',
        'parties.no_parties_found': 'Aucune partie trouvée',
        'parties.empty_description': 'Ajoutez une partie ou modifiez les filtres actuels.',
        'parties.party': 'Partie',
        'parties.person': 'Personne',
        'parties.organisation': 'Organisation',
        'parties.association': 'Association',
        'parties.tenant': 'Locataire',
        'parties.owner': 'Propriétaire',
        'parties.agent': 'Agent',
        'parties.managing_organisation': 'Organisation gestionnaire',
        'parties.no_assigned_role': 'Aucun rôle attribué',
        'parties.contact': 'Contact',
        'parties.edit': 'Modifier',
        'parties.delete': 'Supprimer',
        'parties.page': 'Page',
        'parties.of': 'sur',
        'parties.previous': 'Précédent',
        'parties.next': 'Suivant',
        'parties.unable_to_load_party': 'Impossible de charger la partie.',
        'parties.edit_party': 'Modifier la partie',
        'parties.add_party': 'Ajouter une partie',
        'parties.edit_party_description': 'Mettez à jour l’identité, les coordonnées et les rôles de la partie.',
        'parties.add_party_description': 'Créez une personne, une organisation ou une association.',
        'parties.save_changes': 'Enregistrer les modifications',
        'parties.create_party': 'Créer la partie',
        'parties.saving_changes': 'Enregistrement des modifications…',
        'parties.creating_party': 'Création de la partie…',
        'parties.unable_to_update_party': 'Impossible de mettre à jour la partie.',
        'parties.unable_to_create_party': 'Impossible de créer la partie.',
        'parties.delete_confirmation': 'Supprimer « {{name}} » ?',
        'parties.this_party': 'cette partie',
        'parties.delete_restriction': 'Une partie référencée par des baux, des propriétés ou des opérations financières ne peut pas être supprimée.',
        'parties.unable_to_delete_party': 'Impossible de supprimer la partie.',

        'parties.title': 'Parties — Patrimoine',
        'parties.contacts_stakeholders': 'Contacts et parties prenantes',
        'parties.heading': 'Parties',
        'parties.page_description': 'Gérez les propriétaires, locataires, agents, organisations et associations.',
        'parties.total_parties': 'Total des parties',
        'parties.people': 'Personnes',
        'parties.organisations': 'Organisations',
        'parties.multiple_roles': 'Rôles multiples',
        'parties.directory': 'Répertoire des parties',
        'parties.directory_description': 'Personnes et entités participant aux opérations immobilières.',
        'parties.search': 'Rechercher des parties',
        'parties.search_placeholder': 'Rechercher par nom, e-mail, téléphone...',
        'parties.party_type': 'Type de partie',
        'parties.party_role': 'Rôle de la partie',
        'parties.all_types': 'Tous les types',
        'parties.associations': 'Associations',
        'parties.all_roles': 'Tous les rôles',
        'parties.owners': 'Propriétaires',
        'parties.tenants': 'Locataires',
        'parties.agents': 'Agents',
        'parties.close': 'Fermer',
        'parties.party_type_description': 'Sélectionnez la nature juridique de cette partie.',
        'parties.personal_details': 'Informations personnelles',
        'parties.organisation_details': 'Informations sur l’organisation',
        'parties.contact_identification': 'Contact et identification',
        'parties.contact_identification_description': 'Coordonnées secondaires et informations d’identification facultatives.',
        'parties.roles': 'Rôles',
        'parties.roles_description': 'Une partie peut remplir plusieurs fonctions simultanément.',
        'parties.banking_details': 'Coordonnées bancaires',
        'parties.banking_description': 'Facultatif. Principalement utilisé pour les propriétaires et les agents.',
        'parties.full_name': 'Nom complet',
        'parties.phone': 'Téléphone',
        'parties.email': 'E-mail',
        'parties.legal_name': 'Raison sociale',
        'parties.contact_person': 'Personne de contact',
        'parties.contact_phone': 'Téléphone du contact',
        'parties.contact_email': 'E-mail du contact',
        'parties.alternate_phone': 'Téléphone secondaire',
        'parties.id_number': 'Numéro d’identification',
        'parties.registration_number': 'Numéro d’enregistrement',
        'parties.vat_tin': 'TVA / NIF',
        'parties.address': 'Adresse',
        'parties.bank_name': 'Nom de la banque',
        'parties.bank_branch': 'Agence bancaire',
        'parties.account_name': 'Nom du compte',
        'parties.account_number': 'Numéro de compte',
        'parties.notes': 'Notes',
        'parties.notes_placeholder': 'Notes internes facultatives',
        'parties.cancel': 'Annuler',
        'properties.unable_to_load':
            'Unable to load properties.',

        'properties.no_address':
            'No address provided',

        'properties.unnamed_property':
            'Unnamed Property',

        'properties.unit_lower':
            'unit',

        'properties.units_lower':
            'units',

        'properties.edit':
            'Edit',

        'properties.add_unit':
            'Add Unit',

        'properties.hide_units':
            'Hide Units',

        'properties.view_units':
            'View Units',

        'properties.units':
            'Units',

        'properties.no_ownership_information':
            'No ownership information',

        'properties.owner':
            'Owner',

        'properties.no_units':
            'No units have been added to this property.',

        'properties.unnamed_unit':
            'Unnamed Unit',

        'properties.unit':
            'Unit',

        'properties.page':
            'Page',

        'properties.of':
            'of',

        'properties.previous':
            'Previous',

        'properties.next':
            'Next',

        'properties.edit_property':
            'Edit Property',

        'properties.add_property':
            'Add Property',

        'properties.edit_property_description':
            'Update the building details and ownership allocation.',

        'properties.add_property_description':
            'Create a building, define its ownership and add its units.',

        'properties.save_changes':
            'Save Changes',

        'properties.create_property':
            'Create Property',

        'properties.unable_to_load_owners':
            'Unable to load property owners.',

        'properties.unable_to_load_property':
            'Unable to load property.',

        'properties.party':
            'Party',

        'properties.create_owner_first':
            'Create an owner first…',

        'properties.select_owner':
            'Select owner…',

        'properties.create_new_owner':
            'Create a new owner',

        'properties.new':
            '+ New',

        'properties.no_owners_yet':
            'No owners yet. Create the first Owner Party.',

        'properties.ownership_percentage':
            'Ownership %',

        'properties.remove':
            'Remove',

        'properties.total':
            'Total',

        'properties.unit_name_number':
            'Unit Name / Number',

        'properties.unit_name_placeholder':
            'e.g. Apartment A1',

        'properties.description':
            'Description',

        'properties.optional_description':
            'Optional description',

        'properties.validation_owner_required':
            'A property must have at least one owner.',

        'properties.validation_select_every_owner':
            'Select an owner for every ownership row.',

        'properties.validation_duplicate_owner':
            'The same owner cannot be added more than once.',

        'properties.validation_owner_percentage':
            'Enter a valid ownership percentage for every owner.',

        'properties.validation_ownership_total':
            'Property ownership must total exactly 100%.',

        'properties.validation_unit_required':
            'A property must have at least one unit.',

        'properties.validation_every_unit_name':
            'Every unit must have a name or number.',

        'properties.validation_unique_unit_names':
            'Unit names must be unique within the property.',

        'properties.saving_changes':
            'Saving Changes…',

        'properties.creating_property':
            'Creating Property…',

        'properties.unable_to_update_property':
            'Unable to update property.',

        'properties.unable_to_create_property':
            'Unable to create property.',

        'properties.creating_owner':
            'Creating Owner…',

        'properties.unable_to_create_owner':
            'Unable to create owner.',

        'properties.create_owner':
            'Create Owner',

        'properties.person_required_fields':
            'Name, phone and email are required for a person.',

        'properties.organisation_required_fields':
            'Legal name and contact person details are required.',

        'properties.unable_to_locate_unit':
            'Unable to locate this unit.',

        'properties.property':
            'Property',

        'properties.edit_unit':
            'Edit Unit',

        'properties.edit_unit_description':
            'Update this unit\'s name or description.',

        'properties.add_unit_description':
            'Add a leasable unit to an existing property.',

        'properties.validation_valid_property':
            'A valid property must be selected.',

        'properties.validation_unit_name_required':
            'Unit name or number is required.',

        'properties.adding_unit':
            'Adding Unit…',

        'properties.unable_to_update_unit':
            'Unable to update unit.',

        'properties.unable_to_add_unit':
            'Unable to add unit.',

        'properties.title':
            'Properties — Patrimoine',

        'properties.portfolio':
            'Portfolio',

        'properties.heading':
            'Properties',

        'properties.page_description':
            'Manage buildings, ownership and individual units.',

        'properties.buildings':
            'Buildings',

        'properties.total_units':
            'Total Units',

        'properties.single_unit_properties':
            'Single-Unit Properties',

        'properties.multi_unit_properties':
            'Multi-Unit Properties',

        'properties.property_portfolio':
            'Property Portfolio',

        'properties.portfolio_description':
            'Buildings and their associated units.',

        'properties.search':
            'Search properties',

        'properties.search_placeholder':
            'Search buildings or units...',

        'properties.close':
            'Close',

        'properties.property_details':
            'Property Details',

        'properties.property_details_description':
            'Basic information identifying the building.',

        'properties.property_name':
            'Property Name',

        'properties.property_name_placeholder':
            'e.g. Airport Residential Apartments',

        'properties.location':
            'Location',

        'properties.location_placeholder':
            'e.g. Airport Residential, Accra',

        'properties.address':
            'Address',

        'properties.address_placeholder':
            'Street or property address',

        'properties.optional_property_description':
            'Optional property description',

        'properties.ownership':
            'Ownership',

        'properties.ownership_description':
            'Ownership must total exactly 100%.',

        'properties.add_owner':
            '+ Add Owner',

        'properties.units_description':
            'Every property must contain at least one leasable unit.',

        'properties.cancel':
            'Cancel',

        'properties.create_owner_description':
            'Create an Owner Party and assign it to this property.',

        'properties.owner_type':
            'Owner Type',

        'properties.person':
            'Person',

        'properties.organisation':
            'Organisation',

        'properties.association':
            'Association',

        'properties.full_name':
            'Full Name',

        'properties.phone':
            'Phone',

        'properties.email':
            'Email',

        'properties.legal_name':
            'Legal Name',

        'properties.contact_person':
            'Contact Person',

        'properties.contact_phone':
            'Contact Phone',

        'properties.contact_email':
            'Contact Email',

        'properties.existing_unit_name_placeholder':
            'e.g. Apartment A2',

        'properties.optional_unit_description':
            'Optional unit description',
    },

    fr: {
        'product.property_management':
            'Gestion immobilière',

        'login.title':
            'Connexion — Patrimoine',

        'login.hero_title':
            'Votre patrimoine immobilier, vos finances et vos locataires réunis au même endroit.',

        'login.hero_description':
            'Gérez les immeubles, les baux, les encaissements de loyers, les fonds des propriétaires et les rapports financiers depuis un espace unique.',

        'login.product_name':
            'Patrimoine Gestion immobilière',

        'login.welcome':
            'Bienvenue',

        'login.description':
            'Connectez-vous pour accéder à votre espace de gestion immobilière.',

        'login.email':
            'Adresse e-mail',

        'login.email_placeholder':
            'nom@exemple.com',

        'login.password':
            'Mot de passe',

        'login.password_placeholder':
            'Saisissez votre mot de passe',

        'login.sign_in':
            'Se connecter',

        'login.signing_in':
            'Connexion…',

        'login.unable_to_sign_in':
            'Impossible de se connecter.',

        'login.secure_access':
            'Accès sécurisé à Patrimoine Gestion immobilière.',

        'navigation.workspace':
            'Espace de travail',

        'navigation.dashboard':
            'Tableau de bord',

        'navigation.properties':
            'Propriétés',

        'navigation.parties':
            'Parties',

        'navigation.leases':
            'Baux',

        'navigation.payments':
            'Paiements',

        'navigation.finance':
            'Finance',

        'navigation.tenants':
            'Locataires',

        'navigation.owners':
            'Propriétaires',

        'navigation.reports':
            'Rapports',

        'navigation.settings':
            'Paramètres',

        'navigation.sign_out':
            'Se déconnecter',

        'user.property_manager':
            'Gestionnaire immobilier',

        'dashboard.title':
            'Tableau de bord — Patrimoine',

        'dashboard.overview':
            'Vue d’ensemble',

        'dashboard.heading':
            'Tableau de bord',

        'dashboard.description':
            'Situation actuelle du portefeuille et des finances.',

        'dashboard.buildings':
            'Immeubles',

        'dashboard.total_units':
            'Total des unités',

        'dashboard.occupied_units':
            'Unités occupées',

        'dashboard.vacant_units':
            'Unités vacantes',

        'dashboard.rent_due':
            'Loyers dus',

        'dashboard.rent_overdue':
            'Loyers en retard',

        'dashboard.collected_this_month':
            'Encaissé ce mois-ci',

        'dashboard.owner_funds_held':
            'Fonds des propriétaires détenus',

        'dashboard.overdue_rent':
            'Loyers en retard',

        'dashboard.overdue_description':
            'Obligations impayées nécessitant une attention.',

        'dashboard.upcoming_rent':
            'Loyers à venir',

        'dashboard.upcoming_description':
            'Obligations de loyer arrivant bientôt à échéance.',

        'dashboard.loading':
            'Chargement…',

        'dashboard.no_records':
            'Aucun élément à afficher.',

        'dashboard.tenant':
            'Locataire',

        'dashboard.due':
            'Échéance',

        'dashboard.unable_to_load':
            'Impossible de charger les informations du tableau de bord.',

        'language.en':
            'Anglais',

        'language.fr':
            'Français',

        'settings.title':
            'Paramètres — Patrimoine',

        'settings.administration':
            'Administration',

        'settings.heading':
            'Paramètres',

        'settings.description':
            'Configurez l’organisation qui exploite cette installation de Patrimoine.',

        'settings.managing_organisation':
            'Organisation gestionnaire',

        'settings.managing_organisation_description':
            'Cette organisation représente la société ou l’entité qui gère le portefeuille immobilier dans cette installation de Patrimoine.',

        'settings.organisation_details':
            'Informations sur l’organisation',

        'settings.legal_name':
            'Raison sociale',

        'settings.legal_name_placeholder':
            'p. ex. Apotica Company Limited',

        'settings.address':
            'Adresse',

        'settings.address_placeholder':
            'Adresse de l’organisation',

        'settings.phone':
            'Téléphone',

        'settings.alternate_phone':
            'Téléphone secondaire',

        'settings.general_email':
            'E-mail général',

        'settings.primary_contact':
            'Contact principal',

        'settings.contact_person':
            'Personne de contact',

        'settings.contact_phone':
            'Téléphone du contact',

        'settings.contact_email':
            'E-mail du contact',

        'settings.registration':
            'Immatriculation',

        'settings.registration_number':
            'Numéro d’immatriculation',

        'settings.vat_tin':
            'TVA / NIF',

        'settings.language_currency':
            'Langue et devise',

        'settings.language_currency_description':
            'Ces paramètres s’appliquent à toute l’organisation gestionnaire. La langue et la devise sont indépendantes.',

        'settings.language':
            'Langue',

        'settings.language_help':
            'Contrôle le contenu de Patrimoine normalement visible par les utilisateurs.',

        'settings.currency':
            'Devise',

        'settings.currency_help':
            'Modifie uniquement la présentation. Les valeurs monétaires enregistrées ne sont jamais converties.',

        'settings.financial_defaults':
            'Paramètres financiers par défaut',

        'settings.financial_defaults_description':
            'Les valeurs par défaut s’appliquent uniquement aux nouveaux enregistrements. Les baux et factures existants conservent leurs valeurs enregistrées.',

        'settings.default_vat_rate':
            'Taux de TVA par défaut %',

        'settings.vat_help_label':
            'À propos du taux de TVA par défaut',

        'settings.vat_help_text':
            'Ce taux est prérempli lors de la création d’un nouveau bail. Chaque bail peut toujours remplacer cette valeur, y compris par 0 % lorsque cela s’applique. La modification de ce paramètre ne change pas les baux existants ni les factures historiques.',

        'settings.vat_starting_rate':
            'Utilisé comme taux de TVA initial pour les nouveaux baux.',

        'settings.banking_details':
            'Coordonnées bancaires',

        'settings.optional':
            'Facultatif.',

        'settings.bank_name':
            'Banque',

        'settings.bank_branch':
            'Agence bancaire',

        'settings.account_name':
            'Nom du compte',

        'settings.account_number':
            'Numéro de compte',

        'settings.notes':
            'Notes',

        'settings.save':
            'Enregistrer l’organisation',

        'settings.saving':
            'Enregistrement…',

        'settings.saved':
            'Organisation gestionnaire enregistrée avec succès.',

        'settings.unable_to_load':
            'Impossible de charger l’organisation gestionnaire.',

        'settings.unable_to_save':
            'Impossible d’enregistrer l’organisation gestionnaire.',

        'properties.loading':
            'Chargement des propriétés…',

        'properties.unable_to_load':
            'Impossible de charger les propriétés.',

        'properties.no_address':
            'Aucune adresse renseignée',

        'properties.unnamed_property':
            'Propriété sans nom',

        'properties.unit_lower':
            'unité',

        'properties.units_lower':
            'unités',

        'properties.edit':
            'Modifier',

        'properties.add_unit':
            'Ajouter une unité',

        'properties.hide_units':
            'Masquer les unités',

        'properties.view_units':
            'Voir les unités',

        'properties.units':
            'Unités',

        'properties.no_ownership_information':
            'Aucune information sur la propriété',

        'properties.owner':
            'Propriétaire',

        'properties.no_units':
            'Aucune unité n’a été ajoutée à cette propriété.',

        'properties.unnamed_unit':
            'Unité sans nom',

        'properties.unit':
            'Unité',

        'properties.page':
            'Page',

        'properties.of':
            'sur',

        'properties.previous':
            'Précédent',

        'properties.next':
            'Suivant',

        'properties.edit_property':
            'Modifier la propriété',

        'properties.add_property':
            'Ajouter une propriété',

        'properties.edit_property_description':
            'Modifiez les informations de l’immeuble et la répartition de la propriété.',

        'properties.add_property_description':
            'Créez un immeuble, définissez sa propriété et ajoutez ses unités.',

        'properties.save_changes':
            'Enregistrer les modifications',

        'properties.create_property':
            'Créer la propriété',

        'properties.unable_to_load_owners':
            'Impossible de charger les propriétaires.',

        'properties.unable_to_load_property':
            'Impossible de charger la propriété.',

        'properties.party':
            'Partie',

        'properties.create_owner_first':
            'Créez d’abord un propriétaire…',

        'properties.select_owner':
            'Sélectionner un propriétaire…',

        'properties.create_new_owner':
            'Créer un nouveau propriétaire',

        'properties.new':
            '+ Nouveau',

        'properties.no_owners_yet':
            'Aucun propriétaire pour le moment. Créez la première Partie propriétaire.',

        'properties.ownership_percentage':
            'Part de propriété %',

        'properties.remove':
            'Supprimer',

        'properties.total':
            'Total',

        'properties.unit_name_number':
            'Nom / numéro de l’unité',

        'properties.unit_name_placeholder':
            'p. ex. Appartement A1',

        'properties.description':
            'Description',

        'properties.optional_description':
            'Description facultative',

        'properties.validation_owner_required':
            'Une propriété doit avoir au moins un propriétaire.',

        'properties.validation_select_every_owner':
            'Sélectionnez un propriétaire pour chaque ligne de propriété.',

        'properties.validation_duplicate_owner':
            'Le même propriétaire ne peut pas être ajouté plusieurs fois.',

        'properties.validation_owner_percentage':
            'Saisissez un pourcentage de propriété valide pour chaque propriétaire.',

        'properties.validation_ownership_total':
            'La répartition de la propriété doit totaliser exactement 100 %.',

        'properties.validation_unit_required':
            'Une propriété doit avoir au moins une unité.',

        'properties.validation_every_unit_name':
            'Chaque unité doit avoir un nom ou un numéro.',

        'properties.validation_unique_unit_names':
            'Les noms des unités doivent être uniques au sein de la propriété.',

        'properties.saving_changes':
            'Enregistrement des modifications…',

        'properties.creating_property':
            'Création de la propriété…',

        'properties.unable_to_update_property':
            'Impossible de modifier la propriété.',

        'properties.unable_to_create_property':
            'Impossible de créer la propriété.',

        'properties.creating_owner':
            'Création du propriétaire…',

        'properties.unable_to_create_owner':
            'Impossible de créer le propriétaire.',

        'properties.create_owner':
            'Créer le propriétaire',

        'properties.person_required_fields':
            'Le nom, le téléphone et l’e-mail sont obligatoires pour une personne.',

        'properties.organisation_required_fields':
            'La raison sociale et les coordonnées de la personne de contact sont obligatoires.',

        'properties.unable_to_locate_unit':
            'Impossible de trouver cette unité.',

        'properties.property':
            'Propriété',

        'properties.edit_unit':
            'Modifier l’unité',

        'properties.edit_unit_description':
            'Modifiez le nom ou la description de cette unité.',

        'properties.add_unit_description':
            'Ajoutez une unité louable à une propriété existante.',

        'properties.validation_valid_property':
            'Une propriété valide doit être sélectionnée.',

        'properties.validation_unit_name_required':
            'Le nom ou le numéro de l’unité est obligatoire.',

        'properties.adding_unit':
            'Ajout de l’unité…',

        'properties.unable_to_update_unit':
            'Impossible de modifier l’unité.',

        'properties.unable_to_add_unit':
            'Impossible d’ajouter l’unité.',

        'properties.title':
            'Propriétés — Patrimoine',

        'properties.portfolio':
            'Portefeuille',

        'properties.heading':
            'Propriétés',

        'properties.page_description':
            'Gérez les immeubles, leur propriété et leurs différentes unités.',

        'properties.buildings':
            'Immeubles',

        'properties.total_units':
            'Total des unités',

        'properties.single_unit_properties':
            'Propriétés à une seule unité',

        'properties.multi_unit_properties':
            'Propriétés à plusieurs unités',

        'properties.property_portfolio':
            'Portefeuille immobilier',

        'properties.portfolio_description':
            'Immeubles et unités qui leur sont associées.',

        'properties.search':
            'Rechercher des propriétés',

        'properties.search_placeholder':
            'Rechercher des immeubles ou des unités...',

        'properties.close':
            'Fermer',

        'properties.property_details':
            'Informations sur la propriété',

        'properties.property_details_description':
            'Informations de base permettant d’identifier l’immeuble.',

        'properties.property_name':
            'Nom de la propriété',

        'properties.property_name_placeholder':
            'p. ex. Appartements Airport Residential',

        'properties.location':
            'Localisation',

        'properties.location_placeholder':
            'p. ex. Airport Residential, Accra',

        'properties.address':
            'Adresse',

        'properties.address_placeholder':
            'Rue ou adresse de la propriété',

        'properties.optional_property_description':
            'Description facultative de la propriété',

        'properties.ownership':
            'Propriété',

        'properties.ownership_description':
            'La répartition de la propriété doit totaliser exactement 100 %.',

        'properties.add_owner':
            '+ Ajouter un propriétaire',

        'properties.units_description':
            'Chaque propriété doit comporter au moins une unité pouvant être louée.',

        'properties.cancel':
            'Annuler',

        'properties.create_owner_description':
            'Créez une Partie propriétaire et affectez-la à cette propriété.',

        'properties.owner_type':
            'Type de propriétaire',

        'properties.person':
            'Personne',

        'properties.organisation':
            'Organisation',

        'properties.association':
            'Association',

        'properties.full_name':
            'Nom complet',

        'properties.phone':
            'Téléphone',

        'properties.email':
            'E-mail',

        'properties.legal_name':
            'Raison sociale',

        'properties.contact_person':
            'Personne de contact',

        'properties.contact_phone':
            'Téléphone du contact',

        'properties.contact_email':
            'E-mail du contact',

        'properties.existing_unit_name_placeholder':
            'p. ex. Appartement A2',

        'properties.optional_unit_description':
            'Description facultative de l’unité',
    },
};

/**
 * Resolve a translation from the requested language with English fallback.
 *
 * @param {string} language
 * @param {string} key
 * @returns {string}
 */
export function translationFor(
    language,
    key
) {
    const english =
        translations.en
        || {};

    const catalogue =
        translations[language]
        || english;

    return catalogue[key]
        ?? english[key]
        ?? key;
}

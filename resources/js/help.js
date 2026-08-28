/*
|--------------------------------------------------------------------------
| Patrimoine Help & Documentation
|--------------------------------------------------------------------------
|
| V1.0.7 in-app documentation, available to every authenticated role.
|
| Two tabs:
|
| - Guide       Client-side documentation topics, searchable and
|               filterable by category. Content lives in `help.`
|               translation keys so both languages stay complete.
| - Update log  Localized release history served by GET /api/release-log.
|
| The #updates location hash deep-links to the Update log tab.
|
*/

import {
    apiRequest,
    escapeHtml,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    translations,
} from './translations.js';

/*
|--------------------------------------------------------------------------
| Translation Catalogue
|--------------------------------------------------------------------------
|
| Registered into the shared catalogue at module load, before the
| application bootstrap calls applyTranslations(). Keys already present
| in translations.js are never overridden, so the central catalogue
| remains authoritative when these keys are consolidated there.
|
*/

const helpTranslations = {
    en: {
        'help.title':
            'Help & documentation — Patrimoine',

        'help.eyebrow':
            'Support',

        'help.heading':
            'Help & documentation',

        'help.description':
            'How Patrimoine works, organised by topic, plus the history of application updates.',

        'help.tab_guide':
            'Guide',

        'help.tab_updates':
            'Update log',

        'help.search':
            'Search',

        'help.search_placeholder':
            'Search the guide…',

        'help.category':
            'Category',

        'help.all_categories':
            'All categories',

        'help.no_results':
            'No topics match',

        'help.no_results_description':
            'Try different words or clear the category filter.',

        'help.updates_loading':
            'Loading update log…',

        'help.unable_load_updates':
            'Unable to load the update log.',

        'help.current_version':
            'You are running version {version}.',

        'help.category_getting_started':
            'Getting started',

        'help.category_properties':
            'Properties & units',

        'help.category_parties':
            'Parties',

        'help.category_leases':
            'Leases',

        'help.category_money_in':
            'Money in',

        'help.category_owners':
            'Owners',

        'help.category_invoicing':
            'Invoicing & automation',

        'help.category_reports':
            'Reports & exports',

        'help.category_journal':
            'Financial journal & activity log',

        'help.category_admin':
            'Users & settings',

        /* Getting started */

        'help.topic_signing_in_title':
            'Signing in',

        'help.topic_signing_in_body1':
            'Patrimoine is invitation-based: an administrator creates your account and you receive an e-mail invitation to set your password. Sign in on the login page with your e-mail address and password.',

        'help.topic_signing_in_body2':
            'If you forget your password, use “Forgot password” on the login page to receive a reset link by e-mail. To sign out, use the user menu in the top bar.',

        'help.topic_language_currency_title':
            'Language & currency',

        'help.topic_language_currency_body1':
            'The interface, documents and e-mails are available in English and French, and amounts are presented in GHS (GH₵) or FCFA. Both settings are organisation-wide: they apply to every user.',

        'help.topic_language_currency_body2':
            'Administrators change the language and the currency presentation in Settings. There is no per-user language — the whole organisation works with one language and one currency presentation.',

        'help.topic_appearance_title':
            'Light & dark mode',

        'help.topic_appearance_body1':
            'Patrimoine has a light and a dark theme. Use the appearance control in the top bar to choose Light, Dark, or System — System follows your device preference.',

        'help.topic_appearance_body2':
            'The theme is a personal, per-browser choice. It does not affect other users or the organisation’s settings.',

        'help.topic_roles_title':
            'User roles',

        'help.topic_roles_body1':
            'Every user has one of three fixed roles. Administrator: access to everything, including the Manage area (Activity Log, Financial Journal, Users, Settings). Manager: everything except the Manage area — the day-to-day work on properties, parties, leases, tenants, owners and reports. Viewer: read-only access, including reports and document downloads.',

        'help.topic_roles_body2':
            'Roles are fixed — there are no custom roles and no per-user permissions. An administrator assigns the role when inviting a user and can change it later on the Users page.',

        /* Properties & units */

        'help.topic_creating_properties_title':
            'Creating properties',

        'help.topic_creating_properties_body1':
            'Properties are the buildings of your portfolio. Create one from the Properties page with its name, address and details, then add its units.',

        'help.topic_creating_properties_body2':
            'Everything else builds on the property: its ownership breakdown, its units, and — through the units — leases, invoicing and owner accounting.',

        'help.topic_ownership_title':
            'Ownership percentages',

        'help.topic_ownership_body1':
            'Each property records which owners hold it and at what percentage. The ownership shares must total exactly 100% — the form will not save otherwise.',

        'help.topic_ownership_body2':
            'Ownership percentages drive owner accounting: rent collected for the property is attributed to its owners according to their shares.',

        'help.topic_units_title':
            'Units',

        'help.topic_units_body1':
            'Units are the rentable spaces inside a property. Add, edit or delete them from the property’s detail view. A unit can be marked as commercial, and unit lists can be filtered to commercial units only.',

        'help.topic_units_body2':
            'A unit that other records depend on — a lease, for example — cannot be deleted.',

        'help.topic_deleting_properties_title':
            'Deleting properties',

        'help.topic_deleting_properties_body1':
            'A property can only be deleted while nothing depends on it. As soon as units, leases or financial records reference the property, deletion is blocked.',

        'help.topic_deleting_properties_body2':
            'This protection keeps your financial history complete and auditable — historical records are never orphaned by a deletion.',

        /* Parties */

        'help.topic_party_types_title':
            'People & organisations',

        'help.topic_party_types_body1':
            'Parties are the people and organisations you work with. A party is either a person — with separate Given names and Surname fields — or an organisation with a single name.',

        'help.topic_party_types_body2':
            'Contact details such as e-mail address and phone number are stored on the party and used for documents and e-mailed correspondence.',

        'help.topic_party_roles_title':
            'Party roles',

        'help.topic_party_roles_body1':
            'A party can act as a tenant, an owner, an agent — or several of these at once. The same person can, for example, own one property and rent a unit in another.',

        'help.topic_party_roles_body2':
            'The role determines where the party appears: tenants on leases and in the Tenants workspace, owners in property ownership and the Owners workspace, agents as representatives.',

        'help.topic_party_deletion_title':
            'Deletion protection',

        'help.topic_party_deletion_body1':
            'A party referenced by leases, ownership, payments or other records cannot be deleted. This protects the audit trail and your financial history.',

        'help.topic_party_deletion_body2':
            'A party with no dependent records can be deleted normally.',

        /* Leases */

        'help.topic_party_emails_title':
            'Deciding who Patrimoine emails',

        'help.topic_party_emails_body1':
            'Settings › Preferences carries a switch for everything Patrimoine sends to your tenants, owners and agents. Turn it off and nothing leaves: no invoices, receipts, reminders, notices or vouchers. Anyone who tries to send one is told why rather than left wondering.',

        'help.topic_party_emails_body2':
            'Each party also has its own choice on its record: follow the organisation, always email this party, or never email it. So you can go quiet everywhere and still let one owner through, or keep sending while excluding a single tenant. A party nobody may email is marked in the Parties list. Emails you receive as a user of Patrimoine — sign-in codes, invitations, password resets — are never affected.',

        'help.topic_lease_wizard_title':
            'Creating a lease with the assistant',

        'help.topic_lease_wizard_body1':
            'The Assistant button on the Leases page walks you through a whole letting in one sitting: the property and unit, the owners, the tenant, the agent and their commission, the dates, the notice and rent increases, the rent and advance, then the fees. It explains each term as it asks for it.',

        'help.topic_lease_wizard_body2':
            'Nothing is saved until the last page, where you check everything and choose to save a draft or create and activate. Leaving halfway costs nothing and leaves nothing behind. The assistant skips the owners page when the property already has its owners, offers only vacant units, and fills the end date from the duration you pick — or lets you leave it open, in which case the lease runs until it is terminated.',

        'help.topic_creating_leases_title':
            'Creating a lease',

        'help.topic_creating_leases_body1':
            'A lease links a tenant to a unit. When creating it you set the rent amount, the billing frequency, whether the rent is VAT-inclusive, and the day of the period on which rent falls due.',

        'help.topic_creating_leases_body2':
            'You can also record a security deposit, rent paid in advance, one-off fees, and the management commission that applies to the lease.',

        'help.topic_lease_lifecycle_title':
            'Lease lifecycle',

        'help.topic_lease_lifecycle_body1':
            'An active lease can be extended to a later end date, or terminated. Termination runs a settlement so that deposits, funds held and outstanding amounts are resolved.',

        'help.topic_lease_lifecycle_body2':
            'Deleting a lease is a controlled operation: Patrimoine first shows an impact preview of every record that would be affected, and only proceeds once you confirm.',

        'help.topic_rent_increments_title':
            'Rent increments',

        'help.topic_rent_increments_body1':
            'A rent increase can be scheduled on a lease ahead of time, with its effective date and new amount. A scheduled increment can be cancelled while it is still pending.',

        'help.topic_rent_increments_body2':
            'On the effective date the increment is applied automatically by the nightly scheduler — no manual step is needed — and tenants are sent an increment notice beforehand.',

        /* Money in */

        'help.topic_recording_payments_title':
            'Recording payments & deposits',

        'help.topic_recording_payments_body1':
            'Record tenant payments from the tenant’s accounts view. Money can be received into any fund account kept for the tenant, and deposits are recorded the same way.',

        'help.topic_recording_payments_body2':
            'Every recorded payment produces a receipt that can be downloaded as a PDF and e-mailed to the tenant.',

        'help.topic_fifo_title':
            'FIFO settlement',

        'help.topic_fifo_body1':
            'Payments are applied to a tenant’s outstanding rent oldest-first (FIFO): the oldest unpaid invoice is settled before newer ones.',

        'help.topic_fifo_body2':
            'A partial payment therefore always reduces the oldest debt first, which keeps arrears figures meaningful in every report.',

        'help.topic_withdrawals_transfers_title':
            'Withdrawals & transfers',

        'help.topic_withdrawals_transfers_body1':
            'Money held for a tenant can be withdrawn, and can be transferred between the tenant’s fund accounts. A transfer always requires a reason and produces a printable voucher.',

        'help.topic_withdrawals_transfers_body2':
            'Withdrawals produce vouchers as well, so every movement of funds leaves a document trail.',

        'help.topic_adjustments_documents_title':
            'Adjustments, receipts & vouchers',

        'help.topic_adjustments_documents_body1':
            'Adjustments are documented corrections to an account balance, used when something was recorded wrongly. Like every money movement, they are permanently recorded.',

        'help.topic_adjustments_documents_body2':
            'Receipts and vouchers can be downloaded as PDFs and e-mailed directly from the application.',

        /* Owners */

        'help.topic_owner_entitlement_title':
            'Owner entitlement',

        'help.topic_owner_entitlement_body1':
            'Owner accounting is cash-basis: an owner’s entitlement is their ownership share of rent actually collected — not of rent merely invoiced — minus the management fee.',

        'help.topic_owner_entitlement_body2':
            'Entitlement accrues automatically as tenant payments are recorded, and the Owners workspace shows what is currently held for each owner.',

        'help.topic_owner_expenses_title':
            'Expenses & bills',

        'help.topic_owner_expenses_body1':
            'Two kinds of charges reduce owner funds. Property expenses are recorded against a property and shared between its owners according to their ownership percentages.',

        'help.topic_owner_expenses_body2':
            'Direct expense bills are itemized, multi-line bills recorded against a single owner. The bill document is e-mailed to the owner and can be downloaded.',

        'help.topic_owner_payouts_title':
            'Deposits, payouts & adjustments',

        'help.topic_owner_payouts_body1':
            'Owners can deposit funds into their account, and funds held for an owner can be paid out — every payout produces a receipt.',

        'help.topic_owner_payouts_body2':
            'Adjustments are available for documented corrections. Every movement appears in the owner’s records and in the financial history.',

        /* Invoicing & automation */

        'help.topic_automation_title':
            'Automatic invoicing & notices',

        'help.topic_automation_body1':
            'Patrimoine runs a nightly scheduler. Shortly after midnight it applies rent increments that have reached their effective date, then generates the rent invoices that fall due.',

        'help.topic_automation_body2':
            'In the morning it sends the scheduled documents: rent-increment notices and rent reminders are e-mailed automatically. None of this requires a manual step.',

        /* Reports & exports */

        'help.topic_reports_title':
            'Reports & exports',

        'help.topic_reports_body1':
            'The Reports workspace covers every subject of the application — including collections, arrears aging, occupancy and funds held — each with its own filters.',

        'help.topic_reports_body2':
            'Every report and every list download is available in three formats: PDF, XLSX and CSV. Viewers can run reports and download documents too.',

        /* Financial journal & activity log */

        'help.topic_financial_journal_title':
            'Financial journal (administrators)',

        'help.topic_financial_journal_body1':
            'The Financial Journal is the permanent double-entry accounting record. Every financial event posts balanced debit and credit lines against the chart of accounts. Entries are immutable — corrections are made by reversal entries, never by editing.',

        'help.topic_financial_journal_body2':
            'The opening-balance cutover marks the moment the organisation’s balances were established in Patrimoine: history before the cutover is summarized as opening balances, and everything after it is recorded entry by entry.',

        'help.topic_financial_journal_body3':
            'Only administrators can browse the Journal. It is read-only in the browser and can be exported as PDF, XLSX and CSV.',

        'help.topic_activity_log_title':
            'Activity log (administrators)',

        'help.topic_activity_log_body1':
            'The Activity Log records who did what and when: sign-ins and every significant action, with the acting user, timestamp and details. The Financial Journal records the accounting effect of an operation; the Activity Log records the operational event itself.',

        'help.topic_activity_log_body2':
            'It is tamper-proof, filterable, and exportable as PDF, XLSX and CSV. Only administrators can view it.',

        /* Users & settings */

        'help.topic_user_management_title':
            'Users & invitations (administrators)',

        'help.topic_user_management_body1':
            'Administrators invite users by e-mail from the Users page, choosing the role at invitation time. The invited person sets their own password through the invitation link.',

        'help.topic_user_management_body2':
            'A user’s role can be changed later from the same page.',

        'help.topic_organisation_settings_title':
            'Organisation settings (administrators)',

        'help.topic_organisation_settings_body1':
            'Settings holds the organisation-wide configuration: organisation identity, interface language and currency presentation. Changes apply to every user of the organisation.',

        'help.topic_backup_restore_title':
            'Backup & restore (administrators)',

        'help.topic_backup_restore_body1':
            'Registry backup exports the registry — properties, units, tenants and leases — to a file that can be re-imported later. The import is safe and idempotent: running it again does not duplicate records.',

        'help.topic_backup_restore_body2':
            'Only the registry is restored from files. Financial history — payments, journal entries, documents — is never restored from a backup file; it lives permanently in the application.',
    },

    fr: {
        'help.title':
            'Aide et documentation — Patrimoine',

        'help.eyebrow':
            'Assistance',

        'help.heading':
            'Aide et documentation',

        'help.description':
            'Le fonctionnement de Patrimoine, organisé par thème, ainsi que l’historique des mises à jour de l’application.',

        'help.tab_guide':
            'Guide',

        'help.tab_updates':
            'Journal des mises à jour',

        'help.search':
            'Rechercher',

        'help.search_placeholder':
            'Rechercher dans le guide…',

        'help.category':
            'Catégorie',

        'help.all_categories':
            'Toutes les catégories',

        'help.no_results':
            'Aucun sujet ne correspond',

        'help.no_results_description':
            'Essayez d’autres termes ou effacez le filtre de catégorie.',

        'help.updates_loading':
            'Chargement du journal des mises à jour…',

        'help.unable_load_updates':
            'Impossible de charger le journal des mises à jour.',

        'help.current_version':
            'Vous utilisez la version {version}.',

        'help.category_getting_started':
            'Premiers pas',

        'help.category_properties':
            'Propriétés et unités',

        'help.category_parties':
            'Parties',

        'help.category_leases':
            'Baux',

        'help.category_money_in':
            'Encaissements',

        'help.category_owners':
            'Propriétaires',

        'help.category_invoicing':
            'Facturation et automatisation',

        'help.category_reports':
            'Rapports et exports',

        'help.category_journal':
            'Journal financier et journal d’activité',

        'help.category_admin':
            'Utilisateurs et paramètres',

        /* Premiers pas */

        'help.topic_signing_in_title':
            'Se connecter',

        'help.topic_signing_in_body1':
            'Patrimoine fonctionne sur invitation : un administrateur crée votre compte et vous recevez une invitation par e-mail pour définir votre mot de passe. Connectez-vous sur la page de connexion avec votre adresse e-mail et votre mot de passe.',

        'help.topic_signing_in_body2':
            'En cas d’oubli du mot de passe, utilisez « Mot de passe oublié » sur la page de connexion pour recevoir un lien de réinitialisation par e-mail. Pour vous déconnecter, utilisez le menu utilisateur dans la barre supérieure.',

        'help.topic_language_currency_title':
            'Langue et devise',

        'help.topic_language_currency_body1':
            'L’interface, les documents et les e-mails sont disponibles en anglais et en français, et les montants sont présentés en GHS (GH₵) ou en FCFA. Ces deux réglages valent pour toute l’organisation : ils s’appliquent à chaque utilisateur.',

        'help.topic_language_currency_body2':
            'Les administrateurs modifient la langue et la présentation de la devise dans les Paramètres. Il n’existe pas de langue par utilisateur — toute l’organisation travaille avec une seule langue et une seule présentation de devise.',

        'help.topic_appearance_title':
            'Mode clair et sombre',

        'help.topic_appearance_body1':
            'Patrimoine propose un thème clair et un thème sombre. Utilisez le réglage d’apparence dans la barre supérieure pour choisir Clair, Sombre ou Système — Système suit la préférence de votre appareil.',

        'help.topic_appearance_body2':
            'Le thème est un choix personnel, propre à chaque navigateur. Il n’affecte ni les autres utilisateurs ni les paramètres de l’organisation.',

        'help.topic_roles_title':
            'Rôles des utilisateurs',

        'help.topic_roles_body1':
            'Chaque utilisateur possède l’un des trois rôles fixes. Administrateur : accès à tout, y compris la zone Gestion (Journal d’activité, Journal financier, Utilisateurs, Paramètres). Gestionnaire : tout sauf la zone Gestion — le travail quotidien sur les propriétés, parties, baux, locataires, propriétaires et rapports. Consultation : accès en lecture seule, y compris les rapports et les téléchargements de documents.',

        'help.topic_roles_body2':
            'Les rôles sont fixes — il n’existe ni rôle personnalisé ni permission par utilisateur. Un administrateur attribue le rôle au moment de l’invitation et peut le modifier ensuite depuis la page Utilisateurs.',

        /* Propriétés et unités */

        'help.topic_creating_properties_title':
            'Créer des propriétés',

        'help.topic_creating_properties_body1':
            'Les propriétés sont les immeubles de votre portefeuille. Créez-en une depuis la page Propriétés avec son nom, son adresse et ses caractéristiques, puis ajoutez ses unités.',

        'help.topic_creating_properties_body2':
            'Tout le reste s’appuie sur la propriété : sa répartition de propriété, ses unités et — à travers les unités — les baux, la facturation et la comptabilité des propriétaires.',

        'help.topic_ownership_title':
            'Pourcentages de propriété',

        'help.topic_ownership_body1':
            'Chaque propriété enregistre quels propriétaires la détiennent et à quel pourcentage. Les parts doivent totaliser exactement 100 % — sinon le formulaire ne s’enregistre pas.',

        'help.topic_ownership_body2':
            'Les pourcentages de propriété pilotent la comptabilité des propriétaires : le loyer encaissé pour la propriété est attribué à ses propriétaires selon leurs parts.',

        'help.topic_units_title':
            'Unités',

        'help.topic_units_body1':
            'Les unités sont les espaces louables d’une propriété. Ajoutez, modifiez ou supprimez-les depuis la vue détaillée de la propriété. Une unité peut être marquée comme commerciale, et les listes d’unités peuvent être filtrées sur les unités commerciales.',

        'help.topic_units_body2':
            'Une unité dont dépendent d’autres enregistrements — un bail, par exemple — ne peut pas être supprimée.',

        'help.topic_deleting_properties_title':
            'Supprimer des propriétés',

        'help.topic_deleting_properties_body1':
            'Une propriété ne peut être supprimée que tant que rien n’en dépend. Dès que des unités, des baux ou des écritures financières y font référence, la suppression est bloquée.',

        'help.topic_deleting_properties_body2':
            'Cette protection garde votre historique financier complet et vérifiable — aucune suppression ne laisse d’enregistrements orphelins.',

        /* Parties */

        'help.topic_party_types_title':
            'Personnes et organisations',

        'help.topic_party_types_body1':
            'Les parties sont les personnes et organisations avec lesquelles vous travaillez. Une partie est soit une personne — avec des champs Prénoms et Nom distincts — soit une organisation avec un nom unique.',

        'help.topic_party_types_body2':
            'Les coordonnées, comme l’adresse e-mail et le téléphone, sont enregistrées sur la partie et utilisées pour les documents et la correspondance par e-mail.',

        'help.topic_party_roles_title':
            'Rôles des parties',

        'help.topic_party_roles_body1':
            'Une partie peut agir comme locataire, propriétaire, agent — ou plusieurs de ces rôles à la fois. La même personne peut, par exemple, posséder une propriété et louer une unité dans une autre.',

        'help.topic_party_roles_body2':
            'Le rôle détermine où la partie apparaît : les locataires sur les baux et dans l’espace Locataires, les propriétaires dans la répartition de propriété et l’espace Propriétaires, les agents comme représentants.',

        'help.topic_party_deletion_title':
            'Protection contre la suppression',

        'help.topic_party_deletion_body1':
            'Une partie référencée par des baux, une répartition de propriété, des paiements ou d’autres enregistrements ne peut pas être supprimée. Cela protège la piste d’audit et votre historique financier.',

        'help.topic_party_deletion_body2':
            'Une partie sans enregistrement dépendant peut être supprimée normalement.',

        /* Baux */

        'help.topic_party_emails_title':
            'Choisir à qui Patrimoine écrit',

        'help.topic_party_emails_body1':
            'Paramètres › Préférences comporte un interrupteur pour tout ce que Patrimoine envoie à vos locataires, propriétaires et agents. Désactivez-le et plus rien ne part : ni facture, ni reçu, ni rappel, ni avis, ni bon. Toute tentative d\'envoi en affiche la raison, au lieu de laisser un doute.',

        'help.topic_party_emails_body2':
            'Chaque partie dispose aussi de son propre réglage sur sa fiche : suivre l\'organisation, toujours lui écrire, ou ne jamais lui écrire. Vous pouvez donc tout couper et laisser passer un propriétaire, ou continuer d\'envoyer en excluant un seul locataire. Une partie à qui personne n\'écrit est signalée dans la liste des Parties. Les e-mails que vous recevez en tant qu\'utilisateur — codes de connexion, invitations, réinitialisations — ne sont jamais concernés.',

        'help.topic_lease_wizard_title':
            'Créer un bail avec l\'assistant',

        'help.topic_lease_wizard_body1':
            'Le bouton Assistant de la page Baux vous guide d\'un bout à l\'autre : le bien et le lot, les propriétaires, le locataire, l\'agent et sa commission, les dates, le préavis et les augmentations, le loyer et l\'avance, puis les honoraires. Chaque terme est expliqué au moment où il est demandé.',

        'help.topic_lease_wizard_body2':
            'Rien n\'est enregistré avant la dernière page, où vous vérifiez l\'ensemble puis choisissez d\'enregistrer un brouillon ou de créer et activer. Quitter en cours de route ne coûte rien et ne laisse rien derrière. L\'assistant ignore la page des propriétaires si le bien a déjà les siens, ne propose que les lots vacants, et remplit la date de fin selon la durée choisie — ou vous laisse la laisser ouverte, le bail courant alors jusqu\'à sa résiliation.',

        'help.topic_creating_leases_title':
            'Créer un bail',

        'help.topic_creating_leases_body1':
            'Un bail relie un locataire à une unité. À la création, vous définissez le montant du loyer, la périodicité de facturation, si le loyer est TTC (TVA incluse) et le jour de la période auquel le loyer est exigible.',

        'help.topic_creating_leases_body2':
            'Vous pouvez aussi enregistrer un dépôt de garantie, un loyer payé d’avance, des frais ponctuels et la commission de gestion applicable au bail.',

        'help.topic_lease_lifecycle_title':
            'Cycle de vie du bail',

        'help.topic_lease_lifecycle_body1':
            'Un bail actif peut être prolongé jusqu’à une date ultérieure, ou résilié. La résiliation déclenche un règlement de sortie afin que les dépôts, les fonds détenus et les montants dus soient soldés.',

        'help.topic_lease_lifecycle_body2':
            'La suppression d’un bail est une opération contrôlée : Patrimoine affiche d’abord un aperçu d’impact de chaque enregistrement concerné, et ne poursuit qu’après votre confirmation.',

        'help.topic_rent_increments_title':
            'Augmentations de loyer',

        'help.topic_rent_increments_body1':
            'Une augmentation de loyer peut être planifiée à l’avance sur un bail, avec sa date d’effet et son nouveau montant. Une augmentation planifiée peut être annulée tant qu’elle est en attente.',

        'help.topic_rent_increments_body2':
            'À la date d’effet, l’augmentation est appliquée automatiquement par le planificateur nocturne — aucune intervention manuelle n’est nécessaire — et les locataires reçoivent au préalable un avis d’augmentation.',

        /* Encaissements */

        'help.topic_recording_payments_title':
            'Enregistrer paiements et dépôts',

        'help.topic_recording_payments_body1':
            'Enregistrez les paiements des locataires depuis la vue des comptes du locataire. L’argent peut être reçu sur n’importe quel compte de fonds tenu pour le locataire, et les dépôts s’enregistrent de la même façon.',

        'help.topic_recording_payments_body2':
            'Chaque paiement enregistré produit un reçu téléchargeable en PDF et envoyable par e-mail au locataire.',

        'help.topic_fifo_title':
            'Imputation FIFO',

        'help.topic_fifo_body1':
            'Les paiements sont imputés sur les loyers dus du locataire du plus ancien au plus récent (FIFO) : la facture impayée la plus ancienne est soldée avant les plus récentes.',

        'help.topic_fifo_body2':
            'Un paiement partiel réduit donc toujours la dette la plus ancienne en premier, ce qui garde les chiffres d’arriérés cohérents dans tous les rapports.',

        'help.topic_withdrawals_transfers_title':
            'Retraits et virements',

        'help.topic_withdrawals_transfers_body1':
            'L’argent détenu pour un locataire peut être retiré, et peut être transféré entre les comptes de fonds du locataire. Un virement exige toujours un motif et produit un justificatif imprimable.',

        'help.topic_withdrawals_transfers_body2':
            'Les retraits produisent également des justificatifs : chaque mouvement de fonds laisse une trace documentaire.',

        'help.topic_adjustments_documents_title':
            'Ajustements, reçus et justificatifs',

        'help.topic_adjustments_documents_body1':
            'Les ajustements sont des corrections documentées du solde d’un compte, utilisées quand quelque chose a été mal enregistré. Comme tout mouvement d’argent, ils sont enregistrés de façon permanente.',

        'help.topic_adjustments_documents_body2':
            'Les reçus et justificatifs peuvent être téléchargés en PDF et envoyés par e-mail directement depuis l’application.',

        /* Propriétaires */

        'help.topic_owner_entitlement_title':
            'Droits du propriétaire',

        'help.topic_owner_entitlement_body1':
            'La comptabilité des propriétaires est en base caisse : le droit d’un propriétaire correspond à sa part du loyer réellement encaissé — et non du loyer simplement facturé — diminuée des frais de gestion.',

        'help.topic_owner_entitlement_body2':
            'Les droits s’accumulent automatiquement au fil des paiements des locataires, et l’espace Propriétaires montre ce qui est actuellement détenu pour chaque propriétaire.',

        'help.topic_owner_expenses_title':
            'Dépenses et factures',

        'help.topic_owner_expenses_body1':
            'Deux types de charges réduisent les fonds des propriétaires. Les dépenses de propriété sont enregistrées sur une propriété et réparties entre ses propriétaires selon leurs pourcentages de propriété.',

        'help.topic_owner_expenses_body2':
            'Les factures de dépenses directes sont des factures détaillées, à plusieurs lignes, enregistrées sur un seul propriétaire. Le document de facture est envoyé par e-mail au propriétaire et peut être téléchargé.',

        'help.topic_owner_payouts_title':
            'Dépôts, versements et ajustements',

        'help.topic_owner_payouts_body1':
            'Les propriétaires peuvent déposer des fonds sur leur compte, et les fonds détenus pour un propriétaire peuvent lui être versés — chaque versement produit un reçu.',

        'help.topic_owner_payouts_body2':
            'Les ajustements servent aux corrections documentées. Chaque mouvement apparaît dans les enregistrements du propriétaire et dans l’historique financier.',

        /* Facturation et automatisation */

        'help.topic_automation_title':
            'Facturation et avis automatiques',

        'help.topic_automation_body1':
            'Patrimoine exécute un planificateur nocturne. Peu après minuit, il applique les augmentations de loyer arrivées à leur date d’effet, puis génère les factures de loyer arrivant à échéance.',

        'help.topic_automation_body2':
            'Le matin, il envoie les documents planifiés : les avis d’augmentation de loyer et les rappels de loyer partent automatiquement par e-mail. Rien de tout cela ne demande d’intervention manuelle.',

        /* Rapports et exports */

        'help.topic_reports_title':
            'Rapports et exports',

        'help.topic_reports_body1':
            'L’espace Rapports couvre tous les sujets de l’application — dont les encaissements, la balance âgée des arriérés, l’occupation et les fonds détenus — chacun avec ses propres filtres.',

        'help.topic_reports_body2':
            'Chaque rapport et chaque téléchargement de liste est disponible en trois formats : PDF, XLSX et CSV. Les utilisateurs en consultation peuvent aussi lancer des rapports et télécharger des documents.',

        /* Journal financier et journal d’activité */

        'help.topic_financial_journal_title':
            'Journal financier (administrateurs)',

        'help.topic_financial_journal_body1':
            'Le Journal financier est le registre comptable permanent en partie double. Chaque événement financier passe des lignes de débit et de crédit équilibrées sur le plan comptable. Les écritures sont immuables — les corrections se font par écritures d’extourne, jamais par modification.',

        'help.topic_financial_journal_body2':
            'La bascule des soldes d’ouverture marque le moment où les soldes de l’organisation ont été établis dans Patrimoine : l’historique antérieur à la bascule est résumé en soldes d’ouverture, et tout ce qui suit est enregistré écriture par écriture.',

        'help.topic_financial_journal_body3':
            'Seuls les administrateurs peuvent consulter le Journal. Il est en lecture seule dans le navigateur et exportable en PDF, XLSX et CSV.',

        'help.topic_activity_log_title':
            'Journal d’activité (administrateurs)',

        'help.topic_activity_log_body1':
            'Le Journal d’activité enregistre qui a fait quoi et quand : les connexions et chaque action significative, avec l’utilisateur, l’horodatage et les détails. Le Journal financier enregistre l’effet comptable d’une opération ; le Journal d’activité enregistre l’événement opérationnel lui-même.',

        'help.topic_activity_log_body2':
            'Il est infalsifiable, filtrable et exportable en PDF, XLSX et CSV. Seuls les administrateurs peuvent le consulter.',

        /* Utilisateurs et paramètres */

        'help.topic_user_management_title':
            'Utilisateurs et invitations (administrateurs)',

        'help.topic_user_management_body1':
            'Les administrateurs invitent les utilisateurs par e-mail depuis la page Utilisateurs, en choisissant le rôle au moment de l’invitation. La personne invitée définit son propre mot de passe via le lien d’invitation.',

        'help.topic_user_management_body2':
            'Le rôle d’un utilisateur peut être modifié ultérieurement depuis la même page.',

        'help.topic_organisation_settings_title':
            'Paramètres de l’organisation (administrateurs)',

        'help.topic_organisation_settings_body1':
            'Les Paramètres regroupent la configuration de toute l’organisation : identité de l’organisation, langue de l’interface et présentation de la devise. Les changements s’appliquent à chaque utilisateur de l’organisation.',

        'help.topic_backup_restore_title':
            'Sauvegarde et restauration (administrateurs)',

        'help.topic_backup_restore_body1':
            'La sauvegarde du registre exporte le registre — propriétés, unités, locataires et baux — vers un fichier réimportable plus tard. L’import est sûr et idempotent : le relancer ne duplique aucun enregistrement.',

        'help.topic_backup_restore_body2':
            'Seul le registre est restauré depuis un fichier. L’historique financier — paiements, écritures de journal, documents — n’est jamais restauré depuis une sauvegarde ; il vit de façon permanente dans l’application.',
    },
};

Object.entries(
    helpTranslations
).forEach(
    ([language, entries]) => {
        translations[language] =
            translations[language]
            || {};

        Object.entries(
            entries
        ).forEach(
            ([key, value]) => {
                if (
                    ! (
                        key
                        in translations[language]
                    )
                ) {
                    translations[language][key] =
                        value;
                }
            }
        );
    }
);

/*
|--------------------------------------------------------------------------
| Content Model
|--------------------------------------------------------------------------
*/

const helpCategories = [
    'getting_started',
    'properties',
    'parties',
    'leases',
    'money_in',
    'owners',
    'invoicing',
    'reports',
    'journal',
    'admin',
];

const helpTopics = [
    {
        id: 'signing_in',
        category: 'getting_started',
        titleKey: 'help.topic_signing_in_title',
        bodyKeys: [
            'help.topic_signing_in_body1',
            'help.topic_signing_in_body2',
        ],
    },
    {
        id: 'language_currency',
        category: 'getting_started',
        titleKey: 'help.topic_language_currency_title',
        bodyKeys: [
            'help.topic_language_currency_body1',
            'help.topic_language_currency_body2',
        ],
    },
    {
        id: 'appearance',
        category: 'getting_started',
        titleKey: 'help.topic_appearance_title',
        bodyKeys: [
            'help.topic_appearance_body1',
            'help.topic_appearance_body2',
        ],
    },
    {
        id: 'roles',
        category: 'getting_started',
        titleKey: 'help.topic_roles_title',
        bodyKeys: [
            'help.topic_roles_body1',
            'help.topic_roles_body2',
        ],
    },
    {
        id: 'creating_properties',
        category: 'properties',
        titleKey: 'help.topic_creating_properties_title',
        bodyKeys: [
            'help.topic_creating_properties_body1',
            'help.topic_creating_properties_body2',
        ],
    },
    {
        id: 'ownership',
        category: 'properties',
        titleKey: 'help.topic_ownership_title',
        bodyKeys: [
            'help.topic_ownership_body1',
            'help.topic_ownership_body2',
        ],
    },
    {
        id: 'units',
        category: 'properties',
        titleKey: 'help.topic_units_title',
        bodyKeys: [
            'help.topic_units_body1',
            'help.topic_units_body2',
        ],
    },
    {
        id: 'deleting_properties',
        category: 'properties',
        titleKey: 'help.topic_deleting_properties_title',
        bodyKeys: [
            'help.topic_deleting_properties_body1',
            'help.topic_deleting_properties_body2',
        ],
    },
    {
        id: 'party_types',
        category: 'parties',
        titleKey: 'help.topic_party_types_title',
        bodyKeys: [
            'help.topic_party_types_body1',
            'help.topic_party_types_body2',
        ],
    },
    {
        id: 'party_roles',
        category: 'parties',
        titleKey: 'help.topic_party_roles_title',
        bodyKeys: [
            'help.topic_party_roles_body1',
            'help.topic_party_roles_body2',
        ],
    },
    {
        id: 'party_deletion',
        category: 'parties',
        titleKey: 'help.topic_party_deletion_title',
        bodyKeys: [
            'help.topic_party_deletion_body1',
            'help.topic_party_deletion_body2',
        ],
    },
    {
        id: 'party_emails',
        category: 'parties',
        titleKey: 'help.topic_party_emails_title',
        bodyKeys: [
            'help.topic_party_emails_body1',
            'help.topic_party_emails_body2',
        ],
    },
    {
        id: 'lease_wizard',
        category: 'leases',
        titleKey: 'help.topic_lease_wizard_title',
        bodyKeys: [
            'help.topic_lease_wizard_body1',
            'help.topic_lease_wizard_body2',
        ],
    },
    {
        id: 'creating_leases',
        category: 'leases',
        titleKey: 'help.topic_creating_leases_title',
        bodyKeys: [
            'help.topic_creating_leases_body1',
            'help.topic_creating_leases_body2',
        ],
    },
    {
        id: 'lease_lifecycle',
        category: 'leases',
        titleKey: 'help.topic_lease_lifecycle_title',
        bodyKeys: [
            'help.topic_lease_lifecycle_body1',
            'help.topic_lease_lifecycle_body2',
        ],
    },
    {
        id: 'rent_increments',
        category: 'leases',
        titleKey: 'help.topic_rent_increments_title',
        bodyKeys: [
            'help.topic_rent_increments_body1',
            'help.topic_rent_increments_body2',
        ],
    },
    {
        id: 'recording_payments',
        category: 'money_in',
        titleKey: 'help.topic_recording_payments_title',
        bodyKeys: [
            'help.topic_recording_payments_body1',
            'help.topic_recording_payments_body2',
        ],
    },
    {
        id: 'fifo',
        category: 'money_in',
        titleKey: 'help.topic_fifo_title',
        bodyKeys: [
            'help.topic_fifo_body1',
            'help.topic_fifo_body2',
        ],
    },
    {
        id: 'withdrawals_transfers',
        category: 'money_in',
        titleKey: 'help.topic_withdrawals_transfers_title',
        bodyKeys: [
            'help.topic_withdrawals_transfers_body1',
            'help.topic_withdrawals_transfers_body2',
        ],
    },
    {
        id: 'adjustments_documents',
        category: 'money_in',
        titleKey: 'help.topic_adjustments_documents_title',
        bodyKeys: [
            'help.topic_adjustments_documents_body1',
            'help.topic_adjustments_documents_body2',
        ],
    },
    {
        id: 'owner_entitlement',
        category: 'owners',
        titleKey: 'help.topic_owner_entitlement_title',
        bodyKeys: [
            'help.topic_owner_entitlement_body1',
            'help.topic_owner_entitlement_body2',
        ],
    },
    {
        id: 'owner_expenses',
        category: 'owners',
        titleKey: 'help.topic_owner_expenses_title',
        bodyKeys: [
            'help.topic_owner_expenses_body1',
            'help.topic_owner_expenses_body2',
        ],
    },
    {
        id: 'owner_payouts',
        category: 'owners',
        titleKey: 'help.topic_owner_payouts_title',
        bodyKeys: [
            'help.topic_owner_payouts_body1',
            'help.topic_owner_payouts_body2',
        ],
    },
    {
        id: 'automation',
        category: 'invoicing',
        titleKey: 'help.topic_automation_title',
        bodyKeys: [
            'help.topic_automation_body1',
            'help.topic_automation_body2',
        ],
    },
    {
        id: 'reports',
        category: 'reports',
        titleKey: 'help.topic_reports_title',
        bodyKeys: [
            'help.topic_reports_body1',
            'help.topic_reports_body2',
        ],
    },
    {
        id: 'financial_journal',
        category: 'journal',
        titleKey: 'help.topic_financial_journal_title',
        bodyKeys: [
            'help.topic_financial_journal_body1',
            'help.topic_financial_journal_body2',
            'help.topic_financial_journal_body3',
        ],
    },
    {
        id: 'activity_log',
        category: 'journal',
        titleKey: 'help.topic_activity_log_title',
        bodyKeys: [
            'help.topic_activity_log_body1',
            'help.topic_activity_log_body2',
        ],
    },
    {
        id: 'user_management',
        category: 'admin',
        titleKey: 'help.topic_user_management_title',
        bodyKeys: [
            'help.topic_user_management_body1',
            'help.topic_user_management_body2',
        ],
    },
    {
        id: 'organisation_settings',
        category: 'admin',
        titleKey: 'help.topic_organisation_settings_title',
        bodyKeys: [
            'help.topic_organisation_settings_body1',
        ],
    },
    {
        id: 'backup_restore',
        category: 'admin',
        titleKey: 'help.topic_backup_restore_title',
        bodyKeys: [
            'help.topic_backup_restore_body1',
            'help.topic_backup_restore_body2',
        ],
    },
];

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/

let helpSearchTimer =
    null;

let helpUpdatesLoaded =
    false;

export async function initializeHelp() {
    const workspace =
        document.getElementById(
            'help-workspace'
        );

    if (! workspace) {
        return;
    }

    initializeHelpTabs();
    initializeHelpFilters();

    applyHelpLocationHash();

    window.addEventListener(
        'hashchange',
        applyHelpLocationHash
    );

    renderHelpGuide();
}

/*
|--------------------------------------------------------------------------
| Tabs
|--------------------------------------------------------------------------
*/

function initializeHelpTabs() {
    document
        .getElementById(
            'help-tab-guide'
        )
        ?.addEventListener(
            'click',
            () => {
                window.history.replaceState(
                    null,
                    '',
                    window.location.pathname
                    + window.location.search
                );

                selectHelpTab(
                    'guide'
                );
            }
        );

    document
        .getElementById(
            'help-tab-updates'
        )
        ?.addEventListener(
            'click',
            () => {
                window.history.replaceState(
                    null,
                    '',
                    '#updates'
                );

                selectHelpTab(
                    'updates'
                );
            }
        );
}

function applyHelpLocationHash() {
    selectHelpTab(
        window.location.hash === '#updates'
            ? 'updates'
            : 'guide'
    );
}

function selectHelpTab(
    tab
) {
    const showingUpdates =
        tab === 'updates';

    const activeClasses = [
        'bg-[var(--pm-surface)]',
        'text-[var(--pm-text)]',
        'shadow-sm',
    ];

    const inactiveClasses = [
        'text-[var(--pm-text-muted)]',
        'hover:text-[var(--pm-text)]',
    ];

    const guideTab =
        document.getElementById(
            'help-tab-guide'
        );

    const updatesTab =
        document.getElementById(
            'help-tab-updates'
        );

    [
        [guideTab, ! showingUpdates],
        [updatesTab, showingUpdates],
    ].forEach(
        ([button, active]) => {
            if (! button) {
                return;
            }

            button.setAttribute(
                'aria-selected',
                active
                    ? 'true'
                    : 'false'
            );

            button.classList.remove(
                ...activeClasses,
                ...inactiveClasses
            );

            button.classList.add(
                ...(
                    active
                        ? activeClasses
                        : inactiveClasses
                )
            );
        }
    );

    document
        .getElementById(
            'help-guide-panel'
        )
        ?.classList.toggle(
            'hidden',
            showingUpdates
        );

    document
        .getElementById(
            'help-updates-panel'
        )
        ?.classList.toggle(
            'hidden',
            ! showingUpdates
        );

    document
        .getElementById(
            'help-guide-filters'
        )
        ?.classList.toggle(
            'hidden',
            showingUpdates
        );

    if (
        showingUpdates
        && ! helpUpdatesLoaded
    ) {
        helpUpdatesLoaded =
            true;

        loadHelpUpdates();
    }
}

/*
|--------------------------------------------------------------------------
| Guide Filters
|--------------------------------------------------------------------------
*/

function initializeHelpFilters() {
    document
        .getElementById(
            'help-search'
        )
        ?.addEventListener(
            'input',
            () => {
                clearTimeout(
                    helpSearchTimer
                );

                helpSearchTimer =
                    setTimeout(
                        renderHelpGuide,
                        150
                    );
            }
        );

    document
        .getElementById(
            'help-category'
        )
        ?.addEventListener(
            'change',
            renderHelpGuide
        );
}

/**
 * Normalize text for accent- and case-insensitive matching.
 *
 * @param {string} value
 * @returns {string}
 */
function normalizeHelpText(
    value
) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(
            /[̀-ͯ]/g,
            ''
        );
}

function helpTopicMatches(
    topic,
    query,
    category
) {
    if (
        category !== ''
        && topic.category !== category
    ) {
        return false;
    }

    if (query === '') {
        return true;
    }

    const haystack =
        normalizeHelpText(
            [
                translate(
                    topic.titleKey
                ),

                ...topic.bodyKeys.map(
                    (key) =>
                        translate(key)
                ),
            ].join(' ')
        );

    return query
        .split(/\s+/)
        .filter(Boolean)
        .every(
            (word) =>
                haystack.includes(
                    word
                )
        );
}

/*
|--------------------------------------------------------------------------
| Guide Rendering
|--------------------------------------------------------------------------
*/

function renderHelpGuide() {
    const container =
        document.getElementById(
            'help-guide-content'
        );

    if (! container) {
        return;
    }

    const query =
        normalizeHelpText(
            document.getElementById(
                'help-search'
            )?.value
            || ''
        ).trim();

    const category =
        document.getElementById(
            'help-category'
        )?.value
        || '';

    const matching =
        helpTopics.filter(
            (topic) =>
                helpTopicMatches(
                    topic,
                    query,
                    category
                )
        );

    if (matching.length === 0) {
        container.innerHTML = `
            <div
                class="
                    pm-card px-6 py-14
                    text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'help.no_results'
                        )
                    )}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'help.no_results_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        helpCategories
            .map(
                (categoryId) => {
                    const topics =
                        matching.filter(
                            (topic) =>
                                topic.category
                                === categoryId
                        );

                    if (topics.length === 0) {
                        return '';
                    }

                    return helpCategorySection(
                        categoryId,
                        topics
                    );
                }
            )
            .join('');
}

function helpCategorySection(
    categoryId,
    topics
) {
    return `
        <section class="mt-8 first:mt-2">
            <h2
                class="
                    text-xs font-semibold
                    uppercase tracking-[0.14em]
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        `help.category_${categoryId}`
                    )
                )}
            </h2>

            <div
                class="
                    mt-3 grid
                    grid-cols-1 gap-4
                "
            >
                ${topics
                    .map(helpTopicCard)
                    .join('')}
            </div>
        </section>
    `;
}

function helpTopicCard(
    topic
) {
    return `
        <article
            id="help-topic-${escapeHtml(topic.id)}"
            class="pm-card p-5"
        >
            <h3
                class="
                    text-base font-semibold
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        topic.titleKey
                    )
                )}
            </h3>

            ${topic.bodyKeys
                .map(
                    (key) => `
                        <p
                            class="
                                mt-3 text-sm
                                leading-6
                                text-[var(--pm-text-secondary)]
                            "
                        >
                            ${escapeHtml(
                                translate(key)
                            )}
                        </p>
                    `
                )
                .join('')}
        </article>
    `;
}

/*
|--------------------------------------------------------------------------
| Update Log
|--------------------------------------------------------------------------
*/

async function loadHelpUpdates() {
    const container =
        document.getElementById(
            'help-updates-content'
        );

    if (! container) {
        return;
    }

    hideHelpError();

    container.innerHTML = `
        <div
            class="
                px-5 py-12 text-center
                text-sm
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    'help.updates_loading'
                )
            )}
        </div>
    `;

    try {
        const response =
            await apiRequest(
                '/api/release-log'
            );

        const payload =
            await parseJsonResponse(
                response
            );

        container.innerHTML =
            helpUpdatesMarkup(
                payload
            );
    } catch (error) {
        container.innerHTML =
            '';

        /*
         * Allow a retry the next time the tab is opened.
         */
        helpUpdatesLoaded =
            false;

        showHelpError(
            error instanceof Error
                ? error.message
                : translate(
                    'help.unable_load_updates'
                )
        );
    }
}

function helpUpdatesMarkup(
    payload
) {
    const entries =
        Array.isArray(
            payload?.entries
        )
            ? payload.entries
            : [];

    return `
        <div
            class="
                rounded-xl border
                px-5 py-4
                border-[var(--pm-info-border)]
                bg-[var(--pm-info-background)]
            "
        >
            <div
                class="
                    text-sm font-semibold
                    text-[var(--pm-info-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        'help.current_version',
                        {
                            version:
                                payload?.current_version
                                || '',
                        }
                    )
                )}
            </div>
        </div>

        <div
            class="
                mt-6 space-y-6
                border-l-2
                border-[var(--pm-border)]
                pl-5
                sm:pl-6
            "
        >
            ${entries
                .map(helpReleaseCard)
                .join('')}
        </div>
    `;
}

function helpReleaseCard(
    entry
) {
    const changes =
        Array.isArray(
            entry?.changes
        )
            ? entry.changes
            : [];

    return `
        <article class="relative">
            <span
                class="
                    absolute top-6
                    -left-[25px] h-3 w-3
                    rounded-full border-2
                    border-[var(--pm-border-strong)]
                    bg-[var(--pm-surface)]
                    sm:-left-[29px]
                "
                aria-hidden="true"
            ></span>

            <div class="pm-card p-5">
                <div
                    class="
                        flex flex-wrap
                        items-baseline gap-x-3
                        gap-y-1
                    "
                >
                    <span
                        class="
                            font-mono text-sm
                            font-semibold
                            text-[var(--pm-accent)]
                        "
                    >
                        v${escapeHtml(
                            entry?.version
                            || ''
                        )}
                    </span>

                    <span
                        class="
                            text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            formatHelpReleaseDate(
                                entry?.date
                            )
                        )}
                    </span>
                </div>

                <h3
                    class="
                        mt-2 text-base
                        font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        entry?.title
                        || ''
                    )}
                </h3>

                <ul
                    class="
                        mt-3 list-disc
                        space-y-1.5 pl-5
                        text-sm leading-6
                        text-[var(--pm-text-secondary)]
                    "
                >
                    ${changes
                        .map(
                            (change) => `
                                <li>
                                    ${escapeHtml(
                                        change
                                    )}
                                </li>
                            `
                        )
                        .join('')}
                </ul>
            </div>
        </article>
    `;
}

function formatHelpReleaseDate(
    value
) {
    if (! value) {
        return '';
    }

    const parts =
        String(value)
            .slice(0, 10)
            .split('-');

    if (parts.length !== 3) {
        return String(value);
    }

    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showHelpError(
    message
) {
    const element =
        document.getElementById(
            'help-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        message;

    element.classList.remove(
        'hidden'
    );
}

function hideHelpError() {
    const element =
        document.getElementById(
            'help-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}

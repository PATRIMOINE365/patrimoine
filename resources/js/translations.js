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

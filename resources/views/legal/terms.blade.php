@extends('layouts.legal')

@section('page_title', 'Terms of Service')

@section('content_en')
    <h1>Terms of Service</h1>
    <p class="pm-legal-meta">Version {{ config('legal.terms_version') }} · Effective 26 August 2026</p>

    <h2>1. Who we are</h2>
    <p>
        Patrimoine 365 — including the website, the application programming interfaces (APIs)
        and the applications (together, the "Service") — is owned and operated by
        <strong>{{ config('legal.company.name') }}</strong>, {{ config('legal.company.address') }}
        ("Kality", "we", "us"). You can reach us at
        <a href="mailto:{{ config('legal.company.email') }}">{{ config('legal.company.email') }}</a>
        and, for matters concerning the Service, at
        <a href="mailto:{{ config('legal.mailboxes.legal') }}">{{ config('legal.mailboxes.legal') }}</a>.
    </p>

    <h2>2. Acceptance of these terms</h2>
    <p>
        By creating an organisation on the Service, ticking the acceptance box at signup, or
        using the Service in any way, you agree to these Terms of Service and to our
        <a href="/privacy">Privacy Policy</a> on your own behalf and on behalf of the
        organisation you represent. If you do not agree, do not use the Service.
    </p>

    <h2>3. The Service</h2>
    <p>
        Patrimoine 365 is a property management and property accounting platform: buildings,
        units, leases, tenants, owners, invoicing, payments, tenant funds, owner accounts,
        financial journal, reporting and related document generation. Each customer
        organisation's data is kept strictly separated from every other organisation's data.
    </p>

    <h2>4. Accounts and security</h2>
    <ul>
        <li>You must provide accurate information at signup and keep it up to date.</li>
        <li>Every account requires a verified email address and, at each sign-in, a one-time
            security code delivered by email (multi-factor authentication).</li>
        <li>You are responsible for safeguarding credentials used by your organisation's
            users and for all activity performed under your organisation's accounts.</li>
        <li>Notify us immediately at
            <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>
            if you suspect unauthorised access.</li>
    </ul>

    <h2>5. Plans, trial and licensing</h2>
    <ul>
        <li>New organisations start with a 30-day trial of the Professional plan; no payment
            card is required. After the trial, the organisation continues on the Free plan
            unless a subscription is arranged.</li>
        <li>Plan features, limits and monthly messaging allowances are described on the
            Service's licence page. Exceeding a limit prevents creating new records of the
            limited kind; existing data is never locked, degraded or deleted because of a
            plan change.</li>
        <li>Messaging (email and SMS) beyond a plan's monthly allowance is not carried over
            and is not queued for later delivery.</li>
        <li>For subscriptions, extensions and pricing, contact
            <a href="mailto:{{ config('legal.mailboxes.billing') }}">{{ config('legal.mailboxes.billing') }}</a>.</li>
    </ul>

    <h2>6. Your data and your responsibilities</h2>
    <ul>
        <li>Your organisation's data belongs to your organisation. We process it only to
            provide the Service, as described in the Privacy Policy.</li>
        <li>You are responsible for the lawfulness of the personal data (for example tenant,
            owner and agent details) that your organisation records in the Service, and for
            having the right to use it.</li>
        <li>You can export your data (backups, registers and reports) from within the
            Service at any time.</li>
    </ul>

    <h2>7. Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>use the Service for unlawful purposes or to send unlawful, deceptive or
            unsolicited communications;</li>
        <li>attempt to access another organisation's data or probe, scan or test the
            vulnerability of the Service without written authorisation;</li>
        <li>resell, sublicense or provide the Service to third parties except to your own
            organisation's users, tenants, owners and agents as the Service intends;</li>
        <li>interfere with the integrity or performance of the Service.</li>
    </ul>

    <h2>8. Availability and support</h2>
    <p>
        We work to keep the Service available and correct, but the Service is provided
        "as is" and "as available", without warranties of any kind to the extent permitted
        by law. Support is provided according to your plan, through
        <a href="mailto:{{ config('legal.mailboxes.support') }}">{{ config('legal.mailboxes.support') }}</a>.
    </p>

    <h2>9. Intellectual property</h2>
    <p>
        The Service, its software, design and branding are and remain the property of
        Kality Ltd. These terms grant your organisation a limited, non-exclusive,
        non-transferable right to use the Service for its internal property management
        during the period it is entitled to a plan.
    </p>

    <h2>10. Liability</h2>
    <p>
        To the maximum extent permitted by law, Kality Ltd is not liable for indirect,
        incidental, special or consequential damages, nor for loss of profits, revenue or
        data, arising from the use of or inability to use the Service. Our total aggregate
        liability for any claim related to the Service is limited to the amounts paid by
        your organisation for the Service in the twelve months preceding the claim.
        Nothing in these terms excludes liability that cannot be excluded by law.
    </p>

    <h2>11. Suspension and termination</h2>
    <ul>
        <li>You may stop using the Service at any time and request deletion of your
            organisation's data.</li>
        <li>We may suspend an organisation for material breach of these terms, for security
            reasons, or for non-payment of an agreed subscription; suspension preserves the
            organisation's data.</li>
        <li>We will give reasonable notice before terminating the Service or an
            organisation's access, except where immediate action is required by law or
            security.</li>
    </ul>

    <h2>12. Changes to these terms</h2>
    <p>
        We may update these terms. Material changes are announced through the Service, and
        the version identifier at the top of this page changes. Continued use after a
        change takes effect constitutes acceptance of the updated terms.
    </p>

    <h2>13. Governing law</h2>
    <p>
        These terms are governed by the laws of the Republic of Ghana. Disputes are subject
        to the exclusive jurisdiction of the courts of Ghana, without prejudice to
        mandatory consumer protections of your place of residence.
    </p>

    <h2>14. Contact</h2>
    <p>
        Legal notices:
        <a href="mailto:{{ config('legal.mailboxes.legal') }}">{{ config('legal.mailboxes.legal') }}</a> ·
        Support:
        <a href="mailto:{{ config('legal.mailboxes.support') }}">{{ config('legal.mailboxes.support') }}</a> ·
        Billing:
        <a href="mailto:{{ config('legal.mailboxes.billing') }}">{{ config('legal.mailboxes.billing') }}</a> ·
        Security:
        <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>
    </p>
@endsection

@section('content_fr')
    <h1>Conditions Générales d'Utilisation</h1>
    <p class="pm-legal-meta">Version {{ config('legal.terms_version') }} · En vigueur le 26 août 2026</p>

    <h2>1. Qui sommes-nous</h2>
    <p>
        Patrimoine 365 — y compris le site web, les interfaces de programmation (API) et les
        applications (ensemble, le « Service ») — est détenu et exploité par
        <strong>{{ config('legal.company.name') }}</strong>, {{ config('legal.company.address') }}
        (« Kality », « nous »). Vous pouvez nous joindre à
        <a href="mailto:{{ config('legal.company.email') }}">{{ config('legal.company.email') }}</a>
        et, pour les questions relatives au Service, à
        <a href="mailto:{{ config('legal.mailboxes.legal') }}">{{ config('legal.mailboxes.legal') }}</a>.
    </p>

    <h2>2. Acceptation des conditions</h2>
    <p>
        En créant une organisation sur le Service, en cochant la case d'acceptation lors de
        l'inscription ou en utilisant le Service de quelque manière que ce soit, vous
        acceptez les présentes Conditions ainsi que notre
        <a href="/privacy">Politique de Confidentialité</a>, en votre nom et au nom de
        l'organisation que vous représentez. Si vous n'êtes pas d'accord, n'utilisez pas le
        Service.
    </p>

    <h2>3. Le Service</h2>
    <p>
        Patrimoine 365 est une plateforme de gestion immobilière et de comptabilité
        immobilière : immeubles, unités, baux, locataires, propriétaires, facturation,
        paiements, fonds locataires, comptes propriétaires, journal financier, rapports et
        génération de documents. Les données de chaque organisation cliente sont strictement
        séparées de celles de toute autre organisation.
    </p>

    <h2>4. Comptes et sécurité</h2>
    <ul>
        <li>Vous devez fournir des informations exactes lors de l'inscription et les tenir à
            jour.</li>
        <li>Chaque compte nécessite une adresse e-mail vérifiée et, à chaque connexion, un
            code de sécurité à usage unique envoyé par e-mail (authentification
            multifacteur).</li>
        <li>Vous êtes responsable de la protection des identifiants des utilisateurs de
            votre organisation et de toute activité effectuée depuis ses comptes.</li>
        <li>Signalez-nous immédiatement tout accès non autorisé à
            <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>.</li>
    </ul>

    <h2>5. Forfaits, essai et licences</h2>
    <ul>
        <li>Toute nouvelle organisation bénéficie d'un essai de 30 jours du forfait
            Professionnel, sans carte bancaire. À l'issue de l'essai, l'organisation
            continue sur le forfait Gratuit sauf souscription d'un abonnement.</li>
        <li>Les fonctionnalités, limites et quotas mensuels de messagerie de chaque forfait
            sont décrits sur la page Licence du Service. Le dépassement d'une limite
            empêche la création de nouveaux enregistrements du type concerné ; les données
            existantes ne sont jamais verrouillées, dégradées ni supprimées du fait d'un
            changement de forfait.</li>
        <li>Les messages (e-mail et SMS) au-delà du quota mensuel ne sont ni reportés ni mis
            en file d'attente.</li>
        <li>Pour les abonnements, prolongations et tarifs, contactez
            <a href="mailto:{{ config('legal.mailboxes.billing') }}">{{ config('legal.mailboxes.billing') }}</a>.</li>
    </ul>

    <h2>6. Vos données et vos responsabilités</h2>
    <ul>
        <li>Les données de votre organisation lui appartiennent. Nous ne les traitons que
            pour fournir le Service, comme décrit dans la Politique de Confidentialité.</li>
        <li>Vous êtes responsable de la licéité des données personnelles (par exemple les
            coordonnées des locataires, propriétaires et agents) que votre organisation
            enregistre dans le Service.</li>
        <li>Vous pouvez exporter vos données (sauvegardes, registres et rapports) depuis le
            Service à tout moment.</li>
    </ul>

    <h2>7. Utilisation acceptable</h2>
    <p>Vous vous engagez à ne pas :</p>
    <ul>
        <li>utiliser le Service à des fins illicites ou pour envoyer des communications
            illicites, trompeuses ou non sollicitées ;</li>
        <li>tenter d'accéder aux données d'une autre organisation ni sonder, analyser ou
            tester la vulnérabilité du Service sans autorisation écrite ;</li>
        <li>revendre, sous-licencier ou fournir le Service à des tiers, hormis aux
            utilisateurs, locataires, propriétaires et agents de votre organisation comme
            le Service le prévoit ;</li>
        <li>porter atteinte à l'intégrité ou aux performances du Service.</li>
    </ul>

    <h2>8. Disponibilité et assistance</h2>
    <p>
        Nous œuvrons à maintenir le Service disponible et exact, mais il est fourni « en
        l'état » et « selon disponibilité », sans garantie d'aucune sorte dans la mesure
        permise par la loi. L'assistance est fournie selon votre forfait via
        <a href="mailto:{{ config('legal.mailboxes.support') }}">{{ config('legal.mailboxes.support') }}</a>.
    </p>

    <h2>9. Propriété intellectuelle</h2>
    <p>
        Le Service, son logiciel, son design et sa marque restent la propriété de Kality
        Ltd. Les présentes conditions confèrent à votre organisation un droit limité, non
        exclusif et non transférable d'utiliser le Service pour sa gestion immobilière
        interne pendant la durée de son forfait.
    </p>

    <h2>10. Responsabilité</h2>
    <p>
        Dans la mesure maximale permise par la loi, Kality Ltd n'est pas responsable des
        dommages indirects, accessoires, spéciaux ou consécutifs, ni des pertes de
        bénéfices, de revenus ou de données résultant de l'utilisation ou de
        l'impossibilité d'utiliser le Service. Notre responsabilité totale cumulée est
        limitée aux montants payés par votre organisation pour le Service au cours des
        douze mois précédant la réclamation. Rien dans les présentes n'exclut une
        responsabilité qui ne peut l'être légalement.
    </p>

    <h2>11. Suspension et résiliation</h2>
    <ul>
        <li>Vous pouvez cesser d'utiliser le Service à tout moment et demander la
            suppression des données de votre organisation.</li>
        <li>Nous pouvons suspendre une organisation en cas de manquement grave aux présentes
            conditions, pour des raisons de sécurité ou en cas de non-paiement d'un
            abonnement convenu ; la suspension préserve les données de l'organisation.</li>
        <li>Nous donnerons un préavis raisonnable avant de mettre fin au Service ou à
            l'accès d'une organisation, sauf si la loi ou la sécurité exige une action
            immédiate.</li>
    </ul>

    <h2>12. Modification des conditions</h2>
    <p>
        Nous pouvons mettre à jour les présentes conditions. Les changements importants sont
        annoncés via le Service et l'identifiant de version en haut de cette page change.
        La poursuite de l'utilisation après l'entrée en vigueur d'un changement vaut
        acceptation des conditions mises à jour.
    </p>

    <h2>13. Droit applicable</h2>
    <p>
        Les présentes conditions sont régies par le droit de la République du Ghana. Les
        litiges relèvent de la compétence exclusive des tribunaux du Ghana, sans préjudice
        des protections impératives des consommateurs de votre lieu de résidence.
    </p>

    <h2>14. Contact</h2>
    <p>
        Notifications juridiques :
        <a href="mailto:{{ config('legal.mailboxes.legal') }}">{{ config('legal.mailboxes.legal') }}</a> ·
        Assistance :
        <a href="mailto:{{ config('legal.mailboxes.support') }}">{{ config('legal.mailboxes.support') }}</a> ·
        Facturation :
        <a href="mailto:{{ config('legal.mailboxes.billing') }}">{{ config('legal.mailboxes.billing') }}</a> ·
        Sécurité :
        <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>
    </p>
@endsection

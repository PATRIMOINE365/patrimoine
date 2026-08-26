@extends('layouts.legal')

@section('page_title', 'Privacy Policy')

@section('content_en')
    <h1>Privacy Policy</h1>
    <p class="pm-legal-meta">Version {{ config('legal.privacy_version') }} · Effective 26 August 2026</p>

    <h2>1. Who is responsible</h2>
    <p>
        Patrimoine 365 (the website, APIs and applications — the "Service") is operated by
        <strong>{{ config('legal.company.name') }}</strong>, {{ config('legal.company.address') }}.
        For any privacy matter, contact
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a>.
    </p>
    <p>
        Two roles matter in this policy. For account and platform data (who signed up, plan,
        security logs), Kality Ltd is the data controller. For the business records a
        customer organisation keeps in the Service (its tenants, owners, agents, leases and
        payments), the customer organisation is the controller and Kality Ltd processes that
        data only on the organisation's behalf.
    </p>

    <h2>2. What we collect</h2>
    <ul>
        <li><strong>Account data</strong> — name, email address, optional phone number,
            organisation name, hashed password, language preference, accepted terms
            version.</li>
        <li><strong>Security data</strong> — sign-in and verification events, one-time code
            challenges, IP address, browser and device family, kept in the activity log so
            organisations can audit access to their own data.</li>
        <li><strong>Customer business data</strong> — the property, party and financial
            records your organisation enters. We never use this data for our own
            purposes.</li>
        <li><strong>Technical data</strong> — server logs necessary to operate and secure
            the Service.</li>
    </ul>
    <p>We do not use advertising trackers and we do not sell personal data.</p>

    <h2>3. Why we process it</h2>
    <ul>
        <li>to provide, operate and secure the Service (performance of contract);</li>
        <li>to authenticate sign-ins with emailed one-time codes (security);</li>
        <li>to send transactional email your organisation triggers — invoices, receipts,
            vouchers, reminders (performance of contract);</li>
        <li>to communicate about the account, plans and important changes (legitimate
            interest);</li>
        <li>to comply with legal obligations.</li>
    </ul>

    <h2>4. Cookies and local storage</h2>
    <p>
        The Service uses only what it needs to function: a session cookie for technical
        security and the browser's local storage for your interface preferences (theme,
        language) and your signed-in session. No third-party advertising or analytics
        cookies are used.
    </p>

    <h2>5. Where data lives and who sees it</h2>
    <ul>
        <li>Data is stored on managed servers operated for Kality Ltd by hosting providers
            acting as processors.</li>
        <li>Transactional email is delivered through an email delivery provider acting as a
            processor.</li>
        <li>Each customer organisation's data is isolated: it is never visible to any other
            organisation.</li>
        <li>We disclose data to authorities only where the law requires it.</li>
    </ul>

    <h2>6. How long we keep it</h2>
    <ul>
        <li>Account and business data: for as long as the organisation's account exists.
            Financial records are designed to be immutable inside the Service for audit
            integrity; an organisation may nonetheless request full deletion of its
            account and data.</li>
        <li>Security and server logs: kept for a limited period appropriate to security
            and legal needs, then deleted.</li>
    </ul>

    <h2>7. Your rights</h2>
    <p>
        Subject to applicable law — including Ghana's Data Protection Act, 2012 (Act 843) —
        you may request access to, correction of, or deletion of your personal data, or
        object to certain processing. Write to
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a>;
        we answer as quickly as possible and at the latest within the period the law
        allows. If your data was entered by a customer organisation (for example you are a
        tenant of an agency using the Service), we will refer your request to that
        organisation, which controls it.
    </p>

    <h2>8. Security</h2>
    <ul>
        <li>passwords stored only as strong one-way hashes;</li>
        <li>multi-factor authentication on every sign-in;</li>
        <li>strict per-organisation data isolation enforced at several independent layers;</li>
        <li>encrypted transport (HTTPS) everywhere;</li>
        <li>audit trail of meaningful actions available to each organisation.</li>
    </ul>
    <p>
        To report a vulnerability, contact
        <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>.
    </p>

    <h2>9. Changes to this policy</h2>
    <p>
        We may update this policy; material changes are announced through the Service and
        the version identifier above changes.
    </p>

    <h2>10. Contact</h2>
    <p>
        Privacy requests:
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a> ·
        General:
        <a href="mailto:{{ config('legal.company.email') }}">{{ config('legal.company.email') }}</a>
    </p>
@endsection

@section('content_fr')
    <h1>Politique de Confidentialité</h1>
    <p class="pm-legal-meta">Version {{ config('legal.privacy_version') }} · En vigueur le 26 août 2026</p>

    <h2>1. Qui est responsable</h2>
    <p>
        Patrimoine 365 (le site web, les API et les applications — le « Service ») est
        exploité par <strong>{{ config('legal.company.name') }}</strong>,
        {{ config('legal.company.address') }}. Pour toute question relative à la vie privée,
        contactez
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a>.
    </p>
    <p>
        Deux rôles sont à distinguer. Pour les données de compte et de plateforme (qui
        s'est inscrit, forfait, journaux de sécurité), Kality Ltd est responsable du
        traitement. Pour les données métier qu'une organisation cliente conserve dans le
        Service (ses locataires, propriétaires, agents, baux et paiements), l'organisation
        cliente est responsable du traitement et Kality Ltd n'agit que comme sous-traitant
        pour son compte.
    </p>

    <h2>2. Ce que nous collectons</h2>
    <ul>
        <li><strong>Données de compte</strong> — nom, adresse e-mail, téléphone facultatif,
            nom de l'organisation, mot de passe haché, langue, version des conditions
            acceptées.</li>
        <li><strong>Données de sécurité</strong> — événements de connexion et de
            vérification, codes à usage unique, adresse IP, navigateur et type d'appareil,
            conservés dans le journal d'activité pour permettre à chaque organisation
            d'auditer l'accès à ses propres données.</li>
        <li><strong>Données métier des clients</strong> — les enregistrements immobiliers,
            de tiers et financiers saisis par votre organisation. Nous ne les utilisons
            jamais pour notre propre compte.</li>
        <li><strong>Données techniques</strong> — journaux serveur nécessaires à
            l'exploitation et à la sécurité du Service.</li>
    </ul>
    <p>Nous n'utilisons pas de traceurs publicitaires et ne vendons pas de données personnelles.</p>

    <h2>3. Pourquoi nous les traitons</h2>
    <ul>
        <li>fournir, exploiter et sécuriser le Service (exécution du contrat) ;</li>
        <li>authentifier les connexions par codes à usage unique envoyés par e-mail
            (sécurité) ;</li>
        <li>envoyer les e-mails transactionnels déclenchés par votre organisation —
            factures, reçus, justificatifs, rappels (exécution du contrat) ;</li>
        <li>communiquer au sujet du compte, des forfaits et des changements importants
            (intérêt légitime) ;</li>
        <li>respecter nos obligations légales.</li>
    </ul>

    <h2>4. Cookies et stockage local</h2>
    <p>
        Le Service n'utilise que le strict nécessaire à son fonctionnement : un cookie de
        session à des fins de sécurité technique et le stockage local du navigateur pour
        vos préférences d'interface (thème, langue) et votre session connectée. Aucun
        cookie publicitaire ou d'analyse tiers n'est utilisé.
    </p>

    <h2>5. Où résident les données et qui les voit</h2>
    <ul>
        <li>Les données sont stockées sur des serveurs gérés pour Kality Ltd par des
            hébergeurs agissant comme sous-traitants.</li>
        <li>Les e-mails transactionnels sont acheminés par un prestataire d'envoi d'e-mails
            agissant comme sous-traitant.</li>
        <li>Les données de chaque organisation cliente sont isolées : elles ne sont jamais
            visibles d'une autre organisation.</li>
        <li>Nous ne divulguons des données aux autorités que lorsque la loi l'exige.</li>
    </ul>

    <h2>6. Durée de conservation</h2>
    <ul>
        <li>Données de compte et métier : tant que le compte de l'organisation existe. Les
            enregistrements financiers sont immuables dans le Service pour garantir
            l'intégrité d'audit ; une organisation peut néanmoins demander la suppression
            complète de son compte et de ses données.</li>
        <li>Journaux de sécurité et serveur : conservés pendant une durée limitée adaptée
            aux besoins de sécurité et aux obligations légales, puis supprimés.</li>
    </ul>

    <h2>7. Vos droits</h2>
    <p>
        Sous réserve du droit applicable — notamment la loi ghanéenne sur la protection des
        données de 2012 (Act 843) — vous pouvez demander l'accès à vos données, leur
        rectification ou leur suppression, ou vous opposer à certains traitements.
        Écrivez à
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a> ;
        nous répondons aussi vite que possible et au plus tard dans le délai légal. Si vos
        données ont été saisies par une organisation cliente (par exemple vous êtes
        locataire d'une agence utilisant le Service), nous transmettrons votre demande à
        cette organisation, qui en est responsable.
    </p>

    <h2>8. Sécurité</h2>
    <ul>
        <li>mots de passe stockés uniquement sous forme de hachages robustes à sens
            unique ;</li>
        <li>authentification multifacteur à chaque connexion ;</li>
        <li>isolation stricte des données par organisation, appliquée à plusieurs niveaux
            indépendants ;</li>
        <li>chiffrement des échanges (HTTPS) partout ;</li>
        <li>journal d'audit des actions significatives à la disposition de chaque
            organisation.</li>
    </ul>
    <p>
        Pour signaler une vulnérabilité :
        <a href="mailto:{{ config('legal.mailboxes.security') }}">{{ config('legal.mailboxes.security') }}</a>.
    </p>

    <h2>9. Modifications de la politique</h2>
    <p>
        Nous pouvons mettre à jour la présente politique ; les changements importants sont
        annoncés via le Service et l'identifiant de version ci-dessus change.
    </p>

    <h2>10. Contact</h2>
    <p>
        Demandes vie privée :
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a> ·
        Général :
        <a href="mailto:{{ config('legal.company.email') }}">{{ config('legal.company.email') }}</a>
    </p>
@endsection

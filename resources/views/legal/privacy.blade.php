@extends('layouts.legal')

@section('page_title', 'Privacy Policy')

@section('content_en')
    <h1>Privacy Policy</h1>
    <p class="pm-legal-meta">Version {{ config('legal.privacy_version') }} · Effective 29 August 2026</p>

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
        <li>to communicate about the account, plans and important changes — our
            legitimate interest in telling customers about the service they are
            paying for, which we consider not to override anybody's interests
            because it concerns only their own account;</li>
        <li>to comply with legal obligations.</li>
    </ul>

    <h2>4. Cookies and local storage</h2>
    <p>
        The Service uses only what it needs to work, and nothing that follows you:
    </p>
    <ul>
        <li>a session cookie, for technical security;</li>
        <li>your browser's local storage, for the settings you chose — light or dark,
            language, and how many rows a list should show;</li>
        <li>your browser's session storage, for the token that keeps you signed in
            until you close the tab;</li>
        <li>on {{ config('legal.product.domain') }}, a service worker that lets the
            public pages load again on a poor connection.</li>
    </ul>
    <p>
        There are no advertising cookies, no analytics, no tracking pixels and no
        third-party scripts of any kind. <strong>We show no cookie banner, and that is
        deliberate:</strong> a banner exists to ask for consent, and everything listed
        above is either strictly necessary or a setting you asked for. Asking permission
        for a preference you set yourself would be theatre rather than protection.
    </p>

    <h2>5. Where data lives, and who else touches it</h2>
    <p>
        The Service runs on servers in <strong>France</strong>, operated for Kality Ltd by
        a hosting provider acting on our instructions. Our own staff, in Ghana, can reach
        that data to support you; every such access is written to the activity log your
        organisation can read.
    </p>
    <p>
        We use a small number of companies to run the Service. They act only on our
        instructions and may not use anything they hold for their own purposes:
    </p>
    <table>
        <thead>
            <tr><th>Who</th><th>What they do</th><th>Where</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Our hosting provider</td>
                <td>Runs the servers and the database</td>
                <td>France</td>
            </tr>
            <tr>
                <td>Resend</td>
                <td>Delivers the e-mail the Service sends — sign-in codes, invoices,
                    receipts, reminders</td>
                <td>United States</td>
            </tr>
        </tbody>
    </table>
    <p>
        We will publish any change to that list here before it takes effect. Beyond it:
    </p>
    <ul>
        <li>Each customer organisation's data is isolated: it is never visible to any
            other organisation.</li>
        <li>We do not sell personal data, and we do not share it for anybody's
            advertising.</li>
        <li>We disclose data to authorities only where the law requires it.</li>
    </ul>

    <h2>6. How long we keep it</h2>
    <ul>
        <li><strong>Account and business data:</strong> for as long as the organisation's
            account exists. When an administrator closes the account, everything in it is
            destroyed immediately and permanently — the properties, the parties, the
            leases, the invoices, the payments, the financial journal and every user
            account. We keep no copy.</li>
        <li><strong>The activity log:</strong> kept <em>indefinitely</em>. It records who
            did what, when, and from where, and it is append-only — nothing in it can be
            edited or removed, by you or by us. We keep it for as long as the account
            exists because it is the record that lets an organisation, an auditor or a
            court establish afterwards what actually happened. It dies with the account.</li>
        <li><strong>Financial records:</strong> kept for as long as the account exists.
            They are immutable inside the Service by design: a correction is written as
            its own entry rather than by altering the original, because a ledger you can
            quietly edit is not a ledger.</li>
        <li><strong>E-mail we send:</strong> we keep no copy of the message. That it was
            sent, and when, is in the activity log; the message itself lives only in the
            recipient's inbox.</li>
        <li><strong>One-time sign-in codes:</strong> stored hashed and valid for minutes.</li>
    </ul>

    <h2>7. Your rights</h2>
    <p>
        Kality Ltd is a Ghanaian company and the Service is offered in West Africa, so
        Ghana's Data Protection Act, 2012 (Act 843) is the law that governs it. We have
        nonetheless built the Service to the standard the European Union's General Data
        Protection Regulation sets, because it is the higher one. What follows is
        available to you whether or not the GDPR applies to you.
    </p>
    <ul>
        <li><strong>To know what is held about you, and to have a copy.</strong> If you
            have an account, press <em>Download my data</em> in your profile and you will
            get the whole of it as a file, immediately and without asking anybody. If your
            details were entered by an organisation using the Service — if you are a
            tenant or an owner — ask that organisation: it can produce everything held
            about you at the press of a button.</li>
        <li><strong>To correct what is wrong.</strong> Edit it, or ask the organisation
            that entered it to.</li>
        <li><strong>To be forgotten.</strong> An organisation can erase a tenant, owner or
            agent from the Service. Their name, e-mail address, telephone numbers, postal
            address, identity and registration numbers, bank details and any notes are
            destroyed permanently. The invoices, payments and journal entries remain,
            referring to that person only by a reference and never by name, because the
            law that requires accounting records to be kept is the same law that lets us
            refuse to destroy them. An administrator can close an entire account, which
            destroys everything without exception.</li>
        <li><strong>To take your data elsewhere.</strong> Every report exports as PDF,
            XLSX or CSV; the registry exports as a workbook; and an administrator can
            download the whole organisation as one structured file.</li>
        <li><strong>To object, and to restrict.</strong> You can switch off everything the
            Service sends to a party, individually or for everybody at once.</li>
        <li><strong>To complain.</strong> Write to us first — we would rather fix it —
            and you may also complain to Ghana's Data Protection Commission.</li>
    </ul>
    <p>
        Write to
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a>.
        <strong>We answer within 30 days</strong>, and sooner where we can. If your data
        was entered by a customer organisation, that organisation controls it and decides
        the request; we will pass it on promptly and help them answer it.
    </p>
    <p>
        No decision about you is made by automated means, and we do not profile anybody.
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
    <p>
        <strong>If something goes wrong.</strong> Should personal data be exposed, lost or
        altered without authorisation, we will tell the affected customer organisations
        <strong>within 72 hours</strong> of becoming aware of it, describing what happened,
        what data was involved, what we have done and what we advise. Where the risk to
        people is high and we hold the relationship, we will tell them directly too.
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
    <p class="pm-legal-meta">Version {{ config('legal.privacy_version') }} · En vigueur le 29 août 2026</p>

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
        Le Service n'utilise que ce dont il a besoin pour fonctionner, et rien qui vous
        suive :
    </p>
    <ul>
        <li>un cookie de session, pour la sécurité technique ;</li>
        <li>le stockage local de votre navigateur, pour les réglages que vous avez
            choisis — clair ou sombre, langue, et le nombre de lignes d'une liste ;</li>
        <li>le stockage de session de votre navigateur, pour le jeton qui vous garde
            connecté jusqu'à la fermeture de l'onglet ;</li>
        <li>sur {{ config('legal.product.domain') }}, un service worker qui permet aux
            pages publiques de se recharger sur une connexion médiocre.</li>
    </ul>
    <p>
        Aucun cookie publicitaire, aucune mesure d'audience, aucun pixel de suivi, aucun
        script tiers. <strong>Nous n'affichons aucune bannière cookies, et c'est
        délibéré :</strong> une bannière sert à demander un consentement, et tout ce qui
        précède est soit strictement nécessaire, soit un réglage que vous avez vous-même
        choisi. Demander l'autorisation pour une préférence que vous avez définie
        vous-même relèverait du théâtre, pas de la protection.
    </p>

    <h2>5. Où résident les données, et qui d'autre y touche</h2>
    <p>
        Le Service fonctionne sur des serveurs situés en <strong>France</strong>, exploités
        pour le compte de Kality Ltd par un hébergeur agissant sur nos instructions. Notre
        propre personnel, au Ghana, peut accéder à ces données pour vous assister ; chacun
        de ces accès est inscrit au journal d'activité que votre organisation peut lire.
    </p>
    <p>
        Nous recourons à un petit nombre de sociétés pour faire fonctionner le Service.
        Elles agissent uniquement sur nos instructions et ne peuvent utiliser ce qu'elles
        détiennent à leurs propres fins :
    </p>
    <table>
        <thead>
            <tr><th>Qui</th><th>Rôle</th><th>Où</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Notre hébergeur</td>
                <td>Exploite les serveurs et la base de données</td>
                <td>France</td>
            </tr>
            <tr>
                <td>Resend</td>
                <td>Achemine les e-mails du Service — codes de connexion, factures,
                    reçus, rappels</td>
                <td>États-Unis</td>
            </tr>
        </tbody>
    </table>
    <p>
        Toute modification de cette liste sera publiée ici avant de prendre effet.
    </p>
    <ul>
        <li>Les données de chaque organisation cliente sont isolées : elles ne sont jamais
            visibles d'une autre organisation.</li>
        <li>Nous ne vendons aucune donnée personnelle et n'en partageons aucune à des fins
            publicitaires.</li>
        <li>Nous ne divulguons des données aux autorités que lorsque la loi l'exige.</li>
    </ul>

    <h2>6. Durée de conservation</h2>
    <ul>
        <li><strong>Données de compte et métier :</strong> tant que le compte de
            l'organisation existe. Lorsqu'un administrateur ferme le compte, tout ce qu'il
            contient est détruit immédiatement et définitivement — les biens, les parties,
            les baux, les factures, les paiements, le journal financier et chaque compte
            utilisateur. Nous n'en conservons aucune copie.</li>
        <li><strong>Le journal d'activité :</strong> conservé <em>indéfiniment</em>. Il
            enregistre qui a fait quoi, quand et depuis où, et ne fait que s'ajouter :
            rien n'y peut être modifié ni supprimé, ni par vous ni par nous. Nous le
            conservons tant que le compte existe parce que c'est le registre qui permet à
            une organisation, à un auditeur ou à un tribunal d'établir après coup ce qui
            s'est réellement passé. Il disparaît avec le compte.</li>
        <li><strong>Les enregistrements financiers :</strong> conservés tant que le compte
            existe. Ils sont immuables par construction : une correction s'écrit comme une
            opération à part entière plutôt qu'en modifiant l'originale, car un grand
            livre que l'on peut retoucher discrètement n'en est pas un.</li>
        <li><strong>Les e-mails envoyés :</strong> nous n'en conservons aucune copie. Le
            fait qu'un message ait été envoyé, et quand, figure au journal d'activité ; le
            message lui-même ne réside que dans la boîte du destinataire.</li>
        <li><strong>Les codes de connexion à usage unique :</strong> stockés hachés et
            valables quelques minutes.</li>
    </ul>

    <h2>7. Vos droits</h2>
    <p>
        Kality Ltd est une société ghanéenne et le Service est proposé en Afrique de
        l'Ouest : c'est donc la loi ghanéenne sur la protection des données de 2012
        (Act 843) qui le régit. Nous avons néanmoins conçu le Service selon le standard
        fixé par le Règlement général sur la protection des données de l'Union européenne,
        parce qu'il est le plus exigeant. Ce qui suit vous est ouvert que le RGPD vous soit
        applicable ou non.
    </p>
    <ul>
        <li><strong>Savoir ce qui est détenu sur vous, et en obtenir copie.</strong> Si
            vous avez un compte, appuyez sur <em>Télécharger mes données</em> dans votre
            profil : vous obtenez l'ensemble sous forme de fichier, immédiatement et sans
            rien demander à personne. Si vos coordonnées ont été saisies par une
            organisation utilisant le Service — si vous êtes locataire ou propriétaire —
            adressez-vous à elle : elle peut produire tout ce qui vous concerne d'une
            seule pression.</li>
        <li><strong>Faire corriger ce qui est inexact.</strong> Modifiez-le, ou
            demandez-le à l'organisation qui l'a saisi.</li>
        <li><strong>Être oublié.</strong> Une organisation peut effacer du Service un
            locataire, un propriétaire ou un agent. Son nom, son adresse e-mail, ses
            numéros de téléphone, son adresse postale, ses numéros d'identité et
            d'immatriculation, ses coordonnées bancaires et les notes sont détruits
            définitivement. Les factures, paiements et écritures du journal demeurent, ne
            désignant cette personne que par une référence et jamais par un nom, car la
            loi qui impose de conserver les pièces comptables est celle-là même qui nous
            autorise à refuser de les détruire. Un administrateur peut fermer un compte
            entier, ce qui détruit tout sans exception.</li>
        <li><strong>Emporter vos données ailleurs.</strong> Chaque rapport s'exporte en
            PDF, XLSX ou CSV ; le registre s'exporte en classeur ; et un administrateur
            peut télécharger l'organisation entière en un seul fichier structuré.</li>
        <li><strong>Vous opposer, et limiter.</strong> Vous pouvez couper tout ce que le
            Service envoie à une partie, individuellement ou pour tout le monde.</li>
        <li><strong>Réclamer.</strong> Écrivez-nous d'abord — nous préférons corriger — et
            vous pouvez également saisir la Commission de protection des données du Ghana.</li>
    </ul>
    <p>
        Écrivez à
        <a href="mailto:{{ config('legal.mailboxes.privacy') }}">{{ config('legal.mailboxes.privacy') }}</a>.
        <strong>Nous répondons sous 30 jours</strong>, et plus tôt lorsque c'est possible.
        Si vos données ont été saisies par une organisation cliente, cette organisation en
        est responsable et décide de la demande ; nous la lui transmettons sans délai et
        l'aidons à y répondre.
    </p>
    <p>
        Aucune décision vous concernant n'est prise par un moyen automatisé, et nous ne
        réalisons aucun profilage.
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

    <p>
        <strong>Si quelque chose tourne mal.</strong> Si des données personnelles étaient
        exposées, perdues ou modifiées sans autorisation, nous en informerions les
        organisations clientes concernées <strong>dans les 72 heures</strong> suivant notre
        prise de connaissance, en décrivant ce qui s'est produit, quelles données étaient
        en cause, ce que nous avons fait et ce que nous recommandons. Lorsque le risque
        pour les personnes est élevé et que la relation nous appartient, nous les
        informerions directement également.
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

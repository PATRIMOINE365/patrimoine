# Data protection

Kality Ltd is a Ghanaian company and Patrimoine 365 is sold in West Africa, so
Ghana's Data Protection Act, 2012 (Act 843) is the law that governs it. We have
built to the standard the EU's GDPR sets because it is the higher one, and
because a customer who asks whether we meet it should get a straight answer.

This file is the internal record. The customer-facing version is the Privacy
Policy at `/privacy`, and the two must agree — if you change one, change the
other.

**What is not here.** A Data Processing Agreement, standard contractual clauses
for staff access from Ghana, and the decision on whether GDPR applies to us at
all under Art. 3 all need a lawyer. Nothing in this file is legal advice.

---

## 1. Who we are, in which role

Two roles, and confusing them is the commonest mistake in this kind of software.

| Data | Controller | Processor |
|---|---|---|
| Accounts, plans, licences, security logs, support correspondence | **Kality Ltd** | — |
| A customer's tenants, owners, agents, leases, invoices, payments, journal | **The customer organisation** | **Kality Ltd** |

So: we decide what happens to a customer's *account*. We do not decide anything
about their *tenants*. A tenant who writes to us is referred to their letting
agent, and we help that agent answer.

Contact: `privacy@patrimoine365.com`. Security: `security@patrimoine365.com`.

---

## 2. Record of processing (GDPR Art. 30 in substance)

### As controller

| Purpose | People | Data | Lawful basis | Kept |
|---|---|---|---|---|
| Provide and operate the Service | Users | Name, e-mail, telephone, hashed password, role, language | Performance of contract | Life of the account |
| Authenticate each sign-in | Users | E-mail, hashed one-time code, challenge token | Performance of contract; security | Codes: minutes |
| Secure the Service and prove what happened | Users | IP address, browser, platform, device, action, timestamp | Legitimate interest — establishing after the fact what was done to an account, which no customer can do for themselves | **Indefinitely**, dies with the account |
| Bill and licence | Users | Organisation, plan, licence dates | Performance of contract | Life of the account |
| Tell customers about their own account | Users | Name, e-mail | Legitimate interest — the customer is paying for the thing being described, and it concerns only their own account | Life of the account |
| Meet legal obligations | Users | As required | Legal obligation | As required |

### As processor, on the customer's instructions

| Purpose | People | Data |
|---|---|---|
| Keep the property register | Tenants, owners, agents | Name, e-mail, telephone, postal address, identity and registration numbers, bank details, notes |
| Run the lettings | Tenants, owners | Leases, terms, rent, deposits, notices |
| Keep the accounts | Tenants, owners | Invoices, payments, allocations, fund movements, payouts, journal entries |
| Send the documents the customer triggers | Tenants, owners, agents | Name, e-mail, and the document itself |

We never use any of it for our own purposes.

---

## 3. Sub-processors

| Who | What | Where |
|---|---|---|
| Hosting provider (production) | Runs the servers and the database | **France** |
| Hetzner | Pre-production and internal tooling. No customer production data. | Ashburn, USA |
| Resend | Delivers e-mail: sign-in codes, invoices, receipts, reminders | USA |

Published to customers in Privacy Policy §5. Any addition is published there
before it takes effect.

**Transfers.** Production data is in France; Kality staff in Ghana reach it
through the platform console to provide support. Ghana has no EU adequacy
decision, so if GDPR ever applies to us this needs standard contractual clauses
and a transfer impact assessment. Every staff access is written to the customer's
own activity log.

---

## 4. Retention

| What | How long | Why |
|---|---|---|
| Account and business data | Life of the account | It is the service |
| Activity log | **Indefinitely**, destroyed with the account | Deliberate: it is the record that lets an organisation, an auditor or a court establish what happened. Decided 29 Aug 2026. |
| Financial records | Life of the account | Immutable by design; corrections are written as their own entries |
| E-mail content | Not kept at all | Only the fact of sending is logged |
| One-time codes | Minutes, stored hashed | Security |
| Closed account | Destroyed immediately and completely | `PlatformOrganisationDeletionService` |

There is **no pruning job**, and that is a decision rather than an omission. If
the retention position ever changes, this table and Privacy Policy §6 change
together.

---

## 5. Answering a request

**Target: 30 days.** Ghana's Act 843 and the GDPR both allow about that; we say
30 in public and should beat it.

| Right | How it is served today |
|---|---|
| Access / portability, own account | Self-service: **Download my data** in the profile drawer → `GET /api/auth/me/data` |
| Access / portability, a tenant or owner | The organisation's administrator: **Data** on the party → `GET /api/parties/{id}/data` |
| Access / portability, whole organisation | Settings › Data → **Download everything** → `GET /api/organisation/data` |
| Rectification | Edit the record |
| Erasure, a person | Parties → **Erase**. Pseudonymises: every identifying field destroyed, ledger keeps a reference. `PersonalData::erase()` |
| Erasure, everything | Settings › Organisation → **Close account**. Total and immediate. |
| Restriction / objection to messages | Party e-mail switch, per party or organisation-wide |
| Complaint | `privacy@`, then Ghana's Data Protection Commission |

**A tenant writing to us directly** is referred to their letting agent, who is
the controller. Acknowledge, forward, and help the agent produce the export.

**What erasure does not do.** It does not remove invoices, payments or journal
entries. Those are accounting records, and the law that requires them kept is the
same law that permits refusing to destroy them. What goes is the person: name,
e-mail, telephone numbers, address, identity and registration numbers, bank
details and notes, replaced everywhere by `Erased party #<id>`.

**Not yet possible.** Erasing a *user* (a colleague) rather than a party. Their
name is frozen into activity log snapshots by design, and the log is append-only.
A departing colleague is deactivated instead. If somebody demands erasure of a
user account, it needs a decision — say so rather than improvising.

---

## 6. If there is a breach

1. **Contain**, then preserve evidence. Do not clean up before you have looked.
2. **Assess** within hours: what data, whose, how many, what could follow.
3. **Tell affected customer organisations within 72 hours** of becoming aware.
   As processor we owe the controller notice without undue delay; as controller
   for account data we owe it to the customer directly. Say what happened, what
   data, what we have done, what we advise.
4. **Tell individuals** where the risk to them is high and the relationship is
   ours.
5. **Write it down** — what happened, when we knew, what we did, what we
   decided and why. The record matters even when notification is not required.
6. **Fix the cause**, and add the test that would have caught it.

Reports arrive at `security@patrimoine365.com`.

---

## 7. What the Service already does for you

Worth knowing before anybody proposes adding something:

- **No trackers.** No analytics, no advertising, no third-party scripts, on the
  application or the marketing site. **No cookie banner, deliberately** — a
  banner asks for consent, and everything stored is either strictly necessary or
  a setting the reader chose. Do not add one.
- **MFA on every sign-in**, not just on new devices.
- **Per-organisation isolation** enforced at several independent layers.
- **Append-only activity log**, including the address, browser and device.
- **Passwords** stored only as bcrypt hashes; never exported, in any export.
- **Whole-organisation exports strip credentials** before they are written.

---

## 8. Still to do

Needs a lawyer:

- A Data Processing Agreement to offer customers (Art. 28(3)).
- Standard contractual clauses and a transfer impact assessment for staff access
  from Ghana.
- The Art. 3 scoping decision, and with it whether an EU representative (Art. 27)
  is required.

Needs a decision from us:

- Whether a user account can ever be erased rather than deactivated (§5).
- A written confidentiality undertaking for staff with console access
  (Art. 28(3)(b)). The access is already logged; the paperwork is not written.

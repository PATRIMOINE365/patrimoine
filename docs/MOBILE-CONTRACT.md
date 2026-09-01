# The mobile contract

Everything in this file exists because of one fact: **an installed
application cannot be recalled.**

Today the browser application and the API deploy together, so a breaking
change is free — both halves are edited in one commit and everybody gets
it at once. The moment a build is on somebody's phone that stops being
true for ever. Somebody on the first version will call this API eighteen
months from now.

Patrimoine answers that with a **forced update**: below the floor
published at `GET /api/v1/config`, the application refuses to open and
sends the person to the store. That makes the *client* upgradeable — it
does not make the *contract* changeable, because the floor can only be
enforced by a client that already shipped with the code to enforce it.
Everything below is therefore fixed in the release before the first
build, not in the release where it is first needed.

Introduced in **v1.0.44**.

---

## 1. API version

Every route is mounted twice:

| Prefix | Meaning |
| --- | --- |
| `/api/v1/…` | The current version. **New clients call this.** |
| `/api/…` | An alias of the current version, kept for the browser application and anything already written against it. |

They are the same routes and answer identically; `MobileClientContractTest`
asserts it. Mounting is driven by `config('patrimoine.api.supported')` in
`bootstrap/app.php`, so introducing `v2` beside `v1` is a config entry and
a second copy of the route file, not a rewrite.

`DocumentLinkService` signs both shapes: a signed PDF link issued for
`/api/v1/…` validates at `/api/v1/…`.

**Rule:** a breaking change gets a new version segment. It never lands on
`v1`.

---

## 2. Access tokens

Minted in one place only — `App\Services\AccessTokenService::issue()`,
called from `POST /api/v1/auth/mfa/verify`.

### Device identity

A token's name is fixed at mint time and can never be recovered
afterwards. Clients therefore declare themselves:

| Header | Example | Effect |
| --- | --- | --- |
| `X-Patrimoine-Client` | `mobile` \| `web` \| `api` | Chooses the lifetime policy. Guessed from the user-agent when absent. |
| `X-Patrimoine-Platform` | `ios` \| `android` \| `web` | Recorded on the device row. |
| `X-App-Version` | `1.4.2` | Recorded on the device row. |
| `device_name` (body) | `Komla's Pixel 8` | The name shown in the Devices list. |

Without `device_name` the server composes one from the user-agent
("Chrome on Windows"). **A mobile client should always send its own** —
the handset knows which handset it is and the server never will.

### Lifetimes

Every token carries an **idle window** that each authenticated request
slides forward, and an **absolute ceiling** that nothing slides past.

| Client | Idle | Ceiling |
| --- | --- | --- |
| `web` | 12 hours | 30 days |
| `mobile` | 60 days | 180 days |
| `api` | 30 days | 90 days |

All six are `.env`-overridable (`TOKEN_IDLE_WEB`, `TOKEN_ABSOLUTE_MOBILE`,
…). The window only moves once `TOKEN_SLIDE_AFTER` minutes have passed,
so a read does not become a write.

An arrived expiry is refused by Sanctum at the same door every other
invalid token is refused at: **401**. Clients already handle 401 by
returning to sign-in, so no refresh flow exists and none is needed.

### Devices

| Route | Does |
| --- | --- |
| `GET /api/v1/auth/devices` | Lists the acting user's own signed-in devices. |
| `DELETE /api/v1/auth/devices/{id}` | Revokes one. Revoking the current device answers `signed_out: true`. |
| `DELETE /api/v1/auth/devices` | Revokes every device except the current one. |

Scoped to the token owner, always. No capability gate: reading and
revoking your own sessions is not an administrative act, and asking an
administrator to do it is the wrong answer to "I left my phone in a taxi".

In the product this is **Settings → Devices**.

---

## 3. Launch configuration

`GET /api/v1/config` — public, called before anything is shown.

```jsonc
{
  "release": "1.0.44",
  "api": { "current": "v1", "supported": ["v1"] },
  "minimum_version": { "android": "1.0.0", "ios": "1.0.0" },
  "latest_version":  { "android": null, "ios": null },
  "store_url":       { "android": null, "ios": null },
  "maintenance":     { "active": false, "message": null },
  "features": {
    "signup_in_app": false,
    "payments_in_app": false,
    "biometric_unlock": true
  },
  "links": { "signup": "…", "forgot_password": "…", "terms": "…",
             "privacy": "…", "errors": "…", "support": "…" },
  "languages": ["en", "fr"]
}
```

**The client must:**

1. Call this before the first screen.
2. If its own version is below `minimum_version[platform]`, show the
   update screen and refuse to continue. This is the forced update.
3. If `maintenance.active`, show `maintenance.message` and refuse to
   continue.
4. Otherwise, cache and carry on.

Raising a floor or closing the service is an `.env` change plus
`php artisan config:cache`. **No release, no store review.**

There is deliberately no server-side `426` gate. The floor is enforced by
the client, which is sufficient because the only clients are ours; adding
the gate later is about twenty lines and breaks nothing.

---

## 4. Deep links

Universal Links (iOS) and App Links (Android) on the **existing https
paths**, not a private scheme. A `patrimoine365://` scheme would have
been worthless for the invoice e-mail sent last month; associating the
real paths means every link already in somebody's inbox starts opening
the application the day it is installed.

Served from `App\Http\Controllers\AppAssociationController`:

- `/.well-known/apple-app-site-association`
- `/.well-known/assetlinks.json`

Both **404 until configured**, because Apple caches the association
through its own CDN and a placeholder is the answer that sticks. To
publish them, set on the live host:

```
DEEP_LINK_APPLE_TEAM_ID=…            # from the Apple developer account
DEEP_LINK_APPLE_BUNDLE_ID=com.patrimoine365.app
DEEP_LINK_ANDROID_PACKAGE=com.patrimoine365.app
DEEP_LINK_ANDROID_FINGERPRINTS=AA:BB:…   # comma-separated SHA-256
```

### The frozen path surface

`AppAssociationController::CLAIMED_PATHS` is a published contract from
this release. `MobileClientContractTest` fails if one of them stops
resolving.

```
/dashboard*  /properties*  /parties*  /leases*   /owners*   /tenants*
/accounting* /reports*     /audit*    /archive*  /settings* /help*
/errors*
```

**Adding a path is safe. Changing or removing one breaks links that have
already been sent.** `/signup`, `/login` and the legal pages are
deliberately *not* claimed — they stay web journeys.

---

## 5. Language

Precedence, highest first:

1. `X-Patrimoine-Language: fr` — the client stating what the screen this
   reply lands on is written in.
2. The organisation's setting, once one is bound.
3. The `patrimoine_language` cookie (first paint, before sign-in).
4. `Accept-Language`, negotiated against the registered languages.
5. The platform default.

Why the organisation sits above `Accept-Language`: error codes are
recovered by matching the **rendered sentence** back to a per-language
catalogue. Both halves of the product take the language from the same
place, so they cannot disagree and a sentence is always matchable. An
English handset inside a French organisation therefore reads French.

If that is not wanted, `CLIENT_LANGUAGE_OVERRIDES_ORGANISATION=true`
promotes an explicit `X-Patrimoine-Language` above the organisation. It is
off by default because turning it on means the two can disagree.

**A mobile client should send `X-Patrimoine-Language` on every request,
set to whatever it is rendering in.**

---

## 6. Auth entry points

The application is **sign-in only**.

- Sign up → outbound link to `links.signup` in the browser.
- Plan and payment → the same.

`features.signup_in_app` and `features.payments_in_app` are `false` in
`/config` and are the machine-readable form of this decision. A sign-up
flow inside the application is what invites the in-app-purchase argument
even when no money changes hands there.

First-launch flow: email → password → six-digit code → offer biometrics.

---

## 7. Still to do, before the first build

- [ ] **CORS.** Capacitor serves the WebView from `capacitor://localhost`
      or `https://localhost`, a different origin from
      `app.patrimoine365.com`. There is no `config/cors.php` today, so
      the framework default applies. Publish one and allow those two
      origins. Not hard, but it is what stops the very first build from
      talking to the server at all.
- [ ] **Apple / Android signing identities**, then set the four
      `DEEP_LINK_*` variables so the association files publish.
- [ ] **Store URLs and version floors** (`CLIENT_STORE_*`,
      `CLIENT_MIN_*`) once there is something in a store.

## 8. Deliberately left until later

None of these is a contract change, so none of them has to be right
before the first build.

- **Pagination and payload tuning for cellular** — additive, and better
  tuned against real usage.
- **Offline read caching** — worth doing once it is known which three
  screens people actually open in the field.
- **The responsive layout** — the bulk of the work, and none of it is a
  contract change. It can happen screen by screen after Capacitor is
  wired up.

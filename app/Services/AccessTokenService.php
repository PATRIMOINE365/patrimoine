<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

/**
 * Mints access tokens that know what they are and when they die.
 *
 * Two rules live here and nowhere else.
 *
 * A token is named for the device that asked for it. The name is fixed at
 * the moment it is minted and can never be recovered afterwards, because
 * nothing about a bearer token says where it came from. Getting this
 * wrong in the first release of an application means a Devices list of
 * indistinguishable rows, and nobody can be told which one to revoke.
 *
 * A token carries an idle window that ordinary use slides forward and an
 * absolute ceiling that nothing slides past. A device somebody stops
 * using stops being a credential on its own; a device somebody uses every
 * day is never signed out in the middle of the work.
 */
class AccessTokenService
{
    /**
     * Issue a token for the client making this request.
     */
    public function issue(
        User $user,
        Request $request,
        ?string $deviceName = null
    ): NewAccessToken {
        $clientType = $this->clientType($request);

        $policy = PersonalAccessToken::policyFor($clientType);

        $token = $user->createToken(
            $this->deviceName($request, $deviceName),
            ['*'],
            now()->addMinutes($policy['idle'])
        );

        /*
         * Sanctum builds the row; the device context is written onto it
         * immediately afterwards rather than through createToken, whose
         * signature has no room for it.
         */
        $token->accessToken->forceFill([
            'client_type' => $clientType,
            'platform' => $this->platform($request),
            'app_version' => $this->appVersion($request),
            'created_ip' => $request->ip(),
            'last_used_ip' => $request->ip(),
            'absolute_expires_at' => now()->addMinutes($policy['absolute']),
        ])->save();

        return $token;
    }

    /**
     * Push the idle window forward for a token that has just been used.
     *
     * The window only moves once it has been still for a while: sliding
     * on every request would turn every read into a write, and a few
     * minutes of imprecision on a window measured in hours or months
     * costs nothing.
     *
     * The ceiling is never crossed. A token whose absolute expiry has
     * arrived simply stops being extended, and Sanctum refuses it on the
     * next request.
     */
    public function slide(PersonalAccessToken $token): void
    {
        $policy = $token->lifetimePolicy();

        $target = now()->addMinutes($policy['idle']);

        $ceiling = $token->absolute_expires_at;

        if ($ceiling !== null && $target->greaterThan($ceiling)) {
            $target = $ceiling;
        }

        $current = $token->expires_at;

        if (
            $current !== null
            && $current->greaterThanOrEqualTo($target)
        ) {
            return;
        }

        $slideAfter = (int) config('patrimoine.tokens.slide_after', 5);

        if (
            $current !== null
            && $current->diffInMinutes($target, absolute: true) < $slideAfter
        ) {
            return;
        }

        $token->forceFill([
            'expires_at' => $target,
        ])->save();
    }

    /**
     * Record the address a token was last used from, when it changes.
     *
     * Sanctum already writes last_used_at. The address beside it is what
     * turns "some device" into "the one that was in Accra on Tuesday".
     */
    public function recordUsage(
        PersonalAccessToken $token,
        Request $request
    ): void {
        $ip = $request->ip();

        if ($ip === null || $token->last_used_ip === $ip) {
            return;
        }

        $token->forceFill([
            'last_used_ip' => $ip,
        ])->save();
    }

    /**
     * Which kind of client is asking.
     *
     * A client may declare itself; the mobile application always does.
     * Anything that looks like a browser is treated as the first-party
     * web application, and everything else as an integration.
     */
    public function clientType(Request $request): string
    {
        $declared = mb_strtolower(
            trim((string) $request->header('X-Patrimoine-Client', ''))
        );

        if (
            in_array(
                $declared,
                [
                    PersonalAccessToken::CLIENT_WEB,
                    PersonalAccessToken::CLIENT_MOBILE,
                    PersonalAccessToken::CLIENT_API,
                ],
                true
            )
        ) {
            return $declared;
        }

        $agent = (string) $request->userAgent();

        if (
            Str::contains(
                $agent,
                ['Mozilla', 'AppleWebKit', 'Chrome', 'Safari', 'Firefox', 'Edg/'],
                ignoreCase: true
            )
        ) {
            return PersonalAccessToken::CLIENT_WEB;
        }

        return PersonalAccessToken::CLIENT_API;
    }

    /**
     * The platform the client declared, when it declared one.
     */
    private function platform(Request $request): ?string
    {
        $declared = mb_strtolower(
            trim((string) $request->header('X-Patrimoine-Platform', ''))
        );

        return in_array($declared, ['ios', 'android', 'web'], true)
            ? $declared
            : null;
    }

    /**
     * The application version the client declared, when it declared one.
     */
    private function appVersion(Request $request): ?string
    {
        $declared = trim((string) $request->header('X-App-Version', ''));

        if ($declared === '' || mb_strlen($declared) > 30) {
            return null;
        }

        return $declared;
    }

    /**
     * A name somebody can recognise.
     *
     * The client's own name wins - a phone knows which handset it is and
     * the server never will. Failing that the name is composed from the
     * browser and the operating system, because "patrimoine-api" told
     * nobody anything.
     */
    public function deviceName(
        Request $request,
        ?string $supplied = null
    ): string {
        $supplied = trim((string) $supplied);

        if ($supplied !== '') {
            return Str::limit($supplied, 100, '');
        }

        $agent = (string) $request->userAgent();

        $browser = $this->firstMatch($agent, [
            'Edg/' => 'Edge',
            'OPR/' => 'Opera',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
        ]);

        $system = $this->firstMatch($agent, [
            'iPhone' => 'iPhone',
            'iPad' => 'iPad',
            'Android' => 'Android',
            'Windows NT' => 'Windows',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
        ]);

        if ($browser !== null && $system !== null) {
            return $browser.' on '.$system;
        }

        return $browser
            ?? $system
            ?? 'Unrecognised device';
    }

    /**
     * @param  array<string, string>  $needles
     */
    private function firstMatch(string $haystack, array $needles): ?string
    {
        foreach ($needles as $needle => $label) {
            if (Str::contains($haystack, $needle)) {
                return $label;
            }
        }

        return null;
    }
}

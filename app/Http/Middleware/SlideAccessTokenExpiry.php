<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Services\AccessTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep a token alive for as long as it is being used, and no longer.
 *
 * Every authenticated request pushes the idle window forward, up to the
 * absolute ceiling the token was minted with. Nothing here can extend a
 * token past that ceiling, and nothing here revokes anything: an expiry
 * that has arrived is refused by Sanctum on the next request, which is
 * the same door every other invalid token is refused at.
 *
 * The work happens after the response so that a request which was going
 * to fail still fails for its own reason.
 */
class SlideAccessTokenExpiry
{
    public function __construct(
        private readonly AccessTokenService $tokens
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        $token = $request->user()?->currentAccessToken();

        /*
         * Sanctum's own test helper hands the guard a mock of this class
         * rather than a row, and a request authenticated by a signed
         * document link carries a transient token that was never
         * persisted. Neither has a lifetime to slide, so both are left
         * alone: only a token that exists in the database is touched.
         */
        if (
            ! $token instanceof PersonalAccessToken
            || $token->exists !== true
        ) {
            return $response;
        }

        $this->tokens->slide($token);
        $this->tokens->recordUsage($token, $request);

        return $response;
    }
}

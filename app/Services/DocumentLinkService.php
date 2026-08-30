<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Issues and validates short-lived signed URLs for PDF documents.
 *
 * The browser cannot attach the Sanctum Bearer token to ordinary tab
 * navigation, which previously forced every document to be downloaded
 * through fetch() into a blob before a tab could open it. A signed URL
 * lets the tab navigate straight to the document endpoint instead, so
 * the browser streams and renders the PDF natively.
 *
 * Design rules:
 *
 * - Only the read-only document endpoints listed in SIGNABLE_PATHS can
 *   ever be signed. The signature additionally covers the full query
 *   string, the acting user and the expiry time.
 * - Links expire after TTL_MINUTES. They exist to be opened
 *   immediately after being issued, not to be stored or shared.
 * - The signature is computed over path + query only, never the host,
 *   so reverse-proxy host rewriting cannot invalidate a link.
 * - Authorization is NOT decided here. The signed user is authenticated
 *   by the middleware and the route's own capability middleware then
 *   authorizes exactly as it would for a Bearer-token request.
 */
class DocumentLinkService
{
    /**
     * Minutes a signed document link stays valid.
     */
    private const TTL_MINUTES = 10;

    /**
     * Query parameters reserved for the signature itself.
     */
    private const RESERVED_PARAMETERS = [
        'user',
        'expires',
        'signature',
    ];

    /**
     * Every document endpoint that may be opened through a signed link.
     *
     * @var list<string>
     */
    private const SIGNABLE_PATHS = [
        '#^/api/leases/\d+/termination-notice/pdf$#',
        '#^/api/leases/\d+/financial-history/pdf$#',
        '#^/api/invoices/\d+/pdf$#',
        '#^/api/invoices/\d+/payment-receipt$#',
        '#^/api/payments/\d+/receipt$#',
        '#^/api/owner-deposits/\d+/receipt$#',
        '#^/api/owner-expense-bills/\d+/pdf$#',
        '#^/api/owner-expense-bills/\d+/payment-receipt$#',
        '#^/api/owner-payouts/\d+/receipt$#',
        '#^/api/owner-reserve-transfers/\d+/voucher$#',
        '#^/api/adjustment-vouchers/\d+/pdf$#',
        '#^/api/withdrawal-receipts/\d+/pdf$#',
        '#^/api/tenant-fund-expenses/\d+/voucher$#',
        '#^/api/tenant-fund-transfers/\d+/voucher$#',
        '#^/api/security-deposit-settlements/\d+/voucher$#',
        '#^/api/reports/(payments|occupancy|arrears|funds)/pdf$#',
        '#^/api/reports/(owners|tenants|buildings|units)/\d+/pdf$#',
        '#^/api/reports/managing-organisation/pdf$#',
        '#^/api/registry/export/pdf$#',
    ];

    /**
     * Determine whether the endpoint may be opened through a signed link.
     */
    public function isSignable(
        string $endpoint
    ): bool {
        $path = strtok($endpoint, '?');

        foreach (self::SIGNABLE_PATHS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Issue an absolute signed URL for a signable document endpoint.
     *
     * @throws RuntimeException When the endpoint is not signable.
     */
    public function issue(
        string $endpoint,
        User $user
    ): string {
        if (! $this->isSignable($endpoint)) {
            throw new RuntimeException(
                __('api.documents.not_signable')
            );
        }

        [$path, $query] = array_pad(
            explode('?', $endpoint, 2),
            2,
            ''
        );

        /*
         * Reject endpoints that try to smuggle in the reserved signing
         * parameters through the original query string.
         */
        parse_str($query, $originalParameters);

        foreach (self::RESERVED_PARAMETERS as $reserved) {
            if (array_key_exists($reserved, $originalParameters)) {
                throw new RuntimeException(
                    __('api.documents.not_signable')
                );
            }
        }

        $expires = now()
            ->addMinutes(self::TTL_MINUTES)
            ->getTimestamp();

        $signedQuery = $query === ''
            ? ''
            : $query.'&';

        $signedQuery .= http_build_query([
            'user' => $user->id,
            'expires' => $expires,
        ]);

        $signature = $this->signature(
            $path.'?'.$signedQuery
        );

        return url(
            $path
            .'?'
            .$signedQuery
            .'&signature='
            .$signature
        );
    }

    /**
     * Validate the signature and expiry carried by a document request.
     *
     * The signed material is rebuilt from the request's raw query string
     * with the trailing signature parameter removed, so any tampering
     * with the path, the document filters, the user or the expiry time
     * breaks the signature.
     */
    public function validate(
        Request $request
    ): bool {
        $signature = (string) $request->query(
            'signature',
            ''
        );

        $expires = (int) $request->query(
            'expires',
            0
        );

        if (
            $signature === ''
            || $expires < now()->getTimestamp()
        ) {
            return false;
        }

        $queryWithoutSignature = collect(
            explode(
                '&',
                (string) $request->server->get(
                    'QUERY_STRING',
                    ''
                )
            )
        )
            ->reject(
                fn (string $parameter): bool => Str::startsWith(
                    $parameter,
                    'signature='
                )
            )
            ->implode('&');

        $expected = $this->signature(
            $request->getPathInfo()
            .'?'
            .rtrim($queryWithoutSignature, '&')
        );

        return hash_equals(
            $expected,
            $signature
        );
    }

    /**
     * Compute the HMAC for the given path-and-query material.
     */
    private function signature(
        string $material
    ): string {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(
                Str::after($key, 'base64:')
            );
        }

        return hash_hmac(
            'sha256',
            $material,
            $key
        );
    }
}

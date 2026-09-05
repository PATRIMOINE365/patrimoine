<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * V1.0.51: the browser-side walls, set by the application itself.
 *
 * Until now the only hardening headers came from the nginx inside the
 * pre-production image; production runs on Plesk and sent none at all —
 * the console could be framed, sniffed and scripted from anywhere. A
 * header the application sets travels with it to every host.
 *
 * The Content-Security-Policy is the second wall behind output escaping:
 * script may run only from this origin and from the inline blocks that
 * carry this request's nonce, so an injected `<img onerror>` or a stray
 * `<script>` has nowhere to execute even if a template slips. It is sent
 * on HTML documents only — a PDF, a CSV or a JSON reply has no script to
 * govern and a policy on it only confuses the viewer showing it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * One nonce per request, generated BEFORE the view renders so
         * the @vite tags and the layouts' inline bootstrap scripts can
         * carry it. The policy below must quote the same value.
         */
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        /*
         * PHP announces its own version on every response while the
         * host's expose_php is on. That is the host's setting, but the
         * header is removed here regardless, so no environment leaks it.
         */
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $response->headers->remove('X-Powered-By');

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];

        if ($this->isHtml($response)) {
            $headers['Content-Security-Policy'] = $this->policy($nonce);
        }

        /*
         * HSTS only where TLS actually terminates on this request:
         * sending it over plain HTTP is meaningless, and pre-production
         * sits behind a proxy on plain HTTP.
         */
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    /**
     * Whether the reply is a document a browser will execute script in.
     */
    private function isHtml(Response $response): bool
    {
        $type = (string) $response->headers->get('Content-Type', '');

        return $type === '' || str_contains($type, 'text/html');
    }

    /**
     * The policy, one directive per line so a change is a one-line diff.
     *
     * Inline styles stay allowed: the templates and the PDF-preview
     * markup carry `style` attributes, and a style cannot exfiltrate a
     * token the way a script can.
     */
    private function policy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'self' blob:",
            "worker-src 'self' blob:",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}

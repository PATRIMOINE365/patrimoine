<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads and sends platform support mail through Resend.
 *
 * Patrimoine already sends every transactional email through Resend, and
 * patrimoine365.com has receiving enabled there, so both halves of the
 * support mailbox already exist upstream. This service reads them rather
 * than mirroring them into a local table: a copy would only ever be a
 * stale second version of the truth, and delivery state (delivered,
 * bounced, complained) lives at Resend regardless.
 *
 * The API key is the one the mailer already uses. Nothing here writes to
 * the database.
 */
class ResendMailboxService
{
    private const BASE = 'https://api.resend.com';

    /**
     * Resend caps a page at 100.
     */
    private const MAX_LIMIT = 100;

    public function __construct(
        private readonly ?string $apiKey = null
    ) {
    }

    /**
     * Whether support mail is usable in this environment.
     *
     * Local and CI runs have no Resend key and must degrade to an empty
     * mailbox rather than erroring.
     */
    public function configured(): bool
    {
        return $this->key() !== null;
    }

    /**
     * List mail the application has sent.
     *
     * @return array<string, mixed>
     */
    public function sent(int $limit = 50, ?string $after = null): array
    {
        return $this->list('/emails', $limit, $after);
    }

    /**
     * List mail received at any @patrimoine365.com address.
     *
     * @return array<string, mixed>
     */
    public function received(int $limit = 50, ?string $after = null): array
    {
        return $this->list('/emails/inbound', $limit, $after);
    }

    /**
     * Retrieve one sent message, including its body.
     *
     * @return array<string, mixed>
     */
    public function showSent(string $id): array
    {
        return $this->request('GET', '/emails/'.$id);
    }

    /**
     * Retrieve one received message, including its body.
     *
     * @return array<string, mixed>
     */
    public function showReceived(string $id): array
    {
        return $this->request('GET', '/emails/inbound/'.$id);
    }

    /**
     * Send a support email.
     *
     * @param array<int, string> $to
     * @return array<string, mixed>
     */
    public function send(
        array $to,
        string $subject,
        string $body,
        string $from,
        ?string $replyTo = null
    ): array {
        $payload = [
            'from' => $from,
            'to' => array_values($to),
            'subject' => $subject,

            /*
             * Support replies are written as plain text in the console.
             * Sending them as text keeps them readable in every client and
             * avoids shipping unsanitised HTML from a free-text field.
             */
            'text' => $body,
        ];

        if ($replyTo !== null && $replyTo !== '') {
            $payload['reply_to'] = $replyTo;
        }

        return $this->request('POST', '/emails', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function list(
        string $path,
        int $limit,
        ?string $after
    ): array {
        $query = [
            'limit' => max(1, min($limit, self::MAX_LIMIT)),
        ];

        if ($after !== null && $after !== '') {
            $query['after'] = $after;
        }

        $response = $this->request(
            'GET',
            $path.'?'.http_build_query($query)
        );

        return [
            'data' => $response['data'] ?? [],
            'has_more' => (bool) ($response['has_more'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $path,
        ?array $payload = null
    ): array {
        $key = $this->key();

        if ($key === null) {
            throw new RuntimeException(
                'Support mail is not configured: no Resend API key is set.'
            );
        }

        $request = Http::withToken($key)
            ->acceptJson()
            ->timeout(20);

        $response = $method === 'POST'
            ? $request->post(self::BASE.$path, $payload ?? [])
            : $request->get(self::BASE.$path);

        if ($response->failed()) {
            /*
             * Surface Resend's own message: "domain not verified" and
             * "invalid recipient" are the errors staff will actually hit,
             * and a generic failure would hide them.
             */
            $message = $response->json('message')
                ?? 'Resend request failed.';

            throw new RuntimeException(
                'Support mail: '.$message
            );
        }

        return $response->json() ?? [];
    }

    private function key(): ?string
    {
        $key = $this->apiKey
            ?? config('services.resend.key')
            ?? config('mail.mailers.resend.key')
            ?? env('RESEND_API_KEY');

        $key = is_string($key) ? trim($key) : '';

        return $key === '' ? null : $key;
    }
}

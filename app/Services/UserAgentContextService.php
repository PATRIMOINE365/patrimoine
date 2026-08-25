<?php

namespace App\Services;

/**
 * Derive human-readable client context from a raw User-Agent header.
 *
 * Activity Log freezes historical facts at write time, so browser,
 * platform and device are parsed once when the event is recorded rather
 * than re-interpreted from the raw header on every read. Parsing is
 * deliberately dependency-free: a small ordered ruleset covering the
 * browsers and platforms this application realistically meets, with the
 * raw header always preserved alongside as the source of truth.
 */
class UserAgentContextService
{
    /**
     * Ordered browser detection rules.
     *
     * Order matters: Chromium-derived browsers advertise "Chrome" too,
     * so the derived brands must be recognized before Chrome itself, and
     * Safari must come after Chrome because Chrome advertises "Safari".
     *
     * @var array<string, string>
     */
    private const BROWSER_RULES = [
        'Edg(?:e|A|iOS)?\/([\d.]+)' => 'Microsoft Edge',
        '(?:OPR|Opera)\/([\d.]+)' => 'Opera',
        'SamsungBrowser\/([\d.]+)' => 'Samsung Internet',
        '(?:Firefox|FxiOS)\/([\d.]+)' => 'Firefox',
        'CriOS\/([\d.]+)' => 'Chrome',
        'Chrome\/([\d.]+)' => 'Chrome',
        'Version\/([\d.]+).*Safari' => 'Safari',
        '(?:MSIE ([\d.]+)|Trident\/.*rv:([\d.]+))' => 'Internet Explorer',
    ];

    /**
     * Ordered platform detection rules.
     *
     * iPad/iPhone must precede Mac because modern iPadOS Safari can
     * advertise a Macintosh platform token only in desktop-mode, and
     * Android must precede Linux because Android advertises Linux.
     *
     * @var array<string, string>
     */
    private const PLATFORM_RULES = [
        'Windows NT 10' => 'Windows',
        'Windows NT 6\.3' => 'Windows 8.1',
        'Windows NT 6\.1' => 'Windows 7',
        'Windows' => 'Windows',
        'iPhone|iPod' => 'iOS',
        'iPad' => 'iPadOS',
        'Android' => 'Android',
        'Mac OS X|Macintosh' => 'macOS',
        'CrOS' => 'ChromeOS',
        'Linux' => 'Linux',
    ];

    /**
     * Parse one raw User-Agent header into frozen client context.
     *
     * @return array{
     *     browser: string|null,
     *     platform: string|null,
     *     device: string|null,
     * }
     */
    public function parse(
        ?string $userAgent
    ): array {
        $userAgent = $userAgent === null
            ? null
            : trim($userAgent);

        if ($userAgent === null || $userAgent === '') {
            return [
                'browser' => null,
                'platform' => null,
                'device' => null,
            ];
        }

        return [
            'browser' => $this->browser($userAgent),
            'platform' => $this->platform($userAgent),
            'device' => $this->device($userAgent),
        ];
    }

    /**
     * Return "Name major-version" for the first matching browser rule.
     */
    private function browser(
        string $userAgent
    ): ?string {
        foreach (self::BROWSER_RULES as $pattern => $name) {
            if (
                preg_match(
                    "/{$pattern}/i",
                    $userAgent,
                    $matches
                ) !== 1
            ) {
                continue;
            }

            /*
             * The Internet Explorer rule carries two capture groups;
             * whichever matched holds the version.
             */
            $version =
                array_values(
                    array_filter(
                        array_slice($matches, 1),
                        static fn (string $candidate): bool => $candidate !== ''
                    )
                )[0] ?? null;

            $major = $version === null
                ? null
                : explode('.', $version)[0];

            return $major === null || $major === ''
                ? $name
                : "{$name} {$major}";
        }

        return null;
    }

    /**
     * Return the operating platform for the first matching rule.
     */
    private function platform(
        string $userAgent
    ): ?string {
        foreach (self::PLATFORM_RULES as $pattern => $name) {
            if (
                preg_match(
                    "/(?:{$pattern})/i",
                    $userAgent
                ) === 1
            ) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Classify the device as Mobile, Tablet or Desktop.
     *
     * Android tablets advertise Android without the Mobile token, which
     * is why the tablet check inspects both explicit tablet markers and
     * that combination.
     */
    private function device(
        string $userAgent
    ): string {
        $isTablet =
            preg_match(
                '/iPad|Tablet/i',
                $userAgent
            ) === 1
            || (
                preg_match('/Android/i', $userAgent) === 1
                && preg_match('/Mobile/i', $userAgent) !== 1
            );

        if ($isTablet) {
            return 'Tablet';
        }

        if (
            preg_match(
                '/Mobi|iPhone|iPod/i',
                $userAgent
            ) === 1
        ) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}

<?php

namespace App\Support;

/**
 * Convert an HTML email body into a readable plain-text alternative.
 *
 * V1.0.17: every Patrimoine 365 email ships multipart/alternative. Mail
 * providers — Microsoft 365 in particular — score HTML-only mail more
 * harshly, and a missing text part is one of the signals that pushed our
 * verification email into "High Confidence Phish" quarantine.
 *
 * Deliberately dependency-free and conservative: the goal is a faithful,
 * readable rendering of transactional content, not a general-purpose
 * HTML renderer. Link targets are preserved inline so a text-only reader
 * can still act on the message.
 */
final class HtmlToPlainText
{
    /**
     * Render the plain-text equivalent of one HTML email body.
     */
    public static function convert(string $html): string
    {
        $text = $html;

        /*
         * Invisible machinery first: styles, scripts, the document head
         * and the hidden preheader carry nothing a reader needs.
         */
        $text = preg_replace(
            '#<(style|script|head)\b[^>]*>.*?</\1>#is',
            ' ',
            $text
        ) ?? $text;

        /*
         * Links become "label (url)" so the recipient can still reach the
         * destination. Anchors wrapping only an image keep just the URL.
         */
        $text = preg_replace_callback(
            '#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            static function (array $matches): string {
                $url = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                $label = trim(
                    preg_replace('/\s+/u', ' ', strip_tags($matches[3])) ?? ''
                );

                $label = trim(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($label === '' || $label === $url) {
                    return ' '.$url.' ';
                }

                return ' '.$label.' ('.$url.') ';
            },
            $text
        ) ?? $text;

        /*
         * Images contribute their alt text, when they carry one.
         */
        $text = preg_replace_callback(
            '#<img\b[^>]*>#is',
            static function (array $matches): string {
                if (preg_match('#alt=(["\'])(.*?)\1#is', $matches[0], $alt) !== 1) {
                    return ' ';
                }

                $value = trim($alt[2]);

                return $value === '' ? ' ' : ' '.$value.' ';
            },
            $text
        ) ?? $text;

        /*
         * Structural elements become line breaks so the result keeps the
         * shape of the message rather than collapsing into one paragraph.
         */
        $text = preg_replace(
            '#<(br)\s*/?>#i',
            "\n",
            $text
        ) ?? $text;

        $text = preg_replace(
            '#</(p|div|tr|h1|h2|h3|h4|li|table)\s*>#i',
            "\n",
            $text
        ) ?? $text;

        $text = preg_replace(
            '#</(td|th)\s*>#i',
            "\t",
            $text
        ) ?? $text;

        $text = strip_tags($text);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        /*
         * Email HTML is full of &nbsp; and friends. They decode to
         * characters that read as spaces but survive whitespace
         * collapsing, so normalise them to ordinary spaces.
         */
        $text = str_replace(
            ["\u{00A0}", "\u{200B}", "\u{2060}", "\u{FEFF}"],
            [' ', '', '', ''],
            $text
        );

        /*
         * Tidy up: trim each line, drop runs of blank lines, and never
         * leave trailing whitespace behind.
         */
        $lines = preg_split('/\R/u', $text) ?: [];

        $lines = array_map(
            static fn (string $line): string => trim(
                preg_replace('/[ \t]+/u', ' ', $line) ?? $line
            ),
            $lines
        );

        $rendered = [];

        foreach ($lines as $line) {
            if ($line === '' && ($rendered === [] || end($rendered) === '')) {
                continue;
            }

            $rendered[] = $line;
        }

        return trim(implode("\n", $rendered));
    }
}

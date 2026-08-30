<?php

namespace App\Support;

use RuntimeException;

/**
 * The Untitled UI icon set.
 *
 * One definition, in resources/icons/untitled-ui.json, read by Blade through
 * <x-icon name="..."> and compiled into resources/js/icons.js for the
 * JavaScript-rendered parts of the product. Neither surface may draw an icon
 * of its own: an SVG pasted inline is an icon nobody can restyle, recolour or
 * find again, and the application had twenty-one of them in one layout file.
 *
 * Every icon is a 24-unit line drawing with no fill. Colour arrives as
 * currentColor from whatever the icon sits inside, so the same icon is muted
 * in a table, white on the sidebar and red in a delete button without anyone
 * saying so.
 *
 * An unknown name is a mistake worth hearing about while it can still be
 * fixed, so it throws in local and testing and renders nothing in production
 * — a missing icon must never be able to take a page down in front of a
 * customer.
 */
final class Icons
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $icons = null;

    /**
     * The default rendered size. Untitled UI draws on 24 and renders at 20.
     */
    public const SIZE = 20;

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$icons === null) {
            $path = resource_path('icons/untitled-ui.json');

            $decoded = json_decode(
                (string) file_get_contents($path),
                true
            );

            self::$icons = $decoded['icons'] ?? [];
        }

        return self::$icons;
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, self::all());
    }

    /**
     * The inner markup of one icon, with no <svg> wrapper.
     */
    public static function paths(string $name): string
    {
        $icons = self::all();

        if (! array_key_exists($name, $icons)) {
            if (app()->environment(['local', 'testing'])) {
                throw new RuntimeException(
                    "Unknown icon [{$name}]. Add it to "
                    . 'resources/icons/untitled-ui.json and re-run '
                    . 'scripts/generate-icons.mjs.'
                );
            }

            return '';
        }

        /*
         * The JSON uses single quotes so the file stays readable without
         * escaping every attribute. HTML accepts either.
         */
        return $icons[$name];
    }
}

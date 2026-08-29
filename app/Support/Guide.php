<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The how-to guide.
 *
 * One manual, written once and read in three places: the Guide tab inside
 * Patrimoine, the public pages at patrimoine365.com/documentation/guides,
 * and — through the export script — anywhere else that needs it.
 *
 * The words live in lang/{en,fr}/guide.php. That file carries the whole
 * shape as well as the sentences: categories, the tasks inside them, and
 * the numbered steps inside those. English is the authority for the shape;
 * GuideTest fails if French drifts from it, because a manual that has a
 * step in one language and not the other is worse than one that is simply
 * untranslated.
 *
 * A step may name a screenshot. The pictures are captured, never drawn or
 * hand-shot: scripts/guide-shots.json says how to photograph each one and
 * scripts/capture-guide.mjs takes them from the real application. A shot
 * named here that the manifest cannot take is a test failure, not a broken
 * image on a customer's screen.
 */
final class Guide
{
    /**
     * The languages the guide is written in.
     *
     * @var list<string>
     */
    public const LOCALES = ['en', 'fr'];

    /**
     * The whole guide for one language.
     *
     * @return array{categories: list<array<string, mixed>>}
     */
    public static function for(string $locale): array
    {
        $locale = in_array($locale, self::LOCALES, true)
            ? $locale
            : 'en';

        $source = self::source($locale);

        $categories = [];

        foreach ($source['categories'] ?? [] as $categoryId => $category) {
            $tasks = [];

            foreach ($category['tasks'] ?? [] as $taskId => $task) {
                $steps = [];

                foreach ($task['steps'] ?? [] as $index => $step) {
                    $steps[] = [
                        'number' => $index + 1,
                        'text' => (string) ($step['text'] ?? ''),
                        'shot' => $step['shot'] ?? null,
                        'note' => $step['note'] ?? null,
                    ];
                }

                $tasks[] = [
                    'id' => $taskId,
                    'title' => (string) ($task['title'] ?? ''),
                    'intro' => (string) ($task['intro'] ?? ''),
                    'who' => $task['who'] ?? null,
                    'steps' => $steps,
                    'after' => $task['after'] ?? null,
                ];
            }

            $categories[] = [
                'id' => $categoryId,
                'title' => (string) ($category['title'] ?? ''),
                'summary' => (string) ($category['summary'] ?? ''),
                'tasks' => $tasks,
            ];
        }

        return ['categories' => $categories];
    }

    /**
     * Every screenshot the guide asks for, in order of first appearance.
     *
     * @return list<string>
     */
    public static function shots(string $locale = 'en'): array
    {
        $shots = [];

        foreach (self::for($locale)['categories'] as $category) {
            foreach ($category['tasks'] as $task) {
                foreach ($task['steps'] as $step) {
                    if (
                        is_string($step['shot'])
                        && $step['shot'] !== ''
                        && ! in_array($step['shot'], $shots, true)
                    ) {
                        $shots[] = $step['shot'];
                    }
                }
            }
        }

        return $shots;
    }

    /**
     * How many tasks the guide covers.
     */
    public static function taskCount(string $locale = 'en'): int
    {
        $count = 0;

        foreach (self::for($locale)['categories'] as $category) {
            $count += count($category['tasks']);
        }

        return $count;
    }

    /**
     * The raw language file, read straight from disk.
     *
     * Deliberately not through the translator: the guide is a structure,
     * not a bag of strings, and `__()` would flatten and cache it as one.
     *
     * @return array<string, mixed>
     */
    private static function source(string $locale): array
    {
        $path = lang_path($locale.'/guide.php');

        if (! File::exists($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }
}

<?php

namespace App\Support;

/**
 * The school's libraries (College, Grade School, High School).
 * One list, defined in config/library.php, used everywhere.
 */
class Libraries
{
    /** @return array<int, array{name: string, label: string, slug: string}> */
    public static function all(): array
    {
        return array_values(config('library.libraries', []));
    }

    /** Canonical names, e.g. 'College Library'. These are the values stored in the database. */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }

    /** Short labels, e.g. 'College'. */
    public static function labels(): array
    {
        return array_column(self::all(), 'label');
    }

    /** Short label for a canonical name, or null when the name is not a known library. */
    public static function labelFor(?string $name): ?string
    {
        foreach (self::all() as $library) {
            if ($library['name'] === $name) {
                return $library['label'];
            }
        }

        return null;
    }

    /**
     * Turn whatever a person typed (name, label or slug, any letter case, any spacing or dashes)
     * into the canonical library name. Returns null for blank, unknown values and for the
     * search-only text "All libraries".
     */
    public static function fromAny(?string $value): ?string
    {
        $needle = self::squash((string) $value);

        if ($needle === '') {
            return null;
        }

        foreach (self::all() as $library) {
            foreach ([$library['name'], $library['label'], $library['slug']] as $candidate) {
                if (self::squash($candidate) === $needle) {
                    return $library['name'];
                }
            }
        }

        return null;
    }

    /** Lowercase letters and digits only: "Grade-School Library " -> "gradeschoollibrary". */
    private static function squash(string $text): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($text)) ?? '';
    }
}

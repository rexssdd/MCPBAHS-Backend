<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Drop-in replacement for the built-in 'boolean' cast.
 *
 * Why this exists:
 * Our pgsql connection runs with PDO::ATTR_EMULATE_PREPARES = true (required
 * for Supabase's Supavisor pooler in transaction mode — see config/database.php).
 * Under emulated prepares, PDO inlines bound values as literals instead of
 * sending typed parameters. Laravel's Connection::prepareBindings() always
 * casts PHP bool -> int before binding, so `true` becomes the bare literal
 * `1`. Postgres has no implicit/assignment cast from integer to boolean, so
 * any insert/update of a genuine boolean column then fails with:
 *   "column ... is of type boolean but expression is of type integer"
 *
 * Fix: on write, store the string 'true'/'false' instead of a PHP bool.
 * Postgres's boolean input parser accepts unquoted string literals like
 * 'true'/'false' (and 't'/'f'), so the value round-trips correctly. On read,
 * cast whatever the driver returns (bool, 't'/'f', 1/0) back to a real PHP bool.
 */
class PgBoolean implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower((string) $value),
            ['1', 't', 'true', 'yes', 'y'],
            true
        );
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $truthy = is_string($value)
            ? in_array(strtolower($value), ['1', 't', 'true', 'yes', 'y'], true)
            : (bool) $value;

        return $truthy ? 'true' : 'false';
    }
}

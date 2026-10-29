<?php

declare(strict_types=1);

namespace Dzkit\Internal;

use Dzkit\DzkitException;

/**
 * Shared input handling (dzkit-spec OPERATIONS.md § 1.2–1.4).
 *
 * @internal
 */
final class Input
{
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /** Every Unicode space (PHP's \s without the u-and-UCP flags misses U+00A0 and U+202F). */
    public const SPACE = '[\s\p{Z}\x{FEFF}]';

    /**
     * A whole number from an int, or from a float with no fractional part (16.0, as JSON
     * or a database may give it). Null for anything else, including 16.5.
     */
    public static function integer(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) && is_finite($value) && floor($value) === $value && abs($value) < 2 ** 53) {
            return (int) $value;
        }
        return null;
    }

    /** Converts ٠–٩ and ۰–۹ to 0–9. */
    public static function latinDigits(string $text): string
    {
        return strtr($text, self::DIGITS);
    }

    /**
     * Text as an identifier parser sees it: Latin digits, trimmed of every Unicode space.
     * Null for anything that isn't a valid UTF-8 string, so parsers can reject it.
     */
    public static function text(mixed $input): ?string
    {
        if (!is_string($input) || preg_match('//u', $input) !== 1) {
            return null;
        }
        return (string) preg_replace('/^' . self::SPACE . '+|' . self::SPACE . '+$/u', '', self::latinDigits($input));
    }

    /** Removes every Unicode space and the given extra separator characters. */
    public static function strip(string $text, string $separators): string
    {
        $extra = $separators === '' ? '' : '|[' . preg_quote($separators, '/') . ']';
        return (string) preg_replace('/(?:' . self::SPACE . $extra . ')+/u', '', $text);
    }

    /** The day, "YYYY-MM-DD" in Algeria (UTC+1, no daylight saving), of a date option; today when null. */
    public static function day(\DateTimeInterface|string|null $at): string
    {
        $algiers = new \DateTimeZone('Africa/Algiers');
        if ($at === null) {
            return (new \DateTimeImmutable('now', $algiers))->format('Y-m-d');
        }
        if (is_string($at)) {
            // Check the shape first: PHP's date functions throw on some inputs (NUL bytes).
            $d = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $at) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $at, $algiers) : false;
            if ($d === false || $d->format('Y-m-d') !== $at) {
                throw new DzkitException(DzkitException::INVALID_OPTION, "Expected a date as YYYY-MM-DD, got {$at}");
            }
            return $at;
        }
        return \DateTimeImmutable::createFromInterface($at)->setTimezone($algiers)->format('Y-m-d');
    }

    /** Like day(), for validators and parsers: an invalid date gives null instead of throwing. */
    public static function dayOrNull(mixed $at): ?string
    {
        if ($at !== null && !is_string($at) && !$at instanceof \DateTimeInterface) {
            return null;
        }
        try {
            return self::day($at);
        } catch (DzkitException) {
            return null;
        }
    }

    /**
     * An amount in DA as whole centimes, rounded half up on the amount as written
     * (OPERATIONS.md § 1.3): 1.005 → 101, 2.675 → 268. Floats are read through their
     * shortest decimal representation, never multiplied by 100 in binary.
     *
     * @throws DzkitException invalid-amount
     */
    public static function cents(mixed $amount, string $what = 'amount'): int
    {
        if (is_int($amount)) {
            if ($amount < 0 || $amount > intdiv(PHP_INT_MAX, 100)) {
                throw new DzkitException(DzkitException::INVALID_AMOUNT, "Expected a non-negative {$what}, got {$amount}");
            }
            return $amount * 100;
        }
        if (!is_float($amount) || !is_finite($amount) || $amount < 0) {
            $shown = is_scalar($amount) ? var_export($amount, true) : get_debug_type($amount);
            throw new DzkitException(DzkitException::INVALID_AMOUNT, "Expected a non-negative {$what}, got {$shown}");
        }
        // Shortest representation that reads back as the same float, e.g. "1.005".
        $text = json_encode($amount, JSON_PRESERVE_ZERO_FRACTION);
        if (stripos($text, 'e') !== false) {
            if ($amount >= 1) {
                throw new DzkitException(DzkitException::INVALID_AMOUNT, "Amount too large: {$text}");
            }
            $text = sprintf('%.20F', $amount);
        }
        [$int, $frac] = array_pad(explode('.', $text, 2), 2, '');
        $digits = substr($frac . '000', 0, 3);
        if (strlen($int) > 16) {
            throw new DzkitException(DzkitException::INVALID_AMOUNT, "Amount too large: {$text}");
        }
        $cents = (int) $int * 100 + (int) substr($digits, 0, 2);
        if ((int) $digits[2] >= 5) {
            $cents++;
        }
        return $cents;
    }

    /** Integer division rounding towards negative infinity. */
    public static function floorDiv(int $a, int $b): int
    {
        $q = intdiv($a, $b);
        return ($a % $b !== 0 && (($a < 0) !== ($b < 0))) ? $q - 1 : $q;
    }

    /**
     * Σ cents × millionths, divided by 1 000 000 and rounded half up, exactly and without
     * overflow: each product is split as (q·10⁶ + r)·ppm, so no intermediate exceeds 2⁶³.
     *
     * @param list<array{int, int}> $terms [cents, rate in millionths]
     */
    public static function sumPpmHalfUp(array $terms): int
    {
        $whole = 0;
        $frac = 0;
        foreach ($terms as [$cents, $ppm]) {
            $whole += intdiv($cents, 1_000_000) * $ppm;
            $frac += ($cents % 1_000_000) * $ppm;
        }
        return $whole + self::floorDiv($frac + 500_000, 1_000_000);
    }

    /** A rate as an integer number of millionths (0.23 → 230 000), for exact arithmetic. */
    public static function ppm(float|int $rate): int
    {
        return (int) round($rate * 1_000_000);
    }
}

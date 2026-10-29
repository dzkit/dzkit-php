<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * One of the 69 wilayas, with its official code and names.
 *
 * Codes 1–48: loi n° 84-09 (1984) and décret n° 84-79. Codes 49–58: loi n° 19-12
 * (11 December 2019) and décret présidentiel n° 21-117. Codes 59–69: loi n° 26-06
 * (4 April 2026) and décret présidentiel n° 26-206. Generated from dzkit-spec (rule
 * administrative.wilayas), with its sources.
 */
final class Wilaya
{
    /** @var list<self>|null */
    private static ?array $all = null;

    private function __construct(
        /** Official code, 1–69 (also used on licence plates). */
        public readonly int $code,
        /** Name in French / Latin script. */
        public readonly string $name,
        /** Name in Arabic. */
        public readonly string $nameAr,
        /** For wilayas created after 1984: the code of the wilaya it was created from. */
        public readonly ?int $parent = null,
        /** For wilayas created after 1984: first day it exists, "YYYY-MM-DD". */
        public readonly ?string $validFrom = null,
    ) {
    }

    /**
     * Every wilaya, by code (1 to 69), whatever the date.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return self::$all ??= array_map(
            fn (array $w) => new self($w['code'], $w['name'], $w['name_ar'], $w['parent'] ?? null, $w['valid_from'] ?? null),
            SpecData::WILAYAS,
        );
    }

    /**
     * Looks up a wilaya by its official code: an integer, or text of 1–3 digits ("9",
     * "09", "016", "٠٩"). Returns null for anything else ("16a", "1e1", 16.5), and for a
     * wilaya that doesn't exist yet on the day `$at` (today in Algeria by default).
     */
    public static function get(mixed $code, \DateTimeInterface|string|null $at = null): ?self
    {
        $w = self::byCode($code);
        $day = Input::dayOrNull($at);
        if ($w === null || $day === null || $day < SpecData::WILAYAS_VALID_FROM) {
            return null;
        }
        if ($w->validFrom !== null && $day < $w->validFrom) {
            return null;
        }
        return $w;
    }

    /**
     * The wilaya with this code, whatever the date. Used for plates, which have their own dates.
     *
     * @internal
     */
    public static function byCode(mixed $code): ?self
    {
        if (is_int($code) || is_float($code)) {
            $n = Input::integer($code);
            if ($n === null) {
                return null;
            }
        } else {
            $text = Input::text($code);
            if ($text === null || !preg_match('/^[0-9]{1,3}$/', $text)) {
                return null;
            }
            $n = (int) $text;
        }
        $w = self::all()[$n - 1] ?? null;
        return $w !== null && $w->code === $n ? $w : null;
    }
}

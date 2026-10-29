<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * Algerian licence plates: SERIAL-CYY-WW, e.g. "02639-126-09".
 *
 *   SERIAL  1–6 digits, chronological number
 *   C       vehicle category (1 = passenger car … 9 = motorcycle)
 *   YY      last two digits of the year of first registration
 *   WW      wilaya code, 01–69 (59–69 issued from 1 January 2027)
 *
 * Categories and dates are generated from dzkit-spec (rule vehicles.plates).
 */
final class Plate
{
    /** @var array<int, array{fr: string, ar: string, en: string}> */
    public const CATEGORIES = SpecData::PLATE_CATEGORIES;

    /** First day plates with wilaya codes 59–69 are issued, "YYYY-MM-DD" (Algeria). */
    public const NEW_WILAYAS_FROM = SpecData::PLATE_NEW_WILAYAS_FROM;

    /**
     * Parses a plate written with spaces, dashes or nothing between groups, with Latin or
     * Arabic-Indic digits, as of the day `$at` (today in Algeria by default). Null for
     * anything else, including codes 59–69 before 1 January 2027.
     */
    public static function parse(mixed $input, \DateTimeInterface|string|null $at = null): ?ParsedPlate
    {
        $s = Input::text($input);
        if ($s === null) {
            return null;
        }
        $sp = Input::SPACE;
        if (
            !preg_match('/^([0-9]{1,6})(?:' . $sp . '|-)+([1-9])([0-9]{2})(?:' . $sp . '|-)+([0-9]{2})$/u', $s, $m)
            && !preg_match('/^([0-9]{5,6})([1-9])([0-9]{2})([0-9]{2})$/', Input::strip($s, ''), $m)
        ) {
            return null;
        }
        [, $serial, $category, $year, $ww] = $m;
        if (preg_match('/^0+$/', $serial) || !isset(self::CATEGORIES[(int) $category])) {
            return null;
        }
        $wilaya = Wilaya::byCode($ww);
        $day = Input::dayOrNull($at);
        if ($wilaya === null || $day === null || $wilaya->code > SpecData::PLATE_WILAYA_MAX) {
            return null;
        }
        if ($wilaya->code > SpecData::PLATE_WILAYA_MAX_BEFORE && $day < SpecData::PLATE_NEW_WILAYAS_FROM) {
            return null;
        }
        return new ParsedPlate($serial, (int) $category, self::CATEGORIES[(int) $category]['fr'], $year, $wilaya);
    }

    /** Whether the value is a valid plate on the day `$at` (today by default). */
    public static function isValid(mixed $input, \DateTimeInterface|string|null $at = null): bool
    {
        return self::parse($input, $at) !== null;
    }

    /** "02639-126-09": serial padded to 5 digits, wilaya on 2. Null if the plate isn't valid. */
    public static function format(mixed $input, \DateTimeInterface|string|null $at = null): ?string
    {
        $p = self::parse($input, $at);
        if ($p === null) {
            return null;
        }
        return str_pad($p->serial, SpecData::PLATE_SERIAL_DISPLAY_DIGITS, '0', STR_PAD_LEFT)
            . "-{$p->category}{$p->year}-" . str_pad((string) $p->wilaya->code, 2, '0', STR_PAD_LEFT);
    }
}

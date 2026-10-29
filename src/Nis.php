<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;

/**
 * NIS (numéro d'identification statistique, issued by the ONS): 15 digits, plus a 3-digit
 * sequence number (18 digits) for a secondary establishment (décret exécutif n° 97-396).
 * No check-digit algorithm is published, so this checks the format only.
 */
final class Nis
{
    /** Whether the value has the format of a NIS (spaces, dots and dashes allowed). */
    public static function isValid(mixed $input): bool
    {
        return Nif::hasLength(Nif::compact($input), SpecData::NIS_LENGTHS);
    }

    /** "098 716 010 002 343" → "098716010002343"; null if not text. */
    public static function compact(mixed $input): ?string
    {
        return Nif::compact($input);
    }
}

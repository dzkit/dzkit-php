<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * NIF (numéro d'identification fiscale): 15 digits, or 20 for a branch or secondary
 * establishment. No check-digit algorithm is published, so this checks the format only;
 * to confirm a NIF exists, use the DGI portal (nifenligne.mf.gov.dz).
 */
final class Nif
{
    /** Whether the value has the format of a NIF (spaces, dots and dashes allowed). */
    public static function isValid(mixed $input): bool
    {
        return self::hasLength(self::compact($input), SpecData::NIF_LENGTHS);
    }

    /** "408 020 000 150 039" → "408020000150039"; null if not text. */
    public static function compact(mixed $input): ?string
    {
        $text = Input::text($input);
        return $text === null ? null : Input::strip($text, '.-');
    }

    /**
     * @param list<int> $lengths
     * @internal
     */
    public static function hasLength(?string $digits, array $lengths): bool
    {
        return $digits !== null && preg_match('/^[0-9]+$/', $digits) === 1 && in_array(strlen($digits), $lengths, true);
    }
}

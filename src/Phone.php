<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * Algerian phone numbers (+213), following ARPCE's national numbering plan.
 *
 *   mobile   0[5|6|7]XXXXXXXX  (10 digits): 05 Ooredoo, 06 Mobilis, 07 Djezzy
 *   landline 0[2|3|4]XXXXXXX   (9 digits): 2-digit area code + 6 digits
 *   service  08XXXXXXXX        (10 digits): 0800 freephone, 0801–0809 value-added services
 *   VoIP     09XXXXXXXX        (10 digits): 0982 non-geographic VoIP, 0961 VSAT
 *
 * `allocated` tells whether the number is in a block ARPCE has assigned; validation
 * doesn't require it unless asked, because ARPCE assigns new blocks over time. The
 * operator comes from the prefix: Algeria has no mobile number portability today.
 */
final class Phone
{
    public const MOBILE = 'mobile';
    public const LANDLINE = 'landline';
    public const SERVICE = 'service';
    public const VOIP = 'voip';
    public const TYPES = [self::MOBILE, self::LANDLINE, self::SERVICE, self::VOIP];

    public const NATIONAL = 'national';
    public const INTERNATIONAL = 'international';

    /** Mobile blocks ARPCE has allocated, as 3-digit prefixes after the 0 (inclusive ranges). */
    public const MOBILE_BLOCKS = SpecData::PHONE_MOBILE_BLOCKS;

    /**
     * Parses a phone number: 0551…, +213551…, 00213551…, 213551…, with spaces, dots,
     * dashes, slashes or parentheses, and Latin or Arabic-Indic digits. Null for anything else.
     */
    public static function parse(mixed $input): ?ParsedPhone
    {
        $n = self::nationalNumber($input);
        if ($n === null) {
            return null;
        }
        if (preg_match('/^[567][0-9]{8}$/', $n)) {
            $operator = SpecData::PHONE_OPERATORS[(int) $n[0]];
            $allocated = self::inBlocks((int) substr($n, 0, 3), SpecData::PHONE_MOBILE_BLOCKS[$operator]);
            return new ParsedPhone(self::MOBILE, $n, operator: $operator, allocated: $allocated);
        }
        if (preg_match('/^8[0-9]{8}$/', $n)) {
            $block = (int) substr($n, 0, 3);
            return new ParsedPhone(
                self::SERVICE,
                $n,
                allocated: self::inBlocks($block, SpecData::PHONE_SERVICE_BLOCKS),
                freephone: self::inBlocks($block, SpecData::PHONE_FREEPHONE_BLOCKS),
            );
        }
        if (preg_match('/^9[0-9]{8}$/', $n)) {
            return new ParsedPhone(self::VOIP, $n, allocated: self::inBlocks((int) substr($n, 0, 3), SpecData::PHONE_VOIP_BLOCKS));
        }
        if (preg_match('/^[234][0-9]{7}$/', $n)) {
            return new ParsedPhone(self::LANDLINE, $n, areaCode: substr($n, 0, 2));
        }
        return null;
    }

    /**
     * Whether the value is a phone number, optionally of one type (`$type`) and in an
     * allocated block (`$allocatedOnly`; not checked for landlines).
     *
     * @throws DzkitException invalid-option for an unknown type
     */
    public static function isValid(mixed $input, ?string $type = null, bool $allocatedOnly = false): bool
    {
        if ($type !== null && !in_array($type, self::TYPES, true)) {
            throw new DzkitException(DzkitException::INVALID_OPTION, "Unknown phone type \"{$type}\"; expected one of " . implode(', ', self::TYPES));
        }
        $p = self::parse($input);
        if ($p === null || ($type !== null && $p->type !== $type)) {
            return false;
        }
        return !($allocatedOnly && $p->allocated === false);
    }

    /** Whether the value is a mobile number (optionally in an allocated block). */
    public static function isValidMobile(mixed $input, bool $allocatedOnly = false): bool
    {
        return self::isValid($input, self::MOBILE, $allocatedOnly);
    }

    /** "Ooredoo", "Mobilis" or "Djezzy" for a mobile number; null otherwise. */
    public static function operator(mixed $input): ?string
    {
        return self::parse($input)?->operator;
    }

    /**
     * "0551 23 45 67" (national) or "+213 551 23 45 67" (international); landlines
     * "021 23 45 67" / "+213 21 23 45 67". Null if the number isn't valid.
     *
     * @throws DzkitException invalid-option for an unknown style
     */
    public static function format(mixed $input, string $style = self::NATIONAL): ?string
    {
        if ($style !== self::NATIONAL && $style !== self::INTERNATIONAL) {
            throw new DzkitException(DzkitException::INVALID_OPTION, "Unknown phone style \"{$style}\"; expected national or international");
        }
        $p = self::parse($input);
        if ($p === null) {
            return null;
        }
        $n = $p->nationalNumber;
        $head = $p->type === self::LANDLINE ? 2 : 3;
        $rest = substr($n, 0, $head) . ' ' . implode(' ', str_split(substr($n, $head), 2));
        return $style === self::NATIONAL ? "0{$rest}" : "+213 {$rest}";
    }

    private static function nationalNumber(mixed $input): ?string
    {
        $text = Input::text($input);
        if ($text === null) {
            return null;
        }
        $s = Input::strip($text, '.-()/');
        if (str_starts_with($s, '+213')) {
            $s = substr($s, 4);
        } elseif (str_starts_with($s, '00213')) {
            $s = substr($s, 5);
        } elseif (str_starts_with($s, '213') && (strlen($s) === 11 || strlen($s) === 12)) {
            $s = substr($s, 3);
        } elseif (str_starts_with($s, '0')) {
            $s = substr($s, 1);
        } else {
            return null;
        }
        if (str_starts_with($s, '0')) {
            $s = substr($s, 1); // "+213 0551…" is a common typing mistake
        }
        return preg_match('/^[0-9]+$/', $s) ? $s : null;
    }

    /** @param list<array{int, int}> $blocks */
    private static function inBlocks(int $prefix, array $blocks): bool
    {
        foreach ($blocks as [$from, $to]) {
            if ($prefix >= $from && $prefix <= $to) {
                return true;
            }
        }
        return false;
    }
}

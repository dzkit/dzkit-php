<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * Algérie Poste RIP (relevé d'identité postale) and CCP keys.
 *
 * RIP layout (20 digits): 007 99999 CCCCCCCCCC KK
 *   007        bank code (CCP)
 *   99999      branch code
 *   CCCCCCCCCC CCP account number, left-padded with zeros to 10 digits
 *   KK         RIP key = 97 − ((account × 100 + 85) mod 97), on 2 digits (01–97)
 *
 * Codes and key parameters are generated from dzkit-spec (rule banking.ccp-rip).
 * Every method accepts any value and never throws: invalid input gives false or null.
 */
final class Rip
{
    public const PREFIX = SpecData::RIP_BANK_CODE . SpecData::RIP_BRANCH_CODE;

    private const SEPARATORS = '.-_/';

    /** Whether the value is a valid 20-digit RIP (spaces, dots, dashes, underscores and slashes allowed). */
    public static function isValid(mixed $rip): bool
    {
        return self::parse($rip) !== null;
    }

    /** Parses and checks a RIP. Null when the format or the key is wrong. */
    public static function parse(mixed $rip): ?ParsedRip
    {
        $r = self::digits($rip);
        if ($r === null || !preg_match('/^[0-9]{20}$/', $r) || !str_starts_with($r, self::PREFIX)) {
            return null;
        }
        $account = substr($r, 8, 10);
        $key = substr($r, 18);
        if (self::key($account) !== $key) {
            return null;
        }
        return new ParsedRip(substr($r, 0, 3), substr($r, 3, 5), $account, $key, (string) Ccp::key($account));
    }

    /** The full 20-digit RIP of a CCP account number (text or non-negative integer); null if it isn't one. */
    public static function fromCcp(mixed $account): ?string
    {
        $acc = self::account($account);
        return $acc === null ? null : self::PREFIX . $acc . self::key($acc);
    }

    /** RIP key of a CCP account, on 2 digits; null if the account isn't valid. */
    public static function key(mixed $account): ?string
    {
        $acc = self::account($account);
        if ($acc === null) {
            return null;
        }
        ['modulus' => $m, 'multiplier' => $k, 'offset' => $o] = SpecData::RIP_KEY;
        return str_pad((string) ($m - (((int) $acc * $k + $o) % $m)), 2, '0', STR_PAD_LEFT);
    }

    /** A valid RIP as "007 99999 0023115007 97"; null if it isn't valid. */
    public static function format(mixed $rip): ?string
    {
        $p = self::parse($rip);
        return $p === null ? null : "{$p->bankCode} {$p->branchCode} {$p->account} {$p->key}";
    }

    /**
     * 10-digit account, from text or a non-negative integer; null when it isn't one.
     *
     * @internal
     */
    public static function account(mixed $account): ?string
    {
        if (is_int($account) || is_float($account)) {
            $n = Input::integer($account);
            $raw = $n !== null && $n >= 0 ? (string) $n : null;
        } else {
            $raw = self::digits($account);
        }
        if ($raw === null || $raw === '' || strlen($raw) > SpecData::RIP_ACCOUNT_DIGITS || !preg_match('/^[0-9]+$/', $raw)) {
            return null;
        }
        return str_pad($raw, SpecData::RIP_ACCOUNT_DIGITS, '0', STR_PAD_LEFT);
    }

    private static function digits(mixed $input): ?string
    {
        $text = Input::text($input);
        return $text === null ? null : Input::strip($text, self::SEPARATORS);
    }
}

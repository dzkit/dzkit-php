<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * Algérie Poste CCP account key ("clé CCP"), written after the account number on cheques:
 * the digits weighted 4, 5, 6 … 13 from the right, modulo 100, on 2 digits.
 */
final class Ccp
{
    /** CCP key of an account (text or non-negative integer); null if the account isn't valid. */
    public static function key(mixed $account): ?string
    {
        $acc = Rip::account($account);
        if ($acc === null) {
            return null;
        }
        $sum = 0;
        foreach (SpecData::CCP_KEY_WEIGHTS_FROM_RIGHT as $i => $weight) {
            $sum += (int) $acc[strlen($acc) - 1 - $i] * $weight;
        }
        return str_pad((string) ($sum % SpecData::CCP_KEY_MODULUS), 2, '0', STR_PAD_LEFT);
    }

    /** Whether `$key` (1 or 2 digits, text or integer) is the CCP key of `$account`. */
    public static function isValid(mixed $account, mixed $key): bool
    {
        $expected = self::key($account);
        if (is_int($key) || is_float($key)) {
            $n = Input::integer($key);
            $k = $n !== null && $n >= 0 ? (string) $n : null;
        } else {
            $text = Input::text($key);
            $k = $text === null ? null : Input::strip($text, '.-_/');
        }
        if ($expected === null || $k === null || !preg_match('/^[0-9]{1,2}$/', $k)) {
            return false;
        }
        return $expected === str_pad($k, 2, '0', STR_PAD_LEFT);
    }
}

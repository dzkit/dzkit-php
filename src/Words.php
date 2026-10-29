<?php

declare(strict_types=1);

namespace Dzkit;

use Dzkit\Internal\Input;

/**
 * Numbers and DZD amounts in words, in French and Arabic, for invoices and cheques
 * (dzkit-spec rule language.amount-in-words).
 *
 * French uses the traditional spelling: hyphens below 100, "quatre-vingts" / "quatre-vingt-un",
 * "deux cents" / "deux cent un", "mille" invariable, "un million de dinars".
 *
 * Arabic applies the counted-noun rules to the last two digits: 1 singular + واحد, 2 dual,
 * 3–10 plural (number in ة form), 11–99 singular accusative, otherwise singular. A number
 * directly followed by the noun it counts takes the construct form: مائتا دينار، ألفا دينار،
 * خمسون ألف دينار.
 *
 * Amounts go from 0 to 999 999 999 999.99 DA and are rounded half up to the centime, on
 * the amount as written. Anything else throws DzkitException invalid-amount.
 */
final class Words
{
    /** Largest whole number written in words: just under 1 000 billion. */
    public const MAX_NUMBER = 999_999_999_999;

    public const FR = 'fr';
    public const AR = 'ar';

    // ---------- French ----------

    private const FR_UNITS = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
        'dix-sept', 'dix-huit', 'dix-neuf',
    ];
    private const FR_TENS = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante'];

    /** Integer to French words: 1 280 000 → "un million deux cent quatre-vingt mille". */
    public static function toWordsFr(mixed $n): string
    {
        return self::wordsFr(self::checkNumber($n));
    }

    /** DZD amount in French: 1250.5 → "mille deux cent cinquante dinars algériens et cinquante centimes". */
    public static function amountFr(mixed $amount): string
    {
        [$whole, $cents] = self::split($amount);
        $noun = $whole >= 2 ? 'dinars algériens' : 'dinar algérien';
        $de = $whole >= 1_000_000 && $whole % 1_000_000 === 0 ? 'de ' : '';
        $out = self::wordsFr($whole) . " {$de}{$noun}";
        if ($cents > 0) {
            $out .= ' et ' . self::wordsFr($cents) . ' ' . ($cents >= 2 ? 'centimes' : 'centime');
        }
        return $out;
    }

    private static function frBelow100(int $n, bool $final): string
    {
        if ($n < 20) {
            return self::FR_UNITS[$n];
        }
        $t = intdiv($n, 10);
        $u = $n % 10;
        if ($t === 7 || $t === 9) {
            if ($t === 7 && $u === 1) {
                return 'soixante et onze';
            }
            return ($t === 7 ? 'soixante' : 'quatre-vingt') . '-' . self::FR_UNITS[10 + $u];
        }
        if ($t === 8) {
            return $u === 0 ? ($final ? 'quatre-vingts' : 'quatre-vingt') : 'quatre-vingt-' . self::FR_UNITS[$u];
        }
        if ($u === 0) {
            return self::FR_TENS[$t];
        }
        return $u === 1 ? self::FR_TENS[$t] . ' et un' : self::FR_TENS[$t] . '-' . self::FR_UNITS[$u];
    }

    /** 0–999. `$final`: nothing follows except possibly millions/milliards (which take the plural). */
    private static function frBelow1000(int $n, bool $final): string
    {
        $h = intdiv($n, 100);
        $r = $n % 100;
        $parts = [];
        if ($h > 0) {
            $parts[] = $h === 1 ? 'cent' : self::FR_UNITS[$h] . ' ' . ($r === 0 && $final ? 'cents' : 'cent');
        }
        if ($r > 0 || $h === 0) {
            $parts[] = self::frBelow100($r, $final);
        }
        return implode(' ', $parts);
    }

    private static function wordsFr(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }
        $parts = [];
        $rest = $n;
        foreach ([[1_000_000_000, 'milliard', 'milliards'], [1_000_000, 'million', 'millions']] as [$value, $one, $many]) {
            $count = intdiv($rest, $value);
            $rest %= $value;
            if ($count > 0) {
                // million and milliard are nouns: "deux cents millions", "quatre-vingts millions".
                $parts[] = $count === 1 ? "un {$one}" : self::wordsFr($count) . " {$many}";
            }
        }
        $thousands = intdiv($rest, 1000);
        $units = $rest % 1000;
        if ($thousands > 0) {
            // "mille" is invariable and the number before it never takes the plural.
            $parts[] = $thousands === 1 ? 'mille' : self::frBelow1000($thousands, false) . ' mille';
        }
        if ($units > 0) {
            $parts[] = self::frBelow1000($units, true);
        }
        return implode(' ', $parts);
    }

    // ---------- Arabic ----------

    private const AR_ONES = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة'];
    private const AR_TENS = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    private const AR_HUNDREDS = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

    /** Forms of a counted noun: singular, dual, plural (after 3–10), accusative (after 11–99), dual in construct. */
    private const AR_SCALES = [
        [1_000_000_000, ['singular' => 'مليار', 'dual' => 'ملياران', 'plural' => 'مليارات', 'accusative' => 'مليارا', 'dual_construct' => 'مليارا']],
        [1_000_000, ['singular' => 'مليون', 'dual' => 'مليونان', 'plural' => 'ملايين', 'accusative' => 'مليونا', 'dual_construct' => 'مليونا']],
        [1_000, ['singular' => 'ألف', 'dual' => 'ألفان', 'plural' => 'آلاف', 'accusative' => 'ألفا', 'dual_construct' => 'ألفا']],
    ];
    private const AR_DINAR = ['singular' => 'دينار', 'dual' => 'ديناران', 'plural' => 'دنانير', 'accusative' => 'دينارا'];
    private const AR_ALGERIAN = ['singular' => 'جزائري', 'dual' => 'جزائريان', 'plural' => 'جزائرية', 'accusative' => 'جزائريا'];
    private const AR_CENTIME = ['singular' => 'سنتيم', 'dual' => 'سنتيمان', 'plural' => 'سنتيمات', 'accusative' => 'سنتيما'];

    /** Integer to Arabic words: 2500 → "ألفان وخمسمائة", 200000 → "مائتا ألف". */
    public static function toWordsAr(mixed $n): string
    {
        return self::wordsAr(self::checkNumber($n), false);
    }

    /** DZD amount in Arabic: 1250.5 → "ألف ومائتان وخمسون دينارا جزائريا وخمسون سنتيما". */
    public static function amountAr(mixed $amount): string
    {
        [$whole, $cents] = self::split($amount);
        $out = self::arAmountPart($whole, self::AR_DINAR, self::AR_ALGERIAN);
        if ($cents > 0) {
            $out .= ' و' . self::arAmountPart($cents, self::AR_CENTIME, null);
        }
        return $out;
    }

    private static function arBelow100(int $n): string
    {
        if ($n <= 10) {
            return self::AR_ONES[$n];
        }
        if ($n < 20) {
            return match ($n) {
                11 => 'أحد عشر',
                12 => 'اثنا عشر',
                default => self::AR_ONES[$n - 10] . ' عشر',
            };
        }
        $t = intdiv($n, 10);
        $u = $n % 10;
        return $u === 0 ? self::AR_TENS[$t] : self::AR_ONES[$u] . ' و' . self::AR_TENS[$t];
    }

    /** 0–999. `$construct`: a noun follows directly, so 200 is written مائتا. */
    private static function arBelow1000(int $n, bool $construct): string
    {
        $h = intdiv($n, 100);
        $r = $n % 100;
        if ($h === 0) {
            return self::arBelow100($r);
        }
        if ($r === 0) {
            return $h === 2 && $construct ? 'مائتا' : self::AR_HUNDREDS[$h];
        }
        return self::AR_HUNDREDS[$h] . ' و' . self::arBelow100($r);
    }

    /**
     * "count + scale noun". The count is followed by the noun, so it is in construct form
     * (مائتا ألف). `$construct`: the scale noun itself is followed by another noun.
     */
    private static function arCounted(int $count, array $noun, bool $construct): string
    {
        if ($count === 1) {
            return $noun['singular'];
        }
        if ($count === 2) {
            return $construct ? $noun['dual_construct'] : $noun['dual'];
        }
        $words = self::arBelow1000($count, true);
        $last2 = $count % 100;
        if ($last2 >= 3 && $last2 <= 10) {
            return "{$words} {$noun['plural']}";
        }
        if ($last2 >= 11) {
            return $words . ' ' . ($construct ? $noun['singular'] : $noun['accusative']);
        }
        return "{$words} {$noun['singular']}";
    }

    private static function wordsAr(int $n, bool $construct): string
    {
        if ($n === 0) {
            return 'صفر';
        }
        $parts = [];
        $rest = $n;
        foreach (self::AR_SCALES as [$value, $noun]) {
            $count = intdiv($rest, $value);
            $rest %= $value;
            if ($count === 0) {
                continue;
            }
            // The scale noun is in construct only if it ends the number.
            $last = $construct && $rest === 0;
            $parts[] = $count >= 1000 ? self::wordsAr($count, true) . " {$noun['singular']}" : self::arCounted($count, $noun, $last);
        }
        if ($rest > 0) {
            $parts[] = self::arBelow1000($rest, $construct);
        }
        return implode(' و', $parts);
    }

    private static function arForm(int $count): string
    {
        if ($count === 1) {
            return 'singular';
        }
        if ($count === 2) {
            return 'dual';
        }
        $last2 = $count % 100;
        if ($last2 >= 3 && $last2 <= 10) {
            return 'plural';
        }
        return $last2 >= 11 ? 'accusative' : 'singular';
    }

    private static function arAmountPart(int $count, array $noun, ?array $adjective): string
    {
        $form = self::arForm($count);
        $words = $noun[$form] . ($adjective !== null ? ' ' . $adjective[$form] : '');
        return match ($count) {
            0 => "صفر {$words}",
            1 => "{$words} واحد",
            2 => $words,
            default => self::wordsAr($count, true) . " {$words}",
        };
    }

    // ---------- Both ----------

    /**
     * DZD amount in French ("fr") or Arabic ("ar").
     *
     * @throws DzkitException invalid-amount, or invalid-option for another language
     */
    public static function amount(mixed $amount, string $lang = self::FR): string
    {
        return match ($lang) {
            self::FR => self::amountFr($amount),
            self::AR => self::amountAr($amount),
            default => throw new DzkitException(DzkitException::INVALID_OPTION, "Unknown language \"{$lang}\"; expected fr or ar"),
        };
    }

    /** @return array{int, int} whole dinars and centimes */
    private static function split(mixed $amount): array
    {
        $cents = Input::cents($amount);
        $whole = intdiv($cents, 100);
        self::checkNumber($whole);
        return [$whole, $cents % 100];
    }

    /** An integer from 0 to MAX_NUMBER (a float with no fractional part, such as 3.0, counts). */
    private static function checkNumber(mixed $n): int
    {
        if (is_float($n) && is_finite($n) && floor($n) === $n && abs($n) <= self::MAX_NUMBER + 1) {
            $n = (int) $n;
        }
        if (!is_int($n) || $n < 0 || $n > self::MAX_NUMBER) {
            $shown = is_scalar($n) ? var_export($n, true) : get_debug_type($n);
            throw new DzkitException(DzkitException::INVALID_AMOUNT, 'Expected an integer from 0 to ' . self::MAX_NUMBER . ", got {$shown}");
        }
        return $n;
    }
}

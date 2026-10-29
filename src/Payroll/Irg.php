<?php

declare(strict_types=1);

namespace Dzkit\Payroll;

use Dzkit\DzkitException;
use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * IRG (impôt sur le revenu global) withheld monthly on salaries and pensions, article 104
 * of the Code des impôts directs et taxes assimilées (scale from the 2022 finance law,
 * unchanged for salaries in 2026). Generated from dzkit-spec (rule payroll.irg-salary).
 *
 * On the monthly taxable income (gross minus the employee's social security contribution):
 *   1. taxable ≤ 30 000 DA → no IRG
 *   2. apply the monthly scale, rounded half up to the centime
 *   3. subtract 40 % of it, bounded to [1 000, 1 500] DA
 *   4. salaried, 30 000 < taxable < 35 000 → IRG × 137/51 − 27 925/8
 *      retired or disabled worker, 30 000 < taxable < 42 500 → IRG × 93/61 − 81 213/41
 *   5. round down to the tenth of a dinar
 *
 * Everything is computed with integers (centimes, and tenths of a centime inside), so
 * there is no floating-point error.
 */
final class Irg
{
    public const SALARIED = 'salaried';
    public const RETIRED = 'retired';
    public const DISABLED = 'disabled';
    public const REGIMES = SpecData::IRG_REGIMES;

    /**
     * Monthly IRG to withhold, in DA.
     *
     * @param mixed $taxable monthly taxable income in DA (int or float; anything else throws invalid-amount)
     * @param string $regime salaried (default), retired (régime général) or disabled
     * @param \DateTimeInterface|string|null $at day the salary relates to; today in Algeria by default
     *
     * @throws DzkitException invalid-amount, invalid-option or no-rule-for-date
     */
    public static function calculate(mixed $taxable, string $regime = self::SALARIED, \DateTimeInterface|string|null $at = null): float
    {
        return self::breakdown($taxable, $regime, $at)->irg;
    }

    /**
     * Every step of the monthly IRG, for payslips.
     *
     * @throws DzkitException invalid-amount, invalid-option or no-rule-for-date
     */
    public static function breakdown(mixed $taxable, string $regime = self::SALARIED, \DateTimeInterface|string|null $at = null): IrgBreakdown
    {
        $cents = Input::cents($taxable, 'taxable income');
        if (!in_array($regime, self::REGIMES, true)) {
            throw new DzkitException(DzkitException::INVALID_OPTION, "Unknown IRG regime \"{$regime}\"; expected one of " . implode(', ', self::REGIMES));
        }
        $day = Input::day($at);
        if ($day < SpecData::IRG_VALID_FROM) {
            throw new DzkitException(DzkitException::NO_RULE_FOR_DATE, 'No IRG scale is recorded before ' . SpecData::IRG_VALID_FROM . " (asked for {$day})");
        }

        $scale = self::scaleCents($cents);
        if ($cents <= self::toCents(SpecData::IRG_EXEMPTION_THRESHOLD)) {
            return new IrgBreakdown($cents / 100, $scale / 100, 0.0, 0.0, 0.0, true);
        }

        // From here, amounts are in tenths of a centime (1 DA = 1 000), all integers.
        $scaleT = $scale * 10;
        $abatement = SpecData::IRG_ABATEMENT;
        $abatementT = min(max((int) round($scaleT * $abatement['rate']), (int) ($abatement['min'] * 1000)), (int) ($abatement['max'] * 1000));
        $afterT = $scaleT - $abatementT;

        // IRG in tenths of a dinar, and the second abatement in centimes.
        $tenths = Input::floorDiv($afterT, 100);
        $smoothingCents = 0;
        $smoothing = match (true) {
            $regime === self::SALARIED && $cents < self::toCents(SpecData::IRG_SALARIED_SMOOTHING['below']) => SpecData::IRG_SALARIED_SMOOTHING,
            $regime !== self::SALARIED && $cents < self::toCents(SpecData::IRG_FAVOURABLE_SMOOTHING['below']) => SpecData::IRG_FAVOURABLE_SMOOTHING,
            default => null,
        };
        if ($smoothing !== null) {
            // Smoothed IRG = IRG × a/b − c/d, exactly: (afterT·a·d − 1000·b·c) / (1000·b·d) DA.
            [$a, $b] = $smoothing['multiplier'];
            [$c, $d] = $smoothing['subtract'];
            $num = $afterT * $a * $d - 1000 * $b * $c;
            $den = 1000 * $b * $d;
            $tenths = Input::floorDiv($num * 10, $den);
            // Second abatement = IRG after the first abatement − smoothed IRG, half up to the centime.
            $diffNum = $afterT * $den - 1000 * $num;
            $smoothingCents = Input::floorDiv($diffNum * 200 + 1000 * $den, 2000 * $den);
        }
        return new IrgBreakdown(
            $cents / 100,
            $scale / 100,
            intdiv($abatementT + 5, 10) / 100,
            $smoothingCents / 100,
            max(0, $tenths) / 10,
            false,
        );
    }

    /** Progressive scale in whole centimes, rounded half up. */
    private static function scaleCents(int $taxableCents): int
    {
        $terms = [];
        $lower = 0;
        foreach (SpecData::IRG_MONTHLY_BRACKETS as [$upTo, $rate]) {
            if ($taxableCents <= $lower) {
                break;
            }
            $upper = $upTo === null ? $taxableCents : self::toCents($upTo);
            $terms[] = [min($taxableCents, $upper) - $lower, Input::ppm($rate)];
            if ($upTo === null) {
                break;
            }
            $lower = $upper;
        }
        return Input::sumPpmHalfUp($terms);
    }

    private static function toCents(int|float $da): int
    {
        return (int) round($da * 100);
    }
}

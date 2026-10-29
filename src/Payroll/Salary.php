<?php

declare(strict_types=1);

namespace Dzkit\Payroll;

use Dzkit\DzkitException;
use Dzkit\Generated\SpecData;
use Dzkit\Internal\Input;

/**
 * Gross to net for a regular monthly salary fully subject to CNAS and IRG.
 *
 * CNAS: employee 9 %, employer 25 % + 0.5 % for social services, on the gross salary,
 * rounded half up to the centime (rule payroll.cnas). Non-monthly bonuses (10 % flat
 * withholding), exempt allowances and the minimum-wage floor of the base are out of scope.
 */
final class Salary
{
    public const CNAS_EMPLOYEE_RATE = SpecData::CNAS_EMPLOYEE_RATE;
    public const CNAS_EMPLOYER_RATE = SpecData::CNAS_EMPLOYER_RATE;
    public const CNAS_SOCIAL_SERVICES_RATE = SpecData::CNAS_SOCIAL_SERVICES_RATE;

    /**
     * Largest drop of net pay when gross goes up, in DA: 1.49 DA (salaried) where taxable
     * income passes 30 000 DA, because the law's smoothing formula starts at about 1.5 DA
     * of IRG; 0.1 DA elsewhere. 2 DA leaves a margin.
     */
    private const MAX_NET_DIP_CENTS = 200;

    /**
     * @param \DateTimeInterface|string|null $at day the salary relates to; today in Algeria by default
     * @param float|null $employeeRate custom employee CNAS rate (0 to 1)
     * @param float|null $employerRate custom total employer rate (0 to 1), defaults to 25 % + 0.5 %
     *
     * @throws DzkitException invalid-amount, invalid-option or no-rule-for-date
     */
    public static function grossToNet(
        mixed $gross,
        string $regime = Irg::SALARIED,
        \DateTimeInterface|string|null $at = null,
        ?float $employeeRate = null,
        ?float $employerRate = null,
    ): SalaryResult {
        $grossCents = Input::cents($gross, 'gross salary');
        $employee = self::rate($employeeRate ?? self::CNAS_EMPLOYEE_RATE, 'employeeRate');
        $employer = self::rate($employerRate ?? self::CNAS_EMPLOYER_RATE + self::CNAS_SOCIAL_SERVICES_RATE, 'employerRate');

        $cnasEmployee = Input::sumPpmHalfUp([[$grossCents, $employee]]);
        $taxable = $grossCents - $cnasEmployee;
        $irg = Irg::calculate($taxable / 100, $regime, $at);
        $cnasEmployer = Input::sumPpmHalfUp([[$grossCents, $employer]]);

        return new SalaryResult(
            $grossCents / 100,
            $cnasEmployee / 100,
            $taxable / 100,
            $irg,
            ($taxable - (int) round($irg * 100)) / 100,
            $cnasEmployer / 100,
            ($grossCents + $cnasEmployer) / 100,
        );
    }

    /**
     * Smallest gross salary, to the centime, whose net is at least `$net`.
     * Useful for job offers stated as a net amount.
     *
     * @throws DzkitException invalid-amount, invalid-option or no-rule-for-date
     */
    public static function netToGross(
        mixed $net,
        string $regime = Irg::SALARIED,
        \DateTimeInterface|string|null $at = null,
        ?float $employeeRate = null,
        ?float $employerRate = null,
    ): SalaryResult {
        $target = Input::cents($net, 'net salary');
        $day = Input::day($at); // resolve the date once
        $result = fn (int $cents) => self::grossToNet($cents / 100, $regime, $day, $employeeRate, $employerRate);
        $netAt = fn (int $cents) => (int) round($result($cents)->net * 100);
        if ($target === 0) {
            return $result(0);
        }
        // Net is never above gross, so any gross below the target gives too little:
        // `lo` always fails and `hi` always succeeds.
        $lo = $target - 1;
        $hi = max($lo + 1, $lo * 2);
        while ($netAt($hi) < $target) {
            $lo = $hi;
            $hi *= 2;
        }
        while ($hi - $lo > 1) {
            $mid = intdiv($lo + $hi, 2);
            if ($netAt($mid) >= $target) {
                $hi = $mid;
            } else {
                $lo = $mid;
            }
        }
        // Net is not strictly increasing at the centime, so look a little below the gross
        // found: once the net is more than the largest dip below the target, stop.
        $best = $hi;
        for ($c = $hi - 1; $c >= 0; $c--) {
            $n = $netAt($c);
            if ($n >= $target) {
                $best = $c;
            } elseif ($n < $target - self::MAX_NET_DIP_CENTS) {
                break;
            }
        }
        return $result($best);
    }

    private static function rate(float $rate, string $name): int
    {
        if (!is_finite($rate) || $rate < 0 || $rate > 1) {
            throw new DzkitException(DzkitException::INVALID_OPTION, "{$name} must be a rate between 0 and 1, got {$rate}");
        }
        return Input::ppm($rate);
    }
}

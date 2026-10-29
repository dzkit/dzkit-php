<?php

declare(strict_types=1);

namespace Dzkit\Payroll;

/**
 * Every step of the monthly IRG. Amounts in DA, to the centime. The lines don't always
 * add up exactly, because the IRG is rounded down to 0.1 DA at the end.
 */
final class IrgBreakdown
{
    public function __construct(
        public readonly float $taxable,
        /** Result of the progressive scale, rounded half up to the centime. */
        public readonly float $scaleIrg,
        /** 40 % abatement after its bounds (0 when exempt). */
        public readonly float $abatement,
        /** Second abatement from the smoothing formula (0 when no formula applies). */
        public readonly float $smoothing,
        /** IRG to withhold. */
        public readonly float $irg,
        /** True when taxable income is at or below the exemption threshold (30 000 DA). */
        public readonly bool $exempt,
    ) {
    }
}

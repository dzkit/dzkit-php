<?php

declare(strict_types=1);

namespace Dzkit\Payroll;

/** A monthly salary, gross to net. Amounts in DA, to the centime. */
final class SalaryResult
{
    public function __construct(
        public readonly float $gross,
        public readonly float $cnasEmployee,
        /** Gross minus the employee's CNAS contribution: the base for the IRG. */
        public readonly float $taxable,
        public readonly float $irg,
        public readonly float $net,
        public readonly float $cnasEmployer,
        /** Gross plus the employer's contributions (training and apprenticeship taxes not included). */
        public readonly float $employerCost,
    ) {
    }
}

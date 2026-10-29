<?php

declare(strict_types=1);

namespace Dzkit;

/** A valid licence plate, split into its parts. */
final class ParsedPlate
{
    public function __construct(
        /** Serial as written, without padding. */
        public readonly string $serial,
        /** Vehicle category, 1–9. */
        public readonly int $category,
        /** French name of the category, e.g. "Véhicule de tourisme" (Plate::CATEGORIES has ar and en). */
        public readonly string $categoryName,
        /** Two-digit year of first registration, as written. */
        public readonly string $year,
        public readonly Wilaya $wilaya,
    ) {
    }
}

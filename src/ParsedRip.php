<?php

declare(strict_types=1);

namespace Dzkit;

/** A valid RIP, split into its parts. */
final class ParsedRip
{
    public function __construct(
        /** "007" */
        public readonly string $bankCode,
        /** "99999" */
        public readonly string $branchCode,
        /** CCP account on 10 digits. */
        public readonly string $account,
        /** RIP key on 2 digits. */
        public readonly string $key,
        /** Key you would write after the CCP number on a cheque or form. */
        public readonly string $ccpKey,
    ) {
    }
}

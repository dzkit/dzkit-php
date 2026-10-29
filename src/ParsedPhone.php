<?php

declare(strict_types=1);

namespace Dzkit;

/** A valid phone number, with what its prefix tells. */
final class ParsedPhone
{
    /** "0551234567" */
    public readonly string $national;
    /** "+213551234567" */
    public readonly string $e164;

    public function __construct(
        /** mobile, landline, service or voip (see the Phone constants). */
        public readonly string $type,
        /** Number without country code or leading 0, e.g. "551234567". */
        public readonly string $nationalNumber,
        /** Mobiles: Ooredoo, Mobilis or Djezzy. */
        public readonly ?string $operator = null,
        /** Landlines: 2-digit area code, e.g. "21" for Algiers. */
        public readonly ?string $areaCode = null,
        /** Mobile, service and VoIP numbers: in a block ARPCE has allocated. Null for landlines. */
        public readonly ?bool $allocated = null,
        /** Service numbers: true for 0800 freephone numbers. */
        public readonly ?bool $freephone = null,
    ) {
        $this->national = '0' . $nationalNumber;
        $this->e164 = '+213' . $nationalNumber;
    }
}

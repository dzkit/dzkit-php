<?php

declare(strict_types=1);

namespace Dzkit;

/**
 * Thrown by calculations when the request is impossible (dzkit-spec OPERATIONS.md § 1.1).
 * Validators, parsers and formatters never throw it for their input.
 *
 * `$errorCode` is the same in every dzkit package. (PHP's own getCode() is an integer,
 * so the code lives in its own property.)
 */
final class DzkitException extends \RuntimeException
{
    /** Amount is negative, not a finite number, or larger than the operation supports. */
    public const INVALID_AMOUNT = 'invalid-amount';
    /** An option has a value outside its list, for example an unknown IRG regime. */
    public const INVALID_OPTION = 'invalid-option';
    /** No version of the rule covers the requested date. */
    public const NO_RULE_FOR_DATE = 'no-rule-for-date';

    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

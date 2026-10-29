<?php

declare(strict_types=1);

namespace Dzkit\Tests;

use Dzkit\Ccp;
use Dzkit\DzkitException;
use Dzkit\Nif;
use Dzkit\Payroll\Irg;
use Dzkit\Payroll\Salary;
use Dzkit\Phone;
use Dzkit\Plate;
use Dzkit\Rip;
use Dzkit\Wilaya;
use Dzkit\Words;
use PHPUnit\Framework\TestCase;

/**
 * What the shared test cases can't express in JSON: PHP types (int vs float, objects,
 * resources, invalid UTF-8), DateTime objects, and the exception's shape.
 */
final class PhpSpecificTest extends TestCase
{
    public function testValidatorsNeverThrowWhateverTheyReceive(): void
    {
        $values = [null, true, 0, -1, 1.5, NAN, INF, [], ['0551234567'], new \stdClass(), "\xff\xfe", '', '   ', str_repeat('9', 10000), "0551234567\0", PHP_INT_MAX];
        foreach ($values as $v) {
            $this->assertNull(Phone::parse($v));
            $this->assertFalse(Phone::isValid($v));
            $this->assertNull(Phone::format($v));
            $this->assertNull(Plate::parse($v));
            $this->assertNull(Rip::parse($v));
            $this->assertFalse(Ccp::isValid($v, '99'));
            $this->assertFalse(Ccp::isValid('1234567890', $v));
            $this->assertFalse(Nif::isValid($v));
            $this->assertNull(Wilaya::get(is_array($v) || is_object($v) ? $v : 'x'));
        }
        // A date option with a NUL byte is rejected, not passed to PHP's date parser.
        $this->assertNull(Plate::parse('12345-127-64', "2027-01-01\0"));
    }

    public function testWholeNumberFloatsCountAsIntegers(): void
    {
        // JSON decoders and databases often give 16.0 for 16.
        $this->assertSame('Alger', Wilaya::get(16.0)?->name);
        $this->assertNull(Wilaya::get(16.5));
        $this->assertSame('00799999002311500797', Rip::fromCcp(23115007.0));
        $this->assertNull(Rip::fromCcp(1.5));
        $this->assertTrue(Ccp::isValid(23115007, 32.0));
        $this->assertSame('trois mille', Words::toWordsFr(3000.0));
    }

    public function testDatesAcceptDateTimeObjectsInAlgeriaTime(): void
    {
        // 31 December 2026 at 23:30 UTC is already 1 January 2027 in Algeria (UTC+1).
        $newYear = new \DateTimeImmutable('2026-12-31 23:30:00', new \DateTimeZone('UTC'));
        $this->assertSame(64, Plate::parse('12345-127-64', $newYear)?->wilaya->code);
        $this->assertNull(Plate::parse('12345-127-64', $newYear->modify('-1 hour')));
        $this->assertSame('Barika', Wilaya::get(60, new \DateTime('2026-04-04 12:00', new \DateTimeZone('Africa/Algiers')))?->name);
    }

    public function testErrorsCarryTheirCode(): void
    {
        $code = function (callable $f): string {
            try {
                $f();
            } catch (DzkitException $e) {
                return $e->errorCode;
            }
            return 'no error';
        };
        $this->assertSame(DzkitException::INVALID_OPTION, $code(fn () => Irg::calculate(40_000, 'retraite')));
        $this->assertSame(DzkitException::INVALID_AMOUNT, $code(fn () => Irg::calculate('40000')));
        $this->assertSame(DzkitException::INVALID_AMOUNT, $code(fn () => Salary::grossToNet(-5)));
        $this->assertSame(DzkitException::NO_RULE_FOR_DATE, $code(fn () => Irg::calculate(40_000, at: '2021-12-31')));
        $this->assertSame(DzkitException::INVALID_OPTION, $code(fn () => Irg::calculate(40_000, at: '31/12/2026')));
        $this->assertSame(DzkitException::INVALID_OPTION, $code(fn () => Salary::grossToNet(60_000, employeeRate: 9.0)));
        $this->assertSame(DzkitException::INVALID_OPTION, $code(fn () => Words::amount(1, 'en')));
        $this->assertSame(DzkitException::INVALID_OPTION, $code(fn () => Phone::format('0551234567', 'e164')));
    }

    public function testNetToGrossIsTheSmallestGross(): void
    {
        foreach ([Irg::SALARIED, Irg::RETIRED] as $regime) {
            for ($gross = 20_000.0; $gross <= 80_000; $gross += 1_733.37) {
                $g = round($gross, 2);
                $net = Salary::grossToNet($g, $regime, '2026-10-28')->net;
                $found = Salary::netToGross($net, $regime, '2026-10-28');
                $this->assertGreaterThanOrEqual($net, $found->net);
                $this->assertLessThanOrEqual($g, $found->gross);
                $this->assertLessThan($net, Salary::grossToNet(round($found->gross - 0.01, 2), $regime, '2026-10-28')->net);
            }
        }
    }

    public function testLargeAmountsDontOverflow(): void
    {
        // 64-bit integers would overflow on centimes × millionths without splitting.
        // Checked against an exact rational computation of the scale: 349 999 978 700 DA.
        $this->assertSame(349_999_978_700.0, Irg::calculate(1_000_000_000_000.0, at: '2026-10-28'));
        $this->assertSame(90_000_000_000.0, Salary::grossToNet(1_000_000_000_000, at: '2026-10-28')->cnasEmployee);
    }
}

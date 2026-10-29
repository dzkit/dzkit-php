<?php

declare(strict_types=1);

namespace Dzkit\Tests;

use Dzkit\Ccp;
use Dzkit\Dzkit;
use Dzkit\DzkitException;
use Dzkit\Nif;
use Dzkit\Nis;
use Dzkit\Payroll\Irg;
use Dzkit\Payroll\Salary;
use Dzkit\Phone;
use Dzkit\Plate;
use Dzkit\Rip;
use Dzkit\Wilaya;
use Dzkit\Words;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs every shared test case of dzkit-spec (spec/vectors.json) against this package.
 *
 * Each operation maps to one function and returns the fields the test cases use
 * (snake_case). A case has either `expected` (null means "rejected": null or
 * valid=false) or `error` (a DzkitException with that code).
 */
final class SpecTest extends TestCase
{
    /** The format version this runner understands. A new format needs changes here. */
    private const SUPPORTED_SCHEMA = 3;

    /** @return array<string, callable(array<string, mixed>): ?array<string, mixed>> */
    private static function runners(): array
    {
        $text = fn (?string $t) => $t === null ? null : ['text' => $t];
        $opt = fn (array $in, string $key, mixed $default) => array_key_exists($key, $in) ? $in[$key] : $default;

        return [
            'rip.validate' => function (array $in) {
                $p = Rip::parse($in['rip']);
                return $p === null ? null : ['valid' => true, 'account' => $p->account, 'key' => $p->key];
            },
            'rip.from-ccp' => function (array $in) {
                $rip = Rip::fromCcp($in['account']);
                return $rip === null ? null : ['rip' => $rip, 'ccp_key' => Ccp::key($in['account']), 'rip_key' => substr($rip, 18)];
            },
            'rip.format' => fn (array $in) => $text(Rip::format($in['rip'])),
            'ccp.validate' => fn (array $in) => ['valid' => Ccp::isValid($in['account'], $in['key'])],
            'phone.parse' => function (array $in) {
                $p = Phone::parse($in['number']);
                if ($p === null) {
                    return null;
                }
                return array_filter([
                    'type' => $p->type,
                    'national' => $p->national,
                    'e164' => $p->e164,
                    'operator' => $p->operator,
                    'allocated' => $p->allocated,
                    'area_code' => $p->areaCode,
                    'freephone' => $p->freephone,
                ], fn ($v) => $v !== null);
            },
            'phone.validate' => fn (array $in) => ['valid' => Phone::isValid($in['number'], $opt($in, 'type', null), $opt($in, 'allocated_only', false))],
            'phone.format' => fn (array $in) => $text(Phone::format($in['number'], $opt($in, 'style', Phone::NATIONAL))),
            'plate.parse' => function (array $in) use ($opt) {
                $p = Plate::parse($in['plate'], $opt($in, 'date', null));
                return $p === null ? null : [
                    'serial' => $p->serial,
                    'category' => $p->category,
                    'category_name' => $p->categoryName,
                    'year' => $p->year,
                    'wilaya' => $p->wilaya->code,
                ];
            },
            'plate.format' => fn (array $in) => $text(Plate::format($in['plate'], $opt($in, 'date', null))),
            'nif.validate' => fn (array $in) => ['valid' => Nif::isValid($in['value'])],
            'nis.validate' => fn (array $in) => ['valid' => Nis::isValid($in['value'])],
            'wilaya.lookup' => function (array $in) use ($opt) {
                $w = Wilaya::get($in['code'], $opt($in, 'date', null));
                return $w === null ? null : ['name_fr' => $w->name, 'name_ar' => $w->nameAr, 'parent' => $w->parent, 'valid_from' => $w->validFrom];
            },
            'irg.calculate' => fn (array $in) => ['irg' => Irg::calculate($in['taxable'], $opt($in, 'regime', Irg::SALARIED), $opt($in, 'date', null))],
            'irg.breakdown' => function (array $in) use ($opt) {
                $r = Irg::breakdown($in['taxable'], $opt($in, 'regime', Irg::SALARIED), $opt($in, 'date', null));
                return ['scale_irg' => $r->scaleIrg, 'abatement' => $r->abatement, 'smoothing' => $r->smoothing, 'irg' => $r->irg, 'exempt' => $r->exempt];
            },
            'salary.gross-to-net' => function (array $in) use ($opt) {
                $r = Salary::grossToNet($in['gross'], $opt($in, 'regime', Irg::SALARIED), $opt($in, 'date', null));
                return ['cnas_employee' => $r->cnasEmployee, 'taxable' => $r->taxable, 'irg' => $r->irg, 'net' => $r->net, 'cnas_employer' => $r->cnasEmployer];
            },
            'salary.net-to-gross' => function (array $in) use ($opt) {
                $r = Salary::netToGross($in['net'], $opt($in, 'regime', Irg::SALARIED), $opt($in, 'date', null));
                return ['gross' => $r->gross, 'cnas_employee' => $r->cnasEmployee, 'taxable' => $r->taxable, 'irg' => $r->irg, 'net' => $r->net, 'cnas_employer' => $r->cnasEmployer];
            },
            'words.number-fr' => fn (array $in) => ['text' => Words::toWordsFr($in['number'])],
            'words.amount-fr' => fn (array $in) => ['text' => Words::amountFr($in['amount'])],
            'words.number-ar' => fn (array $in) => ['text' => Words::toWordsAr($in['number'])],
            'words.amount-ar' => fn (array $in) => ['text' => Words::amountAr($in['amount'])],
        ];
    }

    /** @return array<string, mixed> */
    private static function bundle(): array
    {
        static $bundle = null;
        return $bundle ??= json_decode((string) file_get_contents(__DIR__ . '/../spec/vectors.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testFormatVersionIsSupported(): void
    {
        $this->assertSame(self::SUPPORTED_SCHEMA, self::bundle()['schema_version']);
        $this->assertSame(self::bundle()['version'], Dzkit::SPEC_VERSION, 'spec/vectors.json and the generated data come from different dzkit-spec versions');
    }

    public function testEveryOperationHasARunner(): void
    {
        $missing = array_values(array_diff(array_column(self::bundle()['suites'], 'operation'), array_keys(self::runners())));
        $this->assertSame([], $missing);
    }

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function cases(): iterable
    {
        foreach (self::bundle()['suites'] as $suite) {
            foreach ($suite['cases'] as $case) {
                yield "{$suite['operation']} / {$case['id']}" => [$suite['operation'], $case];
            }
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('cases')]
    public function testSharedCase(string $operation, array $case): void
    {
        $run = self::runners()[$operation] ?? null;
        if ($run === null) {
            $this->markTestSkipped("No runner for {$operation}");
        }

        if (array_key_exists('error', $case)) {
            try {
                $got = $run($case['input']);
            } catch (DzkitException $e) {
                $this->assertSame($case['error'], $e->errorCode);
                return;
            }
            $this->fail("Expected DzkitException \"{$case['error']}\", got " . json_encode($got, JSON_UNESCAPED_UNICODE));
        }

        $got = $run($case['input']);
        if ($case['expected'] === null) {
            $this->assertTrue($got === null || ($got['valid'] ?? null) === false, 'Expected a rejection, got ' . json_encode($got, JSON_UNESCAPED_UNICODE));
            return;
        }
        $this->assertNotNull($got, 'Expected a result, got null');
        foreach ($case['expected'] as $field => $value) {
            if ($value === null) {
                continue; // not pinned yet
            }
            $this->assertArrayHasKey($field, $got);
            if ((is_int($value) || is_float($value)) && (is_int($got[$field]) || is_float($got[$field]))) {
                $this->assertSame((float) $value, (float) $got[$field], "Field {$field}");
            } else {
                $this->assertSame($value, $got[$field], "Field {$field}");
            }
        }
    }
}

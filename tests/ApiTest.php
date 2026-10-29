<?php

declare(strict_types=1);

namespace Dzkit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The public API: every class outside Internal/ and Generated/, and its public methods
 * not marked @internal. If this test fails, a name was added, removed or renamed: update
 * the list on purpose and choose the version with the rule in CONTRIBUTING.md (adding is
 * a feature; removing or renaming breaks users).
 */
final class ApiTest extends TestCase
{
    private const API = [
        'Dzkit\\Ccp' => ['isValid', 'key'],
        'Dzkit\\Dzkit' => [],
        'Dzkit\\DzkitException' => [],
        'Dzkit\\Nif' => ['compact', 'isValid'],
        'Dzkit\\Nis' => ['compact', 'isValid'],
        'Dzkit\\ParsedPhone' => [],
        'Dzkit\\ParsedPlate' => [],
        'Dzkit\\ParsedRip' => [],
        'Dzkit\\Payroll\\Irg' => ['breakdown', 'calculate'],
        'Dzkit\\Payroll\\IrgBreakdown' => [],
        'Dzkit\\Payroll\\Salary' => ['grossToNet', 'netToGross'],
        'Dzkit\\Payroll\\SalaryResult' => [],
        'Dzkit\\Phone' => ['format', 'isValid', 'isValidMobile', 'operator', 'parse'],
        'Dzkit\\Plate' => ['format', 'isValid', 'parse'],
        'Dzkit\\Rip' => ['format', 'fromCcp', 'isValid', 'key', 'parse'],
        'Dzkit\\Wilaya' => ['all', 'get'],
        'Dzkit\\Words' => ['amount', 'amountAr', 'amountFr', 'toWordsAr', 'toWordsFr'],
    ];

    public function testExportsExactlyThePublicApi(): void
    {
        $found = [];
        $src = dirname(__DIR__) . '/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($src) + 1, -4);
            if ($file->getExtension() !== 'php' || str_starts_with($relative, 'Internal') || str_starts_with($relative, 'Generated')) {
                continue;
            }
            $class = 'Dzkit\\' . str_replace('/', '\\', $relative);
            $methods = [];
            foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->class === $class && $m->name !== '__construct' && !str_contains((string) $m->getDocComment(), '@internal')) {
                    $methods[] = $m->name;
                }
            }
            sort($methods);
            $found[$class] = $methods;
        }
        ksort($found);
        $this->assertSame(self::API, $found);
    }
}

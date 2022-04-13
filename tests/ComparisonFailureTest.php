<?php declare(strict_types=1);
/*
 * This file is part of sebastian/comparator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Comparator;

use PHPUnit\Framework\TestCase;

/**
 * @covers \SebastianBergmann\Comparator\ComparisonFailure
 *
 * @uses \SebastianBergmann\Comparator\Comparator
 * @uses \SebastianBergmann\Comparator\Factory
 */
final class ComparisonFailureTest extends TestCase
{
    public function testComparisonFailure(): void
    {
        $actual   = "\nB\n";
        $expected = "\nA\n";
        $message  = 'Test message';

        $failure = new ComparisonFailure(
            $expected,
            $actual,
            '|' . $expected,
            '|' . $actual,
            false,
            $message
        );

        $this->assertSame($actual, $failure->getActual());
        $this->assertSame($expected, $failure->getExpected());
        $this->assertSame('|' . $actual, $failure->getActualAsString());
        $this->assertSame('|' . $expected, $failure->getExpectedAsString());

        $diff = '
--- Expected
+++ Actual
@@ @@
 |
-A
+B
';
        $this->assertSame($diff, $failure->getDiff());
        $this->assertSame($message . $diff, $failure->toString());
    }

    public function testComparisonFailurePartialDiff(): void
    {
        $actual = [];
        $expected = [];

        for($i = 1; $i < 10; $i++) {
            $expected[] = sprintf('line%d', $i);
            $actual[] = sprintf($i === 5 ? 'modified line%d' : 'line%d', $i);
        }

        $failure = new ComparisonFailure(
            $expected,
            $actual,
            implode("\n", $expected),
            implode("\n", $actual),
        );

        $diff = <<<TXT
            \n--- Expected
            +++ Actual
            @@ @@
            -skipping 2 lines...
             line3
             line4
            -line5
            +modified line5
             line6
             line7
            +skipping 3 lines...\n
            TXT;

        $this->assertSame($diff, $failure->getPartialDiff(1, 5));
    }

    public function testDiffNotPossible(): void
    {
        $failure = new ComparisonFailure('a', 'b', '', '', true, 'test');
        $this->assertSame('', $failure->getDiff());
        $this->assertSame('test', $failure->toString());
    }
}

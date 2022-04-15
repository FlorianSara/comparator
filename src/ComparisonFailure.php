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

use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Thrown when an assertion for string equality failed.
 */
class ComparisonFailure extends RuntimeException
{
    /**
     * Expected value of the retrieval which does not match $actual.
     *
     * @var mixed
     */
    protected $expected;

    /**
     * Actually retrieved value which does not match $expected.
     *
     * @var mixed
     */
    protected $actual;

    /**
     * The string representation of the expected value.
     *
     * @var string
     */
    protected $expectedAsString;

    /**
     * The string representation of the actual value.
     *
     * @var string
     */
    protected $actualAsString;

    /**
     * @var bool
     */
    protected $identical;

    /**
     * Optional message which is placed in front of the first line
     * returned by toString().
     *
     * @var string
     */
    protected $message;

    /**
     * Initialises with the expected value and the actual value.
     *
     * @param mixed  $expected         expected value retrieved
     * @param mixed  $actual           actual value retrieved
     * @param string $expectedAsString
     * @param string $actualAsString
     * @param bool   $identical
     * @param string $message          a string which is prefixed on all returned lines
     *                                 in the difference output
     */
    public function __construct($expected, $actual, $expectedAsString, $actualAsString, $identical = false, $message = '')
    {
        $this->expected         = $expected;
        $this->actual           = $actual;
        $this->expectedAsString = $expectedAsString;
        $this->actualAsString   = $actualAsString;
        $this->message          = $message;
    }

    public function getActual()
    {
        return $this->actual;
    }

    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @return string
     */
    public function getActualAsString()
    {
        return $this->actualAsString;
    }

    /**
     * @return string
     */
    public function getExpectedAsString()
    {
        return $this->expectedAsString;
    }

    public function getDiff()
    {
        return $this->callDiffer($this->expectedAsString, $this->actualAsString);
    }

    /**   
     * Gets the diff (same as getDiff), but ensure the inputs are not too big for the underlying diffs algorithms.
     * 
     * @param int $threshold triggers line skipping when expectedLines x actualLines > $threshold
     * @param int $lineCount number of lines to display in the diff
     * 
     * @return string
     */
    public function getPartialDiff(int $threshold=1000000, int $lineCount = 20): string
    {
        [$expected, $actual] = $this->skipLines($threshold, $lineCount, $this->expectedAsString, $this->actualAsString);

        return $this->callDiffer($expected, $actual);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->message . $this->getDiff();
    }

    private function callDiffer(string|array $expected, string|array $actual): string
    {
        if (!$actual && !$expected) {
            return '';
        }

        $differ = new Differ(new UnifiedDiffOutputBuilder("\n--- Expected\n+++ Actual\n"));

        return $differ->diff($expected, $actual);
    }

    /**
     * reduce inputs to a $lineCount number of lines when $threshold is hit
     * 
     * @param int $threshold triggers line skipping when expectedLines x actualLines > $threshold
     * @param int $lineCount number of lines to display in the diff
     * @param string $expected
     * @param string $actual
     * 
     * @return array
     */
    private function skipLines(int $threshold, int $lineCount, string $expected, string $actual): array
    {
        $expected = preg_split('/(.*\R)/', $expected, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $actual = preg_split('/(.*\R)/', $actual, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $expectedLines = count($expected);
        $actualLines = count($actual);

        if ($expectedLines * $actualLines < $threshold) {
            return [
                $expected,
                $actual
            ];
        }

        // searching for first diff
        $diffIndex = -1;
        for ($i = 0; $i < $expectedLines && $i < $actualLines; $i++) {
            if ($expected[$i] !== $actual[$i]) {
                $diffIndex = $i;
                break;
            }
        }

        if ($diffIndex < 0) {
            return [
                $expected,
                $actual
            ];
        }

        $from = max($diffIndex - (int)($lineCount / 2), 0);
        $to = min($diffIndex + (int)($lineCount / 2), min($expectedLines, $actualLines));

        $reducedExpected = [];
        $reducedActual = [];

        if ($from > 0) {
            $reducedExpected[] = sprintf("skipping %d lines...\n", $from);
        }

        $reducedExpected = array_merge($reducedExpected, array_slice($expected, $from, $lineCount));
        $reducedActual = array_slice($actual, $from, $lineCount);

        if ($to < $expectedLines) {
            $reducedActual[] = sprintf("skipping %d lines...\n", $expectedLines - $to);
        }

        return [
            $reducedExpected,
            $reducedActual
        ];
    }
}

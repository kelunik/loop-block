<?php

namespace Kelunik\LoopBlock;

use Interop\Async\Loop;

class BlockTest extends \PHPUnit_Framework_TestCase {
    /**
     * @param int $threshold Measure threshold.
     * @param int $interval Check interval.
     * @param int $expectedCallCount Minimum callback calls.
     *
     * @test
     * @dataProvider provideArgs
     */
    public function callsCallbackOnBlock($threshold, $interval, $expectedCallCount) {
        $callCount = 0;

        $detector = new BlockDetector(function () use (&$callCount) {
            $callCount++;
        }, $threshold, $interval);

        Loop::execute(function () use ($detector) {
            $detector->start();

            Loop::repeat(0, function () {
                usleep(100 * 1000);
            });

            Loop::delay(300, function () {
                Loop::stop();
            });
        });

        $this->assertGreaterThanOrEqual($expectedCallCount, $callCount);
    }

    public function provideArgs() {
        return [
            [10, 0, 2],
            [100, 0, 2],
            [200, 0, 1],
            [300, 0, 0],
        ];
    }
}
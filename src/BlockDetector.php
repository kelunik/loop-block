<?php

namespace Kelunik\LoopBlock;

use Interop\Async\Loop;

/**
 * Detects blocking operations in loops.
 */
class BlockDetector {
    private $onBlock;
    private $blockThreshold;
    private $checkInterval;
    private $watcher;
    private $measure;
    private $check;

    /**
     * @param callable $onBlock Callback to be executed if one tick takes longer than $threshold milliseconds.
     * @param int $blockThreshold Tick duration threshold in milliseconds.
     * @param int $checkInterval Check interval, only check every $interval milliseconds one tick.
     */
    public function __construct(callable $onBlock, int $blockThreshold = 10, int $checkInterval = 500) {
        $this->onBlock = $onBlock;
        $this->blockThreshold = $blockThreshold;
        $this->checkInterval = $checkInterval;

        $this->measure = function ($watcherId, $time) {
            $timeDiff = microtime(true) - $time;
            $timeDiff *= 1000;

            if ($timeDiff > $this->blockThreshold && $this->watcher !== null) {
                $onBlock = $this->onBlock;
                $onBlock($timeDiff);
            }
        };

        $this->check = function () {
            $time = microtime(1);

            Loop::defer($this->measure, $time);
        };
    }

    /**
     * Start the detector.
     */
    public function start() {
        if ($this->watcher !== null) {
            return;
        }

        $this->watcher = Loop::repeat($this->checkInterval, function () {
            // Use double defer to calculate complete tick time
            // instead of timer → defer time.
            Loop::defer($this->check);
        });

        Loop::unreference($this->watcher);
    }

    /**
     * Stop the detector.
     */
    public function stop() {
        if ($this->watcher === null) {
            return;
        }

        Loop::cancel($this->watcher);
        $this->watcher = null;
    }
}

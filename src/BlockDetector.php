<?php

namespace Kelunik\LoopBlock;

use Interop\Async\Loop;

/**
 * Detects blocking operations in loops.
 */
class BlockDetector {
    private $callback;
    private $threshold;
    private $interval;
    private $watcher;
    private $measure;
    private $check;

    /**
     * @param callable $callback Callback to be executed if one tick takes longer than $threshold milliseconds.
     * @param int      $threshold Tick duration threshold in milliseconds.
     * @param int      $interval Check interval, only check every $interval milliseconds one tick.
     */
    public function __construct(callable $callback, int $threshold = 10, int $interval = 500) {
        $this->callback = $callback;
        $this->threshold = $threshold;
        $this->interval = $interval;

        $this->measure = function ($watcherId, $time) {
            $timeDiff = microtime(true) - $time;
            $timeDiff *= 1000;

            if ($timeDiff > $this->threshold) {
                $callback = $this->callback;
                $callback($timeDiff);
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

        $this->watcher = Loop::repeat($this->interval, function () {
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
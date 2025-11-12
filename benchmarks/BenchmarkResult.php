<?php

declare(strict_types=1);

namespace Phetl\Benchmarks;

/**
 * Result of a benchmark run
 */
class BenchmarkResult
{
    public function __construct(
        public readonly string $name,
        public readonly int $iterations,
        public readonly array $times,
        public readonly array $memories
    ) {
    }

    /**
     * Get average time in milliseconds
     */
    public function averageTime(): float
    {
        return array_sum($this->times) / count($this->times);
    }

    /**
     * Get median time in milliseconds
     */
    public function medianTime(): float
    {
        $sorted = $this->times;
        sort($sorted);
        $count = count($sorted);
        $middle = (int)floor($count / 2);

        if ($count % 2 === 0) {
            return ($sorted[$middle - 1] + $sorted[$middle]) / 2;
        }

        return $sorted[$middle];
    }

    /**
     * Get minimum time in milliseconds
     */
    public function minTime(): float
    {
        return min($this->times);
    }

    /**
     * Get maximum time in milliseconds
     */
    public function maxTime(): float
    {
        return max($this->times);
    }

    /**
     * Get standard deviation of times
     */
    public function stdDevTime(): float
    {
        $mean = $this->averageTime();
        $variance = array_sum(array_map(
            fn($time) => ($time - $mean) ** 2,
            $this->times
        )) / count($this->times);

        return sqrt($variance);
    }

    /**
     * Get average memory usage in bytes
     */
    public function averageMemory(): float
    {
        return array_sum($this->memories) / count($this->memories);
    }

    /**
     * Get peak memory usage in bytes
     */
    public function peakMemory(): int
    {
        return max($this->memories);
    }

    /**
     * Format memory in human-readable form
     */
    public function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return sprintf('%.2f %s', $value, $units[$index]);
    }

    /**
     * Get summary as array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'iterations' => $this->iterations,
            'time' => [
                'avg_ms' => round($this->averageTime(), 3),
                'median_ms' => round($this->medianTime(), 3),
                'min_ms' => round($this->minTime(), 3),
                'max_ms' => round($this->maxTime(), 3),
                'stddev_ms' => round($this->stdDevTime(), 3),
            ],
            'memory' => [
                'avg_bytes' => (int)$this->averageMemory(),
                'avg_formatted' => $this->formatMemory((int)$this->averageMemory()),
                'peak_bytes' => $this->peakMemory(),
                'peak_formatted' => $this->formatMemory($this->peakMemory()),
            ],
        ];
    }

    /**
     * Format result as human-readable string
     */
    public function toString(): string
    {
        $data = $this->toArray();
        $output = "\n";
        $output .= "Benchmark: {$data['name']}\n";
        $output .= str_repeat('=', 60) . "\n";
        $output .= "Iterations: {$data['iterations']}\n\n";
        $output .= "Time Statistics:\n";
        $output .= sprintf("  Average:  %8.3f ms\n", $data['time']['avg_ms']);
        $output .= sprintf("  Median:   %8.3f ms\n", $data['time']['median_ms']);
        $output .= sprintf("  Min:      %8.3f ms\n", $data['time']['min_ms']);
        $output .= sprintf("  Max:      %8.3f ms\n", $data['time']['max_ms']);
        $output .= sprintf("  StdDev:   %8.3f ms\n", $data['time']['stddev_ms']);
        $output .= "\nMemory Usage:\n";
        $output .= sprintf("  Average:  %s\n", $data['memory']['avg_formatted']);
        $output .= sprintf("  Peak:     %s\n", $data['memory']['peak_formatted']);
        $output .= str_repeat('=', 60) . "\n";

        return $output;
    }
}

<?php

declare(strict_types=1);

namespace Phetl\Benchmarks;

/**
 * Benchmark suite runner
 */
class BenchmarkRunner
{
    private array $benchmarks = [];
    private array $results = [];

    /**
     * Add a benchmark to the suite
     */
    public function add(Benchmark $benchmark): self
    {
        $this->benchmarks[] = $benchmark;
        return $this;
    }

    /**
     * Run all benchmarks
     */
    public function run(): void
    {
        $this->results = [];

        echo "\nRunning " . count($this->benchmarks) . " benchmarks...\n";
        echo str_repeat('=', 60) . "\n";

        foreach ($this->benchmarks as $index => $benchmark) {
            echo "\n[" . ($index + 1) . "/" . count($this->benchmarks) . "] ";
            echo "Running: " . $benchmark->getName() . "...";

            $result = $benchmark->run();
            $this->results[] = $result;

            echo " ✓ Done (" . round($result->averageTime(), 2) . " ms avg)\n";
        }

        echo "\n" . str_repeat('=', 60) . "\n";
        echo "All benchmarks completed!\n\n";
    }

    /**
     * Get all results
     *
     * @return BenchmarkResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Print detailed results
     */
    public function printResults(): void
    {
        foreach ($this->results as $result) {
            echo $result->toString();
        }
    }

    /**
     * Export results to JSON
     */
    public function exportJson(string $filePath): void
    {
        $data = [
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'benchmarks' => array_map(
                fn($result) => $result->toArray(),
                $this->results
            ),
        ];

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Export results to CSV
     */
    public function exportCsv(string $filePath): void
    {
        $handle = fopen($filePath, 'w');

        // Header
        fputcsv($handle, [
            'Name',
            'Iterations',
            'Avg Time (ms)',
            'Median Time (ms)',
            'Min Time (ms)',
            'Max Time (ms)',
            'StdDev Time (ms)',
            'Avg Memory (bytes)',
            'Peak Memory (bytes)',
        ]);

        // Data
        foreach ($this->results as $result) {
            $data = $result->toArray();
            fputcsv($handle, [
                $data['name'],
                $data['iterations'],
                $data['time']['avg_ms'],
                $data['time']['median_ms'],
                $data['time']['min_ms'],
                $data['time']['max_ms'],
                $data['time']['stddev_ms'],
                $data['memory']['avg_bytes'],
                $data['memory']['peak_bytes'],
            ]);
        }

        fclose($handle);
    }

    /**
     * Compare results and show relative performance
     */
    public function printComparison(): void
    {
        if (count($this->results) < 2) {
            echo "Need at least 2 benchmarks to compare.\n";
            return;
        }

        echo "\nPerformance Comparison (Time)\n";
        echo str_repeat('=', 60) . "\n";

        // Sort by average time
        $sorted = $this->results;
        usort($sorted, fn($a, $b) => $a->averageTime() <=> $b->averageTime());

        $baseline = $sorted[0]->averageTime();

        foreach ($sorted as $result) {
            $ratio = $result->averageTime() / $baseline;
            printf(
                "%-40s %8.3f ms  (%.2fx)\n",
                $result->name,
                $result->averageTime(),
                $ratio
            );
        }

        echo "\nPerformance Comparison (Memory)\n";
        echo str_repeat('=', 60) . "\n";

        // Sort by average memory
        $sorted = $this->results;
        usort($sorted, fn($a, $b) => $a->averageMemory() <=> $b->averageMemory());

        $baselineMem = $sorted[0]->averageMemory();

        if ($baselineMem == 0) {
            echo "Memory tracking not available (all measurements are 0)\n";
            echo "This is normal - PHP may not track memory changes for small operations\n";
        } else {
            foreach ($sorted as $result) {
                $ratio = $result->averageMemory() / $baselineMem;
                printf(
                    "%-40s %12s  (%.2fx)\n",
                    $result->name,
                    $result->formatMemory((int)$result->averageMemory()),
                    $ratio
                );
            }
        }

        echo "\n";
    }
}

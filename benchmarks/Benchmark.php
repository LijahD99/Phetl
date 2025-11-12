<?php

declare(strict_types=1);

namespace Phetl\Benchmarks;

/**
 * Base class for all benchmarks
 */
abstract class Benchmark
{
    protected int $iterations = 10;
    protected array $results = [];

    /**
     * Run the benchmark
     */
    public function run(): BenchmarkResult
    {
        // Warmup
        $this->warmup();

        // Run iterations
        $times = [];
        $memories = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            gc_collect_cycles();
            $memStart = memory_get_usage(true);
            $timeStart = microtime(true);

            $this->execute();

            $timeEnd = microtime(true);
            $memEnd = memory_get_usage(true);

            $times[] = ($timeEnd - $timeStart) * 1000; // Convert to ms
            $memories[] = $memEnd - $memStart;
        }

        return new BenchmarkResult(
            name: $this->getName(),
            iterations: $this->iterations,
            times: $times,
            memories: $memories
        );
    }

    /**
     * Get benchmark name
     */
    abstract public function getName(): string;

    /**
     * Execute the benchmark operation
     */
    abstract protected function execute(): void;

    /**
     * Warmup run (not measured)
     */
    protected function warmup(): void
    {
        $this->execute();
    }

    /**
     * Set number of iterations
     */
    public function setIterations(int $iterations): self
    {
        $this->iterations = $iterations;
        return $this;
    }
}

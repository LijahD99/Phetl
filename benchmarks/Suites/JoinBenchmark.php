<?php

declare(strict_types=1);

namespace Phetl\Benchmarks\Suites;

use Phetl\Benchmarks\Benchmark;
use Phetl\Table;

/**
 * Benchmark join operations
 */
class JoinBenchmark extends Benchmark
{
    public function __construct(
        private int $leftRows,
        private int $rightRows,
        private string $joinType = 'inner'
    ) {
    }

    public function getName(): string
    {
        return "Join ({$this->joinType}) - {$this->leftRows}x{$this->rightRows} rows";
    }

    protected function execute(): void
    {
        [$leftData, $rightData] = $this->generateData();
        $left = Table::fromArray($leftData);
        $right = Table::fromArray($rightData);

        switch ($this->joinType) {
            case 'inner':
                $left->innerJoin($right, 'user_id')->toArray();
                break;
            case 'left':
                $left->leftJoin($right, 'user_id')->toArray();
                break;
            case 'right':
                $left->rightJoin($right, 'user_id')->toArray();
                break;
        }
    }

    private function generateData(): array
    {
        // Left table (orders)
        $leftData = [['order_id', 'user_id', 'amount']];
        for ($i = 1; $i <= $this->leftRows; $i++) {
            $leftData[] = [$i, rand(1, $this->rightRows), rand(10, 1000)];
        }

        // Right table (users)
        $rightData = [['user_id', 'name', 'email']];
        for ($i = 1; $i <= $this->rightRows; $i++) {
            $rightData[] = [$i, "User $i", "user$i@example.com"];
        }

        return [$leftData, $rightData];
    }
}

<?php

require __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

// Test simple aggregation
$data = [
    ['category', 'sales'],
    ['A', 100],
    ['A', 200],
    ['B', 150],
];

$table = Table::fromArray($data);

$result = $table->aggregate(['category'], [
    'total' => fn($rows) => array_sum(array_column($rows, 'sales')),
    'count' => fn($rows) => count($rows),
    'test' => function($rows) {
        echo "Rows passed to aggregation: " . count($rows) . "\n";
        if (count($rows) > 0) {
            echo "First row: ";
            print_r($rows[0]);
            $sales = array_column($rows, 'sales');
            echo "Sales column: ";
            print_r($sales);
        }
        return count($rows);
    },
])->toArray();

print_r($result);
echo "Test passed!\n";

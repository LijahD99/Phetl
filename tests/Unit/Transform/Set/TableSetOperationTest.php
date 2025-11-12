<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Set;

use Phetl\Table;

test('Table concat combines multiple tables', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'name'],
        [3, 'Charlie'],
    ]);

    $table3 = Table::fromArray([
        ['id', 'name'],
        [4, 'Diana'],
    ]);

    $result = $table1->concat($table2, $table3)->toArray();

    expect($result)->toHaveCount(5) // header + 4 rows
        ->and($result[0])->toBe(['id', 'name'])
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[2])->toBe([2, 'Bob'])
        ->and($result[3])->toBe([3, 'Charlie'])
        ->and($result[4])->toBe([4, 'Diana']);
});

test('Table concat preserves duplicates', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'], // Duplicate
    ]);

    $result = $table1->concat($table2)->toArray();

    expect($result)->toHaveCount(3) // header + 2 rows
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[2])->toBe([1, 'Alice']);
});

test('Table union removes duplicates', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'], // Duplicate
        [3, 'Charlie'],
    ]);

    $result = $table1->union($table2)->toArray();

    expect($result)->toHaveCount(4) // header + 3 unique rows
        ->and($result[0])->toBe(['id', 'name'])
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[2])->toBe([2, 'Bob'])
        ->and($result[3])->toBe([3, 'Charlie']);
});

test('Table merge combines different headers', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'age'],
        [2, 30],
    ]);

    $result = $table1->merge($table2)->toArray();

    expect($result[0])->toBe(['id', 'name', 'age'])
        ->and($result[1])->toBe([1, 'Alice', null])
        ->and($result[2])->toBe([2, null, 30]);
});

test('set operations can be chained', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'name'],
        [2, 'Bob'],
    ]);

    $table3 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'], // Duplicate
    ]);

    $result = $table1
        ->concat($table2)
        ->union($table3)
        ->toArray();

    expect($result)->toHaveCount(3); // header + 2 unique rows
});

test('concat works with transformations', function (): void {
    $table1 = Table::fromArray([
        ['name', 'age'],
        ['alice', 30],
        ['bob', 25],
    ]);

    $table2 = Table::fromArray([
        ['name', 'age'],
        ['charlie', 35],
    ]);

    $result = $table1
        ->concat($table2)
        ->convert('name', 'strtoupper')
        ->whereGreaterThan('age', 26)
        ->toArray();

    expect($result)->toHaveCount(3) // header + 2 rows
        ->and($result[1][0])->toBe('ALICE')
        ->and($result[2][0])->toBe('CHARLIE');
});

test('merge works with filtering', function (): void {
    $table1 = Table::fromArray([
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ]);

    $table2 = Table::fromArray([
        ['id', 'city'],
        [1, 'NYC'],
        [3, 'LA'],
    ]);

    $result = $table1
        ->merge($table2)
        ->whereNotNull('name')
        ->toArray();

    expect($result)->toHaveCount(3) // header + 2 rows with non-null name
        ->and($result[1])->toBe([1, 'Alice', null])
        ->and($result[2])->toBe([2, 'Bob', null]);
});

test('concat can combine results from different sources', function (): void {
    $array1 = Table::fromArray([
        ['id', 'type'],
        [1, 'array'],
    ]);

    $array2 = Table::fromArray([
        ['id', 'type'],
        [2, 'array'],
    ]);

    $result = $array1->concat($array2)->toArray();

    expect($result)->toHaveCount(3)
        ->and($result[1])->toBe([1, 'array'])
        ->and($result[2])->toBe([2, 'array']);
});

test('union handles large duplicate sets', function (): void {
    $table1 = Table::fromArray([
        ['id'],
        [1],
        [2],
        [3],
    ]);

    $table2 = Table::fromArray([
        ['id'],
        [2],
        [3],
        [4],
    ]);

    $result = $table1->union($table2)->toArray();

    expect($result)->toHaveCount(5); // header + 4 unique rows
});

test('merge preserves original table order', function (): void {
    $table1 = Table::fromArray([
        ['a', 'b'],
        [1, 2],
        [3, 4],
    ]);

    $table2 = Table::fromArray([
        ['c'],
        [5],
    ]);

    $result = $table1->merge($table2)->toArray();

    expect($result[1])->toBe([1, 2, null])
        ->and($result[2])->toBe([3, 4, null])
        ->and($result[3])->toBe([null, null, 5]);
});

test('concat with sorted tables', function (): void {
    $table1 = Table::fromArray([
        ['name', 'score'],
        ['Charlie', 85],
        ['Alice', 90],
    ]);

    $table2 = Table::fromArray([
        ['name', 'score'],
        ['Bob', 88],
    ]);

    $result = $table1
        ->concat($table2)
        ->sortBy('score')
        ->toArray();

    expect($result[1][0])->toBe('Charlie') // 85
        ->and($result[2][0])->toBe('Bob')     // 88
        ->and($result[3][0])->toBe('Alice');  // 90
});

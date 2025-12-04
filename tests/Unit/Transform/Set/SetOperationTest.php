<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Set;

use InvalidArgumentException;
use Phetl\Transform\Set\SetOperation;

function splitTable(array $table): array
{
    return [$table[0], array_slice($table, 1)];
}

beforeEach(function (): void {
    $this->table1 = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $this->table2 = [
        ['id', 'name'],
        [3, 'Charlie'],
        [4, 'Diana'],
    ];

    $this->table3 = [
        ['id', 'name'],
        [1, 'Alice'], // Duplicate from table1
        [5, 'Eve'],
    ];
});

// ====================
// Concat Tests
// ====================

test('concat combines tables vertically', function (): void {
    [$h1, $d1] = splitTable($this->table1);
    [$h2, $d2] = splitTable($this->table2);

    [$headers, $data] = SetOperation::concat([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name']);
    expect($data)->toHaveCount(4)
        ->and($data[0])->toBe([1, 'Alice'])
        ->and($data[1])->toBe([2, 'Bob'])
        ->and($data[2])->toBe([3, 'Charlie'])
        ->and($data[3])->toBe([4, 'Diana']);
});

test('concat preserves duplicates', function (): void {
    [$h1, $d1] = splitTable($this->table1);
    [$h3, $d3] = splitTable($this->table3);

    [$headers, $data] = SetOperation::concat([$h1, $d1], [$h3, $d3]);

    expect($data)->toHaveCount(4)
        ->and($data[0])->toBe([1, 'Alice'])
        ->and($data[2])->toBe([1, 'Alice']); // Duplicate preserved
});

test('concat throws exception for mismatched headers', function (): void {
    $differentHeader = [
        ['id', 'age'], // Different header
        [1, 30],
    ];

    [$h1, $d1] = splitTable($this->table1);
    [$hd, $dd] = splitTable($differentHeader);

    SetOperation::concat([$h1, $d1], [$hd, $dd]);
})->throws(InvalidArgumentException::class, "different header structure");

test('concat handles single table', function (): void {
    [$h1, $d1] = splitTable($this->table1);

    [$headers, $data] = SetOperation::concat([$h1, $d1]);

    expect($headers)->toBe($h1);
    expect($data)->toBe($d1);
});

test('concat handles empty tables', function (): void {
    $empty1 = [['id', 'name']];
    $empty2 = [['id', 'name']];

    [$h1, $d1] = splitTable($empty1);
    [$h2, $d2] = splitTable($empty2);

    [$headers, $data] = SetOperation::concat([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name']);
    expect($data)->toHaveCount(0);
});

test('concat handles multiple tables', function (): void {
    $table3 = [
        ['id', 'name'],
        [5, 'Eve'],
    ];

    [$h1, $d1] = splitTable($this->table1);
    [$h2, $d2] = splitTable($this->table2);
    [$h3, $d3] = splitTable($table3);

    [$headers, $data] = SetOperation::concat([$h1, $d1], [$h2, $d2], [$h3, $d3]);

    expect($data)->toHaveCount(5); // 5 rows
});

test('concat with no tables returns empty', function (): void {
    [$headers, $data] = SetOperation::concat();

    expect($headers)->toBeEmpty();
    expect($data)->toBeEmpty();
});

// ====================
// Union Tests
// ====================

test('union removes duplicates', function (): void {
    [$h1, $d1] = splitTable($this->table1);
    [$h3, $d3] = splitTable($this->table3);

    [$headers, $data] = SetOperation::union([$h1, $d1], [$h3, $d3]);

    expect($headers)->toBe(['id', 'name']);
    expect($data)->toHaveCount(3)
        ->and($data[0])->toBe([1, 'Alice'])
        ->and($data[1])->toBe([2, 'Bob'])
        ->and($data[2])->toBe([5, 'Eve']);
});

test('union with no duplicates behaves like concat', function (): void {
    [$h1, $d1] = splitTable($this->table1);
    [$h2, $d2] = splitTable($this->table2);

    [$headers, $data] = SetOperation::union([$h1, $d1], [$h2, $d2]);

    expect($data)->toHaveCount(4); // 4 unique rows
});

test('union handles complete duplicates', function (): void {
    $duplicate = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    [$h1, $d1] = splitTable($this->table1);
    [$hd, $dd] = splitTable($duplicate);

    [$headers, $data] = SetOperation::union([$h1, $d1], [$hd, $dd]);

    expect($data)->toHaveCount(2); // 2 unique rows
});

test('union handles single table', function (): void {
    [$h1, $d1] = splitTable($this->table1);

    [$headers, $data] = SetOperation::union([$h1, $d1]);

    expect($headers)->toBe($h1);
    expect($data)->toBe($d1);
});

test('union throws exception for mismatched headers', function (): void {
    $differentHeader = [
        ['id', 'age'],
        [1, 30],
    ];

    [$h1, $d1] = splitTable($this->table1);
    [$hd, $dd] = splitTable($differentHeader);

    SetOperation::union([$h1, $d1], [$hd, $dd]);
})->throws(InvalidArgumentException::class, "different header structure");

// ====================
// Merge Tests
// ====================

test('merge combines tables with different headers', function (): void {
    $table1 = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $table2 = [
        ['id', 'age'],
        [1, 30],
        [3, 35],
    ];

    [$h1, $d1] = splitTable($table1);
    [$h2, $d2] = splitTable($table2);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name', 'age'])
        ->and($data[0])->toBe([1, 'Alice', null])
        ->and($data[1])->toBe([2, 'Bob', null])
        ->and($data[2])->toBe([1, null, 30])
        ->and($data[3])->toBe([3, null, 35]);
});

test('merge fills missing values with null', function (): void {
    $table1 = [
        ['a', 'b'],
        [1, 2],
    ];

    $table2 = [
        ['b', 'c'],
        [3, 4],
    ];

    [$h1, $d1] = splitTable($table1);
    [$h2, $d2] = splitTable($table2);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['a', 'b', 'c'])
        ->and($data[0])->toBe([1, 2, null])
        ->and($data[1])->toBe([null, 3, 4]);
});

test('merge handles overlapping columns', function (): void {
    $table1 = [
        ['id', 'name', 'age'],
        [1, 'Alice', 30],
    ];

    $table2 = [
        ['id', 'city'],
        [2, 'NYC'],
    ];

    [$h1, $d1] = splitTable($table1);
    [$h2, $d2] = splitTable($table2);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name', 'age', 'city'])
        ->and($data[0])->toBe([1, 'Alice', 30, null])
        ->and($data[1])->toBe([2, null, null, 'NYC']);
});

test('merge with identical headers behaves like concat', function (): void {
    [$h1, $d1] = splitTable($this->table1);
    [$h2, $d2] = splitTable($this->table2);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name'])
        ->and($data)->toHaveCount(4); // 4 rows
});

test('merge handles single table', function (): void {
    [$h1, $d1] = splitTable($this->table1);

    [$headers, $data] = SetOperation::merge([$h1, $d1]);

    expect($headers)->toBe($h1);
    expect($data)->toBe($d1);
});

test('merge handles empty tables', function (): void {
    $empty1 = [['id', 'name']];
    $empty2 = [['id', 'age']];

    [$h1, $d1] = splitTable($empty1);
    [$h2, $d2] = splitTable($empty2);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2]);

    expect($headers)->toBe(['id', 'name', 'age']);
    expect($data)->toHaveCount(0);
});

test('merge with no tables returns empty', function (): void {
    [$headers, $data] = SetOperation::merge();

    expect($headers)->toBeEmpty();
    expect($data)->toBeEmpty();
});

test('merge handles multiple tables with various columns', function (): void {
    $table1 = [
        ['a'],
        [1],
    ];

    $table2 = [
        ['b'],
        [2],
    ];

    $table3 = [
        ['c'],
        [3],
    ];

    [$h1, $d1] = splitTable($table1);
    [$h2, $d2] = splitTable($table2);
    [$h3, $d3] = splitTable($table3);

    [$headers, $data] = SetOperation::merge([$h1, $d1], [$h2, $d2], [$h3, $d3]);

    expect($headers)->toBe(['a', 'b', 'c'])
        ->and($data[0])->toBe([1, null, null])
        ->and($data[1])->toBe([null, 2, null])
        ->and($data[2])->toBe([null, null, 3]);
});

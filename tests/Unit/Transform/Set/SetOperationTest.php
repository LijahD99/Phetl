<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Set;

use InvalidArgumentException;
use Phetl\Transform\Set\SetOperation;

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
    $result = iterator_to_array(SetOperation::concat($this->table1, $this->table2));

    expect($result)->toHaveCount(5) // header + 4 rows
        ->and($result[0])->toBe(['id', 'name'])
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[2])->toBe([2, 'Bob'])
        ->and($result[3])->toBe([3, 'Charlie'])
        ->and($result[4])->toBe([4, 'Diana']);
});

test('concat preserves duplicates', function (): void {
    $result = iterator_to_array(SetOperation::concat($this->table1, $this->table3));

    expect($result)->toHaveCount(5) // header + 4 rows (including duplicate)
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[3])->toBe([1, 'Alice']); // Duplicate preserved
});

test('concat throws exception for mismatched headers', function (): void {
    $differentHeader = [
        ['id', 'age'], // Different header
        [1, 30],
    ];

    iterator_to_array(SetOperation::concat($this->table1, $differentHeader));
})->throws(InvalidArgumentException::class, "different header structure");

test('concat handles single table', function (): void {
    $result = iterator_to_array(SetOperation::concat($this->table1));

    expect($result)->toBe($this->table1);
});

test('concat handles empty tables', function (): void {
    $empty1 = [['id', 'name']];
    $empty2 = [['id', 'name']];

    $result = iterator_to_array(SetOperation::concat($empty1, $empty2));

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(['id', 'name']);
});

test('concat handles multiple tables', function (): void {
    $table3 = [
        ['id', 'name'],
        [5, 'Eve'],
    ];

    $result = iterator_to_array(SetOperation::concat($this->table1, $this->table2, $table3));

    expect($result)->toHaveCount(6); // header + 5 rows
});

test('concat with no tables returns empty', function (): void {
    $result = iterator_to_array(SetOperation::concat());

    expect($result)->toBeEmpty();
});

// ====================
// Union Tests
// ====================

test('union removes duplicates', function (): void {
    $result = iterator_to_array(SetOperation::union($this->table1, $this->table3));

    expect($result)->toHaveCount(4) // header + 3 unique rows
        ->and($result[0])->toBe(['id', 'name'])
        ->and($result[1])->toBe([1, 'Alice'])
        ->and($result[2])->toBe([2, 'Bob'])
        ->and($result[3])->toBe([5, 'Eve']);
});

test('union with no duplicates behaves like concat', function (): void {
    $result = iterator_to_array(SetOperation::union($this->table1, $this->table2));

    expect($result)->toHaveCount(5); // header + 4 unique rows
});

test('union handles complete duplicates', function (): void {
    $duplicate = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $result = iterator_to_array(SetOperation::union($this->table1, $duplicate));

    expect($result)->toHaveCount(3); // header + 2 unique rows
});

test('union handles single table', function (): void {
    $result = iterator_to_array(SetOperation::union($this->table1));

    expect($result)->toBe($this->table1);
});

test('union throws exception for mismatched headers', function (): void {
    $differentHeader = [
        ['id', 'age'],
        [1, 30],
    ];

    iterator_to_array(SetOperation::union($this->table1, $differentHeader));
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

    $result = iterator_to_array(SetOperation::merge($table1, $table2));

    expect($result[0])->toBe(['id', 'name', 'age'])
        ->and($result[1])->toBe([1, 'Alice', null])
        ->and($result[2])->toBe([2, 'Bob', null])
        ->and($result[3])->toBe([1, null, 30])
        ->and($result[4])->toBe([3, null, 35]);
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

    $result = iterator_to_array(SetOperation::merge($table1, $table2));

    expect($result[0])->toBe(['a', 'b', 'c'])
        ->and($result[1])->toBe([1, 2, null])
        ->and($result[2])->toBe([null, 3, 4]);
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

    $result = iterator_to_array(SetOperation::merge($table1, $table2));

    expect($result[0])->toBe(['id', 'name', 'age', 'city'])
        ->and($result[1])->toBe([1, 'Alice', 30, null])
        ->and($result[2])->toBe([2, null, null, 'NYC']);
});

test('merge with identical headers behaves like concat', function (): void {
    $result = iterator_to_array(SetOperation::merge($this->table1, $this->table2));

    expect($result[0])->toBe(['id', 'name'])
        ->and($result)->toHaveCount(5); // header + 4 rows
});

test('merge handles single table', function (): void {
    $result = iterator_to_array(SetOperation::merge($this->table1));

    expect($result)->toBe($this->table1);
});

test('merge handles empty tables', function (): void {
    $empty1 = [['id', 'name']];
    $empty2 = [['id', 'age']];

    $result = iterator_to_array(SetOperation::merge($empty1, $empty2));

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(['id', 'name', 'age']);
});

test('merge with no tables returns empty', function (): void {
    $result = iterator_to_array(SetOperation::merge());

    expect($result)->toBeEmpty();
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

    $result = iterator_to_array(SetOperation::merge($table1, $table2, $table3));

    expect($result[0])->toBe(['a', 'b', 'c'])
        ->and($result[1])->toBe([1, null, null])
        ->and($result[2])->toBe([null, 2, null])
        ->and($result[3])->toBe([null, null, 3]);
});

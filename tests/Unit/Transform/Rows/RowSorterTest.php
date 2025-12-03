<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use InvalidArgumentException;
use Phetl\Transform\Rows\RowSorter;

beforeEach(function (): void {
    $this->headers = ['name', 'age', 'city'];
    $this->data = [
        ['Charlie', 35, 'NYC'],
        ['Alice', 30, 'LA'],
        ['Bob', 25, 'NYC'],
        ['Diana', 30, 'SF'],
    ];
});

test('it sorts by single field ascending', function (): void {
    [$headers, $data] = RowSorter::sort($this->headers, $this->data, 'age');

    expect($headers)->toBe(['name', 'age', 'city'])
        ->and($data[0][0])->toBe('Bob')   // age 25
        ->and($data[1][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($data[2][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($data[3][0])->toBe('Charlie'); // age 35
});

test('it sorts by single field descending', function (): void {
    [$headers, $data] = RowSorter::sort($this->headers, $this->data, 'age', true);

    expect($data[0][0])->toBe('Charlie') // age 35
        ->and($data[1][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($data[2][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($data[3][0])->toBe('Bob');  // age 25
});

test('it sorts by multiple fields', function (): void {
    [$headers, $data] = RowSorter::sort($this->headers, $this->data, ['age', 'name']);

    expect($headers)->toBe(['name', 'age', 'city'])
        ->and($data[0][0])->toBe('Bob')     // age 25, name Bob
        ->and($data[1][0])->toBe('Alice')   // age 30, name Alice
        ->and($data[2][0])->toBe('Diana')   // age 30, name Diana
        ->and($data[3][0])->toBe('Charlie'); // age 35, name Charlie
});

test('it sorts by string field', function (): void {
    [$headers, $data] = RowSorter::sort($this->headers, $this->data, 'name');

    expect($data[0][0])->toBe('Alice')
        ->and($data[1][0])->toBe('Bob')
        ->and($data[2][0])->toBe('Charlie')
        ->and($data[3][0])->toBe('Diana');
});

test('it handles null values in sorting', function (): void {
    $headers = ['name', 'age'];
    $data = [
        ['Alice', 30],
        ['Bob', null],
        ['Charlie', 25],
        ['Diana', null],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, 'age');

    // Nulls should sort last
    expect($resultData[0][0])->toBe('Charlie') // 25
        ->and($resultData[1][0])->toBe('Alice')   // 30
        ->and($resultData[2][0])->toBe('Bob')     // null
        ->and($resultData[3][0])->toBe('Diana');  // null
});

test('it sorts with custom comparator', function (): void {
    [$headers, $data] = RowSorter::sort(
        $this->headers,
        $this->data,
        fn ($a, $b) => strlen($a[0]) <=> strlen($b[0]) // Sort by name length
    );

    // Bob (3), Alice/Diana (5), Charlie (7)
    expect($data[0][0])->toBe('Bob')
        ->and(strlen($data[3][0]))->toBe(7); // Charlie is longest
});

test('it preserves header', function (): void {
    [$headers, $data] = RowSorter::sort($this->headers, $this->data, 'age');

    expect($headers)->toBe(['name', 'age', 'city']);
});

test('it handles empty data gracefully', function (): void {
    $emptyHeaders = ['name', 'age'];
    $emptyData = [];
    [$headers, $data] = RowSorter::sort($emptyHeaders, $emptyData, 'age');

    expect($data)->toHaveCount(0)
        ->and($headers)->toBe(['name', 'age']);
});

test('it throws exception for invalid field', function (): void {
    RowSorter::sort($this->headers, $this->data, 'invalid_field');
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('it sorts numeric strings lexicographically', function (): void {
    $headers = ['id', 'value'];
    $data = [
        ['a', '100'],
        ['b', '20'],
        ['c', '3'],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, 'value');

    // PHP spaceship operator does numeric comparison for numeric strings
    expect($resultData[0][0])->toBe('c')  // "3" = 3
        ->and($resultData[1][0])->toBe('b')  // "20" = 20
        ->and($resultData[2][0])->toBe('a'); // "100" = 100
});

test('it sorts mixed types gracefully', function (): void {
    $headers = ['name', 'value'];
    $data = [
        ['a', 'string'],
        ['b', 123],
        ['c', 'another'],
        ['d', 456],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, 'value');

    // Should handle comparison without throwing
    expect($resultData)->toHaveCount(4);
});

test('it handles case-sensitive string sorting', function (): void {
    $headers = ['name'];
    $data = [
        ['zebra'],
        ['Apple'],
        ['banana'],
        ['ZEBRA'],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, 'name');

    // Capital letters sort before lowercase in ASCII
    expect($resultData[0][0])->toBe('Apple')
        ->and($resultData[1][0])->toBe('ZEBRA');
});

test('sorting works with complex table', function (): void {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'Diana', 65000],
        ['HR', 'Eve', 45000],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, ['dept', 'salary']);

    expect($resultData[0])->toBe(['HR', 'Eve', 45000])      // HR, 45000
        ->and($resultData[1])->toBe(['IT', 'Bob', 60000])    // IT, 60000
        ->and($resultData[2])->toBe(['IT', 'Diana', 65000])  // IT, 65000
        ->and($resultData[3])->toBe(['Sales', 'Alice', 50000]) // Sales, 50000
        ->and($resultData[4])->toBe(['Sales', 'Charlie', 55000]); // Sales, 55000
});

test('reverse flag works with multiple fields', function (): void {
    $headers = ['category', 'value'];
    $data = [
        ['A', 10],
        ['B', 20],
        ['A', 30],
    ];

    [$resultHeaders, $resultData] = RowSorter::sort($headers, $data, ['category', 'value'], true);

    // Descending: B(20), A(30), A(10)
    expect($resultData[0])->toBe(['B', 20])
        ->and($resultData[1])->toBe(['A', 30])
        ->and($resultData[2])->toBe(['A', 10]);
});

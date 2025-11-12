<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Values;

use InvalidArgumentException;
use Phetl\Transform\Values\ValueConverter;
use Phetl\Transform\Values\ValueReplacer;

beforeEach(function (): void {
    $this->data = [
        ['name', 'age', 'email'],
        ['Alice', 30, 'alice@example.com'],
        ['Bob', 25, 'bob@example.com'],
        ['Charlie', 35, 'CHARLIE@EXAMPLE.COM'],
    ];
});

// ====================
// Value Conversion Tests
// ====================

test('convert applies function to field values', function (): void {
    $result = iterator_to_array(ValueConverter::convert($this->data, 'name', 'strtoupper'));

    expect($result[0])->toBe(['name', 'age', 'email'])
        ->and($result[1])->toBe(['ALICE', 30, 'alice@example.com'])
        ->and($result[2])->toBe(['BOB', 25, 'bob@example.com'])
        ->and($result[3])->toBe(['CHARLIE', 35, 'CHARLIE@EXAMPLE.COM']);
});

test('convert applies closure to field values', function (): void {
    $result = iterator_to_array(ValueConverter::convert(
        $this->data,
        'age',
        fn ($age) => $age * 2
    ));

    expect($result[1][1])->toBe(60)
        ->and($result[2][1])->toBe(50)
        ->and($result[3][1])->toBe(70);
});

test('convert works with string function names', function (): void {
    $result = iterator_to_array(ValueConverter::convert($this->data, 'email', 'strtolower'));

    expect($result[1][2])->toBe('alice@example.com')
        ->and($result[2][2])->toBe('bob@example.com')
        ->and($result[3][2])->toBe('charlie@example.com');
});

test('convert throws exception for invalid field', function (): void {
    iterator_to_array(ValueConverter::convert($this->data, 'invalid_field', 'strtoupper'));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('convert handles empty data gracefully', function (): void {
    $emptyData = [['name', 'age']];
    $result = iterator_to_array(ValueConverter::convert($emptyData, 'name', 'strtoupper'));

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(['name', 'age']);
});

test('convertMultiple applies multiple conversions', function (): void {
    $result = iterator_to_array(ValueConverter::convertMultiple($this->data, [
        'name' => 'strtoupper',
        'age' => fn ($age) => $age + 10,
    ]));

    expect($result[1])->toBe(['ALICE', 40, 'alice@example.com'])
        ->and($result[2])->toBe(['BOB', 35, 'bob@example.com'])
        ->and($result[3])->toBe(['CHARLIE', 45, 'CHARLIE@EXAMPLE.COM']);
});

test('convertMultiple ignores non-existent fields', function (): void {
    $result = iterator_to_array(ValueConverter::convertMultiple($this->data, [
        'name' => 'strtoupper',
        'invalid_field' => 'strtolower',
    ]));

    expect($result[1][0])->toBe('ALICE');
});

// ====================
// Value Replacement Tests
// ====================

test('replace replaces specific value in field', function (): void {
    $result = iterator_to_array(ValueReplacer::replace($this->data, 'age', 30, 999));

    expect($result[1][1])->toBe(999)
        ->and($result[2][1])->toBe(25)
        ->and($result[3][1])->toBe(35);
});

test('replace only affects exact matches', function (): void {
    $result = iterator_to_array(ValueReplacer::replace($this->data, 'name', 'Bob', 'Robert'));

    expect($result[1][0])->toBe('Alice')
        ->and($result[2][0])->toBe('Robert')
        ->and($result[3][0])->toBe('Charlie');
});

test('replace throws exception for invalid field', function (): void {
    iterator_to_array(ValueReplacer::replace($this->data, 'invalid_field', 30, 999));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('replaceMap replaces multiple values using mapping', function (): void {
    $result = iterator_to_array(ValueReplacer::replaceMap($this->data, 'age', [
        30 => 31,
        25 => 26,
    ]));

    expect($result[1][1])->toBe(31)
        ->and($result[2][1])->toBe(26)
        ->and($result[3][1])->toBe(35); // Unchanged
});

test('replaceMap ignores unmapped values', function (): void {
    $result = iterator_to_array(ValueReplacer::replaceMap($this->data, 'name', [
        'Alice' => 'Alicia',
        'Unknown' => 'N/A',
    ]));

    expect($result[1][0])->toBe('Alicia')
        ->and($result[2][0])->toBe('Bob') // Unchanged
        ->and($result[3][0])->toBe('Charlie'); // Unchanged
});

test('replaceMap throws exception for invalid field', function (): void {
    iterator_to_array(ValueReplacer::replaceMap($this->data, 'invalid_field', [30 => 31]));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('replaceAll replaces value across all fields', function (): void {
    $data = [
        ['name', 'status', 'category'],
        ['Alice', 'active', 'VIP'],
        ['Bob', 'inactive', 'active'],
        ['Charlie', 'active', 'regular'],
    ];

    $result = iterator_to_array(ValueReplacer::replaceAll($data, 'active', 'ACTIVE'));

    expect($result[1])->toBe(['Alice', 'ACTIVE', 'VIP'])
        ->and($result[2])->toBe(['Bob', 'inactive', 'ACTIVE'])
        ->and($result[3])->toBe(['Charlie', 'ACTIVE', 'regular']);
});

test('replaceAll preserves header', function (): void {
    $result = iterator_to_array(ValueReplacer::replaceAll($this->data, 'name', 'REPLACED'));

    expect($result[0])->toBe(['name', 'age', 'email']);
});

test('replaceAll handles no matches', function (): void {
    $result = iterator_to_array(ValueReplacer::replaceAll($this->data, 'nonexistent', 'replaced'));

    expect($result)->toEqual($this->data);
});

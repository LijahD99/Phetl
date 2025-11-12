<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Values;

use Phetl\Table;

test('Table convert method converts field values', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['Alice', 30],
        ['Bob', 25],
    ]);

    $result = $table->convert('name', 'strtoupper')->toArray();

    expect($result[1][0])->toBe('ALICE')
        ->and($result[2][0])->toBe('BOB');
});

test('Table convertMultiple converts multiple fields', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['alice', 30],
        ['bob', 25],
    ]);

    $result = $table->convertMultiple([
        'name' => 'strtoupper',
        'age' => fn ($age) => $age + 5,
    ])->toArray();

    expect($result[1])->toBe(['ALICE', 35])
        ->and($result[2])->toBe(['BOB', 30]);
});

test('Table replace method replaces specific value', function (): void {
    $table = Table::fromArray([
        ['status', 'count'],
        ['active', 10],
        ['inactive', 5],
        ['active', 15],
    ]);

    $result = $table->replace('status', 'active', 'ACTIVE')->toArray();

    expect($result[1][0])->toBe('ACTIVE')
        ->and($result[2][0])->toBe('inactive')
        ->and($result[3][0])->toBe('ACTIVE');
});

test('Table replaceMap method replaces using mapping', function (): void {
    $table = Table::fromArray([
        ['status'],
        ['active'],
        ['inactive'],
        ['pending'],
    ]);

    $result = $table->replaceMap('status', [
        'active' => 'A',
        'inactive' => 'I',
        'pending' => 'P',
    ])->toArray();

    expect($result[1][0])->toBe('A')
        ->and($result[2][0])->toBe('I')
        ->and($result[3][0])->toBe('P');
});

test('Table replaceAll method replaces across all fields', function (): void {
    $table = Table::fromArray([
        ['col1', 'col2', 'col3'],
        ['NA', 'value', 'NA'],
        ['data', 'NA', 'test'],
    ]);

    $result = $table->replaceAll('NA', null)->toArray();

    expect($result[1])->toBe([null, 'value', null])
        ->and($result[2])->toBe(['data', null, 'test']);
});

test('value transformations can be chained', function (): void {
    $table = Table::fromArray([
        ['name', 'age', 'status'],
        ['alice', 30, 'active'],
        ['bob', 25, 'inactive'],
    ]);

    $result = $table
        ->convert('name', 'strtoupper')
        ->replace('status', 'active', 'ACTIVE')
        ->convertMultiple(['age' => fn ($age) => $age + 10])
        ->toArray();

    expect($result[1])->toBe(['ALICE', 40, 'ACTIVE'])
        ->and($result[2])->toBe(['BOB', 35, 'inactive']);
});

test('value transformations work with other transformations', function (): void {
    $table = Table::fromArray([
        ['name', 'age', 'city'],
        ['Alice', 30, 'NYC'],
        ['Bob', 25, 'LA'],
        ['Charlie', 35, 'SF'],
        ['David', 20, 'NYC'],
    ]);

    $result = $table
        ->whereGreaterThan('age', 22)
        ->convert('city', 'strtolower')
        ->selectColumns('name', 'city')
        ->toArray();

    expect($result)->toHaveCount(4) // header + 3 rows
        ->and($result[0])->toBe(['name', 'city'])
        ->and($result[1])->toBe(['Alice', 'nyc'])
        ->and($result[2])->toBe(['Bob', 'la'])
        ->and($result[3])->toBe(['Charlie', 'sf']);
});

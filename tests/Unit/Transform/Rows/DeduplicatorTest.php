<?php

declare(strict_types=1);

use Phetl\Table;
use Phetl\Transform\Rows\Deduplicator;

describe('Deduplicator', function () {
    describe('distinct()', function () {
        it('removes duplicate rows', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'NYC'],  // Duplicate
                ['Charlie', 35, 'SF'],
                ['Bob', 25, 'LA'],     // Duplicate
            ];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('removes duplicates based on specific field', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 35, 'SF'],    // Same name, different age/city
                ['Charlie', 35, 'SF'],
            ];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('removes duplicates based on multiple fields', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'SF'],    // Same name/age, different city
                ['Alice', 30, 'NYC'],   // Exact duplicate
                ['Charlie', 35, 'SF'],
            ];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data, ['name', 'age']);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice/30
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('handles empty data', function () {
            $headers = ['name', 'age'];
            $data = [];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
            ]);
        });

        it('handles all unique rows', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe(array_merge([['name', 'age']], $data));
        });

        it('handles null values', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', null],
                ['Bob', 25],
                ['Alice', null],  // Duplicate with null
            ];

            [$resHeaders, $resData] = Deduplicator::distinct($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
                ['Alice', null],
                ['Bob', 25],
            ]);
        });

        it('throws exception for invalid field', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
            ];

            expect(fn () => Deduplicator::distinct($headers, $data, 'invalid'))
                ->toThrow(InvalidArgumentException::class, "Field 'invalid' not found in header");
        });
    });

    describe('unique()', function () {
        it('is an alias for distinct()', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
            ];

            [$resHeaders, $resData] = Deduplicator::unique($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ]);
        });

        it('works with field parameter', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Alice', 35],
            ];

            [$resHeaders, $resData] = Deduplicator::unique($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
                ['Alice', 30],
            ]);
        });
    });

    describe('duplicates()', function () {
        it('returns only duplicate rows', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],  // Duplicate
                ['Charlie', 35],
                ['Bob', 25],    // Duplicate
            ];

            [$resHeaders, $resData] = Deduplicator::duplicates($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ]);
        });

        it('returns duplicates based on specific field', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 35, 'SF'],    // Duplicate by name
                ['Charlie', 35, 'SF'],  // Unique
            ];

            [$resHeaders, $resData] = Deduplicator::duplicates($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice (duplicate)
            ]);
        });

        it('returns duplicates based on multiple fields', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'SF'],    // Duplicate by name/age
                ['Charlie', 35, 'SF'],  // Unique
            ];

            [$resHeaders, $resData] = Deduplicator::duplicates($headers, $data, ['name', 'age']);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice/30 (duplicate)
            ]);
        });

        it('returns empty when no duplicates', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            [$resHeaders, $resData] = Deduplicator::duplicates($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age'],
            ]);
        });
    });

    describe('countDistinct()', function () {
        it('counts occurrences of each unique row', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
                ['Alice', 30],
                ['Bob', 25],
            ];

            [$resHeaders, $resData] = Deduplicator::countDistinct($headers, $data);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'count'],
                ['Alice', 30, 3],
                ['Bob', 25, 2],
            ]);
        });

        it('counts based on specific field', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Alice', 35],
                ['Bob', 25],
                ['Alice', 40],
            ];

            [$resHeaders, $resData] = Deduplicator::countDistinct($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'count'],
                ['Alice', 30, 3],  // First Alice, count = 3
                ['Bob', 25, 1],
            ]);
        });

        it('supports custom count field name', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Alice', 30],
            ];

            [$resHeaders, $resData] = Deduplicator::countDistinct($headers, $data, null, 'frequency');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'frequency'],
                ['Alice', 30, 2],
            ]);
        });

        it('counts based on multiple fields', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Alice', 30, 'LA'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'SF'],
            ];

            [$resHeaders, $resData] = Deduplicator::countDistinct($headers, $data, ['name', 'age']);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'age', 'city', 'count'],
                ['Alice', 30, 'NYC', 3],  // First Alice/30, count = 3
                ['Bob', 25, 'SF', 1],
            ]);
        });
    });

    describe('isUnique()', function () {
        it('returns true when all rows are unique', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            $result = Deduplicator::isUnique($headers, $data);

            expect($result)->toBeTrue();
        });

        it('returns false when duplicates exist', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
            ];

            $result = Deduplicator::isUnique($headers, $data);

            expect($result)->toBeFalse();
        });

        it('checks uniqueness based on specific field', function () {
            $headers = ['name', 'age'];
            $data = [
                ['Alice', 30],
                ['Alice', 35],  // Same name, different age
                ['Bob', 25],
            ];

            expect(Deduplicator::isUnique($headers, $data))->toBeTrue();
            expect(Deduplicator::isUnique($headers, $data, 'name'))->toBeFalse();
        });

        it('checks uniqueness based on multiple fields', function () {
            $headers = ['name', 'age', 'city'];
            $data = [
                ['Alice', 30, 'NYC'],
                ['Alice', 30, 'LA'],  // Same name/age, different city
                ['Bob', 25, 'SF'],
            ];

            expect(Deduplicator::isUnique($headers, $data))->toBeTrue();
            expect(Deduplicator::isUnique($headers, $data, ['name', 'age']))->toBeFalse();
        });

        it('returns true for empty data', function () {
            $headers = ['name', 'age'];
            $data = [];

            $result = Deduplicator::isUnique($headers, $data);

            expect($result)->toBeTrue();
        });
    });
});

describe('Table deduplication methods', function () {
    it('distinct() removes duplicate rows', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Alice', 30],
        ]);

        $result = $table->distinct()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ]);
    });

    it('distinct() works with field parameter', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 35],
        ]);

        $result = $table->distinct('name')->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('unique() is an alias', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
        ]);

        $result = $table->unique()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('duplicates() returns only duplicates', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Alice', 30],
        ]);

        $result = $table->duplicates()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('countDistinct() counts unique rows', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
            ['Bob', 25],
        ]);

        $result = $table->countDistinct()->toArray();

        expect($result)->toBe([
            ['name', 'age', 'count'],
            ['Alice', 30, 2],
            ['Bob', 25, 1],
        ]);
    });

    it('isUnique() checks uniqueness', function () {
        $unique = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ]);

        $duplicates = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
        ]);

        expect($unique->isUnique())->toBeTrue();
        expect($duplicates->isUnique())->toBeFalse();
    });

    it('chains with other operations', function () {
        $table = Table::fromArray([
            ['name', 'age', 'score'],
            ['Alice', 30, 85],
            ['Bob', 25, 90],
            ['Alice', 30, 85],  // Duplicate
            ['Charlie', 35, 78],
            ['Bob', 25, 90],    // Duplicate
        ]);

        $result = $table
            ->distinct()
            ->filter(fn ($row) => $row['score'] >= 80)
            ->selectColumns('name', 'score')
            ->toArray();

        expect($result)->toBe([
            ['name', 'score'],
            ['Alice', 85],
            ['Bob', 90],
        ]);
    });
});

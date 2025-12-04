<?php

declare(strict_types=1);

use Phetl\Table;
use Phetl\Transform\Values\StringTransformer;

describe('StringTransformer', function () {
    describe('upper()', function () {
        it('converts field to uppercase', function () {
            $headers = ['name', 'city'];
            $data = [
                ['alice', 'new york'],
                ['bob', 'los angeles'],
            ];

            [$resultHeaders, $resultData] = StringTransformer::upper($headers, $data, 'name');

            expect($resultData)->toBe([
                ['ALICE', 'new york'],
                ['BOB', 'los angeles'],
            ]);
        });

        it('handles null values', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
                [null],
                ['bob'],
            ];

            [$resultHeaders, $resultData] = StringTransformer::upper($headers, $data, 'name');

            expect($resultData)->toBe([
                ['ALICE'],
                [null],
                ['BOB'],
            ]);
        });
    });

    describe('lower()', function () {
        it('converts field to lowercase', function () {
            $headers = ['name', 'city'];
            $data = [
                ['ALICE', 'NEW YORK'],
                ['BOB', 'LOS ANGELES'],
            ];

            [$resHeaders, $resData] = StringTransformer::lower($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'city'],
                ['alice', 'NEW YORK'],
                ['bob', 'LOS ANGELES'],
            ]);
        });
    });

    describe('trim()', function () {
        it('removes whitespace from field', function () {
            $headers = ['name', 'city'];
            $data = [
                ['  alice  ', ' new york'],
                ['bob  ', '  los angeles  '],
            ];

            [$resHeaders, $resData] = StringTransformer::trim($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'city'],
                ['alice', ' new york'],
                ['bob', '  los angeles  '],
            ]);
        });

        it('trims custom characters', function () {
            $headers = ['name'];
            $data = [
                ['__alice__'],
                ['__bob__'],
            ];

            [$resHeaders, $resData] = StringTransformer::trim($headers, $data, 'name', '_');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['alice'],
                ['bob'],
            ]);
        });
    });

    describe('ltrim()', function () {
        it('removes left whitespace', function () {
            $headers = ['name'];
            $data = [
                ['  alice'],
                ['  bob  '],
            ];

            [$resHeaders, $resData] = StringTransformer::ltrim($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['alice'],
                ['bob  '],
            ]);
        });
    });

    describe('rtrim()', function () {
        it('removes right whitespace', function () {
            $headers = ['name'];
            $data = [
                ['alice  '],
                ['  bob  '],
            ];

            [$resHeaders, $resData] = StringTransformer::rtrim($headers, $data, 'name');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['alice'],
                ['  bob'],
            ]);
        });
    });

    describe('substring()', function () {
        it('extracts substring from field', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
                ['bob'],
                ['charlie'],
            ];

            [$resHeaders, $resData] = StringTransformer::substring($headers, $data, 'name', 0, 3);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['ali'],
                ['bob'],
                ['cha'],
            ]);
        });

        it('extracts substring without length', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
                ['bob'],
            ];

            [$resHeaders, $resData] = StringTransformer::substring($headers, $data, 'name', 2);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['ice'],
                ['b'],
            ]);
        });
    });

    describe('left()', function () {
        it('extracts leftmost characters', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
                ['bob'],
            ];

            [$resHeaders, $resData] = StringTransformer::left($headers, $data, 'name', 3);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['ali'],
                ['bob'],
            ]);
        });
    });

    describe('right()', function () {
        it('extracts rightmost characters', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
                ['bob'],
            ];

            [$resHeaders, $resData] = StringTransformer::right($headers, $data, 'name', 3);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['ice'],
                ['bob'],
            ]);
        });
    });

    describe('pad()', function () {
        it('pads string to specified length', function () {
            $headers = ['code'];
            $data = [
                ['1'],
                ['42'],
                ['123'],
            ];

            [$resHeaders, $resData] = StringTransformer::pad($headers, $data, 'code', 5, '0', STR_PAD_LEFT);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['code'],
                ['00001'],
                ['00042'],
                ['00123'],
            ]);
        });

        it('pads string on right by default', function () {
            $headers = ['name'];
            $data = [
                ['alice'],
            ];

            [$resHeaders, $resData] = StringTransformer::pad($headers, $data, 'name', 10, '_');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name'],
                ['alice_____'],
            ]);
        });
    });

    describe('concat()', function () {
        it('concatenates multiple fields', function () {
            $headers = ['first', 'last', 'full'];
            $data = [
                ['Alice', 'Smith', null],
                ['Bob', 'Jones', null],
            ];

            [$resHeaders, $resData] = StringTransformer::concat($headers, $data, 'full', ['first', 'last'], ' ');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['first', 'last', 'full'],
                ['Alice', 'Smith', 'Alice Smith'],
                ['Bob', 'Jones', 'Bob Jones'],
            ]);
        });

        it('concatenates without separator', function () {
            $headers = ['a', 'b', 'c', 'result'];
            $data = [
                ['X', 'Y', 'Z', null],
            ];

            [$resHeaders, $resData] = StringTransformer::concat($headers, $data, 'result', ['a', 'b', 'c']);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['a', 'b', 'c', 'result'],
                ['X', 'Y', 'Z', 'XYZ'],
            ]);
        });
    });

    describe('split()', function () {
        it('splits field into array', function () {
            $headers = ['tags'];
            $data = [
                ['php,python,javascript'],
                ['ruby,go'],
            ];

            [$resHeaders, $resData] = StringTransformer::split($headers, $data, 'tags', ',');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['tags'],
                [['php', 'python', 'javascript']],
                [['ruby', 'go']],
            ]);
        });

        it('limits split results', function () {
            $headers = ['path'];
            $data = [
                ['a/b/c/d'],
            ];

            [$resHeaders, $resData] = StringTransformer::split($headers, $data, 'path', '/', 2);

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['path'],
                [['a', 'b/c/d']],
            ]);
        });
    });

    describe('replace()', function () {
        it('replaces substring with regex', function () {
            $headers = ['text'];
            $data = [
                ['hello world'],
                ['goodbye world'],
            ];

            [$resHeaders, $resData] = StringTransformer::replace($headers, $data, 'text', '/world/', 'universe');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['text'],
                ['hello universe'],
                ['goodbye universe'],
            ]);
        });

        it('replaces multiple occurrences', function () {
            $headers = ['text'];
            $data = [
                ['the cat and the dog'],
            ];

            [$resHeaders, $resData] = StringTransformer::replace($headers, $data, 'text', '/the/', 'a');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['text'],
                ['a cat and a dog'],
            ]);
        });
    });

    describe('extract()', function () {
        it('extracts pattern from field', function () {
            $headers = ['email', 'domain'];
            $data = [
                ['alice@example.com', null],
                ['bob@test.org', null],
            ];

            [$resHeaders, $resData] = StringTransformer::extract($headers, $data, 'email', 'domain', '/@(.+)$/');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['email', 'domain'],
                ['alice@example.com', 'example.com'],
                ['bob@test.org', 'test.org'],
            ]);
        });

        it('returns null when no match', function () {
            $headers = ['text', 'number'];
            $data = [
                ['no numbers here', null],
                ['has 123 numbers', null],
            ];

            [$resHeaders, $resData] = StringTransformer::extract($headers, $data, 'text', 'number', '/(\d+)/');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['text', 'number'],
                ['no numbers here', null],
                ['has 123 numbers', '123'],
            ]);
        });
    });

    describe('match()', function () {
        it('checks if field matches pattern', function () {
            $headers = ['email', 'valid'];
            $data = [
                ['alice@example.com', null],
                ['invalid-email', null],
                ['bob@test.org', null],
            ];

            [$resHeaders, $resData] = StringTransformer::match($headers, $data, 'email', 'valid', '/^[a-z]+@[a-z]+\.[a-z]+$/');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['email', 'valid'],
                ['alice@example.com', true],
                ['invalid-email', false],
                ['bob@test.org', true],
            ]);
        });
    });

    describe('length()', function () {
        it('calculates field length', function () {
            $headers = ['name', 'length'];
            $data = [
                ['alice', null],
                ['bob', null],
                ['charlie', null],
            ];

            [$resHeaders, $resData] = StringTransformer::length($headers, $data, 'name', 'length');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'length'],
                ['alice', 5],
                ['bob', 3],
                ['charlie', 7],
            ]);
        });

        it('handles null values', function () {
            $headers = ['name', 'length'];
            $data = [
                ['alice', null],
                [null, null],
            ];

            [$resHeaders, $resData] = StringTransformer::length($headers, $data, 'name', 'length');

            $full = array_merge([$resHeaders], $resData);
            expect($full)->toBe([
                ['name', 'length'],
                ['alice', 5],
                [null, 0],
            ]);
        });
    });
});

describe('Table string methods', function () {
    it('converts to uppercase', function () {
        $table = Table::fromArray([
            ['name', 'city'],
            ['alice', 'new york'],
            ['bob', 'los angeles'],
        ]);

        $result = $table->upper('name')->toArray();

        expect($result)->toBe([
            ['name', 'city'],
            ['ALICE', 'new york'],
            ['BOB', 'los angeles'],
        ]);
    });

    it('converts to lowercase', function () {
        $table = Table::fromArray([
            ['name'],
            ['ALICE'],
            ['BOB'],
        ]);

        $result = $table->lower('name')->toArray();

        expect($result)->toBe([
            ['name'],
            ['alice'],
            ['bob'],
        ]);
    });

    it('trims whitespace', function () {
        $table = Table::fromArray([
            ['name'],
            ['  alice  '],
            ['bob  '],
        ]);

        $result = $table->trim('name')->toArray();

        expect($result)->toBe([
            ['name'],
            ['alice'],
            ['bob'],
        ]);
    });

    it('concatenates fields', function () {
        $table = Table::fromArray([
            ['first', 'last', 'full'],
            ['Alice', 'Smith', null],
        ]);

        $result = $table->concatFields('full', ['first', 'last'], ' ')->toArray();

        expect($result)->toBe([
            ['first', 'last', 'full'],
            ['Alice', 'Smith', 'Alice Smith'],
        ]);
    });

    it('extracts pattern', function () {
        $table = Table::fromArray([
            ['email', 'domain'],
            ['alice@example.com', null],
        ]);

        $result = $table->extractPattern('email', 'domain', '/@(.+)$/')->toArray();

        expect($result)->toBe([
            ['email', 'domain'],
            ['alice@example.com', 'example.com'],
        ]);
    });

    it('chains string operations', function () {
        $table = Table::fromArray([
            ['name', 'email'],
            ['  ALICE  ', 'ALICE@EXAMPLE.COM'],
            ['  bob  ', 'bob@test.org'],
        ]);

        $result = $table
            ->trim('name')
            ->lower('name')
            ->lower('email')
            ->toArray();

        expect($result)->toBe([
            ['name', 'email'],
            ['alice', 'alice@example.com'],
            ['bob', 'bob@test.org'],
        ]);
    });
});

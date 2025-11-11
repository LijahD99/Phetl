<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\ArrayExtractor;
use PHPUnit\Framework\TestCase;

final class ArrayExtractorTest extends TestCase
{
    public function test_it_implements_extractor_interface(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $extractor = new ArrayExtractor($data);

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
    }

    public function test_it_extracts_array_data(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $extractor = new ArrayExtractor($data);
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
    }

    public function test_it_validates_non_empty_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data array must contain at least a header row');

        new ArrayExtractor([]);
    }

    public function test_it_yields_rows_lazily(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ];

        $extractor = new ArrayExtractor($data);
        $iterator = $extractor->extract();

        // Should be a Generator (lazy)
        $this->assertInstanceOf(\Generator::class, $iterator);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $extractor = new ArrayExtractor($data);

        // First iteration
        $result1 = iterator_to_array($extractor->extract());
        $this->assertCount(2, $result1);

        // Second iteration (should work independently)
        $result2 = iterator_to_array($extractor->extract());
        $this->assertCount(2, $result2);
        $this->assertEquals($result1, $result2);
    }

    public function test_it_handles_single_header_row(): void
    {
        $data = [
            ['name', 'age', 'city'],
        ];

        $extractor = new ArrayExtractor($data);
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(1, $result);
        $this->assertEquals(['name', 'age', 'city'], $result[0]);
    }

    public function test_it_preserves_row_structure(): void
    {
        $data = [
            ['name', 'age', 'metadata'],
            ['Alice', 30, ['role' => 'admin']],
            ['Bob', 25, ['role' => 'user']],
        ];

        $extractor = new ArrayExtractor($data);
        $result = iterator_to_array($extractor->extract());

        $this->assertIsArray($result[1][2]);
        $this->assertEquals(['role' => 'admin'], $result[1][2]);
    }
}

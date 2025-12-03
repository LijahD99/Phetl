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
        [$headers, $dataRows] = $extractor->extract();

        $this->assertEquals(['name', 'age'], $headers);
        $this->assertCount(2, $dataRows);
        $this->assertEquals(['Alice', 30], $dataRows[0]);
        $this->assertEquals(['Bob', 25], $dataRows[1]);
    }

    public function test_it_validates_non_empty_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data array must contain at least a header row when headers are not provided');

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
        $result = $extractor->extract();

        // Should return a tuple [headers, data]
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        [$headers, $dataRows] = $result;
        $this->assertEquals(['name', 'age'], $headers);
        $this->assertCount(3, $dataRows);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $extractor = new ArrayExtractor($data);

        // First extraction
        [$headers1, $data1] = $extractor->extract();
        $this->assertEquals(['name', 'age'], $headers1);
        $this->assertCount(1, $data1);

        // Second extraction (should work independently)
        [$headers2, $data2] = $extractor->extract();
        $this->assertEquals(['name', 'age'], $headers2);
        $this->assertCount(1, $data2);
        $this->assertEquals($headers1, $headers2);
        $this->assertEquals($data1, $data2);
    }

    public function test_it_handles_single_header_row(): void
    {
        $data = [
            ['name', 'age', 'city'],
        ];

        $extractor = new ArrayExtractor($data);
        [$headers, $dataRows] = $extractor->extract();

        $this->assertEquals(['name', 'age', 'city'], $headers);
        $this->assertCount(0, $dataRows); // Only header, no data rows
    }

    public function test_it_preserves_row_structure(): void
    {
        $data = [
            ['name', 'age', 'metadata'],
            ['Alice', 30, ['role' => 'admin']],
            ['Bob', 25, ['role' => 'user']],
        ];

        $extractor = new ArrayExtractor($data);
        [$headers, $dataRows] = $extractor->extract();

        $this->assertEquals(['name', 'age', 'metadata'], $headers);
        $this->assertIsArray($dataRows[0][2]);
        $this->assertEquals(['role' => 'admin'], $dataRows[0][2]);
    }
}

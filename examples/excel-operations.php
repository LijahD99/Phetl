<?php

/**
 * Example: Excel File Operations with PHETL
 *
 * Demonstrates reading, transforming, and writing Excel files (.xlsx format).
 * Shows sheet selection, data type preservation, and transformation pipelines.
 */

require __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

// ============================================================================
// 1. Reading Excel Files
// ============================================================================

echo "1. Reading Excel Files\n";
echo str_repeat('-', 50) . "\n";

// Basic extraction from Excel file
$sales = Table::fromExcel('data/sales.xlsx');
echo "Read " . count($sales->toArray()) . " rows from sales.xlsx\n";

// Extract from specific sheet by name
$summary = Table::fromExcel('data/report.xlsx', 'Summary');
echo "Read from 'Summary' sheet\n";

// Extract from specific sheet by index (0-based)
$quarterly = Table::fromExcel('data/report.xlsx', 1);
echo "Read from sheet index 1\n\n";

// ============================================================================
// 2. Writing Excel Files
// ============================================================================

echo "2. Writing Excel Files\n";
echo str_repeat('-', 50) . "\n";

// Create sample data
$employees = Table::fromArray([
    ['Name', 'Department', 'Salary', 'Active'],
    ['Alice', 'Engineering', 95000, true],
    ['Bob', 'Sales', 75000, true],
    ['Charlie', 'Marketing', 68000, false],
    ['Diana', 'Engineering', 105000, true],
]);

// Write to Excel file
$rowCount = $employees->toExcel('output/employees.xlsx');
echo "Wrote $rowCount rows to employees.xlsx\n";

// Write to specific sheet
$employees->toExcel('output/employees.xlsx', 'EmployeeList');
echo "Wrote to 'EmployeeList' sheet\n\n";

// ============================================================================
// 3. Data Type Preservation
// ============================================================================

echo "3. Data Type Preservation\n";
echo str_repeat('-', 50) . "\n";

$mixedData = Table::fromArray([
    ['String', 'Integer', 'Float', 'Boolean', 'Null'],
    ['Alice', 30, 95.5, true, null],
    ['Bob', 25, 87.3, false, null],
]);

// Write and read back
$mixedData->toExcel('output/types.xlsx');
$roundtrip = Table::fromExcel('output/types.xlsx');

echo "Data types preserved in round-trip:\n";
foreach ($roundtrip->toArray() as $row) {
    print_r($row);
}
echo "\n";

// ============================================================================
// 4. Transformation Pipelines with Excel
// ============================================================================

echo "4. Transformation Pipelines\n";
echo str_repeat('-', 50) . "\n";

// Extract, Transform, Load (ETL) pipeline
Table::fromExcel('data/sales.xlsx')
    ->whereGreaterThan('amount', 1000)
    ->sortBy('date', true)
    ->selectColumns('customer', 'product', 'amount')
    ->addColumn('category', fn($row) => match(true) {
        $row['amount'] >= 5000 => 'Premium',
        $row['amount'] >= 2000 => 'Standard',
        default => 'Basic'
    })
    ->toExcel('output/filtered_sales.xlsx');

echo "Filtered and transformed sales data\n\n";

// ============================================================================
// 5. Multi-Sheet Processing
// ============================================================================

echo "5. Multi-Sheet Processing\n";
echo str_repeat('-', 50) . "\n";

// Process multiple sheets and combine
$sheet1 = Table::fromExcel('data/quarterly_report.xlsx', 'Q1');
$sheet2 = Table::fromExcel('data/quarterly_report.xlsx', 'Q2');
$sheet3 = Table::fromExcel('data/quarterly_report.xlsx', 'Q3');
$sheet4 = Table::fromExcel('data/quarterly_report.xlsx', 'Q4');

// Combine and analyze
$combined = $sheet1
    ->union($sheet2)
    ->union($sheet3)
    ->union($sheet4);

$summary = $combined->aggregate(['region'], [
    'total_sales' => fn($rows) => array_sum(array_column($rows, 'sales')),
    'avg_sales' => fn($rows) => array_sum(array_column($rows, 'sales')) / count($rows),
    'count' => fn($rows) => count($rows),
]);

$summary->toExcel('output/yearly_summary.xlsx', 'AnnualSummary');
echo "Combined quarterly data into yearly summary\n\n";

// ============================================================================
// 6. Converting Between Formats
// ============================================================================

echo "6. Format Conversion\n";
echo str_repeat('-', 50) . "\n";

// Excel to CSV
Table::fromExcel('data/report.xlsx', 'Data')
    ->toCsv('output/report.csv');
echo "Converted Excel to CSV\n";

// CSV to Excel
Table::fromCsv('data/inventory.csv')
    ->toExcel('output/inventory.xlsx');
echo "Converted CSV to Excel\n";

// Excel to JSON
Table::fromExcel('data/products.xlsx')
    ->toJson('output/products.json', true);
echo "Converted Excel to JSON\n";

// JSON to Excel
Table::fromJson('data/users.json')
    ->toExcel('output/users.xlsx');
echo "Converted JSON to Excel\n\n";

// ============================================================================
// 7. Data Cleaning and Validation
// ============================================================================

echo "7. Data Cleaning and Validation\n";
echo str_repeat('-', 50) . "\n";

// Clean and validate Excel data
Table::fromExcel('data/customer_data.xlsx')
    ->whereNotNull('email')
    ->removeColumns('internal_id', 'notes')
    ->renameColumns([
        'fname' => 'first_name',
        'lname' => 'last_name',
    ])
    ->addColumn('full_name', fn($row) => 
        $row['first_name'] . ' ' . $row['last_name']
    )
    ->filterValid([
        'email' => ['required', 'email'],
        'age' => ['required', 'integer', 'min:18'],
    ])
    ->toExcel('output/clean_customers.xlsx');

echo "Cleaned and validated customer data\n\n";

// ============================================================================
// 8. Statistical Analysis
// ============================================================================

echo "8. Statistical Analysis\n";
echo str_repeat('-', 50) . "\n";

// Analyze Excel data
$metrics = Table::fromExcel('data/performance.xlsx')
    ->aggregate(['department'], [
        'avg_score' => fn($rows) => array_sum(array_column($rows, 'score')) / count($rows),
        'max_score' => fn($rows) => max(array_column($rows, 'score')),
        'min_score' => fn($rows) => min(array_column($rows, 'score')),
        'employee_count' => fn($rows) => count($rows),
    ])
    ->sortBy('avg_score', true);

$metrics->toExcel('output/department_metrics.xlsx');

echo "Generated department performance metrics\n";
echo "Results:\n";
foreach ($metrics->toArray() as $row) {
    print_r($row);
}

echo "\n✓ All Excel examples completed successfully!\n";

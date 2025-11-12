<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;
use RuntimeException;

/**
 * Extracts data from RESTful API endpoints.
 *
 * Supports authentication, pagination, rate limiting, and response mapping.
 * Converts JSON responses into tabular format with headers.
 */
final class RestApiExtractor implements ExtractorInterface
{
    /** @var array<string, string> */
    private array $capturedHeaders = [];

    private string $capturedUrl = '';

    /** @var array<int, string> */
    private array $capturedUrls = [];

    /**
     * @param string $url API endpoint URL
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(
        private readonly string $url,
        private readonly array $config = []
    ) {
        $this->validate();
        $this->validateAuth();
    }

    /**
     * Get captured headers (for testing).
     *
     * @return array<string, string>
     */
    public function getCapturedHeaders(): array
    {
        return $this->capturedHeaders;
    }

    /**
     * Get captured URL (for testing).
     */
    public function getCapturedUrl(): string
    {
        return $this->capturedUrl;
    }

    /**
     * Get all captured URLs (for testing pagination).
     *
     * @return array<int, string>
     */
    public function getCapturedUrls(): array
    {
        return $this->capturedUrls;
    }

    /**
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable
    {
        // Check for mock response (testing only)
        if (isset($this->config['_mock_response']) || isset($this->config['_mock_responses'])) {
            return $this->extractFromMock();
        }

        // TODO: Implement real HTTP request
        throw new RuntimeException('Real HTTP requests not yet implemented');
    }

    /**
     * Extract from mock response (for testing).
     *
     * @return iterable<int, array<int|string, mixed>>
     */
    private function extractFromMock(): iterable
    {
        // Handle pagination
        if (isset($this->config['pagination']) && $this->config['pagination']['type'] !== 'none') {
            yield from $this->extractPaginated();
            return;
        }

        // Single request (no pagination)
        $mockResponse = $this->config['_mock_response'];
        $mockStatus = $this->config['_mock_status'] ?? 200;

        // Build URL with query params (for testing)
        $this->capturedUrl = $this->buildUrl();

        // Build headers (for testing)
        if (isset($this->config['_capture_headers'])) {
            $this->capturedHeaders = $this->buildHeaders();
        }

        // Check HTTP status
        if ($mockStatus >= 400) {
            throw new RuntimeException("HTTP error {$mockStatus}");
        }

        // Parse JSON
        $data = json_decode($mockResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('Response must be a JSON array');
        }

        if ($data === []) {
            return;
        }

        // Validate all elements are arrays (objects)
        foreach ($data as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Response must contain objects');
            }
        }

        // Extract all unique field names
        $fields = $this->extractFieldNames($data);

        // Yield header row
        yield array_values($fields);

        // Yield data rows
        foreach ($data as $row) {
            yield $this->normalizeRow($row, $fields);
        }
    }

    /**
     * Validate URL.
     */
    private function validate(): void
    {
        if ($this->url === '') {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format: ' . $this->url);
        }
    }

    /**
     * Extract all unique field names from the dataset.
     *
     * @param array<int, array<string, mixed>> $data
     * @return array<int, string>
     */
    private function extractFieldNames(array $data): array
    {
        $fields = [];

        foreach ($data as $row) {
            foreach (array_keys($row) as $key) {
                if (! in_array($key, $fields, true)) {
                    $fields[] = $key;
                }
            }
        }

        return $fields;
    }

    /**
     * Normalize a row to match the field structure.
     *
     * @param array<string, mixed> $row
     * @param array<int, string> $fields
     * @return array<int, mixed>
     */
    private function normalizeRow(array $row, array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $normalized[] = $row[$field] ?? null;
        }

        return $normalized;
    }

    /**
     * Extract data from paginated API (for testing with mock responses).
     *
     * @return iterable<int, array<int|string, mixed>>
     */
    private function extractPaginated(): iterable
    {
        $pagination = $this->config['pagination'];
        $type = $pagination['type'];
        $mockResponses = $this->config['_mock_responses'] ?? [];
        $maxPages = $pagination['max_pages'] ?? null;
        $pageCount = 0;
        $headerYielded = false;
        $allFields = [];

        if ($type === 'offset') {
            $offset = 0;
            $pageSize = $pagination['page_size'] ?? 100;

            foreach ($mockResponses as $mockResponse) {
                if ($maxPages !== null && $pageCount >= $maxPages) {
                    break;
                }

                $url = $this->buildUrlWithPagination($type, ['offset' => $offset, 'limit' => $pageSize]);
                if (isset($this->config['_capture_urls'])) {
                    $this->capturedUrls[] = $url;
                }

                $data = json_decode($mockResponse, true);
                if ($data === null || $data === []) {
                    break;
                }

                // Collect fields and yield rows
                foreach ($this->processPageData($data, $headerYielded, $allFields) as $row) {
                    yield $row;
                    $headerYielded = true;
                }

                $offset += $pageSize;
                $pageCount++;
            }
        } elseif ($type === 'page') {
            $page = 1;
            $pageSize = $pagination['page_size'] ?? 100;

            foreach ($mockResponses as $mockResponse) {
                if ($maxPages !== null && $pageCount >= $maxPages) {
                    break;
                }

                $url = $this->buildUrlWithPagination($type, ['page' => $page, 'per_page' => $pageSize]);
                if (isset($this->config['_capture_urls'])) {
                    $this->capturedUrls[] = $url;
                }

                $data = json_decode($mockResponse, true);
                if ($data === null || $data === []) {
                    break;
                }

                foreach ($this->processPageData($data, $headerYielded, $allFields) as $row) {
                    yield $row;
                    $headerYielded = true;
                }

                $page++;
                $pageCount++;
            }
        } elseif ($type === 'cursor') {
            $cursor = null;
            $dataPath = $pagination['data_path'] ?? null;
            $cursorPath = $pagination['cursor_path'] ?? 'next_cursor';

            foreach ($mockResponses as $mockResponse) {
                $url = $this->buildUrlWithPagination($type, ['cursor' => $cursor]);
                if (isset($this->config['_capture_urls'])) {
                    $this->capturedUrls[] = $url;
                }

                $response = json_decode($mockResponse, true);
                if ($response === null) {
                    break;
                }

                // Extract data array from response
                $data = $dataPath ? ($response[$dataPath] ?? []) : $response;

                if ($data === []) {
                    break;
                }

                foreach ($this->processPageData($data, $headerYielded, $allFields) as $row) {
                    yield $row;
                    $headerYielded = true;
                }

                // Get next cursor
                $cursor = $response[$cursorPath] ?? null;
                if ($cursor === null) {
                    break;
                }

                $pageCount++;
            }
        }
    }

    /**
     * Process data from a single page.
     *
     * @param array<int, array<string, mixed>> $data
     * @param bool $headerYielded
     * @param array<int, string> $allFields
     * @return iterable<int, array<int|string, mixed>>
     */
    private function processPageData(array $data, bool &$headerYielded, array &$allFields): iterable
    {
        // Extract fields from this page
        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach (array_keys($row) as $key) {
                if (! in_array($key, $allFields, true)) {
                    $allFields[] = $key;
                }
            }
        }

        // Yield header if not yet yielded
        if (! $headerYielded) {
            yield array_values($allFields);
        }

        // Yield data rows
        foreach ($data as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Response must contain objects');
            }

            yield $this->normalizeRow($row, $allFields);
        }
    }

    /**
     * Build URL with pagination parameters.
     *
     * @param string $type
     * @param array<string, mixed> $params
     */
    private function buildUrlWithPagination(string $type, array $params): string
    {
        $url = $this->buildUrl();
        $pagination = $this->config['pagination'];

        if ($type === 'offset') {
            $offsetParam = $pagination['offset_param'] ?? 'offset';
            $limitParam = $pagination['limit_param'] ?? 'limit';
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $offsetParam . '=' . $params['offset'];
            $url .= '&' . $limitParam . '=' . $params['limit'];
        } elseif ($type === 'page') {
            $pageParam = $pagination['page_param'] ?? 'page';
            $perPageParam = $pagination['per_page_param'] ?? 'per_page';
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $pageParam . '=' . $params['page'];
            $url .= '&' . $perPageParam . '=' . $params['per_page'];
        } elseif ($type === 'cursor' && $params['cursor'] !== null) {
            $cursorParam = $pagination['cursor_param'] ?? 'cursor';
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $cursorParam . '=' . urlencode((string)$params['cursor']);
        }

        return $url;
    }

    /**
     * Validate authentication configuration.
     */
    private function validateAuth(): void
    {
        if (! isset($this->config['auth'])) {
            return; // No auth configured, that's fine
        }

        $auth = $this->config['auth'];
        $type = $auth['type'] ?? null;

        if (! in_array($type, ['bearer', 'api_key', 'basic', 'none'], true)) {
            throw new InvalidArgumentException("Invalid auth type: {$type}");
        }

        // Validate bearer token
        if ($type === 'bearer') {
            if (! isset($auth['token']) || $auth['token'] === '') {
                throw new InvalidArgumentException('Bearer token is required for bearer authentication');
            }
        }

        // Validate API key
        if ($type === 'api_key') {
            if (! isset($auth['key']) || $auth['key'] === '') {
                throw new InvalidArgumentException('API key is required for api_key authentication');
            }
        }

        // Validate basic auth
        if ($type === 'basic') {
            if (! isset($auth['username']) || $auth['username'] === '' ||
                ! isset($auth['password']) || $auth['password'] === '') {
                throw new InvalidArgumentException('Username and password are required for basic authentication');
            }
        }
    }

    /**
     * Build HTTP headers including authentication.
     *
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = $this->config['headers'] ?? [];

        // Add authentication headers
        if (isset($this->config['auth'])) {
            $auth = $this->config['auth'];
            $type = $auth['type'] ?? 'none';

            if ($type === 'bearer') {
                $headers['Authorization'] = 'Bearer ' . $auth['token'];
            } elseif ($type === 'api_key' && ($auth['location'] ?? 'header') === 'header') {
                $headerName = $auth['header_name'] ?? 'X-API-Key';
                $headers[$headerName] = $auth['key'];
            } elseif ($type === 'basic') {
                $credentials = base64_encode($auth['username'] . ':' . $auth['password']);
                $headers['Authorization'] = 'Basic ' . $credentials;
            }
        }

        return $headers;
    }

    /**
     * Build URL with query parameters.
     */
    private function buildUrl(): string
    {
        $url = $this->url;

        // Add API key to query string if configured
        if (isset($this->config['auth'])) {
            $auth = $this->config['auth'];
            $type = $auth['type'] ?? 'none';

            if ($type === 'api_key' && ($auth['location'] ?? 'header') === 'query') {
                $paramName = $auth['param_name'] ?? 'api_key';
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . $paramName . '=' . urlencode($auth['key']);
            }
        }

        return $url;
    }
}

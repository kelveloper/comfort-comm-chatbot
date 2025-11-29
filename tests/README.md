# Test Suite

## Running Tests

Run all tests:
```bash
php tests/run-tests.php
```

Run specific test:
```bash
php tests/run-tests.php --test=vector-search
php tests/run-tests.php --test=faq-crud
php tests/run-tests.php --test=supabase-connection
php tests/run-tests.php --test=embedding-generation
```

Verbose mode (show details for passing tests):
```bash
php tests/run-tests.php --verbose
```

## Test Files

| File | Description |
|------|-------------|
| `test-supabase-connection.php` | Tests Supabase configuration and connectivity |
| `test-faq-crud.php` | Tests FAQ Create, Read, Update, Delete operations |
| `test-vector-search.php` | Tests semantic search functionality |
| `test-embedding-generation.php` | Tests Gemini embedding API |

## Requirements

- WordPress must be installed and accessible
- Supabase must be configured in wp-config.php
- Gemini API key must be configured in plugin settings

## Adding New Tests

1. Create a new file: `tests/test-{name}.php`
2. Use `test_assert()` function for assertions:

```php
test_assert(
    $condition,           // boolean - the condition to test
    'Test name',          // string - description of the test
    'Optional message'    // string - additional context shown on verbose
);
```

## Exit Codes

- `0` - All tests passed
- `1` - One or more tests failed

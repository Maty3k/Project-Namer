# Test Structure

This project organizes tests into separate suites to optimize development workflow and CI/CD performance.

## Test Suites

### Unit Tests (`tests/Unit/`)
- Fast, isolated unit tests
- Test individual classes and methods
- No external dependencies or database interactions
- Run as part of default test suite

### Feature Tests (`tests/Feature/`)
- Application-level tests with proper mocking
- Test complete features and user workflows
- Use mocked external services (HTTP, DNS, AI APIs)
- Run as part of default test suite

### Integration Tests (`tests/Integration/`)
- End-to-end workflow tests
- May test complex interactions between multiple components
- Run separately from default suite to avoid timeouts
- Use `composer test-integration` to run

### Performance Tests (`tests/Performance/`)
- Benchmark and performance validation tests
- May take longer to execute
- Run separately from default suite
- Use `composer test-performance` to run

## Running Tests

### Default Test Suite (Fast)
```bash
# Runs Unit + Feature tests only
composer test
php artisan test
```

### Parallel Testing (Fast)
```bash
# Runs Unit + Feature tests in parallel
composer test-parallel
```

### Integration Tests
```bash
# Runs integration tests separately
composer test-integration
```

### Performance Tests
```bash
# Runs performance tests separately
composer test-performance
```

### All Tests
```bash
# Runs all test suites
composer test-all
```

## Guidelines

### Default Test Suite
- Should complete in under 2 minutes
- Uses mocked external services
- No real API calls or network requests
- Safe for CI/CD and continuous development

### Integration Tests
- Can take longer to execute
- May test real integrations (when appropriate)
- Run manually or in separate CI steps

### Performance Tests
- Focus on benchmarks and performance validation
- May include longer-running operations
- Run on-demand or in specialized CI environments

## External Service Mocking

All tests in the default suite (Unit + Feature) use proper mocking:

- **HTTP Requests**: Use `Http::fake()`
- **DNS Lookups**: Mock `DnsResolverInterface`
- **AI APIs**: Mock Prism or specific AI service classes
- **File Operations**: Use `Storage::fake()`
- **Queue Jobs**: Use `Queue::fake()`

This ensures tests are fast, reliable, and don't depend on external services.
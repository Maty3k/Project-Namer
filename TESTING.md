# Testing Guide

## Running Tests

### Standard Test Execution (Recommended)

```bash
php artisan test
```

This runs all tests sequentially with proper database isolation.

### Parallel Testing (Not Recommended)

⚠️ **IMPORTANT:** Do not use the `--parallel` flag with this project.

```bash
# ❌ DO NOT USE
php artisan test --parallel
```

**Why?** Parallel test execution causes database state conflicts when using SQLite `:memory:` databases. Tests that check record counts will fail because data from parallel processes bleeds between test runs.

**Symptoms of parallel execution issues:**
- Failed assertions like "Failed asserting that 2 is identical to 1"
- Unexpected record counts in database assertions
- Tests pass individually but fail when run together
- Inconsistent test results between runs

### Running Specific Test Suites

```bash
# Run only feature tests
php artisan test --testsuite=Feature

# Run only unit tests
php artisan test --testsuite=Unit

# Run a specific test file
php artisan test tests/Feature/Livewire/SidebarBulkDeleteTest.php

# Run tests matching a filter
php artisan test --filter="SidebarBulkDeleteTest"
```

### Test Coverage

Generate test coverage report:

```bash
php artisan test --coverage
```

### Continuous Integration

When setting up CI/CD pipelines, ensure the test command does NOT include `--parallel`:

```yaml
# ✅ Correct CI configuration
- name: Run Tests
  run: php artisan test

# ❌ Incorrect - will cause failures
- name: Run Tests
  run: php artisan test --parallel
```

## Test Configuration

- **Database:** SQLite in-memory (`:memory:`)
- **Test Framework:** Pest PHP
- **Database Refresh:** Automatic via `RefreshDatabase` trait
- **Isolation:** Per-test (sequential execution only)

## Writing Tests

All feature tests automatically use:
- `RefreshDatabase` trait (configured in `tests/Pest.php`)
- Vite disabled for faster execution
- No execution time limits

### Example Test Structure

```php
<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('example test', function (): void {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    expect(Project::count())->toBe(1);
    expect($project->user_id)->toBe($this->user->id);
});
```

## Troubleshooting

### Tests Failing Due to Record Counts

If tests fail with assertions like:
```
Failed asserting that 2 is identical to 1
```

**Cause:** Tests were run with `--parallel` flag.

**Solution:** Run without `--parallel`:
```bash
php artisan test
```

### Unique Constraint Violations

If you see unique constraint violations (especially in logo generation tests), this is also caused by parallel execution with ID collisions.

**Solution:** Run sequentially without `--parallel`.

## Future Improvements

To enable parallel testing in the future, consider:

1. **Use file-based SQLite databases** with unique names per process
2. **Switch to PostgreSQL/MySQL** for test database
3. **Add ParallelTesting trait** with proper database isolation
4. **Configure separate databases** per parallel worker

Example configuration for parallel testing:
```php
// phpunit.xml
<env name="DB_DATABASE" value="testing_{PARALLEL_PROCESS_ID}.sqlite"/>
```

This would require modifying the test configuration to create isolated database files for each parallel process.

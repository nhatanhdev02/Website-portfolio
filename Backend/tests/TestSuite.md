# Comprehensive Test Suite Documentation

## Overview

This comprehensive test suite covers all aspects of the Laravel Admin Backend API system, including unit tests, feature tests, integration tests, security tests, and performance tests.

## Test Structure

### 1. Unit Tests (`tests/Unit/`)

#### Services Layer Tests
- `AuthServiceTest.php` - Tests authentication service with mocked dependencies
- `HeroServiceTest.php` - Tests hero content management service
- `AboutServiceTest.php` - Tests about section service with file upload handling
- `ServiceManagementServiceTest.php` - Tests service CRUD operations and ordering
- `ProjectServiceTest.php` - Tests project management with image handling
- `BlogServiceTest.php` - Tests blog post management and publishing workflow
- `ContactServiceTest.php` - Tests contact message management (existing)
- `SettingsServiceTest.php` - Tests system settings management (existing)
- `FileUploadServiceTest.php` - Tests file upload service with various scenarios

#### Repository Layer Tests
- `BaseRepositoryTest.php` - Tests base repository functionality
- `ProjectRepositoryTest.php` - Tests project repository with filtering and ordering
- `BlogRepositoryTest.php` - Tests blog repository with pagination and search
- `ContactRepositoryTest.php` - Tests contact repository with bulk operations

### 2. Feature Tests (`tests/Feature/Api/Admin/`)

#### API Endpoint Tests
- `AuthControllerTest.php` - Tests authentication endpoints (login, logout, refresh, profile)
- `HeroControllerTest.php` - Tests hero content management endpoints
- `ProjectControllerTest.php` - Tests project CRUD endpoints with file uploads
- `BlogControllerTest.php` - Tests blog post management and publishing endpoints

### 3. Integration Tests (`tests/Feature/Integration/`)

#### Workflow Tests
- `AdminWorkflowTest.php` - Tests complete admin workflows from login to content management

### 4. Security Tests (`tests/Feature/Security/`)

#### Authentication Security
- `AuthenticationSecurityTest.php` - Tests rate limiting, token expiration, session management

#### File Upload Security
- `FileUploadSecurityTest.php` - Tests file upload security, validation, and sanitization

#### Authorization Security
- `AuthorizationSecurityTest.php` - Tests access control, SQL injection prevention, XSS protection

### 5. Performance Tests (`tests/Feature/Performance/`)

#### API Performance
- `ApiPerformanceTest.php` - Tests response times, database query optimization, memory usage

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Categories

#### Unit Tests Only
```bash
php artisan test tests/Unit/
```

#### Feature Tests Only
```bash
php artisan test tests/Feature/
```

#### Security Tests Only
```bash
php artisan test tests/Feature/Security/
```

#### Performance Tests Only
```bash
php artisan test tests/Feature/Performance/
```

### Run Specific Test Files
```bash
php artisan test tests/Unit/Services/AuthServiceTest.php
php artisan test tests/Feature/Api/Admin/ProjectControllerTest.php
```

### Run Tests with Coverage
```bash
php artisan test --coverage
```

### Run Tests in Parallel
```bash
php artisan test --parallel
```

## Test Database Configuration

The tests use a separate test database to avoid affecting development data:

```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Key Testing Features

### 1. Database Transactions
All tests use `RefreshDatabase` trait to ensure clean state between tests.

### 2. Factory Usage
Tests use model factories for consistent test data generation.

### 3. Mocking
Unit tests use Mockery for dependency injection and isolation.

### 4. File Upload Testing
Tests use Laravel's fake file system for upload testing.

### 5. Authentication Testing
Tests use Laravel Sanctum's testing helpers for authentication.

## Test Coverage Goals

- **Unit Tests**: 90%+ coverage for Services and Repositories
- **Feature Tests**: 100% coverage for API endpoints
- **Integration Tests**: Complete workflow coverage
- **Security Tests**: All security vulnerabilities covered
- **Performance Tests**: Response time and resource usage validation

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
        
    - name: Install Dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader
      
    - name: Copy Environment File
      run: cp .env.example .env
      
    - name: Generate Application Key
      run: php artisan key:generate
      
    - name: Run Tests
      run: php artisan test --coverage
```

## Test Data Management

### Factories
All models have corresponding factories for test data generation:
- `AdminFactory.php`
- `HeroFactory.php`
- `ProjectFactory.php`
- `BlogPostFactory.php`
- `ServiceFactory.php`
- `ContactMessageFactory.php`

### Seeders
Test-specific seeders for complex scenarios:
- `TestDataSeeder.php`

## Performance Benchmarks

### Response Time Targets
- Simple GET endpoints: < 500ms
- List endpoints with pagination: < 1000ms
- File upload endpoints: < 5000ms
- Bulk operations: < 3000ms

### Memory Usage Targets
- Standard operations: < 50MB
- Large dataset operations: < 100MB

## Security Test Coverage

### Authentication
- Rate limiting
- Token expiration
- Session management
- Invalid token handling

### Authorization
- Access control
- SQL injection prevention
- XSS protection
- CSRF protection

### File Upload Security
- File type validation
- Size limits
- Malicious file detection
- Path traversal prevention

## Maintenance

### Adding New Tests
1. Follow existing naming conventions
2. Use appropriate test categories (Unit/Feature/Integration/Security/Performance)
3. Include proper documentation
4. Ensure tests are isolated and repeatable

### Updating Tests
1. Update tests when adding new features
2. Maintain backward compatibility
3. Update documentation
4. Verify test coverage remains high

## Troubleshooting

### Common Issues
1. **Database connection errors**: Check test database configuration
2. **File permission errors**: Ensure storage directories are writable
3. **Memory limit errors**: Increase PHP memory limit for tests
4. **Timeout errors**: Optimize slow tests or increase timeout limits

### Debug Commands
```bash
# Run tests with verbose output
php artisan test --verbose

# Run specific test with debugging
php artisan test tests/Unit/Services/AuthServiceTest.php --verbose

# Check test coverage
php artisan test --coverage-html coverage/
```

# Laravel Admin Backend API - Postman Collection

This directory contains a comprehensive Postman collection and testing suite for the Laravel Admin Backend API, including automated testing capabilities, CI/CD integration, and detailed documentation.

## Files Overview

### Core Files
- `Laravel-Admin-Backend-API.postman_collection.json` - Complete API collection with all endpoints and tests
- `Laravel-Admin-Backend-Development.postman_environment.json` - Development environment variables
- `Laravel-Admin-Backend-Production.postman_environment.json` - Production environment variables

### Testing & Automation
- `run-tests.js` - Advanced test runner script using Newman with comprehensive features
- `ci-test.sh` - CI/CD integration script for automated testing pipelines
- `package.json` - Node.js dependencies and npm scripts for easy test execution

### Documentation
- `README.md` - This comprehensive documentation file

## Quick Start

### 1. Import into Postman

1. Open Postman
2. Click "Import" button
3. Select all JSON files from this directory
4. Choose the appropriate environment (Development/Production)

### 2. Setup Authentication

1. Run the "Admin Login" request from the Authentication folder
2. The auth token will be automatically saved to environment variables
3. All subsequent requests will use this token automatically

### 3. Environment Variables

The collection uses the following environment variables:

| Variable | Description | Example |
|----------|-------------|---------|
| `base_url` | API base URL | `http://127.0.0.1:8000` |
| `admin_username` | Admin username | `admin` |
| `admin_password` | Admin password | `password123` |
| `auth_token` | JWT/Sanctum token | Auto-populated after login |
| `service_id` | Service ID for testing | Auto-populated after creation |
| `project_id` | Project ID for testing | Auto-populated after creation |

## Collection Structure

### 🔐 Authentication
- **Admin Login** - Authenticate and get JWT/Sanctum token
- **Get Current User** - Retrieve current authenticated user info
- **Refresh Token** - Refresh authentication token
- **Admin Logout** - Logout and invalidate token

### 🦸 Hero Section
- **Get Hero Content** - Retrieve hero section data with bilingual support
- **Update Hero Content** - Update hero section with Vietnamese/English content

### ℹ️ About Section
- **Get About Content** - Retrieve about section content
- **Update About Content** - Update about content with skills and experience
- **Upload Profile Image** - Upload and manage profile image with validation

### 🛠️ Services
- **Get All Services** - List all services with pagination and ordering
- **Create Service** - Create new service with bilingual content and styling
- **Get Service by ID** - Retrieve specific service details
- **Update Service** - Update existing service information
- **Reorder Services** - Update service display order via drag-and-drop
- **Delete Service** - Remove service (soft delete)

### 🚀 Projects
- **Get All Projects** - List projects with filtering (category, featured status)
- **Create Project** - Create new project with technologies and images
- **Get Project by ID** - Retrieve specific project details
- **Update Project** - Update existing project information
- **Toggle Project Featured** - Toggle featured status for highlighting
- **Delete Project** - Remove project and associated images

### 📝 Blog Posts
- **Get All Blog Posts** - List blog posts with status filtering (draft/published)
- **Create Blog Post** - Create new blog post with markdown content
- **Get Blog Post by ID** - Retrieve specific blog post
- **Update Blog Post** - Update existing blog post content
- **Publish Blog Post** - Change status from draft to published
- **Delete Blog Post** - Remove blog post

### 📧 Contact Management
- **Get Contact Messages** - List contact messages with read/unread filtering
- **Get Contact Message by ID** - Retrieve specific message details
- **Mark Message as Read** - Update read status for individual messages
- **Bulk Mark as Read** - Mark multiple messages as read simultaneously
- **Delete Contact Message** - Remove contact message
- **Get Contact Info** - Retrieve contact information settings
- **Update Contact Info** - Update contact details and social links

### ⚙️ System Settings
- **Get System Settings** - Retrieve current system configuration
- **Update System Settings** - Update site settings, theme, and SEO
- **Toggle Maintenance Mode** - Enable/disable maintenance mode

## 🧪 Automated Testing

### Prerequisites

#### Option 1: Quick Setup
```bash
cd Backend/docs/postman
npm run setup
```

#### Option 2: Manual Installation
```bash
npm install -g newman
npm install -g newman-reporter-html
npm install -g newman-reporter-htmlextra
```

### Running Tests

#### Basic Usage
```bash
# Development environment
npm run test:dev
# or
node run-tests.js dev

# Production environment
npm run test:prod
# or
node run-tests.js prod
```

#### Advanced Options
```bash
# Test specific folder/section
npm run test:auth                    # Authentication tests only
npm run test:services               # Services tests only
node run-tests.js dev --folder="Hero Section"

# Verbose output with detailed logs
npm run test:verbose
node run-tests.js dev --verbose

# Stop on first failure
npm run test:bail
node run-tests.js dev --bail

# Multiple iterations
node run-tests.js dev --iterations=3

# Skip SSL verification (for self-signed certificates)
node run-tests.js prod --insecure

# Health check before tests
node run-tests.js dev --health-check
```

#### CI/CD Integration
```bash
# Run comprehensive CI/CD test suite
./ci-test.sh dev
./ci-test.sh prod 120 5  # prod environment, 120s timeout, 5 retries

# Available npm scripts for CI/CD
npm run test:ci          # Optimized for CI environments
npm run validate-collection  # Validate collection syntax
npm run clean-reports    # Clean old test reports
```

### Test Reports

Test reports are automatically generated in the `reports/` directory:

#### Report Types
- **HTML Enhanced Report** (`htmlextra`) - Interactive, detailed visual report with request/response data
- **HTML Standard Report** (`html`) - Basic HTML report for visual review
- **JSON Report** - Machine-readable results for programmatic analysis
- **CLI Output** - Real-time console output during test execution

#### Report Features
- 📊 **Test Statistics** - Pass/fail counts, execution times
- 🔍 **Request/Response Details** - Full HTTP data with syntax highlighting
- 📈 **Performance Metrics** - Response times and performance analysis
- 🚨 **Failure Analysis** - Detailed error messages and stack traces
- 🌍 **Environment Data** - Variable values and configuration
- 📱 **Mobile-Friendly** - Responsive design for viewing on any device

## Test Features

### Automatic Token Management
- Login request automatically saves auth token
- All protected endpoints use the saved token
- Logout request clears the token

### Response Validation
- Status code validation
- Response structure validation
- Data type validation
- Business logic validation

### Global Tests
- Response time validation (< 2000ms)
- Content-Type validation
- Error handling validation

### Environment-Specific Testing
- Development: Uses local server (127.0.0.1:8000)
- Production: Uses production API endpoint

## Usage Examples

### Basic Workflow
1. Import collection and environment
2. Run "Admin Login" to authenticate
3. Test any endpoint - authentication is handled automatically
4. Use "Admin Logout" when finished

### Testing CRUD Operations
1. Create a service using "Create Service"
2. Note the returned ID (automatically saved to `service_id`)
3. Use "Get Service by ID" to retrieve it
4. Use "Update Service" to modify it
5. Use "Delete Service" to remove it

### Filtering and Pagination
- Use query parameters in "Get All Services" and "Get All Projects"
- Test different `per_page` values
- Test filtering by category, featured status, etc.

## Error Handling

The collection includes comprehensive error handling tests:
- 401 Unauthorized responses
- 422 Validation error responses
- 404 Not Found responses
- 500 Server error responses

## Security Testing

- Authentication token validation
- Protected endpoint access control
- Input validation testing
- Rate limiting testing (if implemented)

## Customization

### Adding New Endpoints
1. Create new request in appropriate folder
2. Add authentication header: `Bearer {{auth_token}}`
3. Add appropriate tests in the "Tests" tab
4. Update environment variables if needed

### Custom Test Scripts
Add custom validation in the "Tests" tab:
```javascript
pm.test("Custom validation", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property('custom_field');
});
```

## Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Run "Admin Login" first
   - Check if token has expired
   - Verify credentials in environment

2. **Connection Refused**
   - Ensure Laravel server is running
   - Check `base_url` in environment
   - Verify port number

3. **Validation Errors**
   - Check request body format
   - Verify required fields
   - Review API documentation

### Debug Mode
Enable Postman Console (View > Show Postman Console) to see:
- Request/response details
- Console.log outputs
- Error messages

## 🔧 Advanced Features

### Collection-Level Testing
- **Automatic Token Management** - Login saves token, logout clears it
- **Global Response Validation** - Response time and content-type checks
- **Environment Variable Management** - Dynamic ID storage and reuse
- **Error Handling Tests** - Comprehensive validation of error responses

### Security Testing
- **Authentication Flow Testing** - Complete login/logout workflows
- **Authorization Validation** - Protected endpoint access control
- **Input Validation Testing** - Malformed request handling
- **Rate Limiting Tests** - API throttling validation (if implemented)

### Performance Testing
- **Response Time Monitoring** - Automatic performance benchmarking
- **Load Testing Capability** - Multiple iteration support
- **Timeout Configuration** - Environment-specific timeout settings
- **Retry Logic** - Automatic retry on transient failures

## 🚀 CI/CD Integration

### GitHub Actions Example
```yaml
name: API Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Install dependencies
        run: |
          cd Backend/docs/postman
          npm install
      - name: Run API tests
        run: |
          cd Backend/docs/postman
          ./ci-test.sh dev
      - name: Upload test reports
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: test-reports
          path: Backend/docs/postman/reports/
```

### Jenkins Pipeline Example
```groovy
pipeline {
    agent any
    stages {
        stage('API Tests') {
            steps {
                dir('Backend/docs/postman') {
                    sh 'npm install'
                    sh './ci-test.sh dev'
                }
            }
            post {
                always {
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'Backend/docs/postman/reports',
                        reportFiles: '*.html',
                        reportName: 'API Test Report'
                    ])
                }
            }
        }
    }
}
```

## 📚 Usage Examples

### Complete Workflow Testing
```bash
# 1. Test authentication flow
npm run test:auth

# 2. Test content management
npm run test:hero
npm run test:services
npm run test:projects

# 3. Test blog functionality
npm run test:blog

# 4. Test contact management
npm run test:contact

# 5. Test system settings
npm run test:settings

# 6. Run full test suite
npm run test:dev
```

### Development Workflow
```bash
# Start Laravel development server
cd Backend
php artisan serve

# In another terminal, run tests
cd Backend/docs/postman
npm run test:dev --verbose

# Watch for changes and re-run specific tests
npm run test:services  # After modifying services endpoints
```

## 🛠️ Customization

### Adding New Endpoints
1. **Add Request to Collection**
   - Create new request in appropriate folder
   - Add authentication header: `Bearer {{auth_token}}`
   - Configure request body and parameters

2. **Add Tests**
   ```javascript
   pm.test("Status code is 200", function () {
       pm.response.to.have.status(200);
   });
   
   pm.test("Response has required fields", function () {
       var jsonData = pm.response.json();
       pm.expect(jsonData).to.have.property('success');
       pm.expect(jsonData.success).to.be.true;
   });
   ```

3. **Update Environment Variables**
   - Add new variables to environment files
   - Use dynamic variable assignment in tests

4. **Update Documentation**
   - Add endpoint to this README
   - Include usage examples
   - Document expected responses

### Custom Test Scripts
```javascript
// Pre-request script example
const timestamp = Date.now();
pm.environment.set('timestamp', timestamp);

// Test script example
pm.test("Custom business logic validation", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.created_at).to.match(/^\d{4}-\d{2}-\d{2}/);
});

// Environment variable management
if (pm.response.code === 201) {
    const responseJson = pm.response.json();
    pm.environment.set('created_id', responseJson.data.id);
}
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Authentication Failures
```bash
# Check if Laravel server is running
curl http://127.0.0.1:8000/api/health

# Verify credentials in environment
cat Laravel-Admin-Backend-Development.postman_environment.json | grep -A5 admin_username

# Re-run login request manually
npm run test:auth
```

#### 2. Connection Issues
```bash
# Check server status
php artisan serve --host=127.0.0.1 --port=8000

# Verify environment configuration
node -e "console.log(require('./Laravel-Admin-Backend-Development.postman_environment.json'))"

# Test with curl
curl -H "Accept: application/json" http://127.0.0.1:8000/api/admin/auth/login
```

#### 3. Test Failures
```bash
# Run with verbose output
npm run test:verbose

# Run specific failing test
node run-tests.js dev --folder="Services" --verbose

# Check detailed HTML report
open reports/test-report-dev-*.html
```

#### 4. Environment Issues
```bash
# Validate collection syntax
npm run validate-collection

# Check Newman installation
newman --version

# Reinstall dependencies
npm run install-deps
```

### Debug Mode
Enable detailed debugging:
```bash
# Enable Postman Console logging
DEBUG=* node run-tests.js dev

# Run with maximum verbosity
node run-tests.js dev --verbose --bail

# Check individual request
newman run Laravel-Admin-Backend-API.postman_collection.json \
  -e Laravel-Admin-Backend-Development.postman_environment.json \
  --folder "Authentication" \
  --verbose
```

## 📊 Monitoring & Analytics

### Performance Monitoring
The test suite automatically tracks:
- **Response Times** - Average, min, max response times per endpoint
- **Success Rates** - Pass/fail ratios over time
- **Error Patterns** - Common failure points and error types
- **API Health** - Overall API availability and performance

### Reporting Integration
- **Slack Notifications** - Automated test result notifications
- **Email Reports** - Scheduled test summary emails
- **Dashboard Integration** - Real-time test metrics
- **Historical Tracking** - Long-term performance trends

## 🤝 Contributing

### Adding New Test Cases
1. **Fork the repository**
2. **Create feature branch** - `git checkout -b feature/new-endpoint-tests`
3. **Add test requests** - Include comprehensive test coverage
4. **Update documentation** - Add usage examples and descriptions
5. **Test thoroughly** - Verify both success and error scenarios
6. **Submit pull request** - Include detailed description of changes

### Best Practices
- ✅ **Comprehensive Testing** - Cover all success and error scenarios
- ✅ **Clear Naming** - Use descriptive request and test names
- ✅ **Environment Variables** - Use variables for dynamic data
- ✅ **Error Handling** - Test validation and error responses
- ✅ **Documentation** - Keep README and comments up to date
- ✅ **Performance** - Monitor and optimize test execution time

## 📞 Support

### Getting Help
1. **Check Documentation** - Review this README and inline comments
2. **Review Test Reports** - Check HTML reports for detailed error information
3. **Enable Debug Mode** - Use verbose logging for troubleshooting
4. **Check Laravel Logs** - Review backend application logs
5. **Community Support** - Open GitHub issues for bugs or questions

### Useful Resources
- [Newman Documentation](https://learning.postman.com/docs/running-collections/using-newman-cli/command-line-integration-with-newman/)
- [Postman Testing Guide](https://learning.postman.com/docs/writing-scripts/test-scripts/)
- [Laravel API Documentation](https://laravel.com/docs/api-resources)
- [JSON Schema Validation](https://json-schema.org/)

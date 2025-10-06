# API Troubleshooting Guide

This guide helps you diagnose and resolve common issues when working with the Laravel Admin Backend API.

## Table of Contents

- [Authentication Issues](#authentication-issues)
- [Request/Response Problems](#requestresponse-problems)
- [File Upload Issues](#file-upload-issues)
- [Performance Problems](#performance-problems)
- [Network and Connectivity](#network-and-connectivity)
- [Debugging Tools](#debugging-tools)

## Authentication Issues

### 1. 401 Unauthorized Error

**Symptoms:**
- Getting 401 status code on protected endpoints
- "Unauthenticated" error message

**Common Causes & Solutions:**

#### Missing or Invalid Token
```javascript
// ❌ Wrong - Missing Bearer prefix
headers: {
    'Authorization': 'abc123token'
}

// ✅ Correct - Proper Bearer token format
headers: {
    'Authorization': 'Bearer abc123token'
}
```

#### Expired Token
```javascript
// Check token expiration and refresh
async function makeAuthenticatedRequest(url, options) {
    try {
        const response = await fetch(url, options);
        
        if (response.status === 401) {
            // Try to refresh token
            await refreshToken();
            
            // Retry with new token
            options.headers.Authorization = `Bearer ${newToken}`;
            return fetch(url, options);
        }
        
        return response;
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
}
```

#### Token Storage Issues
```javascript
// ❌ Wrong - Token lost on page refresh
let authToken = null;

// ✅ Correct - Persistent storage
const getToken = () => localStorage.getItem('auth_token');
const setToken = (token) => localStorage.setItem('auth_token', token);
```

### 2. Login Failures

**Symptoms:**
- Cannot login with correct credentials
- Getting validation errors

**Debugging Steps:**

1. **Check Request Format:**
```bash
# Test with cURL
curl -X POST http://127.0.0.1:8000/api/admin/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"username":"admin","password":"password123"}' \
  -v
```

2. **Verify Credentials:**
```php
// Check in Laravel tinker
php artisan tinker
>>> $admin = App\Models\Admin::where('username', 'admin')->first();
>>> Hash::check('password123', $admin->password);
```

3. **Check Database Connection:**
```bash
php artisan migrate:status
php artisan db:show
```

## Request/Response Problems

### 1. 422 Validation Errors

**Symptoms:**
- Getting validation error responses
- Required field errors

**Common Issues:**

#### Missing Required Fields
```javascript
// ❌ Wrong - Missing required fields
const serviceData = {
    title_en: "Web Development"
    // Missing title_vi, description_vi, description_en
};

// ✅ Correct - All required fields
const serviceData = {
    title_vi: "Phát triển Web",
    title_en: "Web Development",
    description_vi: "Mô tả dịch vụ",
    description_en: "Service description",
    icon: "fas fa-code",
    color: "#3B82F6",
    bg_color: "#EFF6FF"
};
```

#### Invalid Data Types
```javascript
// ❌ Wrong - String instead of boolean
const projectData = {
    featured: "true" // Should be boolean
};

// ✅ Correct - Proper boolean
const projectData = {
    featured: true
};
```

### 2. 404 Not Found Errors

**Debugging Steps:**

1. **Check Route Registration:**
```bash
php artisan route:list | grep admin
```

2. **Verify Endpoint URL:**
```javascript
// ❌ Wrong - Missing /api prefix
const url = '/admin/hero';

// ✅ Correct - Full API path
const url = '/api/admin/hero';
```

3. **Check Resource Existence:**
```bash
# Test if resource exists
curl -X GET http://127.0.0.1:8000/api/admin/services/999 \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

### 3. 500 Internal Server Error

**Debugging Steps:**

1. **Check Laravel Logs:**
```bash
tail -f storage/logs/laravel.log
```

2. **Enable Debug Mode:**
```bash
# In .env file
APP_DEBUG=true
APP_ENV=local
```

3. **Check Database Connection:**
```bash
php artisan migrate:status
```

## File Upload Issues

### 1. File Size Errors

**Symptoms:**
- 413 Request Entity Too Large
- File size validation errors

**Solutions:**

1. **Check PHP Configuration:**
```bash
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time)"
```

2. **Update PHP Settings:**
```ini
; php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

3. **Client-Side Validation:**
```javascript
function validateFileSize(file, maxSize = 5 * 1024 * 1024) {
    if (file.size > maxSize) {
        throw new Error(`File size (${file.size}) exceeds maximum (${maxSize})`);
    }
}
```

### 2. File Type Errors

**Symptoms:**
- Invalid file type errors
- MIME type validation failures

**Solutions:**

1. **Check File Extension:**
```javascript
function validateFileType(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        throw new Error(`Invalid file type: ${file.type}`);
    }
}
```

2. **Server-Side Validation:**
```php
// Check Laravel validation rules
'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120'
```

## Performance Problems

### 1. Slow API Responses

**Debugging Steps:**

1. **Check Database Queries:**
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> // Make API request
>>> DB::getQueryLog();
```

2. **Monitor Response Times:**
```javascript
const startTime = performance.now();
const response = await fetch('/api/admin/services');
const endTime = performance.now();
console.log(`Request took ${endTime - startTime} milliseconds`);
```

3. **Check Server Resources:**
```bash
# Monitor server performance
top
htop
iostat
```

### 2. Memory Issues

**Solutions:**

1. **Optimize Database Queries:**
```php
// ❌ Wrong - N+1 query problem
$services = Service::all();
foreach ($services as $service) {
    echo $service->user->name;
}

// ✅ Correct - Eager loading
$services = Service::with('user')->get();
```

2. **Implement Pagination:**
```javascript
// Always use pagination for large datasets
const response = await api.getServices({ per_page: 15, page: 1 });
```

## Network and Connectivity

### 1. CORS Issues

**Symptoms:**
- "Access-Control-Allow-Origin" errors
- Preflight request failures

**Solutions:**

1. **Configure Laravel CORS:**
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'https://yourdomain.com'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

2. **Development Proxy:**
```javascript
// React - package.json
{
    "proxy": "http://127.0.0.1:8000"
}

// Vue - vue.config.js
module.exports = {
    devServer: {
        proxy: 'http://127.0.0.1:8000'
    }
};
```

### 2. SSL/HTTPS Issues

**Solutions:**

1. **Development Environment:**
```javascript
// Disable SSL verification for development only
const response = await fetch(url, {
    // Only for development!
    agent: new https.Agent({
        rejectUnauthorized: false
    })
});
```

2. **Production Environment:**
```bash
# Ensure proper SSL certificate
openssl s_client -connect api.yourdomain.com:443
```

## Debugging Tools

### 1. Browser Developer Tools

**Network Tab:**
- Check request/response headers
- Verify request payload
- Monitor response times
- Check status codes

**Console:**
```javascript
// Enable detailed logging
localStorage.setItem('debug', 'api:*');

// Log all API requests
const originalFetch = window.fetch;
window.fetch = function(...args) {
    console.log('API Request:', args);
    return originalFetch.apply(this, args)
        .then(response => {
            console.log('API Response:', response);
            return response;
        });
};
```

### 2. Laravel Debugging

**Telescope (Development):**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Debug Bar:**
```bash
composer require barryvdh/laravel-debugbar --dev
```

**Custom Logging:**
```php
// Add to controllers
Log::info('API Request', [
    'endpoint' => request()->path(),
    'method' => request()->method(),
    'user_id' => auth()->id(),
    'data' => request()->all()
]);
```

### 3. API Testing Tools

**Postman:**
- Import the provided collection
- Test individual endpoints
- Check authentication flow
- Validate responses

**cURL Commands:**
```bash
# Test authentication
curl -X POST http://127.0.0.1:8000/api/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}' \
  -v

# Test protected endpoint
curl -X GET http://127.0.0.1:8000/api/admin/hero \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json" \
  -v
```

### 4. Health Check Endpoint

Create a health check endpoint for monitoring:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0'),
        'environment' => app()->environment(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});
```

Test with:
```bash
curl http://127.0.0.1:8000/api/health
```

## Common Error Codes Reference

| Code | Meaning | Common Causes | Solutions |
|------|---------|---------------|-----------|
| 400 | Bad Request | Invalid JSON, malformed request | Check request format |
| 401 | Unauthorized | Missing/invalid token | Check authentication |
| 403 | Forbidden | Insufficient permissions | Verify user roles |
| 404 | Not Found | Invalid endpoint, missing resource | Check URL and resource ID |
| 422 | Validation Error | Invalid input data | Check required fields |
| 429 | Too Many Requests | Rate limit exceeded | Implement request throttling |
| 500 | Server Error | Application error | Check server logs |
| 503 | Service Unavailable | Maintenance mode, server overload | Check server status |

## Getting Help

1. **Check Documentation:** Review API documentation and guides
2. **Search Logs:** Look for error messages in Laravel logs
3. **Test Isolation:** Test individual endpoints with Postman
4. **Community Support:** Check Laravel and API community forums
5. **Debug Mode:** Enable detailed error reporting in development

Remember to disable debug mode and detailed error reporting in production environments for security reasons.

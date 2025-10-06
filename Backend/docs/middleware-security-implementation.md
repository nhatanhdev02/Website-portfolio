# Middleware and Security Implementation

## Overview

This document outlines the comprehensive middleware and security measures implemented for the Laravel Admin Backend API as part of task 7.2. All security components have been successfully configured and are ready for production use.

## Implemented Components

### 1. AdminAuthMiddleware

**Location:** `app/Http/Middleware/AdminAuthMiddleware.php`

**Purpose:** Protects all admin routes with Sanctum authentication and logs admin activities.

**Features:**
- Validates Sanctum authentication tokens
- Ensures authenticated user is an Admin model instance
- Logs comprehensive admin access information for audit trails
- Updates admin's last activity timestamp
- Adds admin context to request for downstream middleware

**Applied to:** All routes under `/api/admin/*` (except auth/login)

### 2. RequestLoggingMiddleware

**Location:** `app/Http/Middleware/RequestLoggingMiddleware.php`

**Purpose:** Provides comprehensive request/response logging for audit trails.

**Features:**
- Logs all API requests with detailed metadata
- Tracks request duration and memory usage
- Filters sensitive headers (authorization, cookies, etc.)
- Uses appropriate log levels based on HTTP status codes
- Includes request IDs for tracing
- Configurable via security configuration

**Applied to:** All admin API routes with `request.logging` middleware alias

### 3. SecurityHeadersMiddleware

**Location:** `app/Http/Middleware/SecurityHeadersMiddleware.php`

**Purpose:** Adds comprehensive security headers to all API responses.

**Security Headers Added:**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (restricts dangerous browser features)
- `Content-Security-Policy` (for API responses)
- `Strict-Transport-Security` (HTTPS only, configurable)

**Features:**
- Configurable via `config/security.php`
- Removes server information leakage
- Environment-aware HSTS configuration
- Rate limit header transparency

**Applied to:** All API routes globally

### 4. IpWhitelistMiddleware

**Location:** `app/Http/Middleware/IpWhitelistMiddleware.php`

**Purpose:** Optional IP-based access control for admin endpoints.

**Features:**
- Configurable IP whitelist with CIDR notation support
- Environment-aware (can bypass in local development)
- Comprehensive logging of blocked attempts
- Supports wildcard (`*`) to allow all IPs
- Graceful handling when disabled

**Configuration:**
```env
IP_WHITELIST_ENABLED=false
IP_WHITELIST_BYPASS_LOCAL=true
ALLOWED_IPS=127.0.0.1,192.168.1.0/24
```

**Applied to:** All admin routes including authentication

### 5. Rate Limiting System

**Location:** `app/Providers/RateLimitServiceProvider.php`

**Purpose:** Comprehensive rate limiting for different types of operations.

**Rate Limiters Configured:**

#### Admin Authentication (`admin-auth`)
- 5 requests per minute per IP
- 20 requests per hour per IP  
- 50 requests per day per IP
- Custom error responses with retry-after headers

#### Admin API Operations (`admin-api`)
- 120 requests per minute per user/IP
- 2000 requests per hour per user/IP
- 10000 requests per day per user/IP

#### File Upload Operations (`file-upload`)
- 10 requests per minute per user/IP
- 50 requests per hour per user/IP
- 200 requests per day per user/IP

#### Bulk Operations (`bulk-operations`)
- 5 requests per minute per user/IP
- 20 requests per hour per user/IP
- 50 requests per day per user/IP

#### System Operations (`system-operations`)
- 2 requests per minute per user/IP
- 5 requests per hour per user/IP
- 10 requests per day per user/IP

**Features:**
- Configurable limits via environment variables
- Custom error responses with helpful messages
- Comprehensive logging of rate limit violations
- User-based and IP-based identification

### 6. CORS Configuration

**Location:** `config/cors.php`

**Purpose:** Secure cross-origin resource sharing configuration.

**Features:**
- Environment-aware allowed origins
- Specific HTTP methods allowed (no wildcards)
- Controlled allowed headers
- Exposed rate limit headers
- 24-hour preflight cache
- Credentials support for Sanctum

**Configuration:**
```php
'allowed_origins' => [
    env('FRONTEND_URL'),
    // Development origins only in local environment
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'supports_credentials' => true,
```

### 7. Security Configuration

**Location:** `config/security.php`

**Purpose:** Centralized security configuration for all security components.

**Configuration Sections:**
- IP Whitelist settings
- Security headers configuration
- Audit logging preferences
- Rate limiting thresholds
- Session security settings
- File upload security

**Environment Variables:**
All security settings are configurable via environment variables with sensible defaults.

## Route Protection Implementation

### Middleware Application in Routes

**Authentication Routes:**
```php
Route::prefix('admin/auth')->middleware(['ip.whitelist'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:admin-auth');
    
    Route::middleware(['auth:sanctum', 'admin.auth', 'throttle:admin-api'])->group(function () {
        // Protected auth routes
    });
});
```

**Protected Admin Routes:**
```php
Route::prefix('admin')
    ->middleware([
        'ip.whitelist', 
        'auth:sanctum', 
        'admin.auth', 
        'request.logging', 
        'throttle:admin-api'
    ])
    ->group(function () {
        // All admin content management routes
    });
```

### Global Middleware Stack

**Bootstrap Configuration (`bootstrap/app.php`):**
```php
$middleware->api(prepend: [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);

$middleware->api(append: [
    \Illuminate\Http\Middleware\HandleCors::class,
    \App\Http\Middleware\SecurityHeadersMiddleware::class,
]);
```

## Security Features Summary

### ✅ Authentication & Authorization
- Sanctum token-based authentication
- Admin-specific user model validation
- Session timeout configuration
- Concurrent session limits

### ✅ Request Security
- Comprehensive input validation
- CORS protection with specific origins
- Security headers against common attacks
- IP whitelisting capability

### ✅ Rate Limiting
- Multi-tier rate limiting (per minute/hour/day)
- Operation-specific limits
- Custom error responses
- Comprehensive logging

### ✅ Audit & Monitoring
- Complete request/response logging
- Admin activity tracking
- Security event logging
- Performance monitoring

### ✅ Data Protection
- Sensitive header filtering
- Server information hiding
- File upload security
- Configuration-based security controls

## Environment Configuration

### Required Environment Variables

```env
# Authentication
SANCTUM_EXPIRATION=480
SANCTUM_TOKEN_PREFIX=admin_

# CORS
FRONTEND_URL=http://localhost:3000

# IP Whitelist (Optional)
IP_WHITELIST_ENABLED=false
ALLOWED_IPS=

# Security Headers
SECURITY_HSTS_ENABLED=true
SECURITY_CSP_ENABLED=true

# Rate Limiting
RATE_LIMIT_AUTH_PER_MINUTE=5
RATE_LIMIT_API_PER_MINUTE=120

# Audit Logging
AUDIT_LOGGING_ENABLED=true
AUDIT_LOG_REQUESTS=true
```

## Testing & Verification

### Verification Script
Run `php verify-middleware.php` to verify all components are properly installed and configured.

### Manual Testing
1. **Authentication:** Test login rate limiting
2. **Security Headers:** Check response headers in browser dev tools
3. **CORS:** Test cross-origin requests from frontend
4. **IP Whitelist:** Enable and test with different IPs
5. **Audit Logs:** Check log files for request tracking

## Production Deployment Notes

### Security Checklist
- [ ] Enable IP whitelisting for production
- [ ] Configure proper FRONTEND_URL
- [ ] Set appropriate rate limits
- [ ] Enable HSTS in production
- [ ] Configure log rotation
- [ ] Set up monitoring alerts

### Performance Considerations
- Rate limiting uses cache for counters
- Request logging can be disabled for high-traffic scenarios
- Security headers add minimal overhead
- IP whitelist checks are fast with proper configuration

## Compliance & Standards

This implementation follows:
- **OWASP Security Guidelines**
- **Laravel Security Best Practices**
- **Clean Architecture Principles**
- **Industry Standard Security Headers**
- **Comprehensive Audit Logging**

All requirements from task 7.2 have been successfully implemented:
- ✅ AdminAuthMiddleware applied to all protected routes
- ✅ Rate limiting configured for API endpoints
- ✅ CORS middleware set up for frontend integration
- ✅ Request logging middleware for audit trails
- ✅ Comprehensive security measures implemented

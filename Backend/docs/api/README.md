# Laravel Admin Backend API Documentation

Welcome to the Laravel Admin Backend API documentation. This RESTful API provides comprehensive content management capabilities for the "Nhật Anh Dev - Freelance Fullstack" portfolio admin dashboard.

## Table of Contents

- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
- [Error Handling](#error-handling)
- [File Uploads](#file-uploads)
- [Rate Limiting](#rate-limiting)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## 📚 Additional Documentation

- **[Authentication Guide](authentication-guide.md)** - Comprehensive authentication implementation guide
- **[File Upload Guide](file-upload-guide.md)** - Detailed file upload procedures and examples
- **[API Examples](api-examples.md)** - Practical code examples for all endpoints
- **[Integration Examples](integration-examples.md)** - Frontend integration examples (React, Vue, Angular)
- **[Troubleshooting Guide](troubleshooting-guide.md)** - Common issues and solutions
- **[Postman Collection](../postman/README.md)** - Interactive API testing collection

## Getting Started

### Base URL

- **Development**: `http://127.0.0.1:8000/api`
- **Production**: `https://api.nhatanh.dev/api`

### Content Type

All requests should include the following headers:
```http
Content-Type: application/json
Accept: application/json
```

### Response Format

All API responses follow a consistent JSON structure:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        // Validation errors (if applicable)
    }
}
```

## Authentication

The API uses Laravel Sanctum for authentication. All admin endpoints require a valid Bearer token.

### Login Process

1. **Login Request**
```http
POST /api/admin/auth/login
Content-Type: application/json

{
    "username": "admin",
    "password": "your_password"
}
```

2. **Login Response**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "1|abcdef123456789...",
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@example.com",
            "last_login_at": "2024-01-01T12:00:00Z"
        }
    }
}
```

3. **Using the Token**
```http
Authorization: Bearer 1|abcdef123456789...
```

### Token Management

- **Refresh Token**: `POST /api/admin/auth/refresh`
- **Get Current User**: `GET /api/admin/auth/me`
- **Logout**: `POST /api/admin/auth/logout`

## API Endpoints

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/auth/login` | Admin login |
| POST | `/api/admin/auth/logout` | Admin logout |
| POST | `/api/admin/auth/refresh` | Refresh token |
| GET | `/api/admin/auth/me` | Get current user |

### Content Management Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/hero` | Get hero content |
| PUT | `/api/admin/hero` | Update hero content |
| GET | `/api/admin/about` | Get about content |
| PUT | `/api/admin/about` | Update about content |
| POST | `/api/admin/about/image` | Upload profile image |

### Services Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/services` | List all services |
| POST | `/api/admin/services` | Create new service |
| GET | `/api/admin/services/{id}` | Get specific service |
| PUT | `/api/admin/services/{id}` | Update service |
| DELETE | `/api/admin/services/{id}` | Delete service |
| PUT | `/api/admin/services/reorder` | Reorder services |

### Projects Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/projects` | List all projects |
| POST | `/api/admin/projects` | Create new project |
| GET | `/api/admin/projects/{id}` | Get specific project |
| PUT | `/api/admin/projects/{id}` | Update project |
| DELETE | `/api/admin/projects/{id}` | Delete project |
| PUT | `/api/admin/projects/{id}/toggle-featured` | Toggle featured status |
| GET | `/api/admin/projects/featured/list` | Get featured projects |

### Blog Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/blog` | List all blog posts |
| POST | `/api/admin/blog` | Create new blog post |
| GET | `/api/admin/blog/{id}` | Get specific blog post |
| PUT | `/api/admin/blog/{id}` | Update blog post |
| DELETE | `/api/admin/blog/{id}` | Delete blog post |
| PUT | `/api/admin/blog/{id}/publish` | Publish blog post |
| PUT | `/api/admin/blog/{id}/unpublish` | Unpublish blog post |

### Contact Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/contacts/messages` | List contact messages |
| GET | `/api/admin/contacts/messages/{id}` | Get specific message |
| PUT | `/api/admin/contacts/messages/{id}/read` | Mark message as read |
| DELETE | `/api/admin/contacts/messages/{id}` | Delete message |
| GET | `/api/admin/contacts/info` | Get contact info |
| PUT | `/api/admin/contacts/info` | Update contact info |

### Settings Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/settings` | Get all settings |
| PUT | `/api/admin/settings` | Update settings |
| GET | `/api/admin/settings/{key}` | Get specific setting |
| PUT | `/api/admin/settings/{key}` | Update specific setting |

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 204 | No Content - Resource deleted successfully |
| 400 | Bad Request - Invalid request format |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

### Error Response Examples

**Validation Error (422):**
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title_vi": [
            "The Vietnamese title field is required."
        ],
        "email": [
            "The email must be a valid email address."
        ]
    }
}
```

**Authentication Error (401):**
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**Not Found Error (404):**
```json
{
    "success": false,
    "message": "Resource not found"
}
```

## File Uploads

### Supported File Types

- **Images**: JPG, JPEG, PNG, GIF, WebP
- **Maximum Size**: 5MB per file

### Upload Example

```http
POST /api/admin/about/image
Authorization: Bearer your_token_here
Content-Type: multipart/form-data

{
    "image": [binary file data]
}
```

### Upload Response

```json
{
    "success": true,
    "message": "Image uploaded successfully",
    "data": {
        "url": "https://example.com/storage/images/profile.jpg",
        "filename": "profile.jpg",
        "size": 1024000
    }
}
```

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Authentication endpoints**: 5 requests per minute
- **General API endpoints**: 60 requests per minute
- **File upload endpoints**: 10 requests per minute
- **Bulk operations**: 5 requests per minute

### Rate Limit Headers

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

## Examples

### Complete Authentication Flow

```javascript
// 1. Login
const loginResponse = await fetch('/api/admin/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        username: 'admin',
        password: 'password123'
    })
});

const loginData = await loginResponse.json();
const token = loginData.data.token;

// 2. Use token for authenticated requests
const heroResponse = await fetch('/api/admin/hero', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

const heroData = await heroResponse.json();
console.log(heroData);
```

### Creating a Service

```javascript
const serviceData = {
    title_vi: "Phát triển Web",
    title_en: "Web Development",
    description_vi: "Phát triển ứng dụng web hiện đại",
    description_en: "Modern web application development",
    icon: "fas fa-code",
    color: "#3B82F6",
    bg_color: "#EFF6FF"
};

const response = await fetch('/api/admin/services', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify(serviceData)
});

const result = await response.json();
console.log(result);
```

### Updating Hero Content

```javascript
const heroUpdate = {
    greeting_vi: "Xin chào, tôi là",
    greeting_en: "Hello, I'm",
    name: "Nhật Anh",
    title_vi: "Lập trình viên Fullstack",
    title_en: "Fullstack Developer",
    subtitle_vi: "Chuyên về phát triển web hiện đại",
    subtitle_en: "Specialized in modern web development",
    cta_text_vi: "Xem dự án",
    cta_text_en: "View Projects",
    cta_link: "#projects"
};

const response = await fetch('/api/admin/hero', {
    method: 'PUT',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify(heroUpdate)
});

const result = await response.json();
console.log(result);
```

### Filtering Projects

```javascript
// Get featured web projects
const response = await fetch('/api/admin/projects?category=web&featured=true&per_page=10', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

const projects = await response.json();
console.log(projects);
```

## Troubleshooting

### Common Issues

#### 1. Authentication Problems

**Issue**: Getting 401 Unauthorized
**Solutions**:
- Verify the token is included in the Authorization header
- Check if the token has expired (tokens expire after 24 hours)
- Ensure the token format is correct: `Bearer token_here`
- Try refreshing the token using `/api/admin/auth/refresh`

#### 2. Validation Errors

**Issue**: Getting 422 Unprocessable Entity
**Solutions**:
- Check the request body format matches the API requirements
- Ensure all required fields are included
- Verify data types (strings, integers, booleans)
- Check field length limits and format requirements

#### 3. File Upload Issues

**Issue**: File upload failing
**Solutions**:
- Verify file size is under 5MB
- Check file type is supported (JPG, PNG, GIF, WebP)
- Ensure Content-Type is `multipart/form-data`
- Check available disk space on server

#### 4. Rate Limiting

**Issue**: Getting 429 Too Many Requests
**Solutions**:
- Implement request throttling in your client
- Add delays between requests
- Check rate limit headers to understand limits
- Consider caching responses to reduce API calls

### Debug Tips

1. **Enable Logging**: Check Laravel logs for detailed error information
2. **Use Postman**: Test endpoints individually before integrating
3. **Check Network**: Verify network connectivity and DNS resolution
4. **Validate JSON**: Ensure request bodies are valid JSON format
5. **Monitor Performance**: Check response times and optimize queries

### Getting Help

1. **API Documentation**: Review this documentation thoroughly
2. **Postman Collection**: Use the provided collection for testing
3. **Laravel Logs**: Check `storage/logs/laravel.log` for errors
4. **Network Tools**: Use browser dev tools or Postman for debugging

## API Versioning

Currently using version 1.0.0. Future versions will be backward compatible or properly versioned in the URL path.

## Security Considerations

- Always use HTTPS in production
- Store tokens securely (not in localStorage for web apps)
- Implement proper CORS policies
- Validate all input data
- Use environment variables for sensitive configuration
- Regularly rotate authentication secrets
- Monitor for unusual API usage patterns

## Performance Tips

- Use pagination for large datasets
- Implement client-side caching where appropriate
- Compress request/response data when possible
- Use appropriate HTTP methods (GET for retrieval, POST for creation, etc.)
- Batch operations when available (bulk actions)
- Monitor API response times and optimize slow endpoints

## Quick Links

### 🚀 Getting Started
- [Authentication Guide](authentication-guide.md#authentication-flow) - Complete authentication implementation
- [API Examples](api-examples.md#authentication-examples) - Ready-to-use code examples
- [Postman Collection](../postman/README.md#quick-start) - Interactive API testing

### 🔧 Development
- [Integration Examples](integration-examples.md) - Frontend framework integration
- [File Upload Guide](file-upload-guide.md) - Secure file upload implementation
- [Error Handling](api-examples.md#error-handling-examples) - Comprehensive error management

### 🐛 Troubleshooting
- [Common Issues](troubleshooting-guide.md#common-issues) - Quick problem resolution
- [Debug Tools](troubleshooting-guide.md#debugging-tools) - Development debugging techniques
- [Performance Issues](troubleshooting-guide.md#performance-problems) - Optimization strategies

### 📊 Testing
- [Postman Collection](../postman/README.md) - Complete API testing suite
- [Automated Testing](../postman/README.md#automated-testing) - CI/CD integration
- [Health Checks](troubleshooting-guide.md#health-check-endpoint) - API monitoring

## Support and Community

- **Documentation Issues**: Report documentation problems or suggestions
- **API Bugs**: Submit bug reports with detailed reproduction steps
- **Feature Requests**: Propose new API features or improvements
- **Community**: Join discussions about API usage and best practices

---

*This API documentation is maintained alongside the Laravel Admin Backend codebase. For the most up-to-date information, always refer to the latest version of this documentation.*

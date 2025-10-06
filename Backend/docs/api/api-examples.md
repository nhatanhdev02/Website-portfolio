# API Usage Examples

This document provides comprehensive examples of using the Laravel Admin Backend API across different scenarios and use cases.

## Table of Contents

-   [Authentication Examples](#authentication-examples)
-   [Content Management Examples](#content-management-examples)
-   [File Upload Examples](#file-upload-examples)
-   [Error Handling Examples](#error-handling-examples)
-   [Advanced Usage Patterns](#advanced-usage-patterns)

## Authentication Examples

### Basic Login Flow

```javascript
// Login and store token
async function loginUser(username, password) {
    try {
        const response = await fetch("/api/admin/auth/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ username, password }),
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem("auth_token", data.data.token);
            localStorage.setItem("user_data", JSON.stringify(data.data.user));
            return data.data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error("Login failed:", error);
        throw error;
    }
}

// Usage
try {
    const userData = await loginUser("admin", "password123");
    console.log("Logged in as:", userData.user.username);
} catch (error) {
    alert("Login failed: " + error.message);
}
```

### Automatic Token Refresh

```javascript
class AuthManager {
    constructor() {
        this.token = localStorage.getItem("auth_token");
        this.refreshPromise = null;
    }

    async makeAuthenticatedRequest(url, options = {}) {
        // Ensure we have a valid token
        await this.ensureValidToken();

        const response = await fetch(url, {
            ...options,
            headers: {
                ...options.headers,
                Authorization: `Bearer ${this.token}`,
                Accept: "application/json",
            },
        });

        // Handle token expiration
        if (response.status === 401) {
            await this.refreshToken();
            // Retry with new token
            return fetch(url, {
                ...options,
                headers: {
                    ...options.headers,
                    Authorization: `Bearer ${this.token}`,
                    Accept: "application/json",
                },
            });
        }

        return response;
    }

    async refreshToken() {
        if (this.refreshPromise) {
            return this.refreshPromise;
        }

        this.refreshPromise = this.performTokenRefresh();
        try {
            await this.refreshPromise;
        } finally {
            this.refreshPromise = null;
        }
    }

    async performTokenRefresh() {
        const response = await fetch("/api/admin/auth/refresh", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${this.token}`,
                Accept: "application/json",
            },
        });

        const data = await response.json();

        if (data.success) {
            this.token = data.data.token;
            localStorage.setItem("auth_token", this.token);
        } else {
            this.logout();
            throw new Error("Token refresh failed");
        }
    }

    logout() {
        this.token = null;
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");
        window.location.href = "/login";
    }
}

// Usage
const authManager = new AuthManager();
const response = await authManager.makeAuthenticatedRequest("/api/admin/hero");
```

## Content Management Examples

### Hero Section Management

```javascript
class HeroManager {
    constructor(authManager) {
        this.auth = authManager;
    }

    async getHeroContent() {
        const response = await this.auth.makeAuthenticatedRequest(
            "/api/admin/hero"
        );
        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }

    async updateHeroContent(heroData) {
        const response = await this.auth.makeAuthenticatedRequest(
            "/api/admin/hero",
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(heroData),
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }
}

// Usage example
const heroManager = new HeroManager(authManager);

// Get current hero content
const currentHero = await heroManager.getHeroContent();
console.log("Current hero:", currentHero);

// Update hero content
const updatedHero = await heroManager.updateHeroContent({
    greeting_vi: "Xin chào, tôi là",
    greeting_en: "Hello, I'm",
    name: "Nhật Anh",
    title_vi: "Lập trình viên Fullstack",
    title_en: "Fullstack Developer",
    subtitle_vi: "Chuyên về phát triển web hiện đại",
    subtitle_en: "Specialized in modern web development",
    cta_text_vi: "Xem dự án",
    cta_text_en: "View Projects",
    cta_link: "#projects",
});
```

### Services CRUD Operations

```javascript
class ServicesManager {
    constructor(authManager) {
        this.auth = authManager;
    }

    async getAllServices(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = `/api/admin/services${
            queryString ? `?${queryString}` : ""
        }`;

        const response = await this.auth.makeAuthenticatedRequest(url);
        const data = await response.json();

        return data.success ? data.data : [];
    }

    async createService(serviceData) {
        const response = await this.auth.makeAuthenticatedRequest(
            "/api/admin/services",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(serviceData),
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }

    async updateService(id, serviceData) {
        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/services/${id}`,
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(serviceData),
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }

    async deleteService(id) {
        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/services/${id}`,
            {
                method: "DELETE",
            }
        );

        if (response.status === 204) {
            return true;
        }

        const data = await response.json();
        throw new Error(data.message);
    }

    async reorderServices(orderData) {
        const response = await this.auth.makeAuthenticatedRequest(
            "/api/admin/services/reorder",
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ order: orderData }),
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }
}

// Usage examples
const servicesManager = new ServicesManager(authManager);

// Get all services with pagination
const services = await servicesManager.getAllServices({
    per_page: 10,
    page: 1,
});

// Create a new service
const newService = await servicesManager.createService({
    title_vi: "Phát triển Web",
    title_en: "Web Development",
    description_vi: "Phát triển ứng dụng web hiện đại với Laravel và Vue.js",
    description_en:
        "Modern web application development with Laravel and Vue.js",
    icon: "fas fa-code",
    color: "#3B82F6",
    bg_color: "#EFF6FF",
});

// Update existing service
const updatedService = await servicesManager.updateService(1, {
    title_vi: "Phát triển Web (Cập nhật)",
    title_en: "Web Development (Updated)",
    color: "#10B981",
});

// Reorder services
await servicesManager.reorderServices([
    { id: 1, order: 1 },
    { id: 2, order: 2 },
    { id: 3, order: 3 },
]);

// Delete service
await servicesManager.deleteService(1);
```

### Projects Management with Filtering

```javascript
class ProjectsManager {
    constructor(authManager) {
        this.auth = authManager;
    }

    async getProjects(filters = {}) {
        const params = new URLSearchParams();

        // Add filters
        if (filters.category) params.append("category", filters.category);
        if (filters.featured !== undefined)
            params.append("featured", filters.featured);
        if (filters.per_page) params.append("per_page", filters.per_page);
        if (filters.page) params.append("page", filters.page);

        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/projects?${params.toString()}`
        );

        const data = await response.json();
        return data.success ? data.data : [];
    }

    async createProject(projectData) {
        const response = await this.auth.makeAuthenticatedRequest(
            "/api/admin/projects",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(projectData),
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }

    async toggleFeatured(projectId) {
        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/projects/${projectId}/toggle-featured`,
            {
                method: "PATCH",
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }
}

// Usage examples
const projectsManager = new ProjectsManager(authManager);

// Get featured web projects
const featuredWebProjects = await projectsManager.getProjects({
    category: "web",
    featured: true,
    per_page: 5,
});

// Get all mobile projects
const mobileProjects = await projectsManager.getProjects({
    category: "mobile",
    per_page: 10,
    page: 1,
});

// Create new project
const newProject = await projectsManager.createProject({
    title_vi: "Ứng dụng E-commerce",
    title_en: "E-commerce Application",
    description_vi: "Ứng dụng thương mại điện tử hiện đại",
    description_en: "Modern e-commerce application",
    image: "https://example.com/project-image.jpg",
    link: "https://demo.example.com",
    technologies: ["Laravel", "Vue.js", "MySQL", "Redis"],
    category: "web",
    featured: true,
});

// Toggle featured status
const updatedProject = await projectsManager.toggleFeatured(1);
console.log("Project featured status:", updatedProject.featured);
```

## File Upload Examples

### Profile Image Upload with Progress

```javascript
class FileUploadManager {
    constructor(authManager) {
        this.auth = authManager;
    }

    async uploadProfileImage(file, onProgress = null) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("image", file);

            const xhr = new XMLHttpRequest();

            // Track upload progress
            if (onProgress) {
                xhr.upload.addEventListener("progress", (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete);
                    }
                });
            }

            xhr.addEventListener("load", () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } else {
                    const error = JSON.parse(xhr.responseText);
                    reject(new Error(error.message || "Upload failed"));
                }
            });

            xhr.addEventListener("error", () => {
                reject(new Error("Network error during upload"));
            });

            xhr.open("POST", "/api/admin/about/image");
            xhr.setRequestHeader("Authorization", `Bearer ${this.auth.token}`);
            xhr.setRequestHeader("Accept", "application/json");

            xhr.send(formData);
        });
    }

    async uploadProjectImage(projectId, file, altText = "") {
        const formData = new FormData();
        formData.append("image", file);
        if (altText) {
            formData.append("alt_text", altText);
        }

        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/projects/${projectId}/image`,
            {
                method: "POST",
                body: formData,
            }
        );

        const data = await response.json();

        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    }
}

// Usage example
const uploadManager = new FileUploadManager(authManager);

// Upload profile image with progress tracking
const fileInput = document.getElementById("profile-image");
fileInput.addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const result = await uploadManager.uploadProfileImage(
            file,
            (progress) => {
                console.log(`Upload progress: ${Math.round(progress)}%`);
                // Update progress bar
                document.getElementById(
                    "progress-bar"
                ).style.width = `${progress}%`;
            }
        );

        console.log("Upload successful:", result.data.url);
        // Update UI with new image
        document.getElementById("profile-preview").src = result.data.url;
    } catch (error) {
        console.error("Upload failed:", error);
        alert(`Upload failed: ${error.message}`);
    }
});
```

## Error Handling Examples

### Comprehensive Error Handler

```javascript
class ApiErrorHandler {
    static handle(error, context = "") {
        console.error(`API Error ${context}:`, error);

        if (error.response) {
            // Server responded with error status
            const { status, data } = error.response;

            switch (status) {
                case 400:
                    return this.handleBadRequest(data);
                case 401:
                    return this.handleUnauthorized(data);
                case 403:
                    return this.handleForbidden(data);
                case 404:
                    return this.handleNotFound(data);
                case 422:
                    return this.handleValidationError(data);
                case 429:
                    return this.handleRateLimit(data);
                case 500:
                    return this.handleServerError(data);
                default:
                    return this.handleGenericError(data, status);
            }
        } else if (error.request) {
            // Network error
            return this.handleNetworkError();
        } else {
            // Other error
            return this.handleGenericError(error);
        }
    }

    static handleBadRequest(data) {
        return {
            type: "bad_request",
            message: "Invalid request format",
            details: data.message,
        };
    }

    static handleUnauthorized(data) {
        // Clear stored auth data
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");

        return {
            type: "unauthorized",
            message: "Authentication required",
            action: "redirect_to_login",
        };
    }

    static handleValidationError(data) {
        const errors = data.errors || {};
        const errorMessages = Object.keys(errors).map((field) => {
            return `${field}: ${errors[field].join(", ")}`;
        });

        return {
            type: "validation_error",
            message: "Please correct the following errors:",
            details: errorMessages,
            fields: errors,
        };
    }

    static handleRateLimit(data) {
        return {
            type: "rate_limit",
            message: "Too many requests. Please try again later.",
            retryAfter: data.retry_after || 60,
        };
    }

    static handleServerError(data) {
        return {
            type: "server_error",
            message: "Server error occurred. Please try again.",
            details: data.message,
        };
    }

    static handleNetworkError() {
        return {
            type: "network_error",
            message:
                "Network connection failed. Please check your internet connection.",
        };
    }

    static handleGenericError(error, status = null) {
        return {
            type: "generic_error",
            message: error.message || "An unexpected error occurred",
            status: status,
        };
    }
}

// Usage with try-catch
async function safeApiCall(apiFunction, context = "") {
    try {
        return await apiFunction();
    } catch (error) {
        const handledError = ApiErrorHandler.handle(error, context);

        // Display user-friendly error message
        if (handledError.type === "validation_error") {
            displayValidationErrors(handledError.fields);
        } else if (handledError.action === "redirect_to_login") {
            window.location.href = "/login";
        } else {
            displayErrorMessage(handledError.message);
        }

        throw handledError;
    }
}

// Example usage
await safeApiCall(
    () => servicesManager.createService(serviceData),
    "creating service"
);
```

## Advanced Usage Patterns

### Batch Operations

```javascript
class BatchOperations {
    constructor(authManager) {
        this.auth = authManager;
    }

    async batchCreateServices(servicesData) {
        const results = [];
        const errors = [];

        for (const [index, serviceData] of servicesData.entries()) {
            try {
                const result = await this.createService(serviceData);
                results.push({ index, result });
            } catch (error) {
                errors.push({ index, error: error.message, data: serviceData });
            }
        }

        return { results, errors };
    }

    async batchUpdateProjects(updates) {
        const promises = updates.map(async ({ id, data }) => {
            try {
                const result = await this.updateProject(id, data);
                return { success: true, id, result };
            } catch (error) {
                return { success: false, id, error: error.message };
            }
        });

        return Promise.all(promises);
    }

    async bulkDeleteServices(ids) {
        const results = await Promise.allSettled(
            ids.map((id) => this.deleteService(id))
        );

        const successful = [];
        const failed = [];

        results.forEach((result, index) => {
            if (result.status === "fulfilled") {
                successful.push(ids[index]);
            } else {
                failed.push({ id: ids[index], error: result.reason.message });
            }
        });

        return { successful, failed };
    }
}

// Usage
const batchOps = new BatchOperations(authManager);

// Batch create services
const servicesData = [
    { title_vi: "Dịch vụ 1", title_en: "Service 1" /* ... */ },
    { title_vi: "Dịch vụ 2", title_en: "Service 2" /* ... */ },
    { title_vi: "Dịch vụ 3", title_en: "Service 3" /* ... */ },
];

const batchResult = await batchOps.batchCreateServices(servicesData);
console.log(`Created ${batchResult.results.length} services`);
console.log(`Failed ${batchResult.errors.length} services`);
```

### Caching Layer

```javascript
class CachedApiManager {
    constructor(authManager) {
        this.auth = authManager;
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
    }

    async getCachedData(key, fetchFunction) {
        const cached = this.cache.get(key);

        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            return cached.data;
        }

        const data = await fetchFunction();
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
        });

        return data;
    }

    async getServices(params = {}) {
        const cacheKey = `services_${JSON.stringify(params)}`;
        return this.getCachedData(cacheKey, async () => {
            const response = await this.auth.makeAuthenticatedRequest(
                "/api/admin/services"
            );
            const data = await response.json();
            return data.success ? data.data : [];
        });
    }

    invalidateCache(pattern = null) {
        if (pattern) {
            for (const key of this.cache.keys()) {
                if (key.includes(pattern)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    async updateService(id, serviceData) {
        // Update service
        const response = await this.auth.makeAuthenticatedRequest(
            `/api/admin/services/${id}`,
            {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(serviceData),
            }
        );

        const data = await response.json();

        if (data.success) {
            // Invalidate related cache entries
            this.invalidateCache("services");
            return data.data;
        }

        throw new Error(data.message);
    }
}

// Usage
const cachedApi = new CachedApiManager(authManager);

// First call - fetches from API
const services1 = await cachedApi.getServices();

// Second call - returns cached data
const services2 = await cachedApi.getServices();

// Update service - invalidates cache
await cachedApi.updateService(1, updatedData);

// Next call - fetches fresh data
const services3 = await cachedApi.getServices();
```

These examples demonstrate comprehensive usage patterns for the Laravel Admin Backend API, including authentication, CRUD operations, file uploads, error handling, and advanced patterns like batch operations and caching.

# Frontend Integration Examples

This guide provides practical examples for integrating frontend applications with the Laravel Admin Backend API.

## Table of Contents

- [React Integration](#react-integration)
- [Vue.js Integration](#vuejs-integration)
- [Angular Integration](#angular-integration)
- [Vanilla JavaScript](#vanilla-javascript)
- [Mobile Integration](#mobile-integration)

## React Integration

### API Service Layer

```jsx
// services/api.js
class ApiService {
    constructor() {
        this.baseURL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';
        this.token = localStorage.getItem('auth_token');
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    clearToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
            ...options,
        };

        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }

        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }

        return data;
    }

    // Authentication
    async login(username, password) {
        const data = await this.request('/admin/auth/login', {
            method: 'POST',
            body: JSON.stringify({ username, password }),
        });
        this.setToken(data.data.token);
        return data;
    }

    async logout() {
        await this.request('/admin/auth/logout', { method: 'POST' });
        this.clearToken();
    }

    // Hero Section
    async getHero() {
        return this.request('/admin/hero');
    }

    async updateHero(heroData) {
        return this.request('/admin/hero', {
            method: 'PUT',
            body: JSON.stringify(heroData),
        });
    }

    // Services
    async getServices(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.request(`/admin/services${query ? `?${query}` : ''}`);
    }

    async createService(serviceData) {
        return this.request('/admin/services', {
            method: 'POST',
            body: JSON.stringify(serviceData),
        });
    }

    async updateService(id, serviceData) {
        return this.request(`/admin/services/${id}`, {
            method: 'PUT',
            body: JSON.stringify(serviceData),
        });
    }

    async deleteService(id) {
        return this.request(`/admin/services/${id}`, {
            method: 'DELETE',
        });
    }
}

export default new ApiService();
```

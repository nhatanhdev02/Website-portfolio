# File Upload Guide

This guide provides comprehensive information about handling file uploads with the Laravel Admin Backend API.

## Table of Contents

- [Overview](#overview)
- [Supported File Types](#supported-file-types)
- [Upload Endpoints](#upload-endpoints)
- [Security Considerations](#security-considerations)
- [Implementation Examples](#implementation-examples)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

The Laravel Admin Backend API provides secure file upload capabilities for various content types including profile images, project images, and blog thumbnails. All uploads are validated, optimized, and stored securely.

### Key Features

- 🔒 **Secure Validation** - File type, size, and content validation
- 🖼️ **Image Optimization** - Automatic resizing and compression
- 📁 **Organized Storage** - Structured file organization by type
- 🚀 **CDN Ready** - Compatible with cloud storage and CDN
- 🔍 **Virus Scanning** - Optional malware detection
- 📊 **Upload Tracking** - Comprehensive logging and monitoring

## Supported File Types

### Images

| Extension | MIME Type | Max Size | Use Case |
|-----------|-----------|----------|----------|
| `.jpg`, `.jpeg` | `image/jpeg` | 5MB | Profile images, project images |
| `.png` | `image/png` | 5MB | Logos, screenshots |
| `.gif` | `image/gif` | 5MB | Animated content |
| `.webp` | `image/webp` | 5MB | Optimized web images |
| `.svg` | `image/svg+xml` | 1MB | Icons, vector graphics |

### Documents (Future Support)

| Extension | MIME Type | Max Size | Use Case |
|-----------|-----------|----------|----------|
| `.pdf` | `application/pdf` | 10MB | Documentation, resumes |
| `.doc`, `.docx` | `application/msword` | 5MB | Documents |

## Upload Endpoints

### Profile Image Upload

**Endpoint:** `POST /api/admin/about/image`

**Purpose:** Upload profile image for the about section

**Request:**
```http
POST /api/admin/about/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "image": [binary file data]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile image uploaded successfully",
    "data": {
        "url": "https://example.com/storage/about/profile-abc123.jpg",
        "filename": "profile-abc123.jpg",
        "original_name": "my-photo.jpg",
        "size": 1024000,
        "mime_type": "image/jpeg",
        "dimensions": {
            "width": 800,
            "height": 600
        }
    }
}
```

### Project Image Upload

**Endpoint:** `POST /api/admin/projects/{id}/image`

**Purpose:** Upload images for portfolio projects

**Request:**
```http
POST /api/admin/projects/1/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "image": [binary file data],
    "alt_text": "Project screenshot showing the dashboard"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Project image uploaded successfully",
    "data": {
        "url": "https://example.com/storage/projects/project-1-def456.jpg",
        "filename": "project-1-def456.jpg",
        "original_name": "dashboard-screenshot.jpg",
        "size": 2048000,
        "mime_type": "image/jpeg",
        "alt_text": "Project screenshot showing the dashboard",
        "dimensions": {
            "width": 1200,
            "height": 800
        }
    }
}
```

### Blog Thumbnail Upload

**Endpoint:** `POST /api/admin/blog/{id}/thumbnail`

**Purpose:** Upload thumbnail images for blog posts

**Request:**
```http
POST /api/admin/blog/1/thumbnail
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "thumbnail": [binary file data]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Blog thumbnail uploaded successfully",
    "data": {
        "url": "https://example.com/storage/blog/thumbnails/blog-1-ghi789.jpg",
        "filename": "blog-1-ghi789.jpg",
        "original_name": "blog-cover.jpg",
        "size": 512000,
        "mime_type": "image/jpeg",
        "dimensions": {
            "width": 1200,
            "height": 630
        }
    }
}
```

## Security Considerations

### File Validation

The API implements multiple layers of validation:

1. **File Extension Check** - Validates against allowed extensions
2. **MIME Type Validation** - Verifies actual file content type
3. **File Size Limits** - Enforces maximum file sizes
4. **Image Dimension Limits** - Prevents extremely large images
5. **Content Scanning** - Checks for malicious content

### Security Headers

All file uploads require proper authentication and security headers:

```http
Authorization: Bearer {valid_token}
Content-Type: multipart/form-data
X-Requested-With: XMLHttpRequest
```

### File Storage Security

- Files are stored outside the web root when possible
- Unique filenames prevent direct access guessing
- Access URLs are generated with proper permissions
- Temporary files are cleaned up automatically

## Implementation Examples

### JavaScript/Fetch API

```javascript
class FileUploader {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }
    
    async uploadProfileImage(file, onProgress = null) {
        // Validate file before upload
        this.validateImageFile(file);
        
        const formData = new FormData();
        formData.append('image', file);
        
        const xhr = new XMLHttpRequest();
        
        return new Promise((resolve, reject) => {
            // Track upload progress
            if (onProgress) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete);
                    }
                });
            }
            
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } else {
                    const error = JSON.parse(xhr.responseText);
                    reject(new Error(error.message || 'Upload failed'));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('Network error during upload'));
            });
            
            xhr.open('POST', `${this.baseUrl}/api/admin/about/image`);
            xhr.setRequestHeader('Authorization', `Bearer ${this.token}`);
            xhr.setRequestHeader('Accept', 'application/json');
            
            xhr.send(formData);
        });
    }
    
    async uploadProjectImage(projectId, file, altText = '') {
        this.validateImageFile(file);
        
        const formData = new FormData();
        formData.append('image', file);
        if (altText) {
            formData.append('alt_text', altText);
        }
        
        const response = await fetch(`${this.baseUrl}/api/admin/projects/${projectId}/image`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Upload failed');
        }
        
        return data;
    }
    
    validateImageFile(file) {
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            throw new Error(`Invalid file type. Allowed types: ${allowedTypes.join(', ')}`);
        }
        
        // Check file size (5MB limit)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            throw new Error(`File size too large. Maximum size: ${maxSize / 1024 / 1024}MB`);
        }
        
        // Check if it's actually an image
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => reject(new Error('Invalid image file'));
            img.src = URL.createObjectURL(file);
        });
    }
}

// Usage example
const uploader = new FileUploader('http://127.0.0.1:8000', authToken);

// Upload with progress tracking
const fileInput = document.getElementById('profile-image');
const progressBar = document.getElementById('progress');

fileInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    try {
        const result = await uploader.uploadProfileImage(file, (progress) => {
            progressBar.style.width = `${progress}%`;
            progressBar.textContent = `${Math.round(progress)}%`;
        });
        
        console.log('Upload successful:', result);
        
        // Update UI with new image
        const img = document.getElementById('profile-preview');
        img.src = result.data.url;
        
    } catch (error) {
        console.error('Upload failed:', error);
        alert(`Upload failed: ${error.message}`);
    }
});
```

### React Implementation

```jsx
import React, { useState, useCallback } from 'react';
import { useDropzone } from 'react-dropzone';

const ImageUploader = ({ onUpload, maxSize = 5 * 1024 * 1024 }) => {
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(null);
    const [preview, setPreview] = useState(null);
    
    const onDrop = useCallback(async (acceptedFiles, rejectedFiles) => {
        // Handle rejected files
        if (rejectedFiles.length > 0) {
            const rejection = rejectedFiles[0];
            setError(`File rejected: ${rejection.errors[0].message}`);
            return;
        }
        
        const file = acceptedFiles[0];
        if (!file) return;
        
        // Create preview
        const previewUrl = URL.createObjectURL(file);
        setPreview(previewUrl);
        
        // Upload file
        setUploading(true);
        setError(null);
        setProgress(0);
        
        try {
            const result = await uploadFile(file, (progressValue) => {
                setProgress(progressValue);
            });
            
            onUpload(result);
            
        } catch (err) {
            setError(err.message);
            setPreview(null);
        } finally {
            setUploading(false);
            setProgress(0);
        }
    }, [onUpload]);
    
    const uploadFile = (file, onProgress) => {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('image', file);
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete);
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } else {
                    const error = JSON.parse(xhr.responseText);
                    reject(new Error(error.message || 'Upload failed'));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('Network error during upload'));
            });
            
            xhr.open('POST', '/api/admin/about/image');
            xhr.setRequestHeader('Authorization', `Bearer ${localStorage.getItem('auth_token')}`);
            xhr.setRequestHeader('Accept', 'application/json');
            
            xhr.send(formData);
        });
    };
    
    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: {
            'image/*': ['.jpeg', '.jpg', '.png', '.gif', '.webp']
        },
        maxSize,
        multiple: false
    });
    
    return (
        <div className="image-uploader">
            <div
                {...getRootProps()}
                className={`dropzone ${isDragActive ? 'active' : ''} ${uploading ? 'uploading' : ''}`}
            >
                <input {...getInputProps()} />
                
                {preview ? (
                    <div className="preview">
                        <img src={preview} alt="Preview" />
                        {uploading && (
                            <div className="upload-overlay">
                                <div className="progress-bar">
                                    <div 
                                        className="progress-fill" 
                                        style={{ width: `${progress}%` }}
                                    />
                                </div>
                                <span>{Math.round(progress)}%</span>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="upload-prompt">
                        {isDragActive ? (
                            <p>Drop the image here...</p>
                        ) : (
                            <div>
                                <p>Drag & drop an image here, or click to select</p>
                                <p className="file-info">
                                    Supported: JPG, PNG, GIF, WebP (max {maxSize / 1024 / 1024}MB)
                                </p>
                            </div>
                        )}
                    </div>
                )}
            </div>
            
            {error && (
                <div className="error-message">
                    {error}
                </div>
            )}
        </div>
    );
};

// Usage in a component
const ProfileSettings = () => {
    const [profileImage, setProfileImage] = useState(null);
    
    const handleImageUpload = (result) => {
        if (result.success) {
            setProfileImage(result.data.url);
            console.log('Image uploaded:', result.data);
        }
    };
    
    return (
        <div className="profile-settings">
            <h2>Profile Image</h2>
            
            {profileImage && (
                <div className="current-image">
                    <img src={profileImage} alt="Profile" />
                </div>
            )}
            
            <ImageUploader onUpload={handleImageUpload} />
        </div>
    );
};
```

### Vue.js Implementation

```vue
<template>
  <div class="file-uploader">
    <div
      class="drop-zone"
      :class="{ 'drag-over': isDragOver, 'uploading': uploading }"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
      @click="$refs.fileInput.click()"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleFileSelect"
        style="display: none"
      />
      
      <div v-if="preview" class="preview">
        <img :src="preview" alt="Preview" />
        <div v-if="uploading" class="upload-progress">
          <div class="progress-bar">
            <div 
              class="progress-fill" 
              :style="{ width: progress + '%' }"
            ></div>
          </div>
          <span>{{ Math.round(progress) }}%</span>
        </div>
      </div>
      
      <div v-else class="upload-prompt">
        <i class="fas fa-cloud-upload-alt"></i>
        <p v-if="isDragOver">Drop image here</p>
        <p v-else>Click or drag image to upload</p>
        <small>JPG, PNG, GIF, WebP (max 5MB)</small>
      </div>
    </div>
    
    <div v-if="error" class="error">
      {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'FileUploader',
  props: {
    endpoint: {
      type: String,
      required: true
    },
    maxSize: {
      type: Number,
      default: 5 * 1024 * 1024 // 5MB
    }
  },
  data() {
    return {
      isDragOver: false,
      uploading: false,
      progress: 0,
      error: null,
      preview: null
    };
  },
  methods: {
    handleDrop(e) {
      this.isDragOver = false;
      const files = Array.from(e.dataTransfer.files);
      if (files.length > 0) {
        this.uploadFile(files[0]);
      }
    },
    
    handleFileSelect(e) {
      const file = e.target.files[0];
      if (file) {
        this.uploadFile(file);
      }
    },
    
    async uploadFile(file) {
      // Validate file
      if (!this.validateFile(file)) {
        return;
      }
      
      // Create preview
      this.preview = URL.createObjectURL(file);
      
      // Upload
      this.uploading = true;
      this.error = null;
      this.progress = 0;
      
      try {
        const result = await this.performUpload(file);
        this.$emit('upload-success', result);
      } catch (error) {
        this.error = error.message;
        this.preview = null;
      } finally {
        this.uploading = false;
        this.progress = 0;
      }
    },
    
    validateFile(file) {
      // Check file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        this.error = `Invalid file type. Allowed: ${allowedTypes.join(', ')}`;
        return false;
      }
      
      // Check file size
      if (file.size > this.maxSize) {
        this.error = `File too large. Maximum size: ${this.maxSize / 1024 / 1024}MB`;
        return false;
      }
      
      return true;
    },
    
    performUpload(file) {
      return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('image', file);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            this.progress = (e.loaded / e.total) * 100;
          }
        });
        
        xhr.addEventListener('load', () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            resolve(response);
          } else {
            const error = JSON.parse(xhr.responseText);
            reject(new Error(error.message || 'Upload failed'));
          }
        });
        
        xhr.addEventListener('error', () => {
          reject(new Error('Network error during upload'));
        });
        
        xhr.open('POST', this.endpoint);
        xhr.setRequestHeader('Authorization', `Bearer ${this.$store.state.auth.token}`);
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.send(formData);
      });
    }
  }
};
</script>

<style scoped>
.drop-zone {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.drop-zone.drag-over {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.drop-zone.uploading {
  pointer-events: none;
  opacity: 0.7;
}

.preview img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 4px;
}

.upload-progress {
  margin-top: 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #007bff;
  transition: width 0.3s ease;
}

.error {
  color: #dc3545;
  margin-top: 0.5rem;
  font-size: 0.875rem;
}
</style>
```

### PHP Implementation

```php
<?php

class FileUploadClient
{
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }
    
    public function uploadProfileImage($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }
        
        // Validate file
        $this->validateImageFile($filePath);
        
        // Prepare upload
        $cFile = new CURLFile($filePath);
        $postData = ['image' => $cFile];
        
        return $this->performUpload('/api/admin/about/image', $postData);
    }
    
    public function uploadProjectImage($projectId, $filePath, $altText = '')
    {
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }
        
        $this->validateImageFile($filePath);
        
        $cFile = new CURLFile($filePath);
        $postData = ['image' => $cFile];
        
        if ($altText) {
            $postData['alt_text'] = $altText;
        }
        
        return $this->performUpload("/api/admin/projects/{$projectId}/image", $postData);
    }
    
    private function validateImageFile($filePath)
    {
        // Check file size
        $maxSize = 5 * 1024 * 1024; // 5MB
        if (filesize($filePath) > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size of 5MB');
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Check if it's a valid image
        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file');
        }
        
        return true;
    }
    
    private function performUpload($endpoint, $postData)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $data;
        } else {
            $message = $data['message'] ?? 'Upload failed';
            throw new Exception($message, $httpCode);
        }
    }
}

// Usage example
try {
    $uploader = new FileUploadClient('http://127.0.0.1:8000', $authToken);
    
    // Upload profile image
    $result = $uploader->uploadProfileImage('/path/to/profile.jpg');
    echo "Profile image uploaded: " . $result['data']['url'] . "\n";
    
    // Upload project image
    $result = $uploader->uploadProjectImage(1, '/path/to/project.jpg', 'Project screenshot');
    echo "Project image uploaded: " . $result['data']['url'] . "\n";
    
} catch (Exception $e) {
    echo "Upload error: " . $e->getMessage() . "\n";
}
```

## Error Handling

### Common Upload Errors

#### 1. File Too Large (413)

```json
{
    "success": false,
    "message": "File size exceeds maximum allowed size of 5MB",
    "errors": {
        "image": ["The image may not be greater than 5120 kilobytes."]
    }
}
```

#### 2. Invalid File Type (422)

```json
{
    "success": false,
    "message": "Invalid file type",
    "errors": {
        "image": ["The image must be a file of type: jpeg, jpg, png, gif, webp."]
    }
}
```

#### 3. Corrupted File (422)

```json
{
    "success": false,
    "message": "Invalid image file",
    "errors": {
        "image": ["The uploaded file is not a valid image."]
    }
}
```

#### 4. Storage Error (500)

```json
{
    "success": false,
    "message": "Failed to store uploaded file"
}
```

### Error Handling Best Practices

```javascript
async function handleFileUpload(file) {
    try {
        // Pre-upload validation
        validateFile(file);
        
        // Upload with retry logic
        const result = await uploadWithRetry(file, 3);
        
        return result;
        
    } catch (error) {
        // Handle specific error types
        if (error.status === 413) {
            throw new Error('File is too large. Please choose a smaller file.');
        } else if (error.status === 422) {
            const validationErrors = error.data.errors;
            if (validationErrors.image) {
                throw new Error(validationErrors.image[0]);
            }
        } else if (error.status === 500) {
            throw new Error('Server error. Please try again later.');
        } else if (error.name === 'NetworkError') {
            throw new Error('Network error. Please check your connection.');
        }
        
        // Generic error
        throw new Error('Upload failed. Please try again.');
    }
}

async function uploadWithRetry(file, maxRetries) {
    let lastError;
    
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await performUpload(file);
        } catch (error) {
            lastError = error;
            
            // Don't retry on client errors (4xx)
            if (error.status >= 400 && error.status < 500) {
                throw error;
            }
            
            // Wait before retry (exponential backoff)
            if (attempt < maxRetries) {
                const delay = Math.pow(2, attempt) * 1000;
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    }
    
    throw lastError;
}
```

## Best Practices

### 1. Client-Side Optimization

```javascript
// Resize image before upload
async function resizeImage(file, maxWidth = 1200, maxHeight = 800, quality = 0.8) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = () => {
            // Calculate new dimensions
            let { width, height } = img;
            
            if (width > maxWidth) {
                height = (height * maxWidth) / width;
                width = maxWidth;
            }
            
            if (height > maxHeight) {
                width = (width * maxHeight) / height;
                height = maxHeight;
            }
            
            // Resize
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);
            
            // Convert to blob
            canvas.toBlob(resolve, 'image/jpeg', quality);
        };
        
        img.src = URL.createObjectURL(file);
    });
}

// Usage
const originalFile = fileInput.files[0];
const resizedFile = await resizeImage(originalFile);
await uploadFile(resizedFile);
```

### 2. Progress Tracking

```javascript
class UploadManager {
    constructor() {
        this.activeUploads = new Map();
    }
    
    async upload(file, endpoint, onProgress) {
        const uploadId = this.generateUploadId();
        
        try {
            this.activeUploads.set(uploadId, { file, status: 'uploading' });
            
            const result = await this.performUpload(file, endpoint, (progress) => {
                this.activeUploads.set(uploadId, { 
                    file, 
                    status: 'uploading', 
                    progress 
                });
                onProgress?.(progress);
            });
            
            this.activeUploads.set(uploadId, { file, status: 'completed', result });
            return result;
            
        } catch (error) {
            this.activeUploads.set(uploadId, { file, status: 'failed', error });
            throw error;
        }
    }
    
    cancelUpload(uploadId) {
        const upload = this.activeUploads.get(uploadId);
        if (upload && upload.xhr) {
            upload.xhr.abort();
            this.activeUploads.delete(uploadId);
        }
    }
    
    getActiveUploads() {
        return Array.from(this.activeUploads.entries());
    }
}
```

### 3. Batch Upload

```javascript
async function uploadMultipleFiles(files, endpoint) {
    const results = [];
    const errors = [];
    
    // Limit concurrent uploads
    const concurrency = 3;
    const chunks = [];
    
    for (let i = 0; i < files.length; i += concurrency) {
        chunks.push(files.slice(i, i + concurrency));
    }
    
    for (const chunk of chunks) {
        const promises = chunk.map(async (file, index) => {
            try {
                const result = await uploadFile(file, endpoint);
                results.push({ file, result, index: i + index });
            } catch (error) {
                errors.push({ file, error, index: i + index });
            }
        });
        
        await Promise.all(promises);
    }
    
    return { results, errors };
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Upload Timeout

**Problem:** Large files timing out during upload

**Solutions:**
- Increase client timeout settings
- Implement chunked upload for large files
- Compress images before upload
- Use background upload with progress tracking

```javascript
// Increase timeout
const xhr = new XMLHttpRequest();
xhr.timeout = 300000; // 5 minutes

// Chunked upload example
async function uploadInChunks(file, chunkSize = 1024 * 1024) {
    const chunks = Math.ceil(file.size / chunkSize);
    
    for (let chunk = 0; chunk < chunks; chunk++) {
        const start = chunk * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunkData = file.slice(start, end);
        
        await uploadChunk(chunkData, chunk, chunks);
    }
}
```

#### 2. Memory Issues

**Problem:** Browser running out of memory with large images

**Solutions:**
- Resize images before upload
- Use streaming upload
- Process images in web workers

```javascript
// Web Worker for image processing
const worker = new Worker('image-processor.js');

worker.postMessage({ file, maxWidth: 1200, maxHeight: 800 });

worker.onmessage = (e) => {
    const processedFile = e.data;
    uploadFile(processedFile);
};
```

#### 3. CORS Issues

**Problem:** Cross-origin requests blocked

**Solutions:**
- Configure CORS headers on server
- Use proxy for development
- Ensure proper preflight handling

```php
// Laravel CORS configuration
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### 4. File Permissions

**Problem:** Server cannot write uploaded files

**Solutions:**
- Check directory permissions
- Verify storage configuration
- Ensure proper ownership

```bash
# Fix permissions
chmod 755 storage/app/public
chown -R www-data:www-data storage/
```

### Debug Tools

#### 1. Upload Monitoring

```javascript
class UploadDebugger {
    static logUpload(file, endpoint, result) {
        console.group(`Upload: ${file.name}`);
        console.log('File:', {
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: new Date(file.lastModified)
        });
        console.log('Endpoint:', endpoint);
        console.log('Result:', result);
        console.groupEnd();
    }
    
    static logError(file, error) {
        console.group(`Upload Error: ${file.name}`);
        console.error('File:', file);
        console.error('Error:', error);
        console.groupEnd();
    }
}
```

#### 2. Network Analysis

```javascript
// Monitor upload performance
const performanceObserver = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
        if (entry.name.includes('/api/admin/') && entry.name.includes('image')) {
            console.log('Upload Performance:', {
                url: entry.name,
                duration: entry.duration,
                transferSize: entry.transferSize,
                encodedBodySize: entry.encodedBodySize
            });
        }
    });
});

performanceObserver.observe({ entryTypes: ['resource'] });
```

## Conclusion

This file upload guide provides comprehensive information for implementing secure and efficient file uploads with the Laravel Admin Backend API. Key takeaways:

- Always validate files on both client and server side
- Implement proper error handling and retry logic
- Use progress tracking for better user experience
- Optimize images before upload when possible
- Follow security best practices for file handling
- Monitor upload performance and debug issues systematically

For additional help, refer to the main API documentation or use the provided Postman collection for testing upload endpoints.

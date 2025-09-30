/**
 * Image Storage Management Utilities
 * Provides centralized functions for managing uploaded images in localStorage
 */

export interface StoredImage {
  id: string;
  category: string;
  filename: string;
  data: string; // base64 data URL
  thumbnail?: string; // base64 thumbnail data URL
  metadata: {
    width: number;
    height: number;
    type: string;
    originalSize?: number;
    compressedSize?: number;
    compressionRatio?: number;
  };
  uploadDate: string;
}

export interface StorageStats {
  totalImages: number;
  totalSize: number;
  sizeByCategory: Record<string, number>;
  oldestImage?: StoredImage;
  newestImage?: StoredImage;
}

const STORAGE_KEY = 'admin_uploaded_images';
const MAX_STORAGE_SIZE = 4 * 1024 * 1024; // 4MB localStorage limit

/**
 * Get all stored images, optionally filtered by category
 */
export const getStoredImages = (category?: string): StoredImage[] => {
  try {
    const images = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    return category ? images.filter((img: StoredImage) => img.category === category) : images;
  } catch (error) {
    console.error('Error reading stored images:', error);
    return [];
  }
};

/**
 * Get a specific image by ID
 */
export const getImageById = (imageId: string): StoredImage | null => {
  try {
    const images = getStoredImages();
    return images.find(img => img.id === imageId) || null;
  } catch (error) {
    console.error('Error getting image by ID:', error);
    return null;
  }
};

/**
 * Store a new image in localStorage
 */
export const storeImage = (imageData: Omit<StoredImage, 'id' | 'uploadDate'>): string => {
  try {
    const imageId = `img_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const fullImageData: StoredImage = {
      ...imageData,
      id: imageId,
      uploadDate: new Date().toISOString()
    };
    
    const existingImages = getStoredImages();
    
    // Check storage size and clean up if necessary
    const currentStorageSize = JSON.stringify(existingImages).length;
    const newImageSize = JSON.stringify(fullImageData).length;
    
    if (currentStorageSize + newImageSize > MAX_STORAGE_SIZE) {
      // Remove oldest images until we have space
      const sortedImages = [...existingImages].sort((a, b) => 
        new Date(a.uploadDate).getTime() - new Date(b.uploadDate).getTime()
      );
      
      while (sortedImages.length > 0 && 
             JSON.stringify(sortedImages).length + newImageSize > MAX_STORAGE_SIZE) {
        const removed = sortedImages.shift();
        console.log('Removed old image to make space:', removed?.filename);
      }
      
      // Update existing images array
      existingImages.length = 0;
      existingImages.push(...sortedImages);
    }
    
    // Add new image
    existingImages.push(fullImageData);
    
    // Store back to localStorage
    localStorage.setItem(STORAGE_KEY, JSON.stringify(existingImages));
    
    return imageId;
  } catch (error) {
    console.error('Error storing image:', error);
    throw new Error('Failed to store image in local storage');
  }
};

/**
 * Delete an image by ID
 */
export const deleteImage = (imageId: string): boolean => {
  try {
    const images = getStoredImages();
    const filtered = images.filter(img => img.id !== imageId);
    
    if (filtered.length === images.length) {
      return false; // Image not found
    }
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    return true;
  } catch (error) {
    console.error('Error deleting image:', error);
    return false;
  }
};

/**
 * Delete all images in a specific category
 */
export const deleteImagesByCategory = (category: string): number => {
  try {
    const images = getStoredImages();
    const filtered = images.filter(img => img.category !== category);
    const deletedCount = images.length - filtered.length;
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    return deletedCount;
  } catch (error) {
    console.error('Error deleting images by category:', error);
    return 0;
  }
};

/**
 * Clean up old images (older than specified days)
 */
export const cleanupOldImages = (maxAgeDays: number = 30): number => {
  try {
    const images = getStoredImages();
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - maxAgeDays);
    
    const filtered = images.filter(img => 
      new Date(img.uploadDate) > cutoffDate
    );
    
    const removedCount = images.length - filtered.length;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    
    return removedCount;
  } catch (error) {
    console.error('Error cleaning up old images:', error);
    return 0;
  }
};

/**
 * Get storage usage statistics
 */
export const getStorageStats = (): StorageStats => {
  try {
    const images = getStoredImages();
    const storageData = localStorage.getItem(STORAGE_KEY) || '[]';
    const totalSize = new Blob([storageData]).size;
    
    const sizeByCategory: Record<string, number> = {};
    let oldestImage: StoredImage | undefined;
    let newestImage: StoredImage | undefined;
    
    images.forEach(img => {
      // Calculate size by category (approximate)
      const imgSize = JSON.stringify(img).length;
      sizeByCategory[img.category] = (sizeByCategory[img.category] || 0) + imgSize;
      
      // Find oldest and newest
      const uploadDate = new Date(img.uploadDate);
      if (!oldestImage || uploadDate < new Date(oldestImage.uploadDate)) {
        oldestImage = img;
      }
      if (!newestImage || uploadDate > new Date(newestImage.uploadDate)) {
        newestImage = img;
      }
    });
    
    return {
      totalImages: images.length,
      totalSize,
      sizeByCategory,
      oldestImage,
      newestImage
    };
  } catch (error) {
    console.error('Error getting storage stats:', error);
    return {
      totalImages: 0,
      totalSize: 0,
      sizeByCategory: {}
    };
  }
};

/**
 * Get storage usage as percentage
 */
export const getStorageUsage = (): { used: number; total: number; percentage: number } => {
  try {
    const storageData = localStorage.getItem(STORAGE_KEY) || '[]';
    const used = new Blob([storageData]).size;
    
    return {
      used,
      total: MAX_STORAGE_SIZE,
      percentage: (used / MAX_STORAGE_SIZE) * 100
    };
  } catch (error) {
    console.error('Error getting storage usage:', error);
    return { used: 0, total: MAX_STORAGE_SIZE, percentage: 0 };
  }
};

/**
 * Export all images as JSON (for backup)
 */
export const exportImages = (): string => {
  try {
    const images = getStoredImages();
    return JSON.stringify(images, null, 2);
  } catch (error) {
    console.error('Error exporting images:', error);
    throw new Error('Failed to export images');
  }
};

/**
 * Import images from JSON (for restore)
 */
export const importImages = (jsonData: string, overwrite: boolean = false): number => {
  try {
    const importedImages: StoredImage[] = JSON.parse(jsonData);
    
    if (!Array.isArray(importedImages)) {
      throw new Error('Invalid import data format');
    }
    
    let existingImages = overwrite ? [] : getStoredImages();
    let importedCount = 0;
    
    importedImages.forEach(img => {
      // Validate image structure
      if (img.id && img.category && img.filename && img.data && img.uploadDate) {
        // Check if image already exists (by ID)
        const exists = existingImages.some(existing => existing.id === img.id);
        
        if (!exists) {
          existingImages.push(img);
          importedCount++;
        }
      }
    });
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(existingImages));
    return importedCount;
  } catch (error) {
    console.error('Error importing images:', error);
    throw new Error('Failed to import images: ' + (error instanceof Error ? error.message : 'Unknown error'));
  }
};

/**
 * Validate image data integrity
 */
export const validateImageData = (): { valid: number; invalid: number; errors: string[] } => {
  try {
    const images = getStoredImages();
    const errors: string[] = [];
    let valid = 0;
    let invalid = 0;
    
    images.forEach((img, index) => {
      const issues: string[] = [];
      
      if (!img.id) issues.push('missing ID');
      if (!img.category) issues.push('missing category');
      if (!img.filename) issues.push('missing filename');
      if (!img.data || !img.data.startsWith('data:')) issues.push('invalid data URL');
      if (!img.uploadDate) issues.push('missing upload date');
      
      if (issues.length > 0) {
        invalid++;
        errors.push(`Image ${index}: ${issues.join(', ')}`);
      } else {
        valid++;
      }
    });
    
    return { valid, invalid, errors };
  } catch (error) {
    console.error('Error validating image data:', error);
    return { valid: 0, invalid: 0, errors: ['Failed to validate image data'] };
  }
};
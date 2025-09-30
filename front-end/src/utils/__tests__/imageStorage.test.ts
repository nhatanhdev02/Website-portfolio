/**
 * Tests for image storage utilities
 */

import { 
  storeImage, 
  getStoredImages, 
  deleteImage, 
  getStorageStats,
  getStorageUsage,
  cleanupOldImages,
  validateImageData,
  StoredImage 
} from '../imageStorage';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value;
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    }
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
});

describe('Image Storage Utilities', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe('storeImage', () => {
    it('should store an image and return an ID', () => {
      const imageData = {
        category: 'test',
        filename: 'test.jpg',
        data: 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD',
        metadata: {
          width: 100,
          height: 100,
          type: 'image/jpeg'
        }
      };

      const imageId = storeImage(imageData);
      
      expect(imageId).toBeTruthy();
      expect(imageId).toMatch(/^img_\d+_[a-z0-9]+$/);
      
      const storedImages = getStoredImages();
      expect(storedImages).toHaveLength(1);
      expect(storedImages[0].id).toBe(imageId);
      expect(storedImages[0].category).toBe('test');
    });

    it('should store multiple images', () => {
      const imageData1 = {
        category: 'test1',
        filename: 'test1.jpg',
        data: 'data:image/jpeg;base64,test1',
        metadata: { width: 100, height: 100, type: 'image/jpeg' }
      };

      const imageData2 = {
        category: 'test2',
        filename: 'test2.png',
        data: 'data:image/png;base64,test2',
        metadata: { width: 200, height: 200, type: 'image/png' }
      };

      const id1 = storeImage(imageData1);
      const id2 = storeImage(imageData2);

      expect(id1).not.toBe(id2);
      
      const storedImages = getStoredImages();
      expect(storedImages).toHaveLength(2);
    });
  });

  describe('getStoredImages', () => {
    beforeEach(() => {
      // Store test images
      storeImage({
        category: 'about',
        filename: 'profile.jpg',
        data: 'data:image/jpeg;base64,profile',
        metadata: { width: 100, height: 100, type: 'image/jpeg' }
      });

      storeImage({
        category: 'portfolio',
        filename: 'project.png',
        data: 'data:image/png;base64,project',
        metadata: { width: 200, height: 200, type: 'image/png' }
      });
    });

    it('should return all images when no category specified', () => {
      const images = getStoredImages();
      expect(images).toHaveLength(2);
    });

    it('should filter by category', () => {
      const aboutImages = getStoredImages('about');
      expect(aboutImages).toHaveLength(1);
      expect(aboutImages[0].category).toBe('about');

      const portfolioImages = getStoredImages('portfolio');
      expect(portfolioImages).toHaveLength(1);
      expect(portfolioImages[0].category).toBe('portfolio');
    });

    it('should return empty array for non-existent category', () => {
      const images = getStoredImages('nonexistent');
      expect(images).toHaveLength(0);
    });
  });

  describe('deleteImage', () => {
    it('should delete an existing image', () => {
      const imageId = storeImage({
        category: 'test',
        filename: 'test.jpg',
        data: 'data:image/jpeg;base64,test',
        metadata: { width: 100, height: 100, type: 'image/jpeg' }
      });

      expect(getStoredImages()).toHaveLength(1);
      
      const deleted = deleteImage(imageId);
      expect(deleted).toBe(true);
      expect(getStoredImages()).toHaveLength(0);
    });

    it('should return false for non-existent image', () => {
      const deleted = deleteImage('nonexistent');
      expect(deleted).toBe(false);
    });
  });

  describe('getStorageStats', () => {
    it('should return correct statistics', () => {
      storeImage({
        category: 'about',
        filename: 'profile.jpg',
        data: 'data:image/jpeg;base64,profile',
        metadata: { width: 100, height: 100, type: 'image/jpeg' }
      });

      storeImage({
        category: 'about',
        filename: 'banner.jpg',
        data: 'data:image/jpeg;base64,banner',
        metadata: { width: 200, height: 200, type: 'image/jpeg' }
      });

      const stats = getStorageStats();
      expect(stats.totalImages).toBe(2);
      expect(stats.totalSize).toBeGreaterThan(0);
      expect(stats.sizeByCategory.about).toBeGreaterThan(0);
      expect(stats.oldestImage).toBeTruthy();
      expect(stats.newestImage).toBeTruthy();
    });
  });

  describe('getStorageUsage', () => {
    it('should return usage information', () => {
      const usage = getStorageUsage();
      expect(usage.used).toBeGreaterThanOrEqual(0);
      expect(usage.total).toBe(4 * 1024 * 1024); // 4MB
      expect(usage.percentage).toBeGreaterThanOrEqual(0);
      expect(usage.percentage).toBeLessThanOrEqual(100);
    });
  });

  describe('validateImageData', () => {
    it('should validate correct image data', () => {
      storeImage({
        category: 'test',
        filename: 'test.jpg',
        data: 'data:image/jpeg;base64,test',
        metadata: { width: 100, height: 100, type: 'image/jpeg' }
      });

      const validation = validateImageData();
      expect(validation.valid).toBe(1);
      expect(validation.invalid).toBe(0);
      expect(validation.errors).toHaveLength(0);
    });

    it('should detect invalid image data', () => {
      // Manually insert invalid data
      const invalidImage = {
        id: 'test',
        category: '',
        filename: '',
        data: 'invalid',
        metadata: { width: 100, height: 100, type: 'image/jpeg' },
        uploadDate: new Date().toISOString()
      };

      localStorage.setItem('admin_uploaded_images', JSON.stringify([invalidImage]));

      const validation = validateImageData();
      expect(validation.valid).toBe(0);
      expect(validation.invalid).toBe(1);
      expect(validation.errors.length).toBeGreaterThan(0);
    });
  });
});
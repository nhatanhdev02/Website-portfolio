import { validateAboutContent } from '../aboutValidation';
import { AboutContent, STORAGE_KEYS } from '@/types/admin';

describe('About Content Data Management Integration', () => {
  const validAboutContent: AboutContent = {
    description: {
      vi: 'Với hơn 5 năm kinh nghiệm trong lập trình fullstack, tôi chuyên phát triển các ứng dụng web hiện đại sử dụng React, Node.js, và các công nghệ tiên tiến.',
      en: 'With over 5 years of experience in fullstack programming, I specialize in developing modern web applications using React, Node.js, and cutting-edge technologies.'
    },
    profileImage: 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
    experience: {
      vi: 'Đam mê tạo ra những sản phẩm chất lượng cao và trải nghiệm người dùng tuyệt vời.',
      en: 'Passionate about creating high-quality products and excellent user experiences.'
    }
  };

  beforeEach(() => {
    // Clear localStorage before each test
    localStorage.clear();
  });

  afterEach(() => {
    // Clean up localStorage after each test
    localStorage.clear();
  });

  describe('CRUD Operations', () => {
    it('should validate and store about content correctly', () => {
      // Test validation
      const validation = validateAboutContent(validAboutContent);
      expect(validation.isValid).toBe(true);
      expect(validation.errors).toEqual({});
      expect(validation.sanitizedData).toBeDefined();

      // Test storage
      const dataToStore = JSON.stringify(validAboutContent);
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, dataToStore);
      
      const storedData = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
      expect(storedData).toBe(dataToStore);
      
      const parsedData = JSON.parse(storedData!);
      expect(parsedData).toEqual(validAboutContent);
    });

    it('should handle validation errors correctly', () => {
      const invalidContent: AboutContent = {
        description: { vi: '', en: '' }, // Empty descriptions
        profileImage: 'invalid-url', // Invalid image URL
        experience: { vi: 'short', en: 'short' } // Too short experience
      };

      const validation = validateAboutContent(invalidContent);
      expect(validation.isValid).toBe(false);
      expect(validation.errors.description_vi).toBeDefined();
      expect(validation.errors.description_en).toBeDefined();
      expect(validation.errors.profileImage).toBeDefined();
    });

    it('should handle backup creation and restoration', () => {
      // Store initial content
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(validAboutContent));
      
      // Create a backup
      const backupKey = `${STORAGE_KEYS.ABOUT_CONTENT}_backup_${Date.now()}`;
      localStorage.setItem(backupKey, JSON.stringify(validAboutContent));
      
      // Verify backup exists
      const backupData = localStorage.getItem(backupKey);
      expect(backupData).toBeDefined();
      expect(JSON.parse(backupData!)).toEqual(validAboutContent);
      
      // Test backup retrieval
      const allKeys = Object.keys(localStorage);
      const backupKeys = allKeys.filter(key => key.startsWith(`${STORAGE_KEYS.ABOUT_CONTENT}_backup_`));
      expect(backupKeys.length).toBeGreaterThan(0);
    });

    it('should handle export and import functionality', () => {
      const exportData = {
        aboutContent: validAboutContent,
        exportDate: new Date().toISOString(),
        version: '1.0'
      };
      
      const exportJson = JSON.stringify(exportData, null, 2);
      
      // Test import parsing
      const importedData = JSON.parse(exportJson);
      expect(importedData.aboutContent).toEqual(validAboutContent);
      expect(importedData.version).toBe('1.0');
      
      // Validate imported content
      const validation = validateAboutContent(importedData.aboutContent);
      expect(validation.isValid).toBe(true);
    });
  });

  describe('Error Handling', () => {
    it('should handle storage errors gracefully', () => {
      // Mock localStorage to throw an error
      const originalSetItem = localStorage.setItem;
      localStorage.setItem = jest.fn(() => {
        throw new Error('Storage quota exceeded');
      });

      try {
        localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(validAboutContent));
        fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(Error);
        expect((error as Error).message).toContain('Storage quota exceeded');
      }

      // Restore original function
      localStorage.setItem = originalSetItem;
    });

    it('should handle corrupted data gracefully', () => {
      // Store corrupted JSON
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, 'invalid json data');
      
      try {
        const storedData = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
        JSON.parse(storedData!);
        fail('Should have thrown a parsing error');
      } catch (error) {
        expect(error).toBeInstanceOf(SyntaxError);
      }
    });

    it('should validate image upload requirements', () => {
      const contentWithInvalidImage: AboutContent = {
        ...validAboutContent,
        profileImage: 'data:image/bmp;base64,invalid' // Unsupported format
      };

      const validation = validateAboutContent(contentWithInvalidImage, {
        allowedImageTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
      });

      expect(validation.isValid).toBe(false);
      expect(validation.errors.profileImage).toContain('not supported');
    });
  });

  describe('Data Integrity', () => {
    it('should maintain data consistency across operations', () => {
      // Store initial data
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(validAboutContent));
      
      // Retrieve and validate
      const storedData = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
      const parsedData = JSON.parse(storedData!);
      
      const validation = validateAboutContent(parsedData);
      expect(validation.isValid).toBe(true);
      
      // Ensure data hasn't been corrupted
      expect(parsedData.description.vi).toBe(validAboutContent.description.vi);
      expect(parsedData.description.en).toBe(validAboutContent.description.en);
      expect(parsedData.experience.vi).toBe(validAboutContent.experience.vi);
      expect(parsedData.experience.en).toBe(validAboutContent.experience.en);
      expect(parsedData.profileImage).toBe(validAboutContent.profileImage);
    });

    it('should handle concurrent access scenarios', () => {
      // Simulate concurrent writes
      const content1 = { ...validAboutContent, description: { vi: 'Version 1', en: 'Version 1' } };
      const content2 = { ...validAboutContent, description: { vi: 'Version 2', en: 'Version 2' } };
      
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(content1));
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(content2));
      
      const finalData = JSON.parse(localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT)!);
      expect(finalData.description.vi).toBe('Version 2'); // Last write wins
    });
  });
});

// Mock localStorage for testing
const localStorageMock = (() => {
  let store: Record<string, string> = {};

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString();
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
    length: Object.keys(store).length,
    key: (index: number) => Object.keys(store)[index] || null
  };
})();

// Setup localStorage mock
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
});

// Mock jest functions if not available
if (typeof jest === 'undefined') {
  (global as any).jest = {
    fn: (implementation?: Function) => {
      const mockFn = implementation || (() => {});
      (mockFn as any).mockImplementation = (impl: Function) => {
        Object.setPrototypeOf(mockFn, impl);
        return mockFn;
      };
      return mockFn;
    }
  };
}

// Mock test functions if not available
if (typeof describe === 'undefined') {
  (global as any).describe = (name: string, fn: Function) => {
    console.log(`Test Suite: ${name}`);
    fn();
  };
}

if (typeof it === 'undefined') {
  (global as any).it = (name: string, fn: Function) => {
    try {
      fn();
      console.log(`✅ ${name}`);
    } catch (error) {
      console.log(`❌ ${name}: ${error}`);
    }
  };
}

if (typeof expect === 'undefined') {
  (global as any).expect = (actual: any) => ({
    toBe: (expected: any) => {
      if (actual !== expected) {
        throw new Error(`Expected ${actual} to be ${expected}`);
      }
    },
    toEqual: (expected: any) => {
      if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        throw new Error(`Expected ${JSON.stringify(actual)} to equal ${JSON.stringify(expected)}`);
      }
    },
    toBeDefined: () => {
      if (actual === undefined) {
        throw new Error(`Expected ${actual} to be defined`);
      }
    },
    toBeInstanceOf: (constructor: any) => {
      if (!(actual instanceof constructor)) {
        throw new Error(`Expected ${actual} to be instance of ${constructor.name}`);
      }
    },
    toContain: (expected: any) => {
      if (!actual.includes(expected)) {
        throw new Error(`Expected ${actual} to contain ${expected}`);
      }
    },
    toBeGreaterThan: (expected: number) => {
      if (actual <= expected) {
        throw new Error(`Expected ${actual} to be greater than ${expected}`);
      }
    },
    toBeLessThan: (expected: number) => {
      if (actual >= expected) {
        throw new Error(`Expected ${actual} to be less than ${expected}`);
      }
    },
    toHaveLength: (expected: number) => {
      if (actual.length !== expected) {
        throw new Error(`Expected ${actual} to have length ${expected}, got ${actual.length}`);
      }
    }
  });
}

if (typeof beforeEach === 'undefined') {
  (global as any).beforeEach = (fn: Function) => fn();
}

if (typeof afterEach === 'undefined') {
  (global as any).afterEach = (fn: Function) => fn();
}

if (typeof fail === 'undefined') {
  (global as any).fail = (message: string) => {
    throw new Error(message);
  };
}
import {
  validateAboutContent,
  sanitizeTextContent,
  validateImageUrl,
  getImageSizeFromDataUrl,
  hasSignificantChanges,
  validateContentCompleteness,
  analyzeContentQuality,
  detectPotentialIssues
} from '../aboutValidation';
import { AboutContent } from '@/types/admin';

describe('aboutValidation', () => {
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

  describe('validateAboutContent', () => {
    it('should validate valid content', () => {
      const result = validateAboutContent(validAboutContent);
      expect(result.isValid).toBe(true);
      expect(result.errors).toEqual({});
      expect(result.sanitizedData).toBeDefined();
    });

    it('should reject empty descriptions', () => {
      const invalidContent = {
        ...validAboutContent,
        description: { vi: '', en: '' }
      };
      const result = validateAboutContent(invalidContent);
      expect(result.isValid).toBe(false);
      expect(result.errors.description_vi).toBeDefined();
      expect(result.errors.description_en).toBeDefined();
    });

    it('should reject too long descriptions', () => {
      const longText = 'a'.repeat(501);
      const invalidContent = {
        ...validAboutContent,
        description: { vi: longText, en: longText }
      };
      const result = validateAboutContent(invalidContent);
      expect(result.isValid).toBe(false);
      expect(result.errors.description_vi).toContain('500 characters');
      expect(result.errors.description_en).toContain('500 characters');
    });

    it('should reject invalid image URLs', () => {
      const invalidContent = {
        ...validAboutContent,
        profileImage: 'invalid-url'
      };
      const result = validateAboutContent(invalidContent);
      expect(result.isValid).toBe(false);
      expect(result.errors.profileImage).toBeDefined();
    });

    it('should accept valid HTTP URLs', () => {
      const validContent = {
        ...validAboutContent,
        profileImage: 'https://example.com/image.jpg'
      };
      const result = validateAboutContent(validContent);
      expect(result.isValid).toBe(true);
    });
  });

  describe('sanitizeTextContent', () => {
    it('should trim whitespace', () => {
      const result = sanitizeTextContent('  hello world  ');
      expect(result).toBe('hello world');
    });

    it('should normalize multiple spaces', () => {
      const result = sanitizeTextContent('hello    world');
      expect(result).toBe('hello world');
    });

    it('should remove HTML tags', () => {
      const result = sanitizeTextContent('hello <script>alert("xss")</script> world');
      expect(result).toBe('hello alert("xss") world');
    });
  });

  describe('validateImageUrl', () => {
    it('should validate data URLs', () => {
      const dataUrl = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD//2Q==';
      expect(validateImageUrl(dataUrl)).toBe(true);
    });

    it('should validate HTTP URLs', () => {
      expect(validateImageUrl('https://example.com/image.jpg')).toBe(true);
    });

    it('should reject invalid URLs', () => {
      expect(validateImageUrl('invalid-url')).toBe(false);
      expect(validateImageUrl('')).toBe(false);
    });
  });

  describe('getImageSizeFromDataUrl', () => {
    it('should estimate size from data URL', () => {
      const dataUrl = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD//2Q==';
      const size = getImageSizeFromDataUrl(dataUrl);
      expect(size).toBeGreaterThan(0);
    });

    it('should return 0 for invalid data URL', () => {
      expect(getImageSizeFromDataUrl('invalid')).toBe(0);
      expect(getImageSizeFromDataUrl('')).toBe(0);
    });
  });

  describe('hasSignificantChanges', () => {
    it('should detect changes in description', () => {
      const modified = {
        ...validAboutContent,
        description: { vi: 'changed', en: 'changed' }
      };
      expect(hasSignificantChanges(modified, validAboutContent)).toBe(true);
    });

    it('should detect no changes', () => {
      expect(hasSignificantChanges(validAboutContent, validAboutContent)).toBe(false);
    });
  });

  describe('validateContentCompleteness', () => {
    it('should return 100% for complete content', () => {
      const result = validateContentCompleteness(validAboutContent);
      expect(result.completeness).toBe(100);
      expect(result.missingFields).toHaveLength(0);
    });

    it('should detect missing fields', () => {
      const incompleteContent = {
        ...validAboutContent,
        description: { vi: '', en: '' },
        profileImage: ''
      };
      const result = validateContentCompleteness(incompleteContent);
      expect(result.completeness).toBeLessThan(100);
      expect(result.missingFields.length).toBeGreaterThan(0);
      expect(result.suggestions.length).toBeGreaterThan(0);
    });
  });

  describe('analyzeContentQuality', () => {
    it('should analyze content quality', () => {
      const result = analyzeContentQuality(validAboutContent);
      expect(result.score).toBeGreaterThan(0);
      expect(result.score).toBeLessThanOrEqual(100);
      expect(Array.isArray(result.feedback)).toBe(true);
      expect(Array.isArray(result.recommendations)).toBe(true);
    });

    it('should provide recommendations for short content', () => {
      const shortContent = {
        ...validAboutContent,
        description: { vi: 'short', en: 'short' }
      };
      const result = analyzeContentQuality(shortContent);
      expect(result.recommendations.length).toBeGreaterThan(0);
    });
  });

  describe('detectPotentialIssues', () => {
    it('should detect no issues in valid content', () => {
      const result = detectPotentialIssues(validAboutContent);
      expect(result.issues).toHaveLength(0);
    });

    it('should detect placeholder text', () => {
      const placeholderContent = {
        ...validAboutContent,
        description: { vi: 'lorem ipsum dolor', en: 'lorem ipsum dolor' }
      };
      const result = detectPotentialIssues(placeholderContent);
      expect(result.issues.length).toBeGreaterThan(0);
      expect(result.issues[0].type).toBe('error');
    });

    it('should detect identical translations', () => {
      const identicalContent = {
        ...validAboutContent,
        description: { vi: 'same text', en: 'same text' }
      };
      const result = detectPotentialIssues(identicalContent);
      expect(result.issues.some(issue => issue.message.includes('identical'))).toBe(true);
    });

    it('should detect short content', () => {
      const shortContent = {
        ...validAboutContent,
        description: { vi: 'short', en: 'short' }
      };
      const result = detectPotentialIssues(shortContent);
      expect(result.issues.some(issue => issue.type === 'warning')).toBe(true);
    });
  });
});
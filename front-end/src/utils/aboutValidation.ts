import { AboutContent } from '@/types/admin';

export interface AboutValidationResult {
  isValid: boolean;
  errors: {
    description_vi?: string;
    description_en?: string;
    experience_vi?: string;
    experience_en?: string;
    profileImage?: string;
  };
  sanitizedData?: AboutContent;
}

export interface AboutValidationOptions {
  maxDescriptionLength?: number;
  maxExperienceLength?: number;
  requireImage?: boolean;
  allowedImageTypes?: string[];
  maxImageSize?: number; // in bytes
}

const DEFAULT_OPTIONS: Required<AboutValidationOptions> = {
  maxDescriptionLength: 500,
  maxExperienceLength: 300,
  requireImage: true,
  allowedImageTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  maxImageSize: 5 * 1024 * 1024 // 5MB
};

export const validateAboutContent = (
  content: AboutContent,
  options: AboutValidationOptions = {}
): AboutValidationResult => {
  const opts = { ...DEFAULT_OPTIONS, ...options };
  const errors: AboutValidationResult['errors'] = {};

  // Sanitize and validate Vietnamese description
  const sanitizedDescriptionVi = content.description.vi.trim();
  if (!sanitizedDescriptionVi) {
    errors.description_vi = 'Vietnamese description is required';
  } else if (sanitizedDescriptionVi.length > opts.maxDescriptionLength) {
    errors.description_vi = `Description must be ${opts.maxDescriptionLength} characters or less`;
  } else if (sanitizedDescriptionVi.length < 10) {
    errors.description_vi = 'Description must be at least 10 characters long';
  }

  // Sanitize and validate English description
  const sanitizedDescriptionEn = content.description.en.trim();
  if (!sanitizedDescriptionEn) {
    errors.description_en = 'English description is required';
  } else if (sanitizedDescriptionEn.length > opts.maxDescriptionLength) {
    errors.description_en = `Description must be ${opts.maxDescriptionLength} characters or less`;
  } else if (sanitizedDescriptionEn.length < 10) {
    errors.description_en = 'Description must be at least 10 characters long';
  }

  // Sanitize and validate Vietnamese experience
  const sanitizedExperienceVi = content.experience.vi.trim();
  if (!sanitizedExperienceVi) {
    errors.experience_vi = 'Vietnamese experience is required';
  } else if (sanitizedExperienceVi.length > opts.maxExperienceLength) {
    errors.experience_vi = `Experience must be ${opts.maxExperienceLength} characters or less`;
  } else if (sanitizedExperienceVi.length < 5) {
    errors.experience_vi = 'Experience must be at least 5 characters long';
  }

  // Sanitize and validate English experience
  const sanitizedExperienceEn = content.experience.en.trim();
  if (!sanitizedExperienceEn) {
    errors.experience_en = 'English experience is required';
  } else if (sanitizedExperienceEn.length > opts.maxExperienceLength) {
    errors.experience_en = `Experience must be ${opts.maxExperienceLength} characters or less`;
  } else if (sanitizedExperienceEn.length < 5) {
    errors.experience_en = 'Experience must be at least 5 characters long';
  }

  // Validate profile image
  const sanitizedProfileImage = content.profileImage.trim();
  if (opts.requireImage && !sanitizedProfileImage) {
    errors.profileImage = 'Profile image is required';
  } else if (sanitizedProfileImage) {
    // Check if it's a data URL or regular URL
    if (sanitizedProfileImage.startsWith('data:')) {
      // Validate data URL format
      const dataUrlRegex = /^data:([a-zA-Z0-9][a-zA-Z0-9\/+]*);base64,([a-zA-Z0-9+/]+=*)$/;
      if (!dataUrlRegex.test(sanitizedProfileImage)) {
        errors.profileImage = 'Invalid image format';
      } else {
        // Extract MIME type and validate
        const mimeMatch = sanitizedProfileImage.match(/^data:([^;]+);/);
        if (mimeMatch && !opts.allowedImageTypes.includes(mimeMatch[1])) {
          errors.profileImage = `Image type not supported. Use: ${opts.allowedImageTypes.join(', ')}`;
        }
        
        // Estimate file size from base64 (rough calculation)
        const base64Data = sanitizedProfileImage.split(',')[1];
        if (base64Data) {
          const estimatedSize = (base64Data.length * 3) / 4;
          if (estimatedSize > opts.maxImageSize) {
            const maxSizeMB = opts.maxImageSize / (1024 * 1024);
            errors.profileImage = `Image size too large. Maximum size is ${maxSizeMB}MB`;
          }
        }
      }
    } else if (!sanitizedProfileImage.startsWith('http') && !sanitizedProfileImage.startsWith('/')) {
      errors.profileImage = 'Image must be a valid URL or data URL';
    }
  }

  const isValid = Object.keys(errors).length === 0;

  const result: AboutValidationResult = {
    isValid,
    errors
  };

  if (isValid) {
    result.sanitizedData = {
      description: {
        vi: sanitizedDescriptionVi,
        en: sanitizedDescriptionEn
      },
      experience: {
        vi: sanitizedExperienceVi,
        en: sanitizedExperienceEn
      },
      profileImage: sanitizedProfileImage
    };
  }

  return result;
};

// Helper function to sanitize text content
export const sanitizeTextContent = (text: string): string => {
  return text
    .trim()
    .replace(/\s+/g, ' ') // Replace multiple spaces with single space
    .replace(/[\r\n]+/g, '\n') // Normalize line breaks
    .replace(/[<>]/g, ''); // Remove potential HTML tags
};

// Helper function to validate image URL
export const validateImageUrl = (url: string): boolean => {
  if (!url) return false;
  
  // Check for data URL
  if (url.startsWith('data:image/')) {
    return /^data:image\/(jpeg|jpg|png|gif|webp);base64,/.test(url);
  }
  
  // Check for regular URL
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
};

// Helper function to get image size from data URL
export const getImageSizeFromDataUrl = (dataUrl: string): number => {
  if (!dataUrl.startsWith('data:')) return 0;
  
  const base64Data = dataUrl.split(',')[1];
  if (!base64Data) return 0;
  
  // Rough estimation: base64 is ~33% larger than binary
  return (base64Data.length * 3) / 4;
};

// Helper function to check if content has meaningful changes
export const hasSignificantChanges = (
  current: AboutContent,
  previous: AboutContent
): boolean => {
  return (
    current.description.vi !== previous.description.vi ||
    current.description.en !== previous.description.en ||
    current.experience.vi !== previous.experience.vi ||
    current.experience.en !== previous.experience.en ||
    current.profileImage !== previous.profileImage
  );
};

// Helper function to validate content completeness
export const validateContentCompleteness = (content: AboutContent): {
  completeness: number;
  missingFields: string[];
  suggestions: string[];
} => {
  const missingFields: string[] = [];
  const suggestions: string[] = [];
  let completedFields = 0;
  const totalFields = 5; // description.vi, description.en, experience.vi, experience.en, profileImage

  // Check Vietnamese description
  if (!content.description.vi || content.description.vi.trim().length < 10) {
    missingFields.push('Vietnamese description');
    suggestions.push('Add a detailed Vietnamese description (at least 10 characters)');
  } else {
    completedFields++;
  }

  // Check English description
  if (!content.description.en || content.description.en.trim().length < 10) {
    missingFields.push('English description');
    suggestions.push('Add a detailed English description (at least 10 characters)');
  } else {
    completedFields++;
  }

  // Check Vietnamese experience
  if (!content.experience.vi || content.experience.vi.trim().length < 5) {
    missingFields.push('Vietnamese experience');
    suggestions.push('Add Vietnamese experience highlight (at least 5 characters)');
  } else {
    completedFields++;
  }

  // Check English experience
  if (!content.experience.en || content.experience.en.trim().length < 5) {
    missingFields.push('English experience');
    suggestions.push('Add English experience highlight (at least 5 characters)');
  } else {
    completedFields++;
  }

  // Check profile image
  if (!content.profileImage || content.profileImage.trim().length === 0) {
    missingFields.push('Profile image');
    suggestions.push('Upload a professional profile image');
  } else {
    completedFields++;
  }

  const completeness = Math.round((completedFields / totalFields) * 100);

  return {
    completeness,
    missingFields,
    suggestions
  };
};

// Helper function to analyze content quality
export const analyzeContentQuality = (content: AboutContent): {
  score: number;
  feedback: string[];
  recommendations: string[];
} => {
  const feedback: string[] = [];
  const recommendations: string[] = [];
  let score = 0;

  // Analyze description quality
  const viDescLength = content.description.vi.trim().length;
  const enDescLength = content.description.en.trim().length;

  if (viDescLength >= 100 && viDescLength <= 400) {
    score += 20;
    feedback.push('Vietnamese description length is optimal');
  } else if (viDescLength < 100) {
    recommendations.push('Consider expanding your Vietnamese description for better detail');
  } else {
    recommendations.push('Consider shortening your Vietnamese description for better readability');
  }

  if (enDescLength >= 100 && enDescLength <= 400) {
    score += 20;
    feedback.push('English description length is optimal');
  } else if (enDescLength < 100) {
    recommendations.push('Consider expanding your English description for better detail');
  } else {
    recommendations.push('Consider shortening your English description for better readability');
  }

  // Analyze experience quality
  const viExpLength = content.experience.vi.trim().length;
  const enExpLength = content.experience.en.trim().length;

  if (viExpLength >= 50 && viExpLength <= 200) {
    score += 15;
    feedback.push('Vietnamese experience highlight is well-sized');
  } else if (viExpLength < 50) {
    recommendations.push('Add more detail to your Vietnamese experience highlight');
  }

  if (enExpLength >= 50 && enExpLength <= 200) {
    score += 15;
    feedback.push('English experience highlight is well-sized');
  } else if (enExpLength < 50) {
    recommendations.push('Add more detail to your English experience highlight');
  }

  // Check for professional keywords
  const professionalKeywords = [
    'experience', 'kinh nghiệm', 'developer', 'lập trình', 'fullstack', 
    'technology', 'công nghệ', 'project', 'dự án', 'client', 'khách hàng'
  ];
  
  const allText = `${content.description.vi} ${content.description.en} ${content.experience.vi} ${content.experience.en}`.toLowerCase();
  const keywordCount = professionalKeywords.filter(keyword => allText.includes(keyword)).length;
  
  if (keywordCount >= 4) {
    score += 15;
    feedback.push('Good use of professional terminology');
  } else {
    recommendations.push('Consider adding more professional keywords to showcase your expertise');
  }

  // Check image presence
  if (content.profileImage && content.profileImage.trim().length > 0) {
    score += 15;
    feedback.push('Profile image is present');
  } else {
    recommendations.push('Add a professional profile image to complete your about section');
  }

  return {
    score: Math.min(score, 100),
    feedback,
    recommendations
  };
};

// Helper function to detect potential issues
export const detectPotentialIssues = (content: AboutContent): {
  issues: Array<{ type: 'warning' | 'error'; message: string }>;
} => {
  const issues: Array<{ type: 'warning' | 'error'; message: string }> = [];

  // Check for very short content
  if (content.description.vi.trim().length < 50) {
    issues.push({
      type: 'warning',
      message: 'Vietnamese description is quite short - consider adding more detail'
    });
  }

  if (content.description.en.trim().length < 50) {
    issues.push({
      type: 'warning',
      message: 'English description is quite short - consider adding more detail'
    });
  }

  // Check for identical content between languages
  if (content.description.vi.trim() === content.description.en.trim()) {
    issues.push({
      type: 'warning',
      message: 'Vietnamese and English descriptions are identical - consider proper translation'
    });
  }

  if (content.experience.vi.trim() === content.experience.en.trim()) {
    issues.push({
      type: 'warning',
      message: 'Vietnamese and English experience highlights are identical - consider proper translation'
    });
  }

  // Check for placeholder text
  const placeholders = ['lorem ipsum', 'placeholder', 'sample text', 'example'];
  const allText = `${content.description.vi} ${content.description.en} ${content.experience.vi} ${content.experience.en}`.toLowerCase();
  
  placeholders.forEach(placeholder => {
    if (allText.includes(placeholder)) {
      issues.push({
        type: 'error',
        message: `Placeholder text detected: "${placeholder}" - please replace with actual content`
      });
    }
  });

  // Check for broken image URLs
  if (content.profileImage && !content.profileImage.startsWith('data:') && !content.profileImage.startsWith('http') && !content.profileImage.startsWith('/')) {
    issues.push({
      type: 'error',
      message: 'Profile image URL appears to be invalid'
    });
  }

  return { issues };
};
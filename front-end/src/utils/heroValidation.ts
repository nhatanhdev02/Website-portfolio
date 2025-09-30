import { HeroContent } from '@/types/admin';

export interface HeroValidationResult {
  isValid: boolean;
  errors: Record<string, string>;
  sanitizedData?: HeroContent;
}

export interface HeroValidationLimits {
  greeting: number;
  name: number;
  title: number;
  subtitle: number;
  ctaText: number;
  ctaLink: number;
}

export const HERO_LIMITS: HeroValidationLimits = {
  greeting: 50,
  name: 30,
  title: 100,
  subtitle: 200,
  ctaText: 30,
  ctaLink: 200
};

/**
 * Sanitizes a string by trimming whitespace and removing potentially harmful characters
 */
export const sanitizeString = (input: string): string => {
  return input
    .trim()
    .replace(/[\u0000-\u001F\u007F-\u009F]/g, '') // Remove control characters
    .replace(/\s+/g, ' '); // Normalize whitespace
};

/**
 * Validates a URL or anchor link
 */
export const validateLink = (link: string): boolean => {
  const trimmedLink = link.trim();
  
  // Allow anchor links
  if (trimmedLink.startsWith('#')) {
    return trimmedLink.length > 1;
  }
  
  // Allow relative paths
  if (trimmedLink.startsWith('/')) {
    return trimmedLink.length > 1;
  }
  
  // Allow full URLs
  try {
    new URL(trimmedLink);
    return true;
  } catch {
    return false;
  }
};

/**
 * Validates and sanitizes Hero content
 */
export const validateHeroContent = (content: HeroContent): HeroValidationResult => {
  const errors: Record<string, string> = {};
  
  // Sanitize all string fields
  const sanitizedContent: HeroContent = {
    greeting: {
      vi: sanitizeString(content.greeting.vi),
      en: sanitizeString(content.greeting.en)
    },
    name: sanitizeString(content.name),
    title: {
      vi: sanitizeString(content.title.vi),
      en: sanitizeString(content.title.en)
    },
    subtitle: {
      vi: sanitizeString(content.subtitle.vi),
      en: sanitizeString(content.subtitle.en)
    },
    ctaText: {
      vi: sanitizeString(content.ctaText.vi),
      en: sanitizeString(content.ctaText.en)
    },
    ctaLink: sanitizeString(content.ctaLink)
  };

  // Validate Vietnamese greeting
  if (!sanitizedContent.greeting.vi) {
    errors.greeting_vi = 'Vietnamese greeting is required';
  } else if (sanitizedContent.greeting.vi.length > HERO_LIMITS.greeting) {
    errors.greeting_vi = `Vietnamese greeting must be ${HERO_LIMITS.greeting} characters or less`;
  }

  // Validate English greeting
  if (!sanitizedContent.greeting.en) {
    errors.greeting_en = 'English greeting is required';
  } else if (sanitizedContent.greeting.en.length > HERO_LIMITS.greeting) {
    errors.greeting_en = `English greeting must be ${HERO_LIMITS.greeting} characters or less`;
  }

  // Validate name
  if (!sanitizedContent.name) {
    errors.name = 'Name is required';
  } else if (sanitizedContent.name.length > HERO_LIMITS.name) {
    errors.name = `Name must be ${HERO_LIMITS.name} characters or less`;
  }

  // Validate Vietnamese title
  if (!sanitizedContent.title.vi) {
    errors.title_vi = 'Vietnamese title is required';
  } else if (sanitizedContent.title.vi.length > HERO_LIMITS.title) {
    errors.title_vi = `Vietnamese title must be ${HERO_LIMITS.title} characters or less`;
  }

  // Validate English title
  if (!sanitizedContent.title.en) {
    errors.title_en = 'English title is required';
  } else if (sanitizedContent.title.en.length > HERO_LIMITS.title) {
    errors.title_en = `English title must be ${HERO_LIMITS.title} characters or less`;
  }

  // Validate Vietnamese subtitle
  if (!sanitizedContent.subtitle.vi) {
    errors.subtitle_vi = 'Vietnamese subtitle is required';
  } else if (sanitizedContent.subtitle.vi.length > HERO_LIMITS.subtitle) {
    errors.subtitle_vi = `Vietnamese subtitle must be ${HERO_LIMITS.subtitle} characters or less`;
  }

  // Validate English subtitle
  if (!sanitizedContent.subtitle.en) {
    errors.subtitle_en = 'English subtitle is required';
  } else if (sanitizedContent.subtitle.en.length > HERO_LIMITS.subtitle) {
    errors.subtitle_en = `English subtitle must be ${HERO_LIMITS.subtitle} characters or less`;
  }

  // Validate Vietnamese CTA text
  if (!sanitizedContent.ctaText.vi) {
    errors.ctaText_vi = 'Vietnamese CTA text is required';
  } else if (sanitizedContent.ctaText.vi.length > HERO_LIMITS.ctaText) {
    errors.ctaText_vi = `Vietnamese CTA text must be ${HERO_LIMITS.ctaText} characters or less`;
  }

  // Validate English CTA text
  if (!sanitizedContent.ctaText.en) {
    errors.ctaText_en = 'English CTA text is required';
  } else if (sanitizedContent.ctaText.en.length > HERO_LIMITS.ctaText) {
    errors.ctaText_en = `English CTA text must be ${HERO_LIMITS.ctaText} characters or less`;
  }

  // Validate CTA link
  if (!sanitizedContent.ctaLink) {
    errors.ctaLink = 'CTA link is required';
  } else if (sanitizedContent.ctaLink.length > HERO_LIMITS.ctaLink) {
    errors.ctaLink = `CTA link must be ${HERO_LIMITS.ctaLink} characters or less`;
  } else if (!validateLink(sanitizedContent.ctaLink)) {
    errors.ctaLink = 'CTA link must be a valid URL, relative path, or anchor link';
  }

  const isValid = Object.keys(errors).length === 0;

  return {
    isValid,
    errors,
    sanitizedData: isValid ? sanitizedContent : undefined
  };
};

/**
 * Validates partial Hero content (for real-time validation)
 */
export const validatePartialHeroContent = (
  content: Partial<HeroContent>, 
  field: string
): { isValid: boolean; error?: string } => {
  const value = getNestedValue(content, field);
  
  if (typeof value !== 'string') {
    return { isValid: true }; // Skip validation for non-string values
  }

  const sanitizedValue = sanitizeString(value);

  // Field-specific validation
  switch (field) {
    case 'greeting.vi':
    case 'greeting.en':
      if (!sanitizedValue) {
        return { isValid: false, error: 'Greeting is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.greeting) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.greeting} characters or less` };
      }
      break;

    case 'name':
      if (!sanitizedValue) {
        return { isValid: false, error: 'Name is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.name) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.name} characters or less` };
      }
      break;

    case 'title.vi':
    case 'title.en':
      if (!sanitizedValue) {
        return { isValid: false, error: 'Title is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.title) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.title} characters or less` };
      }
      break;

    case 'subtitle.vi':
    case 'subtitle.en':
      if (!sanitizedValue) {
        return { isValid: false, error: 'Subtitle is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.subtitle) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.subtitle} characters or less` };
      }
      break;

    case 'ctaText.vi':
    case 'ctaText.en':
      if (!sanitizedValue) {
        return { isValid: false, error: 'CTA text is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.ctaText) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.ctaText} characters or less` };
      }
      break;

    case 'ctaLink':
      if (!sanitizedValue) {
        return { isValid: false, error: 'CTA link is required' };
      }
      if (sanitizedValue.length > HERO_LIMITS.ctaLink) {
        return { isValid: false, error: `Must be ${HERO_LIMITS.ctaLink} characters or less` };
      }
      if (!validateLink(sanitizedValue)) {
        return { isValid: false, error: 'Must be a valid URL, relative path, or anchor link' };
      }
      break;

    default:
      return { isValid: true };
  }

  return { isValid: true };
};

/**
 * Helper function to get nested object values
 */
function getNestedValue(obj: any, path: string): any {
  return path.split('.').reduce((current, key) => current?.[key], obj);
}
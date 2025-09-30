import { z } from 'zod';

// Internationalized error messages
export const validationMessages = {
  vi: {
    required: 'Trường này là bắt buộc',
    email: 'Email không hợp lệ',
    url: 'URL không hợp lệ',
    minLength: (min: number) => `Tối thiểu ${min} ký tự`,
    maxLength: (max: number) => `Tối đa ${max} ký tự`,
    minValue: (min: number) => `Giá trị tối thiểu là ${min}`,
    maxValue: (max: number) => `Giá trị tối đa là ${max}`,
    invalidFormat: 'Định dạng không hợp lệ',
    fileSize: (max: string) => `Kích thước file tối đa ${max}`,
    fileType: (types: string) => `Chỉ chấp nhận file: ${types}`,
    phoneNumber: 'Số điện thoại không hợp lệ',
    strongPassword: 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số',
    confirmPassword: 'Mật khẩu xác nhận không khớp',
    uniqueValue: 'Giá trị này đã tồn tại',
    invalidDate: 'Ngày không hợp lệ',
    futureDate: 'Ngày phải trong tương lai',
    pastDate: 'Ngày phải trong quá khứ'
  },
  en: {
    required: 'This field is required',
    email: 'Invalid email address',
    url: 'Invalid URL',
    minLength: (min: number) => `Minimum ${min} characters`,
    maxLength: (max: number) => `Maximum ${max} characters`,
    minValue: (min: number) => `Minimum value is ${min}`,
    maxValue: (max: number) => `Maximum value is ${max}`,
    invalidFormat: 'Invalid format',
    fileSize: (max: string) => `Maximum file size ${max}`,
    fileType: (types: string) => `Only accepts: ${types}`,
    phoneNumber: 'Invalid phone number',
    strongPassword: 'Password must be at least 8 characters with uppercase, lowercase and numbers',
    confirmPassword: 'Password confirmation does not match',
    uniqueValue: 'This value already exists',
    invalidDate: 'Invalid date',
    futureDate: 'Date must be in the future',
    pastDate: 'Date must be in the past'
  }
};

export type ValidationLanguage = keyof typeof validationMessages;

// Custom validation schemas with internationalization
export const createValidationSchemas = (language: ValidationLanguage = 'en') => {
  const messages = validationMessages[language];

  // Bilingual content schema
  const bilingualContentSchema = z.object({
    vi: z.string().min(1, messages.required),
    en: z.string().min(1, messages.required)
  });

  // Hero content validation
  const heroContentSchema = z.object({
    greeting: bilingualContentSchema,
    name: z.string().min(1, messages.required).max(100, messages.maxLength(100)),
    title: bilingualContentSchema,
    subtitle: bilingualContentSchema,
    ctaText: bilingualContentSchema,
    ctaLink: z.string().min(1, messages.required)
  });

  // About content validation
  const aboutContentSchema = z.object({
    description: z.object({
      vi: z.string().min(1, messages.required).max(1000, messages.maxLength(1000)),
      en: z.string().min(1, messages.required).max(1000, messages.maxLength(1000))
    }),
    profileImage: z.string().min(1, messages.required),
    experience: z.object({
      vi: z.string().min(1, messages.required).max(500, messages.maxLength(500)),
      en: z.string().min(1, messages.required).max(500, messages.maxLength(500))
    })
  });

  // Service validation
  const serviceSchema = z.object({
    title: bilingualContentSchema,
    description: z.object({
      vi: z.string().min(1, messages.required).max(300, messages.maxLength(300)),
      en: z.string().min(1, messages.required).max(300, messages.maxLength(300))
    }),
    icon: z.string().min(1, messages.required),
    color: z.string().regex(/^#[0-9A-F]{6}$/i, messages.invalidFormat),
    bgColor: z.string().regex(/^#[0-9A-F]{6}$/i, messages.invalidFormat),
    order: z.number().min(0, messages.minValue(0))
  });

  // Project validation
  const projectSchema = z.object({
    title: bilingualContentSchema,
    description: z.object({
      vi: z.string().min(1, messages.required).max(500, messages.maxLength(500)),
      en: z.string().min(1, messages.required).max(500, messages.maxLength(500))
    }),
    image: z.string().min(1, messages.required),
    images: z.array(z.string()).optional(),
    link: z.string().url(messages.url).optional().or(z.literal('')),
    technologies: z.array(z.string()).min(1, messages.required),
    category: z.string().min(1, messages.required),
    featured: z.boolean(),
    order: z.number().min(0, messages.minValue(0))
  });

  // Blog post validation
  const blogPostSchema = z.object({
    title: bilingualContentSchema,
    content: z.object({
      vi: z.string().min(1, messages.required).min(100, messages.minLength(100)),
      en: z.string().min(1, messages.required).min(100, messages.minLength(100))
    }),
    excerpt: z.object({
      vi: z.string().min(1, messages.required).max(200, messages.maxLength(200)),
      en: z.string().min(1, messages.required).max(200, messages.maxLength(200))
    }),
    thumbnail: z.string().min(1, messages.required),
    publishDate: z.date(),
    status: z.enum(['draft', 'published']),
    tags: z.array(z.string()).min(1, messages.required)
  });

  // Contact info validation
  const contactInfoSchema = z.object({
    email: z.string().email(messages.email),
    phone: z.string().regex(/^[\+]?[0-9\s\-\(\)]{10,}$/, messages.phoneNumber),
    github: z.string().url(messages.url),
    linkedin: z.string().url(messages.url)
  });

  // System settings validation
  const systemSettingsSchema = z.object({
    defaultLanguage: z.enum(['vi', 'en']),
    defaultTheme: z.enum(['light', 'dark']),
    colorPalette: z.array(z.string().regex(/^#[0-9A-F]{6}$/i, messages.invalidFormat))
      .min(4, messages.minValue(4))
      .max(16, messages.maxValue(16)),
    maintenanceMode: z.boolean()
  });

  // File validation
  const fileValidationSchema = z.object({
    file: z.instanceof(File),
    maxSize: z.number().optional(),
    allowedTypes: z.array(z.string()).optional()
  });

  return {
    heroContentSchema,
    aboutContentSchema,
    serviceSchema,
    projectSchema,
    blogPostSchema,
    contactInfoSchema,
    systemSettingsSchema,
    fileValidationSchema,
    bilingualContentSchema
  };
};

// Validation result interface
export interface ValidationResult<T = any> {
  isValid: boolean;
  data?: T;
  errors: Record<string, string>;
  fieldErrors: Record<string, string[]>;
}

// Generic validation function
export const validateData = <T>(
  data: unknown,
  schema: z.ZodSchema<T>,
  language: ValidationLanguage = 'en'
): ValidationResult<T> => {
  try {
    const validatedData = schema.parse(data);
    return {
      isValid: true,
      data: validatedData,
      errors: {},
      fieldErrors: {}
    };
  } catch (error) {
    if (error instanceof z.ZodError) {
      const errors: Record<string, string> = {};
      const fieldErrors: Record<string, string[]> = {};

      error.errors.forEach(err => {
        const path = err.path.join('.');
        const message = err.message;
        
        errors[path] = message;
        
        if (!fieldErrors[path]) {
          fieldErrors[path] = [];
        }
        fieldErrors[path].push(message);
      });

      return {
        isValid: false,
        errors,
        fieldErrors
      };
    }

    return {
      isValid: false,
      errors: { general: 'Validation failed' },
      fieldErrors: { general: ['Validation failed'] }
    };
  }
};

// File validation utility
export const validateFile = (
  file: File,
  options: {
    maxSize?: number; // in bytes
    allowedTypes?: string[];
    language?: ValidationLanguage;
  } = {}
): ValidationResult<File> => {
  const { maxSize, allowedTypes, language = 'en' } = options;
  const messages = validationMessages[language];
  const errors: Record<string, string> = {};

  // Check file size
  if (maxSize && file.size > maxSize) {
    const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(1);
    errors.size = messages.fileSize(`${maxSizeMB}MB`);
  }

  // Check file type
  if (allowedTypes && !allowedTypes.includes(file.type)) {
    errors.type = messages.fileType(allowedTypes.join(', '));
  }

  return {
    isValid: Object.keys(errors).length === 0,
    data: Object.keys(errors).length === 0 ? file : undefined,
    errors,
    fieldErrors: Object.keys(errors).reduce((acc, key) => {
      acc[key] = [errors[key]];
      return acc;
    }, {} as Record<string, string[]>)
  };
};

// Async validation for unique values
export const validateUniqueValue = async (
  value: string,
  existingValues: string[],
  language: ValidationLanguage = 'en'
): Promise<ValidationResult<string>> => {
  const messages = validationMessages[language];
  
  const isDuplicate = existingValues.includes(value);
  
  return {
    isValid: !isDuplicate,
    data: isDuplicate ? undefined : value,
    errors: isDuplicate ? { unique: messages.uniqueValue } : {},
    fieldErrors: isDuplicate ? { unique: [messages.uniqueValue] } : {}
  };
};

// Form field validation hook
export const useFieldValidation = (language: ValidationLanguage = 'en') => {
  const schemas = createValidationSchemas(language);

  const validateField = <T>(
    fieldName: string,
    value: unknown,
    schema: z.ZodSchema<T>
  ): ValidationResult<T> => {
    try {
      const validatedValue = schema.parse(value);
      return {
        isValid: true,
        data: validatedValue,
        errors: {},
        fieldErrors: {}
      };
    } catch (error) {
      if (error instanceof z.ZodError) {
        const firstError = error.errors[0];
        return {
          isValid: false,
          errors: { [fieldName]: firstError.message },
          fieldErrors: { [fieldName]: [firstError.message] }
        };
      }

      return {
        isValid: false,
        errors: { [fieldName]: 'Validation failed' },
        fieldErrors: { [fieldName]: ['Validation failed'] }
      };
    }
  };

  return {
    validateField,
    schemas
  };
};

// Data corruption detection
export interface DataIntegrityCheck {
  isValid: boolean;
  issues: string[];
  corruptedFields: string[];
  recoverable: boolean;
}

export const checkDataIntegrity = (
  data: unknown,
  expectedSchema: z.ZodSchema,
  language: ValidationLanguage = 'en'
): DataIntegrityCheck => {
  const messages = validationMessages[language];
  const issues: string[] = [];
  const corruptedFields: string[] = [];

  try {
    // Check if data exists
    if (!data) {
      issues.push('Data is null or undefined');
      return {
        isValid: false,
        issues,
        corruptedFields: ['root'],
        recoverable: false
      };
    }

    // Check if data is an object
    if (typeof data !== 'object') {
      issues.push('Data is not an object');
      return {
        isValid: false,
        issues,
        corruptedFields: ['root'],
        recoverable: false
      };
    }

    // Validate against schema
    const validation = validateData(data, expectedSchema, language);
    
    if (!validation.isValid) {
      Object.keys(validation.errors).forEach(field => {
        issues.push(`${field}: ${validation.errors[field]}`);
        corruptedFields.push(field);
      });
    }

    // Check for unexpected properties (potential corruption)
    const dataObj = data as Record<string, unknown>;
    const schemaKeys = Object.keys(expectedSchema.shape || {});
    const dataKeys = Object.keys(dataObj);
    
    const unexpectedKeys = dataKeys.filter(key => !schemaKeys.includes(key));
    if (unexpectedKeys.length > 0) {
      issues.push(`Unexpected properties found: ${unexpectedKeys.join(', ')}`);
      corruptedFields.push(...unexpectedKeys);
    }

    return {
      isValid: issues.length === 0,
      issues,
      corruptedFields,
      recoverable: corruptedFields.length < Object.keys(dataObj).length / 2 // Recoverable if less than half corrupted
    };

  } catch (error) {
    issues.push(`Data integrity check failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    return {
      isValid: false,
      issues,
      corruptedFields: ['root'],
      recoverable: false
    };
  }
};
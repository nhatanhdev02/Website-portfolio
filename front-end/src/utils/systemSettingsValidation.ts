import { SystemSettings } from '@/types/admin';

export interface SystemSettingsValidationResult {
  isValid: boolean;
  errors: Record<string, string>;
  sanitizedData?: SystemSettings;
}

export interface SystemSettingsValidationOptions {
  maxColors?: number;
  minColors?: number;
  allowedLanguages?: string[];
  allowedThemes?: string[];
  requireColorPalette?: boolean;
}

const DEFAULT_OPTIONS: Required<SystemSettingsValidationOptions> = {
  maxColors: 16,
  minColors: 4,
  allowedLanguages: ['vi', 'en'],
  allowedThemes: ['light', 'dark'],
  requireColorPalette: true
};

export const validateSystemSettings = (
  settings: Partial<SystemSettings>,
  options: SystemSettingsValidationOptions = {}
): SystemSettingsValidationResult => {
  const opts = { ...DEFAULT_OPTIONS, ...options };
  const errors: Record<string, string> = {};
  
  // Validate default language
  if (settings.defaultLanguage !== undefined) {
    if (typeof settings.defaultLanguage !== 'string') {
      errors.defaultLanguage = 'Default language must be a string';
    } else if (!opts.allowedLanguages.includes(settings.defaultLanguage)) {
      errors.defaultLanguage = `Default language must be one of: ${opts.allowedLanguages.join(', ')}`;
    }
  }
  
  // Validate default theme
  if (settings.defaultTheme !== undefined) {
    if (typeof settings.defaultTheme !== 'string') {
      errors.defaultTheme = 'Default theme must be a string';
    } else if (!opts.allowedThemes.includes(settings.defaultTheme)) {
      errors.defaultTheme = `Default theme must be one of: ${opts.allowedThemes.join(', ')}`;
    }
  }
  
  // Validate maintenance mode
  if (settings.maintenanceMode !== undefined) {
    if (typeof settings.maintenanceMode !== 'boolean') {
      errors.maintenanceMode = 'Maintenance mode must be a boolean';
    }
  }
  
  // Validate color palette
  if (settings.colorPalette !== undefined) {
    if (!Array.isArray(settings.colorPalette)) {
      errors.colorPalette = 'Color palette must be an array';
    } else {
      // Check array length
      if (settings.colorPalette.length < opts.minColors) {
        errors.colorPalette = `Color palette must contain at least ${opts.minColors} colors`;
      } else if (settings.colorPalette.length > opts.maxColors) {
        errors.colorPalette = `Color palette cannot contain more than ${opts.maxColors} colors`;
      } else {
        // Validate each color
        const invalidColors: string[] = [];
        const validColors: string[] = [];
        
        settings.colorPalette.forEach((color, index) => {
          if (typeof color !== 'string') {
            invalidColors.push(`Color at index ${index} is not a string`);
          } else if (!/^#[0-9A-F]{6}$/i.test(color)) {
            invalidColors.push(`Color "${color}" at index ${index} is not a valid hex color`);
          } else {
            validColors.push(color.toUpperCase());
          }
        });
        
        if (invalidColors.length > 0) {
          errors.colorPalette = invalidColors.join('; ');
        } else {
          // Check for duplicates
          const uniqueColors = [...new Set(validColors)];
          if (uniqueColors.length !== validColors.length) {
            errors.colorPalette = 'Color palette contains duplicate colors';
          }
        }
      }
    }
  } else if (opts.requireColorPalette) {
    errors.colorPalette = 'Color palette is required';
  }
  
  const isValid = Object.keys(errors).length === 0;
  
  // Create sanitized data if validation passed
  let sanitizedData: SystemSettings | undefined;
  if (isValid && settings.defaultLanguage && settings.defaultTheme && settings.colorPalette && settings.maintenanceMode !== undefined) {
    sanitizedData = {
      defaultLanguage: settings.defaultLanguage as 'vi' | 'en',
      defaultTheme: settings.defaultTheme as 'light' | 'dark',
      colorPalette: settings.colorPalette.map(color => color.toUpperCase()),
      maintenanceMode: settings.maintenanceMode
    };
  }
  
  return {
    isValid,
    errors,
    sanitizedData
  };
};

export const sanitizeSystemSettings = (settings: Partial<SystemSettings>): Partial<SystemSettings> => {
  const sanitized: Partial<SystemSettings> = {};
  
  // Sanitize language
  if (settings.defaultLanguage && typeof settings.defaultLanguage === 'string') {
    const lang = settings.defaultLanguage.toLowerCase().trim();
    if (['vi', 'en'].includes(lang)) {
      sanitized.defaultLanguage = lang as 'vi' | 'en';
    }
  }
  
  // Sanitize theme
  if (settings.defaultTheme && typeof settings.defaultTheme === 'string') {
    const theme = settings.defaultTheme.toLowerCase().trim();
    if (['light', 'dark'].includes(theme)) {
      sanitized.defaultTheme = theme as 'light' | 'dark';
    }
  }
  
  // Sanitize maintenance mode
  if (settings.maintenanceMode !== undefined) {
    sanitized.maintenanceMode = Boolean(settings.maintenanceMode);
  }
  
  // Sanitize color palette
  if (settings.colorPalette && Array.isArray(settings.colorPalette)) {
    const validColors = settings.colorPalette
      .filter(color => typeof color === 'string' && /^#[0-9A-F]{6}$/i.test(color))
      .map(color => color.toUpperCase());
    
    // Remove duplicates
    const uniqueColors = [...new Set(validColors)];
    
    if (uniqueColors.length >= 4) {
      sanitized.colorPalette = uniqueColors.slice(0, 16); // Max 16 colors
    }
  }
  
  return sanitized;
};

export const getSystemSettingsDefaults = (): SystemSettings => {
  return {
    defaultLanguage: 'vi',
    defaultTheme: 'dark',
    colorPalette: [
      '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
      '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'
    ],
    maintenanceMode: false
  };
};

export const validateColorPalette = (colors: string[]): { isValid: boolean; errors: string[] } => {
  const errors: string[] = [];
  
  if (!Array.isArray(colors)) {
    errors.push('Color palette must be an array');
    return { isValid: false, errors };
  }
  
  if (colors.length < 4) {
    errors.push('Color palette must contain at least 4 colors');
  }
  
  if (colors.length > 16) {
    errors.push('Color palette cannot contain more than 16 colors');
  }
  
  colors.forEach((color, index) => {
    if (typeof color !== 'string') {
      errors.push(`Color at index ${index} is not a string`);
    } else if (!/^#[0-9A-F]{6}$/i.test(color)) {
      errors.push(`Color "${color}" at index ${index} is not a valid hex color`);
    }
  });
  
  // Check for duplicates
  const uniqueColors = [...new Set(colors.map(c => c.toUpperCase()))];
  if (uniqueColors.length !== colors.length) {
    errors.push('Color palette contains duplicate colors');
  }
  
  return {
    isValid: errors.length === 0,
    errors
  };
};

export const createSystemSettingsBackup = (settings: SystemSettings): string => {
  const backup = {
    settings,
    timestamp: new Date().toISOString(),
    version: '1.0',
    type: 'system-settings-backup'
  };
  
  return JSON.stringify(backup, null, 2);
};

export const restoreSystemSettingsFromBackup = (backupData: string): SystemSettings | null => {
  try {
    const parsed = JSON.parse(backupData);
    
    if (parsed.type !== 'system-settings-backup') {
      throw new Error('Invalid backup type');
    }
    
    if (!parsed.settings) {
      throw new Error('No settings found in backup');
    }
    
    const validation = validateSystemSettings(parsed.settings);
    if (!validation.isValid) {
      throw new Error(`Invalid settings in backup: ${Object.values(validation.errors).join(', ')}`);
    }
    
    return validation.sanitizedData || null;
  } catch (error) {
    console.error('Failed to restore system settings from backup:', error);
    return null;
  }
};
import {
  HeroContent,
  AboutContent,
  Service,
  Project,
  BlogPost,
  ContactMessage,
  ContactInfo,
  SystemSettings,
  STORAGE_KEYS
} from '@/types/admin';
import { 
  showSuccessNotification, 
  showErrorNotification, 
  showInfoNotification 
} from '@/components/admin/ui/DataChangeNotification';

// Export data structure
export interface AdminDataExport {
  version: string;
  exportDate: string;
  exportId: string;
  data: {
    heroContent: HeroContent;
    aboutContent: AboutContent;
    services: Service[];
    projects: Project[];
    blogPosts: BlogPost[];
    contactMessages: ContactMessage[];
    contactInfo: ContactInfo;
    systemSettings: SystemSettings;
  };
  metadata: {
    totalItems: number;
    dataSize: number;
    checksum: string;
  };
}

// Import validation result
export interface ImportValidationResult {
  isValid: boolean;
  errors: string[];
  warnings: string[];
  data?: AdminDataExport['data'];
  metadata?: {
    version: string;
    exportDate: string;
    itemCounts: Record<string, number>;
  };
}

// Backup configuration
export interface BackupConfig {
  includeMessages: boolean;
  includeImages: boolean;
  compress: boolean;
  maxBackups: number;
}

const defaultBackupConfig: BackupConfig = {
  includeMessages: true,
  includeImages: true,
  compress: false,
  maxBackups: 10
};

// Generate checksum for data integrity
const generateChecksum = (data: string): string => {
  let hash = 0;
  for (let i = 0; i < data.length; i++) {
    const char = data.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32-bit integer
  }
  return Math.abs(hash).toString(16);
};

// Get current admin data from localStorage
const getCurrentAdminData = (): AdminDataExport['data'] => {
  const getData = <T>(key: string, defaultValue: T): T => {
    try {
      const stored = localStorage.getItem(key);
      return stored ? JSON.parse(stored) : defaultValue;
    } catch (error) {
      console.error(`Failed to load ${key}:`, error);
      return defaultValue;
    }
  };

  return {
    heroContent: getData(STORAGE_KEYS.HERO_CONTENT, {} as HeroContent),
    aboutContent: getData(STORAGE_KEYS.ABOUT_CONTENT, {} as AboutContent),
    services: getData(STORAGE_KEYS.SERVICES, [] as Service[]),
    projects: getData(STORAGE_KEYS.PROJECTS, [] as Project[]),
    blogPosts: getData(STORAGE_KEYS.BLOG_POSTS, [] as BlogPost[]),
    contactMessages: getData(STORAGE_KEYS.CONTACT_MESSAGES, [] as ContactMessage[]),
    contactInfo: getData(STORAGE_KEYS.CONTACT_INFO, {} as ContactInfo),
    systemSettings: getData(STORAGE_KEYS.SYSTEM_SETTINGS, {} as SystemSettings)
  };
};

// Export all admin data to JSON
export const exportAdminData = (config: Partial<BackupConfig> = {}): string => {
  try {
    const finalConfig = { ...defaultBackupConfig, ...config };
    const data = getCurrentAdminData();
    
    // Filter data based on config
    if (!finalConfig.includeMessages) {
      data.contactMessages = [];
    }
    
    // Create export object
    const exportData: AdminDataExport = {
      version: '1.0.0',
      exportDate: new Date().toISOString(),
      exportId: `export_${Date.now()}`,
      data,
      metadata: {
        totalItems: Object.values(data).reduce((count, item) => {
          if (Array.isArray(item)) return count + item.length;
          return count + (item ? 1 : 0);
        }, 0),
        dataSize: 0,
        checksum: ''
      }
    };
    
    // Convert to JSON string
    const jsonString = JSON.stringify(exportData, null, 2);
    
    // Update metadata
    exportData.metadata.dataSize = new Blob([jsonString]).size;
    exportData.metadata.checksum = generateChecksum(jsonString);
    
    // Re-stringify with updated metadata
    const finalJsonString = JSON.stringify(exportData, null, 2);
    
    showSuccessNotification(
      'Data Exported',
      `Successfully exported ${exportData.metadata.totalItems} items (${(exportData.metadata.dataSize / 1024).toFixed(1)} KB)`
    );
    
    return finalJsonString;
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Export Failed', `Failed to export data: ${errorMessage}`);
    throw error;
  }
};

// Download exported data as file
export const downloadExportedData = (config: Partial<BackupConfig> = {}): void => {
  try {
    const jsonString = exportAdminData(config);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `admin-data-export-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
    
    showSuccessNotification(
      'Download Started',
      'Admin data export file download has started.'
    );
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Download Failed', `Failed to download export: ${errorMessage}`);
  }
};

// Validate imported data
export const validateImportData = (jsonString: string): ImportValidationResult => {
  const errors: string[] = [];
  const warnings: string[] = [];
  
  try {
    // Parse JSON
    let importData: AdminDataExport;
    try {
      importData = JSON.parse(jsonString);
    } catch (parseError) {
      errors.push('Invalid JSON format');
      return { isValid: false, errors, warnings };
    }
    
    // Check structure
    if (!importData.version) {
      errors.push('Missing version information');
    }
    
    if (!importData.data) {
      errors.push('Missing data section');
      return { isValid: false, errors, warnings };
    }
    
    // Check version compatibility
    const supportedVersions = ['1.0.0'];
    if (!supportedVersions.includes(importData.version)) {
      warnings.push(`Version ${importData.version} may not be fully compatible`);
    }
    
    // Validate data structure
    const requiredFields = [
      'heroContent',
      'aboutContent',
      'services',
      'projects',
      'blogPosts',
      'contactMessages',
      'contactInfo',
      'systemSettings'
    ];
    
    for (const field of requiredFields) {
      if (!(field in importData.data)) {
        errors.push(`Missing required field: ${field}`);
      }
    }
    
    // Validate array fields
    const arrayFields = ['services', 'projects', 'blogPosts', 'contactMessages'];
    for (const field of arrayFields) {
      if (importData.data[field as keyof typeof importData.data] && 
          !Array.isArray(importData.data[field as keyof typeof importData.data])) {
        errors.push(`Field ${field} must be an array`);
      }
    }
    
    // Check data integrity with checksum
    if (importData.metadata?.checksum) {
      const dataString = JSON.stringify(importData.data);
      const calculatedChecksum = generateChecksum(dataString);
      if (calculatedChecksum !== importData.metadata.checksum) {
        warnings.push('Data integrity check failed - data may be corrupted');
      }
    }
    
    // Count items
    const itemCounts: Record<string, number> = {};
    Object.entries(importData.data).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        itemCounts[key] = value.length;
      } else if (value && typeof value === 'object') {
        itemCounts[key] = 1;
      } else {
        itemCounts[key] = 0;
      }
    });
    
    return {
      isValid: errors.length === 0,
      errors,
      warnings,
      data: importData.data,
      metadata: {
        version: importData.version,
        exportDate: importData.exportDate,
        itemCounts
      }
    };
    
  } catch (error) {
    errors.push(`Validation failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    return { isValid: false, errors, warnings };
  }
};

// Import admin data from JSON
export const importAdminData = (jsonString: string, options: {
  overwrite?: boolean;
  createBackup?: boolean;
} = {}): boolean => {
  try {
    const { overwrite = false, createBackup = true } = options;
    
    // Validate import data
    const validation = validateImportData(jsonString);
    
    if (!validation.isValid) {
      showErrorNotification(
        'Import Failed',
        `Import validation failed: ${validation.errors.join(', ')}`
      );
      return false;
    }
    
    if (validation.warnings.length > 0) {
      showInfoNotification(
        'Import Warnings',
        `Import has warnings: ${validation.warnings.join(', ')}`
      );
    }
    
    // Create backup before import
    if (createBackup) {
      try {
        createAutomaticBackup('pre_import');
      } catch (backupError) {
        console.warn('Failed to create pre-import backup:', backupError);
      }
    }
    
    // Import data
    const data = validation.data!;
    
    if (overwrite) {
      // Overwrite all data
      Object.entries(STORAGE_KEYS).forEach(([key, storageKey]) => {
        const dataKey = key.toLowerCase().replace('_', '') as keyof typeof data;
        if (data[dataKey]) {
          localStorage.setItem(storageKey, JSON.stringify(data[dataKey]));
        }
      });
    } else {
      // Merge with existing data
      Object.entries(data).forEach(([key, value]) => {
        const storageKey = STORAGE_KEYS[key.toUpperCase() as keyof typeof STORAGE_KEYS];
        if (storageKey && value) {
          // For arrays, merge with existing data
          if (Array.isArray(value)) {
            try {
              const existing = JSON.parse(localStorage.getItem(storageKey) || '[]');
              const merged = [...existing, ...value];
              localStorage.setItem(storageKey, JSON.stringify(merged));
            } catch {
              localStorage.setItem(storageKey, JSON.stringify(value));
            }
          } else {
            // For objects, merge properties
            try {
              const existing = JSON.parse(localStorage.getItem(storageKey) || '{}');
              const merged = { ...existing, ...value };
              localStorage.setItem(storageKey, JSON.stringify(merged));
            } catch {
              localStorage.setItem(storageKey, JSON.stringify(value));
            }
          }
        }
      });
    }
    
    // Notify data change
    window.dispatchEvent(new CustomEvent('adminDataUpdate'));
    
    const totalItems = Object.values(validation.metadata?.itemCounts || {}).reduce((a, b) => a + b, 0);
    showSuccessNotification(
      'Import Successful',
      `Successfully imported ${totalItems} items from ${validation.metadata?.exportDate || 'unknown date'}`
    );
    
    return true;
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Import Failed', `Failed to import data: ${errorMessage}`);
    return false;
  }
};

// Create automatic backup
export const createAutomaticBackup = (reason: string = 'manual'): string => {
  try {
    const backupData = exportAdminData();
    const backupKey = `admin_backup_${reason}_${Date.now()}`;
    
    localStorage.setItem(backupKey, backupData);
    
    // Clean up old backups
    cleanupOldBackups();
    
    showInfoNotification(
      'Backup Created',
      `Automatic backup created: ${reason}`
    );
    
    return backupKey;
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Backup Failed', `Failed to create backup: ${errorMessage}`);
    throw error;
  }
};

// Get all available backups
export const getAvailableBackups = (): Array<{
  key: string;
  reason: string;
  timestamp: number;
  date: string;
  size: number;
}> => {
  try {
    const backups: Array<{
      key: string;
      reason: string;
      timestamp: number;
      date: string;
      size: number;
    }> = [];
    
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('admin_backup_')) {
        try {
          const parts = key.split('_');
          const reason = parts[2] || 'unknown';
          const timestamp = parseInt(parts[3] || '0', 10);
          const data = localStorage.getItem(key);
          
          if (data && timestamp) {
            backups.push({
              key,
              reason,
              timestamp,
              date: new Date(timestamp).toLocaleString(),
              size: new Blob([data]).size
            });
          }
        } catch (error) {
          console.warn(`Failed to parse backup key: ${key}`, error);
        }
      }
    });
    
    return backups.sort((a, b) => b.timestamp - a.timestamp);
  } catch (error) {
    console.error('Failed to get available backups:', error);
    return [];
  }
};

// Restore from backup
export const restoreFromBackup = (backupKey: string): boolean => {
  try {
    const backupData = localStorage.getItem(backupKey);
    
    if (!backupData) {
      showErrorNotification('Restore Failed', 'Backup not found');
      return false;
    }
    
    // Create backup before restore
    createAutomaticBackup('pre_restore');
    
    // Import backup data
    const success = importAdminData(backupData, { overwrite: true, createBackup: false });
    
    if (success) {
      showSuccessNotification(
        'Restore Successful',
        'Data has been restored from backup'
      );
    }
    
    return success;
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Restore Failed', `Failed to restore from backup: ${errorMessage}`);
    return false;
  }
};

// Delete backup
export const deleteBackup = (backupKey: string): boolean => {
  try {
    localStorage.removeItem(backupKey);
    showInfoNotification('Backup Deleted', 'Backup has been deleted');
    return true;
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    showErrorNotification('Delete Failed', `Failed to delete backup: ${errorMessage}`);
    return false;
  }
};

// Clean up old backups
export const cleanupOldBackups = (maxBackups: number = 10): void => {
  try {
    const backups = getAvailableBackups();
    
    if (backups.length > maxBackups) {
      const toDelete = backups.slice(maxBackups);
      toDelete.forEach(backup => {
        localStorage.removeItem(backup.key);
      });
      
      showInfoNotification(
        'Backups Cleaned',
        `Removed ${toDelete.length} old backups`
      );
    }
  } catch (error) {
    console.error('Failed to cleanup old backups:', error);
  }
};

// Data migration utilities for future schema changes
export interface MigrationRule {
  fromVersion: string;
  toVersion: string;
  migrate: (data: any) => any;
  description: string;
}

const migrationRules: MigrationRule[] = [
  // Example migration rule
  {
    fromVersion: '0.9.0',
    toVersion: '1.0.0',
    migrate: (data: any) => {
      // Example: Add new fields or transform existing ones
      if (data.services) {
        data.services = data.services.map((service: any) => ({
          ...service,
          order: service.order || 0 // Add order field if missing
        }));
      }
      return data;
    },
    description: 'Add order field to services'
  }
];

// Apply migrations to imported data
export const applyMigrations = (data: any, fromVersion: string, toVersion: string): any => {
  let migratedData = { ...data };
  let currentVersion = fromVersion;
  
  // Find and apply applicable migrations
  const applicableMigrations = migrationRules.filter(rule => 
    rule.fromVersion === currentVersion && 
    isVersionLessOrEqual(rule.toVersion, toVersion)
  );
  
  for (const migration of applicableMigrations) {
    try {
      migratedData = migration.migrate(migratedData);
      currentVersion = migration.toVersion;
      console.log(`Applied migration: ${migration.description}`);
    } catch (error) {
      console.error(`Migration failed: ${migration.description}`, error);
      throw new Error(`Migration failed: ${migration.description}`);
    }
  }
  
  return migratedData;
};

// Version comparison utility
const isVersionLessOrEqual = (version1: string, version2: string): boolean => {
  const v1Parts = version1.split('.').map(Number);
  const v2Parts = version2.split('.').map(Number);
  
  for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
    const v1Part = v1Parts[i] || 0;
    const v2Part = v2Parts[i] || 0;
    
    if (v1Part < v2Part) return true;
    if (v1Part > v2Part) return false;
  }
  
  return true; // Equal versions
};
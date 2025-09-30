import React, { createContext, useContext, useState, useEffect, ReactNode, useMemo, useCallback } from 'react';
import {
  AdminContextType,
  AdminUser,
  HeroContent,
  AboutContent,
  Service,
  Project,
  BlogPost,
  ContactMessage,
  ContactInfo,
  SystemSettings,
  STORAGE_KEYS,
  AdminError
} from '@/types/admin';
import { validateHeroContent } from '@/utils/heroValidation';
import { validateAboutContent } from '@/utils/aboutValidation';
import { 
  validateSystemSettings, 
  sanitizeSystemSettings,
  getSystemSettingsDefaults,
  createSystemSettingsBackup,
  restoreSystemSettingsFromBackup
} from '@/utils/systemSettingsValidation';
import { notifyDataChange } from '@/utils/dataTransformation';
import { 
  showSuccessNotification, 
  showErrorNotification, 
  showWarningNotification 
} from '@/components/admin/ui/DataChangeNotification';

const AdminContext = createContext<AdminContextType | undefined>(undefined);

// Default data
const defaultHeroContent: HeroContent = {
  greeting: { vi: 'Xin chào! Tôi là', en: 'Hello! I\'m' },
  name: 'Nhật Anh Dev',
  title: { vi: 'Freelance Fullstack Developer', en: 'Freelance Fullstack Developer' },
  subtitle: { vi: 'Phát triển web toàn diện với công nghệ hiện đại', en: 'Comprehensive web development with modern technology' },
  ctaText: { vi: 'Xem Portfolio', en: 'View Portfolio' },
  ctaLink: '#portfolio'
};

const defaultAboutContent: AboutContent = {
  description: { 
    vi: 'Với hơn 5 năm kinh nghiệm trong lập trình fullstack, tôi chuyên phát triển các ứng dụng web hiện đại sử dụng React, Node.js, và các công nghệ tiên tiến.',
    en: 'With over 5 years of experience in fullstack programming, I specialize in developing modern web applications using React, Node.js, and cutting-edge technologies.'
  },
  profileImage: '/src/assets/pixel-dev-character.png',
  experience: {
    vi: 'Đam mê tạo ra những sản phẩm chất lượng cao và trải nghiệm người dùng tuyệt vời.',
    en: 'Passionate about creating high-quality products and excellent user experiences.'
  }
};

const defaultContactInfo: ContactInfo = {
  email: 'nhatanhdev@gmail.com',
  phone: '+84 123 456 789',
  github: 'https://github.com/nhatanhdev',
  linkedin: 'https://linkedin.com/in/nhatanhdev'
};

const defaultSystemSettings: SystemSettings = getSystemSettingsDefaults();

const defaultContactMessages: ContactMessage[] = [
  {
    id: '1',
    name: 'John Smith',
    email: 'john.smith@example.com',
    message: 'Hi, I\'m interested in your web development services. Could you please provide more information about your pricing and timeline for a small business website?',
    timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000), // 2 hours ago
    read: false
  },
  {
    id: '2',
    name: 'Maria Garcia',
    email: 'maria.garcia@company.com',
    message: 'Hello! I saw your portfolio and I\'m impressed with your work. We have a React project that needs some help. Are you available for freelance work?',
    timestamp: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000), // 1 day ago
    read: true
  },
  {
    id: '3',
    name: 'David Chen',
    email: 'david.chen@startup.io',
    message: 'We\'re a startup looking for a fullstack developer to join our team. Your experience with Node.js and React looks perfect for our needs. Would you be interested in discussing this opportunity?',
    timestamp: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000), // 3 days ago
    read: false
  },
  {
    id: '4',
    name: 'Sarah Johnson',
    email: 'sarah@designstudio.com',
    message: 'Hi Nhật Anh! I\'m a designer and I have a client who needs a custom e-commerce website. Would you be interested in collaborating on this project?',
    timestamp: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000), // 5 days ago
    read: true
  },
  {
    id: '5',
    name: 'Michael Brown',
    email: 'mike.brown@techcorp.com',
    message: 'Hello, we need help migrating our legacy system to a modern tech stack. Your expertise in both frontend and backend development would be valuable. Can we schedule a call?',
    timestamp: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), // 1 week ago
    read: false
  }
];

interface AdminProviderProps {
  children: ReactNode;
}

export const AdminProvider: React.FC<AdminProviderProps> = ({ children }) => {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [heroContent, setHeroContent] = useState<HeroContent>(defaultHeroContent);
  const [aboutContent, setAboutContent] = useState<AboutContent>(defaultAboutContent);
  const [services, setServices] = useState<Service[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [blogPosts, setBlogPosts] = useState<BlogPost[]>([]);
  const [contactMessages, setContactMessages] = useState<ContactMessage[]>(defaultContactMessages);
  const [contactInfo, setContactInfo] = useState<ContactInfo>(defaultContactInfo);
  const [systemSettings, setSystemSettings] = useState<SystemSettings>(defaultSystemSettings);
  const [lastError, setLastError] = useState<AdminError | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  // Load data from localStorage on mount
  useEffect(() => {
    const loadStoredData = () => {
      try {
        // Load user session
        const storedUser = localStorage.getItem(STORAGE_KEYS.ADMIN_USER);
        if (storedUser) {
          const userData = JSON.parse(storedUser);
          // Check if session is still valid (24 hours)
          const loginTime = new Date(userData.loginTime);
          const now = new Date();
          const hoursDiff = (now.getTime() - loginTime.getTime()) / (1000 * 60 * 60);
          
          if (hoursDiff < 24) {
            setUser(userData);
          } else {
            localStorage.removeItem(STORAGE_KEYS.ADMIN_USER);
          }
        }

        // Load content data
        const storedHero = localStorage.getItem(STORAGE_KEYS.HERO_CONTENT);
        if (storedHero) setHeroContent(JSON.parse(storedHero));

        const storedAbout = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
        if (storedAbout) setAboutContent(JSON.parse(storedAbout));

        const storedServices = localStorage.getItem(STORAGE_KEYS.SERVICES);
        if (storedServices) setServices(JSON.parse(storedServices));

        const storedProjects = localStorage.getItem(STORAGE_KEYS.PROJECTS);
        if (storedProjects) setProjects(JSON.parse(storedProjects));

        const storedBlogPosts = localStorage.getItem(STORAGE_KEYS.BLOG_POSTS);
        if (storedBlogPosts) setBlogPosts(JSON.parse(storedBlogPosts));

        const storedMessages = localStorage.getItem(STORAGE_KEYS.CONTACT_MESSAGES);
        if (storedMessages) setContactMessages(JSON.parse(storedMessages));

        const storedContactInfo = localStorage.getItem(STORAGE_KEYS.CONTACT_INFO);
        if (storedContactInfo) setContactInfo(JSON.parse(storedContactInfo));

        const storedSettings = localStorage.getItem(STORAGE_KEYS.SYSTEM_SETTINGS);
        if (storedSettings) setSystemSettings(JSON.parse(storedSettings));
      } catch (error) {
        console.error('Error loading admin data from localStorage:', error);
      }
    };

    loadStoredData();
  }, []);

  // Authentication functions
  const login = async (username: string, password: string): Promise<boolean> => {
    // Simple authentication for demo purposes
    // In production, this would make an API call
    if (username === 'admin' && password === 'admin123') {
      const userData: AdminUser = {
        username,
        isAuthenticated: true,
        loginTime: new Date()
      };
      setUser(userData);
      localStorage.setItem(STORAGE_KEYS.ADMIN_USER, JSON.stringify(userData));
      return true;
    }
    return false;
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem(STORAGE_KEYS.ADMIN_USER);
  };

  // Error handling functions
  const clearError = () => {
    setLastError(null);
  };

  const handleError = (error: unknown, type: AdminError['type'] = 'storage') => {
    const adminError: AdminError = {
      type,
      message: error instanceof Error ? error.message : 'An unknown error occurred'
    };
    setLastError(adminError);
    console.error(`Admin ${type} error:`, error);
  };

  // Content management functions
  const updateHeroContent = (content: Partial<HeroContent>) => {
    try {
      setIsLoading(true);
      clearError();
      
      const updated = { ...heroContent, ...content };
      
      // Validate the complete hero content
      const validation = validateHeroContent(updated);
      
      if (!validation.isValid) {
        const errorMessages = Object.values(validation.errors).join(', ');
        const validationError = new Error(`Validation failed: ${errorMessages}`);
        handleError(validationError, 'validation');
        showErrorNotification('Validation Error', errorMessages);
        throw validationError;
      }
      
      // Use sanitized data if validation passed
      const finalData = validation.sanitizedData || updated;
      
      setHeroContent(finalData);
      localStorage.setItem(STORAGE_KEYS.HERO_CONTENT, JSON.stringify(finalData));
      
      // Notify data change for real-time sync
      notifyDataChange('heroContent', 'update', finalData);
      
      showSuccessNotification('Hero Content Updated', 'Hero section changes have been saved successfully.');
      console.log('Hero content updated successfully');
    } catch (error) {
      if (error instanceof Error && !error.message.includes('Validation failed')) {
        handleError(error, 'storage');
        showErrorNotification('Save Error', 'Failed to save hero content changes.');
      }
      throw error; // Re-throw to allow components to handle the error
    } finally {
      setIsLoading(false);
    }
  };

  const updateAboutContent = (content: Partial<AboutContent>) => {
    try {
      setIsLoading(true);
      clearError();
      
      const updated = { ...aboutContent, ...content };
      
      // Validate the complete about content with enhanced options
      const validation = validateAboutContent(updated, {
        maxDescriptionLength: 500,
        maxExperienceLength: 300,
        requireImage: true,
        allowedImageTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        maxImageSize: 5 * 1024 * 1024 // 5MB
      });
      
      if (!validation.isValid) {
        const errorMessages = Object.values(validation.errors).join(', ');
        const validationError = new Error(`Validation failed: ${errorMessages}`);
        handleError(validationError, 'validation');
        throw validationError;
      }
      
      // Use sanitized data if validation passed
      const finalData = validation.sanitizedData || updated;
      
      // Check storage availability before proceeding
      try {
        const testKey = 'storage_test';
        localStorage.setItem(testKey, 'test');
        localStorage.removeItem(testKey);
      } catch (storageError) {
        const error = new Error('Local storage is not available or full. Please clear some space and try again.');
        handleError(error, 'storage');
        throw error;
      }
      
      // Create backup before updating with error handling
      try {
        const backupKey = `${STORAGE_KEYS.ABOUT_CONTENT}_backup_${Date.now()}`;
        const backupData = JSON.stringify(aboutContent);
        localStorage.setItem(backupKey, backupData);
        
        // Clean up old backups (keep only last 5) with error handling
        const allKeys = Object.keys(localStorage);
        const backupKeys = allKeys
          .filter(key => key.startsWith(`${STORAGE_KEYS.ABOUT_CONTENT}_backup_`))
          .sort()
          .reverse();
        
        if (backupKeys.length > 5) {
          backupKeys.slice(5).forEach(key => {
            try {
              localStorage.removeItem(key);
            } catch (cleanupError) {
              console.warn('Failed to clean up old backup:', key, cleanupError);
            }
          });
        }
      } catch (backupError) {
        console.warn('Failed to create backup, proceeding with update:', backupError);
        // Don't throw here, just warn - we can still proceed with the update
      }
      
      // Update the content with error handling
      try {
        const dataToStore = JSON.stringify(finalData);
        localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, dataToStore);
        setAboutContent(finalData);
        
        // Notify data change for real-time sync
        notifyDataChange('aboutContent', 'update', finalData);
        
        showSuccessNotification('About Content Updated', 'About section changes have been saved successfully.');
        console.log('About content updated successfully');
      } catch (storageError) {
        // Try to restore from backup if main update fails
        try {
          const allKeys = Object.keys(localStorage);
          const backupKeys = allKeys
            .filter(key => key.startsWith(`${STORAGE_KEYS.ABOUT_CONTENT}_backup_`))
            .sort()
            .reverse();
          
          if (backupKeys.length > 0) {
            const latestBackup = localStorage.getItem(backupKeys[0]);
            if (latestBackup) {
              localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, latestBackup);
              console.log('Restored from backup after storage failure');
            }
          }
        } catch (restoreError) {
          console.error('Failed to restore from backup:', restoreError);
        }
        
        const error = new Error('Failed to save about content. Storage may be full or corrupted.');
        handleError(error, 'storage');
        throw error;
      }
      
    } catch (error) {
      if (error instanceof Error && !error.message.includes('Validation failed')) {
        handleError(error, 'storage');
      }
      throw error; // Re-throw to allow components to handle the error
    } finally {
      setIsLoading(false);
    }
  };

  // Service management
  const addService = (service: Omit<Service, 'id'>) => {
    const newService: Service = {
      ...service,
      id: Date.now().toString()
    };
    const updated = [...services, newService];
    setServices(updated);
    localStorage.setItem(STORAGE_KEYS.SERVICES, JSON.stringify(updated));
    
    // Notify data change for real-time sync
    notifyDataChange('services', 'add', newService);
    showSuccessNotification('Service Added', `Service "${newService.title.vi || newService.title.en}" has been added successfully.`);
  };

  const updateService = (id: string, service: Partial<Service>) => {
    const updated = services.map(s => s.id === id ? { ...s, ...service } : s);
    setServices(updated);
    localStorage.setItem(STORAGE_KEYS.SERVICES, JSON.stringify(updated));
    
    // Notify data change for real-time sync
    notifyDataChange('services', 'update', { id, ...service });
    showSuccessNotification('Service Updated', 'Service changes have been saved successfully.');
  };

  const deleteService = (id: string) => {
    const serviceToDelete = services.find(s => s.id === id);
    const updated = services.filter(s => s.id !== id);
    setServices(updated);
    localStorage.setItem(STORAGE_KEYS.SERVICES, JSON.stringify(updated));
    
    // Notify data change for real-time sync
    notifyDataChange('services', 'delete', { id });
    showSuccessNotification('Service Deleted', `Service "${serviceToDelete?.title.vi || serviceToDelete?.title.en || 'Unknown'}" has been deleted.`);
  };

  const reorderServices = (reorderedServices: Service[]) => {
    setServices(reorderedServices);
    localStorage.setItem(STORAGE_KEYS.SERVICES, JSON.stringify(reorderedServices));
    
    // Notify data change for real-time sync
    notifyDataChange('services', 'reorder', reorderedServices);
    showSuccessNotification('Services Reordered', 'Service order has been updated successfully.');
  };

  // Project management
  const addProject = (project: Omit<Project, 'id'>) => {
    const newProject: Project = {
      ...project,
      id: Date.now().toString()
    };
    const updated = [...projects, newProject];
    setProjects(updated);
    localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(updated));
  };

  const updateProject = (id: string, project: Partial<Project>) => {
    const updated = projects.map(p => p.id === id ? { ...p, ...project } : p);
    setProjects(updated);
    localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(updated));
  };

  const deleteProject = (id: string) => {
    const updated = projects.filter(p => p.id !== id);
    setProjects(updated);
    localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(updated));
  };

  const reorderProjects = (reorderedProjects: Project[]) => {
    setProjects(reorderedProjects);
    localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(reorderedProjects));
  };

  // Blog management
  const addBlogPost = (post: Omit<BlogPost, 'id'>) => {
    const newPost: BlogPost = {
      ...post,
      id: Date.now().toString()
    };
    const updated = [...blogPosts, newPost];
    setBlogPosts(updated);
    localStorage.setItem(STORAGE_KEYS.BLOG_POSTS, JSON.stringify(updated));
  };

  const updateBlogPost = (id: string, post: Partial<BlogPost>) => {
    const updated = blogPosts.map(p => p.id === id ? { ...p, ...post } : p);
    setBlogPosts(updated);
    localStorage.setItem(STORAGE_KEYS.BLOG_POSTS, JSON.stringify(updated));
  };

  const deleteBlogPost = (id: string) => {
    const updated = blogPosts.filter(p => p.id !== id);
    setBlogPosts(updated);
    localStorage.setItem(STORAGE_KEYS.BLOG_POSTS, JSON.stringify(updated));
  };

  const publishBlogPost = (id: string) => {
    const updated = blogPosts.map(p => 
      p.id === id ? { ...p, status: 'published' as const, publishDate: new Date() } : p
    );
    setBlogPosts(updated);
    localStorage.setItem(STORAGE_KEYS.BLOG_POSTS, JSON.stringify(updated));
  };

  // Contact management
  const markMessageAsRead = (id: string) => {
    const updated = contactMessages.map(m => m.id === id ? { ...m, read: true } : m);
    setContactMessages(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_MESSAGES, JSON.stringify(updated));
  };

  const deleteMessage = (id: string) => {
    const updated = contactMessages.filter(m => m.id !== id);
    setContactMessages(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_MESSAGES, JSON.stringify(updated));
  };

  const bulkDeleteMessages = (ids: string[]) => {
    const updated = contactMessages.filter(m => !ids.includes(m.id));
    setContactMessages(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_MESSAGES, JSON.stringify(updated));
  };

  const bulkMarkAsRead = (ids: string[]) => {
    const updated = contactMessages.map(m => 
      ids.includes(m.id) ? { ...m, read: true } : m
    );
    setContactMessages(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_MESSAGES, JSON.stringify(updated));
  };

  const addContactMessage = (message: Omit<ContactMessage, 'id'>) => {
    const newMessage: ContactMessage = {
      ...message,
      id: Date.now().toString()
    };
    const updated = [newMessage, ...contactMessages];
    setContactMessages(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_MESSAGES, JSON.stringify(updated));
  };

  const updateContactInfo = (info: Partial<ContactInfo>) => {
    const updated = { ...contactInfo, ...info };
    setContactInfo(updated);
    localStorage.setItem(STORAGE_KEYS.CONTACT_INFO, JSON.stringify(updated));
  };

  const updateSystemSettings = (settings: Partial<SystemSettings>) => {
    try {
      setIsLoading(true);
      clearError();
      
      const updated = { ...systemSettings, ...settings };
      
      // Validate the complete system settings
      const validation = validateSystemSettings(updated);
      
      if (!validation.isValid) {
        const errorMessages = Object.values(validation.errors).join(', ');
        const validationError = new Error(`Validation failed: ${errorMessages}`);
        handleError(validationError, 'validation');
        throw validationError;
      }
      
      // Use sanitized data if validation passed
      const finalData = validation.sanitizedData || updated;
      
      // Create backup before updating
      try {
        const backupKey = `${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_${Date.now()}`;
        const backupData = createSystemSettingsBackup(systemSettings);
        localStorage.setItem(backupKey, backupData);
        
        // Clean up old backups (keep only last 5)
        const allKeys = Object.keys(localStorage);
        const backupKeys = allKeys
          .filter(key => key.startsWith(`${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_`))
          .sort()
          .reverse();
        
        if (backupKeys.length > 5) {
          backupKeys.slice(5).forEach(key => {
            try {
              localStorage.removeItem(key);
            } catch (cleanupError) {
              console.warn('Failed to clean up old backup:', key, cleanupError);
            }
          });
        }
      } catch (backupError) {
        console.warn('Failed to create backup, proceeding with update:', backupError);
      }
      
      // Update the settings
      setSystemSettings(finalData);
      localStorage.setItem(STORAGE_KEYS.SYSTEM_SETTINGS, JSON.stringify(finalData));
      
      // Notify data change for real-time sync
      notifyDataChange('systemSettings', 'update', finalData);
      
      // Create automatic backup for critical changes
      try {
        import('@/utils/dataBackupExport').then(({ createAutomaticBackup }) => {
          createAutomaticBackup('system_settings_update');
        });
      } catch (backupError) {
        console.warn('Failed to create automatic backup:', backupError);
      }
      
      showSuccessNotification('System Settings Updated', 'System settings have been saved successfully.');
      console.log('System settings updated successfully');
    } catch (error) {
      if (error instanceof Error && !error.message.includes('Validation failed')) {
        handleError(error, 'storage');
      }
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  // File management
  const uploadImage = async (file: File, category: string): Promise<string> => {
    try {
      setIsLoading(true);
      clearError();
      
      // For empty file (removal), return empty string
      if (!file.name) {
        return '';
      }
      
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
          const result = e.target?.result as string;
          const imageData = {
            id: Date.now().toString(),
            category,
            filename: file.name,
            data: result,
            uploadDate: new Date().toISOString(),
            size: file.size
          };
          
          try {
            // Store in localStorage
            const existingImages = JSON.parse(localStorage.getItem(STORAGE_KEYS.UPLOADED_IMAGES) || '[]');
            existingImages.push(imageData);
            localStorage.setItem(STORAGE_KEYS.UPLOADED_IMAGES, JSON.stringify(existingImages));
            
            resolve(result);
          } catch (storageError) {
            reject(new Error('Failed to store image in local storage'));
          }
        };
        reader.onerror = () => reject(new Error('Failed to read file'));
        reader.readAsDataURL(file);
      });
    } catch (error) {
      handleError(error, 'upload');
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const deleteImage = (url: string) => {
    try {
      const existingImages = JSON.parse(localStorage.getItem(STORAGE_KEYS.UPLOADED_IMAGES) || '[]');
      const updated = existingImages.filter((img: unknown) => img.data !== url);
      localStorage.setItem(STORAGE_KEYS.UPLOADED_IMAGES, JSON.stringify(updated));
    } catch (error) {
      console.error('Failed to delete image:', error);
      handleError(error, 'storage');
    }
  };

  // About content specific helper functions
  const getAboutContentBackups = (): Array<{ key: string; timestamp: number; data: AboutContent }> => {
    try {
      const allKeys = Object.keys(localStorage);
      const backupKeys = allKeys
        .filter(key => key.startsWith(`${STORAGE_KEYS.ABOUT_CONTENT}_backup_`))
        .sort()
        .reverse();
      
      return backupKeys.map(key => {
        const timestampStr = key.replace(`${STORAGE_KEYS.ABOUT_CONTENT}_backup_`, '');
        const timestamp = parseInt(timestampStr, 10);
        const data = JSON.parse(localStorage.getItem(key) || '{}');
        return { key, timestamp, data };
      }).filter(backup => backup.data.description); // Only valid backups
    } catch (error) {
      console.error('Failed to get about content backups:', error);
      return [];
    }
  };

  const restoreAboutContentFromBackup = (backupKey: string): boolean => {
    try {
      setIsLoading(true);
      clearError();
      
      const backupData = localStorage.getItem(backupKey);
      if (!backupData) {
        throw new Error('Backup not found');
      }
      
      const parsedData = JSON.parse(backupData) as AboutContent;
      
      // Validate the backup data
      const validation = validateAboutContent(parsedData);
      if (!validation.isValid) {
        throw new Error('Backup data is invalid');
      }
      
      // Create a new backup of current state before restoring
      const currentBackupKey = `${STORAGE_KEYS.ABOUT_CONTENT}_backup_${Date.now()}`;
      localStorage.setItem(currentBackupKey, JSON.stringify(aboutContent));
      
      // Restore the backup
      setAboutContent(parsedData);
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, backupData);
      
      console.log('About content restored from backup successfully');
      return true;
    } catch (error) {
      console.error('Failed to restore about content from backup:', error);
      handleError(error, 'storage');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const exportAboutContent = (): string => {
    try {
      const exportData = {
        aboutContent,
        exportDate: new Date().toISOString(),
        version: '1.0'
      };
      return JSON.stringify(exportData, null, 2);
    } catch (error) {
      console.error('Failed to export about content:', error);
      handleError(error, 'storage');
      throw error;
    }
  };

  const importAboutContent = (jsonData: string): boolean => {
    try {
      setIsLoading(true);
      clearError();
      
      const importedData = JSON.parse(jsonData);
      
      // Validate import structure
      if (!importedData.aboutContent || typeof importedData.aboutContent !== 'object') {
        throw new Error('Invalid import format: missing aboutContent');
      }
      
      const contentToImport = importedData.aboutContent as AboutContent;
      
      // Validate the imported content
      const validation = validateAboutContent(contentToImport);
      if (!validation.isValid) {
        const errorMessages = Object.values(validation.errors).join(', ');
        throw new Error(`Invalid content: ${errorMessages}`);
      }
      
      // Create backup before importing
      const backupKey = `${STORAGE_KEYS.ABOUT_CONTENT}_backup_${Date.now()}`;
      localStorage.setItem(backupKey, JSON.stringify(aboutContent));
      
      // Import the content
      const finalData = validation.sanitizedData || contentToImport;
      setAboutContent(finalData);
      localStorage.setItem(STORAGE_KEYS.ABOUT_CONTENT, JSON.stringify(finalData));
      
      console.log('About content imported successfully');
      return true;
    } catch (error) {
      console.error('Failed to import about content:', error);
      handleError(error, 'storage');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const validateAboutContentIntegrity = (): { isValid: boolean; issues: string[] } => {
    try {
      const issues: string[] = [];
      
      // Check if content exists in localStorage
      const storedContent = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
      if (!storedContent) {
        issues.push('No about content found in storage');
        return { isValid: false, issues };
      }
      
      // Check if content can be parsed
      let parsedContent: AboutContent;
      try {
        parsedContent = JSON.parse(storedContent);
      } catch (parseError) {
        issues.push('About content in storage is corrupted (invalid JSON)');
        return { isValid: false, issues };
      }
      
      // Validate content structure and data
      const validation = validateAboutContent(parsedContent);
      if (!validation.isValid) {
        Object.entries(validation.errors).forEach(([field, error]) => {
          issues.push(`${field}: ${error}`);
        });
      }
      
      // Check if current state matches stored state
      const currentContentStr = JSON.stringify(aboutContent);
      const storedContentStr = JSON.stringify(parsedContent);
      if (currentContentStr !== storedContentStr) {
        issues.push('Current state does not match stored state');
      }
      
      // Check image accessibility if it's a URL
      if (parsedContent.profileImage && !parsedContent.profileImage.startsWith('data:')) {
        // For external URLs, we can't easily validate without making a request
        // Just check if it looks like a valid URL
        try {
          new URL(parsedContent.profileImage);
        } catch {
          issues.push('Profile image URL appears to be invalid');
        }
      }
      
      return { isValid: issues.length === 0, issues };
    } catch (error) {
      console.error('Failed to validate about content integrity:', error);
      return { isValid: false, issues: ['Failed to perform integrity check'] };
    }
  };

  // System Settings specific helper functions
  const getSystemSettingsBackups = (): Array<{ key: string; timestamp: number; data: SystemSettings }> => {
    try {
      const allKeys = Object.keys(localStorage);
      const backupKeys = allKeys
        .filter(key => key.startsWith(`${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_`))
        .sort()
        .reverse();
      
      return backupKeys.map(key => {
        const timestampStr = key.replace(`${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_`, '');
        const timestamp = parseInt(timestampStr, 10);
        const backupData = localStorage.getItem(key);
        
        if (backupData) {
          const parsed = JSON.parse(backupData);
          return { key, timestamp, data: parsed.settings };
        }
        
        return null;
      }).filter(backup => backup !== null) as Array<{ key: string; timestamp: number; data: SystemSettings }>;
    } catch (error) {
      console.error('Failed to get system settings backups:', error);
      return [];
    }
  };

  const restoreSystemSettingsFromBackupKey = (backupKey: string): boolean => {
    try {
      setIsLoading(true);
      clearError();
      
      const backupData = localStorage.getItem(backupKey);
      if (!backupData) {
        throw new Error('Backup not found');
      }
      
      const restoredSettings = restoreSystemSettingsFromBackup(backupData);
      if (!restoredSettings) {
        throw new Error('Failed to restore settings from backup');
      }
      
      // Create a new backup of current state before restoring
      const currentBackupKey = `${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_${Date.now()}`;
      const currentBackup = createSystemSettingsBackup(systemSettings);
      localStorage.setItem(currentBackupKey, currentBackup);
      
      // Restore the backup
      setSystemSettings(restoredSettings);
      localStorage.setItem(STORAGE_KEYS.SYSTEM_SETTINGS, JSON.stringify(restoredSettings));
      
      console.log('System settings restored from backup successfully');
      return true;
    } catch (error) {
      console.error('Failed to restore system settings from backup:', error);
      handleError(error, 'storage');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const exportSystemSettings = (): string => {
    try {
      return createSystemSettingsBackup(systemSettings);
    } catch (error) {
      console.error('Failed to export system settings:', error);
      handleError(error, 'storage');
      throw error;
    }
  };

  const importSystemSettings = (jsonData: string): boolean => {
    try {
      setIsLoading(true);
      clearError();
      
      const importedSettings = restoreSystemSettingsFromBackup(jsonData);
      if (!importedSettings) {
        throw new Error('Failed to import system settings');
      }
      
      // Create backup before importing
      const backupKey = `${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_${Date.now()}`;
      const backup = createSystemSettingsBackup(systemSettings);
      localStorage.setItem(backupKey, backup);
      
      // Import the settings
      setSystemSettings(importedSettings);
      localStorage.setItem(STORAGE_KEYS.SYSTEM_SETTINGS, JSON.stringify(importedSettings));
      
      console.log('System settings imported successfully');
      return true;
    } catch (error) {
      console.error('Failed to import system settings:', error);
      handleError(error, 'storage');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const validateSystemSettingsIntegrity = (): { isValid: boolean; issues: string[] } => {
    try {
      const issues: string[] = [];
      
      // Check if settings exist in localStorage
      const storedSettings = localStorage.getItem(STORAGE_KEYS.SYSTEM_SETTINGS);
      if (!storedSettings) {
        issues.push('No system settings found in storage');
        return { isValid: false, issues };
      }
      
      // Check if settings can be parsed
      let parsedSettings: SystemSettings;
      try {
        parsedSettings = JSON.parse(storedSettings);
      } catch (parseError) {
        issues.push('System settings in storage are corrupted (invalid JSON)');
        return { isValid: false, issues };
      }
      
      // Validate settings structure and data
      const validation = validateSystemSettings(parsedSettings);
      if (!validation.isValid) {
        Object.entries(validation.errors).forEach(([field, error]) => {
          issues.push(`${field}: ${error}`);
        });
      }
      
      // Check if current state matches stored state
      const currentSettingsStr = JSON.stringify(systemSettings);
      const storedSettingsStr = JSON.stringify(parsedSettings);
      if (currentSettingsStr !== storedSettingsStr) {
        issues.push('Current state does not match stored state');
      }
      
      return { isValid: issues.length === 0, issues };
    } catch (error) {
      console.error('Failed to validate system settings integrity:', error);
      return { isValid: false, issues: ['Failed to perform integrity check'] };
    }
  };

  const resetSystemSettingsToDefaults = (): void => {
    try {
      setIsLoading(true);
      clearError();
      
      // Create backup before resetting
      const backupKey = `${STORAGE_KEYS.SYSTEM_SETTINGS}_backup_${Date.now()}`;
      const backup = createSystemSettingsBackup(systemSettings);
      localStorage.setItem(backupKey, backup);
      
      // Reset to defaults
      const defaults = getSystemSettingsDefaults();
      setSystemSettings(defaults);
      localStorage.setItem(STORAGE_KEYS.SYSTEM_SETTINGS, JSON.stringify(defaults));
      
      console.log('System settings reset to defaults successfully');
    } catch (error) {
      console.error('Failed to reset system settings to defaults:', error);
      handleError(error, 'storage');
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const contextValue: AdminContextType = {
    user,
    login,
    logout,
    heroContent,
    aboutContent,
    services,
    projects,
    blogPosts,
    contactMessages,
    contactInfo,
    systemSettings,
    lastError,
    isLoading,
    clearError,
    updateHeroContent,
    updateAboutContent,
    addService,
    updateService,
    deleteService,
    reorderServices,
    addProject,
    updateProject,
    deleteProject,
    reorderProjects,
    addBlogPost,
    updateBlogPost,
    deleteBlogPost,
    publishBlogPost,
    markMessageAsRead,
    deleteMessage,
    bulkDeleteMessages,
    bulkMarkAsRead,
    addContactMessage,
    updateContactInfo,
    updateSystemSettings,
    uploadImage,
    deleteImage,
    getAboutContentBackups,
    restoreAboutContentFromBackup,
    exportAboutContent,
    importAboutContent,
    validateAboutContentIntegrity,
    getSystemSettingsBackups,
    restoreSystemSettingsFromBackup: restoreSystemSettingsFromBackupKey,
    exportSystemSettings,
    importSystemSettings,
    validateSystemSettingsIntegrity,
    resetSystemSettingsToDefaults
  };

  return (
    <AdminContext.Provider value={contextValue}>
      {children}
    </AdminContext.Provider>
  );
};

export const useAdmin = (): AdminContextType => {
  const context = useContext(AdminContext);
  if (context === undefined) {
    throw new Error('useAdmin must be used within an AdminProvider');
  }
  return context;
};
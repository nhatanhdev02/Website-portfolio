export interface AdminUser {
  username: string;
  isAuthenticated: boolean;
  loginTime: Date;
}

export interface HeroContent {
  greeting: { vi: string; en: string };
  name: string;
  title: { vi: string; en: string };
  subtitle: { vi: string; en: string };
  ctaText: { vi: string; en: string };
  ctaLink: string;
}

export interface AboutContent {
  description: { vi: string; en: string };
  profileImage: string;
  experience: { vi: string; en: string };
}

export interface Service {
  id: string;
  title: { vi: string; en: string };
  description: { vi: string; en: string };
  icon: string;
  color: string;
  bgColor: string;
  order: number;
}

export interface Project {
  id: string;
  title: { vi: string; en: string };
  description: { vi: string; en: string };
  image: string; // Main/primary image
  images?: string[]; // Additional gallery images
  link?: string;
  technologies: string[];
  category: string;
  featured: boolean;
  order: number;
}

export interface BlogPost {
  id: string;
  title: { vi: string; en: string };
  content: { vi: string; en: string };
  excerpt: { vi: string; en: string };
  thumbnail: string;
  publishDate: Date;
  status: 'draft' | 'published';
  tags: string[];
}

export interface ContactMessage {
  id: string;
  name: string;
  email: string;
  message: string;
  timestamp: Date;
  read: boolean;
}

export interface ContactInfo {
  email: string;
  phone: string;
  github: string;
  linkedin: string;
}

export interface SystemSettings {
  defaultLanguage: 'vi' | 'en';
  defaultTheme: 'light' | 'dark';
  colorPalette: string[];
  maintenanceMode: boolean;
}

export interface AdminError {
  type: 'validation' | 'authentication' | 'storage' | 'upload' | 'network';
  message: string;
  field?: string;
  code?: string;
}

export interface AdminContextType {
  // Authentication
  user: AdminUser | null;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  
  // Content Management
  heroContent: HeroContent;
  aboutContent: AboutContent;
  services: Service[];
  projects: Project[];
  blogPosts: BlogPost[];
  contactMessages: ContactMessage[];
  contactInfo: ContactInfo;
  systemSettings: SystemSettings;
  
  // Error Handling & Loading State
  lastError: AdminError | null;
  isLoading: boolean;
  clearError: () => void;
  
  // CRUD Operations
  updateHeroContent: (content: Partial<HeroContent>) => void;
  updateAboutContent: (content: Partial<AboutContent>) => void;
  addService: (service: Omit<Service, 'id'>) => void;
  updateService: (id: string, service: Partial<Service>) => void;
  deleteService: (id: string) => void;
  reorderServices: (services: Service[]) => void;
  
  addProject: (project: Omit<Project, 'id'>) => void;
  updateProject: (id: string, project: Partial<Project>) => void;
  deleteProject: (id: string) => void;
  reorderProjects: (projects: Project[]) => void;
  
  addBlogPost: (post: Omit<BlogPost, 'id'>) => void;
  updateBlogPost: (id: string, post: Partial<BlogPost>) => void;
  deleteBlogPost: (id: string) => void;
  publishBlogPost: (id: string) => void;
  
  markMessageAsRead: (id: string) => void;
  deleteMessage: (id: string) => void;
  bulkDeleteMessages: (ids: string[]) => void;
  bulkMarkAsRead: (ids: string[]) => void;
  addContactMessage: (message: Omit<ContactMessage, 'id'>) => void;
  
  updateContactInfo: (info: Partial<ContactInfo>) => void;
  updateSystemSettings: (settings: Partial<SystemSettings>) => void;
  
  // System Settings Management
  getSystemSettingsBackups: () => Array<{ key: string; timestamp: number; data: SystemSettings }>;
  restoreSystemSettingsFromBackup: (backupKey: string) => boolean;
  exportSystemSettings: () => string;
  importSystemSettings: (jsonData: string) => boolean;
  validateSystemSettingsIntegrity: () => { isValid: boolean; issues: string[] };
  resetSystemSettingsToDefaults: () => void;
  
  // File Management
  uploadImage: (file: File, category: string) => Promise<string>;
  deleteImage: (url: string) => void;
  
  // About Content Management
  getAboutContentBackups: () => Array<{ key: string; timestamp: number; data: AboutContent }>;
  restoreAboutContentFromBackup: (backupKey: string) => boolean;
  exportAboutContent: () => string;
  importAboutContent: (jsonData: string) => boolean;
  validateAboutContentIntegrity: () => { isValid: boolean; issues: string[] };
}

// Storage keys for different data types
export const STORAGE_KEYS = {
  ADMIN_USER: 'admin_user',
  HERO_CONTENT: 'admin_hero_content',
  ABOUT_CONTENT: 'admin_about_content',
  SERVICES: 'admin_services',
  PROJECTS: 'admin_projects',
  BLOG_POSTS: 'admin_blog_posts',
  CONTACT_MESSAGES: 'admin_contact_messages',
  CONTACT_INFO: 'admin_contact_info',
  SYSTEM_SETTINGS: 'admin_system_settings',
  UPLOADED_IMAGES: 'admin_uploaded_images'
} as const;
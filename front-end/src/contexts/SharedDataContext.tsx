import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import {
  HeroContent,
  AboutContent,
  Service,
  Project,
  BlogPost,
  ContactInfo,
  SystemSettings,
  STORAGE_KEYS
} from '@/types/admin';

// Shared data interface for frontend consumption
export interface SharedDataContextType {
  // Content data
  heroContent: HeroContent;
  aboutContent: AboutContent;
  services: Service[];
  projects: Project[];
  blogPosts: BlogPost[];
  contactInfo: ContactInfo;
  systemSettings: SystemSettings;
  
  // Data synchronization
  refreshData: () => void;
  isDataLoaded: boolean;
  
  // Real-time preview functionality
  previewMode: boolean;
  setPreviewMode: (enabled: boolean) => void;
  previewData: Partial<{
    heroContent: HeroContent;
    aboutContent: AboutContent;
    services: Service[];
    projects: Project[];
    blogPosts: BlogPost[];
    contactInfo: ContactInfo;
    systemSettings: SystemSettings;
  }>;
  setPreviewData: (data: SharedDataContextType['previewData']) => void;
  clearPreview: () => void;
  
  // Data transformation utilities
  getTranslatedContent: (content: { vi: string; en: string }, language: 'vi' | 'en') => string;
  getPublishedBlogPosts: () => BlogPost[];
  getFeaturedProjects: () => Project[];
  getOrderedServices: () => Service[];
  getOrderedProjects: () => Project[];
}

const SharedDataContext = createContext<SharedDataContextType | undefined>(undefined);

// Default data fallbacks
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

const defaultSystemSettings: SystemSettings = {
  defaultLanguage: 'vi',
  defaultTheme: 'light',
  colorPalette: [
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4',
    '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'
  ],
  maintenanceMode: false
};

interface SharedDataProviderProps {
  children: ReactNode;
}

export const SharedDataProvider: React.FC<SharedDataProviderProps> = ({ children }) => {
  // Core data state
  const [heroContent, setHeroContent] = useState<HeroContent>(defaultHeroContent);
  const [aboutContent, setAboutContent] = useState<AboutContent>(defaultAboutContent);
  const [services, setServices] = useState<Service[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [blogPosts, setBlogPosts] = useState<BlogPost[]>([]);
  const [contactInfo, setContactInfo] = useState<ContactInfo>(defaultContactInfo);
  const [systemSettings, setSystemSettings] = useState<SystemSettings>(defaultSystemSettings);
  
  // Data loading state
  const [isDataLoaded, setIsDataLoaded] = useState(false);
  
  // Preview functionality state
  const [previewMode, setPreviewMode] = useState(false);
  const [previewData, setPreviewData] = useState<SharedDataContextType['previewData']>({});

  // Load data from localStorage
  const loadDataFromStorage = () => {
    try {
      // Load hero content
      const storedHero = localStorage.getItem(STORAGE_KEYS.HERO_CONTENT);
      if (storedHero) {
        setHeroContent(JSON.parse(storedHero));
      }

      // Load about content
      const storedAbout = localStorage.getItem(STORAGE_KEYS.ABOUT_CONTENT);
      if (storedAbout) {
        setAboutContent(JSON.parse(storedAbout));
      }

      // Load services
      const storedServices = localStorage.getItem(STORAGE_KEYS.SERVICES);
      if (storedServices) {
        setServices(JSON.parse(storedServices));
      }

      // Load projects
      const storedProjects = localStorage.getItem(STORAGE_KEYS.PROJECTS);
      if (storedProjects) {
        setProjects(JSON.parse(storedProjects));
      }

      // Load blog posts
      const storedBlogPosts = localStorage.getItem(STORAGE_KEYS.BLOG_POSTS);
      if (storedBlogPosts) {
        setBlogPosts(JSON.parse(storedBlogPosts));
      }

      // Load contact info
      const storedContactInfo = localStorage.getItem(STORAGE_KEYS.CONTACT_INFO);
      if (storedContactInfo) {
        setContactInfo(JSON.parse(storedContactInfo));
      }

      // Load system settings
      const storedSettings = localStorage.getItem(STORAGE_KEYS.SYSTEM_SETTINGS);
      if (storedSettings) {
        setSystemSettings(JSON.parse(storedSettings));
      }

      setIsDataLoaded(true);
    } catch (error) {
      console.error('Error loading shared data from localStorage:', error);
      setIsDataLoaded(true); // Still mark as loaded to prevent infinite loading
    }
  };

  // Refresh data from storage
  const refreshData = () => {
    loadDataFromStorage();
  };

  // Listen for storage changes (for real-time updates from admin)
  useEffect(() => {
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key && Object.values(STORAGE_KEYS).includes(e.key as any)) {
        // Refresh data when admin makes changes
        refreshData();
      }
    };

    // Listen for storage events
    window.addEventListener('storage', handleStorageChange);

    // Also listen for custom events for same-tab updates
    const handleCustomStorageChange = () => {
      refreshData();
    };

    window.addEventListener('adminDataUpdate', handleCustomStorageChange);

    return () => {
      window.removeEventListener('storage', handleStorageChange);
      window.removeEventListener('adminDataUpdate', handleCustomStorageChange);
    };
  }, []);

  // Load initial data
  useEffect(() => {
    loadDataFromStorage();
  }, []);

  // Clear preview data
  const clearPreview = () => {
    setPreviewData({});
    setPreviewMode(false);
  };

  // Data transformation utilities
  const getTranslatedContent = (content: { vi: string; en: string }, language: 'vi' | 'en'): string => {
    return content[language] || content.vi || content.en || '';
  };

  const getPublishedBlogPosts = (): BlogPost[] => {
    const posts = previewMode && previewData.blogPosts ? previewData.blogPosts : blogPosts;
    return posts
      .filter(post => post.status === 'published')
      .sort((a, b) => new Date(b.publishDate).getTime() - new Date(a.publishDate).getTime());
  };

  const getFeaturedProjects = (): Project[] => {
    const projectList = previewMode && previewData.projects ? previewData.projects : projects;
    return projectList
      .filter(project => project.featured)
      .sort((a, b) => a.order - b.order);
  };

  const getOrderedServices = (): Service[] => {
    const serviceList = previewMode && previewData.services ? previewData.services : services;
    return serviceList.sort((a, b) => a.order - b.order);
  };

  const getOrderedProjects = (): Project[] => {
    const projectList = previewMode && previewData.projects ? previewData.projects : projects;
    return projectList.sort((a, b) => a.order - b.order);
  };

  // Get current data (with preview override)
  const getCurrentHeroContent = (): HeroContent => {
    return previewMode && previewData.heroContent ? previewData.heroContent : heroContent;
  };

  const getCurrentAboutContent = (): AboutContent => {
    return previewMode && previewData.aboutContent ? previewData.aboutContent : aboutContent;
  };

  const getCurrentContactInfo = (): ContactInfo => {
    return previewMode && previewData.contactInfo ? previewData.contactInfo : contactInfo;
  };

  const getCurrentSystemSettings = (): SystemSettings => {
    return previewMode && previewData.systemSettings ? previewData.systemSettings : systemSettings;
  };

  const contextValue: SharedDataContextType = {
    // Content data (with preview override)
    heroContent: getCurrentHeroContent(),
    aboutContent: getCurrentAboutContent(),
    services: getOrderedServices(),
    projects: getOrderedProjects(),
    blogPosts: getPublishedBlogPosts(),
    contactInfo: getCurrentContactInfo(),
    systemSettings: getCurrentSystemSettings(),
    
    // Data synchronization
    refreshData,
    isDataLoaded,
    
    // Preview functionality
    previewMode,
    setPreviewMode,
    previewData,
    setPreviewData,
    clearPreview,
    
    // Data transformation utilities
    getTranslatedContent,
    getPublishedBlogPosts,
    getFeaturedProjects,
    getOrderedServices,
    getOrderedProjects
  };

  return (
    <SharedDataContext.Provider value={contextValue}>
      {children}
    </SharedDataContext.Provider>
  );
};

export const useSharedData = () => {
  const context = useContext(SharedDataContext);
  if (context === undefined) {
    throw new Error('useSharedData must be used within a SharedDataProvider');
  }
  return context;
};
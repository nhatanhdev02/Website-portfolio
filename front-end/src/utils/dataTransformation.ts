import {
  HeroContent,
  AboutContent,
  Service,
  Project,
  BlogPost,
  ContactInfo,
  SystemSettings
} from '@/types/admin';

// Data transformation utilities for admin → frontend data flow

/**
 * Transform hero content for frontend consumption
 */
export const transformHeroContent = (heroContent: HeroContent, language: 'vi' | 'en') => {
  return {
    greeting: heroContent.greeting[language] || heroContent.greeting.vi,
    name: heroContent.name,
    title: heroContent.title[language] || heroContent.title.vi,
    subtitle: heroContent.subtitle[language] || heroContent.subtitle.vi,
    ctaText: heroContent.ctaText[language] || heroContent.ctaText.vi,
    ctaLink: heroContent.ctaLink
  };
};

/**
 * Transform about content for frontend consumption
 */
export const transformAboutContent = (aboutContent: AboutContent, language: 'vi' | 'en') => {
  return {
    description: aboutContent.description[language] || aboutContent.description.vi,
    profileImage: aboutContent.profileImage,
    experience: aboutContent.experience[language] || aboutContent.experience.vi
  };
};

/**
 * Transform services for frontend consumption
 */
export const transformServices = (services: Service[], language: 'vi' | 'en') => {
  return services
    .sort((a, b) => a.order - b.order)
    .map(service => ({
      id: service.id,
      title: service.title[language] || service.title.vi,
      description: service.description[language] || service.description.vi,
      icon: service.icon,
      color: service.color,
      bgColor: service.bgColor,
      order: service.order
    }));
};

/**
 * Transform projects for frontend consumption
 */
export const transformProjects = (projects: Project[], language: 'vi' | 'en') => {
  return projects
    .sort((a, b) => a.order - b.order)
    .map(project => ({
      id: project.id,
      title: project.title[language] || project.title.vi,
      description: project.description[language] || project.description.vi,
      image: project.image,
      images: project.images || [],
      link: project.link,
      technologies: project.technologies,
      category: project.category,
      featured: project.featured,
      order: project.order
    }));
};

/**
 * Transform blog posts for frontend consumption
 */
export const transformBlogPosts = (blogPosts: BlogPost[], language: 'vi' | 'en') => {
  return blogPosts
    .filter(post => post.status === 'published')
    .sort((a, b) => new Date(b.publishDate).getTime() - new Date(a.publishDate).getTime())
    .map(post => ({
      id: post.id,
      title: post.title[language] || post.title.vi,
      content: post.content[language] || post.content.vi,
      excerpt: post.excerpt[language] || post.excerpt.vi,
      thumbnail: post.thumbnail,
      publishDate: post.publishDate,
      tags: post.tags
    }));
};

/**
 * Transform contact info for frontend consumption
 */
export const transformContactInfo = (contactInfo: ContactInfo) => {
  return {
    email: contactInfo.email,
    phone: contactInfo.phone,
    github: contactInfo.github,
    linkedin: contactInfo.linkedin
  };
};

/**
 * Transform system settings for frontend consumption
 */
export const transformSystemSettings = (systemSettings: SystemSettings) => {
  return {
    defaultLanguage: systemSettings.defaultLanguage,
    defaultTheme: systemSettings.defaultTheme,
    colorPalette: systemSettings.colorPalette,
    maintenanceMode: systemSettings.maintenanceMode
  };
};

/**
 * Generate dynamic translations from admin content
 */
export const generateDynamicTranslations = (
  heroContent: HeroContent,
  aboutContent: AboutContent,
  services: Service[],
  contactInfo: ContactInfo
) => {
  const translations = {
    vi: {
      // Hero Section
      'hero.greeting': heroContent.greeting.vi,
      'hero.name': heroContent.name,
      'hero.title': heroContent.title.vi,
      'hero.subtitle': heroContent.subtitle.vi,
      'hero.cta': heroContent.ctaText.vi,

      // About Section
      'about.description': aboutContent.description.vi,
      'about.experience': aboutContent.experience.vi,

      // Contact Info
      'contact.email': contactInfo.email,
      'contact.phone': contactInfo.phone,

      // Services (dynamic)
      ...services.reduce((acc, service, index) => {
        acc[`services.${index}.title`] = service.title.vi;
        acc[`services.${index}.description`] = service.description.vi;
        return acc;
      }, {} as Record<string, string>)
    },
    en: {
      // Hero Section
      'hero.greeting': heroContent.greeting.en,
      'hero.name': heroContent.name,
      'hero.title': heroContent.title.en,
      'hero.subtitle': heroContent.subtitle.en,
      'hero.cta': heroContent.ctaText.en,

      // About Section
      'about.description': aboutContent.description.en,
      'about.experience': aboutContent.experience.en,

      // Contact Info
      'contact.email': contactInfo.email,
      'contact.phone': contactInfo.phone,

      // Services (dynamic)
      ...services.reduce((acc, service, index) => {
        acc[`services.${index}.title`] = service.title.en;
        acc[`services.${index}.description`] = service.description.en;
        return acc;
      }, {} as Record<string, string>)
    }
  };

  return translations;
};

/**
 * Validate data integrity during transformation
 */
export const validateTransformedData = (data: any, type: string): { isValid: boolean; errors: string[] } => {
  const errors: string[] = [];

  switch (type) {
    case 'hero':
      if (!data.name || typeof data.name !== 'string') {
        errors.push('Hero name is required and must be a string');
      }
      if (!data.greeting || typeof data.greeting !== 'string') {
        errors.push('Hero greeting is required and must be a string');
      }
      break;

    case 'about':
      if (!data.description || typeof data.description !== 'string') {
        errors.push('About description is required and must be a string');
      }
      if (!data.profileImage || typeof data.profileImage !== 'string') {
        errors.push('Profile image is required and must be a string');
      }
      break;

    case 'services':
      if (!Array.isArray(data)) {
        errors.push('Services must be an array');
      } else {
        data.forEach((service, index) => {
          if (!service.title || typeof service.title !== 'string') {
            errors.push(`Service ${index + 1}: title is required and must be a string`);
          }
          if (!service.description || typeof service.description !== 'string') {
            errors.push(`Service ${index + 1}: description is required and must be a string`);
          }
        });
      }
      break;

    case 'projects':
      if (!Array.isArray(data)) {
        errors.push('Projects must be an array');
      } else {
        data.forEach((project, index) => {
          if (!project.title || typeof project.title !== 'string') {
            errors.push(`Project ${index + 1}: title is required and must be a string`);
          }
          if (!project.description || typeof project.description !== 'string') {
            errors.push(`Project ${index + 1}: description is required and must be a string`);
          }
        });
      }
      break;

    case 'blogPosts':
      if (!Array.isArray(data)) {
        errors.push('Blog posts must be an array');
      } else {
        data.forEach((post, index) => {
          if (!post.title || typeof post.title !== 'string') {
            errors.push(`Blog post ${index + 1}: title is required and must be a string`);
          }
          if (!post.content || typeof post.content !== 'string') {
            errors.push(`Blog post ${index + 1}: content is required and must be a string`);
          }
        });
      }
      break;

    case 'contactInfo':
      if (!data.email || typeof data.email !== 'string') {
        errors.push('Contact email is required and must be a string');
      }
      if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
        errors.push('Contact email must be a valid email address');
      }
      break;

    default:
      errors.push(`Unknown data type: ${type}`);
  }

  return {
    isValid: errors.length === 0,
    errors
  };
};

/**
 * Create a data change notification
 */
export const notifyDataChange = (type: string, action: string, data?: any) => {
  // Dispatch custom event for same-tab updates
  const event = new CustomEvent('adminDataUpdate', {
    detail: {
      type,
      action,
      data,
      timestamp: new Date().toISOString()
    }
  });
  
  window.dispatchEvent(event);
  
  // Log the change for debugging
  console.log(`Data change notification: ${type} ${action}`, data);
};

/**
 * Create a preview data snapshot
 */
export const createPreviewSnapshot = (
  heroContent?: HeroContent,
  aboutContent?: AboutContent,
  services?: Service[],
  projects?: Project[],
  blogPosts?: BlogPost[],
  contactInfo?: ContactInfo,
  systemSettings?: SystemSettings
) => {
  const snapshot: any = {};
  
  if (heroContent) snapshot.heroContent = { ...heroContent };
  if (aboutContent) snapshot.aboutContent = { ...aboutContent };
  if (services) snapshot.services = [...services];
  if (projects) snapshot.projects = [...projects];
  if (blogPosts) snapshot.blogPosts = [...blogPosts];
  if (contactInfo) snapshot.contactInfo = { ...contactInfo };
  if (systemSettings) snapshot.systemSettings = { ...systemSettings };
  
  return snapshot;
};

/**
 * Merge preview data with current data
 */
export const mergePreviewData = (currentData: any, previewData: any) => {
  return {
    ...currentData,
    ...previewData
  };
};
import { useMemo, useCallback } from 'react';
import { BlogPost, ContactMessage, Project, Service } from '@/types/admin';

interface UseAdminPerformanceProps {
  blogPosts: BlogPost[];
  contactMessages: ContactMessage[];
  projects: Project[];
  services: Service[];
}

export const useAdminPerformance = ({
  blogPosts,
  contactMessages,
  projects,
  services
}: UseAdminPerformanceProps) => {
  
  // Memoized blog post statistics
  const blogStats = useMemo(() => {
    const published = blogPosts.filter(post => post.status === 'published').length;
    const drafts = blogPosts.filter(post => post.status === 'draft').length;
    const totalWords = blogPosts.reduce((total, post) => {
      const viWords = post.content.vi.split(/\s+/).length;
      const enWords = post.content.en.split(/\s+/).length;
      return total + viWords + enWords;
    }, 0);
    
    return { published, drafts, total: blogPosts.length, totalWords };
  }, [blogPosts]);

  // Memoized contact message statistics
  const messageStats = useMemo(() => {
    const unread = contactMessages.filter(msg => !msg.read).length;
    const read = contactMessages.filter(msg => msg.read).length;
    const recent = contactMessages.filter(msg => {
      const daysDiff = (Date.now() - new Date(msg.timestamp).getTime()) / (1000 * 60 * 60 * 24);
      return daysDiff <= 7;
    }).length;
    
    return { unread, read, total: contactMessages.length, recent };
  }, [contactMessages]);

  // Memoized project statistics
  const projectStats = useMemo(() => {
    const featured = projects.filter(project => project.featured).length;
    const categories = [...new Set(projects.map(project => project.category))];
    const technologies = [...new Set(projects.flatMap(project => project.technologies))];
    
    return { 
      total: projects.length, 
      featured, 
      categories: categories.length,
      technologies: technologies.length,
      categoriesList: categories,
      technologiesList: technologies
    };
  }, [projects]);

  // Memoized service statistics
  const serviceStats = useMemo(() => {
    const colors = [...new Set(services.map(service => service.color))];
    const icons = [...new Set(services.map(service => service.icon))];
    
    return {
      total: services.length,
      uniqueColors: colors.length,
      uniqueIcons: icons.length
    };
  }, [services]);

  // Memoized search functions
  const searchBlogPosts = useCallback((query: string) => {
    if (!query.trim()) return blogPosts;
    
    const lowercaseQuery = query.toLowerCase();
    return blogPosts.filter(post => 
      post.title.vi.toLowerCase().includes(lowercaseQuery) ||
      post.title.en.toLowerCase().includes(lowercaseQuery) ||
      post.content.vi.toLowerCase().includes(lowercaseQuery) ||
      post.content.en.toLowerCase().includes(lowercaseQuery) ||
      post.tags.some(tag => tag.toLowerCase().includes(lowercaseQuery))
    );
  }, [blogPosts]);

  const searchMessages = useCallback((query: string) => {
    if (!query.trim()) return contactMessages;
    
    const lowercaseQuery = query.toLowerCase();
    return contactMessages.filter(message =>
      message.name.toLowerCase().includes(lowercaseQuery) ||
      message.email.toLowerCase().includes(lowercaseQuery) ||
      message.message.toLowerCase().includes(lowercaseQuery)
    );
  }, [contactMessages]);

  const searchProjects = useCallback((query: string) => {
    if (!query.trim()) return projects;
    
    const lowercaseQuery = query.toLowerCase();
    return projects.filter(project =>
      project.title.vi.toLowerCase().includes(lowercaseQuery) ||
      project.title.en.toLowerCase().includes(lowercaseQuery) ||
      project.description.vi.toLowerCase().includes(lowercaseQuery) ||
      project.description.en.toLowerCase().includes(lowercaseQuery) ||
      project.technologies.some(tech => tech.toLowerCase().includes(lowercaseQuery)) ||
      project.category.toLowerCase().includes(lowercaseQuery)
    );
  }, [projects]);

  // Memoized filter functions
  const filterBlogPostsByStatus = useCallback((status: 'draft' | 'published' | 'all') => {
    if (status === 'all') return blogPosts;
    return blogPosts.filter(post => post.status === status);
  }, [blogPosts]);

  const filterMessagesByStatus = useCallback((status: 'read' | 'unread' | 'all') => {
    if (status === 'all') return contactMessages;
    return contactMessages.filter(message => 
      status === 'read' ? message.read : !message.read
    );
  }, [contactMessages]);

  const filterProjectsByCategory = useCallback((category: string) => {
    if (!category || category === 'all') return projects;
    return projects.filter(project => project.category === category);
  }, [projects]);

  // Memoized sorting functions
  const sortBlogPostsByDate = useCallback((ascending = false) => {
    return [...blogPosts].sort((a, b) => {
      const dateA = new Date(a.publishDate).getTime();
      const dateB = new Date(b.publishDate).getTime();
      return ascending ? dateA - dateB : dateB - dateA;
    });
  }, [blogPosts]);

  const sortMessagesByDate = useCallback((ascending = false) => {
    return [...contactMessages].sort((a, b) => {
      const dateA = new Date(a.timestamp).getTime();
      const dateB = new Date(b.timestamp).getTime();
      return ascending ? dateA - dateB : dateB - dateA;
    });
  }, [contactMessages]);

  return {
    // Statistics
    blogStats,
    messageStats,
    projectStats,
    serviceStats,
    
    // Search functions
    searchBlogPosts,
    searchMessages,
    searchProjects,
    
    // Filter functions
    filterBlogPostsByStatus,
    filterMessagesByStatus,
    filterProjectsByCategory,
    
    // Sort functions
    sortBlogPostsByDate,
    sortMessagesByDate
  };
};
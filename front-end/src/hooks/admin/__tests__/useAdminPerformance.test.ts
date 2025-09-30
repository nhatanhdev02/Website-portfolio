import { describe, it, expect, beforeEach } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useAdminPerformance } from '../useAdminPerformance';
import { BlogPost, ContactMessage, Project, Service } from '@/types/admin';

describe('useAdminPerformance', () => {
  const mockBlogPosts: BlogPost[] = [
    {
      id: '1',
      title: { vi: 'Blog 1', en: 'Blog 1' },
      content: { vi: 'Content 1 with many words here', en: 'Content 1 with many words here' },
      excerpt: { vi: 'Excerpt 1', en: 'Excerpt 1' },
      thumbnail: '/thumb1.jpg',
      publishDate: new Date(),
      status: 'published',
      tags: ['tech', 'react']
    },
    {
      id: '2',
      title: { vi: 'Blog 2', en: 'Blog 2' },
      content: { vi: 'Content 2', en: 'Content 2' },
      excerpt: { vi: 'Excerpt 2', en: 'Excerpt 2' },
      thumbnail: '/thumb2.jpg',
      publishDate: new Date(),
      status: 'draft',
      tags: ['javascript']
    }
  ];

  const mockContactMessages: ContactMessage[] = [
    {
      id: '1',
      name: 'John Doe',
      email: 'john@example.com',
      message: 'Hello world',
      timestamp: new Date(),
      read: false
    },
    {
      id: '2',
      name: 'Jane Smith',
      email: 'jane@example.com',
      message: 'Test message',
      timestamp: new Date(Date.now() - 8 * 24 * 60 * 60 * 1000), // 8 days ago
      read: true
    }
  ];

  const mockProjects: Project[] = [
    {
      id: '1',
      title: { vi: 'Project 1', en: 'Project 1' },
      description: { vi: 'Description 1', en: 'Description 1' },
      image: '/project1.jpg',
      technologies: ['React', 'TypeScript'],
      category: 'Web',
      featured: true,
      order: 1
    }
  ];

  const mockServices: Service[] = [
    {
      id: '1',
      title: { vi: 'Service 1', en: 'Service 1' },
      description: { vi: 'Description 1', en: 'Description 1' },
      icon: 'icon1',
      color: '#ff0000',
      bgColor: '#000000',
      order: 1
    }
  ];

  beforeEach(() => {
    // Reset any mocks if needed
  });

  it('calculates blog statistics correctly', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    expect(result.current.blogStats.total).toBe(2);
    expect(result.current.blogStats.published).toBe(1);
    expect(result.current.blogStats.drafts).toBe(1);
    expect(result.current.blogStats.totalWords).toBeGreaterThan(0);
  });

  it('calculates message statistics correctly', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    expect(result.current.messageStats.total).toBe(2);
    expect(result.current.messageStats.unread).toBe(1);
    expect(result.current.messageStats.read).toBe(1);
    expect(result.current.messageStats.recent).toBe(1); // Only one message within 7 days
  });

  it('calculates project statistics correctly', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    expect(result.current.projectStats.total).toBe(1);
    expect(result.current.projectStats.featured).toBe(1);
    expect(result.current.projectStats.categories).toBe(1);
    expect(result.current.projectStats.technologies).toBe(2);
  });

  it('provides search functionality for blog posts', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    const searchResults = result.current.searchBlogPosts('Blog 1');
    expect(searchResults).toHaveLength(1);
    expect(searchResults[0].id).toBe('1');

    const emptyResults = result.current.searchBlogPosts('nonexistent');
    expect(emptyResults).toHaveLength(0);
  });

  it('provides filter functionality for blog posts', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    const publishedPosts = result.current.filterBlogPostsByStatus('published');
    expect(publishedPosts).toHaveLength(1);
    expect(publishedPosts[0].status).toBe('published');

    const draftPosts = result.current.filterBlogPostsByStatus('draft');
    expect(draftPosts).toHaveLength(1);
    expect(draftPosts[0].status).toBe('draft');

    const allPosts = result.current.filterBlogPostsByStatus('all');
    expect(allPosts).toHaveLength(2);
  });

  it('provides sorting functionality', () => {
    const { result } = renderHook(() =>
      useAdminPerformance({
        blogPosts: mockBlogPosts,
        contactMessages: mockContactMessages,
        projects: mockProjects,
        services: mockServices
      })
    );

    const sortedPosts = result.current.sortBlogPostsByDate(false);
    expect(sortedPosts).toHaveLength(2);
    
    const sortedMessages = result.current.sortMessagesByDate(true);
    expect(sortedMessages).toHaveLength(2);
  });
});
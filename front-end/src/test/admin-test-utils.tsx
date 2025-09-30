import React, { ReactElement } from 'react';
import { render, RenderOptions } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { AdminProvider } from '@/contexts/AdminContext';
import { SharedDataProvider } from '@/contexts/SharedDataContext';
import { vi } from 'vitest';

// Mock admin data for testing
export const mockAdminUser = {
  username: 'testadmin',
  isAuthenticated: true,
  loginTime: new Date()
};

export const mockHeroContent = {
  greeting: { vi: 'Xin chào! Tôi là', en: 'Hello! I\'m' },
  name: 'Test Dev',
  title: { vi: 'Test Developer', en: 'Test Developer' },
  subtitle: { vi: 'Test subtitle', en: 'Test subtitle' },
  ctaText: { vi: 'Xem Portfolio', en: 'View Portfolio' },
  ctaLink: '#portfolio'
};

export const mockAboutContent = {
  description: { 
    vi: 'Test description in Vietnamese',
    en: 'Test description in English'
  },
  profileImage: '/test-image.png',
  experience: {
    vi: 'Test experience in Vietnamese',
    en: 'Test experience in English'
  }
};

export const mockService = {
  id: 'test-service-1',
  title: { vi: 'Test Service', en: 'Test Service' },
  description: { vi: 'Test description', en: 'Test description' },
  icon: 'test-icon',
  color: '#00ff00',
  bgColor: '#000000',
  order: 1
};

export const mockProject = {
  id: 'test-project-1',
  title: { vi: 'Test Project', en: 'Test Project' },
  description: { vi: 'Test description', en: 'Test description' },
  image: '/test-project.png',
  link: 'https://test-project.com',
  technologies: ['React', 'TypeScript'],
  category: 'Web Development',
  featured: true,
  order: 1
};

export const mockBlogPost = {
  id: 'test-post-1',
  title: { vi: 'Test Blog Post', en: 'Test Blog Post' },
  content: { vi: 'Test content in Vietnamese', en: 'Test content in English' },
  excerpt: { vi: 'Test excerpt', en: 'Test excerpt' },
  thumbnail: '/test-thumbnail.png',
  publishDate: new Date(),
  status: 'published' as const,
  tags: ['test', 'blog']
};

export const mockContactMessage = {
  id: 'test-message-1',
  name: 'Test User',
  email: 'test@example.com',
  message: 'This is a test message',
  timestamp: new Date(),
  read: false
};

export const mockContactInfo = {
  email: 'test@example.com',
  phone: '+1234567890',
  github: 'https://github.com/testuser',
  linkedin: 'https://linkedin.com/in/testuser'
};

export const mockSystemSettings = {
  defaultLanguage: 'en' as const,
  defaultTheme: 'light' as const,
  colorPalette: ['#00ff00', '#ff0000', '#0000ff'],
  maintenanceMode: false
};

// Custom render function with providers
interface CustomRenderOptions extends Omit<RenderOptions, 'wrapper'> {
  initialEntries?: string[];
  adminContextValue?: Record<string, unknown>;
}

export function renderWithProviders(
  ui: ReactElement,
  {
    initialEntries = ['/'],
    adminContextValue = {},
    ...renderOptions
  }: CustomRenderOptions = {}
) {
  // Mock AdminContext value
  const defaultAdminContext = {
    user: mockAdminUser,
    heroContent: mockHeroContent,
    aboutContent: mockAboutContent,
    services: [mockService],
    projects: [mockProject],
    blogPosts: [mockBlogPost],
    contactMessages: [mockContactMessage],
    contactInfo: mockContactInfo,
    systemSettings: mockSystemSettings,
    isLoading: false,
    lastError: null,
    login: vi.fn().mockResolvedValue(true),
    logout: vi.fn(),
    updateHeroContent: vi.fn(),
    updateAboutContent: vi.fn(),
    addService: vi.fn(),
    updateService: vi.fn(),
    deleteService: vi.fn(),
    reorderServices: vi.fn(),
    addProject: vi.fn(),
    updateProject: vi.fn(),
    deleteProject: vi.fn(),
    reorderProjects: vi.fn(),
    addBlogPost: vi.fn(),
    updateBlogPost: vi.fn(),
    deleteBlogPost: vi.fn(),
    publishBlogPost: vi.fn(),
    markMessageAsRead: vi.fn(),
    deleteMessage: vi.fn(),
    bulkDeleteMessages: vi.fn(),
    updateContactInfo: vi.fn(),
    updateSystemSettings: vi.fn(),
    uploadImage: vi.fn().mockResolvedValue('/mock-image-url.png'),
    deleteImage: vi.fn(),
    clearError: vi.fn(),
    ...adminContextValue
  };

  function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <BrowserRouter>
        <SharedDataProvider>
          <AdminProvider>
            {children}
          </AdminProvider>
        </SharedDataProvider>
      </BrowserRouter>
    );
  }

  return render(ui, { wrapper: Wrapper, ...renderOptions });
}

// Mock file for testing file uploads
export const createMockFile = (
  name: string = 'test.png',
  size: number = 1024,
  type: string = 'image/png'
): File => {
  const file = new File(['mock content'], name, { type });
  Object.defineProperty(file, 'size', { value: size });
  return file;
};

// Mock drag and drop events
export const createMockDragEvent = (files: File[] = []) => {
  return {
    dataTransfer: {
      files,
      items: files.map(file => ({ kind: 'file', type: file.type, getAsFile: () => file })),
      types: ['Files']
    },
    preventDefault: vi.fn(),
    stopPropagation: vi.fn()
  };
};

// Wait for async operations in tests
export const waitForAsync = () => new Promise(resolve => setTimeout(resolve, 0));

// Mock intersection observer for virtual scrolling tests
export const mockIntersectionObserver = () => {
  const mockIntersectionObserver = vi.fn();
  mockIntersectionObserver.mockReturnValue({
    observe: () => null,
    unobserve: () => null,
    disconnect: () => null
  });
  window.IntersectionObserver = mockIntersectionObserver;
  return mockIntersectionObserver;
};

// Mock performance API for performance tests
export const mockPerformanceAPI = () => {
  const mockPerformance = {
    now: vi.fn(() => Date.now()),
    getEntriesByType: vi.fn(() => []),
    mark: vi.fn(),
    measure: vi.fn(),
    memory: {
      usedJSHeapSize: 1024 * 1024,
      totalJSHeapSize: 2048 * 1024,
      jsHeapSizeLimit: 4096 * 1024
    }
  };
  
  Object.defineProperty(window, 'performance', {
    value: mockPerformance,
    writable: true
  });
  
  return mockPerformance;
};

// Helper to test error boundaries
export const ThrowError = ({ shouldThrow }: { shouldThrow: boolean }) => {
  if (shouldThrow) {
    throw new Error('Test error');
  }
  return <div>No error</div>;
};
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { AdminLogin } from '@/components/admin/pages/AdminLogin';
import { AdminProvider } from '@/contexts/AdminContext';

// Mock the AdminContext
const mockLogin = vi.fn();
const mockAdminContext = {
  user: null,
  login: mockLogin,
  logout: vi.fn(),
  isLoading: false,
  heroContent: {} as any,
  aboutContent: {} as any,
  services: [],
  projects: [],
  blogPosts: [],
  contactMessages: [],
  contactInfo: {} as any,
  systemSettings: {} as any,
  lastError: null,
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
  uploadImage: vi.fn(),
  deleteImage: vi.fn(),
  clearError: vi.fn()
};

vi.mock('@/contexts/AdminContext', () => ({
  AdminProvider: ({ children }: { children: React.ReactNode }) => children,
  useAdmin: () => mockAdminContext
}));

describe('Admin Login Workflow', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should render login form', () => {
    render(
      <BrowserRouter>
        <AdminProvider>
          <AdminLogin />
        </AdminProvider>
      </BrowserRouter>
    );

    expect(screen.getByText(/admin login/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
  });

  it('should handle successful login', async () => {
    mockLogin.mockResolvedValue(true);

    render(
      <BrowserRouter>
        <AdminProvider>
          <AdminLogin />
        </AdminProvider>
      </BrowserRouter>
    );

    const usernameInput = screen.getByLabelText(/username/i);
    const passwordInput = screen.getByLabelText(/password/i);
    const loginButton = screen.getByRole('button', { name: /login/i });

    fireEvent.change(usernameInput, { target: { value: 'admin' } });
    fireEvent.change(passwordInput, { target: { value: 'password' } });
    fireEvent.click(loginButton);

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith('admin', 'password');
    });
  });

  it('should handle login failure', async () => {
    mockLogin.mockResolvedValue(false);

    render(
      <BrowserRouter>
        <AdminProvider>
          <AdminLogin />
        </AdminProvider>
      </BrowserRouter>
    );

    const usernameInput = screen.getByLabelText(/username/i);
    const passwordInput = screen.getByLabelText(/password/i);
    const loginButton = screen.getByRole('button', { name: /login/i });

    fireEvent.change(usernameInput, { target: { value: 'wrong' } });
    fireEvent.change(passwordInput, { target: { value: 'credentials' } });
    fireEvent.click(loginButton);

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith('wrong', 'credentials');
    });
  });
});
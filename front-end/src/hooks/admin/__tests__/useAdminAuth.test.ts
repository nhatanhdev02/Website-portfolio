import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useAdminAuth } from '../useAdminAuth';
import * as AdminContext from '@/contexts/AdminContext';

// Mock the AdminContext
vi.mock('@/contexts/AdminContext', () => ({
  useAdmin: vi.fn()
}));

describe('useAdminAuth', () => {
  const mockLogin = vi.fn();
  const mockLogout = vi.fn();
  
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns authenticated state when user is logged in', () => {
    const mockUser = {
      username: 'testuser',
      isAuthenticated: true,
      loginTime: new Date()
    };

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: mockUser,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.user).toEqual(mockUser);
    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.isLoading).toBe(false);
  });

  it('returns unauthenticated state when user is null', () => {
    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: null,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);
  });

  it('validates session expiry correctly', () => {
    const expiredUser = {
      username: 'testuser',
      isAuthenticated: true,
      loginTime: new Date(Date.now() - 25 * 60 * 60 * 1000) // 25 hours ago
    };

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: expiredUser,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.isSessionValid()).toBe(false);
  });

  it('validates active session correctly', () => {
    const activeUser = {
      username: 'testuser',
      isAuthenticated: true,
      loginTime: new Date(Date.now() - 1 * 60 * 60 * 1000) // 1 hour ago
    };

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: activeUser,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.isSessionValid()).toBe(true);
  });

  it('calls login function correctly', async () => {
    mockLogin.mockResolvedValue(true);

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: null,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    await act(async () => {
      const success = await result.current.login('username', 'password');
      expect(success).toBe(true);
    });

    expect(mockLogin).toHaveBeenCalledWith('username', 'password');
  });

  it('calls logout function correctly', () => {
    const mockUser = {
      username: 'testuser',
      isAuthenticated: true,
      loginTime: new Date()
    };

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: mockUser,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    act(() => {
      result.current.logout();
    });

    expect(mockLogout).toHaveBeenCalledTimes(1);
  });

  it('handles loading state correctly', () => {
    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: null,
      login: mockLogin,
      logout: mockLogout,
      isLoading: true
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.isLoading).toBe(true);
  });

  it('handles user without loginTime', () => {
    const userWithoutLoginTime = {
      username: 'testuser',
      isAuthenticated: true
      // loginTime is missing
    };

    vi.mocked(AdminContext.useAdmin).mockReturnValue({
      user: userWithoutLoginTime,
      login: mockLogin,
      logout: mockLogout,
      isLoading: false
    } as any);

    const { result } = renderHook(() => useAdminAuth());

    expect(result.current.isSessionValid()).toBe(false);
    expect(result.current.isAuthenticated).toBe(false);
  });
});
import { useAdmin } from '@/contexts/AdminContext';
import { AdminUser } from '@/types/admin';

export interface UseAdminAuthReturn {
  user: AdminUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  isSessionValid: () => boolean;
}

export const useAdminAuth = (): UseAdminAuthReturn => {
  const { user, login, logout, isLoading } = useAdmin();

  const isAuthenticated = user?.isAuthenticated ?? false;

  const isSessionValid = (): boolean => {
    if (!user || !user.loginTime) return false;
    
    const loginTime = new Date(user.loginTime);
    const now = new Date();
    const hoursDiff = (now.getTime() - loginTime.getTime()) / (1000 * 60 * 60);
    
    // Session expires after 24 hours
    return hoursDiff < 24;
  };

  return {
    user,
    isAuthenticated: isAuthenticated && isSessionValid(),
    isLoading: isLoading ?? false,
    login,
    logout,
    isSessionValid
  };
};
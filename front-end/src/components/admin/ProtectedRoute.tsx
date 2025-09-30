import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAdminAuth } from '@/hooks/admin/useAdminAuth';
import { AdminLogin } from '@/components/admin/pages/AdminLogin';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { user, isLoading } = useAdminAuth();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-pixel-primary font-pixel">Checking authentication...</div>
      </div>
    );
  }

  if (!user?.isAuthenticated) {
    return <AdminLogin />;
  }

  return <>{children}</>;
};
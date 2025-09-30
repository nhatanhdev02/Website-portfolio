import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAdminAuth } from '@/hooks/admin/useAdminAuth';
import { useAdmin } from '@/contexts/AdminContext';
import { PixelButton, PixelBadge } from '@/components/admin/ui';
import { Bell, Mail } from 'lucide-react';

interface AdminHeaderProps {
  onMenuClick: () => void;
}

export const AdminHeader: React.FC<AdminHeaderProps> = ({ onMenuClick }) => {
  const { user, logout } = useAdminAuth();
  const { contactMessages } = useAdmin();
  const location = useLocation();
  const navigate = useNavigate();

  const handleLogout = () => {
    if (window.confirm('Are you sure you want to logout?')) {
      logout();
    }
  };

  // Get unread message count
  const unreadCount = contactMessages.filter(msg => !msg.read).length;

  const handleNotificationClick = () => {
    navigate('/admin/contact');
  };

  // Get page title based on current route
  const getPageTitle = () => {
    const path = location.pathname;
    if (path.includes('/hero')) return 'Hero Section';
    if (path.includes('/about')) return 'About Management';
    if (path.includes('/services')) return 'Services Management';
    if (path.includes('/portfolio')) return 'Portfolio Management';
    if (path.includes('/blog')) return 'Blog Management';
    if (path.includes('/contact')) return 'Contact Management';
    if (path.includes('/settings')) return 'System Settings';
    return 'Dashboard';
  };

  return (
    <header className="bg-gray-800 border-b-2 border-gray-600 px-4 lg:px-6 py-4">
      <div className="flex items-center justify-between">
        {/* Mobile Menu Button & Page Title */}
        <div className="flex items-center gap-4">
          {/* Mobile Menu Button */}
          <button
            onClick={onMenuClick}
            className="lg:hidden p-2 text-gray-400 hover:text-white border-2 border-gray-600 hover:border-gray-500 bg-gray-700 hover:bg-gray-600 transition-all duration-200 shadow-[0_2px_0_0_#374151] hover:shadow-[0_3px_0_0_#374151] active:translate-y-0.5 active:shadow-[0_1px_0_0_#374151]"
          >
            <span className="text-lg">☰</span>
          </button>

          {/* Page Title Area */}
          <div>
            <h2 className="font-mono text-lg lg:text-xl font-bold text-white">
              {getPageTitle()}
            </h2>
            <p className="font-mono text-xs lg:text-sm text-gray-400 hidden sm:block">
              Manage your portfolio content
            </p>
          </div>
        </div>

        {/* User Info & Actions */}
        <div className="flex items-center gap-2 lg:gap-4">
          {/* Message Notifications */}
          <div className="relative">
            <PixelButton
              variant="secondary"
              size="sm"
              onClick={handleNotificationClick}
              className="relative"
            >
              <Mail className="w-4 h-4" />
              {unreadCount > 0 && (
                <div className="absolute -top-1 -right-1">
                  <PixelBadge variant="danger" size="sm">
                    {unreadCount > 99 ? '99+' : unreadCount}
                  </PixelBadge>
                </div>
              )}
            </PixelButton>
          </div>
          {/* User Info - Hidden on small screens */}
          <div className="hidden md:flex items-center gap-3">
            <div className="w-8 h-8 bg-blue-600 border-2 border-blue-800 flex items-center justify-center shadow-[0_2px_0_0_#1e40af]">
              <span className="text-sm font-mono text-white">👤</span>
            </div>
            <div>
              <p className="font-mono text-sm text-white">{user?.username}</p>
              <p className="font-mono text-xs text-gray-400">Administrator</p>
            </div>
          </div>

          {/* User Avatar for mobile */}
          <div className="md:hidden w-8 h-8 bg-blue-600 border-2 border-blue-800 flex items-center justify-center shadow-[0_2px_0_0_#1e40af]">
            <span className="text-sm font-mono text-white">👤</span>
          </div>

          {/* Logout Button */}
          <PixelButton
            variant="secondary"
            size="sm"
            onClick={handleLogout}
            className="text-xs lg:text-sm"
          >
            <span className="hidden sm:inline">Logout</span>
            <span className="sm:hidden">⏻</span>
          </PixelButton>
        </div>
      </div>
    </header>
  );
};
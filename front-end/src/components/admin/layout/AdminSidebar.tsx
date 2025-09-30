import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';

interface NavItem {
  path: string;
  label: string;
  icon: string;
}

interface AdminSidebarProps {
  isOpen: boolean;
  onClose: () => void;
}

const navItems: NavItem[] = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: '📊' },
  { path: '/admin/hero', label: 'Hero Section', icon: '🏠' },
  { path: '/admin/about', label: 'About', icon: '👤' },
  { path: '/admin/services', label: 'Services', icon: '⚙️' },
  { path: '/admin/portfolio', label: 'Portfolio', icon: '💼' },
  { path: '/admin/blog', label: 'Blog', icon: '📝' },
  { path: '/admin/contact', label: 'Contact', icon: '📧' },
  { path: '/admin/settings', label: 'Settings', icon: '🔧' },
];

export const AdminSidebar: React.FC<AdminSidebarProps> = ({ isOpen, onClose }) => {
  const location = useLocation();

  const handleNavClick = () => {
    // Close sidebar on mobile when navigation item is clicked
    if (window.innerWidth < 1024) {
      onClose();
    }
  };

  return (
    <>
      {/* Desktop Sidebar */}
      <aside className="hidden lg:flex lg:flex-col w-64 bg-gray-800 min-h-screen border-r-2 border-gray-600">
        <SidebarContent 
          navItems={navItems} 
          location={location} 
          onNavClick={handleNavClick}
        />
      </aside>

      {/* Mobile Sidebar */}
      <aside className={cn(
        "fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 border-r-2 border-gray-600 transform transition-transform duration-300 ease-in-out lg:hidden",
        isOpen ? "translate-x-0" : "-translate-x-full"
      )}>
        <SidebarContent 
          navItems={navItems} 
          location={location} 
          onNavClick={handleNavClick}
          showCloseButton
          onClose={onClose}
        />
      </aside>
    </>
  );
};

interface SidebarContentProps {
  navItems: NavItem[];
  location: any;
  onNavClick: () => void;
  showCloseButton?: boolean;
  onClose?: () => void;
}

const SidebarContent: React.FC<SidebarContentProps> = ({ 
  navItems, 
  location, 
  onNavClick, 
  showCloseButton, 
  onClose 
}) => {
  return (
    <div className="flex flex-col h-full">
      {/* Logo/Brand */}
      <div className="p-6 border-b-2 border-gray-600">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-600 border-2 border-blue-800 flex items-center justify-center shadow-[0_2px_0_0_#1e40af]">
              <span className="text-lg font-mono text-white">🎮</span>
            </div>
            <div>
              <h1 className="font-mono text-lg font-bold text-white">Admin</h1>
              <p className="font-mono text-xs text-gray-400">Dashboard</p>
            </div>
          </div>
          
          {/* Close button for mobile */}
          {showCloseButton && (
            <button
              onClick={onClose}
              className="lg:hidden p-1 text-gray-400 hover:text-white border-2 border-gray-600 hover:border-gray-500 bg-gray-700 hover:bg-gray-600 transition-colors duration-200"
            >
              <span className="text-lg">✕</span>
            </button>
          )}
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 p-4 overflow-y-auto">
        <ul className="space-y-2">
          {navItems.map((item) => {
            const isActive = location.pathname === item.path;
            
            return (
              <li key={item.path}>
                <Link
                  to={item.path}
                  onClick={onNavClick}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2 font-mono text-sm transition-all duration-200 border-2 active:translate-y-0.5',
                    isActive
                      ? 'bg-blue-600 border-blue-800 text-white shadow-[0_4px_0_0_#1e40af] active:shadow-[0_2px_0_0_#1e40af]'
                      : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600 hover:border-gray-500 hover:text-white shadow-[0_2px_0_0_#374151] hover:shadow-[0_3px_0_0_#374151] active:shadow-[0_1px_0_0_#374151]'
                  )}
                >
                  <span className="text-base">{item.icon}</span>
                  <span>{item.label}</span>
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>

      {/* Footer */}
      <div className="p-4 border-t-2 border-gray-600 bg-gray-800">
        <div className="text-center">
          <p className="font-mono text-xs text-gray-500 mb-1">
            Admin Panel
          </p>
          <p className="font-mono text-xs text-gray-600">
            v1.0.0
          </p>
        </div>
      </div>
    </div>
  );
};
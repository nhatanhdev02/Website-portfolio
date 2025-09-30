import { useState } from 'react';
import { Home, User, Briefcase, FolderOpen, BookOpen, Mail, Sun, Moon, Languages } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';
import { useTheme } from '@/hooks/useTheme';
import { PixelButton } from './PixelButton';
import { cn } from '@/lib/utils';

interface NavigationProps {
  activeSection: string;
  onSectionChange: (section: string) => void;
}

const navigationItems = [
  { id: 'home', icon: Home, labelKey: 'nav.home' },
  { id: 'about', icon: User, labelKey: 'nav.about' },
  { id: 'services', icon: Briefcase, labelKey: 'nav.services' },
  { id: 'portfolio', icon: FolderOpen, labelKey: 'nav.portfolio' },
  { id: 'blog', icon: BookOpen, labelKey: 'nav.blog' },
  { id: 'contact', icon: Mail, labelKey: 'nav.contact' },
];

export function Navigation({ activeSection, onSectionChange }: NavigationProps) {
  const { t, toggleLanguage, language } = useLanguage();
  const { theme, toggleTheme } = useTheme();
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      {/* Desktop Sidebar */}
      <nav className="hidden md:flex fixed right-0 top-0 h-full w-20 lg:w-64 bg-sidebar border-l-2 border-sidebar-border flex-col items-center lg:items-stretch p-4 z-50">
        <div className="flex flex-col h-full justify-between">
          {/* Logo/Title */}
          <div className="mb-8">
            <div className="w-12 h-12 lg:w-auto lg:h-auto bg-primary flex items-center justify-center border-2 border-primary">
              <span className="text-primary-foreground font-display font-bold text-xl lg:text-2xl">
                <span className="lg:hidden">N</span>
                <span className="hidden lg:inline">Nhật Anh Dev</span>
              </span>
            </div>
          </div>

          {/* Navigation Items */}
          <div className="flex-1 space-y-2">
            {navigationItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeSection === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => onSectionChange(item.id)}
                  className={cn(
                    "w-full h-12 flex items-center justify-center lg:justify-start lg:px-4 gap-3 font-pixel transition-all border-2",
                    isActive
                      ? "bg-sidebar-primary text-sidebar-primary-foreground border-sidebar-primary shadow-pixel"
                      : "bg-sidebar-accent text-sidebar-accent-foreground border-transparent hover:border-sidebar-primary hover:shadow-pixel hover:-translate-y-0.5"
                  )}
                >
                  <Icon size={20} />
                  <span className="hidden lg:inline text-sm">{t(item.labelKey)}</span>
                </button>
              );
            })}
          </div>

          {/* Controls */}
          <div className="space-y-2">
            <PixelButton
              variant="ghost"
              size="icon"
              onClick={toggleTheme}
              className="w-full h-12"
            >
              {theme === 'dark' ? <Sun size={20} /> : <Moon size={20} />}
              <span className="hidden lg:inline ml-2 text-sm">
                {theme === 'dark' ? 'Light' : 'Dark'}
              </span>
            </PixelButton>
            
            <PixelButton
              variant="ghost"
              size="icon"
              onClick={toggleLanguage}
              className="w-full h-12"
            >
              <Languages size={20} />
              <span className="hidden lg:inline ml-2 text-sm">
                {language.toUpperCase()}
              </span>
            </PixelButton>
          </div>
        </div>
      </nav>

      {/* Mobile Footer Navigation */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-sidebar border-t-2 border-sidebar-border p-2 z-50">
        <div className="flex justify-around items-center">
          {navigationItems.map((item) => {
            const Icon = item.icon;
            const isActive = activeSection === item.id;
            return (
              <button
                key={item.id}
                onClick={() => onSectionChange(item.id)}
                className={cn(
                  "p-3 border-2 transition-all",
                  isActive
                    ? "bg-sidebar-primary text-sidebar-primary-foreground border-sidebar-primary"
                    : "bg-sidebar-accent text-sidebar-accent-foreground border-transparent hover:border-sidebar-primary"
                )}
              >
                <Icon size={20} />
              </button>
            );
          })}
        </div>
        
        {/* Mobile Controls */}
        <div className="flex justify-center gap-2 mt-2">
          <PixelButton variant="ghost" size="sm" onClick={toggleTheme}>
            {theme === 'dark' ? <Sun size={16} /> : <Moon size={16} />}
          </PixelButton>
          <PixelButton variant="ghost" size="sm" onClick={toggleLanguage}>
            <Languages size={16} />
          </PixelButton>
        </div>
      </nav>
    </>
  );
}
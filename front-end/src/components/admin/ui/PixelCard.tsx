import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PixelCardProps {
  children: ReactNode;
  title?: string;
  subtitle?: string;
  icon?: string;
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger';
  className?: string;
  onClick?: () => void;
  hoverable?: boolean;
}

export const PixelCard: React.FC<PixelCardProps> = ({
  children,
  title,
  subtitle,
  icon,
  variant = 'default',
  className,
  onClick,
  hoverable = false
}) => {
  const getCardClasses = () => {
    const baseClasses = 'border-2 p-4 transition-all duration-200 font-mono';
    
    const variantClasses = {
      default: 'bg-gray-800 border-gray-600 text-white shadow-[0_4px_0_0_#374151]',
      primary: 'bg-blue-900 border-blue-700 text-white shadow-[0_4px_0_0_#1e40af]',
      success: 'bg-green-900 border-green-700 text-white shadow-[0_4px_0_0_#16a34a]',
      warning: 'bg-yellow-900 border-yellow-700 text-white shadow-[0_4px_0_0_#ca8a04]',
      danger: 'bg-red-900 border-red-700 text-white shadow-[0_4px_0_0_#dc2626]'
    };

    const hoverClasses = hoverable || onClick 
      ? 'hover:translate-y-[-2px] hover:shadow-[0_6px_0_0_var(--shadow-color)] cursor-pointer active:translate-y-0 active:shadow-[0_2px_0_0_var(--shadow-color)]'
      : '';

    return cn(baseClasses, variantClasses[variant], hoverClasses);
  };

  const getShadowColorVar = () => {
    const shadowColors = {
      default: '#374151',
      primary: '#1e40af',
      success: '#16a34a',
      warning: '#ca8a04',
      danger: '#dc2626'
    };
    return { '--shadow-color': shadowColors[variant] } as React.CSSProperties;
  };

  return (
    <div 
      className={cn(getCardClasses(), className)}
      onClick={onClick}
      style={getShadowColorVar()}
    >
      {(title || subtitle || icon) && (
        <div className="mb-3 pb-3 border-b-2 border-gray-600">
          <div className="flex items-center gap-3">
            {icon && (
              <div className="text-2xl">
                {icon}
              </div>
            )}
            <div>
              {title && (
                <h3 className="text-lg font-bold text-white">
                  {title}
                </h3>
              )}
              {subtitle && (
                <p className="text-sm text-gray-400">
                  {subtitle}
                </p>
              )}
            </div>
          </div>
        </div>
      )}
      
      <div className="text-gray-300">
        {children}
      </div>
    </div>
  );
};
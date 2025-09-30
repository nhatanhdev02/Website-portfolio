import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PixelBadgeProps {
  children: ReactNode;
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export const PixelBadge: React.FC<PixelBadgeProps> = ({
  children,
  variant = 'default',
  size = 'md',
  className
}) => {
  const baseClasses = 'inline-flex items-center font-mono font-bold border-2 transition-colors duration-200';
  
  const variantClasses = {
    default: 'bg-gray-700 border-gray-600 text-gray-300',
    primary: 'bg-blue-600 border-blue-800 text-white shadow-[0_2px_0_0_#1e40af]',
    success: 'bg-green-600 border-green-800 text-white shadow-[0_2px_0_0_#16a34a]',
    warning: 'bg-yellow-600 border-yellow-800 text-white shadow-[0_2px_0_0_#ca8a04]',
    danger: 'bg-red-600 border-red-800 text-white shadow-[0_2px_0_0_#dc2626]',
    info: 'bg-cyan-600 border-cyan-800 text-white shadow-[0_2px_0_0_#0891b2]'
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-1.5 text-base'
  };

  return (
    <span className={cn(
      baseClasses,
      variantClasses[variant],
      sizeClasses[size],
      className
    )}>
      {children}
    </span>
  );
};
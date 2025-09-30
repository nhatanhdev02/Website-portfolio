import React, { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

interface PixelButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'info';
  size?: 'sm' | 'md' | 'lg';
  pixelStyle?: boolean;
  loading?: boolean;
  fullWidth?: boolean;
}

export const PixelButton: React.FC<PixelButtonProps> = ({
  children,
  variant = 'primary',
  size = 'md',
  pixelStyle = true,
  loading = false,
  fullWidth = false,
  className,
  disabled,
  ...props
}) => {
  const baseClasses = pixelStyle 
    ? 'font-mono border-2 transition-all duration-100 active:translate-y-0.5 active:shadow-none disabled:opacity-50 disabled:cursor-not-allowed disabled:active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900'
    : 'font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2';

  const variantClasses = {
    primary: pixelStyle
      ? 'bg-blue-600 border-blue-800 text-white shadow-[0_4px_0_0_#1e40af] hover:bg-blue-700 hover:border-blue-900 focus:ring-blue-500'
      : 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    secondary: pixelStyle
      ? 'bg-gray-600 border-gray-800 text-white shadow-[0_4px_0_0_#374151] hover:bg-gray-700 hover:border-gray-900 focus:ring-gray-500'
      : 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    danger: pixelStyle
      ? 'bg-red-600 border-red-800 text-white shadow-[0_4px_0_0_#dc2626] hover:bg-red-700 hover:border-red-900 focus:ring-red-500'
      : 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    success: pixelStyle
      ? 'bg-green-600 border-green-800 text-white shadow-[0_4px_0_0_#16a34a] hover:bg-green-700 hover:border-green-900 focus:ring-green-500'
      : 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    warning: pixelStyle
      ? 'bg-yellow-600 border-yellow-800 text-white shadow-[0_4px_0_0_#ca8a04] hover:bg-yellow-700 hover:border-yellow-900 focus:ring-yellow-500'
      : 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
    info: pixelStyle
      ? 'bg-cyan-600 border-cyan-800 text-white shadow-[0_4px_0_0_#0891b2] hover:bg-cyan-700 hover:border-cyan-900 focus:ring-cyan-500'
      : 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500'
  };

  const sizeClasses = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-base',
    lg: 'px-6 py-3 text-lg'
  };

  const widthClasses = fullWidth ? 'w-full' : '';

  return (
    <button
      className={cn(
        baseClasses,
        variantClasses[variant],
        sizeClasses[size],
        widthClasses,
        className
      )}
      disabled={disabled || loading}
      {...props}
    >
      {loading ? (
        <div className="flex items-center justify-center gap-2">
          <div className="w-4 h-4 border-2 border-white border-t-transparent animate-spin" 
               style={{ borderRadius: '2px' }} />
          <span>Loading...</span>
        </div>
      ) : (
        children
      )}
    </button>
  );
};
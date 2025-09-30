import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface PixelInputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  success?: boolean;
  pixelStyle?: boolean;
  variant?: 'default' | 'search' | 'password';
  helperText?: string;
}

export const PixelInput = forwardRef<HTMLInputElement, PixelInputProps>(
  ({ label, error, success, pixelStyle = true, variant = 'default', helperText, className, ...props }, ref) => {
    const getInputClasses = () => {
      if (!pixelStyle) {
        return 'w-full border border-gray-300 bg-white text-gray-900 px-3 py-2 rounded focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed';
      }

      const baseClasses = 'w-full font-mono border-2 bg-gray-800 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-[inset_0_2px_0_0_rgba(0,0,0,0.3)]';
      
      let borderClasses = 'border-gray-600 hover:border-gray-500 focus:border-blue-500 focus:ring-blue-500';
      
      if (error) {
        borderClasses = 'border-red-600 hover:border-red-500 focus:border-red-400 focus:ring-red-500';
      } else if (success) {
        borderClasses = 'border-green-600 hover:border-green-500 focus:border-green-400 focus:ring-green-500';
      }

      const variantClasses = variant === 'search' 
        ? 'pl-8' // Extra padding for search icon
        : variant === 'password'
        ? 'pr-10' // Extra padding for password toggle
        : '';

      return cn(baseClasses, borderClasses, variantClasses);
    };

    return (
      <div className="space-y-2">
        {label && (
          <label className={cn(
            'block text-sm font-medium',
            pixelStyle ? 'font-mono text-gray-300' : 'text-gray-700'
          )}>
            {label}
            {props.required && <span className="text-red-400 ml-1">*</span>}
          </label>
        )}
        
        <div className="relative">
          {variant === 'search' && (
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span className="text-gray-400 text-sm">🔍</span>
            </div>
          )}
          
          <input
            ref={ref}
            className={cn(getInputClasses(), className)}
            {...props}
          />
          
          {/* Success indicator */}
          {success && !error && (
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
              <span className="text-green-400 text-sm">✓</span>
            </div>
          )}
          
          {/* Error indicator */}
          {error && (
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
              <span className="text-red-400 text-sm">⚠</span>
            </div>
          )}
        </div>
        
        {error && (
          <p className={cn(
            'text-sm text-red-400 flex items-center gap-1',
            pixelStyle && 'font-mono'
          )}>
            <span>⚠</span>
            {error}
          </p>
        )}
        
        {success && !error && (
          <p className={cn(
            'text-sm text-green-400 flex items-center gap-1',
            pixelStyle && 'font-mono'
          )}>
            <span>✓</span>
            Looks good!
          </p>
        )}
        
        {helperText && !error && !success && (
          <p className={cn(
            'text-sm text-gray-500',
            pixelStyle && 'font-mono'
          )}>
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

PixelInput.displayName = 'PixelInput';
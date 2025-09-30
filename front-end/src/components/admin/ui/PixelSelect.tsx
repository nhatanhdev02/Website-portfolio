import { SelectHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface SelectOption {
  value: string;
  label: string;
  disabled?: boolean;
}

interface PixelSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  success?: boolean;
  pixelStyle?: boolean;
  options?: SelectOption[];
  placeholder?: string;
  helperText?: string;
  children?: React.ReactNode;
}

export const PixelSelect = forwardRef<HTMLSelectElement, PixelSelectProps>(
  ({ 
    label, 
    error, 
    success, 
    pixelStyle = true, 
    options, 
    placeholder,
    helperText,
    className,
    children,
    ...props 
  }, ref) => {
    // Ensure options is always an array
    const safeOptions = Array.isArray(options) ? options : [];
    const getSelectClasses = () => {
      if (!pixelStyle) {
        return 'w-full px-3 py-2 bg-white text-gray-900 border border-gray-300 rounded focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
      }

      const baseClasses = 'w-full font-mono px-3 py-2 bg-gray-800 text-white border-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-[inset_0_2px_0_0_rgba(0,0,0,0.3)] appearance-none cursor-pointer';
      
      let borderClasses = 'border-gray-600 hover:border-gray-500 focus:border-blue-500 focus:ring-blue-500';
      
      if (error) {
        borderClasses = 'border-red-600 hover:border-red-500 focus:border-red-400 focus:ring-red-500';
      } else if (success) {
        borderClasses = 'border-green-600 hover:border-green-500 focus:border-green-400 focus:ring-green-500';
      }

      return cn(baseClasses, borderClasses);
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
          <select
            ref={ref}
            className={cn(getSelectClasses(), className)}
            {...props}
          >
            {placeholder && (
              <option value="" disabled>
                {placeholder}
              </option>
            )}
            {children ? (
              children
            ) : safeOptions.length > 0 ? (
              safeOptions.map((option) => (
                <option 
                  key={option.value} 
                  value={option.value}
                  disabled={option.disabled}
                  className="bg-gray-800 text-white"
                >
                  {option.label}
                </option>
              ))
            ) : (
              <option value="" disabled className="bg-gray-800 text-white">
                No options available
              </option>
            )}
          </select>
          
          {/* Custom dropdown arrow */}
          <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <span className="text-gray-400 text-sm">▼</span>
          </div>
          
          {/* Success indicator */}
          {success && !error && (
            <div className="absolute inset-y-0 right-8 flex items-center pr-3 pointer-events-none">
              <span className="text-green-400 text-sm">✓</span>
            </div>
          )}
          
          {/* Error indicator */}
          {error && (
            <div className="absolute inset-y-0 right-8 flex items-center pr-3 pointer-events-none">
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

PixelSelect.displayName = 'PixelSelect';
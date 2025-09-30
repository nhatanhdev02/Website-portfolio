import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface PixelCheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string;
  error?: string;
  pixelStyle?: boolean;
  helperText?: string;
}

export const PixelCheckbox = forwardRef<HTMLInputElement, PixelCheckboxProps>(
  ({ label, error, pixelStyle = true, helperText, className, ...props }, ref) => {
    const getCheckboxClasses = () => {
      if (!pixelStyle) {
        return 'h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-offset-2';
      }

      return 'sr-only'; // Hide the default checkbox
    };

    return (
      <div className="space-y-2">
        <label className={cn(
          'flex items-center gap-3 cursor-pointer',
          pixelStyle ? 'font-mono' : ''
        )}>
          <input
            ref={ref}
            type="checkbox"
            className={cn(getCheckboxClasses(), className)}
            {...props}
          />
          
          {pixelStyle && (
            <div className={cn(
              'w-5 h-5 border-2 flex items-center justify-center transition-all duration-200 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-900',
              props.checked 
                ? 'bg-blue-600 border-blue-800 shadow-[0_2px_0_0_#1e40af] focus-within:ring-blue-500' 
                : 'bg-gray-800 border-gray-600 hover:border-gray-500 focus-within:ring-blue-500',
              error && 'border-red-600 focus-within:ring-red-500'
            )}>
              {props.checked && (
                <span className="text-white text-xs font-bold">✓</span>
              )}
            </div>
          )}
          
          {label && (
            <span className={cn(
              'text-sm',
              pixelStyle ? 'text-gray-300' : 'text-gray-700',
              error && 'text-red-400'
            )}>
              {label}
            </span>
          )}
        </label>
        
        {error && (
          <p className={cn(
            'text-sm text-red-400 flex items-center gap-1 ml-8',
            pixelStyle && 'font-mono'
          )}>
            <span>⚠</span>
            {error}
          </p>
        )}
        
        {helperText && !error && (
          <p className={cn(
            'text-sm text-gray-500 ml-8',
            pixelStyle && 'font-mono'
          )}>
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

PixelCheckbox.displayName = 'PixelCheckbox';
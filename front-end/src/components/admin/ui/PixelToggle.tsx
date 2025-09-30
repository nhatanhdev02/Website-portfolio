import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface PixelToggleProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string;
  error?: string;
  pixelStyle?: boolean;
  helperText?: string;
  size?: 'sm' | 'md' | 'lg';
}

export const PixelToggle = forwardRef<HTMLInputElement, PixelToggleProps>(
  ({ label, error, pixelStyle = true, helperText, size = 'md', className, ...props }, ref) => {
    const getToggleClasses = () => {
      if (!pixelStyle) {
        return 'sr-only';
      }

      return 'sr-only'; // Hide the default checkbox
    };

    const getSizeClasses = () => {
      switch (size) {
        case 'sm':
          return {
            track: 'w-8 h-4',
            thumb: 'w-3 h-3',
            translate: 'translate-x-4'
          };
        case 'lg':
          return {
            track: 'w-12 h-6',
            thumb: 'w-5 h-5',
            translate: 'translate-x-6'
          };
        default:
          return {
            track: 'w-10 h-5',
            thumb: 'w-4 h-4',
            translate: 'translate-x-5'
          };
      }
    };

    const sizeClasses = getSizeClasses();

    return (
      <div className="space-y-2">
        <label className={cn(
          'flex items-center gap-3 cursor-pointer',
          pixelStyle ? 'font-mono' : ''
        )}>
          <input
            ref={ref}
            type="checkbox"
            className={cn(getToggleClasses(), className)}
            {...props}
          />
          
          {pixelStyle && (
            <div className={cn(
              'relative border-2 transition-all duration-200 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-900',
              sizeClasses.track,
              props.checked 
                ? 'bg-blue-600 border-blue-800 shadow-[0_2px_0_0_#1e40af] focus-within:ring-blue-500' 
                : 'bg-gray-800 border-gray-600 hover:border-gray-500 focus-within:ring-blue-500',
              error && 'border-red-600 focus-within:ring-red-500'
            )}>
              <div className={cn(
                'absolute top-0.5 left-0.5 bg-white border border-gray-400 transition-transform duration-200',
                sizeClasses.thumb,
                props.checked && sizeClasses.translate
              )} />
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
            'text-sm text-red-400 flex items-center gap-1 ml-14',
            pixelStyle && 'font-mono'
          )}>
            <span>⚠</span>
            {error}
          </p>
        )}
        
        {helperText && !error && (
          <p className={cn(
            'text-sm text-gray-500 ml-14',
            pixelStyle && 'font-mono'
          )}>
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

PixelToggle.displayName = 'PixelToggle';
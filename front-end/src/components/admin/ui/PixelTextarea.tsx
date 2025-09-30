import { TextareaHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface PixelTextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  error?: string;
  success?: boolean;
  pixelStyle?: boolean;
  showCharCount?: boolean;
  maxLength?: number;
  helperText?: string;
}

export const PixelTextarea = forwardRef<HTMLTextAreaElement, PixelTextareaProps>(
  ({ 
    label, 
    error, 
    success, 
    pixelStyle = true, 
    showCharCount = false,
    maxLength,
    helperText,
    className, 
    value,
    ...props 
  }, ref) => {
    const getTextareaClasses = () => {
      if (!pixelStyle) {
        return 'w-full px-3 py-2 bg-white text-gray-900 border border-gray-300 rounded focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors resize-vertical disabled:opacity-50 disabled:cursor-not-allowed';
      }

      const baseClasses = 'w-full font-mono px-3 py-2 bg-gray-800 text-white border-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 transition-all duration-200 resize-vertical disabled:opacity-50 disabled:cursor-not-allowed shadow-[inset_0_2px_0_0_rgba(0,0,0,0.3)] min-h-[80px]';
      
      let borderClasses = 'border-gray-600 hover:border-gray-500 focus:border-blue-500 focus:ring-blue-500';
      
      if (error) {
        borderClasses = 'border-red-600 hover:border-red-500 focus:border-red-400 focus:ring-red-500';
      } else if (success) {
        borderClasses = 'border-green-600 hover:border-green-500 focus:border-green-400 focus:ring-green-500';
      }

      return cn(baseClasses, borderClasses);
    };

    const charCount = typeof value === 'string' ? value.length : 0;
    const isNearLimit = maxLength && charCount > maxLength * 0.8;
    const isOverLimit = maxLength && charCount > maxLength;

    return (
      <div className="space-y-2">
        {label && (
          <div className="flex items-center justify-between">
            <label className={cn(
              'block text-sm font-medium',
              pixelStyle ? 'font-mono text-gray-300' : 'text-gray-700'
            )}>
              {label}
              {props.required && <span className="text-red-400 ml-1">*</span>}
            </label>
            
            {showCharCount && maxLength && (
              <span className={cn(
                'text-xs',
                pixelStyle ? 'font-mono' : '',
                isOverLimit ? 'text-red-400' : isNearLimit ? 'text-yellow-400' : 'text-gray-500'
              )}>
                {charCount}/{maxLength}
              </span>
            )}
          </div>
        )}
        
        <div className="relative">
          <textarea
            ref={ref}
            className={cn(getTextareaClasses(), className)}
            value={value}
            maxLength={maxLength}
            {...props}
          />
          
          {/* Success indicator */}
          {success && !error && (
            <div className="absolute top-2 right-2">
              <span className="text-green-400 text-sm">✓</span>
            </div>
          )}
          
          {/* Error indicator */}
          {error && (
            <div className="absolute top-2 right-2">
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
        
        {showCharCount && !maxLength && (
          <p className={cn(
            'text-xs text-gray-500',
            pixelStyle && 'font-mono'
          )}>
            {charCount} characters
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

PixelTextarea.displayName = 'PixelTextarea';
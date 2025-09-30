import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PixelAlertProps {
  children?: ReactNode;
  type?: 'default' | 'success' | 'warning' | 'danger' | 'info' | 'error';
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info' | 'error';
  title?: string;
  message?: string;
  dismissible?: boolean;
  onClose?: () => void;
  onDismiss?: () => void;
  className?: string;
  pixelStyle?: boolean;
}

export const PixelAlert: React.FC<PixelAlertProps> = ({
  children,
  type,
  variant,
  title,
  message,
  dismissible = true,
  onClose,
  onDismiss,
  className,
  pixelStyle = true
}) => {
  // Use type or variant, with type taking precedence
  const alertVariant = type || variant || 'default';
  // Map 'error' to 'danger' for consistency
  const normalizedVariant = alertVariant === 'error' ? 'danger' : alertVariant;
  
  const handleDismiss = onClose || onDismiss;
  const getAlertClasses = () => {
    if (!pixelStyle) {
      const baseClasses = 'p-4 rounded border';
      const variantClasses = {
        default: 'bg-gray-50 border-gray-200 text-gray-800',
        success: 'bg-green-50 border-green-200 text-green-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        danger: 'bg-red-50 border-red-200 text-red-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800'
      };
      return cn(baseClasses, variantClasses[variant]);
    }

    const baseClasses = 'p-4 border-2 font-mono shadow-[0_4px_0_0_var(--shadow-color)]';
    const variantClasses = {
      default: 'bg-gray-800 border-gray-600 text-gray-300',
      success: 'bg-green-900 border-green-700 text-green-300',
      warning: 'bg-yellow-900 border-yellow-700 text-yellow-300',
      danger: 'bg-red-900 border-red-700 text-red-300',
      info: 'bg-blue-900 border-blue-700 text-blue-300'
    };
    return cn(baseClasses, variantClasses[normalizedVariant]);
  };

  const getIconForVariant = () => {
    const icons = {
      default: 'ℹ',
      success: '✓',
      warning: '⚠',
      danger: '✕',
      info: 'ℹ'
    };
    return icons[normalizedVariant];
  };

  const getShadowColorVar = () => {
    const shadowColors = {
      default: '#374151',
      success: '#16a34a',
      warning: '#ca8a04',
      danger: '#dc2626',
      info: '#1e40af'
    };
    return { '--shadow-color': shadowColors[normalizedVariant] } as React.CSSProperties;
  };

  return (
    <div 
      className={cn(getAlertClasses(), className)}
      style={pixelStyle ? getShadowColorVar() : undefined}
    >
      <div className="flex items-start gap-3">
        {pixelStyle && (
          <div className="flex-shrink-0 text-lg">
            {getIconForVariant()}
          </div>
        )}
        
        <div className="flex-1">
          {title && (
            <h4 className={cn(
              'font-bold mb-2',
              pixelStyle ? 'text-white' : 'text-current'
            )}>
              {title}
            </h4>
          )}
          
          <div className={cn(
            pixelStyle ? 'text-gray-300' : 'text-current'
          )}>
            {message || children}
          </div>
        </div>
        
        {dismissible && handleDismiss && (
          <button
            onClick={handleDismiss}
            className={cn(
              'flex-shrink-0 text-lg hover:opacity-75 transition-opacity duration-200',
              pixelStyle ? 'text-gray-400 hover:text-white' : 'text-current'
            )}
            aria-label="Dismiss alert"
          >
            ✕
          </button>
        )}
      </div>
    </div>
  );
};
import React, { useState, useEffect } from 'react';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { 
  CheckCircle, 
  AlertCircle, 
  Info, 
  X, 
  Save, 
  Undo,
  Eye,
  Clock
} from 'lucide-react';

export interface DataChangeNotificationData {
  id: string;
  type: 'success' | 'warning' | 'info' | 'error';
  title: string;
  message: string;
  timestamp: Date;
  actions?: Array<{
    label: string;
    action: () => void;
    variant?: 'primary' | 'secondary' | 'danger';
  }>;
  autoClose?: boolean;
  duration?: number;
}

interface DataChangeNotificationProps {
  notification: DataChangeNotificationData;
  onClose: (id: string) => void;
  className?: string;
}

export const DataChangeNotification: React.FC<DataChangeNotificationProps> = ({
  notification,
  onClose,
  className = ''
}) => {
  const [isVisible, setIsVisible] = useState(true);
  const [timeLeft, setTimeLeft] = useState(notification.duration || 5000);

  useEffect(() => {
    if (notification.autoClose !== false) {
      const timer = setInterval(() => {
        setTimeLeft(prev => {
          if (prev <= 100) {
            setIsVisible(false);
            setTimeout(() => onClose(notification.id), 300);
            return 0;
          }
          return prev - 100;
        });
      }, 100);

      return () => clearInterval(timer);
    }
  }, [notification.autoClose, notification.duration, notification.id, onClose]);

  const getIcon = () => {
    switch (notification.type) {
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-500" />;
      case 'warning':
        return <AlertCircle className="w-5 h-5 text-yellow-500" />;
      case 'error':
        return <AlertCircle className="w-5 h-5 text-red-500" />;
      case 'info':
      default:
        return <Info className="w-5 h-5 text-blue-500" />;
    }
  };

  const getBorderColor = () => {
    switch (notification.type) {
      case 'success':
        return 'border-green-500/30';
      case 'warning':
        return 'border-yellow-500/30';
      case 'error':
        return 'border-red-500/30';
      case 'info':
      default:
        return 'border-blue-500/30';
    }
  };

  const getBackgroundColor = () => {
    switch (notification.type) {
      case 'success':
        return 'bg-green-500/10';
      case 'warning':
        return 'bg-yellow-500/10';
      case 'error':
        return 'bg-red-500/10';
      case 'info':
      default:
        return 'bg-blue-500/10';
    }
  };

  const formatTime = (timestamp: Date) => {
    return timestamp.toLocaleTimeString('en-US', {
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  };

  return (
    <div
      className={`
        transform transition-all duration-300 ease-in-out
        ${isVisible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'}
        ${className}
      `}
    >
      <PixelCard className={`${getBorderColor()} ${getBackgroundColor()}`}>
        <div className="p-4">
          {/* Header */}
          <div className="flex items-start justify-between mb-2">
            <div className="flex items-center gap-2">
              {getIcon()}
              <h4 className="font-pixel font-bold text-foreground">
                {notification.title}
              </h4>
            </div>
            
            <div className="flex items-center gap-2">
              <div className="flex items-center gap-1 text-xs font-pixel text-muted-foreground">
                <Clock className="w-3 h-3" />
                {formatTime(notification.timestamp)}
              </div>
              <button
                onClick={() => onClose(notification.id)}
                className="text-muted-foreground hover:text-foreground transition-colors p-1"
                title="Close notification"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Message */}
          <p className="font-pixel text-sm text-muted-foreground mb-3">
            {notification.message}
          </p>

          {/* Actions */}
          {notification.actions && notification.actions.length > 0 && (
            <div className="flex items-center gap-2 flex-wrap">
              {notification.actions.map((action, index) => (
                <PixelButton
                  key={index}
                  variant={action.variant || 'secondary'}
                  size="sm"
                  onClick={action.action}
                  className="text-xs"
                >
                  {action.label}
                </PixelButton>
              ))}
            </div>
          )}

          {/* Progress Bar */}
          {notification.autoClose !== false && (
            <div className="mt-3 w-full bg-muted/30 rounded-full h-1 overflow-hidden">
              <div
                className="h-full bg-primary transition-all duration-100 ease-linear"
                style={{
                  width: `${((notification.duration || 5000) - timeLeft) / (notification.duration || 5000) * 100}%`
                }}
              />
            </div>
          )}
        </div>
      </PixelCard>
    </div>
  );
};

// Notification Manager Component
interface NotificationManagerProps {
  className?: string;
}

export const NotificationManager: React.FC<NotificationManagerProps> = ({
  className = ''
}) => {
  const [notifications, setNotifications] = useState<DataChangeNotificationData[]>([]);

  useEffect(() => {
    const handleNotification = (event: CustomEvent<DataChangeNotificationData>) => {
      const notification = event.detail;
      setNotifications(prev => [...prev, notification]);
    };

    window.addEventListener('showNotification' as any, handleNotification);

    return () => {
      window.removeEventListener('showNotification' as any, handleNotification);
    };
  }, []);

  const removeNotification = (id: string) => {
    setNotifications(prev => prev.filter(n => n.id !== id));
  };

  return (
    <div className={`fixed top-4 right-4 z-50 space-y-2 max-w-md ${className}`}>
      {notifications.map(notification => (
        <DataChangeNotification
          key={notification.id}
          notification={notification}
          onClose={removeNotification}
        />
      ))}
    </div>
  );
};

// Confirmation Dialog Component
interface ConfirmationDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  type?: 'info' | 'warning' | 'danger';
  showPreview?: boolean;
  previewData?: any;
}

export const ConfirmationDialog: React.FC<ConfirmationDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  type = 'info',
  showPreview = false,
  previewData
}) => {
  if (!isOpen) return null;

  const getIcon = () => {
    switch (type) {
      case 'warning':
        return <AlertCircle className="w-6 h-6 text-yellow-500" />;
      case 'danger':
        return <AlertCircle className="w-6 h-6 text-red-500" />;
      case 'info':
      default:
        return <Info className="w-6 h-6 text-blue-500" />;
    }
  };

  const getConfirmButtonVariant = () => {
    switch (type) {
      case 'danger':
        return 'danger' as const;
      case 'warning':
        return 'secondary' as const;
      case 'info':
      default:
        return 'primary' as const;
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      {/* Backdrop */}
      <div 
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
      />
      
      {/* Dialog */}
      <PixelCard className="relative max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div className="p-6">
          {/* Header */}
          <div className="flex items-start gap-3 mb-4">
            {getIcon()}
            <div className="flex-1">
              <h3 className="font-pixel font-bold text-lg text-foreground mb-2">
                {title}
              </h3>
              <p className="font-pixel text-sm text-muted-foreground">
                {message}
              </p>
            </div>
          </div>

          {/* Preview */}
          {showPreview && previewData && (
            <div className="mb-4 p-3 bg-muted/30 border border-border rounded">
              <div className="flex items-center gap-2 mb-2">
                <Eye className="w-4 h-4 text-primary" />
                <span className="font-pixel text-sm font-bold text-primary">Preview Changes</span>
              </div>
              <pre className="font-mono text-xs text-muted-foreground overflow-x-auto">
                {JSON.stringify(previewData, null, 2)}
              </pre>
            </div>
          )}

          {/* Actions */}
          <div className="flex items-center gap-3 justify-end">
            <PixelButton
              variant="secondary"
              onClick={onClose}
            >
              {cancelText}
            </PixelButton>
            <PixelButton
              variant={getConfirmButtonVariant()}
              onClick={() => {
                onConfirm();
                onClose();
              }}
            >
              {confirmText}
            </PixelButton>
          </div>
        </div>
      </PixelCard>
    </div>
  );
};

// Utility functions for showing notifications
export const showNotification = (notification: Omit<DataChangeNotificationData, 'id' | 'timestamp'>) => {
  const fullNotification: DataChangeNotificationData = {
    ...notification,
    id: Date.now().toString(),
    timestamp: new Date()
  };

  const event = new CustomEvent('showNotification', {
    detail: fullNotification
  });

  window.dispatchEvent(event);
};

export const showSuccessNotification = (title: string, message: string, actions?: DataChangeNotificationData['actions']) => {
  showNotification({
    type: 'success',
    title,
    message,
    actions
  });
};

export const showErrorNotification = (title: string, message: string, actions?: DataChangeNotificationData['actions']) => {
  showNotification({
    type: 'error',
    title,
    message,
    actions,
    autoClose: false
  });
};

export const showWarningNotification = (title: string, message: string, actions?: DataChangeNotificationData['actions']) => {
  showNotification({
    type: 'warning',
    title,
    message,
    actions,
    duration: 8000
  });
};

export const showInfoNotification = (title: string, message: string, actions?: DataChangeNotificationData['actions']) => {
  showNotification({
    type: 'info',
    title,
    message,
    actions
  });
};
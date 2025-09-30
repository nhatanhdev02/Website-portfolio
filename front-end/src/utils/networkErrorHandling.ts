import { showErrorNotification, showWarningNotification, showInfoNotification } from '@/components/admin/ui/DataChangeNotification';

// Network error types
export interface NetworkError extends Error {
  code?: string;
  status?: number;
  retryable?: boolean;
  timestamp?: Date;
}

// Retry configuration
export interface RetryConfig {
  maxAttempts: number;
  baseDelay: number; // in milliseconds
  maxDelay: number;
  backoffMultiplier: number;
  retryableErrors: string[];
}

// Default retry configuration
const defaultRetryConfig: RetryConfig = {
  maxAttempts: 3,
  baseDelay: 1000,
  maxDelay: 10000,
  backoffMultiplier: 2,
  retryableErrors: ['NETWORK_ERROR', 'TIMEOUT', 'SERVER_ERROR', 'RATE_LIMITED']
};

// Network status monitoring
class NetworkStatusMonitor {
  private isOnline: boolean = navigator.onLine;
  private listeners: Array<(isOnline: boolean) => void> = [];
  private reconnectAttempts: number = 0;
  private maxReconnectAttempts: number = 5;
  private reconnectDelay: number = 2000;

  constructor() {
    this.setupEventListeners();
  }

  private setupEventListeners() {
    window.addEventListener('online', this.handleOnline);
    window.addEventListener('offline', this.handleOffline);
  }

  private handleOnline = () => {
    this.isOnline = true;
    this.reconnectAttempts = 0;
    this.notifyListeners(true);
    
    showInfoNotification(
      'Connection Restored',
      'Internet connection has been restored. Syncing data...'
    );
  };

  private handleOffline = () => {
    this.isOnline = false;
    this.notifyListeners(false);
    
    showWarningNotification(
      'Connection Lost',
      'Internet connection lost. Changes will be saved locally and synced when connection is restored.',
      [{
        label: 'Retry Connection',
        action: () => this.attemptReconnect(),
        variant: 'secondary'
      }]
    );
  };

  private notifyListeners(isOnline: boolean) {
    this.listeners.forEach(listener => listener(isOnline));
  }

  public addListener(listener: (isOnline: boolean) => void) {
    this.listeners.push(listener);
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener);
    };
  }

  public getStatus(): boolean {
    return this.isOnline;
  }

  public attemptReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      showErrorNotification(
        'Connection Failed',
        'Unable to restore connection after multiple attempts. Please check your internet connection.'
      );
      return;
    }

    this.reconnectAttempts++;
    
    showInfoNotification(
      'Reconnecting...',
      `Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`
    );

    setTimeout(() => {
      // Simulate connection test
      fetch('/favicon.ico', { method: 'HEAD', cache: 'no-cache' })
        .then(() => {
          this.handleOnline();
        })
        .catch(() => {
          if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.attemptReconnect();
          } else {
            showErrorNotification(
              'Connection Failed',
              'Unable to restore connection. Please check your internet connection and try again.'
            );
          }
        });
    }, this.reconnectDelay * this.reconnectAttempts);
  }

  public destroy() {
    window.removeEventListener('online', this.handleOnline);
    window.removeEventListener('offline', this.handleOffline);
    this.listeners = [];
  }
}

// Global network monitor instance
export const networkMonitor = new NetworkStatusMonitor();

// Retry utility with exponential backoff
export const withRetry = async <T>(
  operation: () => Promise<T>,
  config: Partial<RetryConfig> = {}
): Promise<T> => {
  const finalConfig = { ...defaultRetryConfig, ...config };
  let lastError: NetworkError;
  
  for (let attempt = 1; attempt <= finalConfig.maxAttempts; attempt++) {
    try {
      return await operation();
    } catch (error) {
      lastError = error as NetworkError;
      
      // Check if error is retryable
      if (!isRetryableError(lastError, finalConfig.retryableErrors)) {
        throw lastError;
      }
      
      // Don't retry on last attempt
      if (attempt === finalConfig.maxAttempts) {
        break;
      }
      
      // Calculate delay with exponential backoff
      const delay = Math.min(
        finalConfig.baseDelay * Math.pow(finalConfig.backoffMultiplier, attempt - 1),
        finalConfig.maxDelay
      );
      
      // Add jitter to prevent thundering herd
      const jitteredDelay = delay + Math.random() * 1000;
      
      console.warn(`Attempt ${attempt} failed, retrying in ${jitteredDelay}ms:`, lastError);
      
      // Show retry notification
      if (attempt > 1) {
        showWarningNotification(
          'Retrying Operation',
          `Attempt ${attempt} failed. Retrying in ${Math.round(jitteredDelay / 1000)} seconds...`
        );
      }
      
      await new Promise(resolve => setTimeout(resolve, jitteredDelay));
    }
  }
  
  throw lastError;
};

// Check if error is retryable
const isRetryableError = (error: NetworkError, retryableErrors: string[]): boolean => {
  // Network offline
  if (!networkMonitor.getStatus()) {
    return true;
  }
  
  // Check error code
  if (error.code && retryableErrors.includes(error.code)) {
    return true;
  }
  
  // Check HTTP status codes
  if (error.status) {
    const retryableStatusCodes = [408, 429, 500, 502, 503, 504];
    return retryableStatusCodes.includes(error.status);
  }
  
  // Check error message patterns
  const retryablePatterns = [
    /network/i,
    /timeout/i,
    /connection/i,
    /fetch/i,
    /cors/i
  ];
  
  return retryablePatterns.some(pattern => pattern.test(error.message));
};

// Enhanced fetch with retry and error handling
export const fetchWithRetry = async (
  url: string,
  options: RequestInit = {},
  retryConfig?: Partial<RetryConfig>
): Promise<Response> => {
  return withRetry(async () => {
    // Check network status
    if (!networkMonitor.getStatus()) {
      const networkError: NetworkError = new Error('Network is offline') as NetworkError;
      networkError.code = 'NETWORK_OFFLINE';
      networkError.retryable = true;
      throw networkError;
    }
    
    // Set timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
    
    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal
      });
      
      clearTimeout(timeoutId);
      
      // Handle HTTP errors
      if (!response.ok) {
        const error: NetworkError = new Error(`HTTP ${response.status}: ${response.statusText}`) as NetworkError;
        error.status = response.status;
        error.retryable = response.status >= 500 || response.status === 429;
        throw error;
      }
      
      return response;
    } catch (error) {
      clearTimeout(timeoutId);
      
      if (error instanceof Error) {
        const networkError = error as NetworkError;
        
        // Handle abort/timeout
        if (error.name === 'AbortError') {
          networkError.code = 'TIMEOUT';
          networkError.retryable = true;
        }
        
        // Handle network errors
        if (error.message.includes('fetch')) {
          networkError.code = 'NETWORK_ERROR';
          networkError.retryable = true;
        }
        
        networkError.timestamp = new Date();
        throw networkError;
      }
      
      throw error;
    }
  }, retryConfig);
};

// Local storage operations with error handling
export const safeLocalStorageOperation = <T>(
  operation: () => T,
  fallback: T,
  errorContext: string = 'localStorage operation'
): T => {
  try {
    return operation();
  } catch (error) {
    console.error(`${errorContext} failed:`, error);
    
    // Check if localStorage is available
    if (!isLocalStorageAvailable()) {
      showErrorNotification(
        'Storage Unavailable',
        'Local storage is not available. Changes may not be saved.',
        [{
          label: 'Learn More',
          action: () => window.open('https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API/Using_the_Web_Storage_API', '_blank'),
          variant: 'secondary'
        }]
      );
    } else if (isStorageQuotaExceeded(error as Error)) {
      showErrorNotification(
        'Storage Full',
        'Local storage is full. Please clear some data or use a different browser.',
        [{
          label: 'Clear Old Data',
          action: () => clearOldStorageData(),
          variant: 'danger'
        }]
      );
    } else {
      showErrorNotification(
        'Storage Error',
        `Failed to ${errorContext}: ${error instanceof Error ? error.message : 'Unknown error'}`
      );
    }
    
    return fallback;
  }
};

// Check if localStorage is available
const isLocalStorageAvailable = (): boolean => {
  try {
    const test = '__localStorage_test__';
    localStorage.setItem(test, test);
    localStorage.removeItem(test);
    return true;
  } catch {
    return false;
  }
};

// Check if error is due to storage quota exceeded
const isStorageQuotaExceeded = (error: Error): boolean => {
  return error.name === 'QuotaExceededError' ||
         error.message.includes('quota') ||
         error.message.includes('storage');
};

// Clear old storage data
const clearOldStorageData = () => {
  try {
    // Remove old backups (keep only latest 2)
    const keys = Object.keys(localStorage);
    const backupKeys = keys.filter(key => key.includes('_backup_')).sort().reverse();
    
    if (backupKeys.length > 2) {
      backupKeys.slice(2).forEach(key => {
        localStorage.removeItem(key);
      });
    }
    
    // Remove error reports older than 7 days
    const errorReports = JSON.parse(localStorage.getItem('admin_error_reports') || '[]');
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    const recentReports = errorReports.filter((report: any) => 
      new Date(report.timestamp) > weekAgo
    );
    
    localStorage.setItem('admin_error_reports', JSON.stringify(recentReports));
    
    showInfoNotification(
      'Storage Cleaned',
      'Old data has been cleared to free up storage space.'
    );
  } catch (error) {
    console.error('Failed to clear old storage data:', error);
  }
};

// Data corruption recovery
export const recoverCorruptedData = <T>(
  corruptedData: unknown,
  backupKey: string,
  defaultData: T,
  validator: (data: unknown) => boolean
): T => {
  try {
    // Try to recover from backup
    const backupData = localStorage.getItem(backupKey);
    if (backupData) {
      const parsed = JSON.parse(backupData);
      if (validator(parsed)) {
        showWarningNotification(
          'Data Recovered',
          'Corrupted data was recovered from backup.',
          [{
            label: 'View Details',
            action: () => console.log('Recovered data:', parsed),
            variant: 'secondary'
          }]
        );
        return parsed;
      }
    }
    
    // Try to partially recover data
    if (typeof corruptedData === 'object' && corruptedData !== null) {
      const merged = { ...defaultData, ...corruptedData };
      if (validator(merged)) {
        showWarningNotification(
          'Data Partially Recovered',
          'Some data was recovered, but some fields were reset to defaults.'
        );
        return merged;
      }
    }
    
    // Fall back to default data
    showErrorNotification(
      'Data Reset',
      'Data was corrupted and could not be recovered. Reset to defaults.',
      [{
        label: 'Report Issue',
        action: () => {
          const errorReport = {
            type: 'data_corruption',
            corruptedData,
            timestamp: new Date().toISOString()
          };
          console.error('Data corruption report:', errorReport);
        },
        variant: 'secondary'
      }]
    );
    
    return defaultData;
  } catch (error) {
    console.error('Data recovery failed:', error);
    return defaultData;
  }
};

// Hook for network status
export const useNetworkStatus = () => {
  const [isOnline, setIsOnline] = React.useState(networkMonitor.getStatus());
  
  React.useEffect(() => {
    const unsubscribe = networkMonitor.addListener(setIsOnline);
    return unsubscribe;
  }, []);
  
  return {
    isOnline,
    attemptReconnect: () => networkMonitor.attemptReconnect()
  };
};
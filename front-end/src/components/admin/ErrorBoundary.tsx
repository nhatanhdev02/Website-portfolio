import React, { Component, ErrorInfo, ReactNode } from 'react';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { AlertTriangle, RefreshCw, Home, Bug } from 'lucide-react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
  errorId: string;
}

export class AdminErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
      errorId: ''
    };
  }

  static getDerivedStateFromError(error: Error): Partial<State> {
    return {
      hasError: true,
      error,
      errorId: Date.now().toString()
    };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    this.setState({
      error,
      errorInfo
    });

    // Log error to console
    console.error('Admin Error Boundary caught an error:', error, errorInfo);

    // Call custom error handler if provided
    this.props.onError?.(error, errorInfo);

    // Report error to monitoring service (if available)
    this.reportError(error, errorInfo);
  }

  private reportError = (error: Error, errorInfo: ErrorInfo) => {
    try {
      // Store error in localStorage for debugging
      const errorReport = {
        timestamp: new Date().toISOString(),
        error: {
          name: error.name,
          message: error.message,
          stack: error.stack
        },
        errorInfo: {
          componentStack: errorInfo.componentStack
        },
        userAgent: navigator.userAgent,
        url: window.location.href,
        userId: 'admin' // In a real app, get from auth context
      };

      const existingErrors = JSON.parse(localStorage.getItem('admin_error_reports') || '[]');
      existingErrors.push(errorReport);
      
      // Keep only last 10 error reports
      if (existingErrors.length > 10) {
        existingErrors.splice(0, existingErrors.length - 10);
      }
      
      localStorage.setItem('admin_error_reports', JSON.stringify(existingErrors));
    } catch (reportingError) {
      console.error('Failed to report error:', reportingError);
    }
  };

  private handleRetry = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
      errorId: ''
    });
  };

  private handleGoHome = () => {
    window.location.href = '/admin';
  };

  private handleReload = () => {
    window.location.reload();
  };

  private copyErrorDetails = () => {
    const errorDetails = {
      error: this.state.error?.message,
      stack: this.state.error?.stack,
      componentStack: this.state.errorInfo?.componentStack,
      timestamp: new Date().toISOString(),
      url: window.location.href
    };

    navigator.clipboard.writeText(JSON.stringify(errorDetails, null, 2))
      .then(() => {
        alert('Error details copied to clipboard');
      })
      .catch(() => {
        console.error('Failed to copy error details');
      });
  };

  render() {
    if (this.state.hasError) {
      // Custom fallback UI
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4">
          <PixelCard className="max-w-2xl w-full">
            <div className="p-8 text-center">
              {/* Error Icon */}
              <div className="mb-6">
                <AlertTriangle className="w-16 h-16 text-red-500 mx-auto mb-4" />
                <h1 className="font-display text-3xl font-bold text-foreground mb-2">
                  Oops! Something went wrong
                </h1>
                <p className="font-pixel text-muted-foreground">
                  The admin panel encountered an unexpected error. Don't worry, your data is safe.
                </p>
              </div>

              {/* Error Details */}
              <div className="mb-6 text-left">
                <details className="bg-muted/30 border border-border rounded p-4">
                  <summary className="font-pixel font-bold text-sm cursor-pointer mb-2">
                    Error Details (Click to expand)
                  </summary>
                  <div className="space-y-2 text-xs font-mono">
                    <div>
                      <strong>Error ID:</strong> {this.state.errorId}
                    </div>
                    <div>
                      <strong>Message:</strong> {this.state.error?.message}
                    </div>
                    <div>
                      <strong>Type:</strong> {this.state.error?.name}
                    </div>
                    {this.state.error?.stack && (
                      <div>
                        <strong>Stack Trace:</strong>
                        <pre className="mt-1 p-2 bg-background border border-border rounded text-xs overflow-x-auto">
                          {this.state.error.stack}
                        </pre>
                      </div>
                    )}
                  </div>
                </details>
              </div>

              {/* Action Buttons */}
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <PixelButton
                  variant="primary"
                  onClick={this.handleRetry}
                  className="flex items-center gap-2"
                >
                  <RefreshCw className="w-4 h-4" />
                  Try Again
                </PixelButton>

                <PixelButton
                  variant="secondary"
                  onClick={this.handleGoHome}
                  className="flex items-center gap-2"
                >
                  <Home className="w-4 h-4" />
                  Go to Dashboard
                </PixelButton>

                <PixelButton
                  variant="secondary"
                  onClick={this.handleReload}
                  className="flex items-center gap-2"
                >
                  <RefreshCw className="w-4 h-4" />
                  Reload Page
                </PixelButton>

                <PixelButton
                  variant="secondary"
                  onClick={this.copyErrorDetails}
                  className="flex items-center gap-2"
                >
                  <Bug className="w-4 h-4" />
                  Copy Error Details
                </PixelButton>
              </div>

              {/* Help Text */}
              <div className="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded">
                <p className="font-pixel text-sm text-blue-600">
                  <strong>Need help?</strong> If this error persists, try clearing your browser cache 
                  or contact support with the error ID: <code className="bg-blue-500/20 px-1 rounded">{this.state.errorId}</code>
                </p>
              </div>
            </div>
          </PixelCard>
        </div>
      );
    }

    return this.props.children;
  }
}

// Hook for error reporting
export const useErrorReporting = () => {
  const reportError = (error: Error, context?: string) => {
    console.error(`Error in ${context || 'unknown context'}:`, error);
    
    // Store error report
    try {
      const errorReport = {
        timestamp: new Date().toISOString(),
        context: context || 'manual',
        error: {
          name: error.name,
          message: error.message,
          stack: error.stack
        },
        userAgent: navigator.userAgent,
        url: window.location.href
      };

      const existingErrors = JSON.parse(localStorage.getItem('admin_error_reports') || '[]');
      existingErrors.push(errorReport);
      
      if (existingErrors.length > 10) {
        existingErrors.splice(0, existingErrors.length - 10);
      }
      
      localStorage.setItem('admin_error_reports', JSON.stringify(existingErrors));
    } catch (reportingError) {
      console.error('Failed to report error:', reportingError);
    }
  };

  const getErrorReports = () => {
    try {
      return JSON.parse(localStorage.getItem('admin_error_reports') || '[]');
    } catch {
      return [];
    }
  };

  const clearErrorReports = () => {
    localStorage.removeItem('admin_error_reports');
  };

  return {
    reportError,
    getErrorReports,
    clearErrorReports
  };
};
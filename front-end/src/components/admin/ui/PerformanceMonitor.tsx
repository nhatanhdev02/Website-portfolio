import React, { useState, useEffect } from 'react';
import { PixelCard } from './PixelCard';
import { PixelButton } from './PixelButton';

interface PerformanceMetrics {
  renderTime: number;
  memoryUsage: number;
  bundleSize: number;
  imageOptimizations: number;
  cacheHitRate: number;
}

interface PerformanceMonitorProps {
  className?: string;
}

export const PerformanceMonitor: React.FC<PerformanceMonitorProps> = ({ className = '' }) => {
  const [metrics, setMetrics] = useState<PerformanceMetrics>({
    renderTime: 0,
    memoryUsage: 0,
    bundleSize: 0,
    imageOptimizations: 0,
    cacheHitRate: 0
  });
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const updateMetrics = () => {
      // Performance API metrics
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
      const renderTime = navigation ? navigation.loadEventEnd - navigation.loadEventStart : 0;

      // Memory usage (if available)
      const memoryInfo = (performance as any).memory;
      const memoryUsage = memoryInfo ? memoryInfo.usedJSHeapSize / 1024 / 1024 : 0;

      // Bundle size estimation
      const resources = performance.getEntriesByType('resource');
      const jsResources = resources.filter(resource => resource.name.includes('.js'));
      const bundleSize = jsResources.reduce((total, resource) => total + (resource.transferSize || 0), 0) / 1024;

      setMetrics({
        renderTime: Math.round(renderTime),
        memoryUsage: Math.round(memoryUsage * 100) / 100,
        bundleSize: Math.round(bundleSize * 100) / 100,
        imageOptimizations: getImageOptimizationCount(),
        cacheHitRate: getCacheHitRate()
      });
    };

    updateMetrics();
    const interval = setInterval(updateMetrics, 5000);

    return () => clearInterval(interval);
  }, []);

  const getImageOptimizationCount = (): number => {
    // Get from ImagePerformanceMonitor if available
    try {
      const stored = localStorage.getItem('admin_image_optimizations');
      return stored ? JSON.parse(stored).length : 0;
    } catch {
      return 0;
    }
  };

  const getCacheHitRate = (): number => {
    // Calculate cache hit rate based on localStorage usage
    try {
      const cacheEntries = Object.keys(localStorage).filter(key => key.startsWith('admin_'));
      return Math.min(100, cacheEntries.length * 10); // Simplified calculation
    } catch {
      return 0;
    }
  };

  const clearPerformanceData = () => {
    // Clear performance-related localStorage data
    const keys = Object.keys(localStorage).filter(key => 
      key.includes('performance') || key.includes('metrics')
    );
    keys.forEach(key => localStorage.removeItem(key));
    
    // Reset metrics
    setMetrics({
      renderTime: 0,
      memoryUsage: 0,
      bundleSize: 0,
      imageOptimizations: 0,
      cacheHitRate: 0
    });
  };

  if (!isVisible) {
    return (
      <PixelButton
        onClick={() => setIsVisible(true)}
        variant="secondary"
        size="sm"
        className="fixed bottom-4 right-4 z-50"
      >
        📊 Performance
      </PixelButton>
    );
  }

  return (
    <div className={`fixed bottom-4 right-4 z-50 w-80 ${className}`}>
      <PixelCard className="p-4">
        <div className="flex justify-between items-center mb-4">
          <h3 className="font-pixel text-sm text-pixel-primary">Performance Monitor</h3>
          <PixelButton
            onClick={() => setIsVisible(false)}
            variant="secondary"
            size="sm"
          >
            ✕
          </PixelButton>
        </div>

        <div className="space-y-3">
          <div className="flex justify-between">
            <span className="text-xs text-pixel-muted">Render Time:</span>
            <span className="text-xs font-pixel text-pixel-text">
              {metrics.renderTime}ms
            </span>
          </div>

          <div className="flex justify-between">
            <span className="text-xs text-pixel-muted">Memory Usage:</span>
            <span className="text-xs font-pixel text-pixel-text">
              {metrics.memoryUsage}MB
            </span>
          </div>

          <div className="flex justify-between">
            <span className="text-xs text-pixel-muted">Bundle Size:</span>
            <span className="text-xs font-pixel text-pixel-text">
              {metrics.bundleSize}KB
            </span>
          </div>

          <div className="flex justify-between">
            <span className="text-xs text-pixel-muted">Image Optimizations:</span>
            <span className="text-xs font-pixel text-pixel-text">
              {metrics.imageOptimizations}
            </span>
          </div>

          <div className="flex justify-between">
            <span className="text-xs text-pixel-muted">Cache Hit Rate:</span>
            <span className="text-xs font-pixel text-pixel-text">
              {metrics.cacheHitRate}%
            </span>
          </div>

          <div className="pt-2 border-t border-pixel-border">
            <PixelButton
              onClick={clearPerformanceData}
              variant="secondary"
              size="sm"
              className="w-full"
            >
              Clear Data
            </PixelButton>
          </div>
        </div>
      </PixelCard>
    </div>
  );
};

// Performance measurement utilities
export class AdminPerformanceTracker {
  private static measurements: Map<string, number> = new Map();

  static startMeasurement(name: string) {
    this.measurements.set(name, performance.now());
  }

  static endMeasurement(name: string): number {
    const startTime = this.measurements.get(name);
    if (!startTime) return 0;

    const duration = performance.now() - startTime;
    this.measurements.delete(name);
    
    // Log slow operations
    if (duration > 100) {
      console.warn(`Slow operation detected: ${name} took ${duration.toFixed(2)}ms`);
    }

    return duration;
  }

  static measureAsync<T>(name: string, fn: () => Promise<T>): Promise<T> {
    this.startMeasurement(name);
    return fn().finally(() => {
      this.endMeasurement(name);
    });
  }

  static measureSync<T>(name: string, fn: () => T): T {
    this.startMeasurement(name);
    try {
      return fn();
    } finally {
      this.endMeasurement(name);
    }
  }
}

// React hook for performance tracking
export const usePerformanceTracking = (componentName: string) => {
  useEffect(() => {
    AdminPerformanceTracker.startMeasurement(`${componentName}_mount`);
    
    return () => {
      AdminPerformanceTracker.endMeasurement(`${componentName}_mount`);
    };
  }, [componentName]);

  const trackOperation = (operationName: string, fn: () => void) => {
    AdminPerformanceTracker.measureSync(`${componentName}_${operationName}`, fn);
  };

  const trackAsyncOperation = async <T>(operationName: string, fn: () => Promise<T>): Promise<T> => {
    return AdminPerformanceTracker.measureAsync(`${componentName}_${operationName}`, fn);
  };

  return { trackOperation, trackAsyncOperation };
};
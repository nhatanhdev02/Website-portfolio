interface ImageOptimizationOptions {
  maxWidth?: number;
  maxHeight?: number;
  quality?: number;
  format?: 'jpeg' | 'png' | 'webp';
  pixelArt?: boolean;
}

interface OptimizedImage {
  dataUrl: string;
  size: number;
  width: number;
  height: number;
  format: string;
}

export class ImageOptimizer {
  private static canvas: HTMLCanvasElement | null = null;
  private static ctx: CanvasRenderingContext2D | null = null;

  private static getCanvas(): { canvas: HTMLCanvasElement; ctx: CanvasRenderingContext2D } {
    if (!this.canvas) {
      this.canvas = document.createElement('canvas');
      this.ctx = this.canvas.getContext('2d');
      if (!this.ctx) {
        throw new Error('Could not get canvas context');
      }
    }
    return { canvas: this.canvas, ctx: this.ctx! };
  }

  static async optimizeImage(
    file: File,
    options: ImageOptimizationOptions = {}
  ): Promise<OptimizedImage> {
    const {
      maxWidth = 1920,
      maxHeight = 1080,
      quality = 0.8,
      format = 'jpeg',
      pixelArt = false
    } = options;

    return new Promise((resolve, reject) => {
      const img = new Image();
      
      img.onload = () => {
        try {
          const { canvas, ctx } = this.getCanvas();
          
          // Calculate new dimensions
          const { width, height } = this.calculateDimensions(
            img.width,
            img.height,
            maxWidth,
            maxHeight
          );

          canvas.width = width;
          canvas.height = height;

          // Set image rendering for pixel art
          if (pixelArt) {
            ctx.imageSmoothingEnabled = false;
            (ctx as any).mozImageSmoothingEnabled = false;
            (ctx as any).webkitImageSmoothingEnabled = false;
            (ctx as any).msImageSmoothingEnabled = false;
          } else {
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
          }

          // Draw and compress
          ctx.drawImage(img, 0, 0, width, height);
          
          const mimeType = format === 'png' ? 'image/png' : 
                          format === 'webp' ? 'image/webp' : 'image/jpeg';
          
          const dataUrl = canvas.toDataURL(mimeType, quality);
          const size = this.getDataUrlSize(dataUrl);

          resolve({
            dataUrl,
            size,
            width,
            height,
            format: mimeType
          });
        } catch (error) {
          reject(error);
        }
      };

      img.onerror = () => reject(new Error('Failed to load image'));
      img.src = URL.createObjectURL(file);
    });
  }

  static async createThumbnail(
    file: File,
    size: number = 150
  ): Promise<OptimizedImage> {
    return this.optimizeImage(file, {
      maxWidth: size,
      maxHeight: size,
      quality: 0.7,
      format: 'jpeg'
    });
  }

  static async optimizeForPixelArt(
    file: File,
    maxSize: number = 512
  ): Promise<OptimizedImage> {
    return this.optimizeImage(file, {
      maxWidth: maxSize,
      maxHeight: maxSize,
      quality: 1.0,
      format: 'png',
      pixelArt: true
    });
  }

  static async createMultipleSizes(
    file: File,
    sizes: number[] = [150, 300, 600, 1200]
  ): Promise<Record<string, OptimizedImage>> {
    const results: Record<string, OptimizedImage> = {};
    
    for (const size of sizes) {
      try {
        const optimized = await this.optimizeImage(file, {
          maxWidth: size,
          maxHeight: size,
          quality: 0.8,
          format: 'jpeg'
        });
        results[`${size}w`] = optimized;
      } catch (error) {
        console.warn(`Failed to create ${size}px version:`, error);
      }
    }
    
    return results;
  }

  private static calculateDimensions(
    originalWidth: number,
    originalHeight: number,
    maxWidth: number,
    maxHeight: number
  ): { width: number; height: number } {
    let { width, height } = { width: originalWidth, height: originalHeight };

    // Calculate scaling factor
    const widthRatio = maxWidth / width;
    const heightRatio = maxHeight / height;
    const ratio = Math.min(widthRatio, heightRatio);

    // Only scale down, never up
    if (ratio < 1) {
      width = Math.round(width * ratio);
      height = Math.round(height * ratio);
    }

    return { width, height };
  }

  private static getDataUrlSize(dataUrl: string): number {
    // Approximate size calculation for base64 data URL
    const base64 = dataUrl.split(',')[1];
    return Math.round((base64.length * 3) / 4);
  }

  static formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  static validateImageFile(file: File): { valid: boolean; error?: string } {
    // Check file type
    if (!file.type.startsWith('image/')) {
      return { valid: false, error: 'File must be an image' };
    }

    // Check supported formats
    const supportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!supportedFormats.includes(file.type)) {
      return { valid: false, error: 'Unsupported image format' };
    }

    // Check file size (10MB limit)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
      return { valid: false, error: 'File size must be less than 10MB' };
    }

    return { valid: true };
  }
}

// Utility functions for common use cases
export const optimizeProfileImage = (file: File) => 
  ImageOptimizer.optimizeImage(file, {
    maxWidth: 400,
    maxHeight: 400,
    quality: 0.9,
    format: 'jpeg'
  });

export const optimizeProjectImage = (file: File) =>
  ImageOptimizer.optimizeImage(file, {
    maxWidth: 800,
    maxHeight: 600,
    quality: 0.85,
    format: 'jpeg'
  });

export const optimizeBlogThumbnail = (file: File) =>
  ImageOptimizer.optimizeImage(file, {
    maxWidth: 600,
    maxHeight: 400,
    quality: 0.8,
    format: 'jpeg'
  });

export const optimizePixelIcon = (file: File) =>
  ImageOptimizer.optimizeForPixelArt(file, 64);

// Performance monitoring for image operations
export class ImagePerformanceMonitor {
  private static metrics: Array<{
    operation: string;
    originalSize: number;
    optimizedSize: number;
    compressionRatio: number;
    processingTime: number;
    timestamp: Date;
  }> = [];

  static recordOptimization(
    operation: string,
    originalSize: number,
    optimizedSize: number,
    processingTime: number
  ) {
    const compressionRatio = ((originalSize - optimizedSize) / originalSize) * 100;
    
    this.metrics.push({
      operation,
      originalSize,
      optimizedSize,
      compressionRatio,
      processingTime,
      timestamp: new Date()
    });

    // Keep only last 100 metrics
    if (this.metrics.length > 100) {
      this.metrics = this.metrics.slice(-100);
    }
  }

  static getMetrics() {
    return [...this.metrics];
  }

  static getAverageCompressionRatio(): number {
    if (this.metrics.length === 0) return 0;
    
    const totalRatio = this.metrics.reduce((sum, metric) => sum + metric.compressionRatio, 0);
    return totalRatio / this.metrics.length;
  }

  static getTotalSavings(): number {
    return this.metrics.reduce((total, metric) => 
      total + (metric.originalSize - metric.optimizedSize), 0
    );
  }
}
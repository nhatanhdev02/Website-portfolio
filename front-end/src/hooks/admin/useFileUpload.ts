import { useState, useCallback } from 'react';
import { 
  storeImage, 
  deleteImage, 
  getStoredImages, 
  getStorageUsage as getStorageUsageUtil,
  cleanupOldImages as cleanupOldImagesUtil,
  StoredImage 
} from '@/utils/imageStorage';

export interface FileUploadOptions {
  maxSize?: number; // in MB
  acceptedTypes?: string[];
  quality?: number; // for image compression (0-1)
  maxWidth?: number;
  maxHeight?: number;
  pixelArt?: boolean;
  category?: string; // for organizing uploaded images
  generateThumbnail?: boolean; // generate thumbnail for large images
  thumbnailSize?: number; // thumbnail max dimension
}

export interface FileUploadResult {
  url: string;
  file: File;
  originalSize: number;
  compressedSize: number;
  compressionRatio: number;
  thumbnail?: string; // thumbnail data URL if generated
  metadata: {
    width: number;
    height: number;
    type: string;
    category: string;
    uploadDate: string;
    id: string;
  };
}

export interface UseFileUploadReturn {
  uploadFile: (file: File, options?: FileUploadOptions) => Promise<FileUploadResult>;
  isUploading: boolean;
  uploadProgress: number;
  error: string | null;
  clearError: () => void;
  getStoredImages: (category?: string) => unknown[];
  deleteStoredImage: (imageId: string) => boolean;
  getStorageUsage: () => { used: number; total: number; percentage: number };
  cleanupOldImages: (maxAge?: number) => number;
}

const DEFAULT_OPTIONS: Required<FileUploadOptions> = {
  maxSize: 5, // 5MB
  acceptedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  quality: 0.8,
  maxWidth: 1920,
  maxHeight: 1080,
  pixelArt: false,
  category: 'general',
  generateThumbnail: false,
  thumbnailSize: 200
};

// Re-export utility functions for convenience
export { getStoredImages, deleteImage as deleteStoredImage, getStorageUsage as getStorageUsageUtil, cleanupOldImages as cleanupOldImagesUtil } from '@/utils/imageStorage';

export const useFileUpload = (): UseFileUploadReturn => {
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  const validateFile = useCallback((file: File, options: Required<FileUploadOptions>): void => {
    // Check file type
    if (!options.acceptedTypes.includes(file.type)) {
      throw new Error(`File type not supported. Please use: ${options.acceptedTypes.map(type => type.split('/')[1]).join(', ')}`);
    }

    // Check file size
    const fileSizeMB = file.size / (1024 * 1024);
    if (fileSizeMB > options.maxSize) {
      throw new Error(`File size too large. Maximum size is ${options.maxSize}MB`);
    }
  }, []);

  const processImage = useCallback((
    file: File, 
    options: Required<FileUploadOptions>
  ): Promise<{ 
    blob: Blob; 
    dataUrl: string; 
    thumbnail?: string;
    metadata: { width: number; height: number; type: string };
  }> => {
    return new Promise((resolve, reject) => {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const img = new Image();

      img.onload = () => {
        try {
          const originalWidth = img.width;
          const originalHeight = img.height;
          
          // Calculate new dimensions
          let { width, height } = img;
          
          // For pixel art, preserve original dimensions if they're small
          if (options.pixelArt && width <= 512 && height <= 512) {
            canvas.width = width;
            canvas.height = height;
          } else {
            // Scale down if necessary
            if (width > options.maxWidth || height > options.maxHeight) {
              const ratio = Math.min(options.maxWidth / width, options.maxHeight / height);
              width = Math.floor(width * ratio);
              height = Math.floor(height * ratio);
            }
            canvas.width = width;
            canvas.height = height;
          }

          // Set canvas context properties for pixel art
          if (options.pixelArt && ctx) {
            ctx.imageSmoothingEnabled = false;
            ctx.webkitImageSmoothingEnabled = false;
            ctx.mozImageSmoothingEnabled = false;
            ctx.msImageSmoothingEnabled = false;
          }

          // Draw and compress main image
          ctx?.drawImage(img, 0, 0, width, height);
          
          // Generate thumbnail if requested
          let thumbnailPromise: Promise<string | undefined> = Promise.resolve(undefined);
          
          if (options.generateThumbnail) {
            thumbnailPromise = new Promise((thumbResolve) => {
              const thumbCanvas = document.createElement('canvas');
              const thumbCtx = thumbCanvas.getContext('2d');
              
              // Calculate thumbnail dimensions maintaining aspect ratio
              const thumbSize = options.thumbnailSize;
              const aspectRatio = originalWidth / originalHeight;
              let thumbWidth, thumbHeight;
              
              if (aspectRatio > 1) {
                thumbWidth = thumbSize;
                thumbHeight = thumbSize / aspectRatio;
              } else {
                thumbWidth = thumbSize * aspectRatio;
                thumbHeight = thumbSize;
              }
              
              thumbCanvas.width = thumbWidth;
              thumbCanvas.height = thumbHeight;
              
              // Set pixel art properties for thumbnail too
              if (options.pixelArt && thumbCtx) {
                thumbCtx.imageSmoothingEnabled = false;
                thumbCtx.webkitImageSmoothingEnabled = false;
                thumbCtx.mozImageSmoothingEnabled = false;
                thumbCtx.msImageSmoothingEnabled = false;
              }
              
              thumbCtx?.drawImage(img, 0, 0, thumbWidth, thumbHeight);
              const thumbnailDataUrl = thumbCanvas.toDataURL(file.type, 0.7);
              thumbResolve(thumbnailDataUrl);
            });
          }
          
          // Convert main canvas to blob
          canvas.toBlob(
            async (blob) => {
              if (!blob) {
                reject(new Error('Failed to compress image'));
                return;
              }
              
              const dataUrl = canvas.toDataURL(file.type, options.quality);
              const thumbnail = await thumbnailPromise;
              
              resolve({ 
                blob, 
                dataUrl,
                thumbnail,
                metadata: {
                  width: originalWidth,
                  height: originalHeight,
                  type: file.type
                }
              });
            },
            file.type,
            options.quality
          );
        } catch (error) {
          reject(error);
        }
      };

      img.onerror = () => {
        reject(new Error('Failed to load image for processing'));
      };

      img.src = URL.createObjectURL(file);
    });
  }, []);

  const storeImageInLocalStorage = useCallback((
    dataUrl: string, 
    filename: string, 
    category: string,
    metadata: { width: number; height: number; type: string },
    thumbnail?: string,
    originalSize?: number,
    compressedSize?: number
  ): string => {
    try {
      const imageData = {
        category,
        filename,
        data: dataUrl,
        thumbnail,
        metadata: {
          ...metadata,
          originalSize,
          compressedSize,
          compressionRatio: originalSize && compressedSize ? (1 - compressedSize / originalSize) : 0
        }
      };
      
      return storeImage(imageData);
    } catch (error) {
      throw new Error('Failed to store image in local storage: ' + (error instanceof Error ? error.message : 'Unknown error'));
    }
  }, []);

  const uploadFile = useCallback(async (
    file: File, 
    options: FileUploadOptions = {}
  ): Promise<FileUploadResult> => {
    const mergedOptions = { ...DEFAULT_OPTIONS, ...options };
    
    setIsUploading(true);
    setUploadProgress(0);
    setError(null);

    try {
      // Validate file
      validateFile(file, mergedOptions);
      setUploadProgress(20);

      // Process image if it's an image file
      const isImage = file.type.startsWith('image/');
      let finalBlob: Blob = file;
      let dataUrl: string;
      let thumbnail: string | undefined;
      let metadata: { width: number; height: number; type: string };

      if (isImage) {
        setUploadProgress(40);
        const processed = await processImage(file, mergedOptions);
        finalBlob = processed.blob;
        dataUrl = processed.dataUrl;
        thumbnail = processed.thumbnail;
        metadata = processed.metadata;
        setUploadProgress(70);
      } else {
        // For non-image files, just convert to data URL
        dataUrl = await new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = (e) => resolve(e.target?.result as string);
          reader.onerror = () => reject(new Error('Failed to read file'));
          reader.readAsDataURL(file);
        });
        
        metadata = {
          width: 0,
          height: 0,
          type: file.type
        };
      }

      // Store in localStorage with enhanced metadata
      const imageId = storeImageInLocalStorage(
        dataUrl, 
        file.name, 
        mergedOptions.category,
        metadata,
        thumbnail,
        file.size,
        finalBlob.size
      );
      setUploadProgress(90);

      // Simulate network delay for realistic UX
      await new Promise(resolve => setTimeout(resolve, 200));
      setUploadProgress(100);

      const result: FileUploadResult = {
        url: dataUrl,
        file: new File([finalBlob], file.name, { type: file.type }),
        originalSize: file.size,
        compressedSize: finalBlob.size,
        compressionRatio: file.size > 0 ? (1 - finalBlob.size / file.size) : 0,
        thumbnail,
        metadata: {
          ...metadata,
          category: mergedOptions.category,
          uploadDate: new Date().toISOString(),
          id: imageId
        }
      };

      return result;
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Upload failed';
      setError(errorMessage);
      throw error;
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
    }
  }, [validateFile, processImage, storeImageInLocalStorage]);

  return {
    uploadFile,
    isUploading,
    uploadProgress,
    error,
    clearError,
    getStoredImages,
    deleteStoredImage: deleteImage,
    getStorageUsage: getStorageUsageUtil,
    cleanupOldImages: cleanupOldImagesUtil
  };
};
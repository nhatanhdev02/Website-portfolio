import React, { useState, useRef, DragEvent, ChangeEvent } from 'react';
import { cn } from '@/lib/utils';
import { PixelButton } from './PixelButton';
import { useFileUpload, FileUploadOptions } from '@/hooks/admin/useFileUpload';

interface ImageUploadProps {
  onUpload: (file: File, preview: string, result?: any) => void;
  currentImage?: string;
  acceptedTypes?: string[];
  maxSize?: number; // in MB
  pixelArt?: boolean;
  label?: string;
  error?: string;
  helperText?: string;
  className?: string;
  uploadOptions?: FileUploadOptions;
  showProgress?: boolean;
  showCompressionInfo?: boolean;
  showThumbnail?: boolean;
  category?: string;
  showStorageInfo?: boolean;
}

export const ImageUpload: React.FC<ImageUploadProps> = ({
  onUpload,
  currentImage,
  acceptedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  maxSize = 5, // 5MB default
  pixelArt = false,
  label,
  error,
  helperText,
  className,
  uploadOptions,
  showProgress = true,
  showCompressionInfo = false,
  showThumbnail = false,
  category = 'general',
  showStorageInfo = false
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [preview, setPreview] = useState<string | null>(currentImage || null);
  const [compressionInfo, setCompressionInfo] = useState<{
    originalSize: number;
    compressedSize: number;
    compressionRatio: number;
  } | null>(null);
  const [uploadResult, setUploadResult] = useState<unknown>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  const { 
    uploadFile, 
    isUploading, 
    uploadProgress, 
    error: uploadError, 
    clearError,
    getStorageUsage 
  } = useFileUpload();

  const processFile = async (file: File) => {
    try {
      clearError();
      
      const options: FileUploadOptions = {
        maxSize,
        acceptedTypes,
        pixelArt,
        category,
        generateThumbnail: showThumbnail,
        ...uploadOptions
      };

      const result = await uploadFile(file, options);
      
      setPreview(result.url);
      setUploadResult(result);
      setCompressionInfo({
        originalSize: result.originalSize,
        compressedSize: result.compressedSize,
        compressionRatio: result.compressionRatio
      });
      
      onUpload(result.file, result.url, result);
    } catch (error) {
      console.error('Upload error:', error);
      // Error is handled by the useFileUpload hook
    }
  };

  const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);

    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) {
      processFile(files[0]);
    }
  };

  const handleFileSelect = (e: ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files && files.length > 0) {
      processFile(files[0]);
    }
  };

  const handleBrowseClick = () => {
    fileInputRef.current?.click();
  };

  const handleRemoveImage = () => {
    setPreview(null);
    setCompressionInfo(null);
    setUploadResult(null);
    clearError();
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    // Create empty file event to clear the upload
    onUpload(new File([], ''), '', null);
  };

  const displayError = error || uploadError;
  
  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return (
    <div className={cn('space-y-2', className)}>
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}

      <div
        className={cn(
          'relative border-2 border-dashed rounded-lg p-6 transition-all duration-200',
          'bg-gray-800 font-mono',
          isDragging
            ? 'border-blue-500 bg-blue-900/20'
            : displayError
            ? 'border-red-600 bg-red-900/20'
            : 'border-gray-600 hover:border-gray-500',
          'cursor-pointer'
        )}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={handleBrowseClick}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept={acceptedTypes.join(',')}
          onChange={handleFileSelect}
          className="hidden"
        />

        {isUploading ? (
          <div className="text-center space-y-4">
            <div className="text-4xl text-blue-400">
              📤
            </div>
            <div className="space-y-2">
              <p className="text-gray-300">Uploading image...</p>
              {showProgress && (
                <div className="w-full bg-gray-700 rounded-full h-2">
                  <div 
                    className="bg-blue-500 h-2 rounded-full transition-all duration-300"
                    style={{ width: `${uploadProgress}%` }}
                  />
                </div>
              )}
              <p className="text-sm text-gray-500">{uploadProgress}%</p>
            </div>
          </div>
        ) : preview ? (
          <div className="space-y-4">
            <div className="relative">
              <img
                src={preview}
                alt="Preview"
                className={cn(
                  'max-w-full max-h-48 mx-auto rounded border-2 border-gray-600',
                  pixelArt && 'image-rendering-pixelated'
                )}
                style={pixelArt ? { imageRendering: 'pixelated' } : {}}
              />
              <div className="absolute top-2 right-2">
                <PixelButton
                  size="sm"
                  variant="danger"
                  onClick={(e) => {
                    e.stopPropagation();
                    handleRemoveImage();
                  }}
                  disabled={isUploading}
                >
                  ❌
                </PixelButton>
              </div>
            </div>
            
            {/* Thumbnail Preview */}
            {showThumbnail && uploadResult?.thumbnail && (
              <div className="mt-2">
                <p className="text-xs text-gray-400 mb-1">Thumbnail:</p>
                <img
                  src={uploadResult.thumbnail}
                  alt="Thumbnail"
                  className={cn(
                    'w-16 h-16 object-cover rounded border border-gray-600',
                    pixelArt && 'image-rendering-pixelated'
                  )}
                  style={pixelArt ? { imageRendering: 'pixelated' } : {}}
                />
              </div>
            )}

            {/* Compression Info */}
            {showCompressionInfo && compressionInfo && (
              <div className="text-xs text-gray-500 font-mono space-y-1">
                <div className="flex justify-between">
                  <span>Original:</span>
                  <span>{formatFileSize(compressionInfo.originalSize)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Compressed:</span>
                  <span>{formatFileSize(compressionInfo.compressedSize)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Saved:</span>
                  <span className="text-green-400">
                    {(compressionInfo.compressionRatio * 100).toFixed(1)}%
                  </span>
                </div>
                {uploadResult?.metadata && (
                  <>
                    <div className="flex justify-between">
                      <span>Dimensions:</span>
                      <span>{uploadResult.metadata.width}×{uploadResult.metadata.height}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Category:</span>
                      <span className="text-blue-400">{uploadResult.metadata.category}</span>
                    </div>
                  </>
                )}
              </div>
            )}
            
            <p className="text-sm text-gray-400 text-center">
              Click to replace image or drag a new one here
            </p>
          </div>
        ) : (
          <div className="text-center space-y-4">
            <div className="text-4xl text-gray-500">
              📷
            </div>
            <div className="space-y-2">
              <p className="text-gray-300">
                {isDragging ? 'Drop image here' : 'Drag & drop an image here'}
              </p>
              <p className="text-sm text-gray-500">
                or click to browse files
              </p>
            </div>
          </div>
        )}
      </div>

      {displayError && (
        <p className="text-sm text-red-400 flex items-center gap-1 font-mono">
          <span>⚠</span>
          {displayError}
        </p>
      )}

      {helperText && !displayError && (
        <p className="text-sm text-gray-500 font-mono">
          {helperText}
        </p>
      )}

      <div className="text-xs text-gray-500 font-mono">
        Supported formats: {acceptedTypes.map(type => type.split('/')[1]).join(', ')} • Max size: {maxSize}MB
      </div>

      {/* Storage Usage Info */}
      {showStorageInfo && (
        <div className="text-xs text-gray-500 font-mono border-t border-gray-700 pt-2 mt-2">
          {(() => {
            const usage = getStorageUsage();
            return (
              <div className="space-y-1">
                <div className="flex justify-between">
                  <span>Storage used:</span>
                  <span>{formatFileSize(usage.used)} / {formatFileSize(usage.total)}</span>
                </div>
                <div className="w-full bg-gray-700 rounded-full h-1">
                  <div 
                    className={cn(
                      "h-1 rounded-full transition-all duration-300",
                      usage.percentage > 80 ? "bg-red-500" : 
                      usage.percentage > 60 ? "bg-yellow-500" : "bg-green-500"
                    )}
                    style={{ width: `${Math.min(usage.percentage, 100)}%` }}
                  />
                </div>
                <div className="text-center">
                  <span className={cn(
                    usage.percentage > 80 ? "text-red-400" : 
                    usage.percentage > 60 ? "text-yellow-400" : "text-green-400"
                  )}>
                    {usage.percentage.toFixed(1)}% used
                  </span>
                </div>
              </div>
            );
          })()}
        </div>
      )}
    </div>
  );
};
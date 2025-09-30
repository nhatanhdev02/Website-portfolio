import React, { useState, useRef, DragEvent, ChangeEvent } from 'react';
import { cn } from '@/lib/utils';
import { PixelButton } from './PixelButton';
import { useFileUpload, FileUploadOptions } from '@/hooks/admin/useFileUpload';

interface ImageItem {
  id: string;
  url: string;
  file?: File;
  isUploading?: boolean;
  uploadProgress?: number;
}

interface MultiImageUploadProps {
  images: string[];
  onImagesChange: (images: string[]) => void;
  maxImages?: number;
  acceptedTypes?: string[];
  maxSize?: number; // in MB
  label?: string;
  error?: string;
  helperText?: string;
  className?: string;
  category?: string;
  showPreview?: boolean;
  allowReorder?: boolean;
}

export const MultiImageUpload: React.FC<MultiImageUploadProps> = ({
  images,
  onImagesChange,
  maxImages = 5,
  acceptedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  maxSize = 5,
  label,
  error,
  helperText,
  className,
  category = 'portfolio',
  showPreview = true,
  allowReorder = true
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [imageItems, setImageItems] = useState<ImageItem[]>(() =>
    images.map((url, index) => ({ id: `existing-${index}`, url }))
  );
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  const { uploadFile, isUploading, error: uploadError, clearError } = useFileUpload();

  const processFiles = async (files: FileList | File[]) => {
    const fileArray = Array.from(files);
    const remainingSlots = maxImages - imageItems.length;
    const filesToProcess = fileArray.slice(0, remainingSlots);

    for (const file of filesToProcess) {
      const tempId = `temp-${Date.now()}-${Math.random()}`;
      const tempItem: ImageItem = {
        id: tempId,
        url: URL.createObjectURL(file),
        file,
        isUploading: true,
        uploadProgress: 0
      };

      setImageItems(prev => [...prev, tempItem]);

      try {
        const options: FileUploadOptions = {
          maxSize,
          acceptedTypes,
          category,
          generateThumbnail: true
        };

        const result = await uploadFile(file, options);
        
        setImageItems(prev => prev.map(item => 
          item.id === tempId 
            ? { ...item, url: result.url, isUploading: false, file: undefined }
            : item
        ));

        // Update the parent component
        const newImages = [...images, result.url];
        onImagesChange(newImages);
      } catch (error) {
        console.error('Upload error:', error);
        // Remove the failed upload
        setImageItems(prev => prev.filter(item => item.id !== tempId));
      }
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
    const imageFiles = files.filter(file => acceptedTypes.includes(file.type));
    
    if (imageFiles.length > 0) {
      processFiles(imageFiles);
    }
  };

  const handleFileSelect = (e: ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files && files.length > 0) {
      processFiles(files);
    }
  };

  const handleBrowseClick = () => {
    fileInputRef.current?.click();
  };

  const removeImage = (index: number) => {
    const newImages = images.filter((_, i) => i !== index);
    onImagesChange(newImages);
    setImageItems(prev => prev.filter((_, i) => i !== index));
  };

  const moveImage = (fromIndex: number, toIndex: number) => {
    if (!allowReorder) return;

    const newImages = [...images];
    const [movedImage] = newImages.splice(fromIndex, 1);
    newImages.splice(toIndex, 0, movedImage);
    onImagesChange(newImages);

    const newImageItems = [...imageItems];
    const [movedItem] = newImageItems.splice(fromIndex, 1);
    newImageItems.splice(toIndex, 0, movedItem);
    setImageItems(newImageItems);
  };

  const handleDragStart = (e: DragEvent<HTMLDivElement>, index: number) => {
    if (!allowReorder) return;
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
  };

  const handleImageDragOver = (e: DragEvent<HTMLDivElement>) => {
    if (!allowReorder || draggedIndex === null) return;
    e.preventDefault();
  };

  const handleImageDrop = (e: DragEvent<HTMLDivElement>, dropIndex: number) => {
    if (!allowReorder || draggedIndex === null) return;
    e.preventDefault();
    
    if (draggedIndex !== dropIndex) {
      moveImage(draggedIndex, dropIndex);
    }
    setDraggedIndex(null);
  };

  const canAddMore = imageItems.length < maxImages;
  const displayError = error || uploadError;

  return (
    <div className={cn('space-y-4', className)}>
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}

      {/* Image Grid */}
      {imageItems.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {imageItems.map((item, index) => (
            <div
              key={item.id}
              className={cn(
                'relative group border-2 border-gray-600 rounded-lg overflow-hidden bg-gray-800',
                allowReorder && 'cursor-move',
                draggedIndex === index && 'opacity-50'
              )}
              draggable={allowReorder}
              onDragStart={(e) => handleDragStart(e, index)}
              onDragEnd={handleDragEnd}
              onDragOver={handleImageDragOver}
              onDrop={(e) => handleImageDrop(e, index)}
            >
              {/* Image */}
              <div className="aspect-square relative">
                <img
                  src={item.url}
                  alt={`Project image ${index + 1}`}
                  className="w-full h-full object-cover"
                />
                
                {/* Upload Progress */}
                {item.isUploading && (
                  <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <div className="text-center space-y-2">
                      <div className="text-white text-sm font-mono">Uploading...</div>
                      <div className="w-16 bg-gray-700 rounded-full h-1">
                        <div 
                          className="bg-blue-500 h-1 rounded-full transition-all duration-300"
                          style={{ width: `${item.uploadProgress || 0}%` }}
                        />
                      </div>
                    </div>
                  </div>
                )}

                {/* Hover Effects */}
                {showPreview && !item.isUploading && (
                  <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center">
                    <div className="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                      <PixelButton
                        size="sm"
                        variant="primary"
                        onClick={(e) => {
                          e.stopPropagation();
                          // Open image in new tab for preview
                          window.open(item.url, '_blank');
                        }}
                      >
                        👁️
                      </PixelButton>
                    </div>
                  </div>
                )}
              </div>

              {/* Image Controls */}
              <div className="absolute top-2 right-2 flex gap-1">
                {index === 0 && (
                  <div className="bg-blue-600 border border-blue-800 px-1 py-0.5 text-xs font-mono text-white">
                    PRIMARY
                  </div>
                )}
                <PixelButton
                  size="sm"
                  variant="danger"
                  onClick={(e) => {
                    e.stopPropagation();
                    removeImage(index);
                  }}
                  disabled={item.isUploading}
                >
                  ✕
                </PixelButton>
              </div>

              {/* Reorder Indicators */}
              {allowReorder && imageItems.length > 1 && (
                <div className="absolute bottom-2 left-2">
                  <div className="bg-gray-800 bg-opacity-75 border border-gray-600 px-1 py-0.5 text-xs font-mono text-gray-300">
                    {index + 1}
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Upload Area */}
      {canAddMore && (
        <div
          className={cn(
            'border-2 border-dashed rounded-lg p-6 transition-all duration-200 cursor-pointer',
            'bg-gray-800 font-mono',
            isDragging
              ? 'border-blue-500 bg-blue-900/20'
              : displayError
              ? 'border-red-600 bg-red-900/20'
              : 'border-gray-600 hover:border-gray-500'
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
            multiple
            className="hidden"
          />

          <div className="text-center space-y-4">
            <div className="text-4xl text-gray-500">
              📷
            </div>
            <div className="space-y-2">
              <p className="text-gray-300">
                {isDragging ? 'Drop images here' : 'Drag & drop images here'}
              </p>
              <p className="text-sm text-gray-500">
                or click to browse files
              </p>
              <p className="text-xs text-gray-500">
                {imageItems.length} / {maxImages} images • Max {maxSize}MB each
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Error Display */}
      {displayError && (
        <p className="text-sm text-red-400 flex items-center gap-1 font-mono">
          <span>⚠</span>
          {displayError}
        </p>
      )}

      {/* Helper Text */}
      {helperText && !displayError && (
        <p className="text-sm text-gray-500 font-mono">
          {helperText}
        </p>
      )}

      {/* Instructions */}
      <div className="text-xs text-gray-500 font-mono space-y-1">
        <div>• First image will be used as the primary project image</div>
        {allowReorder && <div>• Drag images to reorder them</div>}
        <div>• Supported formats: {acceptedTypes.map(type => type.split('/')[1]).join(', ')}</div>
      </div>
    </div>
  );
};
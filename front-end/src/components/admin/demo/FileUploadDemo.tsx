import React, { useState } from 'react';
import { ImageUpload } from '@/components/admin/ui/ImageUpload';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { useFileUpload } from '@/hooks/admin/useFileUpload';
import { getStorageStats, getStorageUsage, cleanupOldImages } from '@/utils/imageStorage';

/**
 * Demo component to showcase the enhanced file upload system
 * This demonstrates all the new features implemented in task 4.2
 */
export const FileUploadDemo: React.FC = () => {
  const [uploadedImage, setUploadedImage] = useState<string>('');
  const [uploadResult, setUploadResult] = useState<any>(null);
  const [storageStats, setStorageStats] = useState<any>(null);
  
  const { getStoredImages, cleanupOldImages: cleanup } = useFileUpload();

  const handleImageUpload = (file: File, preview: string, result?: any) => {
    setUploadedImage(preview);
    setUploadResult(result);
    console.log('Upload result:', result);
    
    // Update storage stats
    updateStorageStats();
  };

  const updateStorageStats = () => {
    const stats = getStorageStats();
    const usage = getStorageUsage();
    setStorageStats({ ...stats, usage });
  };

  const handleCleanup = () => {
    const removed = cleanupOldImages(1); // Remove images older than 1 day for demo
    alert(`Cleaned up ${removed} old images`);
    updateStorageStats();
  };

  const showAllImages = () => {
    const images = getStoredImages();
    console.log('All stored images:', images);
    alert(`Found ${images.length} stored images. Check console for details.`);
  };

  React.useEffect(() => {
    updateStorageStats();
  }, []);

  return (
    <div className="space-y-6 p-6">
      <PixelCard 
        title="File Upload System Demo" 
        subtitle="Testing enhanced image upload with compression, thumbnails, and storage management"
      >
        <div className="space-y-6">
          {/* Basic Upload */}
          <div>
            <h3 className="text-lg font-mono text-gray-300 mb-3">Basic Image Upload</h3>
            <ImageUpload
              onUpload={handleImageUpload}
              currentImage={uploadedImage}
              pixelArt={true}
              label="Upload Test Image"
              helperText="Upload an image to test compression and storage"
              showProgress={true}
              showCompressionInfo={true}
              category="demo"
            />
          </div>

          {/* Advanced Upload with Thumbnails */}
          <div>
            <h3 className="text-lg font-mono text-gray-300 mb-3">Advanced Upload (with Thumbnails)</h3>
            <ImageUpload
              onUpload={handleImageUpload}
              pixelArt={false}
              label="Upload with Thumbnail Generation"
              helperText="This upload generates thumbnails and shows storage info"
              showProgress={true}
              showCompressionInfo={true}
              showThumbnail={true}
              showStorageInfo={true}
              category="demo-advanced"
              uploadOptions={{
                generateThumbnail: true,
                thumbnailSize: 150,
                quality: 0.8,
                maxWidth: 1024,
                maxHeight: 1024
              }}
            />
          </div>

          {/* Upload Result Display */}
          {uploadResult && (
            <div>
              <h3 className="text-lg font-mono text-gray-300 mb-3">Upload Result</h3>
              <div className="bg-gray-800 p-4 rounded border border-gray-600 font-mono text-sm">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p><strong>Original Size:</strong> {(uploadResult.originalSize / 1024).toFixed(2)} KB</p>
                    <p><strong>Compressed Size:</strong> {(uploadResult.compressedSize / 1024).toFixed(2)} KB</p>
                    <p><strong>Compression Ratio:</strong> {(uploadResult.compressionRatio * 100).toFixed(1)}%</p>
                  </div>
                  <div>
                    <p><strong>Dimensions:</strong> {uploadResult.metadata?.width}×{uploadResult.metadata?.height}</p>
                    <p><strong>Type:</strong> {uploadResult.metadata?.type}</p>
                    <p><strong>Category:</strong> {uploadResult.metadata?.category}</p>
                    <p><strong>ID:</strong> {uploadResult.metadata?.id}</p>
                  </div>
                </div>
                {uploadResult.thumbnail && (
                  <div className="mt-4">
                    <p><strong>Thumbnail Generated:</strong></p>
                    <img 
                      src={uploadResult.thumbnail} 
                      alt="Thumbnail" 
                      className="mt-2 border border-gray-600 rounded"
                      style={{ maxWidth: '100px', maxHeight: '100px' }}
                    />
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Storage Statistics */}
          {storageStats && (
            <div>
              <h3 className="text-lg font-mono text-gray-300 mb-3">Storage Statistics</h3>
              <div className="bg-gray-800 p-4 rounded border border-gray-600 font-mono text-sm">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p><strong>Total Images:</strong> {storageStats.totalImages}</p>
                    <p><strong>Total Size:</strong> {(storageStats.totalSize / 1024).toFixed(2)} KB</p>
                    <p><strong>Storage Used:</strong> {storageStats.usage?.percentage.toFixed(1)}%</p>
                  </div>
                  <div>
                    <p><strong>Categories:</strong></p>
                    {Object.entries(storageStats.sizeByCategory).map(([category, size]) => (
                      <p key={category} className="ml-2">
                        {category}: {((size as number) / 1024).toFixed(2)} KB
                      </p>
                    ))}
                  </div>
                </div>
                
                {/* Storage Usage Bar */}
                <div className="mt-4">
                  <p className="mb-2"><strong>Storage Usage:</strong></p>
                  <div className="w-full bg-gray-700 rounded-full h-2">
                    <div 
                      className={`h-2 rounded-full transition-all duration-300 ${
                        storageStats.usage?.percentage > 80 ? 'bg-red-500' : 
                        storageStats.usage?.percentage > 60 ? 'bg-yellow-500' : 'bg-green-500'
                      }`}
                      style={{ width: `${Math.min(storageStats.usage?.percentage || 0, 100)}%` }}
                    />
                  </div>
                  <p className="text-xs mt-1 text-gray-400">
                    {(storageStats.usage?.used / 1024).toFixed(2)} KB / {(storageStats.usage?.total / 1024).toFixed(2)} KB
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Management Actions */}
          <div>
            <h3 className="text-lg font-mono text-gray-300 mb-3">Storage Management</h3>
            <div className="flex gap-3 flex-wrap">
              <PixelButton
                variant="info"
                onClick={showAllImages}
              >
                📋 Show All Images
              </PixelButton>
              
              <PixelButton
                variant="warning"
                onClick={handleCleanup}
              >
                🧹 Cleanup Old Images
              </PixelButton>
              
              <PixelButton
                variant="secondary"
                onClick={updateStorageStats}
              >
                🔄 Refresh Stats
              </PixelButton>
            </div>
          </div>

          {/* Feature Summary */}
          <div>
            <h3 className="text-lg font-mono text-gray-300 mb-3">Implemented Features</h3>
            <div className="bg-gray-800 p-4 rounded border border-gray-600">
              <ul className="space-y-2 text-sm font-mono text-gray-300">
                <li>✅ <strong>File Type Validation:</strong> Validates accepted image formats</li>
                <li>✅ <strong>Size Limit Enforcement:</strong> Configurable maximum file size</li>
                <li>✅ <strong>Image Compression:</strong> Client-side image optimization with quality control</li>
                <li>✅ <strong>Image Resizing:</strong> Automatic resizing to maximum dimensions</li>
                <li>✅ <strong>Pixel Art Support:</strong> Special handling for pixel art (no smoothing)</li>
                <li>✅ <strong>Thumbnail Generation:</strong> Optional thumbnail creation</li>
                <li>✅ <strong>Base64 Storage:</strong> Images stored as base64 in localStorage</li>
                <li>✅ <strong>Storage Management:</strong> Automatic cleanup when storage limit reached</li>
                <li>✅ <strong>Category Organization:</strong> Images organized by category</li>
                <li>✅ <strong>Metadata Tracking:</strong> Comprehensive metadata for each image</li>
                <li>✅ <strong>Storage Statistics:</strong> Real-time storage usage monitoring</li>
                <li>✅ <strong>Progress Indicators:</strong> Upload progress with visual feedback</li>
                <li>✅ <strong>Error Handling:</strong> Comprehensive error handling and validation</li>
                <li>✅ <strong>Drag & Drop:</strong> Intuitive drag and drop interface</li>
              </ul>
            </div>
          </div>
        </div>
      </PixelCard>
    </div>
  );
};
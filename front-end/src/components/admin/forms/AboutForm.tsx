import React, { useState, useEffect } from 'react';
import { PixelTextarea } from '@/components/admin/ui/PixelTextarea';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelAlert } from '@/components/admin/ui/PixelAlert';
import { ImageUpload } from '@/components/admin/ui/ImageUpload';
import { AboutContent } from '@/types/admin';
import { validateAboutContent, sanitizeTextContent } from '@/utils/aboutValidation';

interface AboutFormProps {
  initialData: AboutContent;
  onSave: (data: AboutContent) => void;
  onCancel: () => void;
  onPreview: (data: AboutContent) => void;
  onImageUpload: (file: File, category: string) => Promise<string>;
}

interface FormErrors {
  description_vi?: string;
  description_en?: string;
  experience_vi?: string;
  experience_en?: string;
  profileImage?: string;
}

export const AboutForm: React.FC<AboutFormProps> = ({
  initialData,
  onSave,
  onCancel,
  onPreview,
  onImageUpload
}) => {
  const [formData, setFormData] = useState<AboutContent>(initialData);
  const [errors, setErrors] = useState<FormErrors>({});
  const [currentTab, setCurrentTab] = useState<'vi' | 'en'>('vi');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [isUploadingImage, setIsUploadingImage] = useState(false);

  // Character limits
  const limits = {
    description: 500,
    experience: 300
  };

  const validateForm = (): boolean => {
    const validation = validateAboutContent(formData, {
      maxDescriptionLength: limits.description,
      maxExperienceLength: limits.experience,
      requireImage: true
    });

    // Convert validation errors to form errors format
    const newErrors: FormErrors = {};
    if (validation.errors.description_vi) {
      newErrors.description_vi = validation.errors.description_vi;
    }
    if (validation.errors.description_en) {
      newErrors.description_en = validation.errors.description_en;
    }
    if (validation.errors.experience_vi) {
      newErrors.experience_vi = validation.errors.experience_vi;
    }
    if (validation.errors.experience_en) {
      newErrors.experience_en = validation.errors.experience_en;
    }
    if (validation.errors.profileImage) {
      newErrors.profileImage = validation.errors.profileImage;
    }

    setErrors(newErrors);
    return validation.isValid;
  };

  const handleTextChange = (field: string, value: string) => {
    // Sanitize input for better data quality
    const sanitizedValue = field.includes('description') || field.includes('experience') 
      ? sanitizeTextContent(value) 
      : value;

    setFormData(prev => {
      if (field.includes('.')) {
        const [parent, child] = field.split('.');
        return {
          ...prev,
          [parent]: {
            ...(prev[parent as keyof AboutContent] as Record<string, string>),
            [child]: sanitizedValue
          }
        };
      }
      return {
        ...prev,
        [field]: sanitizedValue
      };
    });

    // Clear error when user starts typing
    const errorKey = field.replace('.', '_') as keyof FormErrors;
    if (errors[errorKey]) {
      setErrors(prev => ({
        ...prev,
        [errorKey]: undefined
      }));
    }
  };

  const handleImageUpload = async (file: File, preview: string, result?: unknown) => {
    if (!file.name) {
      // Empty file means removal
      setFormData(prev => ({ ...prev, profileImage: '' }));
      return;
    }

    setIsUploadingImage(true);
    try {
      const imageUrl = await onImageUpload(file, 'about');
      setFormData(prev => ({ ...prev, profileImage: imageUrl }));
      
      // Clear image error if it exists
      if (errors.profileImage) {
        setErrors(prev => ({ ...prev, profileImage: undefined }));
      }

      // Log upload result for debugging
      if (result) {
        console.log('Image upload result:', {
          originalSize: result.originalSize,
          compressedSize: result.compressedSize,
          compressionRatio: result.compressionRatio,
          metadata: result.metadata
        });
      }
    } catch (error) {
      console.error('Error uploading image:', error);
      
      // Provide specific error messages based on error type
      let errorMessage = 'Failed to upload image. Please try again.';
      
      if (error instanceof Error) {
        if (error.message.includes('storage')) {
          errorMessage = 'Storage is full or unavailable. Please clear some space and try again.';
        } else if (error.message.includes('size')) {
          errorMessage = 'Image file is too large. Please use an image smaller than 5MB.';
        } else if (error.message.includes('type') || error.message.includes('format')) {
          errorMessage = 'Invalid image format. Please use JPEG, PNG, GIF, or WebP.';
        } else if (error.message.includes('corrupted') || error.message.includes('invalid')) {
          errorMessage = 'Image file appears to be corrupted. Please try a different image.';
        } else if (error.message.includes('network') || error.message.includes('connection')) {
          errorMessage = 'Network error. Please check your connection and try again.';
        }
      }
      
      setErrors(prev => ({ 
        ...prev, 
        profileImage: errorMessage
      }));
      
      // Also set a save error for more visibility
      setSaveError(`Image upload failed: ${errorMessage}`);
    } finally {
      setIsUploadingImage(false);
    }
  };

  const handlePreview = () => {
    if (validateForm()) {
      onPreview(formData);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    setSaveError(null);
    
    try {
      // Simulate API delay
      await new Promise(resolve => setTimeout(resolve, 500));
      
      onSave(formData);
      setShowSuccess(true);
      
      // Hide success message after 3 seconds
      setTimeout(() => setShowSuccess(false), 3000);
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Failed to save about content';
      setSaveError(errorMessage);
      console.error('Error saving about content:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleReset = () => {
    setFormData(initialData);
    setErrors({});
    setShowSuccess(false);
    setSaveError(null);
  };

  // Auto-preview on form changes (debounced)
  useEffect(() => {
    const timer = setTimeout(() => {
      if (Object.keys(errors).length === 0) {
        onPreview(formData);
      }
    }, 1000);

    return () => clearTimeout(timer);
  }, [formData, errors, onPreview]);

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Success Alert */}
      {showSuccess && (
        <PixelAlert variant="success" title="Success!">
          About content has been updated successfully.
        </PixelAlert>
      )}

      {/* Save Error Alert */}
      {saveError && (
        <PixelAlert 
          variant="danger" 
          title="Save Failed"
          dismissible
          onDismiss={() => setSaveError(null)}
        >
          {saveError}
        </PixelAlert>
      )}

      {/* Language Tabs */}
      <div className="flex gap-2 mb-6">
        <PixelButton
          type="button"
          size="sm"
          variant={currentTab === 'vi' ? 'primary' : 'secondary'}
          onClick={() => setCurrentTab('vi')}
        >
          🇻🇳 Vietnamese
        </PixelButton>
        <PixelButton
          type="button"
          size="sm"
          variant={currentTab === 'en' ? 'primary' : 'secondary'}
          onClick={() => setCurrentTab('en')}
        >
          🇺🇸 English
        </PixelButton>
      </div>

      {/* Profile Image Upload */}
      <PixelCard title="Profile Image" subtitle="Upload your profile picture">
        <ImageUpload
          onUpload={handleImageUpload}
          currentImage={formData.profileImage}
          acceptedTypes={['image/jpeg', 'image/png', 'image/gif', 'image/webp']}
          maxSize={5}
          pixelArt={true}
          label="Profile Picture"
          error={errors.profileImage}
          helperText="Upload a professional profile picture. Pixel art style recommended."
          uploadOptions={{
            quality: 0.9,
            maxWidth: 512,
            maxHeight: 512,
            pixelArt: true,
            category: 'about',
            generateThumbnail: true,
            thumbnailSize: 128
          }}
          showProgress={true}
          showCompressionInfo={true}
          showThumbnail={true}
          category="about"
          showStorageInfo={true}
        />
        {isUploadingImage && (
          <div className="mt-2 text-sm text-blue-400 font-mono">
            📤 Uploading image...
          </div>
        )}
      </PixelCard>

      {/* Language-specific Content */}
      <PixelCard 
        title={`${currentTab === 'vi' ? 'Vietnamese' : 'English'} Content`}
        subtitle={`About content in ${currentTab === 'vi' ? 'Vietnamese' : 'English'}`}
        icon={currentTab === 'vi' ? '🇻🇳' : '🇺🇸'}
      >
        <div className="space-y-4">
          <PixelTextarea
            label="Description"
            value={formData.description[currentTab]}
            onChange={(e) => handleTextChange(`description.${currentTab}`, e.target.value)}
            error={errors[`description_${currentTab}` as keyof FormErrors]}
            maxLength={limits.description}
            showCharCount
            required
            rows={6}
            helperText="Describe your background, skills, and expertise"
            placeholder={currentTab === 'vi' 
              ? 'Với hơn 5 năm kinh nghiệm trong lập trình fullstack...' 
              : 'With over 5 years of experience in fullstack programming...'
            }
          />

          <PixelTextarea
            label="Experience Highlight"
            value={formData.experience[currentTab]}
            onChange={(e) => handleTextChange(`experience.${currentTab}`, e.target.value)}
            error={errors[`experience_${currentTab}` as keyof FormErrors]}
            maxLength={limits.experience}
            showCharCount
            required
            rows={4}
            helperText="Brief highlight of your passion and approach"
            placeholder={currentTab === 'vi' 
              ? 'Đam mê tạo ra những sản phẩm chất lượng cao...' 
              : 'Passionate about creating high-quality products...'
            }
          />
        </div>
      </PixelCard>

      {/* Form Actions */}
      <div className="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-600">
        <PixelButton
          type="submit"
          variant="success"
          loading={isSubmitting}
          disabled={isUploadingImage}
          className="flex-1"
        >
          💾 Save Changes
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="info"
          onClick={handlePreview}
          disabled={isSubmitting || isUploadingImage}
        >
          👁️ Preview
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="secondary"
          onClick={handleReset}
          disabled={isSubmitting || isUploadingImage}
        >
          🔄 Reset
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="danger"
          onClick={onCancel}
          disabled={isSubmitting || isUploadingImage}
        >
          ❌ Cancel
        </PixelButton>
      </div>

      {/* Form Validation Summary */}
      {Object.keys(errors).length > 0 && (
        <PixelAlert variant="danger" title="Please fix the following errors:">
          <ul className="list-disc list-inside space-y-1 text-sm">
            {Object.entries(errors).map(([field, error]) => (
              <li key={field}>{error}</li>
            ))}
          </ul>
        </PixelAlert>
      )}
    </form>
  );
};
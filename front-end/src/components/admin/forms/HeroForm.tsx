import React, { useState, useEffect } from 'react';
import { PixelInput } from '@/components/admin/ui/PixelInput';
import { PixelTextarea } from '@/components/admin/ui/PixelTextarea';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelAlert } from '@/components/admin/ui/PixelAlert';
import { HeroContent } from '@/types/admin';

interface HeroFormProps {
  initialData: HeroContent;
  onSave: (data: HeroContent) => void;
  onCancel: () => void;
  onPreview: (data: HeroContent) => void;
}

interface FormErrors {
  greeting_vi?: string;
  greeting_en?: string;
  name?: string;
  title_vi?: string;
  title_en?: string;
  subtitle_vi?: string;
  subtitle_en?: string;
  ctaText_vi?: string;
  ctaText_en?: string;
  ctaLink?: string;
}

export const HeroForm: React.FC<HeroFormProps> = ({
  initialData,
  onSave,
  onCancel,
  onPreview
}) => {
  const [formData, setFormData] = useState<HeroContent>(initialData);
  const [errors, setErrors] = useState<FormErrors>({});
  const [currentTab, setCurrentTab] = useState<'vi' | 'en'>('vi');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  // Character limits
  const limits = {
    greeting: 50,
    name: 30,
    title: 100,
    subtitle: 200,
    ctaText: 30,
    ctaLink: 200
  };

  const validateForm = (): boolean => {
    const newErrors: FormErrors = {};

    // Vietnamese validations
    if (!formData.greeting.vi.trim()) {
      newErrors.greeting_vi = 'Vietnamese greeting is required';
    } else if (formData.greeting.vi.length > limits.greeting) {
      newErrors.greeting_vi = `Must be ${limits.greeting} characters or less`;
    }

    if (!formData.title.vi.trim()) {
      newErrors.title_vi = 'Vietnamese title is required';
    } else if (formData.title.vi.length > limits.title) {
      newErrors.title_vi = `Must be ${limits.title} characters or less`;
    }

    if (!formData.subtitle.vi.trim()) {
      newErrors.subtitle_vi = 'Vietnamese subtitle is required';
    } else if (formData.subtitle.vi.length > limits.subtitle) {
      newErrors.subtitle_vi = `Must be ${limits.subtitle} characters or less`;
    }

    if (!formData.ctaText.vi.trim()) {
      newErrors.ctaText_vi = 'Vietnamese CTA text is required';
    } else if (formData.ctaText.vi.length > limits.ctaText) {
      newErrors.ctaText_vi = `Must be ${limits.ctaText} characters or less`;
    }

    // English validations
    if (!formData.greeting.en.trim()) {
      newErrors.greeting_en = 'English greeting is required';
    } else if (formData.greeting.en.length > limits.greeting) {
      newErrors.greeting_en = `Must be ${limits.greeting} characters or less`;
    }

    if (!formData.title.en.trim()) {
      newErrors.title_en = 'English title is required';
    } else if (formData.title.en.length > limits.title) {
      newErrors.title_en = `Must be ${limits.title} characters or less`;
    }

    if (!formData.subtitle.en.trim()) {
      newErrors.subtitle_en = 'English subtitle is required';
    } else if (formData.subtitle.en.length > limits.subtitle) {
      newErrors.subtitle_en = `Must be ${limits.subtitle} characters or less`;
    }

    if (!formData.ctaText.en.trim()) {
      newErrors.ctaText_en = 'English CTA text is required';
    } else if (formData.ctaText.en.length > limits.ctaText) {
      newErrors.ctaText_en = `Must be ${limits.ctaText} characters or less`;
    }

    // Common validations
    if (!formData.name.trim()) {
      newErrors.name = 'Name is required';
    } else if (formData.name.length > limits.name) {
      newErrors.name = `Must be ${limits.name} characters or less`;
    }

    if (!formData.ctaLink.trim()) {
      newErrors.ctaLink = 'CTA link is required';
    } else if (formData.ctaLink.length > limits.ctaLink) {
      newErrors.ctaLink = `Must be ${limits.ctaLink} characters or less`;
    } else if (!formData.ctaLink.startsWith('#') && !formData.ctaLink.startsWith('/') && !formData.ctaLink.startsWith('http')) {
      newErrors.ctaLink = 'Link must start with #, /, or http';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => {
      if (field.includes('.')) {
        const [parent, child] = field.split('.');
        return {
          ...prev,
          [parent]: {
            ...(prev[parent as keyof HeroContent] as unknown),
            [child]: value
          }
        };
      }
      return {
        ...prev,
        [field]: value
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
      const errorMessage = error instanceof Error ? error.message : 'Failed to save hero content';
      setSaveError(errorMessage);
      console.error('Error saving hero content:', error);
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
          Hero content has been updated successfully.
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

      {/* Common Fields */}
      <PixelCard title="Common Information" subtitle="Language-independent content">
        <div className="space-y-4">
          <PixelInput
            label="Name"
            value={formData.name}
            onChange={(e) => handleInputChange('name', e.target.value)}
            error={errors.name}
            maxLength={limits.name}
            required
            helperText={`${formData.name.length}/${limits.name} characters`}
          />

          <PixelInput
            label="CTA Link"
            value={formData.ctaLink}
            onChange={(e) => handleInputChange('ctaLink', e.target.value)}
            error={errors.ctaLink}
            maxLength={limits.ctaLink}
            required
            helperText="Use #section, /page, or full URL"
            placeholder="#portfolio"
          />
        </div>
      </PixelCard>

      {/* Language-specific Fields */}
      <PixelCard 
        title={`${currentTab === 'vi' ? 'Vietnamese' : 'English'} Content`}
        subtitle={`Content in ${currentTab === 'vi' ? 'Vietnamese' : 'English'}`}
        icon={currentTab === 'vi' ? '🇻🇳' : '🇺🇸'}
      >
        <div className="space-y-4">
          <PixelInput
            label="Greeting"
            value={formData.greeting[currentTab]}
            onChange={(e) => handleInputChange(`greeting.${currentTab}`, e.target.value)}
            error={errors[`greeting_${currentTab}` as keyof FormErrors]}
            maxLength={limits.greeting}
            required
            helperText={`${formData.greeting[currentTab].length}/${limits.greeting} characters`}
            placeholder={currentTab === 'vi' ? 'Xin chào! Tôi là' : 'Hello! I\'m'}
          />

          <PixelInput
            label="Title"
            value={formData.title[currentTab]}
            onChange={(e) => handleInputChange(`title.${currentTab}`, e.target.value)}
            error={errors[`title_${currentTab}` as keyof FormErrors]}
            maxLength={limits.title}
            required
            helperText={`${formData.title[currentTab].length}/${limits.title} characters`}
            placeholder={currentTab === 'vi' ? 'Freelance Fullstack Developer' : 'Freelance Fullstack Developer'}
          />

          <PixelTextarea
            label="Subtitle"
            value={formData.subtitle[currentTab]}
            onChange={(e) => handleInputChange(`subtitle.${currentTab}`, e.target.value)}
            error={errors[`subtitle_${currentTab}` as keyof FormErrors]}
            maxLength={limits.subtitle}
            showCharCount
            required
            rows={3}
            helperText="Brief description of your expertise"
            placeholder={currentTab === 'vi' 
              ? 'Phát triển web toàn diện với công nghệ hiện đại' 
              : 'Comprehensive web development with modern technology'
            }
          />

          <PixelInput
            label="Call-to-Action Text"
            value={formData.ctaText[currentTab]}
            onChange={(e) => handleInputChange(`ctaText.${currentTab}`, e.target.value)}
            error={errors[`ctaText_${currentTab}` as keyof FormErrors]}
            maxLength={limits.ctaText}
            required
            helperText={`${formData.ctaText[currentTab].length}/${limits.ctaText} characters`}
            placeholder={currentTab === 'vi' ? 'Xem Portfolio' : 'View Portfolio'}
          />
        </div>
      </PixelCard>

      {/* Form Actions */}
      <div className="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-600">
        <PixelButton
          type="submit"
          variant="success"
          loading={isSubmitting}
          className="flex-1"
        >
          💾 Save Changes
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="info"
          onClick={handlePreview}
          disabled={isSubmitting}
        >
          👁️ Preview
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="secondary"
          onClick={handleReset}
          disabled={isSubmitting}
        >
          🔄 Reset
        </PixelButton>
        
        <PixelButton
          type="button"
          variant="danger"
          onClick={onCancel}
          disabled={isSubmitting}
        >
          ❌ Cancel
        </PixelButton>
      </div>

      {/* Form Validation Summary */}
      {Object.keys(errors).length > 0 && (
        <PixelAlert variant="error" title="Please fix the following errors:">
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
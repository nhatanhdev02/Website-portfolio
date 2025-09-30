import React, { useState, useEffect } from 'react';
import { Service } from '@/types/admin';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelInput } from '@/components/admin/ui/PixelInput';
import { PixelTextarea } from '@/components/admin/ui/PixelTextarea';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { IconPicker } from '@/components/admin/ui/IconPicker';
import { ColorPicker } from '@/components/admin/ui/ColorPicker';
import { ServicePreview } from '@/components/admin/ui/ServicePreview';

interface ServiceFormProps {
  service?: Service;
  onSubmit: (service: Omit<Service, 'id'>) => void;
  onCancel: () => void;
  isEditing?: boolean;
}

export const ServiceForm: React.FC<ServiceFormProps> = ({
  service,
  onSubmit,
  onCancel,
  isEditing = false
}) => {
  const [formData, setFormData] = useState({
    title: { vi: '', en: '' },
    description: { vi: '', en: '' },
    icon: '⚙️',
    color: '#3b82f6',
    bgColor: '#1e40af',
    order: 0
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (service) {
      setFormData({
        title: service.title,
        description: service.description,
        icon: service.icon,
        color: service.color,
        bgColor: service.bgColor,
        order: service.order
      });
    }
  }, [service]);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.title.vi.trim()) {
      newErrors.titleVi = 'Vietnamese title is required';
    }
    if (!formData.title.en.trim()) {
      newErrors.titleEn = 'English title is required';
    }
    if (!formData.description.vi.trim()) {
      newErrors.descriptionVi = 'Vietnamese description is required';
    }
    if (!formData.description.en.trim()) {
      newErrors.descriptionEn = 'English description is required';
    }
    if (!formData.icon.trim()) {
      newErrors.icon = 'Icon is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    
    try {
      await new Promise(resolve => setTimeout(resolve, 500)); // Simulate API call
      onSubmit(formData);
    } catch (error) {
      console.error('Error submitting service:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
    
    // Clear error when user starts typing
    if (errors[field]) {
      setErrors(prev => ({
        ...prev,
        [field]: ''
      }));
    }
  };

  const handleBilingualChange = (field: 'title' | 'description', lang: 'vi' | 'en', value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: {
        ...prev[field],
        [lang]: value
      }
    }));
    
    // Clear error when user starts typing
    const errorKey = `${field}${lang.charAt(0).toUpperCase() + lang.slice(1)}`;
    if (errors[errorKey]) {
      setErrors(prev => ({
        ...prev,
        [errorKey]: ''
      }));
    }
  };

  return (
    <PixelCard title={isEditing ? 'Edit Service' : 'Add New Service'} className="max-w-2xl">
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Service Preview */}
        <div className="mb-6">
          <ServicePreview 
            service={formData} 
            showVariations={true}
          />
        </div>

        {/* Title Fields */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <PixelInput
            label="Title (Vietnamese)"
            value={formData.title.vi}
            onChange={(e) => handleBilingualChange('title', 'vi', e.target.value)}
            error={errors.titleVi}
            placeholder="Tên dịch vụ..."
            required
          />
          <PixelInput
            label="Title (English)"
            value={formData.title.en}
            onChange={(e) => handleBilingualChange('title', 'en', e.target.value)}
            error={errors.titleEn}
            placeholder="Service name..."
            required
          />
        </div>

        {/* Description Fields */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <PixelTextarea
            label="Description (Vietnamese)"
            value={formData.description.vi}
            onChange={(e) => handleBilingualChange('description', 'vi', e.target.value)}
            error={errors.descriptionVi}
            placeholder="Mô tả dịch vụ..."
            rows={4}
            required
          />
          <PixelTextarea
            label="Description (English)"
            value={formData.description.en}
            onChange={(e) => handleBilingualChange('description', 'en', e.target.value)}
            error={errors.descriptionEn}
            placeholder="Service description..."
            rows={4}
            required
          />
        </div>

        {/* Icon and Colors */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <IconPicker
            label="Service Icon"
            value={formData.icon}
            onChange={(icon) => handleInputChange('icon', icon)}
            error={errors.icon}
          />
          <ColorPicker
            label="Icon Color"
            value={formData.color}
            onChange={(color) => handleInputChange('color', color)}
          />
          <ColorPicker
            label="Background Color"
            value={formData.bgColor}
            onChange={(color) => handleInputChange('bgColor', color)}
          />
        </div>

        {/* Order */}
        <PixelInput
          label="Display Order"
          type="number"
          value={formData.order.toString()}
          onChange={(e) => handleInputChange('order', e.target.value)}
          placeholder="0"
          min="0"
          helperText="Lower numbers appear first"
        />

        {/* Action Buttons */}
        <div className="flex gap-3 pt-4">
          <PixelButton
            type="submit"
            variant="primary"
            loading={isSubmitting}
            disabled={isSubmitting}
          >
            {isEditing ? 'Update Service' : 'Add Service'}
          </PixelButton>
          <PixelButton
            type="button"
            variant="secondary"
            onClick={onCancel}
            disabled={isSubmitting}
          >
            Cancel
          </PixelButton>
        </div>
      </form>
    </PixelCard>
  );
};
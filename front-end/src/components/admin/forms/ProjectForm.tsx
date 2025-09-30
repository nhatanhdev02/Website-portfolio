import React, { useState, useEffect } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Project } from '@/types/admin';
import { 
  PixelButton, 
  PixelInput, 
  PixelTextarea, 
  PixelSelect, 
  PixelCheckbox,
  PixelAlert 
} from '@/components/admin/ui';
import { ImageUpload } from '@/components/admin/ui/ImageUpload';
import { MultiImageUpload } from '@/components/admin/ui/MultiImageUpload';
import { TechnologyTagInput } from '@/components/admin/ui/TechnologyTagInput';
import { CategoryManager } from '@/components/admin/ui/CategoryManager';
import { ProjectOrderManager } from '@/components/admin/ui/ProjectOrderManager';
import { cn } from '@/lib/utils';

interface ProjectFormProps {
  project?: Project | null;
  onClose: () => void;
}

interface ProjectFormData {
  title: { vi: string; en: string };
  description: { vi: string; en: string };
  image: string;
  images: string[];
  link: string;
  technologies: string[];
  category: string;
  featured: boolean;
  order: number;
}

const defaultFormData: ProjectFormData = {
  title: { vi: '', en: '' },
  description: { vi: '', en: '' },
  image: '',
  images: [],
  link: '',
  technologies: [],
  category: '',
  featured: false,
  order: 0
};



export const ProjectForm: React.FC<ProjectFormProps> = ({ project, onClose }) => {
  const { addProject, updateProject, projects, lastError, clearError } = useAdmin();
  const [formData, setFormData] = useState<ProjectFormData>(defaultFormData);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const isEditing = !!project;

  useEffect(() => {
    if (project) {
      setFormData({
        title: project.title,
        description: project.description,
        image: project.image,
        images: project.images || [],
        link: project.link || '',
        technologies: project.technologies,
        category: project.category,
        featured: project.featured,
        order: project.order
      });
    } else {
      // Set default order for new projects
      const maxOrder = Math.max(0, ...projects.map(p => p.order));
      setFormData(prev => ({ ...prev, order: maxOrder + 1 }));
    }
  }, [project, projects]);

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Title validation
    if (!formData.title.en.trim()) {
      newErrors.titleEn = 'English title is required';
    }
    if (!formData.title.vi.trim()) {
      newErrors.titleVi = 'Vietnamese title is required';
    }

    // Description validation
    if (!formData.description.en.trim()) {
      newErrors.descriptionEn = 'English description is required';
    }
    if (!formData.description.vi.trim()) {
      newErrors.descriptionVi = 'Vietnamese description is required';
    }

    // Category validation
    if (!formData.category.trim()) {
      newErrors.category = 'Category is required';
    }

    // Technologies validation
    if (formData.technologies.length === 0) {
      newErrors.technologies = 'At least one technology is required';
    }

    // Image validation
    if (!formData.image.trim()) {
      newErrors.image = 'Project image is required';
    }

    // Link validation (if provided)
    if (formData.link.trim()) {
      try {
        new URL(formData.link);
      } catch {
        newErrors.link = 'Please enter a valid URL';
      }
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
    clearError();

    try {
      const projectData = {
        title: formData.title,
        description: formData.description,
        image: formData.image,
        images: formData.images.length > 0 ? formData.images : undefined,
        link: formData.link || undefined,
        technologies: formData.technologies,
        category: formData.category,
        featured: formData.featured,
        order: formData.order
      };

      if (isEditing && project) {
        updateProject(project.id, projectData);
      } else {
        addProject(projectData);
      }

      onClose();
    } catch (error) {
      console.error('Error saving project:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleImageUpload = (file: File, preview: string) => {
    setFormData(prev => ({ ...prev, image: preview }));
    if (errors.image) {
      setErrors(prev => ({ ...prev, image: '' }));
    }
  };

  // Get existing categories from all projects for CategoryManager
  const existingCategories = projects.map(p => p.category).filter(Boolean);

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold font-mono text-white">
          {isEditing ? 'Edit Project' : 'Add New Project'}
        </h2>
        <PixelButton variant="secondary" onClick={onClose}>
          ✕ Close
        </PixelButton>
      </div>

      {/* Error Display */}
      {lastError && (
        <PixelAlert variant="error" onClose={clearError} className="mb-6">
          {lastError.message}
        </PixelAlert>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Project Titles */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <PixelInput
              label="Project Title (English)"
              value={formData.title.en}
              onChange={(e) => setFormData(prev => ({
                ...prev,
                title: { ...prev.title, en: e.target.value }
              }))}
              error={errors.titleEn}
              placeholder="Enter project title in English"
              required
            />
          </div>
          <div>
            <PixelInput
              label="Project Title (Vietnamese)"
              value={formData.title.vi}
              onChange={(e) => setFormData(prev => ({
                ...prev,
                title: { ...prev.title, vi: e.target.value }
              }))}
              error={errors.titleVi}
              placeholder="Nhập tiêu đề dự án bằng tiếng Việt"
              required
            />
          </div>
        </div>

        {/* Project Descriptions */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <PixelTextarea
              label="Description (English)"
              value={formData.description.en}
              onChange={(e) => setFormData(prev => ({
                ...prev,
                description: { ...prev.description, en: e.target.value }
              }))}
              error={errors.descriptionEn}
              placeholder="Describe your project in English..."
              rows={4}
              required
            />
          </div>
          <div>
            <PixelTextarea
              label="Description (Vietnamese)"
              value={formData.description.vi}
              onChange={(e) => setFormData(prev => ({
                ...prev,
                description: { ...prev.description, vi: e.target.value }
              }))}
              error={errors.descriptionVi}
              placeholder="Mô tả dự án của bạn bằng tiếng Việt..."
              rows={4}
              required
            />
          </div>
        </div>

        {/* Primary Project Image */}
        <div>
          <ImageUpload
            label="Primary Project Image"
            currentImage={formData.image}
            onUpload={handleImageUpload}
            acceptedTypes={['image/jpeg', 'image/png', 'image/gif', 'image/webp']}
            maxSize={5}
            error={errors.image}
            helperText="Upload the main screenshot or preview image of your project"
            category="portfolio"
            showCompressionInfo
            showStorageInfo
          />
        </div>

        {/* Additional Project Images */}
        <div>
          <MultiImageUpload
            label="Additional Project Images (Gallery)"
            images={formData.images}
            onImagesChange={(images) => setFormData(prev => ({ ...prev, images }))}
            maxImages={5}
            acceptedTypes={['image/jpeg', 'image/png', 'image/gif', 'image/webp']}
            maxSize={5}
            helperText="Upload additional screenshots, mockups, or project images (optional)"
            category="portfolio"
            showPreview
            allowReorder
          />
        </div>

        {/* Category Management */}
        <CategoryManager
          selectedCategory={formData.category}
          onCategoryChange={(category) => {
            setFormData(prev => ({ ...prev, category }));
            if (errors.category) {
              setErrors(prev => ({ ...prev, category: '' }));
            }
          }}
          existingCategories={existingCategories}
          error={errors.category}
          helperText="Choose or create a category for your project"
          allowCustom={true}
        />

        {/* Project Link */}
        <PixelInput
          label="Project Link (Optional)"
          type="url"
          value={formData.link}
          onChange={(e) => setFormData(prev => ({ ...prev, link: e.target.value }))}
          error={errors.link}
          placeholder="https://example.com"
          helperText="Link to live demo, repository, or project page"
        />

        {/* Technologies */}
        <TechnologyTagInput
          technologies={formData.technologies}
          onTechnologiesChange={(technologies) => {
            setFormData(prev => ({ ...prev, technologies }));
            if (errors.technologies) {
              setErrors(prev => ({ ...prev, technologies: '' }));
            }
          }}
          label="Technologies Used"
          error={errors.technologies}
          helperText="Add technologies, frameworks, and tools used in this project"
          maxTags={15}
        />

        {/* Project Display Settings */}
        <ProjectOrderManager
          currentProject={project}
          allProjects={projects}
          order={formData.order}
          featured={formData.featured}
          onOrderChange={(order) => setFormData(prev => ({ ...prev, order }))}
          onFeaturedChange={(featured) => setFormData(prev => ({ ...prev, featured }))}
        />

        {/* Form Actions */}
        <div className="flex gap-4 pt-6 border-t-2 border-gray-600">
          <PixelButton
            type="submit"
            disabled={isSubmitting}
            className="flex-1"
          >
            {isSubmitting ? '💾 Saving...' : isEditing ? '💾 Update Project' : '➕ Add Project'}
          </PixelButton>
          <PixelButton
            type="button"
            variant="secondary"
            onClick={onClose}
            disabled={isSubmitting}
          >
            Cancel
          </PixelButton>
        </div>
      </form>
    </div>
  );
};
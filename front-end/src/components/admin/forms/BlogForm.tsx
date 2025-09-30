import React, { useState, useEffect } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { BlogPost } from '@/types/admin';
import { 
  PixelButton, 
  PixelInput, 
  PixelCard, 
  PixelSelect,
  PixelBadge,
  MarkdownEditor,
  ImageUpload
} from '@/components/admin/ui';
import { cn } from '@/lib/utils';

interface BlogFormProps {
  post?: BlogPost;
  onSave?: (post: BlogPost) => void;
  onCancel?: () => void;
}

interface BlogFormData {
  title: { vi: string; en: string };
  content: { vi: string; en: string };
  excerpt: { vi: string; en: string };
  thumbnail: string;
  tags: string[];
  status: 'draft' | 'published';
  publishDate: Date;
}

export const BlogForm: React.FC<BlogFormProps> = ({
  post,
  onSave,
  onCancel
}) => {
  const { addBlogPost, updateBlogPost, uploadImage } = useAdmin();
  
  // Form state
  const [formData, setFormData] = useState<BlogFormData>({
    title: { vi: '', en: '' },
    content: { vi: '', en: '' },
    excerpt: { vi: '', en: '' },
    thumbnail: '',
    tags: [],
    status: 'draft',
    publishDate: new Date()
  });
  
  const [currentLanguage, setCurrentLanguage] = useState<'vi' | 'en'>('en');
  const [newTag, setNewTag] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isDirty, setIsDirty] = useState(false);
  const [autoSaveEnabled, setAutoSaveEnabled] = useState(true);

  // Initialize form with existing post data
  useEffect(() => {
    if (post) {
      setFormData({
        title: post.title,
        content: post.content,
        excerpt: post.excerpt,
        thumbnail: post.thumbnail,
        tags: [...post.tags],
        status: post.status,
        publishDate: new Date(post.publishDate)
      });
    }
  }, [post]);

  // Mark form as dirty when data changes
  useEffect(() => {
    if (post) {
      const hasChanges = 
        JSON.stringify(formData.title) !== JSON.stringify(post.title) ||
        JSON.stringify(formData.content) !== JSON.stringify(post.content) ||
        JSON.stringify(formData.excerpt) !== JSON.stringify(post.excerpt) ||
        formData.thumbnail !== post.thumbnail ||
        JSON.stringify(formData.tags) !== JSON.stringify(post.tags) ||
        formData.status !== post.status;
      
      setIsDirty(hasChanges);
    } else {
      const hasContent = 
        formData.title.vi.trim() || 
        formData.title.en.trim() || 
        formData.content.vi.trim() || 
        formData.content.en.trim();
      
      setIsDirty(hasContent);
    }
  }, [formData, post]);

  // Validation
  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Title validation
    if (!formData.title.en.trim()) {
      newErrors.titleEn = 'English title is required';
    }
    if (!formData.title.vi.trim()) {
      newErrors.titleVi = 'Vietnamese title is required';
    }

    // Content validation
    if (!formData.content.en.trim()) {
      newErrors.contentEn = 'English content is required';
    }
    if (!formData.content.vi.trim()) {
      newErrors.contentVi = 'Vietnamese content is required';
    }

    // Excerpt validation
    if (!formData.excerpt.en.trim()) {
      newErrors.excerptEn = 'English excerpt is required';
    }
    if (!formData.excerpt.vi.trim()) {
      newErrors.excerptVi = 'Vietnamese excerpt is required';
    }

    // Excerpt length validation
    if (formData.excerpt.en.length > 200) {
      newErrors.excerptEn = 'English excerpt must be 200 characters or less';
    }
    if (formData.excerpt.vi.length > 200) {
      newErrors.excerptVi = 'Vietnamese excerpt must be 200 characters or less';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle form field updates
  const updateField = (field: keyof BlogFormData, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear related errors
    if (field === 'title') {
      setErrors(prev => {
        const { titleEn, titleVi, ...rest } = prev;
        return rest;
      });
    }
  };

  const updateBilingualField = (field: 'title' | 'content' | 'excerpt', lang: 'vi' | 'en', value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: { ...prev[field], [lang]: value }
    }));
    
    // Clear related errors
    const errorKey = `${field}${lang.charAt(0).toUpperCase() + lang.slice(1)}`;
    if (errors[errorKey]) {
      setErrors(prev => {
        const { [errorKey]: removed, ...rest } = prev;
        return rest;
      });
    }
  };

  // Handle tag management
  const addTag = () => {
    const tag = newTag.trim().toLowerCase();
    if (tag && !formData.tags.includes(tag)) {
      updateField('tags', [...formData.tags, tag]);
      setNewTag('');
    }
  };

  const removeTag = (tagToRemove: string) => {
    updateField('tags', formData.tags.filter(tag => tag !== tagToRemove));
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTag();
    }
  };

  // Handle image upload
  const handleImageUpload = async (file: File) => {
    try {
      setIsLoading(true);
      const imageUrl = await uploadImage(file, 'blog');
      updateField('thumbnail', imageUrl);
    } catch (error) {
      console.error('Failed to upload image:', error);
      setErrors(prev => ({ ...prev, thumbnail: 'Failed to upload image' }));
    } finally {
      setIsLoading(false);
    }
  };

  // Auto-save functionality
  const handleAutoSave = (content: string) => {
    if (!autoSaveEnabled || !post) return;
    
    // Only auto-save content, not the entire form
    const updatedPost: BlogPost = {
      ...post,
      content: { ...formData.content, [currentLanguage]: content }
    };
    
    updateBlogPost(post.id, { content: updatedPost.content });
  };

  // Handle form submission
  const handleSave = async (publishNow: boolean = false) => {
    if (!validateForm()) return;

    try {
      setIsLoading(true);
      
      const postData = {
        ...formData,
        status: publishNow ? 'published' as const : formData.status,
        publishDate: publishNow ? new Date() : formData.publishDate
      };

      if (post) {
        // Update existing post
        const updatedPost: BlogPost = { ...post, ...postData };
        updateBlogPost(post.id, postData);
        onSave?.(updatedPost);
      } else {
        // Create new post
        addBlogPost(postData);
        onSave?.({ ...postData, id: Date.now().toString() } as BlogPost);
      }

      setIsDirty(false);
    } catch (error) {
      console.error('Failed to save post:', error);
      setErrors(prev => ({ ...prev, submit: 'Failed to save post' }));
    } finally {
      setIsLoading(false);
    }
  };

  // Handle cancel with unsaved changes warning
  const handleCancel = () => {
    if (isDirty) {
      if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
        onCancel?.();
      }
    } else {
      onCancel?.();
    }
  };

  // Schedule post for future publication
  const handleSchedule = () => {
    if (!validateForm()) return;
    
    const scheduledDate = prompt('Enter publication date (YYYY-MM-DD HH:MM):');
    if (!scheduledDate) return;
    
    try {
      const date = new Date(scheduledDate);
      if (isNaN(date.getTime())) {
        alert('Invalid date format');
        return;
      }
      
      updateField('publishDate', date);
      updateField('status', 'draft');
      handleSave();
    } catch (error) {
      alert('Invalid date format');
    }
  };

  const formatDate = (date: Date) => {
    return date.toISOString().slice(0, 16); // Format for datetime-local input
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-mono font-bold text-white">
            {post ? 'Edit Blog Post' : 'Create New Blog Post'}
          </h2>
          <div className="flex items-center gap-2 mt-1">
            {post && (
              <PixelBadge variant={post.status === 'published' ? 'success' : 'warning'} size="sm">
                {post.status}
              </PixelBadge>
            )}
            {isDirty && (
              <PixelBadge variant="info" size="sm">
                Unsaved Changes
              </PixelBadge>
            )}
          </div>
        </div>

        <div className="flex gap-2">
          <PixelButton
            variant="secondary"
            size="sm"
            onClick={handleCancel}
            disabled={isLoading}
          >
            Cancel
          </PixelButton>
          
          <PixelButton
            variant="primary"
            size="sm"
            onClick={() => handleSave(false)}
            disabled={isLoading}
            loading={isLoading}
          >
            Save Draft
          </PixelButton>
          
          {formData.status === 'draft' && (
            <PixelButton
              variant="success"
              size="sm"
              onClick={() => handleSave(true)}
              disabled={isLoading}
            >
              Publish Now
            </PixelButton>
          )}
        </div>
      </div>

      {/* Language Toggle */}
      <PixelCard className="p-4">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-mono font-bold text-white">Content Language</h3>
          <div className="flex border-2 border-gray-600 bg-gray-800 overflow-hidden">
            <button
              onClick={() => setCurrentLanguage('en')}
              className={cn(
                'px-3 py-1 text-sm font-mono border-r-2 border-gray-600 transition-colors duration-200',
                currentLanguage === 'en'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              )}
            >
              English
            </button>
            <button
              onClick={() => setCurrentLanguage('vi')}
              className={cn(
                'px-3 py-1 text-sm font-mono transition-colors duration-200',
                currentLanguage === 'vi'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              )}
            >
              Tiếng Việt
            </button>
          </div>
        </div>

        {/* Auto-save toggle */}
        {post && (
          <div className="flex items-center gap-2 mb-4">
            <input
              type="checkbox"
              id="autosave"
              checked={autoSaveEnabled}
              onChange={(e) => setAutoSaveEnabled(e.target.checked)}
              className="w-4 h-4"
            />
            <label htmlFor="autosave" className="font-mono text-sm text-gray-300">
              Enable auto-save (saves content every 30 seconds)
            </label>
          </div>
        )}
      </PixelCard>

      {/* Basic Information */}
      <PixelCard className="p-4 space-y-4">
        <h3 className="font-mono font-bold text-white">Basic Information</h3>
        
        {/* Title */}
        <div>
          <label className="block font-mono text-sm text-gray-300 mb-2">
            Title ({currentLanguage === 'en' ? 'English' : 'Tiếng Việt'}) *
          </label>
          <PixelInput
            type="text"
            value={formData.title[currentLanguage]}
            onChange={(e) => updateBilingualField('title', currentLanguage, e.target.value)}
            placeholder={`Enter title in ${currentLanguage === 'en' ? 'English' : 'Vietnamese'}...`}
            className={errors[`title${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`] ? 'border-red-500' : ''}
          />
          {errors[`title${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`] && (
            <p className="text-red-400 text-xs font-mono mt-1">
              {errors[`title${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`]}
            </p>
          )}
        </div>

        {/* Excerpt */}
        <div>
          <label className="block font-mono text-sm text-gray-300 mb-2">
            Excerpt ({currentLanguage === 'en' ? 'English' : 'Tiếng Việt'}) * 
            <span className="text-gray-500">
              ({formData.excerpt[currentLanguage].length}/200)
            </span>
          </label>
          <textarea
            value={formData.excerpt[currentLanguage]}
            onChange={(e) => updateBilingualField('excerpt', currentLanguage, e.target.value)}
            placeholder={`Brief description in ${currentLanguage === 'en' ? 'English' : 'Vietnamese'}...`}
            maxLength={200}
            rows={3}
            className={cn(
              'w-full p-3 bg-gray-800 border-2 border-gray-600 text-gray-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none',
              errors[`excerpt${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`] ? 'border-red-500' : ''
            )}
          />
          {errors[`excerpt${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`] && (
            <p className="text-red-400 text-xs font-mono mt-1">
              {errors[`excerpt${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`]}
            </p>
          )}
        </div>

        {/* Thumbnail */}
        <div>
          <label className="block font-mono text-sm text-gray-300 mb-2">
            Thumbnail Image
          </label>
          <ImageUpload
            onUpload={handleImageUpload}
            currentImage={formData.thumbnail}
            acceptedTypes={['image/jpeg', 'image/png', 'image/gif', 'image/webp']}
            maxSize={5 * 1024 * 1024} // 5MB
            pixelArt={true}
          />
          {errors.thumbnail && (
            <p className="text-red-400 text-xs font-mono mt-1">{errors.thumbnail}</p>
          )}
        </div>
      </PixelCard>

      {/* Content Editor */}
      <PixelCard className="p-4">
        <h3 className="font-mono font-bold text-white mb-4">
          Content ({currentLanguage === 'en' ? 'English' : 'Tiếng Việt'}) *
        </h3>
        
        <MarkdownEditor
          value={formData.content[currentLanguage]}
          onChange={(value) => updateBilingualField('content', currentLanguage, value)}
          placeholder={`Write your blog post content in ${currentLanguage === 'en' ? 'English' : 'Vietnamese'}...`}
          height="500px"
          autoSave={autoSaveEnabled && !!post}
          onAutoSave={handleAutoSave}
        />
        
        {errors[`content${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`] && (
          <p className="text-red-400 text-xs font-mono mt-2">
            {errors[`content${currentLanguage.charAt(0).toUpperCase() + currentLanguage.slice(1)}`]}
          </p>
        )}
      </PixelCard>

      {/* Tags and Metadata */}
      <PixelCard className="p-4 space-y-4">
        <h3 className="font-mono font-bold text-white">Tags & Metadata</h3>
        
        {/* Tags */}
        <div>
          <label className="block font-mono text-sm text-gray-300 mb-2">
            Tags
          </label>
          
          {/* Existing tags */}
          {formData.tags.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-3">
              {formData.tags.map(tag => (
                <span
                  key={tag}
                  className="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 border-2 border-blue-800 text-white text-xs font-mono"
                >
                  #{tag}
                  <button
                    onClick={() => removeTag(tag)}
                    className="text-blue-200 hover:text-white ml-1"
                  >
                    ×
                  </button>
                </span>
              ))}
            </div>
          )}
          
          {/* Add new tag */}
          <div className="flex gap-2">
            <PixelInput
              type="text"
              value={newTag}
              onChange={(e) => setNewTag(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder="Add a tag..."
              className="flex-1"
            />
            <PixelButton
              variant="secondary"
              size="sm"
              onClick={addTag}
              disabled={!newTag.trim()}
            >
              Add
            </PixelButton>
          </div>
        </div>

        {/* Publication Settings */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block font-mono text-sm text-gray-300 mb-2">
              Status
            </label>
            <PixelSelect
              value={formData.status}
              onChange={(e) => updateField('status', e.target.value as 'draft' | 'published')}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </PixelSelect>
          </div>

          <div>
            <label className="block font-mono text-sm text-gray-300 mb-2">
              Publish Date
            </label>
            <input
              type="datetime-local"
              value={formatDate(formData.publishDate)}
              onChange={(e) => updateField('publishDate', new Date(e.target.value))}
              className="w-full p-2 bg-gray-800 border-2 border-gray-600 text-gray-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </PixelCard>

      {/* Action Buttons */}
      <PixelCard className="p-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <PixelButton
            variant="primary"
            onClick={() => handleSave(false)}
            disabled={isLoading}
            loading={isLoading}
            fullWidth
          >
            Save as Draft
          </PixelButton>
          
          {formData.status === 'draft' && (
            <PixelButton
              variant="success"
              onClick={() => handleSave(true)}
              disabled={isLoading}
              fullWidth
            >
              Publish Now
            </PixelButton>
          )}
          
          <PixelButton
            variant="info"
            onClick={handleSchedule}
            disabled={isLoading}
            fullWidth
          >
            Schedule Publication
          </PixelButton>
        </div>
        
        {errors.submit && (
          <p className="text-red-400 text-xs font-mono mt-2">{errors.submit}</p>
        )}
      </PixelCard>
    </div>
  );
};
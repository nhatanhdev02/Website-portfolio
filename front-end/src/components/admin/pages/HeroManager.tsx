import React, { useState } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelBadge } from '@/components/admin/ui/PixelBadge';
import { PixelAlert } from '@/components/admin/ui/PixelAlert';
import { HeroForm } from '@/components/admin/forms/HeroForm';
import { HeroContent } from '@/types/admin';

export const HeroManager: React.FC = () => {
  const { heroContent, updateHeroContent, lastError, isLoading, clearError } = useAdmin();
  const [isEditing, setIsEditing] = useState(false);
  const [previewData, setPreviewData] = useState<HeroContent | null>(null);
  const [currentLanguage, setCurrentLanguage] = useState<'vi' | 'en'>('vi');

  const handleSave = (data: HeroContent) => {
    try {
      updateHeroContent(data);
      setIsEditing(false);
      setPreviewData(null);
    } catch (error) {
      // Error is handled by the AdminContext
      console.error('Failed to save hero content:', error);
    }
  };

  const handleCancel = () => {
    setIsEditing(false);
    setPreviewData(null);
  };

  const handlePreview = (data: HeroContent) => {
    setPreviewData(data);
  };

  const displayData = previewData || heroContent;

  return (
    <div className="space-y-6">
      {/* Error Alert */}
      {lastError && lastError.type === 'validation' && (
        <PixelAlert 
          variant="danger" 
          title="Validation Error"
          dismissible
          onDismiss={clearError}
        >
          {lastError.message}
        </PixelAlert>
      )}

      {lastError && lastError.type === 'storage' && (
        <PixelAlert 
          variant="danger" 
          title="Storage Error"
          dismissible
          onDismiss={clearError}
        >
          Failed to save changes: {lastError.message}
        </PixelAlert>
      )}

      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white font-mono">Hero Section Management</h1>
          <p className="text-gray-400 font-mono mt-2">
            Manage the main landing page content and call-to-action
          </p>
        </div>
        
        <div className="flex items-center gap-3">
          {previewData && (
            <PixelBadge variant="warning" size="md">
              Preview Mode
            </PixelBadge>
          )}
          
          {!isEditing && (
            <PixelButton
              variant="primary"
              onClick={() => setIsEditing(true)}
              loading={isLoading}
              disabled={isLoading}
            >
              ✏️ Edit Hero Content
            </PixelButton>
          )}
        </div>
      </div>

      {/* Language Toggle for Preview */}
      {!isEditing && (
        <PixelCard>
          <div className="flex items-center gap-4">
            <span className="text-sm text-gray-400 font-mono">Preview Language:</span>
            <div className="flex gap-2">
              <PixelButton
                size="sm"
                variant={currentLanguage === 'vi' ? 'primary' : 'secondary'}
                onClick={() => setCurrentLanguage('vi')}
              >
                🇻🇳 Vietnamese
              </PixelButton>
              <PixelButton
                size="sm"
                variant={currentLanguage === 'en' ? 'primary' : 'secondary'}
                onClick={() => setCurrentLanguage('en')}
              >
                🇺🇸 English
              </PixelButton>
            </div>
          </div>
        </PixelCard>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Current Content Display / Preview */}
        <PixelCard 
          title={previewData ? "Preview" : "Current Content"}
          subtitle={`Displaying ${currentLanguage === 'vi' ? 'Vietnamese' : 'English'} version`}
          icon={previewData ? "👁️" : "📄"}
          variant={previewData ? "warning" : "default"}
        >
          <div className="space-y-4">
            {/* Greeting */}
            <div>
              <label className="text-xs text-gray-500 font-mono uppercase tracking-wide">
                Greeting
              </label>
              <p className="text-lg text-white font-mono">
                {displayData.greeting[currentLanguage]}
              </p>
            </div>

            {/* Name */}
            <div>
              <label className="text-xs text-gray-500 font-mono uppercase tracking-wide">
                Name
              </label>
              <p className="text-xl text-blue-400 font-mono font-bold">
                {displayData.name}
              </p>
            </div>

            {/* Title */}
            <div>
              <label className="text-xs text-gray-500 font-mono uppercase tracking-wide">
                Title
              </label>
              <p className="text-lg text-green-400 font-mono">
                {displayData.title[currentLanguage]}
              </p>
            </div>

            {/* Subtitle */}
            <div>
              <label className="text-xs text-gray-500 font-mono uppercase tracking-wide">
                Subtitle
              </label>
              <p className="text-sm text-gray-300 font-mono leading-relaxed">
                {displayData.subtitle[currentLanguage]}
              </p>
            </div>

            {/* CTA */}
            <div>
              <label className="text-xs text-gray-500 font-mono uppercase tracking-wide">
                Call to Action
              </label>
              <div className="flex items-center gap-3 mt-2">
                <PixelButton size="sm" variant="primary">
                  {displayData.ctaText[currentLanguage]}
                </PixelButton>
                <span className="text-xs text-gray-500 font-mono">
                  → {displayData.ctaLink}
                </span>
              </div>
            </div>

            {/* Preview Notice */}
            {previewData && (
              <div className="mt-4 p-3 bg-yellow-900 border border-yellow-700 rounded">
                <p className="text-yellow-200 text-sm font-mono">
                  ⚠️ This is a preview. Changes are not saved yet.
                </p>
              </div>
            )}
          </div>
        </PixelCard>

        {/* Edit Form */}
        {isEditing ? (
          <PixelCard 
            title="Edit Hero Content"
            subtitle="Update the main landing page content"
            icon="✏️"
            variant="primary"
          >
            <HeroForm
              initialData={heroContent}
              onSave={handleSave}
              onCancel={handleCancel}
              onPreview={handlePreview}
            />
          </PixelCard>
        ) : (
          <PixelCard 
            title="Content Statistics"
            subtitle="Hero section information"
            icon="📊"
          >
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="text-center p-3 bg-gray-700 border border-gray-600 rounded">
                  <div className="text-2xl font-bold text-blue-400 font-mono">
                    {displayData.greeting.vi.length + displayData.greeting.en.length}
                  </div>
                  <div className="text-xs text-gray-400 font-mono">
                    Greeting chars
                  </div>
                </div>
                
                <div className="text-center p-3 bg-gray-700 border border-gray-600 rounded">
                  <div className="text-2xl font-bold text-green-400 font-mono">
                    {displayData.title.vi.length + displayData.title.en.length}
                  </div>
                  <div className="text-xs text-gray-400 font-mono">
                    Title chars
                  </div>
                </div>
                
                <div className="text-center p-3 bg-gray-700 border border-gray-600 rounded">
                  <div className="text-2xl font-bold text-yellow-400 font-mono">
                    {displayData.subtitle.vi.length + displayData.subtitle.en.length}
                  </div>
                  <div className="text-xs text-gray-400 font-mono">
                    Subtitle chars
                  </div>
                </div>
                
                <div className="text-center p-3 bg-gray-700 border border-gray-600 rounded">
                  <div className="text-2xl font-bold text-purple-400 font-mono">
                    {displayData.ctaText.vi.length + displayData.ctaText.en.length}
                  </div>
                  <div className="text-xs text-gray-400 font-mono">
                    CTA chars
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-400 font-mono">CTA Link:</span>
                  <PixelBadge variant="info" size="sm">
                    {displayData.ctaLink}
                  </PixelBadge>
                </div>
                
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-400 font-mono">Languages:</span>
                  <div className="flex gap-1">
                    <PixelBadge variant="success" size="sm">🇻🇳 VI</PixelBadge>
                    <PixelBadge variant="success" size="sm">🇺🇸 EN</PixelBadge>
                  </div>
                </div>
              </div>

              <div className="pt-4 border-t border-gray-600">
                <p className="text-xs text-gray-500 font-mono text-center">
                  Click "Edit Hero Content" to make changes
                </p>
              </div>
            </div>
          </PixelCard>
        )}
      </div>
    </div>
  );
};
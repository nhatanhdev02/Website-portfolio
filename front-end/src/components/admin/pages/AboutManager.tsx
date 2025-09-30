import React, { useState } from 'react';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelAlert } from '@/components/admin/ui/PixelAlert';
import { AboutForm } from '@/components/admin/forms/AboutForm';
import { useAdmin } from '@/contexts/AdminContext';
import { AboutContent } from '@/types/admin';

export const AboutManager: React.FC = () => {
  const { 
    aboutContent, 
    updateAboutContent, 
    uploadImage, 
    lastError, 
    clearError,
    isLoading,
    getAboutContentBackups,
    restoreAboutContentFromBackup,
    exportAboutContent,
    importAboutContent,
    validateAboutContentIntegrity
  } = useAdmin();
  const [isEditing, setIsEditing] = useState(false);
  const [previewData, setPreviewData] = useState<AboutContent>(aboutContent);
  const [currentLanguage, setCurrentLanguage] = useState<'vi' | 'en'>('vi');
  const [showBackups, setShowBackups] = useState(false);
  const [integrityCheck, setIntegrityCheck] = useState<{ isValid: boolean; issues: string[] } | null>(null);

  const handleSave = async (data: AboutContent) => {
    try {
      await updateAboutContent(data);
      setIsEditing(false);
      setPreviewData(data);
    } catch (error) {
      console.error('Error saving about content:', error);
      // Error will be displayed via the lastError from context
    }
  };

  const handleCancel = () => {
    setIsEditing(false);
    setPreviewData(aboutContent);
    clearError();
  };

  const handlePreview = (data: AboutContent) => {
    setPreviewData(data);
  };

  const handleImageUpload = async (file: File, category: string): Promise<string> => {
    try {
      return await uploadImage(file, category);
    } catch (error) {
      console.error('Image upload failed:', error);
      // Error will be handled by the context and displayed via lastError
      throw error;
    }
  };

  const handleExportData = () => {
    try {
      const exportData = exportAboutContent();
      const blob = new Blob([exportData], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `about-content-${new Date().toISOString().split('T')[0]}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  const handleImportData = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = (e) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          try {
            const jsonData = e.target?.result as string;
            const success = importAboutContent(jsonData);
            if (success) {
              setPreviewData(aboutContent);
              alert('About content imported successfully!');
            } else {
              alert('Failed to import about content. Please check the file format.');
            }
          } catch (error) {
            console.error('Import failed:', error);
            alert('Failed to import about content. Invalid file format.');
          }
        };
        reader.readAsText(file);
      }
    };
    input.click();
  };

  const handleRestoreBackup = (backupKey: string) => {
    if (confirm('Are you sure you want to restore from this backup? Current changes will be lost.')) {
      const success = restoreAboutContentFromBackup(backupKey);
      if (success) {
        setPreviewData(aboutContent);
        setShowBackups(false);
        alert('Content restored from backup successfully!');
      } else {
        alert('Failed to restore from backup.');
      }
    }
  };

  const handleIntegrityCheck = () => {
    const result = validateAboutContentIntegrity();
    setIntegrityCheck(result);
  };

  const handleResetToDefault = () => {
    if (confirm('Are you sure you want to reset to default content? This cannot be undone.')) {
      const defaultContent: AboutContent = {
        description: { 
          vi: 'Với hơn 5 năm kinh nghiệm trong lập trình fullstack, tôi chuyên phát triển các ứng dụng web hiện đại sử dụng React, Node.js, và các công nghệ tiên tiến.',
          en: 'With over 5 years of experience in fullstack programming, I specialize in developing modern web applications using React, Node.js, and cutting-edge technologies.'
        },
        profileImage: '/src/assets/pixel-dev-character.png',
        experience: {
          vi: 'Đam mê tạo ra những sản phẩm chất lượng cao và trải nghiệm người dùng tuyệt vời.',
          en: 'Passionate about creating high-quality products and excellent user experiences.'
        }
      };
      try {
        updateAboutContent(defaultContent);
        setPreviewData(defaultContent);
      } catch (error) {
        console.error('Reset failed:', error);
      }
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white font-mono">
            📝 About Section Manager
          </h1>
          <p className="text-gray-400 font-mono">
            Manage your personal information and experience descriptions
          </p>
        </div>
        
        <div className="flex gap-2">
          {!isEditing ? (
            <PixelButton
              variant="primary"
              onClick={() => setIsEditing(true)}
            >
              ✏️ Edit About
            </PixelButton>
          ) : (
            <PixelButton
              variant="secondary"
              onClick={handleCancel}
            >
              ❌ Cancel Edit
            </PixelButton>
          )}
        </div>
      </div>

      {/* Error Display */}
      {lastError && (
        <PixelAlert 
          variant="danger" 
          title="Error"
          dismissible
          onDismiss={clearError}
        >
          {lastError.message}
        </PixelAlert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Edit Form */}
        {isEditing && (
          <div className="lg:col-span-1">
            <PixelCard title="Edit About Content" subtitle="Update your personal information">
              <AboutForm
                initialData={aboutContent}
                onSave={handleSave}
                onCancel={handleCancel}
                onPreview={handlePreview}
                onImageUpload={handleImageUpload}
              />
            </PixelCard>
          </div>
        )}

        {/* Current Content Display / Preview */}
        <div className={isEditing ? 'lg:col-span-1' : 'lg:col-span-2'}>
          <PixelCard 
            title={isEditing ? "Live Preview" : "Current About Content"} 
            subtitle={isEditing ? "Preview of your changes" : "How your about section appears"}
          >
            {/* Language Toggle for Preview */}
            <div className="flex gap-2 mb-6">
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

            {/* Profile Image Preview */}
            <div className="mb-6">
              <h3 className="text-lg font-bold text-white mb-3 font-mono">
                Profile Image
              </h3>
              {previewData.profileImage ? (
                <div className="flex justify-center">
                  <img
                    src={previewData.profileImage}
                    alt="Profile"
                    className="w-32 h-32 rounded-lg border-2 border-gray-600 object-cover"
                    style={{ imageRendering: 'pixelated' }}
                  />
                </div>
              ) : (
                <div className="flex justify-center">
                  <div className="w-32 h-32 rounded-lg border-2 border-gray-600 bg-gray-700 flex items-center justify-center">
                    <span className="text-4xl text-gray-500">📷</span>
                  </div>
                </div>
              )}
            </div>

            {/* Description Preview */}
            <div className="mb-6">
              <h3 className="text-lg font-bold text-white mb-3 font-mono">
                Description
              </h3>
              <div className="bg-gray-700 p-4 rounded border border-gray-600">
                <p className="text-gray-300 leading-relaxed">
                  {previewData.description[currentLanguage] || 
                    `No ${currentLanguage === 'vi' ? 'Vietnamese' : 'English'} description available`
                  }
                </p>
              </div>
            </div>

            {/* Experience Preview */}
            <div className="mb-6">
              <h3 className="text-lg font-bold text-white mb-3 font-mono">
                Experience Highlight
              </h3>
              <div className="bg-gray-700 p-4 rounded border border-gray-600">
                <p className="text-gray-300 leading-relaxed">
                  {previewData.experience[currentLanguage] || 
                    `No ${currentLanguage === 'vi' ? 'Vietnamese' : 'English'} experience highlight available`
                  }
                </p>
              </div>
            </div>

            {/* Content Statistics */}
            <div className="grid grid-cols-2 gap-4 mt-6 pt-4 border-t border-gray-600">
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-400 font-mono">
                  {previewData.description[currentLanguage]?.length || 0}
                </div>
                <div className="text-sm text-gray-400 font-mono">
                  Description chars
                </div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-green-400 font-mono">
                  {previewData.experience[currentLanguage]?.length || 0}
                </div>
                <div className="text-sm text-gray-400 font-mono">
                  Experience chars
                </div>
              </div>
            </div>
          </PixelCard>
        </div>
      </div>

      {/* Quick Actions */}
      {!isEditing && (
        <PixelCard title="Quick Actions" subtitle="Common about management tasks">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <PixelButton
              variant="info"
              onClick={() => setIsEditing(true)}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">✏️</span>
              <span className="text-sm">Edit Content</span>
            </PixelButton>
            
            <PixelButton
              variant="secondary"
              onClick={handleExportData}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">💾</span>
              <span className="text-sm">Export Data</span>
            </PixelButton>
            
            <PixelButton
              variant="info"
              onClick={handleImportData}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">📥</span>
              <span className="text-sm">Import Data</span>
            </PixelButton>
            
            <PixelButton
              variant="warning"
              onClick={handleResetToDefault}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">🔄</span>
              <span className="text-sm">Reset Default</span>
            </PixelButton>
          </div>
        </PixelCard>
      )}

      {/* Data Management */}
      {!isEditing && (
        <PixelCard title="Data Management" subtitle="Backup, restore, and validation tools">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <PixelButton
              variant="secondary"
              onClick={() => setShowBackups(!showBackups)}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">🗂️</span>
              <span className="text-sm">View Backups</span>
            </PixelButton>
            
            <PixelButton
              variant="info"
              onClick={handleIntegrityCheck}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">🔍</span>
              <span className="text-sm">Check Integrity</span>
            </PixelButton>
            
            <PixelButton
              variant="success"
              onClick={() => {
                // Force a backup by updating with current content
                try {
                  updateAboutContent({});
                  alert('Backup created successfully!');
                } catch (error) {
                  console.error('Backup failed:', error);
                }
              }}
              disabled={isLoading}
              className="h-16 flex flex-col items-center justify-center"
            >
              <span className="text-xl mb-1">💾</span>
              <span className="text-sm">Create Backup</span>
            </PixelButton>
          </div>
        </PixelCard>
      )}

      {/* Backup Management */}
      {showBackups && (
        <PixelCard title="Content Backups" subtitle="Restore from previous versions">
          <div className="space-y-4">
            {(() => {
              const backups = getAboutContentBackups();
              if (backups.length === 0) {
                return (
                  <div className="text-center py-8 text-gray-400">
                    <span className="text-4xl mb-2 block">📁</span>
                    <p>No backups available</p>
                  </div>
                );
              }
              
              return backups.map((backup) => (
                <div key={backup.key} className="flex items-center justify-between p-4 bg-gray-700 rounded border border-gray-600">
                  <div>
                    <div className="font-mono text-white">
                      {new Date(backup.timestamp).toLocaleString()}
                    </div>
                    <div className="text-sm text-gray-400">
                      {backup.data.description.vi.substring(0, 50)}...
                    </div>
                  </div>
                  <PixelButton
                    variant="info"
                    size="sm"
                    onClick={() => handleRestoreBackup(backup.key)}
                    disabled={isLoading}
                  >
                    🔄 Restore
                  </PixelButton>
                </div>
              ));
            })()}
          </div>
        </PixelCard>
      )}

      {/* Integrity Check Results */}
      {integrityCheck && (
        <PixelCard title="Integrity Check Results" subtitle="Data validation status">
          <div className="space-y-4">
            <div className={`p-4 rounded border ${
              integrityCheck.isValid 
                ? 'bg-green-900 border-green-600 text-green-100' 
                : 'bg-red-900 border-red-600 text-red-100'
            }`}>
              <div className="flex items-center gap-2 mb-2">
                <span className="text-xl">
                  {integrityCheck.isValid ? '✅' : '❌'}
                </span>
                <span className="font-bold">
                  {integrityCheck.isValid ? 'Data is valid' : 'Data has issues'}
                </span>
              </div>
              
              {integrityCheck.issues.length > 0 && (
                <div>
                  <p className="font-semibold mb-2">Issues found:</p>
                  <ul className="list-disc list-inside space-y-1">
                    {integrityCheck.issues.map((issue, index) => (
                      <li key={index} className="text-sm">{issue}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
            
            <PixelButton
              variant="secondary"
              onClick={() => setIntegrityCheck(null)}
              size="sm"
            >
              Close
            </PixelButton>
          </div>
        </PixelCard>
      )}
    </div>
  );
};
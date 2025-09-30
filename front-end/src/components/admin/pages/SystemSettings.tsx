import React, { useState } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelToggle } from '@/components/admin/ui/PixelToggle';
import { PixelSelect } from '@/components/admin/ui/PixelSelect';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelAlert } from '@/components/admin/ui/PixelAlert';
import { ColorPaletteManager } from '@/components/admin/ui/ColorPaletteManager';
import { BackupManager } from '@/components/admin/ui/BackupManager';

export const SystemSettings: React.FC = () => {
  const { 
    systemSettings, 
    updateSystemSettings, 
    lastError, 
    clearError,
    resetSystemSettingsToDefaults,
    exportSystemSettings,
    importSystemSettings,
    getSystemSettingsBackups,
    restoreSystemSettingsFromBackup,
    validateSystemSettingsIntegrity
  } = useAdmin();
  const [isLoading, setIsLoading] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [previewSettings, setPreviewSettings] = useState(systemSettings);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [showColorPalette, setShowColorPalette] = useState(false);
  const [showConfirmDialog, setShowConfirmDialog] = useState<{
    type: 'reset' | 'maintenance' | 'import';
    title: string;
    message: string;
    action: () => void;
  } | null>(null);
  const [importData, setImportData] = useState('');
  const [showBackupManager, setShowBackupManager] = useState(false);

  // Language options
  const languageOptions = [
    { value: 'vi', label: 'Tiếng Việt (Vietnamese)' },
    { value: 'en', label: 'English' }
  ];

  // Theme options
  const themeOptions = [
    { value: 'light', label: 'Light Mode' },
    { value: 'dark', label: 'Dark Mode' }
  ];

  const handleSettingChange = (key: keyof typeof systemSettings, value: unknown) => {
    // Check for critical changes that need confirmation
    if (key === 'maintenanceMode' && value === true && !previewSettings.maintenanceMode) {
      setShowConfirmDialog({
        type: 'maintenance',
        title: 'Enable Maintenance Mode',
        message: 'This will make the website unavailable to public users. Only admin users will be able to access the site. Are you sure you want to continue?',
        action: () => {
          const newSettings = { ...previewSettings, [key]: value };
          setPreviewSettings(newSettings);
          if (showPreview) {
            updateSystemSettings({ [key]: value });
          }
          setShowConfirmDialog(null);
        }
      });
      return;
    }
    
    const newSettings = { ...previewSettings, [key]: value };
    setPreviewSettings(newSettings);
    
    if (showPreview) {
      // Apply preview immediately
      updateSystemSettings({ [key]: value });
    }
  };

  const handleColorPaletteChange = (colors: string[]) => {
    handleSettingChange('colorPalette', colors);
  };

  const handleColorPalettePreview = (colors: string[]) => {
    if (showPreview) {
      updateSystemSettings({ colorPalette: colors });
    }
  };

  const handleSaveSettings = async () => {
    try {
      setIsLoading(true);
      clearError();
      
      // Apply all settings
      updateSystemSettings(previewSettings);
      
      setSuccessMessage('System settings saved successfully!');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch (error) {
      console.error('Failed to save system settings:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleResetSettings = () => {
    setShowConfirmDialog({
      type: 'reset',
      title: 'Reset to Defaults',
      message: 'This will reset all system settings to their default values. This action cannot be undone (but a backup will be created). Are you sure you want to continue?',
      action: () => {
        try {
          resetSystemSettingsToDefaults();
          setPreviewSettings(systemSettings);
          setSuccessMessage('Settings reset to defaults successfully!');
          setTimeout(() => setSuccessMessage(null), 3000);
        } catch (error) {
          console.error('Failed to reset settings:', error);
        }
        setShowConfirmDialog(null);
      }
    });
  };

  const handleExportSettings = () => {
    try {
      const exportData = exportSystemSettings();
      const dataBlob = new Blob([exportData], { type: 'application/json' });
      const url = URL.createObjectURL(dataBlob);
      
      const link = document.createElement('a');
      link.href = url;
      link.download = `system-settings-${new Date().toISOString().split('T')[0]}.json`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
      
      setSuccessMessage('Settings exported successfully!');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch (error) {
      console.error('Failed to export settings:', error);
    }
  };

  const handleImportSettings = () => {
    if (!importData.trim()) return;
    
    setShowConfirmDialog({
      type: 'import',
      title: 'Import Settings',
      message: 'This will replace all current system settings with the imported data. A backup of current settings will be created. Are you sure you want to continue?',
      action: () => {
        try {
          const success = importSystemSettings(importData);
          if (success) {
            setPreviewSettings(systemSettings);
            setImportData('');
            setSuccessMessage('Settings imported successfully!');
            setTimeout(() => setSuccessMessage(null), 3000);
          }
        } catch (error) {
          console.error('Failed to import settings:', error);
        }
        setShowConfirmDialog(null);
      }
    });
  };

  const handleValidateIntegrity = () => {
    const validation = validateSystemSettingsIntegrity();
    if (validation.isValid) {
      setSuccessMessage('System settings integrity check passed!');
      setTimeout(() => setSuccessMessage(null), 3000);
    } else {
      const errorMessage = `Integrity issues found: ${validation.issues.join(', ')}`;
      console.error('System settings integrity issues:', validation.issues);
    }
  };

  const getMaintenanceModeImpact = () => {
    if (previewSettings.maintenanceMode) {
      return {
        title: 'Maintenance Mode Active',
        description: 'When enabled, visitors will see a maintenance page instead of the main website. Only admin users can access the site.',
        impact: 'High - Site will be unavailable to public users',
        color: 'warning' as const
      };
    }
    return {
      title: 'Site Available',
      description: 'The website is accessible to all visitors with normal functionality.',
      impact: 'None - Site operates normally',
      color: 'success' as const
    };
  };

  const getLanguagePreview = () => {
    const lang = previewSettings.defaultLanguage;
    return {
      greeting: lang === 'vi' ? 'Xin chào! Tôi là' : 'Hello! I\'m',
      title: lang === 'vi' ? 'Freelance Fullstack Developer' : 'Freelance Fullstack Developer',
      subtitle: lang === 'vi' 
        ? 'Phát triển web toàn diện với công nghệ hiện đại' 
        : 'Comprehensive web development with modern technology'
    };
  };

  const getThemePreview = () => {
    const theme = previewSettings.defaultTheme;
    return {
      name: theme === 'light' ? 'Light Mode' : 'Dark Mode',
      description: theme === 'light' 
        ? 'Clean, bright interface with light backgrounds' 
        : 'Modern, dark interface with reduced eye strain',
      colors: theme === 'light'
        ? { bg: '#ffffff', text: '#1f2937', accent: '#3b82f6' }
        : { bg: '#1f2937', text: '#f9fafb', accent: '#60a5fa' }
    };
  };

  const maintenanceImpact = getMaintenanceModeImpact();
  const languagePreview = getLanguagePreview();
  const themePreview = getThemePreview();

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold font-mono text-white mb-2">System Settings</h1>
          <p className="text-gray-400 font-mono">Configure global website settings and preferences</p>
        </div>
        
        <div className="flex items-center gap-3">
          <PixelToggle
            checked={showPreview}
            onChange={(e) => setShowPreview(e.target.checked)}
            label="Live Preview"
            helperText="Apply changes immediately"
          />
        </div>
      </div>

      {/* Error Display */}
      {lastError && (
        <PixelAlert
          type="error"
          title="Settings Error"
          message={lastError.message}
          onClose={clearError}
        />
      )}

      {/* Success Message */}
      {successMessage && (
        <PixelAlert
          type="success"
          title="Success"
          message={successMessage}
          onClose={() => setSuccessMessage(null)}
        />
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Language Settings */}
        <PixelCard
          title="Language Settings"
          subtitle="Default language for new visitors"
          icon="🌐"
        >
          <div className="space-y-4">
            <PixelSelect
              label="Default Language"
              value={previewSettings.defaultLanguage}
              onChange={(e) => handleSettingChange('defaultLanguage', e.target.value as 'vi' | 'en')}
              options={languageOptions}
              helperText="Language shown to first-time visitors"
            />
            
            {/* Language Preview */}
            <div className="border-2 border-gray-600 p-3 bg-gray-700">
              <h4 className="text-sm font-mono text-gray-400 mb-2">Preview</h4>
              <div className="space-y-1 text-sm">
                <div className="text-white font-mono">{languagePreview.greeting}</div>
                <div className="text-blue-400 font-mono">{languagePreview.title}</div>
                <div className="text-gray-300 font-mono">{languagePreview.subtitle}</div>
              </div>
            </div>
          </div>
        </PixelCard>

        {/* Theme Settings */}
        <PixelCard
          title="Theme Settings"
          subtitle="Default appearance for new visitors"
          icon="🎨"
        >
          <div className="space-y-4">
            <PixelSelect
              label="Default Theme"
              value={previewSettings.defaultTheme}
              onChange={(e) => handleSettingChange('defaultTheme', e.target.value as 'light' | 'dark')}
              options={themeOptions}
              helperText="Theme applied to first-time visitors"
            />
            
            {/* Theme Preview */}
            <div className="border-2 border-gray-600 p-3 bg-gray-700">
              <h4 className="text-sm font-mono text-gray-400 mb-2">Preview</h4>
              <div 
                className="p-3 border-2 border-gray-500 text-sm font-mono"
                style={{ 
                  backgroundColor: themePreview.colors.bg,
                  color: themePreview.colors.text,
                  borderColor: themePreview.colors.accent
                }}
              >
                <div className="font-bold">{themePreview.name}</div>
                <div className="text-xs opacity-75">{themePreview.description}</div>
              </div>
            </div>
          </div>
        </PixelCard>

        {/* Maintenance Mode */}
        <PixelCard
          title="Maintenance Mode"
          subtitle="Control site availability"
          icon="🔧"
          variant={maintenanceImpact.color}
        >
          <div className="space-y-4">
            <PixelToggle
              checked={previewSettings.maintenanceMode}
              onChange={(e) => handleSettingChange('maintenanceMode', e.target.checked)}
              label="Enable Maintenance Mode"
              helperText="Temporarily disable public access to the website"
            />
            
            {/* Maintenance Mode Impact Preview */}
            <div className="border-2 border-gray-600 p-3 bg-gray-700">
              <h4 className="text-sm font-mono text-gray-400 mb-2">Frontend Impact</h4>
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <span className={`text-sm font-mono ${
                    maintenanceImpact.color === 'warning' ? 'text-yellow-400' : 'text-green-400'
                  }`}>
                    {maintenanceImpact.color === 'warning' ? '⚠' : '✓'}
                  </span>
                  <span className="text-white font-mono text-sm font-bold">
                    {maintenanceImpact.title}
                  </span>
                </div>
                <p className="text-gray-300 text-sm font-mono">
                  {maintenanceImpact.description}
                </p>
                <div className="text-xs font-mono text-gray-400">
                  Impact: {maintenanceImpact.impact}
                </div>
              </div>
            </div>
          </div>
        </PixelCard>

        {/* System Status */}
        <PixelCard
          title="System Status"
          subtitle="Current configuration overview"
          icon="📊"
          variant="primary"
        >
          <div className="space-y-3">
            <div className="grid grid-cols-2 gap-4 text-sm font-mono">
              <div>
                <div className="text-gray-400">Language</div>
                <div className="text-white">
                  {languageOptions.find(opt => opt.value === systemSettings.defaultLanguage)?.label}
                </div>
              </div>
              <div>
                <div className="text-gray-400">Theme</div>
                <div className="text-white">
                  {themeOptions.find(opt => opt.value === systemSettings.defaultTheme)?.label}
                </div>
              </div>
              <div>
                <div className="text-gray-400">Maintenance</div>
                <div className={systemSettings.maintenanceMode ? 'text-yellow-400' : 'text-green-400'}>
                  {systemSettings.maintenanceMode ? 'Active' : 'Disabled'}
                </div>
              </div>
              <div>
                <div className="text-gray-400">Colors</div>
                <div className="text-white">
                  {systemSettings.colorPalette.length} colors
                </div>
              </div>
            </div>
            
            {/* Color Palette Preview */}
            <div className="border-t-2 border-gray-600 pt-3">
              <div className="text-gray-400 text-sm font-mono mb-2">Color Palette</div>
              <div className="flex gap-1 flex-wrap">
                {systemSettings.colorPalette.map((color, index) => (
                  <div
                    key={index}
                    className="w-6 h-6 border-2 border-gray-500"
                    style={{ backgroundColor: color }}
                    title={color}
                  />
                ))}
              </div>
            </div>
          </div>
        </PixelCard>
      </div>

      {/* Color Palette Management */}
      <PixelCard
        title="Color Palette Management"
        subtitle="Customize the retro color scheme"
        icon="🎨"
      >
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-sm font-mono text-white font-bold">Current Palette</h4>
              <p className="text-xs font-mono text-gray-400">
                {previewSettings.colorPalette.length} colors configured
              </p>
            </div>
            
            <PixelButton
              variant={showColorPalette ? 'secondary' : 'primary'}
              size="sm"
              onClick={() => setShowColorPalette(!showColorPalette)}
            >
              {showColorPalette ? 'Hide Editor' : 'Edit Palette'}
            </PixelButton>
          </div>
          
          {/* Color Preview */}
          <div className="border-2 border-gray-600 p-3 bg-gray-700">
            <div className="flex gap-1 flex-wrap">
              {previewSettings.colorPalette.map((color, index) => (
                <div
                  key={index}
                  className="w-8 h-8 border-2 border-gray-500 hover:scale-110 transition-transform duration-200"
                  style={{ backgroundColor: color }}
                  title={color.toUpperCase()}
                />
              ))}
            </div>
          </div>
          
          {/* Live Preview Demo */}
          {showPreview && (
            <div className="border-2 border-blue-600 p-3 bg-blue-900">
              <h4 className="text-sm font-mono text-blue-300 mb-2">Live Preview</h4>
              <div className="grid grid-cols-4 gap-2">
                {previewSettings.colorPalette.slice(0, 4).map((color, index) => (
                  <div
                    key={index}
                    className="p-2 border-2 text-center text-xs font-mono text-white"
                    style={{ 
                      backgroundColor: color,
                      borderColor: previewSettings.colorPalette[(index + 1) % previewSettings.colorPalette.length]
                    }}
                  >
                    UI Element
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </PixelCard>

      {/* Color Palette Editor */}
      {showColorPalette && (
        <ColorPaletteManager
          colors={previewSettings.colorPalette}
          onChange={handleColorPaletteChange}
          onPreview={handleColorPalettePreview}
          showPreview={showPreview}
          maxColors={16}
          minColors={4}
        />
      )}

      {/* Backup and Validation */}
      <PixelCard
        title="Backup & Validation"
        subtitle="Manage settings backups and data integrity"
        icon="💾"
      >
        <div className="space-y-4">
          {/* Export/Import */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h4 className="text-sm font-mono text-gray-400 mb-2">Export Settings</h4>
              <PixelButton
                variant="info"
                size="sm"
                onClick={handleExportSettings}
                fullWidth
              >
                Download Backup
              </PixelButton>
            </div>
            
            <div>
              <h4 className="text-sm font-mono text-gray-400 mb-2">Import Settings</h4>
              <div className="space-y-2">
                <textarea
                  value={importData}
                  onChange={(e) => setImportData(e.target.value)}
                  placeholder="Paste backup JSON here..."
                  className="w-full h-20 px-2 py-1 text-xs font-mono bg-gray-800 border-2 border-gray-600 text-white resize-none focus:outline-none focus:border-blue-500"
                />
                <PixelButton
                  variant="success"
                  size="sm"
                  onClick={handleImportSettings}
                  disabled={!importData.trim()}
                  fullWidth
                >
                  Import Backup
                </PixelButton>
              </div>
            </div>
          </div>
          
          {/* Validation */}
          <div className="border-t-2 border-gray-600 pt-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-sm font-mono text-white font-bold">Data Integrity</h4>
                <p className="text-xs font-mono text-gray-400">
                  Validate settings consistency and structure
                </p>
              </div>
              <PixelButton
                variant="warning"
                size="sm"
                onClick={handleValidateIntegrity}
              >
                Check Integrity
              </PixelButton>
            </div>
          </div>
        </div>
      </PixelCard>

      {/* Data Backup & Export */}
      <BackupManager />

      {/* Action Buttons */}
      <div className="flex items-center justify-between pt-6 border-t-2 border-gray-600">
        <div className="flex gap-3">
          <PixelButton
            variant="danger"
            size="sm"
            onClick={handleResetSettings}
          >
            Reset to Defaults
          </PixelButton>
        </div>
        
        <div className="flex gap-3">
          <PixelButton
            variant="secondary"
            onClick={() => setPreviewSettings(systemSettings)}
          >
            Cancel Changes
          </PixelButton>
          <PixelButton
            variant="primary"
            onClick={handleSaveSettings}
            loading={isLoading}
            disabled={JSON.stringify(previewSettings) === JSON.stringify(systemSettings)}
          >
            Save Settings
          </PixelButton>
        </div>
      </div>

      {/* Confirmation Dialog */}
      {showConfirmDialog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <PixelCard className="max-w-md w-full" variant="warning">
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <span className="text-2xl">⚠</span>
                <h3 className="text-lg font-bold text-white font-mono">{showConfirmDialog.title}</h3>
              </div>
              
              <p className="text-sm font-mono text-gray-300">
                {showConfirmDialog.message}
              </p>
              
              <div className="flex gap-3 pt-4 border-t-2 border-gray-600">
                <PixelButton
                  variant="danger"
                  onClick={showConfirmDialog.action}
                >
                  Confirm
                </PixelButton>
                <PixelButton
                  variant="secondary"
                  onClick={() => setShowConfirmDialog(null)}
                >
                  Cancel
                </PixelButton>
              </div>
            </div>
          </PixelCard>
        </div>
      )}
    </div>
  );
};
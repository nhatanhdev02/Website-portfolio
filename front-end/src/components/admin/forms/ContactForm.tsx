import React, { useState, useEffect } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { ContactInfo } from '@/types/admin';
import { PixelCard, PixelButton, PixelInput, PixelAlert } from '@/components/admin/ui';
import { Save, RotateCcw, Eye, Mail, Phone, Github, Linkedin, ExternalLink, Download, Upload } from 'lucide-react';

interface ContactFormProps {}

export const ContactForm: React.FC<ContactFormProps> = () => {
  const { contactInfo, updateContactInfo, lastError, clearError } = useAdmin();
  
  const [formData, setFormData] = useState<ContactInfo>(contactInfo);
  const [showPreview, setShowPreview] = useState(false);
  const [hasChanges, setHasChanges] = useState(false);
  const [validationErrors, setValidationErrors] = useState<Partial<Record<keyof ContactInfo, string>>>({});
  const [saveSuccess, setSaveSuccess] = useState(false);

  // Update form data when contactInfo changes
  useEffect(() => {
    setFormData(contactInfo);
    setHasChanges(false);
  }, [contactInfo]);

  // Check for changes
  useEffect(() => {
    const hasFormChanges = JSON.stringify(formData) !== JSON.stringify(contactInfo);
    setHasChanges(hasFormChanges);
  }, [formData, contactInfo]);

  // Clear success message after 3 seconds
  useEffect(() => {
    if (saveSuccess) {
      const timer = setTimeout(() => setSaveSuccess(false), 3000);
      return () => clearTimeout(timer);
    }
  }, [saveSuccess]);

  const handleInputChange = (field: keyof ContactInfo, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear validation error for this field
    if (validationErrors[field]) {
      setValidationErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
    
    // Clear any previous errors
    if (lastError) {
      clearError();
    }
  };

  const validateForm = (): boolean => {
    const errors: Partial<Record<keyof ContactInfo, string>> = {};

    // Email validation
    if (!formData.email.trim()) {
      errors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'Please enter a valid email address';
    }

    // Phone validation (optional but should be valid if provided)
    if (formData.phone.trim() && !/^[\+]?[0-9\s\-\(\)]{10,}$/.test(formData.phone.replace(/\s/g, ''))) {
      errors.phone = 'Please enter a valid phone number';
    }

    // GitHub URL validation (optional but should be valid if provided)
    if (formData.github.trim()) {
      try {
        const url = new URL(formData.github);
        if (!url.hostname.includes('github.com')) {
          errors.github = 'Please enter a valid GitHub URL';
        }
      } catch {
        errors.github = 'Please enter a valid GitHub URL';
      }
    }

    // LinkedIn URL validation (optional but should be valid if provided)
    if (formData.linkedin.trim()) {
      try {
        const url = new URL(formData.linkedin);
        if (!url.hostname.includes('linkedin.com')) {
          errors.linkedin = 'Please enter a valid LinkedIn URL';
        }
      } catch {
        errors.linkedin = 'Please enter a valid LinkedIn URL';
      }
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSave = () => {
    if (!validateForm()) {
      return;
    }

    try {
      updateContactInfo(formData);
      setSaveSuccess(true);
      setHasChanges(false);
    } catch (error) {
      console.error('Failed to save contact info:', error);
    }
  };

  const handleReset = () => {
    setFormData(contactInfo);
    setValidationErrors({});
    setHasChanges(false);
    if (lastError) {
      clearError();
    }
  };

  const handleExport = () => {
    const exportData = {
      contactInfo: formData,
      exportDate: new Date().toISOString(),
      version: '1.0'
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `contact-info-backup-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  const handleImport = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const importData = JSON.parse(e.target?.result as string);
        
        if (importData.contactInfo && typeof importData.contactInfo === 'object') {
          const importedInfo = importData.contactInfo as ContactInfo;
          
          // Validate imported data structure
          const requiredFields: (keyof ContactInfo)[] = ['email', 'phone', 'github', 'linkedin'];
          const hasAllFields = requiredFields.every(field => 
            typeof importedInfo[field] === 'string'
          );
          
          if (hasAllFields) {
            setFormData(importedInfo);
          } else {
            alert('Invalid import file format');
          }
        } else {
          alert('Invalid import file format');
        }
      } catch (error) {
        alert('Failed to parse import file');
      }
    };
    
    reader.readAsText(file);
    // Reset the input
    event.target.value = '';
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-white mb-2">Contact Information</h2>
          <p className="text-gray-400">
            Manage your contact details displayed on the website
          </p>
        </div>
        
        <div className="flex gap-2">
          <PixelButton
            variant="secondary"
            size="sm"
            onClick={() => setShowPreview(!showPreview)}
            className="flex items-center gap-2"
          >
            <Eye className="w-4 h-4" />
            {showPreview ? 'Hide' : 'Show'} Preview
          </PixelButton>
        </div>
      </div>

      {/* Success Message */}
      {saveSuccess && (
        <PixelAlert variant="success">
          Contact information saved successfully!
        </PixelAlert>
      )}

      {/* Error Message */}
      {lastError && (
        <PixelAlert variant="danger">
          {lastError.message}
        </PixelAlert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Contact Form */}
        <PixelCard className="p-6">
          <h3 className="text-lg font-bold text-white mb-4">Edit Contact Details</h3>
          
          <div className="space-y-4">
            {/* Email */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                <Mail className="w-4 h-4 inline mr-2" />
                Email Address *
              </label>
              <PixelInput
                type="email"
                value={formData.email}
                onChange={(e) => handleInputChange('email', e.target.value)}
                placeholder="your.email@example.com"
                className={validationErrors.email ? 'border-red-500' : ''}
              />
              {validationErrors.email && (
                <p className="text-red-400 text-sm mt-1">{validationErrors.email}</p>
              )}
            </div>

            {/* Phone */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                <Phone className="w-4 h-4 inline mr-2" />
                Phone Number
              </label>
              <PixelInput
                type="tel"
                value={formData.phone}
                onChange={(e) => handleInputChange('phone', e.target.value)}
                placeholder="+84 123 456 789"
                className={validationErrors.phone ? 'border-red-500' : ''}
              />
              {validationErrors.phone && (
                <p className="text-red-400 text-sm mt-1">{validationErrors.phone}</p>
              )}
            </div>

            {/* GitHub */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                <Github className="w-4 h-4 inline mr-2" />
                GitHub Profile
              </label>
              <PixelInput
                type="url"
                value={formData.github}
                onChange={(e) => handleInputChange('github', e.target.value)}
                placeholder="https://github.com/yourusername"
                className={validationErrors.github ? 'border-red-500' : ''}
              />
              {validationErrors.github && (
                <p className="text-red-400 text-sm mt-1">{validationErrors.github}</p>
              )}
            </div>

            {/* LinkedIn */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                <Linkedin className="w-4 h-4 inline mr-2" />
                LinkedIn Profile
              </label>
              <PixelInput
                type="url"
                value={formData.linkedin}
                onChange={(e) => handleInputChange('linkedin', e.target.value)}
                placeholder="https://linkedin.com/in/yourusername"
                className={validationErrors.linkedin ? 'border-red-500' : ''}
              />
              {validationErrors.linkedin && (
                <p className="text-red-400 text-sm mt-1">{validationErrors.linkedin}</p>
              )}
            </div>
          </div>

          {/* Form Actions */}
          <div className="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-600">
            <PixelButton
              variant="primary"
              onClick={handleSave}
              disabled={!hasChanges || Object.keys(validationErrors).length > 0}
              className="flex items-center gap-2"
            >
              <Save className="w-4 h-4" />
              Save Changes
            </PixelButton>
            
            <PixelButton
              variant="secondary"
              onClick={handleReset}
              disabled={!hasChanges}
              className="flex items-center gap-2"
            >
              <RotateCcw className="w-4 h-4" />
              Reset
            </PixelButton>
          </div>
        </PixelCard>

        {/* Preview and Backup */}
        <div className="space-y-6">
          {/* Frontend Preview */}
          {showPreview && (
            <PixelCard className="p-6">
              <h3 className="text-lg font-bold text-white mb-4">Frontend Preview</h3>
              <div className="space-y-3">
                <div className="p-4 bg-gray-800 border border-gray-600 rounded">
                  <h4 className="text-white font-semibold mb-3">Contact Information</h4>
                  
                  {formData.email && (
                    <div className="flex items-center gap-2 mb-2">
                      <Mail className="w-4 h-4 text-blue-400" />
                      <a 
                        href={`mailto:${formData.email}`}
                        className="text-blue-400 hover:text-blue-300 transition-colors"
                      >
                        {formData.email}
                      </a>
                    </div>
                  )}
                  
                  {formData.phone && (
                    <div className="flex items-center gap-2 mb-2">
                      <Phone className="w-4 h-4 text-green-400" />
                      <a 
                        href={`tel:${formData.phone}`}
                        className="text-green-400 hover:text-green-300 transition-colors"
                      >
                        {formData.phone}
                      </a>
                    </div>
                  )}
                  
                  {formData.github && (
                    <div className="flex items-center gap-2 mb-2">
                      <Github className="w-4 h-4 text-gray-400" />
                      <a 
                        href={formData.github}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-gray-400 hover:text-gray-300 transition-colors flex items-center gap-1"
                      >
                        GitHub Profile
                        <ExternalLink className="w-3 h-3" />
                      </a>
                    </div>
                  )}
                  
                  {formData.linkedin && (
                    <div className="flex items-center gap-2">
                      <Linkedin className="w-4 h-4 text-blue-500" />
                      <a 
                        href={formData.linkedin}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-500 hover:text-blue-400 transition-colors flex items-center gap-1"
                      >
                        LinkedIn Profile
                        <ExternalLink className="w-3 h-3" />
                      </a>
                    </div>
                  )}
                </div>
              </div>
            </PixelCard>
          )}

          {/* Backup and Restore */}
          <PixelCard className="p-6">
            <h3 className="text-lg font-bold text-white mb-4">Backup & Restore</h3>
            
            <div className="space-y-3">
              <PixelButton
                variant="secondary"
                onClick={handleExport}
                className="w-full flex items-center justify-center gap-2"
              >
                <Download className="w-4 h-4" />
                Export Contact Info
              </PixelButton>
              
              <div>
                <input
                  type="file"
                  accept=".json"
                  onChange={handleImport}
                  className="hidden"
                  id="import-contact-info"
                />
                <label 
                  htmlFor="import-contact-info"
                  className="block"
                >
                  <div className="w-full flex items-center justify-center gap-2 cursor-pointer px-4 py-2 text-base font-mono border-2 transition-all duration-100 active:translate-y-0.5 active:shadow-none bg-gray-600 border-gray-800 text-white shadow-[0_4px_0_0_#374151] hover:bg-gray-700 hover:border-gray-900">
                    <Upload className="w-4 h-4" />
                    Import Contact Info
                  </div>
                </label>
              </div>
            </div>
            
            <div className="mt-4 p-3 bg-gray-800 border border-gray-600 rounded">
              <p className="text-xs text-gray-400">
                <strong>Backup:</strong> Export your contact information as a JSON file for safekeeping.
                <br />
                <strong>Restore:</strong> Import a previously exported contact info file to restore your settings.
              </p>
            </div>
          </PixelCard>
        </div>
      </div>
    </div>
  );
};
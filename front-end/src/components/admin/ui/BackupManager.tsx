import React, { useState, useEffect } from 'react';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { ConfirmationDialog } from '@/components/admin/ui/DataChangeNotification';
import {
  Download,
  Upload,
  Save,
  Trash2,
  RefreshCw,
  Clock,
  FileText,
  AlertTriangle,
  CheckCircle,
  Info
} from 'lucide-react';
import {
  exportAdminData,
  downloadExportedData,
  importAdminData,
  validateImportData,
  createAutomaticBackup,
  getAvailableBackups,
  restoreFromBackup,
  deleteBackup,
  cleanupOldBackups,
  BackupConfig,
  ImportValidationResult
} from '@/utils/dataBackupExport';

interface BackupManagerProps {
  className?: string;
}

export const BackupManager: React.FC<BackupManagerProps> = ({ className = '' }) => {
  const [backups, setBackups] = useState<ReturnType<typeof getAvailableBackups>>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showImportDialog, setShowImportDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [showRestoreDialog, setShowRestoreDialog] = useState(false);
  const [selectedBackup, setSelectedBackup] = useState<string>('');
  const [importFile, setImportFile] = useState<File | null>(null);
  const [importValidation, setImportValidation] = useState<ImportValidationResult | null>(null);
  const [backupConfig, setBackupConfig] = useState<BackupConfig>({
    includeMessages: true,
    includeImages: true,
    compress: false,
    maxBackups: 10
  });

  // Load backups on mount
  useEffect(() => {
    loadBackups();
  }, []);

  const loadBackups = () => {
    try {
      const availableBackups = getAvailableBackups();
      setBackups(availableBackups);
    } catch (error) {
      console.error('Failed to load backups:', error);
    }
  };

  const handleCreateBackup = async () => {
    setIsLoading(true);
    try {
      await createAutomaticBackup('manual');
      loadBackups();
    } catch (error) {
      console.error('Failed to create backup:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleExportData = () => {
    setIsLoading(true);
    try {
      downloadExportedData(backupConfig);
    } catch (error) {
      console.error('Failed to export data:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      setImportFile(file);
      validateImportFile(file);
    }
  };

  const validateImportFile = async (file: File) => {
    try {
      const text = await file.text();
      const validation = validateImportData(text);
      setImportValidation(validation);
    } catch (error) {
      setImportValidation({
        isValid: false,
        errors: ['Failed to read file'],
        warnings: []
      });
    }
  };

  const handleImportData = async () => {
    if (!importFile) return;

    setIsLoading(true);
    try {
      const text = await importFile.text();
      const success = importAdminData(text, { overwrite: false, createBackup: true });
      
      if (success) {
        setShowImportDialog(false);
        setImportFile(null);
        setImportValidation(null);
        loadBackups();
      }
    } catch (error) {
      console.error('Failed to import data:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRestoreBackup = () => {
    if (!selectedBackup) return;

    setIsLoading(true);
    try {
      const success = restoreFromBackup(selectedBackup);
      if (success) {
        setShowRestoreDialog(false);
        setSelectedBackup('');
        loadBackups();
      }
    } catch (error) {
      console.error('Failed to restore backup:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDeleteBackup = () => {
    if (!selectedBackup) return;

    try {
      const success = deleteBackup(selectedBackup);
      if (success) {
        setShowDeleteDialog(false);
        setSelectedBackup('');
        loadBackups();
      }
    } catch (error) {
      console.error('Failed to delete backup:', error);
    }
  };

  const handleCleanupBackups = () => {
    try {
      cleanupOldBackups(backupConfig.maxBackups);
      loadBackups();
    } catch (error) {
      console.error('Failed to cleanup backups:', error);
    }
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  };

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Export & Import Section */}
      <PixelCard>
        <div className="p-6">
          <h3 className="font-pixel text-lg font-bold text-foreground mb-4">
            Data Export & Import
          </h3>
          
          {/* Export Configuration */}
          <div className="mb-4 space-y-3">
            <h4 className="font-pixel text-sm font-bold text-muted-foreground">Export Options</h4>
            <div className="grid grid-cols-2 gap-4">
              <label className="flex items-center gap-2 font-pixel text-sm">
                <input
                  type="checkbox"
                  checked={backupConfig.includeMessages}
                  onChange={(e) => setBackupConfig(prev => ({ ...prev, includeMessages: e.target.checked }))}
                  className="rounded"
                />
                Include Messages
              </label>
              <label className="flex items-center gap-2 font-pixel text-sm">
                <input
                  type="checkbox"
                  checked={backupConfig.includeImages}
                  onChange={(e) => setBackupConfig(prev => ({ ...prev, includeImages: e.target.checked }))}
                  className="rounded"
                />
                Include Images
              </label>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex flex-wrap gap-3">
            <PixelButton
              variant="primary"
              onClick={handleExportData}
              disabled={isLoading}
              className="flex items-center gap-2"
            >
              <Download className="w-4 h-4" />
              Export Data
            </PixelButton>

            <PixelButton
              variant="secondary"
              onClick={() => setShowImportDialog(true)}
              disabled={isLoading}
              className="flex items-center gap-2"
            >
              <Upload className="w-4 h-4" />
              Import Data
            </PixelButton>

            <PixelButton
              variant="secondary"
              onClick={handleCreateBackup}
              disabled={isLoading}
              className="flex items-center gap-2"
            >
              <Save className="w-4 h-4" />
              Create Backup
            </PixelButton>
          </div>
        </div>
      </PixelCard>

      {/* Backups List */}
      <PixelCard>
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-pixel text-lg font-bold text-foreground">
              Available Backups ({backups.length})
            </h3>
            <div className="flex gap-2">
              <PixelButton
                variant="secondary"
                size="sm"
                onClick={loadBackups}
                className="flex items-center gap-2"
              >
                <RefreshCw className="w-4 h-4" />
                Refresh
              </PixelButton>
              <PixelButton
                variant="secondary"
                size="sm"
                onClick={handleCleanupBackups}
                className="flex items-center gap-2"
              >
                <Trash2 className="w-4 h-4" />
                Cleanup Old
              </PixelButton>
            </div>
          </div>

          {backups.length === 0 ? (
            <div className="text-center py-8">
              <FileText className="w-12 h-12 text-muted-foreground mx-auto mb-3" />
              <p className="font-pixel text-muted-foreground">No backups available</p>
              <p className="font-pixel text-xs text-muted-foreground mt-1">
                Create your first backup to get started
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {backups.map((backup) => (
                <div
                  key={backup.key}
                  className="flex items-center justify-between p-3 bg-muted/30 border border-border rounded"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <Clock className="w-4 h-4 text-muted-foreground" />
                      <span className="font-pixel text-sm font-bold text-foreground">
                        {backup.reason.replace('_', ' ').toUpperCase()}
                      </span>
                    </div>
                    <p className="font-pixel text-xs text-muted-foreground">
                      {backup.date} • {formatFileSize(backup.size)}
                    </p>
                  </div>
                  
                  <div className="flex gap-2">
                    <PixelButton
                      variant="secondary"
                      size="sm"
                      onClick={() => {
                        setSelectedBackup(backup.key);
                        setShowRestoreDialog(true);
                      }}
                      className="flex items-center gap-1"
                    >
                      <RefreshCw className="w-3 h-3" />
                      Restore
                    </PixelButton>
                    <PixelButton
                      variant="danger"
                      size="sm"
                      onClick={() => {
                        setSelectedBackup(backup.key);
                        setShowDeleteDialog(true);
                      }}
                      className="flex items-center gap-1"
                    >
                      <Trash2 className="w-3 h-3" />
                      Delete
                    </PixelButton>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </PixelCard>

      {/* Import Dialog */}
      <ConfirmationDialog
        isOpen={showImportDialog}
        onClose={() => {
          setShowImportDialog(false);
          setImportFile(null);
          setImportValidation(null);
        }}
        onConfirm={handleImportData}
        title="Import Admin Data"
        message="Select a JSON file to import admin data. This will merge with existing data."
        confirmText="Import"
        cancelText="Cancel"
        type="info"
      >
        <div className="space-y-4">
          {/* File Input */}
          <div>
            <label className="block font-pixel text-sm font-bold text-foreground mb-2">
              Select Import File
            </label>
            <input
              type="file"
              accept=".json"
              onChange={handleFileSelect}
              className="w-full p-2 border border-border rounded bg-background text-foreground font-pixel text-sm"
            />
          </div>

          {/* Validation Results */}
          {importValidation && (
            <div className="space-y-2">
              {importValidation.isValid ? (
                <div className="flex items-center gap-2 p-2 bg-green-500/10 border border-green-500/30 rounded">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span className="font-pixel text-sm text-green-600">File is valid and ready to import</span>
                </div>
              ) : (
                <div className="flex items-start gap-2 p-2 bg-red-500/10 border border-red-500/30 rounded">
                  <AlertTriangle className="w-4 h-4 text-red-500 mt-0.5" />
                  <div>
                    <p className="font-pixel text-sm text-red-600 font-bold">Validation Errors:</p>
                    <ul className="font-pixel text-xs text-red-600 mt-1 space-y-1">
                      {importValidation.errors.map((error, index) => (
                        <li key={index}>• {error}</li>
                      ))}
                    </ul>
                  </div>
                </div>
              )}

              {importValidation.warnings.length > 0 && (
                <div className="flex items-start gap-2 p-2 bg-yellow-500/10 border border-yellow-500/30 rounded">
                  <Info className="w-4 h-4 text-yellow-500 mt-0.5" />
                  <div>
                    <p className="font-pixel text-sm text-yellow-600 font-bold">Warnings:</p>
                    <ul className="font-pixel text-xs text-yellow-600 mt-1 space-y-1">
                      {importValidation.warnings.map((warning, index) => (
                        <li key={index}>• {warning}</li>
                      ))}
                    </ul>
                  </div>
                </div>
              )}

              {importValidation.metadata && (
                <div className="p-2 bg-blue-500/10 border border-blue-500/30 rounded">
                  <p className="font-pixel text-sm text-blue-600 font-bold mb-1">Import Preview:</p>
                  <div className="font-pixel text-xs text-blue-600 space-y-1">
                    <p>Version: {importValidation.metadata.version}</p>
                    <p>Export Date: {new Date(importValidation.metadata.exportDate).toLocaleString()}</p>
                    <p>Items: {Object.entries(importValidation.metadata.itemCounts).map(([key, count]) => 
                      `${key}: ${count}`
                    ).join(', ')}</p>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </ConfirmationDialog>

      {/* Restore Confirmation Dialog */}
      <ConfirmationDialog
        isOpen={showRestoreDialog}
        onClose={() => {
          setShowRestoreDialog(false);
          setSelectedBackup('');
        }}
        onConfirm={handleRestoreBackup}
        title="Restore from Backup"
        message="This will replace all current data with the backup data. A backup of current data will be created automatically."
        confirmText="Restore"
        cancelText="Cancel"
        type="warning"
      />

      {/* Delete Confirmation Dialog */}
      <ConfirmationDialog
        isOpen={showDeleteDialog}
        onClose={() => {
          setShowDeleteDialog(false);
          setSelectedBackup('');
        }}
        onConfirm={handleDeleteBackup}
        title="Delete Backup"
        message="Are you sure you want to delete this backup? This action cannot be undone."
        confirmText="Delete"
        cancelText="Cancel"
        type="danger"
      />
    </div>
  );
};
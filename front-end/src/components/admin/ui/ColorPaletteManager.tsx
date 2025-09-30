import React, { useState } from 'react';
import { PixelCard } from './PixelCard';
import { PixelButton } from './PixelButton';
import { ColorPicker } from './ColorPicker';
import { PixelAlert } from './PixelAlert';
import { cn } from '@/lib/utils';

interface ColorPaletteManagerProps {
  colors: string[];
  onChange: (colors: string[]) => void;
  maxColors?: number;
  minColors?: number;
  presetSchemes?: { name: string; colors: string[] }[];
  onPreview?: (colors: string[]) => void;
  showPreview?: boolean;
}

// Predefined retro color schemes
const DEFAULT_PRESET_SCHEMES = [
  {
    name: 'Classic Retro',
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316']
  },
  {
    name: 'Neon Nights',
    colors: ['#ff00ff', '#00ffff', '#ffff00', '#ff0080', '#8000ff', '#00ff80', '#ff8000', '#0080ff']
  },
  {
    name: 'Pixel Perfect',
    colors: ['#1e40af', '#16a34a', '#ca8a04', '#dc2626', '#7c3aed', '#0891b2', '#65a30d', '#ea580c']
  },
  {
    name: 'Pastel Dreams',
    colors: ['#93c5fd', '#86efac', '#fbbf24', '#fca5a5', '#c4b5fd', '#67e8f9', '#a3e635', '#fdba74']
  },
  {
    name: 'Dark Mode',
    colors: ['#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#e5e7eb', '#f3f4f6', '#f9fafb']
  },
  {
    name: 'Cyberpunk',
    colors: ['#ff0040', '#00ff80', '#4080ff', '#ff8000', '#8040ff', '#40ff80', '#ff4080', '#80ff40']
  }
];

export const ColorPaletteManager: React.FC<ColorPaletteManagerProps> = ({
  colors,
  onChange,
  maxColors = 16,
  minColors = 4,
  presetSchemes = DEFAULT_PRESET_SCHEMES,
  onPreview,
  showPreview = false
}) => {
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
  const [showExportModal, setShowExportModal] = useState(false);
  const [importData, setImportData] = useState('');
  const [error, setError] = useState<string | null>(null);

  const handleColorChange = (index: number, newColor: string) => {
    const newColors = [...colors];
    newColors[index] = newColor;
    onChange(newColors);
    
    if (showPreview && onPreview) {
      onPreview(newColors);
    }
  };

  const handleAddColor = () => {
    if (colors.length >= maxColors) {
      setError(`Maximum ${maxColors} colors allowed`);
      return;
    }
    
    const newColors = [...colors, '#3b82f6'];
    onChange(newColors);
    setEditingIndex(newColors.length - 1);
  };

  const handleRemoveColor = (index: number) => {
    if (colors.length <= minColors) {
      setError(`Minimum ${minColors} colors required`);
      return;
    }
    
    const newColors = colors.filter((_, i) => i !== index);
    onChange(newColors);
    setEditingIndex(null);
  };

  const handleDragStart = (e: React.DragEvent, index: number) => {
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    setDragOverIndex(index);
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
    setDragOverIndex(null);
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    
    if (draggedIndex === null || draggedIndex === dropIndex) return;
    
    const newColors = [...colors];
    const draggedColor = newColors[draggedIndex];
    
    // Remove dragged color
    newColors.splice(draggedIndex, 1);
    
    // Insert at new position
    const insertIndex = draggedIndex < dropIndex ? dropIndex - 1 : dropIndex;
    newColors.splice(insertIndex, 0, draggedColor);
    
    onChange(newColors);
    setDraggedIndex(null);
    setDragOverIndex(null);
  };

  const handleApplyPreset = (preset: { name: string; colors: string[] }) => {
    onChange(preset.colors);
    if (showPreview && onPreview) {
      onPreview(preset.colors);
    }
  };

  const handleExportPalette = () => {
    const exportData = {
      name: 'Custom Palette',
      colors: colors,
      exportDate: new Date().toISOString(),
      version: '1.0'
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'color-palette.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    setShowExportModal(false);
  };

  const handleImportPalette = () => {
    try {
      const parsed = JSON.parse(importData);
      
      if (!parsed.colors || !Array.isArray(parsed.colors)) {
        throw new Error('Invalid palette format: missing colors array');
      }
      
      // Validate colors
      const validColors = parsed.colors.filter((color: string) => 
        typeof color === 'string' && /^#[0-9A-F]{6}$/i.test(color)
      );
      
      if (validColors.length === 0) {
        throw new Error('No valid colors found in import data');
      }
      
      if (validColors.length < minColors) {
        throw new Error(`Palette must contain at least ${minColors} colors`);
      }
      
      if (validColors.length > maxColors) {
        validColors.splice(maxColors);
      }
      
      onChange(validColors);
      setImportData('');
      setError(null);
      
      if (showPreview && onPreview) {
        onPreview(validColors);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to import palette');
    }
  };

  const clearError = () => setError(null);

  return (
    <div className="space-y-6">
      {/* Error Display */}
      {error && (
        <PixelAlert
          type="error"
          title="Palette Error"
          message={error}
          onClose={clearError}
        />
      )}

      {/* Color Grid */}
      <PixelCard
        title="Color Palette Editor"
        subtitle={`${colors.length}/${maxColors} colors`}
        icon="🎨"
      >
        <div className="space-y-4">
          {/* Color Grid */}
          <div className="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
            {colors.map((color, index) => (
              <div
                key={index}
                className={cn(
                  'relative group',
                  dragOverIndex === index && draggedIndex !== index && 'ring-2 ring-blue-500'
                )}
                draggable
                onDragStart={(e) => handleDragStart(e, index)}
                onDragOver={(e) => handleDragOver(e, index)}
                onDragEnd={handleDragEnd}
                onDrop={(e) => handleDrop(e, index)}
              >
                <div
                  className={cn(
                    'w-full aspect-square border-2 cursor-pointer transition-all duration-200 hover:scale-105',
                    editingIndex === index 
                      ? 'border-white shadow-[0_0_0_2px_#3b82f6]' 
                      : 'border-gray-500 hover:border-gray-400',
                    draggedIndex === index && 'opacity-50 scale-95'
                  )}
                  style={{ backgroundColor: color }}
                  onClick={() => setEditingIndex(editingIndex === index ? null : index)}
                  title={`${color.toUpperCase()} - Click to edit`}
                />
                
                {/* Color Actions */}
                <div className="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleRemoveColor(index);
                    }}
                    className="w-6 h-6 bg-red-600 border-2 border-red-800 text-white text-xs font-mono hover:bg-red-700 transition-colors duration-200"
                    disabled={colors.length <= minColors}
                    title="Remove color"
                  >
                    ✕
                  </button>
                </div>
                
                {/* Color Value */}
                <div className="absolute -bottom-6 left-0 right-0 text-center">
                  <span className="text-xs font-mono text-gray-400 bg-gray-800 px-1">
                    {color.toUpperCase()}
                  </span>
                </div>
              </div>
            ))}
            
            {/* Add Color Button */}
            {colors.length < maxColors && (
              <button
                onClick={handleAddColor}
                className="w-full aspect-square border-2 border-dashed border-gray-600 hover:border-gray-500 bg-gray-800 hover:bg-gray-700 transition-all duration-200 flex items-center justify-center text-gray-400 hover:text-white text-2xl"
                title="Add new color"
              >
                +
              </button>
            )}
          </div>
          
          {/* Color Editor */}
          {editingIndex !== null && (
            <div className="border-t-2 border-gray-600 pt-4">
              <h4 className="text-sm font-mono text-gray-400 mb-3">
                Editing Color {editingIndex + 1}
              </h4>
              <ColorPicker
                value={colors[editingIndex]}
                onChange={(color) => handleColorChange(editingIndex, color)}
                presetColors={colors}
              />
            </div>
          )}
        </div>
      </PixelCard>

      {/* Preset Schemes */}
      <PixelCard
        title="Preset Color Schemes"
        subtitle="Quick color palette templates"
        icon="🎯"
      >
        <div className="space-y-3">
          {presetSchemes.map((scheme, schemeIndex) => (
            <div
              key={schemeIndex}
              className="flex items-center justify-between p-3 border-2 border-gray-600 hover:border-gray-500 bg-gray-700 hover:bg-gray-600 transition-all duration-200"
            >
              <div className="flex items-center gap-3">
                <div className="flex gap-1">
                  {scheme.colors.slice(0, 8).map((color, colorIndex) => (
                    <div
                      key={colorIndex}
                      className="w-4 h-4 border border-gray-500"
                      style={{ backgroundColor: color }}
                    />
                  ))}
                  {scheme.colors.length > 8 && (
                    <div className="w-4 h-4 border border-gray-500 bg-gray-800 flex items-center justify-center text-xs text-gray-400">
                      +{scheme.colors.length - 8}
                    </div>
                  )}
                </div>
                <span className="font-mono text-white text-sm">{scheme.name}</span>
              </div>
              
              <PixelButton
                size="sm"
                variant="secondary"
                onClick={() => handleApplyPreset(scheme)}
              >
                Apply
              </PixelButton>
            </div>
          ))}
        </div>
      </PixelCard>

      {/* Import/Export */}
      <PixelCard
        title="Import/Export"
        subtitle="Backup and restore color palettes"
        icon="💾"
      >
        <div className="space-y-4">
          <div className="flex gap-3">
            <PixelButton
              variant="info"
              size="sm"
              onClick={() => setShowExportModal(true)}
            >
              Export Palette
            </PixelButton>
          </div>
          
          {/* Import Section */}
          <div className="border-t-2 border-gray-600 pt-4">
            <h4 className="text-sm font-mono text-gray-400 mb-2">Import Palette</h4>
            <div className="space-y-3">
              <textarea
                value={importData}
                onChange={(e) => setImportData(e.target.value)}
                placeholder="Paste exported palette JSON here..."
                className="w-full h-24 px-3 py-2 font-mono text-sm bg-gray-800 border-2 border-gray-600 text-white resize-none focus:outline-none focus:border-blue-500"
              />
              <div className="flex gap-2">
                <PixelButton
                  variant="success"
                  size="sm"
                  onClick={handleImportPalette}
                  disabled={!importData.trim()}
                >
                  Import
                </PixelButton>
                <PixelButton
                  variant="secondary"
                  size="sm"
                  onClick={() => setImportData('')}
                >
                  Clear
                </PixelButton>
              </div>
            </div>
          </div>
        </div>
      </PixelCard>

      {/* Export Modal */}
      {showExportModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <PixelCard className="max-w-md w-full">
            <div className="space-y-4">
              <h3 className="text-lg font-bold text-white font-mono">Export Color Palette</h3>
              
              <div className="space-y-3">
                <div>
                  <div className="text-sm font-mono text-gray-400 mb-2">Current Palette</div>
                  <div className="flex gap-1 flex-wrap">
                    {colors.map((color, index) => (
                      <div
                        key={index}
                        className="w-6 h-6 border-2 border-gray-500"
                        style={{ backgroundColor: color }}
                        title={color}
                      />
                    ))}
                  </div>
                </div>
                
                <div className="text-sm font-mono text-gray-300">
                  This will download a JSON file containing your current color palette.
                  You can import this file later to restore the palette.
                </div>
              </div>
              
              <div className="flex gap-3 pt-4 border-t-2 border-gray-600">
                <PixelButton
                  variant="primary"
                  onClick={handleExportPalette}
                >
                  Download
                </PixelButton>
                <PixelButton
                  variant="secondary"
                  onClick={() => setShowExportModal(false)}
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
import React, { useState } from 'react';
import { PixelButton } from './PixelButton';
import { PixelCard } from './PixelCard';
import { PixelInput } from './PixelInput';
import { cn } from '@/lib/utils';

interface ColorPickerProps {
  value: string;
  onChange: (color: string) => void;
  label?: string;
  error?: string;
  presetColors?: string[];
}

// Predefined retro/pixel art color palette
const DEFAULT_PRESET_COLORS = [
  // Blues
  '#1e40af', '#3b82f6', '#60a5fa', '#93c5fd',
  // Greens  
  '#16a34a', '#22c55e', '#4ade80', '#86efac',
  // Reds
  '#dc2626', '#ef4444', '#f87171', '#fca5a5',
  // Yellows/Oranges
  '#ca8a04', '#eab308', '#f59e0b', '#fbbf24',
  // Purples
  '#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd',
  // Cyans
  '#0891b2', '#06b6d4', '#22d3ee', '#67e8f9',
  // Grays
  '#374151', '#4b5563', '#6b7280', '#9ca3af',
  // Dark colors
  '#111827', '#1f2937', '#374151', '#4b5563'
];

export const ColorPicker: React.FC<ColorPickerProps> = ({
  value,
  onChange,
  label,
  error,
  presetColors = DEFAULT_PRESET_COLORS
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [customColor, setCustomColor] = useState(value);

  const handleColorSelect = (color: string) => {
    onChange(color);
    setCustomColor(color);
    setIsOpen(false);
  };

  const handleCustomColorChange = (color: string) => {
    setCustomColor(color);
  };

  const handleCustomColorSubmit = () => {
    if (customColor && /^#[0-9A-F]{6}$/i.test(customColor)) {
      onChange(customColor);
      setIsOpen(false);
    }
  };

  const isValidHex = (color: string) => /^#[0-9A-F]{6}$/i.test(color);

  return (
    <div className="space-y-2">
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}
      
      <div className="relative">
        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className={cn(
            'w-full flex items-center justify-between px-3 py-2 font-mono border-2 bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 transition-all duration-200',
            error 
              ? 'border-red-600 hover:border-red-500 focus:border-red-400 focus:ring-red-500'
              : 'border-gray-600 hover:border-gray-500 focus:border-blue-500 focus:ring-blue-500'
          )}
        >
          <div className="flex items-center gap-3">
            <div 
              className="w-6 h-6 border-2 border-gray-500 flex-shrink-0"
              style={{ backgroundColor: value }}
            />
            <span className="text-sm text-gray-300 font-mono">
              {value.toUpperCase()}
            </span>
          </div>
          <span className="text-gray-400">
            {isOpen ? '▲' : '▼'}
          </span>
        </button>

        {isOpen && (
          <div className="absolute top-full left-0 right-0 z-50 mt-1">
            <PixelCard className="max-h-96 overflow-hidden">
              <div className="space-y-4">
                {/* Preset Colors */}
                <div>
                  <h4 className="text-sm font-mono text-gray-400 mb-2">Preset Colors</h4>
                  <div className="grid grid-cols-8 gap-2">
                    {presetColors.map((color) => (
                      <button
                        key={color}
                        onClick={() => handleColorSelect(color)}
                        className={cn(
                          'w-8 h-8 border-2 transition-all duration-200 hover:scale-110',
                          value === color
                            ? 'border-white shadow-[0_0_0_2px_#3b82f6]'
                            : 'border-gray-500 hover:border-gray-400'
                        )}
                        style={{ backgroundColor: color }}
                        title={color.toUpperCase()}
                      />
                    ))}
                  </div>
                </div>

                {/* Custom Color Input */}
                <div className="border-t-2 border-gray-600 pt-4">
                  <h4 className="text-sm font-mono text-gray-400 mb-2">Custom Color</h4>
                  <div className="space-y-3">
                    {/* Hex Input */}
                    <div className="flex gap-2">
                      <PixelInput
                        placeholder="#3b82f6"
                        value={customColor}
                        onChange={(e) => handleCustomColorChange(e.target.value)}
                        className="flex-1"
                        error={customColor && !isValidHex(customColor) ? 'Invalid hex color' : undefined}
                      />
                      <PixelButton
                        size="sm"
                        onClick={handleCustomColorSubmit}
                        disabled={!customColor || !isValidHex(customColor)}
                      >
                        Apply
                      </PixelButton>
                    </div>

                    {/* Native Color Picker */}
                    <div className="flex items-center gap-3">
                      <input
                        type="color"
                        value={customColor}
                        onChange={(e) => handleCustomColorChange(e.target.value)}
                        className="w-12 h-8 border-2 border-gray-600 bg-gray-800 cursor-pointer"
                      />
                      <span className="text-sm text-gray-400 font-mono">
                        Or use color picker
                      </span>
                    </div>

                    {/* Color Preview */}
                    {customColor && isValidHex(customColor) && (
                      <div className="flex items-center gap-3 p-2 border-2 border-gray-600 bg-gray-700">
                        <div 
                          className="w-8 h-8 border-2 border-gray-500"
                          style={{ backgroundColor: customColor }}
                        />
                        <div className="text-sm font-mono">
                          <div className="text-white">{customColor.toUpperCase()}</div>
                          <div className="text-gray-400">Preview</div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Close Button */}
                <div className="border-t-2 border-gray-600 pt-4">
                  <PixelButton
                    variant="secondary"
                    size="sm"
                    onClick={() => setIsOpen(false)}
                    fullWidth
                  >
                    Close
                  </PixelButton>
                </div>
              </div>
            </PixelCard>
          </div>
        )}
      </div>

      {error && (
        <p className="text-sm text-red-400 flex items-center gap-1 font-mono">
          <span>⚠</span>
          {error}
        </p>
      )}
    </div>
  );
};
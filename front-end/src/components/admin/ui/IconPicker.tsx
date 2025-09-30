import React, { useState } from 'react';
import { PixelButton } from './PixelButton';
import { PixelCard } from './PixelCard';
import { PixelInput } from './PixelInput';
import { cn } from '@/lib/utils';

interface IconPickerProps {
  value: string;
  onChange: (icon: string) => void;
  label?: string;
  error?: string;
}

// Predefined pixel art style icons for services
const ICON_CATEGORIES = {
  'Development': ['💻', '⚙️', '🔧', '🛠️', '📱', '🖥️', '⌨️', '🖱️', '💾', '🔌'],
  'Design': ['🎨', '✏️', '📐', '🖌️', '🎭', '🖼️', '📏', '🔍', '💡', '✨'],
  'Business': ['💼', '📊', '📈', '💰', '🏢', '📋', '📝', '📞', '📧', '🤝'],
  'Marketing': ['📢', '📣', '🎯', '📺', '📻', '📰', '🔊', '📱', '💬', '🌐'],
  'Analytics': ['📊', '📈', '📉', '🔍', '📋', '📑', '🧮', '⚡', '🎯', '📌'],
  'Security': ['🔒', '🛡️', '🔐', '🔑', '⚠️', '🚨', '👁️', '🔍', '🛠️', '⚙️'],
  'Communication': ['📞', '📧', '💬', '📱', '📻', '📺', '🔊', '📢', '📣', '🗣️'],
  'Tools': ['🔧', '🛠️', '⚙️', '🔨', '📏', '📐', '✂️', '📎', '📌', '🔗']
};

const ALL_ICONS = Object.values(ICON_CATEGORIES).flat();

export const IconPicker: React.FC<IconPickerProps> = ({
  value,
  onChange,
  label,
  error
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string>('Development');
  const [customIcon, setCustomIcon] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const handleIconSelect = (icon: string) => {
    onChange(icon);
    setIsOpen(false);
  };

  const handleCustomIconSubmit = () => {
    if (customIcon.trim()) {
      onChange(customIcon.trim());
      setCustomIcon('');
      setIsOpen(false);
    }
  };

  const filteredIcons = searchTerm 
    ? ALL_ICONS.filter(icon => {
        // Simple search - you could enhance this with icon names/descriptions
        return true; // For now, show all icons when searching
      })
    : ICON_CATEGORIES[selectedCategory] || [];

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
            <span className="text-2xl">{value || '❓'}</span>
            <span className="text-sm text-gray-300">
              {value ? 'Selected Icon' : 'Choose Icon'}
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
                {/* Search */}
                <PixelInput
                  placeholder="Search icons..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  variant="search"
                />

                {/* Category Tabs */}
                {!searchTerm && (
                  <div className="flex flex-wrap gap-1">
                    {Object.keys(ICON_CATEGORIES).map((category) => (
                      <button
                        key={category}
                        onClick={() => setSelectedCategory(category)}
                        className={cn(
                          'px-2 py-1 text-xs font-mono border-2 transition-colors duration-200',
                          selectedCategory === category
                            ? 'bg-blue-600 border-blue-800 text-white'
                            : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600'
                        )}
                      >
                        {category}
                      </button>
                    ))}
                  </div>
                )}

                {/* Icon Grid */}
                <div className="max-h-48 overflow-y-auto">
                  <div className="grid grid-cols-8 gap-2">
                    {filteredIcons.map((icon, index) => (
                      <button
                        key={`${icon}-${index}`}
                        onClick={() => handleIconSelect(icon)}
                        className={cn(
                          'w-10 h-10 flex items-center justify-center border-2 transition-all duration-200 hover:scale-110',
                          value === icon
                            ? 'bg-blue-600 border-blue-800 text-white'
                            : 'bg-gray-700 border-gray-600 hover:bg-gray-600 hover:border-gray-500'
                        )}
                        title={`Select ${icon}`}
                      >
                        <span className="text-lg">{icon}</span>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Custom Icon Input */}
                <div className="border-t-2 border-gray-600 pt-4">
                  <div className="flex gap-2">
                    <PixelInput
                      placeholder="Custom emoji..."
                      value={customIcon}
                      onChange={(e) => setCustomIcon(e.target.value)}
                      maxLength={2}
                      className="flex-1"
                    />
                    <PixelButton
                      size="sm"
                      onClick={handleCustomIconSubmit}
                      disabled={!customIcon.trim()}
                    >
                      Add
                    </PixelButton>
                  </div>
                  <p className="text-xs text-gray-500 font-mono mt-1">
                    Enter any emoji or Unicode character
                  </p>
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
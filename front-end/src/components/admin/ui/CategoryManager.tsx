import React, { useState, useMemo } from 'react';
import { cn } from '@/lib/utils';
import { PixelButton, PixelInput, PixelSelect, PixelCard } from '@/components/admin/ui';

interface CategoryManagerProps {
  selectedCategory: string;
  onCategoryChange: (category: string) => void;
  existingCategories?: string[];
  label?: string;
  error?: string;
  helperText?: string;
  allowCustom?: boolean;
  className?: string;
}

const predefinedCategories = [
  'Web Application',
  'Mobile App',
  'E-commerce',
  'Portfolio',
  'Landing Page',
  'Dashboard',
  'API/Backend',
  'Game',
  'Tool/Utility',
  'Blog/CMS',
  'Social Media',
  'Educational',
  'Healthcare',
  'Finance',
  'Real Estate',
  'Travel',
  'Food & Restaurant',
  'Entertainment',
  'Business',
  'Other'
];

export const CategoryManager: React.FC<CategoryManagerProps> = ({
  selectedCategory,
  onCategoryChange,
  existingCategories = [],
  label = "Project Category",
  error,
  helperText,
  allowCustom = true,
  className
}) => {
  const [showCustomInput, setShowCustomInput] = useState(false);
  const [customCategory, setCustomCategory] = useState('');

  // Combine predefined and existing categories, remove duplicates
  const allCategories = useMemo(() => {
    const combined = [...predefinedCategories, ...existingCategories];
    return Array.from(new Set(combined)).sort();
  }, [existingCategories]);

  // Get category usage statistics
  const categoryStats = useMemo(() => {
    const stats: Record<string, number> = {};
    existingCategories.forEach(category => {
      stats[category] = (stats[category] || 0) + 1;
    });
    return stats;
  }, [existingCategories]);

  const handleCategorySelect = (category: string) => {
    if (category === 'custom') {
      setShowCustomInput(true);
      setCustomCategory('');
    } else {
      onCategoryChange(category);
      setShowCustomInput(false);
    }
  };

  const handleCustomCategorySubmit = () => {
    const trimmedCategory = customCategory.trim();
    if (trimmedCategory) {
      onCategoryChange(trimmedCategory);
      setShowCustomInput(false);
      setCustomCategory('');
    }
  };

  const handleCustomCategoryKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleCustomCategorySubmit();
    } else if (e.key === 'Escape') {
      setShowCustomInput(false);
      setCustomCategory('');
    }
  };

  return (
    <div className={cn('space-y-3', className)}>
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}

      {/* Category Selection */}
      <div className="space-y-3">
        {/* Dropdown Selection */}
        <PixelSelect
          value={showCustomInput ? 'custom' : selectedCategory}
          onChange={(e) => handleCategorySelect(e.target.value)}
          error={error}
        >
          <option value="">Select a category</option>
          
          {/* Popular Categories (if we have usage stats) */}
          {Object.keys(categoryStats).length > 0 && (
            <optgroup label="Popular Categories">
              {Object.entries(categoryStats)
                .sort(([,a], [,b]) => b - a)
                .slice(0, 5)
                .map(([category, count]) => (
                  <option key={category} value={category}>
                    {category} ({count} project{count !== 1 ? 's' : ''})
                  </option>
                ))}
            </optgroup>
          )}
          
          {/* All Categories */}
          <optgroup label="All Categories">
            {allCategories.map(category => (
              <option key={category} value={category}>
                {category}
                {categoryStats[category] ? ` (${categoryStats[category]})` : ''}
              </option>
            ))}
          </optgroup>
          
          {allowCustom && (
            <optgroup label="Custom">
              <option value="custom">+ Add Custom Category</option>
            </optgroup>
          )}
        </PixelSelect>

        {/* Custom Category Input */}
        {showCustomInput && (
          <div className="space-y-2">
            <PixelInput
              value={customCategory}
              onChange={(e) => setCustomCategory(e.target.value)}
              onKeyDown={handleCustomCategoryKeyDown}
              placeholder="Enter custom category name"
              autoFocus
            />
            <div className="flex gap-2">
              <PixelButton
                size="sm"
                onClick={handleCustomCategorySubmit}
                disabled={!customCategory.trim()}
              >
                ✓ Add Category
              </PixelButton>
              <PixelButton
                size="sm"
                variant="secondary"
                onClick={() => {
                  setShowCustomInput(false);
                  setCustomCategory('');
                }}
              >
                Cancel
              </PixelButton>
            </div>
          </div>
        )}
      </div>

      {/* Category Quick Selection Grid */}
      {!showCustomInput && (
        <div>
          <p className="text-xs text-gray-400 font-mono mb-2">Quick select:</p>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
            {predefinedCategories.slice(0, 8).map(category => (
              <button
                key={category}
                type="button"
                onClick={() => onCategoryChange(category)}
                className={cn(
                  "px-2 py-1 text-xs font-mono border-2 transition-colors text-left",
                  selectedCategory === category
                    ? "bg-blue-600 border-blue-800 text-white"
                    : "bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600 hover:border-gray-500"
                )}
              >
                {category}
                {categoryStats[category] && (
                  <span className="text-gray-400 ml-1">({categoryStats[category]})</span>
                )}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Selected Category Display */}
      {selectedCategory && !showCustomInput && (
        <div className="flex items-center justify-between p-2 bg-blue-900/20 border border-blue-600 rounded">
          <div className="flex items-center gap-2">
            <span className="text-sm font-mono text-blue-300">Selected:</span>
            <span className="text-sm font-mono text-white font-bold">{selectedCategory}</span>
            {categoryStats[selectedCategory] && (
              <span className="text-xs text-gray-400 font-mono">
                ({categoryStats[selectedCategory]} project{categoryStats[selectedCategory] !== 1 ? 's' : ''})
              </span>
            )}
          </div>
          <PixelButton
            size="sm"
            variant="secondary"
            onClick={() => onCategoryChange('')}
          >
            Clear
          </PixelButton>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <p className="text-sm text-red-400 flex items-center gap-1 font-mono">
          <span>⚠</span>
          {error}
        </p>
      )}

      {/* Helper Text */}
      {helperText && !error && (
        <p className="text-sm text-gray-500 font-mono">
          {helperText}
        </p>
      )}

      {/* Category Statistics */}
      {Object.keys(categoryStats).length > 0 && (
        <PixelCard className="p-3">
          <h4 className="text-sm font-mono font-bold text-white mb-2">Category Usage</h4>
          <div className="space-y-1">
            {Object.entries(categoryStats)
              .sort(([,a], [,b]) => b - a)
              .slice(0, 5)
              .map(([category, count]) => (
                <div key={category} className="flex items-center justify-between text-xs font-mono">
                  <span className="text-gray-300">{category}</span>
                  <div className="flex items-center gap-2">
                    <div className="w-16 bg-gray-700 rounded-full h-1">
                      <div 
                        className="bg-blue-500 h-1 rounded-full"
                        style={{ 
                          width: `${(count / Math.max(...Object.values(categoryStats))) * 100}%` 
                        }}
                      />
                    </div>
                    <span className="text-gray-400 w-8 text-right">{count}</span>
                  </div>
                </div>
              ))}
          </div>
        </PixelCard>
      )}
    </div>
  );
};
import React from 'react';
import { Service } from '@/types/admin';
import { PixelCard } from './PixelCard';

interface ServicePreviewProps {
  service: Partial<Service>;
  title?: string;
  showVariations?: boolean;
}

export const ServicePreview: React.FC<ServicePreviewProps> = ({
  service,
  title = 'Preview',
  showVariations = false
}) => {
  const defaultService = {
    icon: '⚙️',
    color: '#3b82f6',
    bgColor: '#1e40af',
    title: { vi: 'Tên dịch vụ', en: 'Service Name' },
    description: { vi: 'Mô tả dịch vụ...', en: 'Service description...' }
  };

  const previewService = { ...defaultService, ...service };

  const colorVariations = showVariations ? [
    { name: 'Light Background', bgOpacity: '10' },
    { name: 'Medium Background', bgOpacity: '20' },
    { name: 'Dark Background', bgOpacity: '30' },
  ] : [{ name: 'Current', bgOpacity: '20' }];

  return (
    <div className="space-y-4">
      <h4 className="font-mono text-sm text-gray-400">{title}:</h4>
      
      <div className="space-y-3">
        {colorVariations.map((variation) => (
          <div key={variation.name} className="space-y-2">
            {showVariations && (
              <div className="text-xs font-mono text-gray-500">
                {variation.name}
              </div>
            )}
            
            <div 
              className="p-4 border-2 border-gray-600 bg-gray-700 flex items-center gap-4 transition-all duration-200 hover:border-gray-500"
              style={{ 
                backgroundColor: previewService.bgColor + variation.bgOpacity,
                borderColor: previewService.bgColor + '40'
              }}
            >
              <div 
                className="w-12 h-12 flex items-center justify-center border-2 border-gray-500 flex-shrink-0 transition-transform duration-200 hover:scale-110"
                style={{ 
                  color: previewService.color,
                  borderColor: previewService.color + '60',
                  backgroundColor: previewService.color + '10'
                }}
              >
                <span className="text-2xl">{previewService.icon}</span>
              </div>
              
              <div className="min-w-0 flex-1">
                <h5 className="font-mono font-bold text-white mb-1">
                  {previewService.title.en || 'Service Title'}
                </h5>
                <p className="text-sm text-gray-300 line-clamp-2">
                  {previewService.description.en || 'Service description...'}
                </p>
                
                {showVariations && (
                  <div className="mt-2 text-xs text-gray-400 font-mono">
                    Vietnamese: {previewService.title.vi || 'Tên dịch vụ'}
                  </div>
                )}
              </div>
              
              {/* Hover effect indicator */}
              <div className="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <span className="text-gray-400 text-sm">→</span>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Color Information */}
      {showVariations && (
        <PixelCard variant="default" className="text-xs">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <div className="text-gray-400 font-mono mb-1">Icon Color:</div>
              <div className="flex items-center gap-2">
                <div 
                  className="w-4 h-4 border border-gray-500"
                  style={{ backgroundColor: previewService.color }}
                />
                <span className="font-mono text-white">
                  {previewService.color.toUpperCase()}
                </span>
              </div>
            </div>
            
            <div>
              <div className="text-gray-400 font-mono mb-1">Background Color:</div>
              <div className="flex items-center gap-2">
                <div 
                  className="w-4 h-4 border border-gray-500"
                  style={{ backgroundColor: previewService.bgColor }}
                />
                <span className="font-mono text-white">
                  {previewService.bgColor.toUpperCase()}
                </span>
              </div>
            </div>
          </div>
        </PixelCard>
      )}
    </div>
  );
};
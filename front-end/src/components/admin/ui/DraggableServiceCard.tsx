import React from 'react';
import { Service } from '@/types/admin';
import { PixelCard } from './PixelCard';
import { PixelButton } from './PixelButton';
import { cn } from '@/lib/utils';

interface DraggableServiceCardProps {
  service: Service;
  onEdit: () => void;
  onDelete: () => void;
  deleteConfirm: boolean;
  onConfirmDelete: () => void;
  onCancelDelete: () => void;
  isDragging: boolean;
  dragHandleProps: React.HTMLAttributes<HTMLDivElement>;
  showOrder?: boolean;
}

export const DraggableServiceCard: React.FC<DraggableServiceCardProps> = ({
  service,
  onEdit,
  onDelete,
  deleteConfirm,
  onConfirmDelete,
  onCancelDelete,
  isDragging,
  dragHandleProps,
  showOrder = true
}) => {
  return (
    <PixelCard 
      className={cn(
        'h-full transition-all duration-200',
        isDragging && 'shadow-lg border-blue-500'
      )}
    >
      <div className="flex flex-col h-full">
        {/* Drag Handle and Order */}
        <div className="flex items-center justify-between mb-3 pb-2 border-b-2 border-gray-600">
          <div 
            {...dragHandleProps}
            className={cn(
              'flex items-center gap-2 px-3 py-2 border-2 border-gray-600 bg-gray-700 hover:bg-gray-600 active:bg-gray-500 transition-all duration-200 select-none',
              isDragging ? 'cursor-grabbing bg-blue-600 border-blue-400 shadow-lg' : 'cursor-grab hover:border-gray-500',
              'touch-manipulation' // Better touch handling
            )}
            title="Drag to reorder • Touch and hold on mobile"
          >
            <span className={cn(
              'text-lg transition-colors duration-200',
              isDragging ? 'text-blue-300' : 'text-gray-400'
            )}>⋮⋮</span>
            {showOrder && (
              <span className="text-xs font-mono text-gray-400">
                #{service.order + 1}
              </span>
            )}
          </div>
          
          <div className="text-xs font-mono text-gray-500">
            ID: {service.id.slice(-4)}
          </div>
        </div>

        {/* Service Preview */}
        <div 
          className="p-4 border-2 border-gray-600 mb-4 flex items-center gap-3"
          style={{ backgroundColor: service.bgColor + '20' }}
        >
          <div 
            className="w-12 h-12 flex items-center justify-center border-2 border-gray-500 flex-shrink-0"
            style={{ color: service.color }}
          >
            <span className="text-2xl">{service.icon}</span>
          </div>
          <div className="min-w-0 flex-1">
            <h3 className="font-mono font-bold text-white truncate">
              {service.title.en}
            </h3>
            <p className="text-sm text-gray-300 line-clamp-2">
              {service.description.en}
            </p>
          </div>
        </div>

        {/* Service Details */}
        <div className="flex-1 space-y-3 text-sm">
          <div>
            <span className="text-gray-400 font-mono">Vietnamese:</span>
            <p className="text-white font-mono">{service.title.vi}</p>
            <p className="text-gray-300 text-xs line-clamp-2">{service.description.vi}</p>
          </div>
          
          <div className="flex items-center gap-4 text-xs">
            <div>
              <span className="text-gray-400">Order:</span>
              <span className="text-white ml-1 font-mono">{service.order}</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-gray-400">Colors:</span>
              <div 
                className="w-4 h-4 border border-gray-500"
                style={{ backgroundColor: service.color }}
                title={`Icon: ${service.color}`}
              />
              <div 
                className="w-4 h-4 border border-gray-500"
                style={{ backgroundColor: service.bgColor }}
                title={`Background: ${service.bgColor}`}
              />
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="mt-4 pt-4 border-t-2 border-gray-600">
          {deleteConfirm ? (
            <div className="space-y-2">
              <p className="text-sm text-red-400 font-mono">Delete this service?</p>
              <div className="flex gap-2">
                <PixelButton
                  size="sm"
                  variant="danger"
                  onClick={onConfirmDelete}
                  fullWidth
                >
                  Yes, Delete
                </PixelButton>
                <PixelButton
                  size="sm"
                  variant="secondary"
                  onClick={onCancelDelete}
                  fullWidth
                >
                  Cancel
                </PixelButton>
              </div>
            </div>
          ) : (
            <div className="flex gap-2">
              <PixelButton
                size="sm"
                variant="primary"
                onClick={onEdit}
                fullWidth
              >
                ✏️ Edit
              </PixelButton>
              <PixelButton
                size="sm"
                variant="danger"
                onClick={onDelete}
                fullWidth
              >
                🗑️ Delete
              </PixelButton>
            </div>
          )}
        </div>
      </div>
    </PixelCard>
  );
};
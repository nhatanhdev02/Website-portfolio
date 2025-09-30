import React, { useState } from 'react';
import { Service } from '@/types/admin';
import { DragDropList } from '@/components/admin/ui/DragDropList';
import { DraggableServiceCard } from '@/components/admin/ui/DraggableServiceCard';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelButton } from '@/components/admin/ui/PixelButton';

// Demo services for testing
const demoServices: Service[] = [
  {
    id: 'service-1',
    title: { vi: 'Phát triển Web', en: 'Web Development' },
    description: { vi: 'Tạo website hiện đại và responsive', en: 'Create modern and responsive websites' },
    icon: '💻',
    color: '#3b82f6',
    bgColor: '#1e40af',
    order: 0
  },
  {
    id: 'service-2',
    title: { vi: 'Ứng dụng Mobile', en: 'Mobile Apps' },
    description: { vi: 'Phát triển ứng dụng di động đa nền tảng', en: 'Develop cross-platform mobile applications' },
    icon: '📱',
    color: '#10b981',
    bgColor: '#047857',
    order: 1
  },
  {
    id: 'service-3',
    title: { vi: 'API Backend', en: 'Backend API' },
    description: { vi: 'Xây dựng API mạnh mẽ và bảo mật', en: 'Build robust and secure APIs' },
    icon: '⚙️',
    color: '#f59e0b',
    bgColor: '#d97706',
    order: 2
  },
  {
    id: 'service-4',
    title: { vi: 'Tư vấn Tech', en: 'Tech Consulting' },
    description: { vi: 'Tư vấn giải pháp công nghệ phù hợp', en: 'Provide suitable technology solutions' },
    icon: '🎯',
    color: '#ef4444',
    bgColor: '#dc2626',
    order: 3
  }
];

export const DragDropDemo: React.FC = () => {
  const [services, setServices] = useState<Service[]>(demoServices);
  const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
  const [reorderHistory, setReorderHistory] = useState<Service[][]>([demoServices]);

  const handleReorder = (reorderedServices: Service[]) => {
    setServices(reorderedServices);
    setReorderHistory(prev => [...prev, reorderedServices]);
    console.log('Services reordered:', reorderedServices.map(s => ({ id: s.id, order: s.order, title: s.title.en })));
  };

  const handleEdit = (serviceId: string) => {
    console.log('Edit service:', serviceId);
    alert(`Edit service: ${services.find(s => s.id === serviceId)?.title.en}`);
  };

  const handleDelete = (serviceId: string) => {
    setDeleteConfirm(serviceId);
  };

  const confirmDelete = (serviceId: string) => {
    const updatedServices = services.filter(s => s.id !== serviceId);
    setServices(updatedServices);
    setDeleteConfirm(null);
    console.log('Service deleted:', serviceId);
  };

  const resetDemo = () => {
    setServices(demoServices);
    setDeleteConfirm(null);
    setReorderHistory([demoServices]);
  };

  const undoLastReorder = () => {
    if (reorderHistory.length > 1) {
      const newHistory = reorderHistory.slice(0, -1);
      setReorderHistory(newHistory);
      setServices(newHistory[newHistory.length - 1]);
    }
  };

  return (
    <div className="p-6 space-y-6 max-w-4xl mx-auto">
      <PixelCard variant="primary">
        <div className="text-center">
          <h1 className="text-2xl font-bold font-mono text-white mb-2">
            🎮 Drag & Drop Demo
          </h1>
          <p className="text-gray-300 font-mono text-sm mb-4">
            Test the drag-and-drop service reordering functionality
          </p>
          <div className="flex gap-2 justify-center">
            <PixelButton onClick={resetDemo} variant="secondary" size="sm">
              🔄 Reset Demo
            </PixelButton>
            <PixelButton 
              onClick={undoLastReorder} 
              variant="secondary" 
              size="sm"
              disabled={reorderHistory.length <= 1}
            >
              ↶ Undo Last Reorder
            </PixelButton>
          </div>
        </div>
      </PixelCard>

      <PixelCard>
        <div className="mb-4">
          <h2 className="font-mono text-lg text-white mb-2">
            📋 Service List ({services.length} items)
          </h2>
          <div className="text-sm text-gray-400 font-mono space-y-1">
            <p>• 🖱️ Desktop: Click and drag to reorder</p>
            <p>• 📱 Mobile: Touch and hold, then drag</p>
            <p>• ⋮⋮ Use the drag handle for better control</p>
          </div>
        </div>

        <DragDropList
          items={services}
          onReorder={handleReorder}
          renderItem={(service, isDragging, dragHandleProps) => (
            <DraggableServiceCard
              service={service}
              onEdit={() => handleEdit(service.id)}
              onDelete={() => handleDelete(service.id)}
              deleteConfirm={deleteConfirm === service.id}
              onConfirmDelete={() => confirmDelete(service.id)}
              onCancelDelete={() => setDeleteConfirm(null)}
              isDragging={isDragging}
              dragHandleProps={dragHandleProps}
              showOrder={true}
            />
          )}
        />
      </PixelCard>

      {/* Debug Info */}
      <PixelCard variant="secondary">
        <h3 className="font-mono text-white mb-2">🔍 Debug Info</h3>
        <div className="text-xs font-mono text-gray-300 space-y-1">
          <p>Current Order: {services.map(s => `${s.title.en}(${s.order})`).join(' → ')}</p>
          <p>Reorder History: {reorderHistory.length} entries</p>
          <p>Delete Confirm: {deleteConfirm || 'None'}</p>
        </div>
      </PixelCard>

      {/* Feature Checklist */}
      <PixelCard>
        <h3 className="font-mono text-white mb-3">✅ Feature Checklist</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm font-mono">
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Drag-and-drop functionality</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Visual feedback during drag</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Touch support for mobile</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Order persistence</span>
            </div>
          </div>
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Drag handle for control</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Responsive design</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Pixel art styling</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-green-400">✓</span>
              <span className="text-gray-300">Error handling</span>
            </div>
          </div>
        </div>
      </PixelCard>
    </div>
  );
};
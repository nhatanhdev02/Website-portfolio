import React, { useState } from 'react';
import { Service } from '@/types/admin';
import { useAdmin } from '@/contexts/AdminContext';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelInput } from '@/components/admin/ui/PixelInput';
import { ServiceForm } from '@/components/admin/forms/ServiceForm';
import { DragDropList } from '@/components/admin/ui/DragDropList';
import { DraggableServiceCard } from '@/components/admin/ui/DraggableServiceCard';

type ServicesManagerProps = object

export const ServicesManager: React.FC<ServicesManagerProps> = () => {
  const { services, addService, updateService, deleteService, reorderServices } = useAdmin();
  const [showForm, setShowForm] = useState(false);
  const [editingService, setEditingService] = useState<Service | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

  // Filter services based on search term
  const filteredServices = services.filter(service =>
    service.title.en.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.title.vi.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.description.en.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.description.vi.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // Sort services by order
  const sortedServices = [...filteredServices].sort((a, b) => a.order - b.order);

  const handleAddService = () => {
    setEditingService(null);
    setShowForm(true);
  };

  const handleEditService = (service: Service) => {
    setEditingService(service);
    setShowForm(true);
  };

  const handleDeleteService = (serviceId: string) => {
    setDeleteConfirm(serviceId);
  };

  const confirmDelete = (serviceId: string) => {
    deleteService(serviceId);
    setDeleteConfirm(null);
  };

  const handleFormSubmit = (serviceData: Omit<Service, 'id'>) => {
    if (editingService) {
      updateService(editingService.id, serviceData);
    } else {
      addService(serviceData);
    }
    setShowForm(false);
    setEditingService(null);
  };

  const handleFormCancel = () => {
    setShowForm(false);
    setEditingService(null);
  };

  const handleReorder = (reorderedServices: Service[]) => {
    // Validate the reordered services
    if (!reorderedServices || reorderedServices.length === 0) {
      console.warn('Invalid reorder operation: empty or null services array');
      return;
    }

    // Ensure all services have valid order values
    const validatedServices = reorderedServices.map((service, index) => ({
      ...service,
      order: index
    }));

    // Log the reorder operation for debugging
    console.log('Services reordered:', {
      from: services.map(s => ({ id: s.id, order: s.order, title: s.title.en })),
      to: validatedServices.map(s => ({ id: s.id, order: s.order, title: s.title.en }))
    });

    reorderServices(validatedServices);
  };

  if (showForm) {
    return (
      <div className="p-6">
        <ServiceForm
          service={editingService || undefined}
          onSubmit={handleFormSubmit}
          onCancel={handleFormCancel}
          isEditing={!!editingService}
        />
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold font-mono text-white">Services Management</h1>
          <p className="text-gray-400 font-mono text-sm">
            Manage your service offerings ({services.length} total)
          </p>
        </div>
        <PixelButton onClick={handleAddService} variant="primary">
          ➕ Add Service
        </PixelButton>
      </div>

      {/* Search and Filters */}
      <PixelCard>
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <PixelInput
              variant="search"
              placeholder="Search services..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="flex gap-2">
            <PixelButton
              variant="secondary"
              size="sm"
              onClick={() => setSearchTerm('')}
              disabled={!searchTerm}
            >
              Clear
            </PixelButton>
            <PixelButton
              variant={viewMode === 'grid' ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setViewMode('grid')}
            >
              📱 Grid
            </PixelButton>
            <PixelButton
              variant={viewMode === 'list' ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setViewMode('list')}
            >
              📋 List
            </PixelButton>
          </div>
        </div>
      </PixelCard>

      {/* Services Display */}
      {sortedServices.length === 0 ? (
        <PixelCard variant="default" className="text-center py-12">
          <div className="text-gray-400">
            <div className="text-4xl mb-4">📋</div>
            <h3 className="text-lg font-mono font-bold mb-2">
              {searchTerm ? 'No services found' : 'No services yet'}
            </h3>
            <p className="text-sm mb-4">
              {searchTerm 
                ? 'Try adjusting your search terms'
                : 'Start by adding your first service'
              }
            </p>
            {!searchTerm && (
              <PixelButton onClick={handleAddService} variant="primary">
                Add First Service
              </PixelButton>
            )}
          </div>
        </PixelCard>
      ) : viewMode === 'list' ? (
        <PixelCard>
          <div className="mb-4">
            <h3 className="font-mono text-lg text-white mb-2">Drag to Reorder Services</h3>
            <p className="text-sm text-gray-400 font-mono">
              Services are displayed in order on your website. Drag and drop to change the order.
            </p>
          </div>
          <DragDropList
            items={sortedServices}
            onReorder={handleReorder}
            renderItem={(service, isDragging, dragHandleProps) => (
              <DraggableServiceCard
                service={service}
                onEdit={() => handleEditService(service)}
                onDelete={() => handleDeleteService(service.id)}
                deleteConfirm={deleteConfirm === service.id}
                onConfirmDelete={() => confirmDelete(service.id)}
                onCancelDelete={() => setDeleteConfirm(null)}
                isDragging={isDragging}
                dragHandleProps={dragHandleProps}
              />
            )}
          />
        </PixelCard>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {sortedServices.map((service) => (
            <ServiceCard
              key={service.id}
              service={service}
              onEdit={() => handleEditService(service)}
              onDelete={() => handleDeleteService(service.id)}
              deleteConfirm={deleteConfirm === service.id}
              onConfirmDelete={() => confirmDelete(service.id)}
              onCancelDelete={() => setDeleteConfirm(null)}
            />
          ))}
        </div>
      )}

      {/* Stats */}
      {services.length > 0 && (
        <PixelCard variant="primary">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
              <div className="text-2xl font-bold font-mono text-white">
                {services.length}
              </div>
              <div className="text-sm text-gray-300">Total Services</div>
            </div>
            <div>
              <div className="text-2xl font-bold font-mono text-white">
                {filteredServices.length}
              </div>
              <div className="text-sm text-gray-300">Filtered</div>
            </div>
            <div>
              <div className="text-2xl font-bold font-mono text-white">
                {Math.max(...services.map(s => s.order), 0)}
              </div>
              <div className="text-sm text-gray-300">Max Order</div>
            </div>
            <div>
              <div className="text-2xl font-bold font-mono text-white">
                {new Set(services.map(s => s.icon)).size}
              </div>
              <div className="text-sm text-gray-300">Unique Icons</div>
            </div>
          </div>
        </PixelCard>
      )}
    </div>
  );
};

interface ServiceCardProps {
  service: Service;
  onEdit: () => void;
  onDelete: () => void;
  deleteConfirm: boolean;
  onConfirmDelete: () => void;
  onCancelDelete: () => void;
}

const ServiceCard: React.FC<ServiceCardProps> = ({
  service,
  onEdit,
  onDelete,
  deleteConfirm,
  onConfirmDelete,
  onCancelDelete
}) => {
  return (
    <PixelCard className="h-full">
      <div className="flex flex-col h-full">
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
import React, { useState, useRef } from 'react';
import { cn } from '@/lib/utils';

interface DragDropItem {
  id: string;
  order: number;
}

interface DragDropListProps<T extends DragDropItem> {
  items: T[];
  onReorder: (reorderedItems: T[]) => void;
  renderItem: (item: T, isDragging: boolean, dragHandleProps: React.HTMLAttributes<HTMLDivElement>) => React.ReactNode;
  className?: string;
  disabled?: boolean;
}

interface DragState {
  isDragging: boolean;
  draggedItemId: string | null;
  draggedOverItemId: string | null;
  startY: number;
  currentY: number;
}

export function DragDropList<T extends DragDropItem>({
  items,
  onReorder,
  renderItem,
  className,
  disabled = false
}: DragDropListProps<T>) {
  const [dragState, setDragState] = useState<DragState>({
    isDragging: false,
    draggedItemId: null,
    draggedOverItemId: null,
    startY: 0,
    currentY: 0
  });

  const listRef = useRef<HTMLDivElement>(null);
  const draggedElementRef = useRef<HTMLElement | null>(null);

  // Sort items by order
  const sortedItems = [...items].sort((a, b) => a.order - b.order);

  const handleDragStart = (e: React.DragEvent, itemId: string) => {
    if (disabled) return;

    const target = e.target as HTMLElement;
    draggedElementRef.current = target.closest('[data-drag-item]') as HTMLElement;
    
    setDragState(prev => ({
      ...prev,
      isDragging: true,
      draggedItemId: itemId,
      startY: e.clientY
    }));

    // Enhanced drag image with better visual feedback
    if (draggedElementRef.current) {
      const dragImage = draggedElementRef.current.cloneNode(true) as HTMLElement;
      dragImage.style.opacity = '0.7';
      dragImage.style.transform = 'rotate(3deg) scale(1.05)';
      dragImage.style.border = '2px solid #3b82f6';
      dragImage.style.borderRadius = '4px';
      dragImage.style.boxShadow = '0 8px 16px rgba(0,0,0,0.3)';
      dragImage.style.position = 'absolute';
      dragImage.style.top = '-1000px';
      dragImage.style.zIndex = '9999';
      document.body.appendChild(dragImage);
      e.dataTransfer.setDragImage(dragImage, e.nativeEvent.offsetX, e.nativeEvent.offsetY);
      setTimeout(() => {
        if (document.body.contains(dragImage)) {
          document.body.removeChild(dragImage);
        }
      }, 0);
    }

    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', itemId);
  };

  const handleDragOver = (e: React.DragEvent, itemId: string) => {
    if (disabled || !dragState.isDragging) return;
    
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    setDragState(prev => ({
      ...prev,
      draggedOverItemId: itemId,
      currentY: e.clientY
    }));
  };

  const handleDragLeave = (e: React.DragEvent) => {
    if (disabled) return;
    
    // Only clear if we're leaving the entire list area
    const rect = listRef.current?.getBoundingClientRect();
    if (rect && (e.clientY < rect.top || e.clientY > rect.bottom)) {
      setDragState(prev => ({
        ...prev,
        draggedOverItemId: null
      }));
    }
  };

  const handleDrop = (e: React.DragEvent, targetItemId: string) => {
    if (disabled || !dragState.draggedItemId) return;
    
    e.preventDefault();
    
    const draggedItemId = dragState.draggedItemId;
    if (draggedItemId === targetItemId) {
      resetDragState();
      return;
    }

    // Find the items
    const draggedItem = sortedItems.find(item => item.id === draggedItemId);
    const targetItem = sortedItems.find(item => item.id === targetItemId);
    
    if (!draggedItem || !targetItem) {
      resetDragState();
      return;
    }

    // Create new order
    const reorderedItems = [...sortedItems];
    const draggedIndex = reorderedItems.findIndex(item => item.id === draggedItemId);
    const targetIndex = reorderedItems.findIndex(item => item.id === targetItemId);

    // Remove dragged item and insert at target position
    const [removed] = reorderedItems.splice(draggedIndex, 1);
    reorderedItems.splice(targetIndex, 0, removed);

    // Update order values
    const updatedItems = reorderedItems.map((item, index) => ({
      ...item,
      order: index
    }));

    onReorder(updatedItems);
    resetDragState();
  };

  const handleDragEnd = () => {
    resetDragState();
  };

  const resetDragState = () => {
    setDragState({
      isDragging: false,
      draggedItemId: null,
      draggedOverItemId: null,
      startY: 0,
      currentY: 0
    });
  };

  // Enhanced touch support for mobile with better feedback
  const handleTouchStart = (e: React.TouchEvent, itemId: string) => {
    if (disabled) return;
    
    const touch = e.touches[0];
    const target = e.target as HTMLElement;
    draggedElementRef.current = target.closest('[data-drag-item]') as HTMLElement;
    
    setDragState(prev => ({
      ...prev,
      isDragging: true,
      draggedItemId: itemId,
      startY: touch.clientY,
      currentY: touch.clientY
    }));

    // Add haptic feedback if available
    if ('vibrate' in navigator) {
      navigator.vibrate(50);
    }

    // Add visual feedback for touch start
    if (draggedElementRef.current) {
      draggedElementRef.current.style.transition = 'transform 0.2s ease';
      draggedElementRef.current.style.transform = 'scale(1.02)';
    }
  };

  const handleTouchMove = (e: React.TouchEvent) => {
    if (disabled || !dragState.isDragging) return;
    
    e.preventDefault();
    const touch = e.touches[0];
    
    setDragState(prev => ({
      ...prev,
      currentY: touch.clientY
    }));

    // Enhanced element detection for better touch handling
    const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
    const dragItem = elementBelow?.closest('[data-drag-item]');
    const itemId = dragItem?.getAttribute('data-item-id');
    
    if (itemId && itemId !== dragState.draggedItemId) {
      setDragState(prev => ({
        ...prev,
        draggedOverItemId: itemId
      }));

      // Provide haptic feedback when hovering over valid drop target
      if ('vibrate' in navigator) {
        navigator.vibrate(10);
      }
    }

    // Update visual position for dragged element
    if (draggedElementRef.current) {
      const deltaY = touch.clientY - dragState.startY;
      draggedElementRef.current.style.transform = `translateY(${deltaY}px) scale(1.02) rotate(2deg)`;
      draggedElementRef.current.style.zIndex = '1000';
      draggedElementRef.current.style.opacity = '0.8';
    }
  };

  const handleTouchEnd = (e: React.TouchEvent) => {
    if (disabled || !dragState.isDragging) return;
    
    const touch = e.changedTouches[0];
    const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
    const dragItem = elementBelow?.closest('[data-drag-item]');
    const targetItemId = dragItem?.getAttribute('data-item-id');
    
    // Reset visual styles
    if (draggedElementRef.current) {
      draggedElementRef.current.style.transform = '';
      draggedElementRef.current.style.zIndex = '';
      draggedElementRef.current.style.opacity = '';
      draggedElementRef.current.style.transition = '';
    }
    
    if (targetItemId && targetItemId !== dragState.draggedItemId) {
      // Provide success haptic feedback
      if ('vibrate' in navigator) {
        navigator.vibrate([50, 50, 50]);
      }
      
      // Simulate drop
      const syntheticEvent = {
        preventDefault: () => {},
        dataTransfer: { dropEffect: 'move' }
      } as React.DragEvent;
      
      handleDrop(syntheticEvent, targetItemId);
    } else {
      resetDragState();
    }
  };

  const getDragHandleProps = (itemId: string) => ({
    draggable: !disabled,
    onDragStart: (e: React.DragEvent) => handleDragStart(e, itemId),
    onDragEnd: handleDragEnd,
    onTouchStart: (e: React.TouchEvent) => handleTouchStart(e, itemId),
    onTouchMove: handleTouchMove,
    onTouchEnd: handleTouchEnd,
    style: {
      cursor: disabled ? 'default' : 'grab',
      touchAction: 'none'
    }
  });

  return (
    <div 
      ref={listRef}
      className={cn('space-y-2', className)}
      onDragLeave={handleDragLeave}
    >
      {sortedItems.map((item) => {
        const isDragging = dragState.draggedItemId === item.id;
        const isDraggedOver = dragState.draggedOverItemId === item.id;
        
        return (
          <div
            key={item.id}
            data-drag-item
            data-item-id={item.id}
            className={cn(
              'transition-all duration-200 relative',
              isDragging && 'opacity-60 scale-95 rotate-2 z-50 shadow-lg border-2 border-blue-500',
              isDraggedOver && dragState.draggedItemId !== item.id && 'transform translate-y-2 scale-105 bg-blue-900/20 border-2 border-blue-400',
              disabled && 'cursor-not-allowed opacity-50',
              !disabled && !isDragging && 'hover:scale-[1.01] hover:shadow-md'
            )}
            onDragOver={(e) => handleDragOver(e, item.id)}
            onDrop={(e) => handleDrop(e, item.id)}
          >
            {renderItem(item, isDragging, getDragHandleProps(item.id))}
          </div>
        );
      })}
      
      {/* Visual feedback for empty state */}
      {sortedItems.length === 0 && (
        <div className="text-center py-8 text-gray-500 font-mono">
          <div className="text-2xl mb-2">📋</div>
          <p>No items to reorder</p>
        </div>
      )}
      
      {/* Drag instructions */}
      {!disabled && sortedItems.length > 1 && (
        <div className="text-xs text-gray-500 font-mono text-center mt-4 p-2 border-t border-gray-600">
          💡 Drag and drop to reorder • Touch and hold on mobile
        </div>
      )}
    </div>
  );
}
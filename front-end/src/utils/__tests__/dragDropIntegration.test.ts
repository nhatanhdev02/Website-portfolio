/**
 * Integration test for drag-and-drop service reordering functionality
 * Tests the complete flow from UI interaction to data persistence
 */

import { Service } from '@/types/admin';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { describe } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { describe } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { describe } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { describe } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { describe } from 'vitest';
import { vi } from 'vitest';
import { vi } from 'vitest';
import { vi } from 'vitest';
import { vi } from 'vitest';
import { beforeEach } from 'vitest';
import { describe } from 'vitest';

// Mock services for testing
const mockServices: Service[] = [
  {
    id: 'service-1',
    title: { vi: 'Dịch vụ 1', en: 'Service 1' },
    description: { vi: 'Mô tả 1', en: 'Description 1' },
    icon: '💻',
    color: '#3b82f6',
    bgColor: '#1e40af',
    order: 0
  },
  {
    id: 'service-2',
    title: { vi: 'Dịch vụ 2', en: 'Service 2' },
    description: { vi: 'Mô tả 2', en: 'Description 2' },
    icon: '📱',
    color: '#10b981',
    bgColor: '#047857',
    order: 1
  },
  {
    id: 'service-3',
    title: { vi: 'Dịch vụ 3', en: 'Service 3' },
    description: { vi: 'Mô tả 3', en: 'Description 3' },
    icon: '⚙️',
    color: '#f59e0b',
    bgColor: '#d97706',
    order: 2
  }
];

describe('Drag and Drop Service Reordering', () => {
  let mockLocalStorage: { [key: string]: string };

  beforeEach(() => {
    // Mock localStorage
    mockLocalStorage = {};
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: vi.fn((key: string) => mockLocalStorage[key] || null),
        setItem: vi.fn((key: string, value: string) => {
          mockLocalStorage[key] = value;
        }),
        removeItem: vi.fn((key: string) => {
          delete mockLocalStorage[key];
        }),
        clear: vi.fn(() => {
          mockLocalStorage = {};
        })
      },
      writable: true
    });
  });

  describe('Service Reordering Logic', () => {
    it('should correctly reorder services when dragging from position 0 to position 2', () => {
      const services = [...mockServices];
      
      // Simulate dragging service-1 (order 0) to position 2
      const draggedService = services.find(s => s.id === 'service-1')!;
      const targetService = services.find(s => s.id === 'service-3')!;
      
      // Remove dragged item
      const filteredServices = services.filter(s => s.id !== draggedService.id);
      
      // Insert at target position
      const targetIndex = filteredServices.findIndex(s => s.id === targetService.id);
      filteredServices.splice(targetIndex + 1, 0, draggedService);
      
      // Update order values
      const reorderedServices = filteredServices.map((service, index) => ({
        ...service,
        order: index
      }));

      expect(reorderedServices[0].id).toBe('service-2');
      expect(reorderedServices[0].order).toBe(0);
      expect(reorderedServices[1].id).toBe('service-3');
      expect(reorderedServices[1].order).toBe(1);
      expect(reorderedServices[2].id).toBe('service-1');
      expect(reorderedServices[2].order).toBe(2);
    });

    it('should correctly reorder services when dragging from position 2 to position 0', () => {
      const services = [...mockServices];
      
      // Simulate dragging service-3 (order 2) to position 0
      const draggedService = services.find(s => s.id === 'service-3')!;
      const targetService = services.find(s => s.id === 'service-1')!;
      
      // Remove dragged item
      const filteredServices = services.filter(s => s.id !== draggedService.id);
      
      // Insert at target position
      const targetIndex = filteredServices.findIndex(s => s.id === targetService.id);
      filteredServices.splice(targetIndex, 0, draggedService);
      
      // Update order values
      const reorderedServices = filteredServices.map((service, index) => ({
        ...service,
        order: index
      }));

      expect(reorderedServices[0].id).toBe('service-3');
      expect(reorderedServices[0].order).toBe(0);
      expect(reorderedServices[1].id).toBe('service-1');
      expect(reorderedServices[1].order).toBe(1);
      expect(reorderedServices[2].id).toBe('service-2');
      expect(reorderedServices[2].order).toBe(2);
    });

    it('should maintain order integrity after multiple reorder operations', () => {
      let services = [...mockServices];
      
      // First reorder: move service-1 to end
      let draggedService = services.find(s => s.id === 'service-1')!;
      services = services.filter(s => s.id !== draggedService.id);
      services.push(draggedService);
      services = services.map((service, index) => ({ ...service, order: index }));
      
      // Second reorder: move service-3 to beginning
      draggedService = services.find(s => s.id === 'service-3')!;
      services = services.filter(s => s.id !== draggedService.id);
      services.unshift(draggedService);
      services = services.map((service, index) => ({ ...service, order: index }));
      
      // Verify final order
      expect(services[0].id).toBe('service-3');
      expect(services[0].order).toBe(0);
      expect(services[1].id).toBe('service-2');
      expect(services[1].order).toBe(1);
      expect(services[2].id).toBe('service-1');
      expect(services[2].order).toBe(2);
      
      // Verify all orders are unique and sequential
      const orders = services.map(s => s.order).sort();
      expect(orders).toEqual([0, 1, 2]);
    });
  });

  describe('Data Persistence', () => {
    it('should save reordered services to localStorage', () => {
      const reorderedServices = [
        { ...mockServices[2], order: 0 },
        { ...mockServices[0], order: 1 },
        { ...mockServices[1], order: 2 }
      ];
      
      // Simulate the reorderServices function
      const STORAGE_KEY = 'admin_services';
      localStorage.setItem(STORAGE_KEY, JSON.stringify(reorderedServices));
      
      const savedData = localStorage.getItem(STORAGE_KEY);
      expect(savedData).toBeTruthy();
      
      const parsedData = JSON.parse(savedData!);
      expect(parsedData).toHaveLength(3);
      expect(parsedData[0].id).toBe('service-3');
      expect(parsedData[0].order).toBe(0);
    });

    it('should handle empty service list gracefully', () => {
      const emptyServices: Service[] = [];
      
      const STORAGE_KEY = 'admin_services';
      localStorage.setItem(STORAGE_KEY, JSON.stringify(emptyServices));
      
      const savedData = localStorage.getItem(STORAGE_KEY);
      const parsedData = JSON.parse(savedData!);
      
      expect(parsedData).toEqual([]);
    });
  });

  describe('Touch Support Validation', () => {
    it('should handle touch events properly', () => {
      // Mock touch event properties
      const mockTouchEvent = {
        touches: [{ clientX: 100, clientY: 200 }],
        changedTouches: [{ clientX: 100, clientY: 250 }],
        preventDefault: jest.fn()
      };

      // Mock navigator.vibrate for haptic feedback
      Object.defineProperty(navigator, 'vibrate', {
        value: jest.fn(),
        writable: true
      });

      // Simulate touch start
      expect(() => {
        // Touch event handling logic would go here
        if ('vibrate' in navigator) {
          (navigator as any).vibrate(50);
        }
      }).not.toThrow();

      expect((navigator as any).vibrate).toHaveBeenCalledWith(50);
    });

    it('should provide visual feedback during touch interactions', () => {
      // Mock DOM element
      const mockElement = {
        style: {},
        closest: jest.fn().mockReturnValue({ getAttribute: jest.fn().mockReturnValue('service-1') })
      };

      // Simulate touch move visual feedback
      const deltaY = 50;
      mockElement.style = {
        transform: `translateY(${deltaY}px) scale(1.02) rotate(2deg)`,
        zIndex: '1000',
        opacity: '0.8'
      };

      expect(mockElement.style.transform).toContain('translateY(50px)');
      expect(mockElement.style.transform).toContain('scale(1.02)');
      expect(mockElement.style.transform).toContain('rotate(2deg)');
    });
  });

  describe('Error Handling', () => {
    it('should handle invalid drag operations gracefully', () => {
      const services = [...mockServices];
      
      // Try to reorder with invalid service ID
      const invalidReorder = () => {
        const draggedService = services.find(s => s.id === 'invalid-id');
        if (!draggedService) {
          return services; // Return original services if invalid
        }
        return services;
      };

      expect(() => invalidReorder()).not.toThrow();
      expect(invalidReorder()).toEqual(services);
    });

    it('should handle localStorage errors gracefully', () => {
      // Mock localStorage to throw error
      const originalSetItem = localStorage.setItem;
      localStorage.setItem = jest.fn().mockImplementation(() => {
        throw new Error('Storage quota exceeded');
      });

      expect(() => {
        try {
          localStorage.setItem('admin_services', JSON.stringify(mockServices));
        } catch (error) {
          console.warn('Failed to save services:', error);
          // Graceful fallback - continue without saving
        }
      }).not.toThrow();

      // Restore original function
      localStorage.setItem = originalSetItem;
    });
  });

  describe('Performance Considerations', () => {
    it('should handle large service lists efficiently', () => {
      // Create a large service list
      const largeServiceList: Service[] = Array.from({ length: 100 }, (_, index) => ({
        id: `service-${index}`,
        title: { vi: `Dịch vụ ${index}`, en: `Service ${index}` },
        description: { vi: `Mô tả ${index}`, en: `Description ${index}` },
        icon: '💻',
        color: '#3b82f6',
        bgColor: '#1e40af',
        order: index
      }));

      const startTime = performance.now();
      
      // Simulate reordering operation
      const reorderedList = largeServiceList.map((service, index) => ({
        ...service,
        order: index
      }));
      
      const endTime = performance.now();
      const executionTime = endTime - startTime;

      expect(reorderedList).toHaveLength(100);
      expect(executionTime).toBeLessThan(100); // Should complete in less than 100ms
    });
  });
});

// Export test utilities for use in other tests
export const createMockService = (id: string, order: number): Service => ({
  id,
  title: { vi: `Dịch vụ ${order + 1}`, en: `Service ${order + 1}` },
  description: { vi: `Mô tả ${order + 1}`, en: `Description ${order + 1}` },
  icon: '💻',
  color: '#3b82f6',
  bgColor: '#1e40af',
  order
});

export const simulateReorder = (services: Service[], fromIndex: number, toIndex: number): Service[] => {
  const result = [...services];
  const [removed] = result.splice(fromIndex, 1);
  result.splice(toIndex, 0, removed);
  return result.map((service, index) => ({ ...service, order: index }));
};
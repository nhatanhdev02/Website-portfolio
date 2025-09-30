import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { DragDropList } from '../DragDropList';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { expect } from 'vitest';
import { it } from 'vitest';
import { beforeEach } from 'vitest';
import { vi } from 'vitest';
import { describe } from 'vitest';

interface TestItem {
  id: string;
  order: number;
  name: string;
}

const mockItems: TestItem[] = [
  { id: '1', order: 0, name: 'Item 1' },
  { id: '2', order: 1, name: 'Item 2' },
  { id: '3', order: 2, name: 'Item 3' }
];

const TestItemComponent: React.FC<{
  item: TestItem;
  isDragging: boolean;
  dragHandleProps: React.HTMLAttributes<HTMLDivElement>;
}> = ({ item, isDragging, dragHandleProps }) => (
  <div 
    data-testid={`item-${item.id}`}
    className={isDragging ? 'dragging' : ''}
  >
    <div {...dragHandleProps} data-testid={`handle-${item.id}`}>
      Drag Handle
    </div>
    <span>{item.name}</span>
  </div>
);

describe('DragDropList', () => {
  const mockOnReorder = vi.fn();

  beforeEach(() => {
    mockOnReorder.mockClear();
  });

  it('renders items in correct order', () => {
    render(
      <DragDropList
        items={mockItems}
        onReorder={mockOnReorder}
        renderItem={(item, isDragging, dragHandleProps) => (
          <TestItemComponent 
            item={item} 
            isDragging={isDragging} 
            dragHandleProps={dragHandleProps} 
          />
        )}
      />
    );

    expect(screen.getByTestId('item-1')).toBeInTheDocument();
    expect(screen.getByTestId('item-2')).toBeInTheDocument();
    expect(screen.getByTestId('item-3')).toBeInTheDocument();
  });

  it('handles drag start event', () => {
    render(
      <DragDropList
        items={mockItems}
        onReorder={mockOnReorder}
        renderItem={(item, isDragging, dragHandleProps) => (
          <TestItemComponent 
            item={item} 
            isDragging={isDragging} 
            dragHandleProps={dragHandleProps} 
          />
        )}
      />
    );

    const dragHandle = screen.getByTestId('handle-1');
    
    // Mock dataTransfer
    const mockDataTransfer = {
      effectAllowed: '',
      setData: jest.fn(),
      setDragImage: jest.fn()
    };

    const dragStartEvent = new Event('dragstart', { bubbles: true });
    Object.defineProperty(dragStartEvent, 'dataTransfer', {
      value: mockDataTransfer
    });

    fireEvent(dragHandle, dragStartEvent);
    
    expect(mockDataTransfer.setData).toHaveBeenCalledWith('text/plain', '1');
  });

  it('handles touch events for mobile', () => {
    render(
      <DragDropList
        items={mockItems}
        onReorder={mockOnReorder}
        renderItem={(item, isDragging, dragHandleProps) => (
          <TestItemComponent 
            item={item} 
            isDragging={isDragging} 
            dragHandleProps={dragHandleProps} 
          />
        )}
      />
    );

    const dragHandle = screen.getByTestId('handle-1');
    
    // Test touch start
    fireEvent.touchStart(dragHandle, {
      touches: [{ clientX: 100, clientY: 100 }]
    });

    // Test touch move
    fireEvent.touchMove(dragHandle, {
      touches: [{ clientX: 100, clientY: 150 }]
    });

    // Test touch end
    fireEvent.touchEnd(dragHandle, {
      changedTouches: [{ clientX: 100, clientY: 150 }]
    });

    // Should not crash and should handle touch events
    expect(dragHandle).toBeInTheDocument();
  });

  it('disables drag when disabled prop is true', () => {
    render(
      <DragDropList
        items={mockItems}
        onReorder={mockOnReorder}
        renderItem={(item, isDragging, dragHandleProps) => (
          <TestItemComponent 
            item={item} 
            isDragging={isDragging} 
            dragHandleProps={dragHandleProps} 
          />
        )}
        disabled={true}
      />
    );

    const dragHandle = screen.getByTestId('handle-1');
    expect(dragHandle).toHaveStyle('cursor: default');
  });

  it('shows empty state when no items', () => {
    render(
      <DragDropList
        items={[]}
        onReorder={mockOnReorder}
        renderItem={(item, isDragging, dragHandleProps) => (
          <TestItemComponent 
            item={item} 
            isDragging={isDragging} 
            dragHandleProps={dragHandleProps} 
          />
        )}
      />
    );

    expect(screen.getByText('No items to reorder')).toBeInTheDocument();
  });
});
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { ImageUpload } from '../ImageUpload';
import { createMockFile } from '@/test/admin-test-utils';

describe('ImageUpload', () => {
  const mockOnUpload = vi.fn();
  const mockOnError = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders upload area', () => {
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
      />
    );

    expect(screen.getByText(/drag.*drop.*image/i)).toBeInTheDocument();
    expect(screen.getByText(/click to browse/i)).toBeInTheDocument();
  });

  it('handles file selection via input', async () => {
    const mockFile = createMockFile('test.png', 1024, 'image/png');
    
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
      />
    );

    const fileInput = screen.getByLabelText(/upload image/i);
    
    Object.defineProperty(fileInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    fireEvent.change(fileInput);

    await waitFor(() => {
      expect(mockOnUpload).toHaveBeenCalled();
    });
  });

  it('validates file type', async () => {
    const invalidFile = createMockFile('test.txt', 1024, 'text/plain');
    
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
      />
    );

    const fileInput = screen.getByLabelText(/upload image/i);
    
    Object.defineProperty(fileInput, 'files', {
      value: [invalidFile],
      writable: false,
    });

    fireEvent.change(fileInput);

    await waitFor(() => {
      expect(mockOnError).toHaveBeenCalledWith(
        expect.stringContaining('Invalid file type')
      );
    });
  });

  it('validates file size', async () => {
    const largeFile = createMockFile('large.png', 10 * 1024 * 1024, 'image/png'); // 10MB
    
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024} // 5MB limit
      />
    );

    const fileInput = screen.getByLabelText(/upload image/i);
    
    Object.defineProperty(fileInput, 'files', {
      value: [largeFile],
      writable: false,
    });

    fireEvent.change(fileInput);

    await waitFor(() => {
      expect(mockOnError).toHaveBeenCalledWith(
        expect.stringContaining('File size too large')
      );
    });
  });

  it('shows current image when provided', () => {
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
        currentImage="/current-image.jpg"
      />
    );

    const currentImage = screen.getByAltText(/current/i);
    expect(currentImage).toBeInTheDocument();
    expect(currentImage).toHaveAttribute('src', '/current-image.jpg');
  });

  it('handles drag and drop', async () => {
    const mockFile = createMockFile('test.png', 1024, 'image/png');
    
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
      />
    );

    const dropZone = screen.getByText(/drag.*drop.*image/i).closest('div');
    
    const dropEvent = {
      preventDefault: vi.fn(),
      stopPropagation: vi.fn(),
      dataTransfer: {
        files: [mockFile],
        items: [{ kind: 'file', type: 'image/png', getAsFile: () => mockFile }],
        types: ['Files']
      }
    };

    fireEvent.drop(dropZone!, dropEvent as any);

    await waitFor(() => {
      expect(mockOnUpload).toHaveBeenCalled();
    });
  });

  it('shows loading state during upload', () => {
    render(
      <ImageUpload
        onUpload={mockOnUpload}
        onError={mockOnError}
        acceptedTypes={['image/png', 'image/jpeg']}
        maxSize={5 * 1024 * 1024}
        isLoading={true}
      />
    );

    expect(screen.getByText(/uploading/i)).toBeInTheDocument();
  });
});
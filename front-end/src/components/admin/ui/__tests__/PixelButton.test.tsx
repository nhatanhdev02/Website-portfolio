import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { PixelButton } from '../PixelButton';

describe('PixelButton', () => {
  it('renders with default props', () => {
    render(<PixelButton>Click me</PixelButton>);
    
    const button = screen.getByRole('button', { name: 'Click me' });
    expect(button).toBeInTheDocument();
    expect(button).toHaveClass('pixel-button');
  });

  it('applies variant classes correctly', () => {
    const { rerender } = render(<PixelButton variant="primary">Primary</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-primary');

    rerender(<PixelButton variant="secondary">Secondary</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-secondary');

    rerender(<PixelButton variant="danger">Danger</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-danger');

    rerender(<PixelButton variant="success">Success</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-success');
  });

  it('applies size classes correctly', () => {
    const { rerender } = render(<PixelButton size="sm">Small</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-sm');

    rerender(<PixelButton size="md">Medium</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-md');

    rerender(<PixelButton size="lg">Large</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('pixel-button-lg');
  });

  it('handles click events', () => {
    const handleClick = vi.fn();
    render(<PixelButton onClick={handleClick}>Click me</PixelButton>);
    
    fireEvent.click(screen.getByRole('button'));
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('can be disabled', () => {
    const handleClick = vi.fn();
    render(<PixelButton disabled onClick={handleClick}>Disabled</PixelButton>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    
    fireEvent.click(button);
    expect(handleClick).not.toHaveBeenCalled();
  });

  it('applies custom className', () => {
    render(<PixelButton className="custom-class">Custom</PixelButton>);
    expect(screen.getByRole('button')).toHaveClass('custom-class');
  });

  it('forwards ref correctly', () => {
    const ref = vi.fn();
    render(<PixelButton ref={ref}>Ref test</PixelButton>);
    expect(ref).toHaveBeenCalled();
  });

  it('supports loading state', () => {
    render(<PixelButton loading>Loading</PixelButton>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    expect(button).toHaveClass('pixel-button-loading');
  });

  it('renders with icon', () => {
    render(<PixelButton icon="🎮">With Icon</PixelButton>);
    
    const button = screen.getByRole('button');
    expect(button).toHaveTextContent('🎮 With Icon');
  });
});
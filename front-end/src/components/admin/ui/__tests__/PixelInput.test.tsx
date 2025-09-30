import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { PixelInput } from '../PixelInput';

describe('PixelInput', () => {
  it('renders with default props', () => {
    render(<PixelInput />);
    
    const input = screen.getByRole('textbox');
    expect(input).toBeInTheDocument();
    expect(input).toHaveClass('pixel-input');
  });

  it('handles value changes', () => {
    const handleChange = vi.fn();
    render(<PixelInput onChange={handleChange} />);
    
    const input = screen.getByRole('textbox');
    fireEvent.change(input, { target: { value: 'test value' } });
    
    expect(handleChange).toHaveBeenCalledTimes(1);
    expect(handleChange).toHaveBeenCalledWith(expect.objectContaining({
      target: expect.objectContaining({ value: 'test value' })
    }));
  });

  it('displays placeholder text', () => {
    render(<PixelInput placeholder="Enter text here" />);
    
    const input = screen.getByPlaceholderText('Enter text here');
    expect(input).toBeInTheDocument();
  });

  it('can be disabled', () => {
    render(<PixelInput disabled />);
    
    const input = screen.getByRole('textbox');
    expect(input).toBeDisabled();
    expect(input).toHaveClass('pixel-input-disabled');
  });

  it('shows error state', () => {
    render(<PixelInput error="This field is required" />);
    
    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('pixel-input-error');
    
    const errorMessage = screen.getByText('This field is required');
    expect(errorMessage).toBeInTheDocument();
    expect(errorMessage).toHaveClass('pixel-input-error-message');
  });

  it('shows success state', () => {
    render(<PixelInput success />);
    
    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('pixel-input-success');
  });

  it('renders with label', () => {
    render(<PixelInput label="Username" />);
    
    const label = screen.getByText('Username');
    expect(label).toBeInTheDocument();
    expect(label).toHaveClass('pixel-input-label');
  });

  it('supports different input types', () => {
    const { rerender } = render(<PixelInput type="email" />);
    expect(screen.getByRole('textbox')).toHaveAttribute('type', 'email');

    rerender(<PixelInput type="password" />);
    expect(screen.getByLabelText(/password/i)).toHaveAttribute('type', 'password');

    rerender(<PixelInput type="number" />);
    expect(screen.getByRole('spinbutton')).toHaveAttribute('type', 'number');
  });

  it('applies custom className', () => {
    render(<PixelInput className="custom-input" />);
    expect(screen.getByRole('textbox')).toHaveClass('custom-input');
  });

  it('forwards ref correctly', () => {
    const ref = vi.fn();
    render(<PixelInput ref={ref} />);
    expect(ref).toHaveBeenCalled();
  });

  it('shows character count when maxLength is provided', () => {
    render(<PixelInput maxLength={50} value="Hello" showCharCount />);
    
    const charCount = screen.getByText('5/50');
    expect(charCount).toBeInTheDocument();
    expect(charCount).toHaveClass('pixel-input-char-count');
  });

  it('handles focus and blur events', () => {
    const handleFocus = vi.fn();
    const handleBlur = vi.fn();
    
    render(<PixelInput onFocus={handleFocus} onBlur={handleBlur} />);
    
    const input = screen.getByRole('textbox');
    
    fireEvent.focus(input);
    expect(handleFocus).toHaveBeenCalledTimes(1);
    expect(input).toHaveClass('pixel-input-focused');
    
    fireEvent.blur(input);
    expect(handleBlur).toHaveBeenCalledTimes(1);
    expect(input).not.toHaveClass('pixel-input-focused');
  });
});
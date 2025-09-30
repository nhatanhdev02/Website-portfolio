# Pixel UI Components

A comprehensive collection of pixel-styled UI components for the admin dashboard, featuring a retro gaming aesthetic with consistent hover and focus effects.

## Components Overview

### Form Controls

#### PixelButton
A versatile button component with multiple variants and animations.

**Props:**
- `variant`: 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'info'
- `size`: 'sm' | 'md' | 'lg'
- `loading`: boolean - Shows loading spinner
- `fullWidth`: boolean - Makes button full width
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Pixel-style shadow effects
- Smooth press animations
- Loading states with pixel spinner
- Focus ring for accessibility
- Consistent hover effects

#### PixelInput
Text input component with validation states and helper text.

**Props:**
- `label`: string - Input label
- `error`: string - Error message
- `success`: boolean - Success state
- `variant`: 'default' | 'search' | 'password'
- `helperText`: string - Helper text below input
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Built-in validation states
- Search variant with icon
- Inset shadow for depth
- Focus ring and border animations
- Character indicators for validation

#### PixelTextarea
Multi-line text input with character counting and validation.

**Props:**
- `label`: string - Textarea label
- `error`: string - Error message
- `success`: boolean - Success state
- `showCharCount`: boolean - Shows character counter
- `maxLength`: number - Maximum character limit
- `helperText`: string - Helper text
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Character counting with visual warnings
- Resizable with consistent styling
- Validation state indicators
- Focus effects matching other inputs

#### PixelSelect
Dropdown select component with custom styling.

**Props:**
- `label`: string - Select label
- `error`: string - Error message
- `success`: boolean - Success state
- `options`: SelectOption[] - Array of options
- `placeholder`: string - Placeholder text
- `helperText`: string - Helper text
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Custom dropdown arrow
- Consistent validation states
- Pixel-styled options
- Keyboard navigation support

#### PixelCheckbox
Custom checkbox with pixel styling.

**Props:**
- `label`: string - Checkbox label
- `error`: string - Error message
- `helperText`: string - Helper text
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Custom pixel-style checkbox
- Animated check mark
- Focus ring for accessibility
- Error state styling

#### PixelRadio
Radio button component matching checkbox styling.

**Props:**
- `label`: string - Radio label
- `error`: string - Error message
- `helperText`: string - Helper text
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Pixel-style radio button
- Consistent with checkbox design
- Focus and hover effects
- Group support

#### PixelToggle
Toggle switch component with multiple sizes.

**Props:**
- `label`: string - Toggle label
- `error`: string - Error message
- `helperText`: string - Helper text
- `size`: 'sm' | 'md' | 'lg'
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Smooth sliding animation
- Multiple size options
- Pixel-style track and thumb
- Accessible keyboard controls

### Display Components

#### PixelCard
Container component with optional header and variants.

**Props:**
- `title`: string - Card title
- `subtitle`: string - Card subtitle
- `icon`: string - Header icon
- `variant`: 'default' | 'primary' | 'success' | 'warning' | 'danger'
- `hoverable`: boolean - Enables hover effects
- `onClick`: function - Click handler

**Features:**
- Multiple color variants
- Optional header with icon
- Hover animations when clickable
- Consistent shadow effects

#### PixelBadge
Small status indicator component.

**Props:**
- `variant`: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
- `size`: 'sm' | 'md' | 'lg'

**Features:**
- Multiple variants and sizes
- Pixel-style shadows
- Consistent color scheme

#### PixelAlert
Alert/notification component with dismissible option.

**Props:**
- `variant`: 'default' | 'success' | 'warning' | 'danger' | 'info'
- `title`: string - Alert title
- `dismissible`: boolean - Shows dismiss button
- `onDismiss`: function - Dismiss handler
- `pixelStyle`: boolean - Enables pixel styling (default: true)

**Features:**
- Multiple alert types
- Optional dismiss functionality
- Icon indicators
- Consistent styling with other components

## Design Principles

### Pixel Art Aesthetic
- Monospace fonts for consistency
- Sharp, pixelated borders (no border-radius)
- Retro color palette
- Blocky shadows and effects

### Accessibility
- Focus rings on all interactive elements
- Proper ARIA labels and roles
- Keyboard navigation support
- High contrast ratios
- Screen reader compatibility

### Consistency
- Unified color scheme across components
- Consistent spacing and sizing
- Matching animation timings
- Shared visual language

### Validation States
All form components support three states:
- **Default**: Normal state with hover effects
- **Error**: Red borders and error messages
- **Success**: Green borders and success indicators

## Usage Examples

```tsx
import {
  PixelButton,
  PixelInput,
  PixelCard,
  PixelAlert
} from '@/components/admin/ui';

// Basic form
<PixelCard title="User Settings" icon="⚙️">
  <PixelInput
    label="Username"
    placeholder="Enter username..."
    helperText="Must be unique"
  />
  
  <PixelButton variant="primary" fullWidth>
    Save Changes
  </PixelButton>
</PixelCard>

// Error handling
<PixelInput
  label="Email"
  error="Invalid email format"
  value={email}
  onChange={handleEmailChange}
/>

// Success notification
<PixelAlert variant="success" title="Saved!">
  Your changes have been saved successfully.
</PixelAlert>
```

## Customization

### Non-Pixel Mode
All components support a `pixelStyle={false}` prop to use standard styling:

```tsx
<PixelButton pixelStyle={false} variant="primary">
  Standard Button
</PixelButton>
```

### Custom Styling
Components accept `className` prop for additional customization:

```tsx
<PixelCard className="border-purple-600 bg-purple-900">
  Custom colored card
</PixelCard>
```

## Testing

Use the `PixelUIDemo` component to test all components in various states:

```tsx
import { PixelUIDemo } from '@/components/admin/ui';

// Render demo page
<PixelUIDemo />
```

This provides a comprehensive showcase of all components with different variants, states, and interactions.
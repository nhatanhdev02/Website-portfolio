import React, { useState } from 'react';
import {
  PixelButton,
  PixelInput,
  PixelTextarea,
  PixelSelect,
  PixelCheckbox,
  PixelRadio,
  PixelToggle,
  PixelCard,
  PixelBadge,
  PixelAlert
} from './index';

export const PixelUIDemo: React.FC = () => {
  const [inputValue, setInputValue] = useState('');
  const [textareaValue, setTextareaValue] = useState('');
  const [selectValue, setSelectValue] = useState('');
  const [checkboxValue, setCheckboxValue] = useState(false);
  const [radioValue, setRadioValue] = useState('option1');
  const [toggleValue, setToggleValue] = useState(false);
  const [showAlert, setShowAlert] = useState(true);

  const selectOptions = [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
    { value: 'option3', label: 'Option 3' }
  ];

  return (
    <div className="p-8 bg-gray-900 min-h-screen space-y-8">
      <h1 className="text-3xl font-bold text-white font-mono mb-8">
        Pixel UI Components Demo
      </h1>

      {/* Alerts */}
      {showAlert && (
        <PixelAlert
          variant="info"
          title="Demo Alert"
          dismissible
          onDismiss={() => setShowAlert(false)}
        >
          This is a demo of the pixel-styled alert component with dismissible functionality.
        </PixelAlert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Buttons */}
        <PixelCard title="Buttons" icon="🔘">
          <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
              <PixelButton variant="primary" size="sm">Primary SM</PixelButton>
              <PixelButton variant="secondary" size="md">Secondary MD</PixelButton>
              <PixelButton variant="success" size="lg">Success LG</PixelButton>
            </div>
            <div className="flex flex-wrap gap-2">
              <PixelButton variant="danger">Danger</PixelButton>
              <PixelButton variant="warning">Warning</PixelButton>
              <PixelButton variant="info">Info</PixelButton>
            </div>
            <PixelButton variant="primary" fullWidth loading>
              Loading Button
            </PixelButton>
          </div>
        </PixelCard>

        {/* Form Controls */}
        <PixelCard title="Form Controls" icon="📝">
          <div className="space-y-4">
            <PixelInput
              label="Text Input"
              placeholder="Enter some text..."
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              helperText="This is helper text"
            />
            
            <PixelInput
              label="Search Input"
              variant="search"
              placeholder="Search..."
              helperText="Search variant with icon"
            />
            
            <PixelSelect
              label="Select Dropdown"
              options={selectOptions}
              value={selectValue}
              onChange={(e) => setSelectValue(e.target.value)}
              placeholder="Choose an option..."
              helperText="Select from available options"
            />
          </div>
        </PixelCard>

        {/* Textarea */}
        <PixelCard title="Textarea" icon="📄">
          <PixelTextarea
            label="Description"
            placeholder="Enter a longer description..."
            value={textareaValue}
            onChange={(e) => setTextareaValue(e.target.value)}
            showCharCount
            maxLength={200}
            helperText="Maximum 200 characters"
          />
        </PixelCard>

        {/* Checkboxes and Radios */}
        <PixelCard title="Checkboxes & Radios" icon="☑️">
          <div className="space-y-4">
            <PixelCheckbox
              label="Enable notifications"
              checked={checkboxValue}
              onChange={(e) => setCheckboxValue(e.target.checked)}
              helperText="Receive email notifications"
            />
            
            <div className="space-y-2">
              <p className="text-sm font-mono text-gray-300">Choose an option:</p>
              <PixelRadio
                name="demo-radio"
                label="Option 1"
                value="option1"
                checked={radioValue === 'option1'}
                onChange={(e) => setRadioValue(e.target.value)}
              />
              <PixelRadio
                name="demo-radio"
                label="Option 2"
                value="option2"
                checked={radioValue === 'option2'}
                onChange={(e) => setRadioValue(e.target.value)}
              />
            </div>
            
            <PixelToggle
              label="Dark Mode"
              checked={toggleValue}
              onChange={(e) => setToggleValue(e.target.checked)}
              helperText="Toggle dark mode on/off"
            />
          </div>
        </PixelCard>

        {/* Badges */}
        <PixelCard title="Badges" icon="🏷️">
          <div className="flex flex-wrap gap-2">
            <PixelBadge variant="default">Default</PixelBadge>
            <PixelBadge variant="primary">Primary</PixelBadge>
            <PixelBadge variant="success">Success</PixelBadge>
            <PixelBadge variant="warning">Warning</PixelBadge>
            <PixelBadge variant="danger">Danger</PixelBadge>
            <PixelBadge variant="info">Info</PixelBadge>
          </div>
        </PixelCard>

        {/* Error States */}
        <PixelCard title="Error States" icon="⚠️" variant="warning">
          <div className="space-y-4">
            <PixelInput
              label="Input with Error"
              error="This field is required"
              placeholder="Enter value..."
            />
            
            <PixelCheckbox
              label="Required checkbox"
              error="You must accept the terms"
            />
            
            <PixelAlert variant="danger" title="Error Alert">
              Something went wrong! Please try again.
            </PixelAlert>
          </div>
        </PixelCard>
      </div>

      {/* Success States */}
      <PixelCard title="Success States" icon="✅" variant="success">
        <div className="space-y-4">
          <PixelInput
            label="Valid Input"
            success
            value="Valid value"
            readOnly
          />
          
          <PixelAlert variant="success" title="Success!">
            Your changes have been saved successfully.
          </PixelAlert>
        </div>
      </PixelCard>
    </div>
  );
};
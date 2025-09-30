import React, { useState, useRef, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { PixelInput } from './PixelInput';

interface TechnologyTagInputProps {
  technologies: string[];
  onTechnologiesChange: (technologies: string[]) => void;
  suggestions?: string[];
  label?: string;
  error?: string;
  helperText?: string;
  placeholder?: string;
  maxTags?: number;
  className?: string;
}

const defaultSuggestions = [
  'React', 'Vue.js', 'Angular', 'Next.js', 'Nuxt.js', 'Svelte',
  'Node.js', 'Express', 'NestJS', 'FastAPI', 'Django', 'Flask',
  'TypeScript', 'JavaScript', 'Python', 'Java', 'C#', 'PHP', 'Go', 'Rust',
  'MongoDB', 'PostgreSQL', 'MySQL', 'Redis', 'SQLite', 'Firebase',
  'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'Vercel', 'Netlify',
  'Tailwind CSS', 'Bootstrap', 'Material-UI', 'Chakra UI', 'Ant Design',
  'React Native', 'Flutter', 'Ionic', 'Electron',
  'GraphQL', 'REST API', 'Socket.io', 'WebRTC',
  'Jest', 'Cypress', 'Playwright', 'Vitest',
  'Webpack', 'Vite', 'Rollup', 'Parcel',
  'Git', 'GitHub', 'GitLab', 'Bitbucket'
];

export const TechnologyTagInput: React.FC<TechnologyTagInputProps> = ({
  technologies,
  onTechnologiesChange,
  suggestions = defaultSuggestions,
  label,
  error,
  helperText,
  placeholder = "Type technology name...",
  maxTags = 20,
  className
}) => {
  const [inputValue, setInputValue] = useState('');
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [selectedSuggestionIndex, setSelectedSuggestionIndex] = useState(-1);
  const inputRef = useRef<HTMLInputElement>(null);
  const suggestionsRef = useRef<HTMLDivElement>(null);

  // Filter suggestions based on input and exclude already selected technologies
  const filteredSuggestions = suggestions.filter(suggestion =>
    suggestion.toLowerCase().includes(inputValue.toLowerCase()) &&
    !technologies.includes(suggestion)
  ).slice(0, 10); // Limit to 10 suggestions

  useEffect(() => {
    // Reset selected suggestion when filtered suggestions change
    setSelectedSuggestionIndex(-1);
  }, [filteredSuggestions]);

  const addTechnology = (tech: string) => {
    const trimmedTech = tech.trim();
    if (trimmedTech && !technologies.includes(trimmedTech) && technologies.length < maxTags) {
      onTechnologiesChange([...technologies, trimmedTech]);
      setInputValue('');
      setShowSuggestions(false);
      setSelectedSuggestionIndex(-1);
    }
  };

  const removeTechnology = (index: number) => {
    const newTechnologies = technologies.filter((_, i) => i !== index);
    onTechnologiesChange(newTechnologies);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setInputValue(value);
    setShowSuggestions(value.length > 0);
  };

  const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    switch (e.key) {
      case 'Enter':
      case ',':
        e.preventDefault();
        if (selectedSuggestionIndex >= 0 && filteredSuggestions[selectedSuggestionIndex]) {
          addTechnology(filteredSuggestions[selectedSuggestionIndex]);
        } else if (inputValue.trim()) {
          addTechnology(inputValue);
        }
        break;
      
      case 'ArrowDown':
        e.preventDefault();
        if (showSuggestions && filteredSuggestions.length > 0) {
          setSelectedSuggestionIndex(prev => 
            prev < filteredSuggestions.length - 1 ? prev + 1 : 0
          );
        }
        break;
      
      case 'ArrowUp':
        e.preventDefault();
        if (showSuggestions && filteredSuggestions.length > 0) {
          setSelectedSuggestionIndex(prev => 
            prev > 0 ? prev - 1 : filteredSuggestions.length - 1
          );
        }
        break;
      
      case 'Escape':
        setShowSuggestions(false);
        setSelectedSuggestionIndex(-1);
        break;
      
      case 'Backspace':
        if (inputValue === '' && technologies.length > 0) {
          removeTechnology(technologies.length - 1);
        }
        break;
    }
  };

  const handleInputFocus = () => {
    if (inputValue.length > 0) {
      setShowSuggestions(true);
    }
  };

  const handleInputBlur = () => {
    // Delay hiding suggestions to allow clicking on them
    setTimeout(() => {
      setShowSuggestions(false);
      setSelectedSuggestionIndex(-1);
    }, 200);
  };

  const handleSuggestionClick = (suggestion: string) => {
    addTechnology(suggestion);
    inputRef.current?.focus();
  };

  return (
    <div className={cn('space-y-2', className)}>
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}

      {/* Input Container */}
      <div className="relative">
        {/* Tags and Input */}
        <div className={cn(
          'min-h-[42px] p-2 border-2 bg-gray-800 font-mono text-sm text-white flex flex-wrap gap-2 items-center',
          error ? 'border-red-600' : 'border-gray-600 focus-within:border-blue-500'
        )}>
          {/* Technology Tags */}
          {technologies.map((tech, index) => (
            <div
              key={index}
              className="flex items-center gap-1 px-2 py-1 bg-blue-600 border border-blue-800 text-white text-xs font-mono"
            >
              <span>{tech}</span>
              <button
                type="button"
                onClick={() => removeTechnology(index)}
                className="text-blue-200 hover:text-white ml-1 transition-colors"
              >
                ✕
              </button>
            </div>
          ))}

          {/* Input Field */}
          <input
            ref={inputRef}
            type="text"
            value={inputValue}
            onChange={handleInputChange}
            onKeyDown={handleInputKeyDown}
            onFocus={handleInputFocus}
            onBlur={handleInputBlur}
            placeholder={technologies.length === 0 ? placeholder : ''}
            disabled={technologies.length >= maxTags}
            className="flex-1 min-w-[120px] bg-transparent outline-none placeholder-gray-500 disabled:cursor-not-allowed"
          />
        </div>

        {/* Suggestions Dropdown */}
        {showSuggestions && filteredSuggestions.length > 0 && (
          <div
            ref={suggestionsRef}
            className="absolute top-full left-0 right-0 z-10 mt-1 bg-gray-800 border-2 border-gray-600 max-h-48 overflow-y-auto"
          >
            {filteredSuggestions.map((suggestion, index) => (
              <button
                key={suggestion}
                type="button"
                onClick={() => handleSuggestionClick(suggestion)}
                className={cn(
                  'w-full px-3 py-2 text-left text-sm font-mono transition-colors',
                  index === selectedSuggestionIndex
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-300 hover:bg-gray-700'
                )}
              >
                {suggestion}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <p className="text-sm text-red-400 flex items-center gap-1 font-mono">
          <span>⚠</span>
          {error}
        </p>
      )}

      {/* Helper Text */}
      {helperText && !error && (
        <p className="text-sm text-gray-500 font-mono">
          {helperText}
        </p>
      )}

      {/* Instructions */}
      <div className="text-xs text-gray-500 font-mono space-y-1">
        <div>• Type and press Enter or comma to add technology</div>
        <div>• Use arrow keys to navigate suggestions</div>
        <div>• Press Backspace to remove last tag</div>
        <div>• {technologies.length} / {maxTags} technologies added</div>
      </div>
    </div>
  );
};
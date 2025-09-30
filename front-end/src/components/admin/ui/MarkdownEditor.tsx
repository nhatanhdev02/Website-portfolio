import React, { useState, useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';
import { PixelButton } from './PixelButton';

interface MarkdownEditorProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  height?: string;
  autoSave?: boolean;
  onAutoSave?: (value: string) => void;
  autoSaveInterval?: number; // in milliseconds
}

export const MarkdownEditor: React.FC<MarkdownEditorProps> = ({
  value,
  onChange,
  placeholder = 'Write your markdown content here...',
  height = '400px',
  autoSave = false,
  onAutoSave,
  autoSaveInterval = 30000 // 30 seconds
}) => {
  const [activeTab, setActiveTab] = useState<'edit' | 'preview' | 'split'>('split');
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const autoSaveTimeoutRef = useRef<NodeJS.Timeout>();

  // Auto-save functionality
  useEffect(() => {
    if (!autoSave || !onAutoSave) return;

    // Clear existing timeout
    if (autoSaveTimeoutRef.current) {
      clearTimeout(autoSaveTimeoutRef.current);
    }

    // Set new timeout
    autoSaveTimeoutRef.current = setTimeout(() => {
      onAutoSave(value);
      setLastSaved(new Date());
    }, autoSaveInterval);

    return () => {
      if (autoSaveTimeoutRef.current) {
        clearTimeout(autoSaveTimeoutRef.current);
      }
    };
  }, [value, autoSave, onAutoSave, autoSaveInterval]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (autoSaveTimeoutRef.current) {
        clearTimeout(autoSaveTimeoutRef.current);
      }
    };
  }, []);

  // Markdown toolbar actions
  const insertMarkdown = (before: string, after: string = '', placeholder: string = '') => {
    const textarea = textareaRef.current;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = value.substring(start, end);
    const replacement = selectedText || placeholder;
    
    const newValue = 
      value.substring(0, start) + 
      before + replacement + after + 
      value.substring(end);
    
    onChange(newValue);

    // Restore cursor position
    setTimeout(() => {
      const newCursorPos = start + before.length + replacement.length;
      textarea.setSelectionRange(newCursorPos, newCursorPos);
      textarea.focus();
    }, 0);
  };

  const toolbarButtons = [
    { label: 'Bold', action: () => insertMarkdown('**', '**', 'bold text'), icon: 'B' },
    { label: 'Italic', action: () => insertMarkdown('*', '*', 'italic text'), icon: 'I' },
    { label: 'Code', action: () => insertMarkdown('`', '`', 'code'), icon: '</>' },
    { label: 'Link', action: () => insertMarkdown('[', '](url)', 'link text'), icon: '🔗' },
    { label: 'Image', action: () => insertMarkdown('![', '](image-url)', 'alt text'), icon: '🖼️' },
    { label: 'Heading', action: () => insertMarkdown('## ', '', 'Heading'), icon: 'H' },
    { label: 'List', action: () => insertMarkdown('- ', '', 'List item'), icon: '•' },
    { label: 'Quote', action: () => insertMarkdown('> ', '', 'Quote'), icon: '"' },
  ];

  // Simple markdown to HTML converter (basic implementation)
  const markdownToHtml = (markdown: string): string => {
    let html = markdown;
    
    // Headers
    html = html.replace(/^### (.*$)/gim, '<h3 class="text-lg font-bold mb-2 text-white">$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2 class="text-xl font-bold mb-3 text-white">$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1 class="text-2xl font-bold mb-4 text-white">$1</h1>');
    
    // Bold and Italic
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-white">$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em class="italic text-gray-300">$1</em>');
    
    // Code
    html = html.replace(/`(.*?)`/g, '<code class="bg-gray-700 text-green-400 px-1 py-0.5 rounded font-mono text-sm">$1</code>');
    
    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-400 hover:text-blue-300 underline" target="_blank" rel="noopener noreferrer">$1</a>');
    
    // Images
    html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" class="max-w-full h-auto border-2 border-gray-600 my-2" style="image-rendering: pixelated;" />');
    
    // Lists
    html = html.replace(/^\- (.*$)/gim, '<li class="ml-4 text-gray-300">• $1</li>');
    html = html.replace(/(<li.*<\/li>)/s, '<ul class="mb-3">$1</ul>');
    
    // Blockquotes
    html = html.replace(/^> (.*$)/gim, '<blockquote class="border-l-4 border-blue-600 pl-4 italic text-gray-400 my-2">$1</blockquote>');
    
    // Line breaks
    html = html.replace(/\n\n/g, '</p><p class="mb-3 text-gray-300">');
    html = html.replace(/\n/g, '<br>');
    
    // Wrap in paragraphs
    if (html && !html.startsWith('<')) {
      html = '<p class="mb-3 text-gray-300">' + html + '</p>';
    }
    
    return html;
  };

  const formatLastSaved = (date: Date | null): string => {
    if (!date) return '';
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    
    if (minutes < 1) return 'Saved just now';
    if (minutes === 1) return 'Saved 1 minute ago';
    return `Saved ${minutes} minutes ago`;
  };

  return (
    <div className="border-2 border-gray-600 bg-gray-800 overflow-hidden">
      {/* Toolbar */}
      <div className="border-b-2 border-gray-600 bg-gray-700 p-3">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          {/* View Mode Tabs */}
          <div className="flex border-2 border-gray-600 bg-gray-800 overflow-hidden">
            <button
              onClick={() => setActiveTab('edit')}
              className={cn(
                'px-3 py-1 text-sm font-mono border-r-2 border-gray-600 transition-colors duration-200',
                activeTab === 'edit'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              )}
            >
              Edit
            </button>
            <button
              onClick={() => setActiveTab('preview')}
              className={cn(
                'px-3 py-1 text-sm font-mono border-r-2 border-gray-600 transition-colors duration-200',
                activeTab === 'preview'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              )}
            >
              Preview
            </button>
            <button
              onClick={() => setActiveTab('split')}
              className={cn(
                'px-3 py-1 text-sm font-mono transition-colors duration-200',
                activeTab === 'split'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              )}
            >
              Split
            </button>
          </div>

          {/* Auto-save status */}
          {autoSave && (
            <div className="text-xs font-mono text-gray-400">
              {formatLastSaved(lastSaved)}
            </div>
          )}
        </div>

        {/* Markdown Toolbar */}
        {(activeTab === 'edit' || activeTab === 'split') && (
          <div className="flex flex-wrap gap-1 mt-3 pt-3 border-t-2 border-gray-600">
            {toolbarButtons.map((button) => (
              <PixelButton
                key={button.label}
                variant="secondary"
                size="sm"
                onClick={button.action}
                title={button.label}
                className="text-xs"
              >
                {button.icon}
              </PixelButton>
            ))}
          </div>
        )}
      </div>

      {/* Editor Content */}
      <div className="flex" style={{ height }}>
        {/* Edit Mode */}
        {(activeTab === 'edit' || activeTab === 'split') && (
          <div className={cn(
            'flex-1 border-gray-600',
            activeTab === 'split' ? 'border-r-2' : ''
          )}>
            <textarea
              ref={textareaRef}
              value={value}
              onChange={(e) => onChange(e.target.value)}
              placeholder={placeholder}
              className="w-full h-full p-4 bg-gray-800 text-gray-300 font-mono text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset"
              style={{ minHeight: height }}
            />
          </div>
        )}

        {/* Preview Mode */}
        {(activeTab === 'preview' || activeTab === 'split') && (
          <div className="flex-1 p-4 bg-gray-900 overflow-y-auto">
            {value.trim() ? (
              <div 
                className="prose prose-invert max-w-none"
                dangerouslySetInnerHTML={{ __html: markdownToHtml(value) }}
              />
            ) : (
              <div className="text-gray-500 font-mono text-sm italic">
                Preview will appear here as you type...
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};
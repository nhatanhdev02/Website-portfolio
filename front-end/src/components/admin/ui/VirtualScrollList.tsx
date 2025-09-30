import React, { useState, useEffect, useRef, useMemo } from 'react';

interface VirtualScrollListProps<T> {
  items: T[];
  itemHeight: number;
  containerHeight: number;
  renderItem: (item: T, index: number) => React.ReactNode;
  overscan?: number;
  className?: string;
}

export function VirtualScrollList<T>({
  items,
  itemHeight,
  containerHeight,
  renderItem,
  overscan = 5,
  className = ''
}: VirtualScrollListProps<T>) {
  const [scrollTop, setScrollTop] = useState(0);
  const scrollElementRef = useRef<HTMLDivElement>(null);

  const totalHeight = items.length * itemHeight;

  const visibleRange = useMemo(() => {
    const start = Math.floor(scrollTop / itemHeight);
    const end = Math.min(
      start + Math.ceil(containerHeight / itemHeight),
      items.length - 1
    );

    return {
      start: Math.max(0, start - overscan),
      end: Math.min(items.length - 1, end + overscan)
    };
  }, [scrollTop, itemHeight, containerHeight, items.length, overscan]);

  const visibleItems = useMemo(() => {
    const result = [];
    for (let i = visibleRange.start; i <= visibleRange.end; i++) {
      result.push({
        index: i,
        item: items[i],
        offsetY: i * itemHeight
      });
    }
    return result;
  }, [items, visibleRange, itemHeight]);

  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    setScrollTop(e.currentTarget.scrollTop);
  };

  useEffect(() => {
    const scrollElement = scrollElementRef.current;
    if (scrollElement) {
      const handleScrollEvent = () => {
        setScrollTop(scrollElement.scrollTop);
      };

      scrollElement.addEventListener('scroll', handleScrollEvent, { passive: true });
      return () => scrollElement.removeEventListener('scroll', handleScrollEvent);
    }
  }, []);

  return (
    <div
      ref={scrollElementRef}
      className={`overflow-auto ${className}`}
      style={{ height: containerHeight }}
      onScroll={handleScroll}
    >
      <div style={{ height: totalHeight, position: 'relative' }}>
        {visibleItems.map(({ index, item, offsetY }) => (
          <div
            key={index}
            style={{
              position: 'absolute',
              top: offsetY,
              left: 0,
              right: 0,
              height: itemHeight
            }}
          >
            {renderItem(item, index)}
          </div>
        ))}
      </div>
    </div>
  );
}

// Specialized components for admin use cases
interface VirtualMessageListProps {
  messages: Array<{
    id: string;
    name: string;
    email: string;
    message: string;
    timestamp: Date;
    read: boolean;
  }>;
  onMessageClick: (messageId: string) => void;
  containerHeight?: number;
}

export const VirtualMessageList: React.FC<VirtualMessageListProps> = ({
  messages,
  onMessageClick,
  containerHeight = 400
}) => {
  const renderMessage = (message: any, index: number) => (
    <div
      className={`p-4 border-b border-pixel-border cursor-pointer hover:bg-pixel-bg-secondary transition-colors ${
        !message.read ? 'bg-pixel-accent/10' : ''
      }`}
      onClick={() => onMessageClick(message.id)}
    >
      <div className="flex justify-between items-start mb-2">
        <div className="font-pixel text-sm text-pixel-primary">
          {message.name}
        </div>
        <div className="text-xs text-pixel-muted">
          {new Date(message.timestamp).toLocaleDateString()}
        </div>
      </div>
      <div className="text-xs text-pixel-muted mb-2">{message.email}</div>
      <div className="text-sm text-pixel-text line-clamp-2">
        {message.message}
      </div>
      {!message.read && (
        <div className="mt-2">
          <span className="inline-block w-2 h-2 bg-pixel-accent rounded-full"></span>
        </div>
      )}
    </div>
  );

  return (
    <VirtualScrollList
      items={messages}
      itemHeight={120}
      containerHeight={containerHeight}
      renderItem={renderMessage}
      className="border border-pixel-border rounded-lg"
    />
  );
};

interface VirtualBlogListProps {
  posts: Array<{
    id: string;
    title: { vi: string; en: string };
    status: 'draft' | 'published';
    publishDate: Date;
    tags: string[];
  }>;
  onPostClick: (postId: string) => void;
  language: 'vi' | 'en';
  containerHeight?: number;
}

export const VirtualBlogList: React.FC<VirtualBlogListProps> = ({
  posts,
  onPostClick,
  language,
  containerHeight = 400
}) => {
  const renderPost = (post: any, index: number) => (
    <div
      className="p-4 border-b border-pixel-border cursor-pointer hover:bg-pixel-bg-secondary transition-colors"
      onClick={() => onPostClick(post.id)}
    >
      <div className="flex justify-between items-start mb-2">
        <h3 className="font-pixel text-sm text-pixel-primary line-clamp-1">
          {post.title[language]}
        </h3>
        <span
          className={`px-2 py-1 text-xs font-pixel rounded ${
            post.status === 'published'
              ? 'bg-green-100 text-green-800'
              : 'bg-yellow-100 text-yellow-800'
          }`}
        >
          {post.status}
        </span>
      </div>
      <div className="text-xs text-pixel-muted mb-2">
        {new Date(post.publishDate).toLocaleDateString()}
      </div>
      <div className="flex flex-wrap gap-1">
        {post.tags.slice(0, 3).map((tag: string) => (
          <span
            key={tag}
            className="px-2 py-1 text-xs bg-pixel-accent/20 text-pixel-accent rounded"
          >
            {tag}
          </span>
        ))}
        {post.tags.length > 3 && (
          <span className="text-xs text-pixel-muted">
            +{post.tags.length - 3} more
          </span>
        )}
      </div>
    </div>
  );

  return (
    <VirtualScrollList
      items={posts}
      itemHeight={100}
      containerHeight={containerHeight}
      renderItem={renderPost}
      className="border border-pixel-border rounded-lg"
    />
  );
};
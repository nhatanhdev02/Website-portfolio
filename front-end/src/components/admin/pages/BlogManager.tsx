import React, { useState, useMemo } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { BlogPost } from '@/types/admin';
import { PixelButton, PixelInput, PixelSelect, PixelCard, PixelCheckbox, PixelBadge } from '@/components/admin/ui';
import { BlogForm } from '@/components/admin/forms/BlogForm';
import { cn } from '@/lib/utils';

type FilterStatus = 'all' | 'draft' | 'published';
type SortBy = 'date' | 'title' | 'status';
type SortOrder = 'asc' | 'desc';

export const BlogManager: React.FC = () => {
  const { blogPosts, deleteBlogPost, publishBlogPost } = useAdmin();
  
  // Filter and search state
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState<FilterStatus>('all');
  const [filterTags, setFilterTags] = useState<string[]>([]);
  const [sortBy, setSortBy] = useState<SortBy>('date');
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc');
  
  // Selection state for bulk operations
  const [selectedPosts, setSelectedPosts] = useState<string[]>([]);
  
  // UI state
  const [showFilters, setShowFilters] = useState(false);
  const [currentView, setCurrentView] = useState<'list' | 'create' | 'edit'>('list');
  const [editingPost, setEditingPost] = useState<BlogPost | null>(null);

  // Get all unique tags from blog posts
  const allTags = useMemo(() => {
    const tags = new Set<string>();
    blogPosts.forEach(post => {
      post.tags.forEach(tag => tags.add(tag));
    });
    return Array.from(tags).sort();
  }, [blogPosts]);

  // Filter and sort blog posts
  const filteredAndSortedPosts = useMemo(() => {
    let filtered = blogPosts.filter(post => {
      // Status filter
      if (filterStatus !== 'all' && post.status !== filterStatus) {
        return false;
      }
      
      // Tag filter
      if (filterTags.length > 0 && !filterTags.some(tag => post.tags.includes(tag))) {
        return false;
      }
      
      // Search filter (title and content)
      if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase();
        const titleMatch = post.title.vi.toLowerCase().includes(query) || 
                          post.title.en.toLowerCase().includes(query);
        const contentMatch = post.content.vi.toLowerCase().includes(query) || 
                            post.content.en.toLowerCase().includes(query);
        const excerptMatch = post.excerpt.vi.toLowerCase().includes(query) || 
                            post.excerpt.en.toLowerCase().includes(query);
        
        if (!titleMatch && !contentMatch && !excerptMatch) {
          return false;
        }
      }
      
      return true;
    });

    // Sort posts
    filtered.sort((a, b) => {
      let comparison = 0;
      
      switch (sortBy) {
        case 'date':
          comparison = new Date(a.publishDate).getTime() - new Date(b.publishDate).getTime();
          break;
        case 'title':
          comparison = a.title.en.localeCompare(b.title.en);
          break;
        case 'status':
          comparison = a.status.localeCompare(b.status);
          break;
      }
      
      return sortOrder === 'asc' ? comparison : -comparison;
    });

    return filtered;
  }, [blogPosts, searchQuery, filterStatus, filterTags, sortBy, sortOrder]);

  // Handle post selection
  const handleSelectPost = (postId: string, selected: boolean) => {
    if (selected) {
      setSelectedPosts(prev => [...prev, postId]);
    } else {
      setSelectedPosts(prev => prev.filter(id => id !== postId));
    }
  };

  const handleSelectAll = (selected: boolean) => {
    if (selected) {
      setSelectedPosts(filteredAndSortedPosts.map(post => post.id));
    } else {
      setSelectedPosts([]);
    }
  };

  // Handle tag filter toggle
  const handleTagFilter = (tag: string) => {
    setFilterTags(prev => 
      prev.includes(tag) 
        ? prev.filter(t => t !== tag)
        : [...prev, tag]
    );
  };

  // Handle bulk operations
  const handleBulkDelete = () => {
    if (selectedPosts.length === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selectedPosts.length} post(s)?`)) {
      selectedPosts.forEach(postId => deleteBlogPost(postId));
      setSelectedPosts([]);
    }
  };

  const handleBulkPublish = () => {
    if (selectedPosts.length === 0) return;
    
    const draftPosts = selectedPosts.filter(postId => {
      const post = blogPosts.find(p => p.id === postId);
      return post?.status === 'draft';
    });
    
    if (draftPosts.length === 0) {
      alert('No draft posts selected to publish.');
      return;
    }
    
    if (confirm(`Are you sure you want to publish ${draftPosts.length} draft post(s)?`)) {
      draftPosts.forEach(postId => publishBlogPost(postId));
      setSelectedPosts([]);
    }
  };

  // Handle individual post actions
  const handleDeletePost = (postId: string) => {
    const post = blogPosts.find(p => p.id === postId);
    if (!post) return;
    
    if (confirm(`Are you sure you want to delete "${post.title.en}"?`)) {
      deleteBlogPost(postId);
    }
  };

  const handlePublishPost = (postId: string) => {
    const post = blogPosts.find(p => p.id === postId);
    if (!post) return;
    
    if (post.status === 'published') {
      alert('This post is already published.');
      return;
    }
    
    if (confirm(`Are you sure you want to publish "${post.title.en}"?`)) {
      publishBlogPost(postId);
    }
  };

  // Handle navigation
  const handleCreatePost = () => {
    setCurrentView('create');
    setEditingPost(null);
  };

  const handleEditPost = (post: BlogPost) => {
    setCurrentView('edit');
    setEditingPost(post);
  };

  const handleFormSave = (post: BlogPost) => {
    setCurrentView('list');
    setEditingPost(null);
  };

  const handleFormCancel = () => {
    setCurrentView('list');
    setEditingPost(null);
  };

  // Render different views based on current state
  if (currentView === 'create') {
    return (
      <BlogForm
        onSave={handleFormSave}
        onCancel={handleFormCancel}
      />
    );
  }

  if (currentView === 'edit' && editingPost) {
    return (
      <BlogForm
        post={editingPost}
        onSave={handleFormSave}
        onCancel={handleFormCancel}
      />
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-mono font-bold text-white mb-2">Blog Management</h1>
          <p className="text-gray-400 font-mono text-sm">
            Manage blog posts, drafts, and publication workflow
          </p>
        </div>
        
        <div className="flex gap-2">
          <PixelButton
            variant="secondary"
            size="sm"
            onClick={() => setShowFilters(!showFilters)}
          >
            {showFilters ? 'Hide Filters' : 'Show Filters'}
          </PixelButton>
          
          <PixelButton
            variant="primary"
            size="sm"
            onClick={handleCreatePost}
          >
            + New Post
          </PixelButton>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <PixelCard className="p-4">
          <div className="text-center">
            <div className="text-2xl font-mono font-bold text-blue-400">
              {blogPosts.length}
            </div>
            <div className="text-sm font-mono text-gray-400">Total Posts</div>
          </div>
        </PixelCard>
        
        <PixelCard className="p-4">
          <div className="text-center">
            <div className="text-2xl font-mono font-bold text-green-400">
              {blogPosts.filter(p => p.status === 'published').length}
            </div>
            <div className="text-sm font-mono text-gray-400">Published</div>
          </div>
        </PixelCard>
        
        <PixelCard className="p-4">
          <div className="text-center">
            <div className="text-2xl font-mono font-bold text-yellow-400">
              {blogPosts.filter(p => p.status === 'draft').length}
            </div>
            <div className="text-sm font-mono text-gray-400">Drafts</div>
          </div>
        </PixelCard>
      </div>

      {/* Filters */}
      {showFilters && (
        <PixelCard className="p-4 space-y-4">
          <h3 className="font-mono font-bold text-white mb-3">Filters & Search</h3>
          
          {/* Search */}
          <div>
            <label className="block font-mono text-sm text-gray-300 mb-2">
              Search posts
            </label>
            <PixelInput
              type="text"
              placeholder="Search by title, content, or excerpt..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Status Filter */}
            <div>
              <label className="block font-mono text-sm text-gray-300 mb-2">
                Status
              </label>
              <PixelSelect
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value as FilterStatus)}
              >
                <option value="all">All Posts</option>
                <option value="draft">Drafts Only</option>
                <option value="published">Published Only</option>
              </PixelSelect>
            </div>

            {/* Sort By */}
            <div>
              <label className="block font-mono text-sm text-gray-300 mb-2">
                Sort By
              </label>
              <PixelSelect
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value as SortBy)}
              >
                <option value="date">Date</option>
                <option value="title">Title</option>
                <option value="status">Status</option>
              </PixelSelect>
            </div>

            {/* Sort Order */}
            <div>
              <label className="block font-mono text-sm text-gray-300 mb-2">
                Order
              </label>
              <PixelSelect
                value={sortOrder}
                onChange={(e) => setSortOrder(e.target.value as SortOrder)}
              >
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
              </PixelSelect>
            </div>
          </div>

          {/* Tag Filters */}
          {allTags.length > 0 && (
            <div>
              <label className="block font-mono text-sm text-gray-300 mb-2">
                Filter by Tags
              </label>
              <div className="flex flex-wrap gap-2">
                {allTags.map(tag => (
                  <button
                    key={tag}
                    onClick={() => handleTagFilter(tag)}
                    className={cn(
                      'px-3 py-1 text-xs font-mono border-2 transition-all duration-200',
                      filterTags.includes(tag)
                        ? 'bg-blue-600 border-blue-800 text-white shadow-[0_2px_0_0_#1e40af]'
                        : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600 hover:border-gray-500'
                    )}
                  >
                    #{tag}
                  </button>
                ))}
              </div>
            </div>
          )}
        </PixelCard>
      )}

      {/* Bulk Operations */}
      {selectedPosts.length > 0 && (
        <PixelCard className="p-4">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div className="font-mono text-sm text-gray-300">
              {selectedPosts.length} post(s) selected
            </div>
            
            <div className="flex gap-2">
              <PixelButton
                variant="success"
                size="sm"
                onClick={handleBulkPublish}
              >
                Publish Selected
              </PixelButton>
              
              <PixelButton
                variant="danger"
                size="sm"
                onClick={handleBulkDelete}
              >
                Delete Selected
              </PixelButton>
              
              <PixelButton
                variant="secondary"
                size="sm"
                onClick={() => setSelectedPosts([])}
              >
                Clear Selection
              </PixelButton>
            </div>
          </div>
        </PixelCard>
      )}

      {/* Posts List */}
      <PixelCard className="overflow-hidden">
        {filteredAndSortedPosts.length === 0 ? (
          <div className="p-8 text-center">
            <div className="text-4xl mb-4">📝</div>
            <h3 className="font-mono text-lg text-gray-300 mb-2">
              {blogPosts.length === 0 ? 'No blog posts yet' : 'No posts match your filters'}
            </h3>
            <p className="font-mono text-sm text-gray-500 mb-4">
              {blogPosts.length === 0 
                ? 'Create your first blog post to get started'
                : 'Try adjusting your search or filter criteria'
              }
            </p>
            {blogPosts.length === 0 && (
              <PixelButton
                variant="primary"
                onClick={handleCreatePost}
              >
                Create First Post
              </PixelButton>
            )}
          </div>
        ) : (
          <div className="divide-y-2 divide-gray-600">
            {/* Header */}
            <div className="p-4 bg-gray-700 border-b-2 border-gray-600">
              <div className="flex items-center gap-4">
                <PixelCheckbox
                  checked={selectedPosts.length === filteredAndSortedPosts.length && filteredAndSortedPosts.length > 0}
                  onChange={(e) => handleSelectAll(e.target.checked)}
                />
                <div className="font-mono text-sm text-gray-300 font-bold">
                  Select All ({filteredAndSortedPosts.length})
                </div>
              </div>
            </div>

            {/* Posts */}
            {filteredAndSortedPosts.map(post => (
              <BlogPostRow
                key={post.id}
                post={post}
                selected={selectedPosts.includes(post.id)}
                onSelect={(selected) => handleSelectPost(post.id, selected)}
                onDelete={() => handleDeletePost(post.id)}
                onPublish={() => handlePublishPost(post.id)}
              />
            ))}
          </div>
        )}
      </PixelCard>
    </div>
  );
};

interface BlogPostRowProps {
  post: BlogPost;
  selected: boolean;
  onSelect: (selected: boolean) => void;
  onDelete: () => void;
  onPublish: () => void;
}

const BlogPostRow: React.FC<BlogPostRowProps> = ({
  post,
  selected,
  onSelect,
  onDelete,
  onPublish
}) => {
  const formatDate = (date: Date) => {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getStatusBadge = (status: BlogPost['status']) => {
    if (status === 'published') {
      return (
        <PixelBadge variant="success" size="sm">
          Published
        </PixelBadge>
      );
    } else {
      return (
        <PixelBadge variant="warning" size="sm">
          Draft
        </PixelBadge>
      );
    }
  };

  return (
    <div className="p-4 hover:bg-gray-700 transition-colors duration-200">
      <div className="flex items-start gap-4">
        {/* Selection checkbox */}
        <div className="pt-1">
          <PixelCheckbox
            checked={selected}
            onChange={(e) => onSelect(e.target.checked)}
          />
        </div>

        {/* Thumbnail */}
        <div className="flex-shrink-0">
          {post.thumbnail ? (
            <img
              src={post.thumbnail}
              alt={post.title.en}
              className="w-16 h-16 object-cover border-2 border-gray-600"
              style={{ imageRendering: 'pixelated' }}
            />
          ) : (
            <div className="w-16 h-16 bg-gray-600 border-2 border-gray-500 flex items-center justify-center">
              <span className="text-gray-400 text-xl">📝</span>
            </div>
          )}
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-2">
            <div className="flex-1 min-w-0">
              <h3 className="font-mono font-bold text-white truncate">
                {post.title.en}
              </h3>
              {post.title.vi !== post.title.en && (
                <h4 className="font-mono text-sm text-gray-400 truncate">
                  {post.title.vi}
                </h4>
              )}
            </div>
            
            <div className="flex items-center gap-2">
              {getStatusBadge(post.status)}
            </div>
          </div>

          <p className="font-mono text-sm text-gray-400 mb-3 line-clamp-2">
            {post.excerpt.en}
          </p>

          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div className="flex flex-wrap items-center gap-2 text-xs font-mono text-gray-500">
              <span>📅 {formatDate(post.publishDate)}</span>
              {post.tags.length > 0 && (
                <>
                  <span>•</span>
                  <div className="flex gap-1">
                    {post.tags.slice(0, 3).map(tag => (
                      <span key={tag} className="text-blue-400">#{tag}</span>
                    ))}
                    {post.tags.length > 3 && (
                      <span className="text-gray-500">+{post.tags.length - 3}</span>
                    )}
                  </div>
                </>
              )}
            </div>

            <div className="flex gap-2">
              <PixelButton
                variant="secondary"
                size="sm"
                onClick={() => handleEditPost(post)}
              >
                Edit
              </PixelButton>
              
              {post.status === 'draft' && (
                <PixelButton
                  variant="success"
                  size="sm"
                  onClick={onPublish}
                >
                  Publish
                </PixelButton>
              )}
              
              <PixelButton
                variant="danger"
                size="sm"
                onClick={onDelete}
              >
                Delete
              </PixelButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
import React from 'react';
import { BlogManager } from '@/components/admin/pages/BlogManager';
import { AdminProvider } from '@/contexts/AdminContext';

/**
 * Demo component to showcase the Blog Management system
 * This demonstrates all the implemented features:
 * - Blog post list with filtering and search
 * - Post status management (draft/published)
 * - Bulk operations
 * - Markdown editor with live preview
 * - Bilingual content support
 * - Image upload for thumbnails
 * - Tag management
 * - Publication workflow
 */
export const BlogDemo: React.FC = () => {
  return (
    <AdminProvider>
      <div className="min-h-screen bg-gray-900 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="mb-6">
            <h1 className="text-3xl font-mono font-bold text-white mb-2">
              Blog Management Demo
            </h1>
            <p className="text-gray-400 font-mono">
              Complete blog management system with CRUD operations, markdown editing, and publication workflow
            </p>
          </div>
          
          <BlogManager />
        </div>
      </div>
    </AdminProvider>
  );
};

export default BlogDemo;
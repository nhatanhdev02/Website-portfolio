import React, { useState, useMemo } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Project } from '@/types/admin';
import { PixelButton, PixelInput, PixelSelect, PixelCard, PixelAlert } from '@/components/admin/ui';
import { ProjectImagePreview } from '@/components/admin/ui/ProjectImagePreview';
import { ProjectForm } from '@/components/admin/forms/ProjectForm';
import { cn } from '@/lib/utils';

interface ProjectCardProps {
  project: Project;
  onEdit: (project: Project) => void;
  onDelete: (id: string) => void;
}

const ProjectCard: React.FC<ProjectCardProps> = ({ project, onEdit, onDelete }) => {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const handleDelete = () => {
    onDelete(project.id);
    setShowDeleteConfirm(false);
  };

  return (
    <PixelCard className="relative group">
      {/* Featured Badge */}
      {project.featured && (
        <div className="absolute -top-2 -right-2 z-10">
          <div className="bg-yellow-500 border-2 border-yellow-700 px-2 py-1 text-xs font-mono font-bold text-black shadow-[0_2px_0_0_#b45309]">
            ⭐ FEATURED
          </div>
        </div>
      )}

      {/* Project Image Preview */}
      <div className="mb-4">
        <ProjectImagePreview
          project={project}
          showHoverEffect={true}
          showGallery={true}
        />
      </div>

      {/* Project Info */}
      <div className="space-y-3">
        <div>
          <h3 className="font-mono font-bold text-white text-lg mb-1">
            {project.title.en}
          </h3>
          <p className="text-sm text-gray-400 font-mono">
            {project.title.vi}
          </p>
        </div>

        <p className="text-sm text-gray-300 line-clamp-3">
          {project.description.en}
        </p>

        {/* Technologies */}
        <div className="flex flex-wrap gap-1">
          {project.technologies.slice(0, 3).map((tech, index) => (
            <span
              key={index}
              className="px-2 py-1 text-xs font-mono bg-blue-600 border border-blue-800 text-white"
            >
              {tech}
            </span>
          ))}
          {project.technologies.length > 3 && (
            <span className="px-2 py-1 text-xs font-mono bg-gray-600 border border-gray-800 text-gray-300">
              +{project.technologies.length - 3}
            </span>
          )}
        </div>

        {/* Category */}
        <div className="flex items-center gap-2">
          <span className="text-xs text-gray-500 font-mono">Category:</span>
          <span className="px-2 py-1 text-xs font-mono bg-gray-700 border border-gray-600 text-gray-300">
            {project.category}
          </span>
        </div>

        {/* Project Link */}
        {project.link && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-gray-500 font-mono">Link:</span>
            <a
              href={project.link}
              target="_blank"
              rel="noopener noreferrer"
              className="text-xs text-blue-400 hover:text-blue-300 font-mono underline"
            >
              View Project
            </a>
          </div>
        )}

        {/* Actions */}
        <div className="flex gap-2 pt-2">
          <PixelButton
            size="sm"
            variant="primary"
            onClick={() => onEdit(project)}
            className="flex-1"
          >
            ✏️ Edit
          </PixelButton>
          <PixelButton
            size="sm"
            variant="danger"
            onClick={() => setShowDeleteConfirm(true)}
          >
            🗑️
          </PixelButton>
        </div>
      </div>

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <PixelCard className="max-w-md mx-4">
            <h3 className="font-mono font-bold text-white text-lg mb-4">
              Delete Project
            </h3>
            <p className="text-gray-300 mb-6">
              Are you sure you want to delete "{project.title.en}"? This action cannot be undone.
            </p>
            <div className="flex gap-3">
              <PixelButton
                variant="danger"
                onClick={handleDelete}
                className="flex-1"
              >
                Delete
              </PixelButton>
              <PixelButton
                variant="secondary"
                onClick={() => setShowDeleteConfirm(false)}
                className="flex-1"
              >
                Cancel
              </PixelButton>
            </div>
          </PixelCard>
        </div>
      )}
    </PixelCard>
  );
};

export const PortfolioManager: React.FC = () => {
  const { projects, deleteProject, lastError, clearError } = useAdmin();
  const [showForm, setShowForm] = useState(false);
  const [editingProject, setEditingProject] = useState<Project | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [technologyFilter, setTechnologyFilter] = useState('');
  const [sortBy, setSortBy] = useState<'order' | 'title' | 'category' | 'featured'>('order');

  // Get unique categories and technologies for filters
  const { categories, technologies } = useMemo(() => {
    const cats = new Set<string>();
    const techs = new Set<string>();
    
    projects.forEach(project => {
      cats.add(project.category);
      project.technologies.forEach(tech => techs.add(tech));
    });
    
    return {
      categories: Array.from(cats).sort(),
      technologies: Array.from(techs).sort()
    };
  }, [projects]);

  // Filter and sort projects
  const filteredProjects = useMemo(() => {
    let filtered = projects.filter(project => {
      const matchesSearch = searchTerm === '' || 
        project.title.en.toLowerCase().includes(searchTerm.toLowerCase()) ||
        project.title.vi.toLowerCase().includes(searchTerm.toLowerCase()) ||
        project.description.en.toLowerCase().includes(searchTerm.toLowerCase()) ||
        project.description.vi.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesCategory = categoryFilter === '' || project.category === categoryFilter;
      
      const matchesTechnology = technologyFilter === '' || 
        project.technologies.some(tech => tech.toLowerCase().includes(technologyFilter.toLowerCase()));
      
      return matchesSearch && matchesCategory && matchesTechnology;
    });

    // Sort projects
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'title':
          return a.title.en.localeCompare(b.title.en);
        case 'category':
          return a.category.localeCompare(b.category);
        case 'featured':
          if (a.featured && !b.featured) return -1;
          if (!a.featured && b.featured) return 1;
          return a.order - b.order;
        case 'order':
        default:
          return a.order - b.order;
      }
    });

    return filtered;
  }, [projects, searchTerm, categoryFilter, technologyFilter, sortBy]);

  const handleAddProject = () => {
    setEditingProject(null);
    setShowForm(true);
  };

  const handleEditProject = (project: Project) => {
    setEditingProject(project);
    setShowForm(true);
  };

  const handleDeleteProject = (id: string) => {
    deleteProject(id);
  };

  const handleFormClose = () => {
    setShowForm(false);
    setEditingProject(null);
  };

  const clearFilters = () => {
    setSearchTerm('');
    setCategoryFilter('');
    setTechnologyFilter('');
    setSortBy('order');
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold font-mono text-white mb-2">
            Portfolio Management
          </h1>
          <p className="text-gray-400 font-mono">
            Manage your portfolio projects, images, and categories
          </p>
        </div>
        <PixelButton onClick={handleAddProject}>
          ➕ Add Project
        </PixelButton>
      </div>

      {/* Error Display */}
      {lastError && (
        <PixelAlert variant="error" onClose={clearError}>
          {lastError.message}
        </PixelAlert>
      )}

      {/* Filters and Search */}
      <PixelCard>
        <div className="space-y-4">
          <h3 className="font-mono font-bold text-white">Filters & Search</h3>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Search */}
            <PixelInput
              placeholder="Search projects..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />

            {/* Category Filter */}
            <PixelSelect
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value)}
            >
              <option value="">All Categories</option>
              {categories.map(category => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </PixelSelect>

            {/* Technology Filter */}
            <PixelInput
              placeholder="Filter by technology..."
              value={technologyFilter}
              onChange={(e) => setTechnologyFilter(e.target.value)}
            />

            {/* Sort By */}
            <PixelSelect
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value as unknown)}
            >
              <option value="order">Sort by Order</option>
              <option value="title">Sort by Title</option>
              <option value="category">Sort by Category</option>
              <option value="featured">Featured First</option>
            </PixelSelect>
          </div>

          <div className="flex items-center justify-between">
            <p className="text-sm text-gray-400 font-mono">
              Showing {filteredProjects.length} of {projects.length} projects
            </p>
            {(searchTerm || categoryFilter || technologyFilter || sortBy !== 'order') && (
              <PixelButton size="sm" variant="secondary" onClick={clearFilters}>
                Clear Filters
              </PixelButton>
            )}
          </div>
        </div>
      </PixelCard>

      {/* Projects Grid */}
      {filteredProjects.length === 0 ? (
        <PixelCard>
          <div className="text-center py-12">
            <div className="text-6xl mb-4">📁</div>
            <h3 className="font-mono font-bold text-white text-xl mb-2">
              {projects.length === 0 ? 'No Projects Yet' : 'No Projects Found'}
            </h3>
            <p className="text-gray-400 mb-6">
              {projects.length === 0 
                ? 'Start building your portfolio by adding your first project.'
                : 'Try adjusting your search criteria or filters.'
              }
            </p>
            {projects.length === 0 && (
              <PixelButton onClick={handleAddProject}>
                ➕ Add Your First Project
              </PixelButton>
            )}
          </div>
        </PixelCard>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredProjects.map((project) => (
            <ProjectCard
              key={project.id}
              project={project}
              onEdit={handleEditProject}
              onDelete={handleDeleteProject}
            />
          ))}
        </div>
      )}

      {/* Project Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 border-2 border-gray-600 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <ProjectForm
              project={editingProject}
              onClose={handleFormClose}
            />
          </div>
        </div>
      )}
    </div>
  );
};
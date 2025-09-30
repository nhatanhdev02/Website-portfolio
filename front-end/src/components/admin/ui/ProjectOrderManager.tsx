import React, { useState, useMemo } from 'react';
import { cn } from '@/lib/utils';
import { PixelButton, PixelInput, PixelCheckbox, PixelCard } from '@/components/admin/ui';
import { Project } from '@/types/admin';

interface ProjectOrderManagerProps {
  currentProject?: Project;
  allProjects: Project[];
  order: number;
  featured: boolean;
  onOrderChange: (order: number) => void;
  onFeaturedChange: (featured: boolean) => void;
  label?: string;
  className?: string;
}

export const ProjectOrderManager: React.FC<ProjectOrderManagerProps> = ({
  currentProject,
  allProjects,
  order,
  featured,
  onOrderChange,
  onFeaturedChange,
  label = "Project Display Settings",
  className
}) => {
  const [showAdvanced, setShowAdvanced] = useState(false);

  // Calculate order statistics and suggestions
  const orderStats = useMemo(() => {
    const otherProjects = allProjects.filter(p => p.id !== currentProject?.id);
    const orders = otherProjects.map(p => p.order).sort((a, b) => a - b);
    const featuredProjects = otherProjects.filter(p => p.featured);
    
    return {
      minOrder: Math.min(0, ...orders),
      maxOrder: Math.max(0, ...orders),
      totalProjects: otherProjects.length,
      featuredCount: featuredProjects.length,
      availableOrders: orders,
      suggestedOrder: {
        first: 0,
        last: (orders.length > 0 ? Math.max(...orders) : 0) + 1,
        afterFeatured: featuredProjects.length > 0 
          ? Math.max(...featuredProjects.map(p => p.order)) + 1 
          : 0
      }
    };
  }, [allProjects, currentProject]);

  // Get projects that would be affected by the current order
  const getOrderContext = () => {
    const otherProjects = allProjects.filter(p => p.id !== currentProject?.id);
    const before = otherProjects.filter(p => p.order < order).slice(-2);
    const after = otherProjects.filter(p => p.order > order).slice(0, 2);
    const atSameOrder = otherProjects.filter(p => p.order === order);
    
    return { before, after, atSameOrder };
  };

  const orderContext = getOrderContext();

  const handleQuickOrder = (newOrder: number) => {
    onOrderChange(newOrder);
  };

  const handleFeaturedToggle = (checked: boolean) => {
    onFeaturedChange(checked);
    
    // If making featured, suggest moving to featured section
    if (checked && orderStats.featuredCount > 0) {
      const suggestedOrder = orderStats.suggestedOrder.afterFeatured;
      if (order > suggestedOrder) {
        onOrderChange(suggestedOrder);
      }
    }
  };

  return (
    <div className={cn('space-y-4', className)}>
      {label && (
        <label className="block text-sm font-medium font-mono text-gray-300">
          {label}
        </label>
      )}

      {/* Featured Toggle */}
      <div className="space-y-2">
        <PixelCheckbox
          checked={featured}
          onChange={handleFeaturedToggle}
          label="Featured Project"
          helperText="Featured projects are highlighted and typically shown first"
        />
        
        {featured && (
          <div className="p-2 bg-yellow-900/20 border border-yellow-600 rounded text-xs font-mono text-yellow-300">
            ⭐ This project will be highlighted as featured
            {orderStats.featuredCount > 0 && (
              <span className="block mt-1 text-yellow-400">
                Currently {orderStats.featuredCount} other featured project{orderStats.featuredCount !== 1 ? 's' : ''}
              </span>
            )}
          </div>
        )}
      </div>

      {/* Order Input */}
      <div className="space-y-2">
        <PixelInput
          label="Display Order"
          type="number"
          min="0"
          value={order}
          onChange={(e) => onOrderChange(parseInt(e.target.value) || 0)}
          helperText="Lower numbers appear first (0 = first position)"
        />

        {/* Quick Order Buttons */}
        <div className="space-y-2">
          <p className="text-xs text-gray-400 font-mono">Quick positioning:</p>
          <div className="flex flex-wrap gap-2">
            <PixelButton
              size="sm"
              variant="secondary"
              onClick={() => handleQuickOrder(orderStats.suggestedOrder.first)}
              disabled={order === orderStats.suggestedOrder.first}
            >
              📍 First
            </PixelButton>
            
            {featured && orderStats.featuredCount > 0 && (
              <PixelButton
                size="sm"
                variant="secondary"
                onClick={() => handleQuickOrder(orderStats.suggestedOrder.afterFeatured)}
                disabled={order === orderStats.suggestedOrder.afterFeatured}
              >
                ⭐ After Featured
              </PixelButton>
            )}
            
            <PixelButton
              size="sm"
              variant="secondary"
              onClick={() => handleQuickOrder(orderStats.suggestedOrder.last)}
              disabled={order === orderStats.suggestedOrder.last}
            >
              📍 Last
            </PixelButton>
            
            <PixelButton
              size="sm"
              variant="secondary"
              onClick={() => setShowAdvanced(!showAdvanced)}
            >
              {showAdvanced ? '📊 Hide' : '📊 Show'} Context
            </PixelButton>
          </div>
        </div>
      </div>

      {/* Order Context */}
      {showAdvanced && (
        <PixelCard className="p-3 space-y-3">
          <h4 className="text-sm font-mono font-bold text-white">Display Order Context</h4>
          
          {/* Current Position Preview */}
          <div className="space-y-2">
            <p className="text-xs text-gray-400 font-mono">Current position preview:</p>
            <div className="space-y-1 text-xs font-mono">
              {/* Before */}
              {orderContext.before.map((project, index) => (
                <div key={project.id} className="flex items-center gap-2 text-gray-500">
                  <span className="w-6 text-right">{project.order}</span>
                  <span className="flex-1">{project.title.en}</span>
                  {project.featured && <span className="text-yellow-400">⭐</span>}
                </div>
              ))}
              
              {/* Current */}
              <div className="flex items-center gap-2 text-blue-300 bg-blue-900/20 px-1 py-0.5 border border-blue-600">
                <span className="w-6 text-right font-bold">{order}</span>
                <span className="flex-1 font-bold">
                  {currentProject?.title.en || 'Current Project'}
                </span>
                {featured && <span className="text-yellow-400">⭐</span>}
              </div>
              
              {/* Same Order Warning */}
              {orderContext.atSameOrder.length > 0 && (
                <div className="text-yellow-400 bg-yellow-900/20 px-1 py-0.5 border border-yellow-600">
                  ⚠ {orderContext.atSameOrder.length} other project{orderContext.atSameOrder.length !== 1 ? 's' : ''} at order {order}
                </div>
              )}
              
              {/* After */}
              {orderContext.after.map((project, index) => (
                <div key={project.id} className="flex items-center gap-2 text-gray-500">
                  <span className="w-6 text-right">{project.order}</span>
                  <span className="flex-1">{project.title.en}</span>
                  {project.featured && <span className="text-yellow-400">⭐</span>}
                </div>
              ))}
            </div>
          </div>

          {/* Statistics */}
          <div className="grid grid-cols-2 gap-4 text-xs font-mono">
            <div>
              <p className="text-gray-400">Total Projects:</p>
              <p className="text-white font-bold">{orderStats.totalProjects + 1}</p>
            </div>
            <div>
              <p className="text-gray-400">Featured Projects:</p>
              <p className="text-white font-bold">
                {orderStats.featuredCount + (featured ? 1 : 0)}
              </p>
            </div>
            <div>
              <p className="text-gray-400">Order Range:</p>
              <p className="text-white font-bold">
                {orderStats.minOrder} - {Math.max(orderStats.maxOrder, order)}
              </p>
            </div>
            <div>
              <p className="text-gray-400">Current Position:</p>
              <p className="text-white font-bold">
                {allProjects.filter(p => p.id !== currentProject?.id && p.order < order).length + 1} / {orderStats.totalProjects + 1}
              </p>
            </div>
          </div>

          {/* Recommendations */}
          <div className="space-y-2">
            <p className="text-xs text-gray-400 font-mono">Recommendations:</p>
            <div className="space-y-1 text-xs font-mono">
              {featured && order > orderStats.suggestedOrder.afterFeatured && (
                <div className="text-yellow-400">
                  💡 Consider moving featured project earlier (order {orderStats.suggestedOrder.afterFeatured})
                </div>
              )}
              {orderContext.atSameOrder.length > 0 && (
                <div className="text-orange-400">
                  ⚠ Multiple projects at same order may cause inconsistent sorting
                </div>
              )}
              {order > orderStats.maxOrder + 10 && (
                <div className="text-blue-400">
                  💡 Large gap in order numbers - consider using {orderStats.maxOrder + 1}
                </div>
              )}
            </div>
          </div>
        </PixelCard>
      )}
    </div>
  );
};
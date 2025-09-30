import React, { useState } from 'react';
import { cn } from '@/lib/utils';
import { Project } from '@/types/admin';

interface ProjectImagePreviewProps {
  project: Project;
  className?: string;
  showHoverEffect?: boolean;
  showGallery?: boolean;
}

export const ProjectImagePreview: React.FC<ProjectImagePreviewProps> = ({
  project,
  className,
  showHoverEffect = true,
  showGallery = true
}) => {
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [showFullGallery, setShowFullGallery] = useState(false);
  
  const allImages = [project.image, ...(project.images || [])].filter(Boolean);
  const currentImage = allImages[currentImageIndex] || project.image;

  const nextImage = () => {
    setCurrentImageIndex((prev) => (prev + 1) % allImages.length);
  };

  const prevImage = () => {
    setCurrentImageIndex((prev) => (prev - 1 + allImages.length) % allImages.length);
  };

  return (
    <div className={cn('relative group', className)}>
      {/* Main Image Container */}
      <div className="relative overflow-hidden border-2 border-gray-600 rounded-lg bg-gray-800">
        <div className="aspect-video relative">
          <img
            src={currentImage}
            alt={project.title.en}
            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          />
          
          {/* Pixel Art Overlay Effect (matching frontend) */}
          {showHoverEffect && (
            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
          )}

          {/* Project Info Overlay (matching frontend hover) */}
          {showHoverEffect && (
            <div className="absolute inset-0 flex flex-col justify-end p-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <div className="space-y-2">
                <h3 className="font-mono font-bold text-white text-lg">
                  {project.title.en}
                </h3>
                <p className="text-sm text-gray-200 line-clamp-2">
                  {project.description.en}
                </p>
                
                {/* Technologies */}
                <div className="flex flex-wrap gap-1">
                  {project.technologies.slice(0, 3).map((tech, index) => (
                    <span
                      key={index}
                      className="px-2 py-1 text-xs font-mono bg-blue-600/80 border border-blue-500 text-white backdrop-blur-sm"
                    >
                      {tech}
                    </span>
                  ))}
                  {project.technologies.length > 3 && (
                    <span className="px-2 py-1 text-xs font-mono bg-gray-600/80 border border-gray-500 text-gray-200 backdrop-blur-sm">
                      +{project.technologies.length - 3}
                    </span>
                  )}
                </div>

                {/* Project Link */}
                {project.link && (
                  <div className="flex items-center gap-2">
                    <a
                      href={project.link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 px-3 py-1 bg-green-600/80 border border-green-500 text-white text-xs font-mono hover:bg-green-500/80 transition-colors backdrop-blur-sm"
                      onClick={(e) => e.stopPropagation()}
                    >
                      🔗 View Live
                    </a>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Gallery Navigation */}
          {showGallery && allImages.length > 1 && (
            <>
              {/* Previous Button */}
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  prevImage();
                }}
                className="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 border-2 border-gray-600 text-white p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 hover:bg-black/70"
              >
                ◀
              </button>

              {/* Next Button */}
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  nextImage();
                }}
                className="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 border-2 border-gray-600 text-white p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 hover:bg-black/70"
              >
                ▶
              </button>

              {/* Image Counter */}
              <div className="absolute bottom-2 right-2 bg-black/50 border border-gray-600 px-2 py-1 text-xs font-mono text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                {currentImageIndex + 1} / {allImages.length}
              </div>

              {/* Gallery Dots */}
              <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                {allImages.map((_, index) => (
                  <button
                    key={index}
                    onClick={(e) => {
                      e.stopPropagation();
                      setCurrentImageIndex(index);
                    }}
                    className={cn(
                      'w-2 h-2 border border-gray-400 transition-colors',
                      index === currentImageIndex
                        ? 'bg-white'
                        : 'bg-gray-600 hover:bg-gray-400'
                    )}
                  />
                ))}
              </div>
            </>
          )}

          {/* Featured Badge */}
          {project.featured && (
            <div className="absolute top-2 left-2">
              <div className="bg-yellow-500 border-2 border-yellow-700 px-2 py-1 text-xs font-mono font-bold text-black shadow-[0_2px_0_0_#b45309]">
                ⭐ FEATURED
              </div>
            </div>
          )}

          {/* Gallery Button */}
          {showGallery && allImages.length > 1 && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                setShowFullGallery(true);
              }}
              className="absolute top-2 right-2 bg-black/50 border border-gray-600 text-white px-2 py-1 text-xs font-mono opacity-0 group-hover:opacity-100 transition-opacity duration-300 hover:bg-black/70"
            >
              📷 Gallery
            </button>
          )}
        </div>
      </div>

      {/* Full Gallery Modal */}
      {showFullGallery && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
          <div className="relative max-w-4xl w-full">
            {/* Close Button */}
            <button
              onClick={() => setShowFullGallery(false)}
              className="absolute -top-12 right-0 bg-red-600 border-2 border-red-800 text-white px-3 py-1 font-mono hover:bg-red-500 transition-colors"
            >
              ✕ Close
            </button>

            {/* Gallery Grid */}
            <div className="bg-gray-800 border-2 border-gray-600 rounded-lg p-4">
              <h3 className="font-mono font-bold text-white text-lg mb-4">
                {project.title.en} - Gallery
              </h3>
              
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                {allImages.map((image, index) => (
                  <div
                    key={index}
                    className={cn(
                      'relative aspect-video border-2 cursor-pointer transition-all duration-200',
                      index === currentImageIndex
                        ? 'border-blue-500 shadow-[0_0_0_2px_#3b82f6]'
                        : 'border-gray-600 hover:border-gray-500'
                    )}
                    onClick={() => {
                      setCurrentImageIndex(index);
                      setShowFullGallery(false);
                    }}
                  >
                    <img
                      src={image}
                      alt={`${project.title.en} - Image ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                    {index === 0 && (
                      <div className="absolute top-1 left-1 bg-blue-600 border border-blue-800 px-1 py-0.5 text-xs font-mono text-white">
                        PRIMARY
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
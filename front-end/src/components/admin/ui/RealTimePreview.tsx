import React, { useState, useEffect } from 'react';
import { useSharedData } from '@/contexts/SharedDataContext';
import { useLanguage } from '@/hooks/useLanguage';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { Eye, EyeOff, Monitor, Smartphone, Tablet, RefreshCw } from 'lucide-react';
import {
  HeroContent,
  AboutContent,
  Service,
  Project,
  BlogPost,
  ContactInfo,
  SystemSettings
} from '@/types/admin';

interface RealTimePreviewProps {
  previewData?: Partial<{
    heroContent: HeroContent;
    aboutContent: AboutContent;
    services: Service[];
    projects: Project[];
    blogPosts: BlogPost[];
    contactInfo: ContactInfo;
    systemSettings: SystemSettings;
  }>;
  onPreviewToggle?: (enabled: boolean) => void;
  className?: string;
}

type DeviceType = 'desktop' | 'tablet' | 'mobile';

export const RealTimePreview: React.FC<RealTimePreviewProps> = ({
  previewData,
  onPreviewToggle,
  className = ''
}) => {
  const { 
    previewMode, 
    setPreviewMode, 
    setPreviewData, 
    clearPreview,
    refreshData 
  } = useSharedData();
  const { language } = useLanguage();
  
  const [deviceType, setDeviceType] = useState<DeviceType>('desktop');
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Update preview data when props change
  useEffect(() => {
    if (previewData && Object.keys(previewData).length > 0) {
      setPreviewData(previewData);
      if (!previewMode) {
        setPreviewMode(true);
      }
    }
  }, [previewData, setPreviewData, setPreviewMode, previewMode]);

  const handlePreviewToggle = () => {
    const newPreviewMode = !previewMode;
    setPreviewMode(newPreviewMode);
    
    if (!newPreviewMode) {
      clearPreview();
    } else if (previewData) {
      setPreviewData(previewData);
    }
    
    onPreviewToggle?.(newPreviewMode);
  };

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      refreshData();
      // Small delay to show the refresh animation
      await new Promise(resolve => setTimeout(resolve, 500));
    } finally {
      setIsRefreshing(false);
    }
  };

  const getDeviceClass = () => {
    switch (deviceType) {
      case 'mobile':
        return 'w-80 h-96';
      case 'tablet':
        return 'w-96 h-72';
      case 'desktop':
      default:
        return 'w-full h-96';
    }
  };

  const getDeviceIcon = (type: DeviceType) => {
    switch (type) {
      case 'mobile':
        return <Smartphone className="w-4 h-4" />;
      case 'tablet':
        return <Tablet className="w-4 h-4" />;
      case 'desktop':
      default:
        return <Monitor className="w-4 h-4" />;
    }
  };

  return (
    <PixelCard className={`${className}`}>
      <div className="p-4">
        {/* Preview Controls */}
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <h3 className="font-pixel text-lg font-bold text-foreground">
              Real-time Preview
            </h3>
            {previewMode && (
              <span className="px-2 py-1 bg-primary/20 text-primary text-xs font-pixel rounded border border-primary/30">
                PREVIEW MODE
              </span>
            )}
          </div>
          
          <div className="flex items-center gap-2">
            {/* Device Type Selector */}
            <div className="flex border border-border rounded overflow-hidden">
              {(['desktop', 'tablet', 'mobile'] as DeviceType[]).map((type) => (
                <button
                  key={type}
                  onClick={() => setDeviceType(type)}
                  className={`p-2 transition-colors ${
                    deviceType === type
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-background hover:bg-muted text-muted-foreground'
                  }`}
                  title={`${type.charAt(0).toUpperCase() + type.slice(1)} view`}
                >
                  {getDeviceIcon(type)}
                </button>
              ))}
            </div>

            {/* Refresh Button */}
            <PixelButton
              variant="secondary"
              size="sm"
              onClick={handleRefresh}
              disabled={isRefreshing}
              className="p-2"
              title="Refresh preview"
            >
              <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
            </PixelButton>

            {/* Preview Toggle */}
            <PixelButton
              variant={previewMode ? "primary" : "secondary"}
              size="sm"
              onClick={handlePreviewToggle}
              className="flex items-center gap-2"
            >
              {previewMode ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              {previewMode ? 'Exit Preview' : 'Enable Preview'}
            </PixelButton>
          </div>
        </div>

        {/* Preview Frame */}
        <div className="border-2 border-border rounded-lg overflow-hidden bg-muted/30">
          <div className={`mx-auto transition-all duration-300 ${getDeviceClass()}`}>
            <iframe
              src={`${window.location.origin}?preview=${previewMode}&lang=${language}`}
              className="w-full h-full border-0"
              title="Frontend Preview"
              sandbox="allow-scripts allow-same-origin"
            />
          </div>
        </div>

        {/* Preview Info */}
        {previewMode && (
          <div className="mt-4 p-3 bg-primary/10 border border-primary/30 rounded">
            <div className="flex items-start gap-2">
              <Eye className="w-4 h-4 text-primary mt-0.5 flex-shrink-0" />
              <div className="text-sm font-pixel text-primary">
                <p className="font-bold mb-1">Preview Mode Active</p>
                <p className="text-xs opacity-80">
                  Changes are being previewed in real-time. The actual website will update when you save your changes.
                </p>
                {previewData && Object.keys(previewData).length > 0 && (
                  <div className="mt-2">
                    <p className="text-xs opacity-80">
                      Previewing changes to: {Object.keys(previewData).join(', ')}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Device Info */}
        <div className="mt-2 text-xs font-pixel text-muted-foreground text-center">
          Viewing in {deviceType} mode • Language: {language.toUpperCase()}
        </div>
      </div>
    </PixelCard>
  );
};

// Simplified preview component for inline use
export const InlinePreview: React.FC<{
  data: any;
  type: 'hero' | 'about' | 'service' | 'project' | 'blog';
  language?: 'vi' | 'en';
  className?: string;
}> = ({ data, type, language = 'vi', className = '' }) => {
  const renderPreview = () => {
    switch (type) {
      case 'hero':
        return (
          <div className="text-center p-4 bg-gradient-to-br from-primary/20 to-secondary/20 rounded">
            <h1 className="font-display text-2xl font-bold mb-2">
              {data.greeting?.[language]} <span className="text-primary">{data.name}</span>
            </h1>
            <h2 className="font-pixel text-lg text-primary mb-2">{data.title?.[language]}</h2>
            <p className="font-pixel text-sm text-muted-foreground mb-3">{data.subtitle?.[language]}</p>
            <button className="px-4 py-2 bg-primary text-primary-foreground font-pixel text-sm rounded">
              {data.ctaText?.[language]}
            </button>
          </div>
        );

      case 'about':
        return (
          <div className="flex gap-4 p-4 bg-muted/30 rounded">
            {data.profileImage && (
              <img 
                src={data.profileImage} 
                alt="Profile" 
                className="w-16 h-16 rounded object-cover flex-shrink-0"
              />
            )}
            <div>
              <p className="font-pixel text-sm mb-2">{data.description?.[language]}</p>
              <p className="font-pixel text-xs text-muted-foreground">{data.experience?.[language]}</p>
            </div>
          </div>
        );

      case 'service':
        return (
          <div className="p-4 border border-border rounded" style={{ backgroundColor: data.bgColor }}>
            <div className="flex items-center gap-2 mb-2">
              {data.icon && <span className="text-lg">{data.icon}</span>}
              <h3 className="font-pixel font-bold" style={{ color: data.color }}>
                {data.title?.[language]}
              </h3>
            </div>
            <p className="font-pixel text-sm text-muted-foreground">
              {data.description?.[language]}
            </p>
          </div>
        );

      case 'project':
        return (
          <div className="border border-border rounded overflow-hidden">
            {data.image && (
              <img 
                src={data.image} 
                alt={data.title?.[language]} 
                className="w-full h-32 object-cover"
              />
            )}
            <div className="p-3">
              <h3 className="font-pixel font-bold mb-1">{data.title?.[language]}</h3>
              <p className="font-pixel text-xs text-muted-foreground mb-2">
                {data.description?.[language]}
              </p>
              {data.technologies && (
                <div className="flex flex-wrap gap-1">
                  {data.technologies.slice(0, 3).map((tech: string, index: number) => (
                    <span 
                      key={index}
                      className="px-2 py-1 bg-primary/20 text-primary text-xs font-pixel rounded"
                    >
                      {tech}
                    </span>
                  ))}
                </div>
              )}
            </div>
          </div>
        );

      case 'blog':
        return (
          <div className="border border-border rounded overflow-hidden">
            {data.thumbnail && (
              <img 
                src={data.thumbnail} 
                alt={data.title?.[language]} 
                className="w-full h-24 object-cover"
              />
            )}
            <div className="p-3">
              <h3 className="font-pixel font-bold mb-1">{data.title?.[language]}</h3>
              <p className="font-pixel text-xs text-muted-foreground mb-2">
                {data.excerpt?.[language]}
              </p>
              {data.tags && (
                <div className="flex flex-wrap gap-1">
                  {data.tags.slice(0, 2).map((tag: string, index: number) => (
                    <span 
                      key={index}
                      className="px-2 py-1 bg-secondary/20 text-secondary text-xs font-pixel rounded"
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              )}
            </div>
          </div>
        );

      default:
        return (
          <div className="p-4 bg-muted/30 rounded text-center">
            <p className="font-pixel text-sm text-muted-foreground">Preview not available</p>
          </div>
        );
    }
  };

  return (
    <div className={`${className}`}>
      <div className="mb-2 flex items-center gap-2">
        <Eye className="w-4 h-4 text-primary" />
        <span className="font-pixel text-sm font-bold text-primary">Preview</span>
      </div>
      {renderPreview()}
    </div>
  );
};